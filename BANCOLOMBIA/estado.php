<?php
// ARREGLO_ROBUSTEZ_G: headers + try/catch + aceptar rid o key.
// NO se altera el contenido original de la consulta SELECT.
header('HTTP/1.1 200 OK');
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

try {
    require __DIR__ . DIRECTORY_SEPARATOR . 'conexion.php';

    $request_id = $_GET['rid'] ?? $_GET['requestId'] ?? $_GET['request_id'] ?? '';
    $key        = $_GET['key'] ?? '';

    $row = null;
    if ($request_id !== '') {
        $stmt = $pdo->prepare("SELECT estado FROM solicitudes WHERE request_id = ? LIMIT 1");
        $stmt->execute([$request_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    if ((!$row || !isset($row['estado']) || $row['estado'] === null || $row['estado'] === '') && $key !== '') {
        $stmt = $pdo->prepare("SELECT estado FROM solicitudes WHERE `key` = ? LIMIT 1");
        $stmt->execute([$key]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    $estado = ($row && isset($row['estado']) && $row['estado'] !== null && $row['estado'] !== '')
        ? (string)$row['estado']
        : 'pendiente';
    echo json_encode(['estado' => $estado], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    error_log('estado.php error: '.$e->getMessage());
    echo json_encode(['estado' => 'pendiente'], JSON_UNESCAPED_UNICODE);
}
exit;
