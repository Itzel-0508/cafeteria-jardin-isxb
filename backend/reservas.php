<?php
// ================================================================
//  reservas.php — API completa de Reservas | Jardín POS
//
//  GET  ?accion=listar              → todas las reservas activas
//  POST ?accion=guardar             → crear nueva reserva
//  POST ?accion=cancelar            → cancelar reserva (activa=0)
//  POST ?accion=activar             → reactivar reserva cancelada
//  GET  ?accion=hoy                 → reservas del día de hoy
//  GET  ?accion=por_mesa&id_mesa=N  → reservas de una mesa
// ================================================================
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

require_once 'conexion.php';

// ── Crear tabla reserva si no existe ─────────────────────────
// Esto garantiza que el sistema funcione aunque la tabla no exista aún
$dp->query("
    CREATE TABLE IF NOT EXISTS reserva (
        id_reserva      INT AUTO_INCREMENT PRIMARY KEY,
        id_mesa_fk      INT NOT NULL,
        nombre_cliente  VARCHAR(100) NOT NULL,
        fecha_reserva   DATETIME NOT NULL,
        num_personas    INT NOT NULL DEFAULT 2,
        nota            TEXT,
        evento          VARCHAR(100),
        notif_minutos   INT DEFAULT 15,
        activa          TINYINT(1) NOT NULL DEFAULT 1,
        ts_creacion     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (id_mesa_fk) REFERENCES mesa(id_mesa) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

$accion = $_GET['accion'] ?? '';
$body   = json_decode(file_get_contents('php://input'), true) ?? [];

// ════════════════════════════════════════════════════════════════
// LISTAR — reservas activas (para el picker de mesas)
//          usar ?accion=todas para ver historial completo
// ════════════════════════════════════════════════════════════════
if ($accion === 'listar' || $accion === 'todas') {
    $soloActivas = ($accion === 'listar') ? "WHERE r.activa = 1" : "";
    $res = $dp->query("
        SELECT
            r.id_reserva,
            r.id_mesa_fk,
            m.numero_mesa,
            r.nombre_cliente,
            r.fecha_reserva,
            r.num_personas,
            r.nota,
            r.evento,
            r.notif_minutos,
            r.activa,
            r.ts_creacion
        FROM reserva r
        LEFT JOIN mesa m ON m.id_mesa = r.id_mesa_fk
        $soloActivas
        ORDER BY r.fecha_reserva DESC
    ");

    if (!$res) respJson(["ok" => false, "error" => $dp->error]);

    $reservas = [];
    while ($row = $res->fetch_assoc()) {
        $reservas[] = [
            'id_reserva'     => (int)$row['id_reserva'],
            'id_mesa_fk'     => (int)$row['id_mesa_fk'],
            'numero_mesa'    => (int)$row['numero_mesa'],
            'nombre_cliente' => $row['nombre_cliente'],
            'fecha_reserva'  => $row['fecha_reserva'],
            'num_personas'   => (int)$row['num_personas'],
            'nota'           => $row['nota'] ?? '',
            'evento'         => $row['evento'] ?? '',
            'notif_minutos'  => (int)$row['notif_minutos'],
            'activa'         => (int)$row['activa'],
            'ts_creacion'    => $row['ts_creacion'],
        ];
    }
    respJson(["ok" => true, "reservas" => $reservas]);
}

// ════════════════════════════════════════════════════════════════
// HOY — solo las reservas activas del día de hoy
// ════════════════════════════════════════════════════════════════
if ($accion === 'hoy') {
    $res = $dp->query("
        SELECT
            r.id_reserva,
            r.id_mesa_fk,
            m.numero_mesa,
            r.nombre_cliente,
            r.fecha_reserva,
            r.num_personas,
            r.nota,
            r.evento,
            r.notif_minutos,
            r.activa
        FROM reserva r
        LEFT JOIN mesa m ON m.id_mesa = r.id_mesa_fk
        WHERE r.activa = 1
          AND DATE(r.fecha_reserva) = CURDATE()
        ORDER BY r.fecha_reserva ASC
    ");

    if (!$res) respJson(["ok" => false, "error" => $dp->error]);

    $reservas = [];
    while ($row = $res->fetch_assoc()) {
        $reservas[] = [
            'id_reserva'     => (int)$row['id_reserva'],
            'id_mesa_fk'     => (int)$row['id_mesa_fk'],
            'numero_mesa'    => (int)$row['numero_mesa'],
            'nombre_cliente' => $row['nombre_cliente'],
            'fecha_reserva'  => $row['fecha_reserva'],
            'num_personas'   => (int)$row['num_personas'],
            'nota'           => $row['nota'] ?? '',
            'evento'         => $row['evento'] ?? '',
            'notif_minutos'  => (int)$row['notif_minutos'],
            'activa'         => (int)$row['activa'],
        ];
    }
    respJson(["ok" => true, "reservas" => $reservas]);
}

// ════════════════════════════════════════════════════════════════
// POR MESA — reservas activas de una mesa específica
// ════════════════════════════════════════════════════════════════
if ($accion === 'por_mesa') {
    $id_mesa = (int)($_GET['id_mesa'] ?? 0);
    if (!$id_mesa) respJson(["ok" => false, "error" => "id_mesa requerido"]);

    $res = $dp->query("
        SELECT
            r.id_reserva,
            r.id_mesa_fk,
            m.numero_mesa,
            r.nombre_cliente,
            r.fecha_reserva,
            r.num_personas,
            r.nota,
            r.evento,
            r.notif_minutos,
            r.activa
        FROM reserva r
        LEFT JOIN mesa m ON m.id_mesa = r.id_mesa_fk
        WHERE r.id_mesa_fk = $id_mesa AND r.activa = 1
        ORDER BY r.fecha_reserva ASC
    ");

    if (!$res) respJson(["ok" => false, "error" => $dp->error]);

    $reservas = [];
    while ($row = $res->fetch_assoc()) {
        $reservas[] = [
            'id_reserva'     => (int)$row['id_reserva'],
            'id_mesa_fk'     => (int)$row['id_mesa_fk'],
            'numero_mesa'    => (int)$row['numero_mesa'],
            'nombre_cliente' => $row['nombre_cliente'],
            'fecha_reserva'  => $row['fecha_reserva'],
            'num_personas'   => (int)$row['num_personas'],
            'nota'           => $row['nota'] ?? '',
            'evento'         => $row['evento'] ?? '',
            'notif_minutos'  => (int)$row['notif_minutos'],
            'activa'         => (int)$row['activa'],
        ];
    }
    respJson(["ok" => true, "reservas" => $reservas]);
}

// ════════════════════════════════════════════════════════════════
// GUARDAR — crear nueva reserva
// ════════════════════════════════════════════════════════════════
if ($accion === 'guardar') {
    // ── Validar campos obligatorios ───────────────────────────
    $nombre        = trim($body['nombre']        ?? '');
    $fecha_reserva = trim($body['fecha_reserva'] ?? '');
    $id_mesa       = (int)($body['id_mesa']      ?? 0);
    $num_personas  = (int)($body['num_personas'] ?? 2);
    $nota          = trim($body['nota']          ?? '');
    $evento        = trim($body['evento']        ?? '');
    $notif_min     = (int)($body['notif_minutos'] ?? 15);
    if (!$nombre)
        respJson(["ok" => false, "error" => "El nombre del cliente es obligatorio"]);
    if (!$fecha_reserva)
        respJson(["ok" => false, "error" => "La fecha y hora son obligatorias"]);
    if (!$id_mesa)
        respJson(["ok" => false, "error" => "Debes seleccionar una mesa"]);
    if ($num_personas < 1 || $num_personas > 50)
        respJson(["ok" => false, "error" => "Número de personas inválido (1-50)"]);

    // ── Validar que la mesa existe ────────────────────────────
    $chkMesa = $dp->query("SELECT id_mesa FROM mesa WHERE id_mesa = $id_mesa LIMIT 1");
    if (!$chkMesa || $chkMesa->num_rows === 0)
        respJson(["ok" => false, "error" => "La mesa seleccionada no existe"]);

    // ── Validar formato de fecha ──────────────────────────────
    $ts = strtotime($fecha_reserva);
    if (!$ts || $ts === -1)
        respJson(["ok" => false, "error" => "Formato de fecha inválido: $fecha_reserva"]);

    $fechaDB = date('Y-m-d H:i:s', $ts);

    // ── Verificar que no haya otra reserva activa para esa
    //    mesa en el mismo día (conflicto de horario ±2h) ──────
    $fechaSolo = date('Y-m-d', $ts);
    $desde     = date('Y-m-d H:i:s', $ts - 7200); // -2 horas
    $hasta     = date('Y-m-d H:i:s', $ts + 7200); // +2 horas

    $chkConflicto = $dp->query("
        SELECT id_reserva, nombre_cliente, fecha_reserva
        FROM reserva
        WHERE id_mesa_fk   = $id_mesa
          AND activa        = 1
          AND fecha_reserva BETWEEN '$desde' AND '$hasta'
        LIMIT 1
    ");

    if ($chkConflicto && $chkConflicto->num_rows > 0) {
        $conf = $chkConflicto->fetch_assoc();
        $horaConf = date('H:i', strtotime($conf['fecha_reserva']));
        respJson([
            "ok"    => false,
            "error" => "Ya existe una reserva para esa mesa a las {$horaConf} ({$conf['nombre_cliente']}). Elige otra hora o mesa."
        ]);
    }

    // ── Escapar strings para la inserción ────────────────────
    $nombreDB  = $dp->real_escape_string($nombre);
    $notaDB    = $dp->real_escape_string($nota);
    $eventoDb  = $dp->real_escape_string($evento);

    // ── Insertar ──────────────────────────────────────────────
    $dp->query("
        INSERT INTO reserva
            (id_mesa_fk, nombre_cliente, fecha_reserva, num_personas, nota, evento, notif_minutos, activa)
        VALUES
            ($id_mesa, '$nombreDB', '$fechaDB', $num_personas, '$notaDB', '$eventoDb', $notif_min, 1)
    ");

    if ($dp->error)
        respJson(["ok" => false, "error" => "Error al guardar en BD: " . $dp->error]);

    $id_nueva = (int)$dp->insert_id;

    // ── Si la reserva es para HOY, marcar la mesa como reservada ──
    $hoy = date('Y-m-d');
    if ($fechaSolo === $hoy) {
        $dp->query("
            UPDATE mesa
            SET estado = 'reservada'
            WHERE id_mesa = $id_mesa AND estado = 'libre'
        ");
        // Solo cambia si estaba libre — no sobreescribir ocupada/cobro
    }

    // ── Respuesta exitosa ─────────────────────────────────────
    respJson([
        "ok"         => true,
        "id_reserva" => $id_nueva,
        "message"    => "Reserva guardada correctamente para $nombre"
    ]);
}

// ════════════════════════════════════════════════════════════════
// CANCELAR — desactivar una reserva (no se borra, solo activa=0)
// ════════════════════════════════════════════════════════════════
if ($accion === 'cancelar') {
    $id_reserva = (int)($body['id_reserva'] ?? 0);
    if (!$id_reserva)
        respJson(["ok" => false, "error" => "id_reserva requerido"]);

    // Obtener mesa y fecha antes de cancelar
    $rRes = $dp->query("
        SELECT id_mesa_fk, fecha_reserva
        FROM reserva
        WHERE id_reserva = $id_reserva AND activa = 1
        LIMIT 1
    ");

    if (!$rRes || $rRes->num_rows === 0)
        respJson(["ok" => false, "error" => "Reserva no encontrada o ya cancelada"]);

    $rDat    = $rRes->fetch_assoc();
    $id_mesa = (int)$rDat['id_mesa_fk'];
    $fRes    = $rDat['fecha_reserva'];

    // Cancelar la reserva
    $dp->query("UPDATE reserva SET activa = 0 WHERE id_reserva = $id_reserva");
    if ($dp->error)
        respJson(["ok" => false, "error" => $dp->error]);

    // Si la reserva era de hoy y la mesa estaba reservada,
    // verificar si tiene otras reservas activas hoy antes de liberar
    $hoy = date('Y-m-d');
    $fechaSolo = date('Y-m-d', strtotime($fRes));
    if ($fechaSolo === $hoy) {
        $otraRes = $dp->query("
            SELECT COUNT(*) as c
            FROM reserva
            WHERE id_mesa_fk = $id_mesa
              AND activa = 1
              AND DATE(fecha_reserva) = CURDATE()
        ");
        $otras = $otraRes ? (int)$otraRes->fetch_assoc()['c'] : 0;

        if ($otras === 0) {
            // Sin más reservas activas hoy → liberar la mesa si estaba reservada
            $dp->query("
                UPDATE mesa SET estado = 'libre'
                WHERE id_mesa = $id_mesa AND estado = 'reservada'
            ");
        }
    }

    respJson(["ok" => true, "message" => "Reserva cancelada correctamente"]);
}

// ════════════════════════════════════════════════════════════════
// ACTIVAR — reactivar una reserva cancelada
// ════════════════════════════════════════════════════════════════
if ($accion === 'activar') {
    $id_reserva = (int)($body['id_reserva'] ?? 0);
    if (!$id_reserva)
        respJson(["ok" => false, "error" => "id_reserva requerido"]);

    $dp->query("UPDATE reserva SET activa = 1 WHERE id_reserva = $id_reserva");
    if ($dp->error)
        respJson(["ok" => false, "error" => $dp->error]);

    respJson(["ok" => true, "message" => "Reserva reactivada correctamente"]);
}

// ── Acción no reconocida ──────────────────────────────────────
respJson(["ok" => false, "error" => "Acción desconocida: '$accion'"]);
?>
