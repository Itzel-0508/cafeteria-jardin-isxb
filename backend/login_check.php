<?php
// ================================================================
//  login_check.php
//  Valida nombre + PIN. Redirige a la pantalla correcta por rol.
//  Registra el acceso en log_acceso.
// ================================================================
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

require_once 'conexion.php';

// Helper JSON (por si conexion.php no lo tiene)
if (!function_exists('respJson')) {
    function respJson(array $data): void {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}

// Crear tabla de log si no existe
$dp->query("
    CREATE TABLE IF NOT EXISTS log_acceso (
        id_log      INT AUTO_INCREMENT PRIMARY KEY,
        id_usuario  INT NOT NULL,
        nombre      VARCHAR(100) NOT NULL,
        rol         VARCHAR(40)  NOT NULL,
        ts          DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
        ip          VARCHAR(45)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

$body   = json_decode(file_get_contents('php://input'), true) ?? [];
$nombre = trim($body['nombre'] ?? '');
$pin    = trim($body['pin']    ?? '');

if (!$nombre) respJson(["ok" => false, "error" => "Escribe tu nombre"]);
if (!$pin)    respJson(["ok" => false, "error" => "Ingresa tu PIN"]);

// 1. Verificar si el nombre existe
$s1 = $dp->prepare("SELECT id_usuario, nombre, pin, rol, activo FROM usuario WHERE nombre = ? LIMIT 1");
$s1->bind_param('s', $nombre);
$s1->execute();
$r1 = $s1->get_result();

if ($r1->num_rows === 0)
    respJson(["ok" => false, "error" => "El nombre '{$nombre}' no está registrado"]);

$user = $r1->fetch_assoc();

// 2. Verificar cuenta activa
if (!$user['activo'])
    respJson(["ok" => false, "error" => "La cuenta de {$nombre} está desactivada"]);

// 3. Verificar PIN (SHA2-256)
$s2 = $dp->prepare("SELECT id_usuario FROM usuario WHERE nombre = ? AND pin = SHA2(?, 256) AND activo = 1 LIMIT 1");
$s2->bind_param('ss', $nombre, $pin);
$s2->execute();
$r2 = $s2->get_result();

if ($r2->num_rows === 0)
    respJson(["ok" => false, "error" => "PIN incorrecto para {$nombre}"]);

// 4. Registrar acceso
$ip = $_SERVER['REMOTE_ADDR'] ?? '';
$sl = $dp->prepare("INSERT INTO log_acceso (id_usuario, nombre, rol, ip) VALUES (?, ?, ?, ?)");
$sl->bind_param('isss', $user['id_usuario'], $user['nombre'], $user['rol'], $ip);
$sl->execute();

// 5. Destino por rol
// Admin  → se maneja inline en index.php (no redirige al cargar el archivo)
// Mesero → se queda en index.php (pantalla de mesas)
// Cajero → cobro.php  (solo ve las mesas en cobro)
// Cocina → cocina.php (solo ve comandas de cocina)
// Barra  → barra.php  (solo ve comandas de barra)
$destinos = [
    'admin'  => 'index.php',   // JS lo intercepta y muestra panel inline
    'mesero' => 'index.php',
    'cajero' => 'cobro.php',
    'cocina' => 'cocina.php',
    'barra'  => 'barra.php',
];

respJson([
    "ok"         => true,
    "id_usuario" => (int)$user['id_usuario'],
    "nombre"     => $user['nombre'],
    "rol"        => $user['rol'],
    "destino"    => $destinos[$user['rol']] ?? 'index.php'
]);
?>
