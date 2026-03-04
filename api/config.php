<?php
// ============================================================
//  Configuración de conexión a MySQL
//  Ajusta estos valores según tu instalación de XAMPP
// ============================================================

define('DB_HOST',     'localhost');
define('DB_PORT',     '3306');
define('DB_NAME',     'clinicadental');
define('DB_USER',     'root');       // Usuario por defecto en XAMPP
define('DB_PASSWORD', 'nayeli21');   // Contraseña de root MySQL
define('DB_CHARSET',  'utf8mb4');

// ============================================================
//  Función que retorna la conexión PDO
// ============================================================
function getDB(): PDO {
    static $pdo = null;

    if ($pdo === null) {
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            DB_HOST, DB_PORT, DB_NAME, DB_CHARSET
        );
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASSWORD, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Error de conexión a la base de datos: ' . $e->getMessage()]);
            exit;
        }
    }

    return $pdo;
}

// ============================================================
//  Headers CORS y JSON para todas las respuestas de la API
// ============================================================
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}
