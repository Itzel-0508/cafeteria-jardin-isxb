<?php
// ================================================================
//  usuarios.php — Panel de Administración de Usuarios
//  GET  ?accion=listar           → todos los usuarios
//  POST ?accion=crear            { nombre, pin, rol }
//  POST ?accion=editar           { id_usuario, nombre, pin, rol }
//  POST ?accion=toggle_activo    { id_usuario }
//  GET  ?accion=historial        → últimos 50 accesos (tabla log_acceso)

// ================================================================
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

require_once 'conexion.php';

// Crear tabla de log si no existe (sin alterar la BD original)
$dp->query("
    CREATE TABLE IF NOT EXISTS log_acceso (
        id_log       INT AUTO_INCREMENT PRIMARY KEY,
        id_usuario   INT NOT NULL,
        nombre       VARCHAR(100) NOT NULL,
        rol          VARCHAR(40)  NOT NULL,
        ts           DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
        ip           VARCHAR(45)
    )
");

$accion = $_GET['accion'] ?? '';
$body   = json_decode(file_get_contents('php://input'), true) ?? [];

// ── LISTAR todos los usuarios ─────────────────────────────────
if ($accion === 'listar') {
    // Añadir columna pin_visible de forma segura (compatible MySQL 5.x y 8.x)
    $colCheck = $dp->query("SHOW COLUMNS FROM usuario LIKE 'pin_visible'");
    if ($colCheck && $colCheck->num_rows === 0) {
        $dp->query("ALTER TABLE usuario ADD COLUMN pin_visible VARCHAR(20) NULL");
    }
    // Añadir columna correo si no existe
    $colCheckC = $dp->query("SHOW COLUMNS FROM usuario LIKE 'correo'");
    if ($colCheckC && $colCheckC->num_rows === 0) {
        $dp->query("ALTER TABLE usuario ADD COLUMN correo VARCHAR(120) NULL");
    }
    $dp->errno; // limpiar cualquier error residual
    $dp->error;

    $res = $dp->query("
        SELECT id_usuario, nombre, rol, activo, fecha_alta,
               IFNULL(pin_visible,'') AS pin_visible,
               IFNULL(correo,'') AS correo
        FROM usuario
        ORDER BY FIELD(rol,'admin','cajero','mesero','cocina','barra'), nombre
    ");
    if (!$res) respJson(["ok" => false, "error" => $dp->error]);
    $usuarios = [];
    while ($row = $res->fetch_assoc()) $usuarios[] = $row;
    respJson(["ok" => true, "usuarios" => $usuarios]);
}

// ── CREAR usuario ─────────────────────────────────────────────
if ($accion === 'crear') {
    $nombre = trim($body['nombre'] ?? '');
    $pin    = trim($body['pin']    ?? '');
    $rol    = $body['rol']         ?? '';
    $roles  = ['mesero','cocina','barra','cajero','admin'];

    if (!$nombre) respJson(["ok" => false, "error" => "El nombre es obligatorio"]);
    if (!$pin || strlen($pin) < 4) respJson(["ok" => false, "error" => "El PIN debe tener al menos 4 dígitos"]);
    if (!in_array($rol, $roles))   respJson(["ok" => false, "error" => "Rol inválido"]);

    // No permitir crear más de un administrador
    if ($rol === 'admin') {
        $cntAdm = $dp->query("SELECT COUNT(*) AS c FROM usuario WHERE rol='admin'")->fetch_assoc()['c'];
        if ($cntAdm >= 1)
            respJson(["ok" => false, "error" => "Ya existe un administrador. Solo puede haber uno en el sistema."]);
    }

    // Verificar nombre único
    $chk = $dp->prepare("SELECT id_usuario FROM usuario WHERE nombre = ? LIMIT 1");
    $chk->bind_param('s', $nombre);
    $chk->execute();
    if ($chk->get_result()->num_rows > 0)
        respJson(["ok" => false, "error" => "Ya existe un usuario con ese nombre"]);

    // Añadir columna pin_visible si no existe
    $colCk = $dp->query("SHOW COLUMNS FROM usuario LIKE 'pin_visible'");
    if ($colCk && $colCk->num_rows === 0) $dp->query("ALTER TABLE usuario ADD COLUMN pin_visible VARCHAR(20) NULL");
    $dp->error; // limpiar error
    $correo = trim($body['correo'] ?? '');
    $colCk3 = $dp->query("SHOW COLUMNS FROM usuario LIKE 'correo'");
    if ($colCk3 && $colCk3->num_rows === 0) $dp->query("ALTER TABLE usuario ADD COLUMN correo VARCHAR(120) NULL");
    $stmt = $dp->prepare("INSERT INTO usuario (nombre, pin, rol, pin_visible, correo) VALUES (?, SHA2(?,256), ?, ?, ?)");
    $stmt->bind_param('sssss', $nombre, $pin, $rol, $pin, $correo);
    $stmt->execute();
    if ($dp->error) respJson(["ok" => false, "error" => $dp->error]);
    respJson(["ok" => true, "id_usuario" => $dp->insert_id]);
}

// ── EDITAR usuario ────────────────────────────────────────────
if ($accion === 'editar') {
    $id     = (int)($body['id_usuario'] ?? 0);
    $nombre = trim($body['nombre']      ?? '');
    $pin    = trim($body['pin']         ?? '');
    $rol    = $body['rol']              ?? '';
    $roles  = ['mesero','cocina','barra','cajero','admin'];

    if (!$id || !$nombre) respJson(["ok" => false, "error" => "Datos incompletos"]);
    if (!in_array($rol, $roles)) respJson(["ok" => false, "error" => "Rol inválido"]);

    // No permitir asignar rol admin si ya existe otro (distinto al que se edita)
    if ($rol === 'admin') {
        $cntAdm2 = $dp->query("SELECT COUNT(*) AS c FROM usuario WHERE rol='admin' AND id_usuario != $id")->fetch_assoc()['c'];
        if ($cntAdm2 >= 1)
            respJson(["ok" => false, "error" => "Ya existe un administrador. Solo puede haber uno."]);
    }

    // Verificar nombre único (excepto el mismo usuario)
    $chk = $dp->prepare("SELECT id_usuario FROM usuario WHERE nombre = ? AND id_usuario != ? LIMIT 1");
    $chk->bind_param('si', $nombre, $id);
    $chk->execute();
    if ($chk->get_result()->num_rows > 0)
        respJson(["ok" => false, "error" => "Ese nombre ya lo usa otro usuario"]);

    $colCk2 = $dp->query("SHOW COLUMNS FROM usuario LIKE 'pin_visible'");
    if ($colCk2 && $colCk2->num_rows === 0) $dp->query("ALTER TABLE usuario ADD COLUMN pin_visible VARCHAR(20) NULL");
    $dp->error;
    $correo = trim($body['correo'] ?? '');
    if ($pin) {
        $stmt = $dp->prepare("UPDATE usuario SET nombre=?, pin=SHA2(?,256), rol=?, pin_visible=?, correo=? WHERE id_usuario=?");
        $stmt->bind_param('sssssi', $nombre, $pin, $rol, $pin, $correo, $id);
    } else {
        $stmt = $dp->prepare("UPDATE usuario SET nombre=?, rol=?, correo=? WHERE id_usuario=?");
        $stmt->bind_param('sssi', $nombre, $rol, $correo, $id);
    }
    $stmt->execute();
    if ($dp->error) respJson(["ok" => false, "error" => $dp->error]);
    respJson(["ok" => true]);
}

// ── ACTIVAR / DESACTIVAR usuario ──────────────────────────────
if ($accion === 'toggle_activo') {
    $id = (int)($body['id_usuario'] ?? 0);
    if (!$id) respJson(["ok" => false, "error" => "id_usuario requerido"]);

    // No desactivar al único admin activo
    $res = $dp->query("SELECT rol, activo FROM usuario WHERE id_usuario = $id LIMIT 1");
    if (!$res || !$res->num_rows) respJson(["ok" => false, "error" => "Usuario no encontrado"]);
    $u = $res->fetch_assoc();

    if ($u['rol'] === 'admin' && $u['activo'] == 1) {
        $cnt = $dp->query("SELECT COUNT(*) AS c FROM usuario WHERE rol='admin' AND activo=1")->fetch_assoc()['c'];
        if ($cnt <= 1) respJson(["ok" => false, "error" => "No puedes desactivar al único administrador"]);
    }

    $dp->query("UPDATE usuario SET activo = NOT activo WHERE id_usuario = $id");
    if ($dp->error) respJson(["ok" => false, "error" => $dp->error]);

    $nuevo = $dp->query("SELECT activo FROM usuario WHERE id_usuario=$id")->fetch_assoc()['activo'];
    respJson(["ok" => true, "activo" => (int)$nuevo]);
}

// ── REGISTRAR ACCESO (llamado desde login_check) ──────────────
if ($accion === 'log_acceso') {
    $id_u  = (int)($body['id_usuario'] ?? 0);
    $nomb  = $body['nombre'] ?? '';
    $rol   = $body['rol']    ?? '';
    $ip    = $_SERVER['REMOTE_ADDR'] ?? '';
    if (!$id_u) respJson(["ok" => false, "error" => "Datos incompletos"]);
    $stmt = $dp->prepare("INSERT INTO log_acceso (id_usuario, nombre, rol, ip) VALUES (?,?,?,?)");
    $stmt->bind_param('isss', $id_u, $nomb, $rol, $ip);
    $stmt->execute();
    respJson(["ok" => true]);
}

// ── HISTORIAL DE ACCESOS ──────────────────────────────────────
if ($accion === 'historial') {
    // Verificar si existe la tabla
    $check = $dp->query("SHOW TABLES LIKE 'log_acceso'");
    if (!$check || $check->num_rows === 0) {
        respJson(["ok" => true, "historial" => []]);
    }
    $res = $dp->query("SELECT * FROM log_acceso ORDER BY ts DESC LIMIT 100");
    if (!$res) respJson(["ok" => false, "error" => $dp->error]);
    $logs = [];
    while ($row = $res->fetch_assoc()) $logs[] = $row;
    respJson(["ok" => true, "historial" => $logs]);
}

// ── ELIMINAR USUARIO PERMANENTEMENTE ──────────────────────────
if ($accion === 'eliminar') {
    $id = (int)($body['id_usuario'] ?? 0);
    if (!$id) respJson(["ok" => false, "error" => "id_usuario requerido"]);

    // No permitir borrar al único admin activo
    $r = $dp->query("SELECT rol, activo FROM usuario WHERE id_usuario = $id LIMIT 1");
    if (!$r || !$r->num_rows) respJson(["ok" => false, "error" => "Usuario no encontrado"]);
    $u = $r->fetch_assoc();
    if ($u['rol'] === 'admin') {
        $cnt = $dp->query("SELECT COUNT(*) AS c FROM usuario WHERE rol='admin'")->fetch_assoc()['c'];
        if ($cnt <= 1) respJson(["ok" => false, "error" => "No puedes borrar al único administrador del sistema"]);
    }

    // Borrar también sus turnos y log de accesos
    $dp->query("DELETE FROM turno_dia WHERE id_usuario = $id");
    $dp->query("DELETE FROM log_acceso WHERE id_usuario = $id");
    $dp->query("DELETE FROM usuario WHERE id_usuario = $id");
    if ($dp->error) respJson(["ok" => false, "error" => $dp->error]);
    respJson(["ok" => true]);
}

respJson(["ok" => false, "error" => "Acción desconocida: '$accion'"]);
?>
