<?php
// ================================================================
//  conexion.php — Conexión MySQL | Jardín POS
// ================================================================
$server   = "localhost";
$username = "root";
$password = "170805";
$bdname   = "CafeteriaISXB";

$dp = new mysqli($server, $username, $password, $bdname, 3306);

if ($dp->connect_error) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(["ok" => false, "error" => "Error de conexión: " . $dp->connect_error]);
    exit;
}

$dp->set_charset("utf8mb4");

// Helper global: respuesta JSON y salir
if (!function_exists('respJson')) {
    function respJson(array $data): void {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}
?>
