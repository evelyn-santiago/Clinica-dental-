<?php
// ============================================================
//  API de Login
//  POST /api/login.php  { "usuario": "...", "password": "..." }
// ============================================================

require_once __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

// Obtener datos del cuerpo JSON
$body = json_decode(file_get_contents('php://input'), true);

$usuario  = trim($body['usuario']  ?? '');
$password = trim($body['password'] ?? '');

if (empty($usuario) || empty($password)) {
    http_response_code(400);
    echo json_encode(['error' => 'Usuario y contraseña son requeridos']);
    exit;
}

// Buscar usuario en la base de datos (password con MD5 igual que datos semilla)
$pdo  = getDB();
$stmt = $pdo->prepare(
    'SELECT id, usuario, nombre, rol, avatar_url
     FROM usuarios
     WHERE usuario = :usuario
       AND password = MD5(:password)
       AND activo = 1
     LIMIT 1'
);
$stmt->execute([':usuario' => $usuario, ':password' => $password]);
$user = $stmt->fetch();

if (!$user) {
    http_response_code(401);
    echo json_encode(['error' => 'Usuario o contraseña incorrectos']);
    exit;
}

// Iniciar sesión PHP para guardar datos del usuario
session_start();
$_SESSION['usuario_id']  = $user['id'];
$_SESSION['usuario']     = $user['usuario'];
$_SESSION['nombre']      = $user['nombre'];
$_SESSION['rol']         = $user['rol'];
$_SESSION['avatar_url']  = $user['avatar_url'];

echo json_encode([
    'success'    => true,
    'mensaje'    => 'Login exitoso',
    'usuario'    => [
        'id'         => $user['id'],
        'nombre'     => $user['nombre'],
        'rol'        => $user['rol'],
        'avatar_url' => $user['avatar_url'],
    ]
]);
