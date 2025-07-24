<?php
require_once 'config.php';
require_once 'get_token.php';
require_once 'insert_data.php';
session_start();
date_default_timezone_set("Asia/Dhaka");
?>

<!DOCTYPE html>
<html lang="bn">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>অর্ডার নিশ্চিত</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <style>
    body { background-color: #f9f9f9; font-family: 'Segoe UI', sans-serif; }
    .notice-box {
      max-width: 700px; margin: 40px auto; padding: 30px;
      background-color: #fff0f5; border: 1px solid #f8bbd0;
      border-radius: 10px; font-size: 15px; color: #880e4f;
    }
    .highlight { font-weight: bold; color: #d10050; }
    .success-box {
      max-width: 500px; margin: 30px auto 10px; background-color: #e6f7ff;
      border: 1px solid #b2ebf2; border-radius: 10px; padding: 20px;
      color: #004d40; font-size: 16px;
    }
  </style>
</head>
<body>

<?php
if (isset($_GET['paymentID'])) {
    $paymentID = $_GET['paymentID'];
    $token = $_SESSION['token'];

    $url = BKASH_BASE_URL . "/execute/";
    $headers = [
        "Content-Type: application/json",
        "authorization: Bearer $token",
        "x-app-key: " . BKASH_APP_KEY
    ];

    $post_token = json_encode(['paymentID' => $paymentID]);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_token);
    $result = curl_exec($ch);
    curl_close($ch);

    $res = json_decode($result);

    // Fallback if execute fails
    if (!isset($res->transactionStatus) || $res->transactionStatus !== 'Completed') {
        $queryUrl = BKASH_BASE_URL . '/payment/status';
        $queryData = json_encode(["paymentID" => $paymentID]);

        $ch = curl_init($queryUrl);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $queryData);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        $queryResult = curl_exec($ch);
        curl_close($ch);

        $res = json_decode($queryResult);
    }

    // ✅ If payment is successful
    if ($res && $res->transactionStatus === 'Completed') {
        // Insert into DB
        $orderData = [
            'nid' => $_SESSION['nid'],
            'dob' => $_SESSION['dob'],
            'whatsapp' => $_SESSION['whatsapp'],
            'payment_reference' => $_SESSION['payment_reference'],
            'product_id' => $_SESSION['product_id'],
            'amount' => $_SESSION['amount'],
            'contact_number' => $_SESSION['contact_number'],
            'createPayment' => 'paid'
        ];

        $insert_id = insertOrder($orderData);
        updateOrder($insert_id, (array)$res);

        // ✅ Telegram Notification - ONLY PAYMENT SUCCESS MESSAGE
        $botToken = '';
        $chatId   = '';

        $trxID    = $res->trxID ?? 'N/A';
        $amount   = $res->amount ?? 'N/A';
        $wa       = $res->customerMsisdn ?? ($res->payerAccount ?? 'N/A');
        $time     = date("j F Y, h:i A");

        $nid      = $_SESSION['nid'] ?? 'N/A';
        $dob      = $_SESSION['dob'] ?? 'N/A';
        $whatsapp = $_SESSION['whatsapp'] ?? $wa;

        $message = <<<MSG
✅ *পেমেন্ট সম্পন্ন হয়েছে*

🧾 *TrxID:* `$trxID`
💳 *Amount:* `৳$amount`
🆔 *NID:* `$nid`
🎂 *DOB:* `$dob`
📱 *WhatsApp:* `$whatsapp`
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
        file_get_contents($url, false, stream_context_create($options));

        // Show Thank You Message on Page
        echo "
        <div class='success-box'>
          <h4 class='text-success fw-bold mb-3'>✅ পেমেন্ট রিসিভ হয়েছে</h4>
          আপনার অর্ডার সফলভাবে গ্রহণ করা হয়েছে। <br>
          <strong>ট্রান্স্যাকশন আইডি:</strong> <span class='text-dark'>$trxID</span><br>
          <strong>পেমেন্ট এমাউন্ট:</strong> <span class='text-dark'>৳$amount</span><br><br>
          <span class='text-muted small'>
            অনুগ্রহ করে ৩০ মিনিট অপেক্ষা করুন। আপনার ফাইল প্রস্তুত হওয়ার পর WhatsApp-এ পাঠানো হবে।
          </span>
        </div>";
    } else {
        echo "<div class='success-box text-danger'>❌ পেমেন্ট সফল হয়নি। দয়া করে আবার চেষ্টা করুন।</div>";
    }
}
?>

<!-- Notice Message -->
<div class="notice-box">
  <strong class="d-block mb-2">নোটিশ:</strong>
  ধন্যবাদ ভাই, আপনার অর্ডার রিসিভ করা হয়েছে। ৩০ মিনিটের মধ্যে ফাইল পেয়ে যাবেন।<br><br>
  <span class="highlight">শুক্রবারে কাজ বন্ধ থাকে।</span><br>
  প্রতিদিন সকাল ৯টা থেকে রাত ৮টা পর্যন্ত সেবা চালু। রাত ৮টার পর অর্ডার করলে, ফাইল পরদিন সকাল ৯টার পর ডেলিভারি হবে।<br><br>
  <span class="highlight">বৃহস্পতিবার রাত ৮টার পর ও শুক্রবারে অর্ডার করবেন না।</span><br>
  এরপরও অর্ডার করলে, যেহেতু আপনি নোটিশ জেনেও অর্ডার করেছেন, অনুগ্রহ করে শনিবার পর্যন্ত অপেক্ষা করুন।<br><br>
  এক্সকিউজ হিসেবে <strong>“জানতাম না”</strong> বলা যাবে না। কারণ পেমেন্ট লিংকের উপরে স্পষ্টভাবে লেখা আছে শুক্রবারে কাজ বন্ধ।<br><br>
  আপনি যদি বলেন <em>“কাস্টমার নিবে না”</em> বা <em>“লস হবে”</em>—এই কথাগুলো গ্রহণযোগ্য নয়।<br><br>
  আমাদের কোনো দোষ না থাকায়, ফাইল ডেলিভারি হলে রিফান্ড দেয়া হবে না।<br>
  <span class="highlight">তাই বিনীত অনুরোধ—এই বিষয়ে আর কোনো প্রশ্ন করবেন না।</span>
</div>

</body>
</html>
