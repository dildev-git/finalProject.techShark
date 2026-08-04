document.addEventListener('DOMContentLoaded', function () {

    // --- 1. Order Search Filter ---
    const orderSearch = document.getElementById('orderSearch');
    if (orderSearch) {
        orderSearch.addEventListener('input', function () {
            const q = this.value.toLowerCase();
            document.querySelectorAll('.order-panel').forEach(panel => {
                const rows = panel.querySelectorAll('tbody tr.order-row');
                if (!rows.length) return;

                let visible = 0;
                rows.forEach(row => {
                    const searchAttr = row.getAttribute('data-search') || '';
                    const text = row.textContent.toLowerCase();
                    const show = searchAttr.includes(q) || text.includes(q);

                    row.style.display = show ? '' : 'none';
                    if (show) visible++;
                });

                // If there are no rows, hide the entire panel (Status block).
                panel.style.display = (!q || visible > 0) ? '' : 'none';
            });
        });
    }

    // --- 2. Order Details Modal Logic ---
    const modal = document.getElementById('orderModal');
    const closeBtn = document.getElementById('closeOrderModal');
    const viewBtns = document.querySelectorAll('.btn-view-order');

    viewBtns.forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            const oid = this.getAttribute('data-id');

            // Using window.orders data sent from PHP
            if (!window.ordersData || !window.ordersData[oid]) return;
            const order = window.ordersData[oid];

            const formattedOid = 'ORD-' + String(oid).padStart(4, '0');
            document.getElementById('mod_oid').innerText = '#' + formattedOid;
            document.getElementById('mod_cname').innerText = order.customerName || 'N/A';
            document.getElementById('mod_cemail').innerText = order.customerEmail || 'N/A';
            document.getElementById('mod_cphone').innerText = order.customerPhone || 'N/A';
            document.getElementById('mod_caddr').innerText = order.customerAddress || 'N/A';
            document.getElementById('mod_ccity').innerText = order.customerCity || 'N/A';

            document.getElementById('mod_pmethod').innerText = order.paymentMethod || 'N/A';
            document.getElementById('mod_pstatus').innerText = order.paymentStatus || 'N/A';
            document.getElementById('mod_pdate').innerText = order.paymentDate || 'N/A';
            document.getElementById('mod_ostatus').innerText = order.status || 'N/A';

            document.getElementById('mod_total').innerText = parseFloat(order.totalAmount).toLocaleString('en-US', { minimumFractionDigits: 2 });

            // Putting the items related to the order into the table
            const tbody = document.getElementById('mod_items_body');
            tbody.innerHTML = '';

            if (order.items && order.items.length > 0) {
                order.items.forEach(item => {
                    const subtotal = item.quantity * item.unitPrice;
                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td style="padding:10px;border-bottom:1px solid #e2e8f0;">${item.productName}</td>
                        <td style="padding:10px;border-bottom:1px solid #e2e8f0;text-align:center;">${item.quantity}</td>
                        <td style="padding:10px;border-bottom:1px solid #e2e8f0;text-align:right;">${parseFloat(item.unitPrice).toLocaleString('en-US', { minimumFractionDigits: 2 })}</td>
                        <td style="padding:10px;border-bottom:1px solid #e2e8f0;text-align:right;">${subtotal.toLocaleString('en-US', { minimumFractionDigits: 2 })}</td>
                    `;
                    tbody.appendChild(tr);
                });
            } else {
                tbody.innerHTML = '<tr><td colspan="4" style="text-align:center;padding:10px;">No items found.</td></tr>';
            }

            modal.style.display = 'flex';
        });
    });

    // Close the Modal
    if (closeBtn) {
        closeBtn.addEventListener('click', () => modal.style.display = 'none');
    }

    if (modal) {
        modal.addEventListener('click', function (e) {
            if (e.target === this) this.style.display = 'none';
        });
    }
});