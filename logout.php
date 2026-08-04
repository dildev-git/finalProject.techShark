<?php
/**
 * =============================================================================
 * logout.php — Session Termination
 * =============================================================================
 * Destroys the active session, clears the "remember me" cookie if set,
 * and redirects the user to the login page with a ?logout=1 flag so the
 * login page can display a "You have been logged out" message if desired.
 *
 * This file contains no HTML — it performs a redirect and exits immediately.
 * =============================================================================
 */

session_start();

// Clear all session variables
$_SESSION = array();

// Destroy the server-side session
session_destroy();

// Remove the "remember me" cookie if it was set at login
if (isset($_COOKIE['user_email'])) {
    setcookie('user_email', '', time() - 3600, '/');
}

// Redirect to the login page
header('Location: index.php');
exit();
?>