<?php
// ================================================================
//  menu_api.php — Gestión del menú (solo admin)
// ================================================================
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

require_once 'conexion.php';

$accion = $_GET['accion'] ?? '';
$body   = json_decode(file_get_contents('php://input'), true) ?? [];

// ── LISTAR todos los productos ────────────────────────────────
if ($accion === 'listar') {
    $res = $dp->query("
        SELECT id_producto, nombre, precio, categoria, subcategoria,
               tipo, ruta_defecto, activo
        FROM producto
        WHERE activo = 1
        ORDER BY categoria, subcategoria, nombre
    ");
    if (!$res) respJson(["ok" => false, "error" => $dp->error]);
    $prods = [];
    while ($row = $res->fetch_assoc()) $prods[] = $row;
    respJson(["ok" => true, "productos" => $prods]);
}

// ── CREAR producto ────────────────────────────────────────────
if ($accion === 'crear') {
    $nombre   = trim($body['nombre']       ?? '');
    $precio   = (float)($body['precio']    ?? 0);
    $cat      = trim($body['categoria']    ?? '');
    $sub      = trim($body['subcategoria'] ?? '');
    $tipo     = in_array($body['tipo'] ?? '', ['bebida','comida','coctel'])
                ? $body['tipo'] : 'comida';
    $ruta     = in_array($body['ruta_defecto'] ?? '', ['barra','cocina'])
                ? $body['ruta_defecto'] : 'cocina';
    $temporada= (int)($body['es_temporada'] ?? 0);

    if (!$nombre || $precio <= 0 || !$cat)
        respJson(["ok" => false, "error" => "Nombre, precio y categoría son obligatorios"]);

    $nb  = $dp->real_escape_string($nombre);
    $sb  = $dp->real_escape_string($sub);
    $cb  = $dp->real_escape_string($cat);
    $dp->query("
        INSERT INTO producto (nombre, precio, categoria, subcategoria, tipo, ruta_defecto, es_temporada)
        VALUES ('$nb', $precio, '$cb', '$sb', '$tipo', '$ruta', $temporada)
    ");
    if ($dp->error) respJson(["ok" => false, "error" => $dp->error]);
    respJson(["ok" => true, "id_producto" => $dp->insert_id]);
}

// ── EDITAR producto ───────────────────────────────────────────
if ($accion === 'editar') {
    $id     = (int)($body['id_producto'] ?? 0);
    if (!$id) respJson(["ok" => false, "error" => "id_producto requerido"]);

    $sets = [];
    if (!empty($body['nombre'])) $sets[] = "nombre = '" . $dp->real_escape_string(trim($body['nombre'])) . "'";
    if (isset($body['precio']) && (float)$body['precio'] > 0) $sets[] = "precio = " . (float)$body['precio'];
    if (isset($body['activo'])) $sets[] = "activo = " . (int)$body['activo'];

    if (empty($sets)) respJson(["ok" => false, "error" => "Nada que actualizar"]);

    $dp->query("UPDATE producto SET " . implode(', ', $sets) . " WHERE id_producto = $id");
    if ($dp->error) respJson(["ok" => false, "error" => $dp->error]);
    respJson(["ok" => true]);
}

// ── ELIMINAR (desactivar) producto ────────────────────────────
if ($accion === 'eliminar') {
    $id = (int)($body['id_producto'] ?? 0);
    if (!$id) respJson(["ok" => false, "error" => "id_producto requerido"]);
    $dp->query("UPDATE producto SET activo = 0 WHERE id_producto = $id");
    if ($dp->error) respJson(["ok" => false, "error" => $dp->error]);
    respJson(["ok" => true]);
}

// ── CREAR producto de temporada ───────────────────────────────
if ($accion === 'temporada_crear') {
    $nombre    = trim($body['nombre']     ?? '');
    $precio    = (float)($body['precio'] ?? 0);
    $tipo_temp = $body['tipo_temp']       ?? 'postre';

    if (!$nombre || $precio <= 0)
        respJson(["ok" => false, "error" => "Nombre y precio requeridos"]);

    $cat  = $tipo_temp === 'bebida' ? 'bebidasTemp' : 'postres';
    $sub  = $tipo_temp === 'bebida' ? 'Bebidas Temporada' : 'Postres Temporada';
    $tipo = $tipo_temp === 'bebida' ? 'bebida' : 'comida';
    $ruta = $tipo_temp === 'bebida' ? 'barra' : 'cocina';
    $nb   = $dp->real_escape_string($nombre);

    $dp->query("
        INSERT INTO producto (nombre, precio, categoria, subcategoria, tipo, ruta_defecto, es_temporada)
        VALUES ('$nb', $precio, '$cat', '$sub', '$tipo', '$ruta', 1)
    ");
    if ($dp->error) respJson(["ok" => false, "error" => $dp->error]);
    respJson(["ok" => true, "id_producto" => $dp->insert_id]);
}

// ── LISTAR productos de temporada ─────────────────────────────
if ($accion === 'temporada_listar') {
    $res = $dp->query("
        SELECT id_producto, nombre, precio, categoria, tipo, ruta_defecto
        FROM producto
        WHERE es_temporada = 1 AND activo = 1
        ORDER BY categoria, nombre
    ");
    $prods = [];
    while ($row = $res->fetch_assoc()) $prods[] = $row;
    respJson(["ok" => true, "productos" => $prods]);
}

respJson(["ok" => false, "error" => "Acción desconocida: '$accion'"]);
?>
