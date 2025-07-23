<?php
require_once 'config.php';
require_once 'get_token.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Save form data to session
    $_SESSION['nid'] = $_POST['nid'] ?? '';
    $_SESSION['dob'] = $_POST['dob'] ?? '';
    $_SESSION['whatsapp'] = $_POST['whatsapp'] ?? '';
    $_SESSION['payment_reference'] = $_POST['payment_reference'] ?? '';
    $_SESSION['product_id'] = $_POST['product_id'] ?? '';
    $_SESSION['amount'] = $_POST['amount'] ?? '';
    $_SESSION['contact_number'] = $_POST['contact_number'] ?? '';

    // Get bKash token
    $tokenData = getBkashToken();
    $token = $tokenData['id_token'];
    $_SESSION['token'] = $token;

    $headers = [
        "Content-Type: application/json",
        "authorization: Bearer $token",
        "x-app-key: " . BKASH_APP_KEY
    ];

    $invoice = "INV" . time();
    $_SESSION['invoice'] = $invoice;

    $post_data = [
        'mode' => '0011',
        'payerReference' => $_SESSION['whatsapp'],
        'callbackURL' => CALLBACK_URL,
        'amount' => $_SESSION['amount'],
        'currency' => 'BDT',
        'intent' => 'sale',
        'merchantInvoiceNumber' => $invoice
    ];

    $ch = curl_init(BKASH_CREATE_PAYMENT_URL);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post_data));
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    $result = curl_exec($ch);
    curl_close($ch);

    $res = json_decode($result, true);

    if (!empty($res['bkashURL'])) {
        header("Location: " . $res['bkashURL']);
        exit;
    } else {
        echo "❌ Error creating payment. Please try again.";
    }
}
?>
