<?php
// ================================================================
//  turnos.php — Registro de turnos y distribución de propinas
//  GET  ?accion=listar_hoy          → personal que trabajó hoy
//  GET  ?accion=listar_fecha&fecha= → personal de una fecha
//  POST ?accion=registrar           { id_usuario, rol_turno, fecha }
//  POST ?accion=quitar              { id_turno }
//  POST ?accion=dividir_propinas    { fecha, total_propina, modo, ids_usuarios[] }
//  GET  ?accion=historial_propinas  → propinas repartidas últimos 30 días
// ================================================================
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

require_once 'conexion.php';

// Crear tabla si no existe
$dp->query("
    CREATE TABLE IF NOT EXISTS turno_dia (
        id_turno     INT AUTO_INCREMENT PRIMARY KEY,
        id_usuario   INT NOT NULL,
        fecha        DATE NOT NULL DEFAULT (CURDATE()),
        rol_turno    VARCHAR(40) NOT NULL,
        propina      DECIMAL(10,2) NOT NULL DEFAULT 0,
        ts_registro  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uq_usuario_fecha (id_usuario, fecha),
        FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

$accion = $_GET['accion'] ?? '';
$body   = json_decode(file_get_contents('php://input'), true) ?? [];

// ── LISTAR quién trabajó hoy ──────────────────────────────────
if ($accion === 'listar_hoy') {
    $fecha = date('Y-m-d');
    _listarFecha($fecha);
}

// ── LISTAR por fecha específica ───────────────────────────────
if ($accion === 'listar_fecha') {
    $fecha = $dp->real_escape_string($_GET['fecha'] ?? date('Y-m-d'));
    _listarFecha($fecha);
}

function _listarFecha($fecha) {
    global $dp;
    $res = $dp->query("
        SELECT t.id_turno, t.id_usuario, t.fecha, t.rol_turno, t.propina, t.ts_registro,
               u.nombre, u.rol AS rol_base, u.activo
        FROM turno_dia t
        JOIN usuario u ON u.id_usuario = t.id_usuario
        WHERE t.fecha = '$fecha'
        ORDER BY FIELD(t.rol_turno,'admin','cajero','mesero','cocina','barra'), u.nombre
    ");
    $turnos = [];
    while ($r = $res->fetch_assoc()) $turnos[] = $r;

    // También traer todos los usuarios activos para el selector
    $res2 = $dp->query("
        SELECT id_usuario, nombre, rol, activo
        FROM usuario
        WHERE activo = 1
        ORDER BY FIELD(rol,'admin','cajero','mesero','cocina','barra'), nombre
    ");
    $usuarios = [];
    while ($r = $res2->fetch_assoc()) $usuarios[] = $r;

    respJson(["ok" => true, "turnos" => $turnos, "usuarios" => $usuarios, "fecha" => $fecha]);
}

// ── REGISTRAR turno ───────────────────────────────────────────
if ($accion === 'registrar') {
    $id_usuario = (int)($body['id_usuario'] ?? 0);
    $rol_turno  = trim($body['rol_turno'] ?? '');
    $fecha      = trim($body['fecha'] ?? date('Y-m-d'));

    if (!$id_usuario || !$rol_turno)
        respJson(["ok" => false, "error" => "id_usuario y rol_turno requeridos"]);

    $roles = ['admin','mesero','cocina','barra','cajero'];
    if (!in_array($rol_turno, $roles))
        respJson(["ok" => false, "error" => "Rol inválido"]);

    $f = $dp->real_escape_string($fecha);
    $r = $dp->real_escape_string($rol_turno);

    $dp->query("
        INSERT INTO turno_dia (id_usuario, fecha, rol_turno)
        VALUES ($id_usuario, '$f', '$r')
        ON DUPLICATE KEY UPDATE rol_turno = '$r'
    ");
    if ($dp->error) respJson(["ok" => false, "error" => $dp->error]);
    respJson(["ok" => true, "id_turno" => $dp->insert_id]);
}

// ── QUITAR del turno ──────────────────────────────────────────
if ($accion === 'quitar') {
    $id_turno = (int)($body['id_turno'] ?? 0);
    if (!$id_turno) respJson(["ok" => false, "error" => "id_turno requerido"]);
    $dp->query("DELETE FROM turno_dia WHERE id_turno = $id_turno");
    if ($dp->error) respJson(["ok" => false, "error" => $dp->error]);
    respJson(["ok" => true]);
}

// ── DIVIDIR PROPINAS ──────────────────────────────────────────
if ($accion === 'dividir_propinas') {
    $fecha        = $dp->real_escape_string($body['fecha'] ?? date('Y-m-d'));
    $total        = (float)($body['total_propina'] ?? 0);
    $modo         = $body['modo'] ?? 'todos';      // 'todos' | 'meseros' | 'manual'
    $ids          = $body['ids_usuarios'] ?? [];   // array de ids si modo=manual

    if ($total <= 0) respJson(["ok" => false, "error" => "Total de propina inválido"]);

    // Quién participa según el modo
    if ($modo === 'meseros') {
        $res = $dp->query("SELECT id_usuario FROM turno_dia WHERE fecha='$fecha' AND rol_turno='mesero'");
    } elseif ($modo === 'manual' && !empty($ids)) {
        $idsStr = implode(',', array_map('intval', $ids));
        $res = $dp->query("SELECT id_usuario FROM turno_dia WHERE fecha='$fecha' AND id_usuario IN ($idsStr)");
    } else {
        // todos — excepto admin
        $res = $dp->query("SELECT id_usuario FROM turno_dia WHERE fecha='$fecha' AND rol_turno != 'admin'");
    }

    $participantes = [];
    while ($r = $res->fetch_assoc()) $participantes[] = (int)$r['id_usuario'];

    if (empty($participantes)) respJson(["ok" => false, "error" => "No hay personal en el turno para repartir"]);

    $porPersona = round($total / count($participantes), 2);

    // Actualizar propina de cada uno
    foreach ($participantes as $uid) {
        $dp->query("UPDATE turno_dia SET propina = $porPersona WHERE id_usuario = $uid AND fecha = '$fecha'");
    }

    respJson([
        "ok"           => true,
        "participantes"=> count($participantes),
        "por_persona"  => $porPersona,
        "total"        => $total
    ]);
}

// ── HISTORIAL DE PROPINAS ─────────────────────────────────────
if ($accion === 'historial_propinas') {
    $res = $dp->query("
        SELECT t.fecha, u.nombre, t.rol_turno, t.propina
        FROM turno_dia t
        JOIN usuario u ON u.id_usuario = t.id_usuario
        WHERE t.propina > 0
        ORDER BY t.fecha DESC, u.nombre
        LIMIT 300
    ");
    $hist = [];
    while ($r = $res->fetch_assoc()) $hist[] = $r;
    respJson(["ok" => true, "historial" => $hist]);
}

// ── TOTAL PROPINAS DEL DÍA (desde pagos) ─────────────────────
if ($accion === 'propinas_hoy') {
    $fecha = $dp->real_escape_string($_GET['fecha'] ?? date('Y-m-d'));
    $res = $dp->query("SELECT IFNULL(SUM(propina),0) AS total FROM pago WHERE DATE(fecha_hora)='$fecha'");
    $row = $res->fetch_assoc();
    respJson(["ok" => true, "total_propinas" => (float)$row['total']]);
}

respJson(["ok" => false, "error" => "Acción desconocida: '$accion'"]);
?>
