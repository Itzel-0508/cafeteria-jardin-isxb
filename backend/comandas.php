<?php
// ================================================================
//  comandas.php
//  GET  ?area=cocina|barra        → comandas activas del área
//  POST ?accion=notificar         { mesa, origen, destino, tipo, mensaje }
//  GET  ?accion=notificaciones&destino=mesero  → notifs pendientes
//  POST ?accion=marcar_leida      { id_notif }
// ================================================================
error_reporting(0); // suprimir warnings que rompen el JSON
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

require_once 'conexion.php';

$accion = $_GET['accion'] ?? '';
$area   = $_GET['area']   ?? '';
$body   = json_decode(file_get_contents('php://input'), true) ?? [];

// ── COMANDAS ACTIVAS por área ─────────────────────────────────
if ($area === 'cocina' || $area === 'barra') {
    $vista = $area === 'cocina' ? 'vista_cocina_activa' : 'vista_barra_activa';
    $res   = $dp->query("SELECT * FROM `$vista`");
    if (!$res) respJson(["ok" => false, "error" => $dp->error]);

    // Agrupar por pedido
    $comandas = [];
    while ($row = $res->fetch_assoc()) {
        $pid = $row['id_pedido'];
        // BUG FIX: usar id_pedido directamente como clave única (no numero_mesa)
        // para evitar mezcla de productos cuando hay varias mesas activas
        if (!isset($comandas[$pid])) {
            // BUG FIX: el timestamp viene de BD en segundos reales del servidor
            // Se convierte a ms para JS y se marca como '_fromBD' para que el
            // cliente NO lo sobreescriba con un valor de localStorage más viejo
            $ts_ms = strtotime($row['timestamp_envio']) * 1000;
            $comandas[$pid] = [
                'id'             => 'CMD-BD-' . $pid,   // prefijo BD para evitar colisión con ids de localStorage
                'id_pedido'      => $pid,
                'mesa'           => $row['numero_mesa'],
                'hora'           => date('H:i', strtotime($row['timestamp_envio'])),
                'timestamp'      => $ts_ms,
                '_ts'            => $ts_ms,              // BUG FIX: forzar _ts igual al timestamp real de BD
                'estadoGeneral'  => $row['estado_general'],
                'etiqueta'       => $row['etiqueta_destino'],
                'destino'        => strtolower($row['etiqueta_destino']) === 'llevar' ? 'llevar' : 'aqui',
                'minutos_espera' => $row['minutos_espera'],
                'items'          => []
            ];
        }
        $comandas[$pid]['items'][] = [
            'id_detalle'   => $row['id_detalle'],
            'nombre'       => $row['producto'],
            'cantidad'     => (int)$row['cantidad'],
            'nota'         => $row['nota'] ?? '',
            'estadoItem'   => $row['estado_item'],
            'destinoItem'  => $row['destino_item'],
            // BUG FIX: tsEnvio de cada ítem debe ser el mismo que el pedido,
            // no un valor independiente que puede diferir en milisegundos
            'tsEnvio'      => strtotime($row['timestamp_envio']) * 1000,
        ];
    }

    respJson(["ok" => true, "comandas" => array_values($comandas)]);
}

// ── CAMBIAR ESTADO de un ítem (desde cocina/barra) ───────────
if ($accion === 'item_estado') {
    $id_detalle  = (int)($body['id_detalle']  ?? 0);
    $estado_item = $body['estado_item'] ?? '';
    $validos     = ['pendiente','preparando','listo'];
    if (!$id_detalle || !in_array($estado_item, $validos))
        respJson(["ok" => false, "error" => "Parámetros inválidos"]);

    $stmt = $dp->prepare("UPDATE detalle_pedido SET estado_item = ? WHERE id_detalle = ?");
    $stmt->bind_param('si', $estado_item, $id_detalle);
    $stmt->execute();
    if ($stmt->error) respJson(["ok" => false, "error" => $stmt->error]);

    // Si todos los ítems del pedido están listos, el trigger trg_pedido_listo
    // actualiza automáticamente el estado_general del pedido
    respJson(["ok" => true]);
}

// ── CANCELAR pedido completo desde cocina/barra ──────────────
if ($accion === 'cancelar') {
    $id_pedido = (int)($body['id_pedido'] ?? 0);
    if (!$id_pedido)
        respJson(["ok" => false, "error" => "id_pedido requerido"]);

    // Marcar como entregado (equivalente a cancelado — libera la mesa via trigger)
    // o simplemente borrar los detalles para que no aparezca más en las vistas
    $stmt = $dp->prepare("UPDATE pedido SET estado_general = 'entregado' WHERE id_pedido = ?");
    $stmt->bind_param('i', $id_pedido);
    $stmt->execute();
    if ($stmt->error) respJson(["ok" => false, "error" => $stmt->error]);

    respJson(["ok" => true]);
}

// ── REGISTRAR NOTIFICACIÓN entre áreas ───────────────────────
if ($accion === 'notificar') {
    $numero_mesa = (int)($body['mesa']    ?? 0);
    $origen      = $body['origen']   ?? '';
    $destino     = $body['destino']  ?? '';
    $tipo        = $body['tipo']     ?? '';
    $mensaje     = $body['mensaje']  ?? '';

    if (!$numero_mesa || !$origen || !$destino || !$tipo)
        respJson(["ok" => false, "error" => "Faltan parámetros"]);

    // Buscar id_mesa por número
    $r = $dp->query("SELECT id_mesa FROM mesa WHERE numero_mesa = $numero_mesa LIMIT 1");
    if (!$r || !$r->num_rows) respJson(["ok" => false, "error" => "Mesa no encontrada"]);
    $id_mesa = (int)$r->fetch_assoc()['id_mesa'];

    $stmt = $dp->prepare(
        "INSERT INTO notificacion (id_mesa_fk, origen, destino, tipo, mensaje)
         VALUES (?, ?, ?, ?, ?)"
    );
    $stmt->bind_param('issss', $id_mesa, $origen, $destino, $tipo, $mensaje);
    $stmt->execute();
    if ($dp->error) respJson(["ok" => false, "error" => $dp->error]);

    respJson(["ok" => true]);
}

// ── OBTENER NOTIFICACIONES no leídas ─────────────────────────
if ($accion === 'notificaciones') {
    $destino = $_GET['destino'] ?? 'mesero';
    $res = $dp->query(
        "SELECT n.*, m.numero_mesa
         FROM notificacion n
         JOIN mesa m ON m.id_mesa = n.id_mesa_fk
         WHERE n.destino IN ('$destino','todos') AND n.leida = 0
           AND n.ts > DATE_SUB(NOW(), INTERVAL 30 MINUTE)
         ORDER BY n.ts DESC"
    );
    $notifs = [];
    while ($row = $res->fetch_assoc()) $notifs[] = $row;
    respJson(["ok" => true, "notificaciones" => $notifs]);
}

// ── MARCAR NOTIFICACIÓN como leída ───────────────────────────
if ($accion === 'marcar_leida') {
    $id = (int)($body['id_notif'] ?? 0);
    if (!$id) respJson(["ok" => false, "error" => "id_notif requerido"]);
    $dp->query("UPDATE notificacion SET leida = 1 WHERE id_notif = $id");
    respJson(["ok" => true]);
}

respJson(["ok" => false, "error" => "Acción desconocida"]);
?>
