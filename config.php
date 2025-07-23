<?php
    define('BKASH_USERNAME', '');
    define('BKASH_PASSWORD', '');
    define('BKASH_APP_KEY', '');
    define('BKASH_APP_SECRET', '');
    define('BKASH_PRODUCT', 'TokenizedCheckout');

    define('BKASH_BASE_URL', 'https://tokenized.pay.bka.sh/v1.2.0-beta/tokenized/checkout');

    define('BKASH_GRANT_TOKEN_URL', BKASH_BASE_URL.'/token/grant');
    define('BKASH_CREATE_PAYMENT_URL', BKASH_BASE_URL.'/create');
    define('BKASH_EXECUTE_PAYMENT_URL', BKASH_BASE_URL.'/execute');

    define('CALLBACK_URL', 'https://fokinni.site/BKASH-PHP/execute_payment.php');
    define('ADMIN_USER', 'admin');
    define('ADMIN_PASS', 'admin123');

    define('DB_HOST', 'localhost');
    define('DB_USER', '');
    define('DB_PASSWORD', '');
    define('DB_NAME', '');


    $conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

?>