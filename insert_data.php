<?php
require_once 'config.php'; // contains $conn = new mysqli(...);

// Set timezone
date_default_timezone_set('Asia/Dhaka');

// Function to insert new order
function insertOrder($data) {
    global $conn;

    $createPayment      = isset($data['createPayment']) ? $data['createPayment'] : '';
    $whatsapp           = $conn->real_escape_string($data['whatsapp']);
    $payment_reference  = isset($data['payment_reference']) ? $conn->real_escape_string($data['payment_reference']) : $whatsapp;
    $dob                = $conn->real_escape_string($data['dob']);
    $nid                = $conn->real_escape_string($data['nid']);
    $product_id         = (int)$data['product_id'];
    $amount             = (float)$data['amount'];
    $contact_number     = isset($data['contact_number']) ? $conn->real_escape_string($data['contact_number']) : '';

    // Insert into MySQL
    $query = "INSERT INTO `order_lists` SET 
        `product_id` = '$product_id',  
        `amount` = '$amount',  
        `nid` = '$nid', 
        `dob` = '$dob', 
        `whatsappNumber` = '$whatsapp', 
        `contactNumber` = '$contact_number', 
        `createPayment` = '$createPayment',
        `payerReference` = '$payment_reference'";

    if (!$conn->query($query)) {
        die("Database Insert Failed: " . $conn->error);
    }

    $insert_id = $conn->insert_id;

    // 🔔 Send Telegram Notification
    sendTelegramNotification($nid, $dob, $whatsapp, $amount);

    return $insert_id;
}

// Function to update order after payment
function updateOrder($insertId, $res) {
    global $conn;

    $transactionStatus = $res['transactionStatus'];
    $executePaymentJson = $conn->real_escape_string(json_encode($res));

    $query = "UPDATE `order_lists` SET 
        `statusCode` = '$transactionStatus', 
        `status` = 'paid',
        `executePayment` = '$executePaymentJson' 
        WHERE `id` = '$insertId'";

    if (!$conn->query($query)) {
        die("Database Update Failed: " . $conn->error);
    }
}

// ✅ Telegram Notification Function
function sendTelegramNotification($nid, $dob, $whatsapp, $amount) {
    $botToken = '8015956654:AAHo_fBwXkzxe4P0I3NukzVPIegaQlaf8AY';
    $chatId   = '7521566295'; // ✅ Your real Telegram ID

    $time = date("j F Y, h:i A");

    $message = <<<MSG
📥 *New Order Received*

🆔 *NID:* `$nid`
🎂 *DOB:* `$dob`
📱 *WhatsApp:* `$whatsapp`
💳 *Amount:* `৳$amount`
🕒 *Time:* $time
MSG;

    $url = "https://api.telegram.org/bot$botToken/sendMessage";

    $data = [
        'chat_id' => $chatId,
        'text' => $message,
        'parse_mode' => 'Markdown'
    ];

    $options = [
        'http' => [
            'method'  => 'POST',
            'header'  => "Content-Type:application/x-www-form-urlencoded\r\n",
            'content' => http_build_query($data)
        ]
    ];

    $context = stream_context_create($options);
    @file_get_contents($url, false, $context); // @ used to suppress warnings
}
?>
