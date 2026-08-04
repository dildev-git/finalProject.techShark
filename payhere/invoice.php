<?php
/**
 * invoice.php  –  PayHere PDF Invoice Generator
 *
 * Generates a downloadable PDF invoice for a paid order.
 * Requires: FPDF 1.84  at  payhere/lib/fpdf.php
 *
 * Usage: /payhere/invoice.php?order_id=5
 */

session_start();
require_once '../includes/dbconnection.php';

/** @var mysqli $conn Provided by dbconnection.php */

// ── Auth guard ─────────────────────────────────────────────────────────────────
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Customer') {
    header('Location: ../login.php');
    exit;
}

$customer_id = (int) $_SESSION['user_id'];
$order_id    = (int) ($_GET['order_id'] ?? 0);

if ($order_id <= 0) {
    die('Invalid order ID.');
}

// ── Fetch order — must belong to the logged-in customer ───────────────────────
$stmt = mysqli_prepare($conn,
    'SELECT o.orderID, o.orderDate, o.totalAmount, o.status,
            c.fullName, c.email, c.contactNo, c.address, c.city
     FROM `Order` o
     JOIN Customer c ON o.customerID = c.customerID
     WHERE o.orderID = ? AND o.customerID = ?');
mysqli_stmt_bind_param($stmt, 'ii', $order_id, $customer_id);
mysqli_stmt_execute($stmt);
$order = mysqli_stmt_get_result($stmt)->fetch_assoc();
mysqli_stmt_close($stmt);

if (!$order) {
    die('Order not found or access denied.');
}

// ── Fetch order items ──────────────────────────────────────────────────────────
$stmt = mysqli_prepare($conn,
    'SELECT od.quantity, od.unitPrice, p.productName
     FROM Order_Details od
     JOIN Product p ON od.productID = p.productID
     WHERE od.orderID = ?');
mysqli_stmt_bind_param($stmt, 'i', $order_id);
mysqli_stmt_execute($stmt);
$items = mysqli_stmt_get_result($stmt)->fetch_all(MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

if (empty($items)) {
    die('No items found for this order.');
}

// ── Fetch payment info ─────────────────────────────────────────────────────────
$stmt = mysqli_prepare($conn,
    'SELECT method, status, paymentDate FROM Payment WHERE orderID = ? ORDER BY paymentID DESC LIMIT 1');
mysqli_stmt_bind_param($stmt, 'i', $order_id);
mysqli_stmt_execute($stmt);
$payment = mysqli_stmt_get_result($stmt)->fetch_assoc();
mysqli_stmt_close($stmt);

// ── Load FPDF ──────────────────────────────────────────────────────────────────
define('FPDF_FONTPATH', __DIR__ . '/lib/font/');
require_once __DIR__ . '/lib/fpdf.php';

// ── Helper: convert UTF-8 string to Latin-1 for FPDF ──────────────────────────
// FPDF 1.84 uses Latin-1 encoding internally.
// On PHP 8.2+ utf8_encode() is removed — we use mb_convert_encoding instead.
function to_latin(string $str): string {
    return mb_convert_encoding($str, 'ISO-8859-1', 'UTF-8');
}

// ── Derived values ─────────────────────────────────────────────────────────────
$order_ref  = '#ORD-' . str_pad($order_id, 4, '0', STR_PAD_LEFT);
$is_paid    = strtolower($order['status']) === 'paid' || strtolower($payment['status'] ?? '') === 'paid' || strtolower($payment['status'] ?? '') === 'completed';
$status_txt = strtoupper($payment['status'] ?? 'PENDING');

// ── PDF subclass with branded header / footer ─────────────────────────────────
class TechSharkInvoice extends FPDF
{
    public string $order_ref  = '';
    public bool   $is_paid    = false;
    public string $status_txt = '';

    public function Header(): void
    {
        // Dark navy background strip
        $this->SetFillColor(15, 23, 42);
        $this->Rect(0, 0, 210, 36, 'F');

        // Brand name
        $this->SetFont('Helvetica', 'B', 22);
        $this->SetTextColor(255, 255, 255);
        $this->SetXY(14, 8);
        $this->Cell(90, 10, 'TECH SHARK', 0, 0, 'L');

        // Tag line
        $this->SetFont('Helvetica', '', 8);
        $this->SetXY(14, 21);
        $this->Cell(90, 5, 'techshark.lk  |  support@techshark.lk', 0, 0, 'L');

        // "INVOICE" label (emerald green)
        $this->SetFont('Helvetica', 'B', 26);
        $this->SetTextColor(16, 185, 129);
        $this->SetXY(110, 7);
        $this->Cell(86, 12, 'INVOICE', 0, 0, 'R');

        // PAID / PENDING badge (No unicode characters to avoid FPDF crash)
        if ($this->is_paid) {
            $this->SetFillColor(5, 150, 105);       // emerald
        } else {
            $this->SetFillColor(217, 119, 6);        // amber
        }
        $this->SetTextColor(255, 255, 255);
        $this->SetFont('Helvetica', 'B', 9);
        $this->SetXY(152, 22);
        $badge_text = $this->is_paid ? '  PAID  ' : 'PENDING';
        $this->Cell(44, 8, $badge_text, 0, 0, 'C', true);

        // Reset text color for body
        $this->SetTextColor(0, 0, 0);
        $this->SetY(44);
    }

    public function Footer(): void
    {
        $this->SetY(-16);
        $this->SetFillColor(15, 23, 42);
        $this->Rect(0, $this->GetY(), 210, 18, 'F');
        $this->SetTextColor(148, 163, 184);
        $this->SetFont('Helvetica', '', 8);
        $y = $this->GetY();
        $this->SetXY(0, $y);
        $this->Cell(0, 18,
            'Thank you for shopping with Tech Shark  |  Page ' . $this->PageNo(),
            0, 0, 'C');
    }
}

// ── Build PDF ─────────────────────────────────────────────────────────────────
$pdf = new TechSharkInvoice('P', 'mm', 'A4');
$pdf->order_ref  = $order_ref;
$pdf->is_paid    = $is_paid;
$pdf->status_txt = $status_txt;
$pdf->SetAutoPageBreak(true, 22);
$pdf->SetMargins(14, 14, 14);
$pdf->AddPage();

// ── Section: Bill To / Invoice Details ────────────────────────────────────────
$pdf->SetFont('Helvetica', 'B', 9);
$pdf->SetTextColor(100, 116, 139);
$pdf->Cell(95, 6, 'BILL TO', 0, 0, 'L');
$pdf->Cell(95, 6, 'INVOICE DETAILS', 0, 1, 'R');

$pdf->SetFont('Helvetica', '', 10);
$pdf->SetTextColor(15, 23, 42);

$rows = [
    [to_latin($order['fullName']),                         'Invoice No: ' . $order_ref],
    [to_latin($order['email']),                            'Date: ' . date('d M Y', strtotime($order['orderDate']))],
    [to_latin($order['contactNo'] ?? 'N/A'),               'Payment: ' . to_latin($payment['method'] ?? 'N/A')],
    [to_latin(($order['address'] ?? '') . ', ' . ($order['city'] ?? '')), 'Payment Status: ' . $status_txt],
];

foreach ($rows as $row) {
    $pdf->Cell(95, 5.5, $row[0], 0, 0, 'L');
    $pdf->Cell(95, 5.5, $row[1], 0, 1, 'R');
}

$pdf->Ln(5);

// ── Divider ───────────────────────────────────────────────────────────────────
$pdf->SetDrawColor(203, 213, 225);
$pdf->SetLineWidth(0.4);
$pdf->Line(14, $pdf->GetY(), 196, $pdf->GetY());
$pdf->Ln(5);

// ── Items table header ────────────────────────────────────────────────────────
$pdf->SetFillColor(15, 23, 42);
$pdf->SetTextColor(255, 255, 255);
$pdf->SetFont('Helvetica', 'B', 10);

$pdf->Cell(87, 9, 'Product', 1, 0, 'L', true);
$pdf->Cell(23, 9, 'Qty',     1, 0, 'C', true);
$pdf->Cell(40, 9, 'Unit Price (LKR)', 1, 0, 'R', true);
$pdf->Cell(36, 9, 'Total (LKR)',      1, 1, 'R', true);

// ── Items rows ────────────────────────────────────────────────────────────────
$pdf->SetFont('Helvetica', '', 10);
$alt = false;
foreach ($items as $item) {
    $line_total = $item['unitPrice'] * $item['quantity'];
    // Alternate row shading
    if ($alt) {
        $pdf->SetFillColor(241, 245, 249);
    } else {
        $pdf->SetFillColor(255, 255, 255);
    }
    $pdf->SetTextColor(15, 23, 42);

    $pdf->Cell(87, 8.5, to_latin($item['productName']),           1, 0, 'L', true);
    $pdf->Cell(23, 8.5, (string)$item['quantity'],                1, 0, 'C', true);
    $pdf->Cell(40, 8.5, number_format($item['unitPrice'],  2),    1, 0, 'R', true);
    $pdf->Cell(36, 8.5, number_format($line_total,         2),    1, 1, 'R', true);
    $alt = !$alt;
}

// ── Total row ─────────────────────────────────────────────────────────────────
$pdf->Ln(4);
$pdf->SetFillColor(15, 23, 42);
$pdf->SetTextColor(255, 255, 255);
$pdf->SetFont('Helvetica', 'B', 11);
$pdf->SetX(128);
$pdf->Cell(40, 11, 'TOTAL AMOUNT', 1, 0, 'L', true);
$pdf->Cell(28, 11, 'LKR ' . number_format($order['totalAmount'], 2), 1, 1, 'R', true);

// ── Notes ─────────────────────────────────────────────────────────────────────
$pdf->Ln(10);
$pdf->SetFont('Helvetica', 'I', 8.5);
$pdf->SetTextColor(100, 116, 139);
$note = "This is a computer-generated invoice and does not require a physical signature.\n"
      . "For support: support@techshark.lk  |  Tel: +94 11 234 5678";
$pdf->MultiCell(0, 5, $note, 0, 'C');

// ── Send PDF as download ──────────────────────────────────────────────────────
// ob_clean() ensures no stray whitespace/output breaks the PDF stream
if (ob_get_level()) {
    ob_end_clean();
}

$filename = 'TechShark_Invoice_' . str_pad($order_id, 4, '0', STR_PAD_LEFT) . '.pdf';
$pdf->Output('D', $filename);   // 'D' = force browser download
exit;
