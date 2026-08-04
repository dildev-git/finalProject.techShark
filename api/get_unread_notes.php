<?php
session_start();
include('../includes/dbconnection.php');

if (!isset($conn) || !$conn || !isset($_SESSION['user_id']) || $_SESSION['role'] === 'Customer') {
    echo json_encode(['count' => 0]);
    exit;
}

header('Content-Type: application/json');

$user_id   = (int)$_SESSION['user_id'];
$user_role = $_SESSION['role'];
$count = 0;

if ($user_role === 'Administrator') {
    $last_viewed = null;

    if (isset($_SESSION['notes_last_viewed'])) {
        // If the session exists, use it.
        $last_viewed = $_SESSION['notes_last_viewed'];
    } elseif (isset($_COOKIE['admin_notes_last_viewed'])) {
        // if not session get from cookie(putting it back into the session)
        $last_viewed = $_COOKIE['admin_notes_last_viewed'];
        $_SESSION['notes_last_viewed'] = $last_viewed; 
    }

    // We are not creating new cookies here! (That is only done in staff dashboard.php)

    if ($last_viewed) {
        $last_viewed_esc = mysqli_real_escape_string($conn, $last_viewed);
        $count_res = mysqli_query($conn, "SELECT COUNT(*) as c FROM Staff_Notes WHERE createdAt > '$last_viewed_esc'");
        $count = $count_res ? (int)mysqli_fetch_assoc($count_res)['c'] : 0;
    } else {
        // If there is no cookie, it shows all the numbers (because I haven't checked Notes even once yet).
        $count_res = mysqli_query($conn, "SELECT COUNT(*) as c FROM Staff_Notes");
        $count = $count_res ? (int)mysqli_fetch_assoc($count_res)['c'] : 0;
    }

} else {
    // Code for general staff
    $res = mysqli_query($conn, "SELECT last_note_viewed_at FROM Staff WHERE staffID = $user_id");
    $row = $res ? mysqli_fetch_assoc($res) : null;
    
    if ($row && $row['last_note_viewed_at']) {
        $last_viewed_esc = mysqli_real_escape_string($conn, $row['last_note_viewed_at']);
        $count_res = mysqli_query($conn, "SELECT COUNT(*) as c FROM Staff_Notes WHERE createdAt > '$last_viewed_esc'");
        $count = $count_res ? (int)mysqli_fetch_assoc($count_res)['c'] : 0;
    } else {
        $count_res = mysqli_query($conn, "SELECT COUNT(*) as c FROM Staff_Notes");
        $count = $count_res ? (int)mysqli_fetch_assoc($count_res)['c'] : 0;
    }
}

echo json_encode(['count' => $count]);
?>