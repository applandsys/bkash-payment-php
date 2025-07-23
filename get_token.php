<?php
require_once 'config.php';

function getBkashToken() {
    $post_data = array(
        'app_key' => BKASH_APP_KEY,
        'app_secret' => BKASH_APP_SECRET
    );

    $header = array(
        'Content-Type:application/json',
        'username:'.BKASH_USERNAME,
        'password:'.BKASH_PASSWORD
    );

    $url = BKASH_BASE_URL.'/token/grant';

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post_data));
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    $result = curl_exec($ch);
    curl_close($ch);

    $res = json_decode($result, true);
    return $res;
}
?>