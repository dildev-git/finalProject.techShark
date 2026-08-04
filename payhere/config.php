<?php
// ─── PayHere Gateway Configuration ────────────────────────────────────────────
// Switch PAYHERE_SANDBOX to false when going live, and update credentials below.

define('PAYHERE_SANDBOX',     true);
define('PAYHERE_MERCHANT_ID', '');          // Your PayHere Merchant ID
define('PAYHERE_SECRET',      '');  // Your Merchant Secret

// Sandbox vs Live checkout URL
define('PAYHERE_CHECKOUT_URL',
    PAYHERE_SANDBOX
        ? 'https://sandbox.payhere.lk/pay/checkout'
        : 'https://www.payhere.lk/pay/checkout'
);

// Currency — PayHere sandbox works with LKR
define('PAYHERE_CURRENCY', 'LKR');

// Your domain — PayHere will call these URLs
define('SITE_URL', 'http://localhost/techshark_v1');  // No trailing slash
define('NOTIFY_URL',  SITE_URL . '/payhere/notify.php');
define('RETURN_URL',  SITE_URL . '/payhere/success.php');
define('CANCEL_URL',  SITE_URL . '/cart.php');
