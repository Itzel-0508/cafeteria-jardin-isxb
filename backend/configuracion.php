<?php
// ================================================================
//  configuracion.php — API de Configuración del Sistema
//  Guarda y lee ajustes en la tabla `config_sistema`
//
//  GET  ?accion=leer              → devuelve config actual
//  POST ?accion=guardar_smtp      → guarda credenciales de correo
//  POST ?accion=probar_smtp       → envía correo de prueba
// ================================================================
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

require_once 'conexion.php';

// ── Crear tabla de configuración si no existe ─────────────────
$dp->query("
    CREATE TABLE IF NOT EXISTS config_sistema (
        clave   VARCHAR(80)   NOT NULL PRIMARY KEY,
        valor   TEXT          NOT NULL,
        ts      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP
            ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

$accion = $_GET['accion'] ?? '';
$body   = json_decode(file_get_contents('php://input'), true) ?? [];

// ================================================================
//  LEER configuración actual
// ================================================================
if ($accion === 'leer') {
    $res  = $dp->query("SELECT clave, valor FROM config_sistema");
    $conf = [];
    while ($r = $res->fetch_assoc()) $conf[$r['clave']] = $r['valor'];

    // Ocultar contraseña: devolver solo si tiene valor (sin exponerla)
    respJson([
        'ok'             => true,
        'smtp_host'      => $conf['smtp_host']      ?? 'smtp.gmail.com',
        'smtp_port'      => $conf['smtp_port']      ?? '587',
        'smtp_user'      => $conf['smtp_user']      ?? '',
        'smtp_pass_set'  => !empty($conf['smtp_pass']),
        'smtp_pass_value'=> $conf['smtp_pass']      ?? '', // valor real para el ojito
        'smtp_from_name' => $conf['smtp_from_name'] ?? 'Jardín POS',
        'negocio_nombre' => $conf['negocio_nombre'] ?? '',
    ]);
}

// ================================================================
//  GUARDAR configuración SMTP
// ================================================================
if ($accion === 'guardar_smtp') {
    $host      = trim($body['smtp_host']      ?? 'smtp.gmail.com');
    $port      = (int)($body['smtp_port']      ?? 587);
    $user      = trim($body['smtp_user']      ?? '');
    $pass      = trim($body['smtp_pass']      ?? '');
    $from_name = trim($body['smtp_from_name'] ?? 'Jardín POS');
    $negocio   = trim($body['negocio_nombre'] ?? '');

    if (!$user) respJson(['ok' => false, 'error' => 'El correo de envío es obligatorio']);
    if (!filter_var($user, FILTER_VALIDATE_EMAIL))
        respJson(['ok' => false, 'error' => 'El correo de envío no tiene formato válido']);

    $pares = [
        'smtp_host'      => $host,
        'smtp_port'      => $port,
        'smtp_user'      => $user,
        'smtp_from_name' => $from_name,
        'negocio_nombre' => $negocio,
    ];

    // La contraseña solo se actualiza si el admin envió una nueva
    if ($pass !== '') {
        $pares['smtp_pass'] = $pass;
    }

    $stmt = $dp->prepare(
        "INSERT INTO config_sistema (clave, valor)
         VALUES (?, ?)
         ON DUPLICATE KEY UPDATE valor = VALUES(valor)"
    );

    foreach ($pares as $clave => $valor) {
        $stmt->bind_param('ss', $clave, $valor);
        $stmt->execute();
        if ($dp->error) respJson(['ok' => false, 'error' => $dp->error]);
    }

    respJson(['ok' => true]);
}

// ================================================================
//  PROBAR SMTP: envía un correo de prueba al mismo admin
// ================================================================
if ($accion === 'probar_smtp') {
    $destinatario = trim($body['correo_prueba'] ?? '');
    if (!$destinatario || !filter_var($destinatario, FILTER_VALIDATE_EMAIL))
        respJson(['ok' => false, 'error' => 'Ingresa un correo válido para la prueba']);

    // Cargar config guardada
    $res  = $dp->query("SELECT clave, valor FROM config_sistema");
    $conf = [];
    while ($r = $res->fetch_assoc()) $conf[$r['clave']] = $r['valor'];

    if (empty($conf['smtp_user']) || empty($conf['smtp_pass']))
        respJson(['ok' => false, 'error' => 'Guarda primero el correo y la contraseña SMTP antes de probar']);

    // Inyectar config en las constantes que usa correo_config.php
    _definirSMTP($conf);

    require_once __DIR__ . '/correo_config.php';

    $nombre_neg = $conf['negocio_nombre'] ?? 'Jardín POS';
    $html = '<!DOCTYPE html><html><body style="margin:0;background:#F0F4EE;font-family:Arial,sans-serif;">
<div style="max-width:480px;margin:32px auto;background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 8px 24px rgba(0,0,0,.12);">
  <div style="background:linear-gradient(135deg,#2E5226,#4A7A3D);padding:28px 24px;text-align:center;">
    <div style="font-size:36px;">🌿</div>
    <h2 style="color:#fff;margin:8px 0 0;font-size:22px;">' . htmlspecialchars($nombre_neg) . '</h2>
  </div>
  <div style="padding:32px 28px;text-align:center;">
    <div style="font-size:48px;margin-bottom:16px;">✅</div>
    <h3 style="color:#2E5226;font-size:20px;margin-bottom:12px;">¡Correo configurado correctamente!</h3>
    <p style="color:#6B7F65;font-size:14px;line-height:1.6;">
      El sistema de correo de <strong>' . htmlspecialchars($nombre_neg) . '</strong><br>
      está funcionando y listo para enviar PINs.
    </p>
    <hr style="border:none;border-top:1px solid #D4E0CF;margin:24px 0 16px;">
    <p style="color:#6B7F65;font-size:11px;">Este es un correo de prueba generado automáticamente.</p>
  </div>
</div></body></html>';

    $resultado = enviarCorreo($destinatario, '✅ Prueba de correo — ' . $nombre_neg, $html);
    respJson($resultado);
}

// ================================================================
//  Helpers
// ================================================================
function _definirSMTP(array $conf): void {
    // Solo definir si aún no están definidas (por si correo_config.php ya las tiene)
    $map = [
        'SMTP_HOST'      => $conf['smtp_host']      ?? 'smtp.gmail.com',
        'SMTP_PORT'      => (int)($conf['smtp_port'] ?? 587),
        'SMTP_USER'      => $conf['smtp_user']       ?? '',
        'SMTP_PASS'      => $conf['smtp_pass']       ?? '',
        'SMTP_FROM'      => $conf['smtp_user']       ?? '',
        'SMTP_FROM_NAME' => $conf['smtp_from_name']  ?? 'Jardín POS',
    ];
    foreach ($map as $k => $v) {
        if (!defined($k)) define($k, $v);
    }
}

respJson(['ok' => false, 'error' => "Acción desconocida: '$accion'"]);
