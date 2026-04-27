<?php
// ================================================================
//  pedidos.php
//  POST ?accion=guardar   { mesa_id, usuario_id, destino, items[] }
//  GET  ?accion=activos   → pedidos activos de todas las mesas
//  POST ?accion=estado    { id_pedido, estado }
//  POST ?accion=item_estado { id_detalle, estado_item }
// ================================================================
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

require_once 'conexion.php';

$accion = $_GET['accion'] ?? '';
$body   = json_decode(file_get_contents('php://input'), true) ?? [];

// ── GUARDAR nuevo pedido con sus ítems ────────────────────────
if ($accion === 'guardar') {
    $mesa_id    = (int)($body['mesa_id']    ?? 0);
    $usuario_id = (int)($body['usuario_id'] ?? 1);
    $destino    = $body['destino']    ?? 'AQUI';
    $items      = $body['items']      ?? [];

    if (!$mesa_id || !count($items))
        respJson(["ok" => false, "error" => "Faltan datos (mesa_id=$mesa_id items=" . count($items) . ")"]);

    // ── 1. Insertar cabecera del pedido ───────────────────────
    $stmt = $dp->prepare(
        "INSERT INTO pedido (id_mesa_fk, id_usuario_fk, etiqueta_destino)
         VALUES (?, ?, ?)"
    );
    if (!$stmt) respJson(["ok" => false, "error" => "prepare pedido: " . $dp->error]);
    $stmt->bind_param('iis', $mesa_id, $usuario_id, $destino);
    $stmt->execute();
    if ($stmt->error) respJson(["ok" => false, "error" => "execute pedido: " . $stmt->error]);
    $id_pedido = (int)$stmt->insert_id;
    $stmt->close();
    if (!$id_pedido) respJson(["ok" => false, "error" => "insert_id = 0, revisa tabla pedido"]);
    // ── 2. Insertar cada ítem en detalle_pedido ───────────────
    $errores = [];

    foreach ($items as $item) {
        // ── Resolver id_producto y ruta_defecto desde BD ──────
        $id_producto  = 0;
        $ruta_defecto = 'cocina';
        $nb = $dp->real_escape_string($item['nombre'] ?? '');

        // 1) Buscar por nombre exacto
        $r = $dp->query("SELECT id_producto, ruta_defecto FROM producto WHERE nombre = '$nb' AND activo = 1 LIMIT 1");
        if ($r && $r->num_rows) {
            $rp = $r->fetch_assoc();
            $id_producto  = (int)$rp['id_producto'];
            $ruta_defecto = $rp['ruta_defecto'];
        }
        // 2) Fallback: id numérico del JS (por si coincide)
        if (!$id_producto) {
            $id_js = (int)($item['id'] ?? 0);
            if ($id_js) {
                $r2 = $dp->query("SELECT id_producto, ruta_defecto FROM producto WHERE id_producto = $id_js AND activo = 1 LIMIT 1");
                if ($r2 && $r2->num_rows) {
                    $rp = $r2->fetch_assoc();
                    $id_producto  = (int)$rp['id_producto'];
                    $ruta_defecto = $rp['ruta_defecto'];
                }
            }
        }
        // 3) Fallback LIKE: cubre "Crepa Dulce (2 Ing.)" → "Crepa Dulce"
        if (!$id_producto) {
            $nb_base = $dp->real_escape_string(trim(strtok($item['nombre'] ?? '', '(')));
            if ($nb_base) {
                $r3 = $dp->query("SELECT id_producto, ruta_defecto FROM producto WHERE nombre LIKE '$nb_base%' AND activo = 1 LIMIT 1");
                if ($r3 && $r3->num_rows) {
                    $rp = $r3->fetch_assoc();
                    $id_producto  = (int)$rp['id_producto'];
                    $ruta_defecto = $rp['ruta_defecto'];
                }
            }
        }

        if (!$id_producto) {
            $errores[] = "Producto no encontrado en BD: '" . ($item['nombre'] ?? '?') . "'";
            continue;
        }

        $cantidad  = (int)   ($item['cantidad']    ?? 1);
        $precio    = (float) ($item['precio']      ?? 0);
        $extra     = (float) ($item['extraPrecio'] ?? 0);
        $nota      = (string)($item['nota']        ?? '');
        $dest_item = (string)($item['destinoItem'] ?? 'aqui');

        // Usar ruta del JS si es válida; si no, usar ruta_defecto de la BD
        $ruta_js = (string)($item['ruta'] ?? '');
        $ruta    = in_array($ruta_js, ['cocina', 'barra']) ? $ruta_js : $ruta_defecto;

        // Nuevo statement por ítem — 8 parámetros: i i i d d s s s
        $si = $dp->prepare(
            "INSERT INTO detalle_pedido
             (id_pedido_fk, id_producto_fk, cantidad,
              precio_unitario, extra_precio,
              nota, destino_item, ruta_area)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
        );
        if (!$si) { $errores[] = "prepare: " . $dp->error; continue; }

        $si->bind_param('iiiddsss',
            $id_pedido, $id_producto, $cantidad,
            $precio, $extra,
            $nota, $dest_item, $ruta
        );
        $si->execute();
        if ($si->error) $errores[] = $si->error;
        $si->close();
    }

    respJson(["ok" => true, "id_pedido" => $id_pedido, "errores" => $errores]);
}

// ── PEDIDOS ACTIVOS de todas las mesas ───────────────────────
if ($accion === 'activos') {
    $res = $dp->query(
        "SELECT p.id_pedido, p.id_mesa_fk, m.numero_mesa,
                p.timestamp_envio, p.estado_general, p.etiqueta_destino,
                dp.id_detalle, pr.nombre AS producto,
                dp.cantidad, dp.precio_unitario, dp.extra_precio,
                dp.nota, dp.estado_item, dp.destino_item, dp.ruta_area
         FROM pedido p
         JOIN mesa m ON m.id_mesa = p.id_mesa_fk
         JOIN detalle_pedido dp ON dp.id_pedido_fk = p.id_pedido
         JOIN producto pr ON pr.id_producto = dp.id_producto_fk
         WHERE p.estado_general != 'entregado'
         ORDER BY p.timestamp_envio ASC"
    );
    if (!$res) respJson(["ok" => false, "error" => $dp->error]);

    $pedidos = [];
    while ($row = $res->fetch_assoc()) {
        $pid = $row['id_pedido'];
        if (!isset($pedidos[$pid])) {
            $pedidos[$pid] = [
                'id_pedido'       => $pid,
                'id_mesa'         => $row['id_mesa_fk'],
                'numero_mesa'     => $row['numero_mesa'],
                'timestamp_envio' => $row['timestamp_envio'],
                'estado_general'  => $row['estado_general'],
                'etiqueta'        => $row['etiqueta_destino'],
                'items'           => []
            ];
        }
        $pedidos[$pid]['items'][] = [
            'id_detalle'  => $row['id_detalle'],
            'producto'    => $row['producto'],
            'cantidad'    => $row['cantidad'],
            'precio'      => $row['precio_unitario'],
            'extra'       => $row['extra_precio'],
            'nota'        => $row['nota'],
            'estado_item' => $row['estado_item'],
            'destino'     => $row['destino_item'],
            'ruta'        => $row['ruta_area'],
        ];
    }
    respJson(["ok" => true, "pedidos" => array_values($pedidos)]);
}

// ── CAMBIAR ESTADO de un pedido ───────────────────────────────
if ($accion === 'estado') {
    $id_pedido = (int)($body['id_pedido'] ?? 0);
    $estado    = $body['estado'] ?? '';
    $validos   = ['pendiente','preparando','listo','entregado'];
    if (!$id_pedido || !in_array($estado, $validos))
        respJson(["ok" => false, "error" => "Parámetros inválidos"]);

    $stmt = $dp->prepare("UPDATE pedido SET estado_general = ? WHERE id_pedido = ?");
    $stmt->bind_param('si', $estado, $id_pedido);
    $stmt->execute();

    // Si el pedido queda listo, marcar notifs intermedias de esa mesa como leídas
    // (iniciando, parcial, etc. ya no son relevantes)
    if ($estado === 'listo' || $estado === 'entregado') {
        $r = $dp->query("SELECT id_mesa_fk FROM pedido WHERE id_pedido = $id_pedido LIMIT 1");
        if ($r && $r->num_rows > 0) {
            $id_mesa = (int)$r->fetch_assoc()['id_mesa_fk'];
            $dp->query("UPDATE notificacion SET leida = 1
                        WHERE id_mesa_fk = $id_mesa AND leida = 0
                        AND tipo IN ('iniciando','parcial','cocina_lista','bebidas_listas')");
        }
    }
    respJson(["ok" => true]);
}

// ── ESTADOS DE ÍTEMS para el polling de mesas ────────────────
// GET ?accion=estados_items
// Devuelve el estado actual de cada detalle_pedido de pedidos activos
// para que app.js pueda mostrar palomita/⏳/· en las tarjetas de mesa
if ($accion === 'estados_items') {
    $res = $dp->query("
        SELECT
            dp.id_detalle,
            dp.estado_item,
            p.id_mesa_fk,
            pr.nombre
        FROM detalle_pedido dp
        JOIN pedido  p  ON p.id_pedido    = dp.id_pedido_fk
        JOIN producto pr ON pr.id_producto = dp.id_producto_fk
        JOIN mesa m      ON m.id_mesa      = p.id_mesa_fk
        WHERE (
            p.estado_general NOT IN ('entregado')
            OR (
                p.estado_general = 'entregado'
                AND p.timestamp_envio > DATE_SUB(NOW(), INTERVAL 10 MINUTE)
            )
        )
          AND m.estado NOT IN ('libre')
        ORDER BY dp.id_detalle
    ");
    if (!$res) respJson(["ok" => false, "error" => $dp->error]);
    $items = [];
    while ($row = $res->fetch_assoc()) {
        $items[] = [
            'id_detalle'  => (int)$row['id_detalle'],
            'id_mesa_fk'  => (int)$row['id_mesa_fk'],
            'nombre'      => $row['nombre'],
            'estado_item' => $row['estado_item'],
        ];
    }
    respJson(["ok" => true, "items" => $items]);
}

// ── CAMBIAR ESTADO de un ítem ─────────────────────────────────
if ($accion === 'item_estado') {
    $id_detalle  = (int)($body['id_detalle']  ?? 0);
    $estado_item = $body['estado_item'] ?? '';
    $validos     = ['pendiente','preparando','listo'];
    if (!$id_detalle || !in_array($estado_item, $validos))
        respJson(["ok" => false, "error" => "Parámetros inválidos"]);

    $stmt = $dp->prepare("UPDATE detalle_pedido SET estado_item = ? WHERE id_detalle = ?");
    $stmt->bind_param('si', $estado_item, $id_detalle);
    $stmt->execute();
    // El trigger trg_mesa_entregado revisa si todos los ítems están listos
    respJson(["ok" => true]);
}

respJson(["ok" => false, "error" => "Acción desconocida: '$accion'"]);
?>
