<?php
// ============================================================
//  API de Pacientes
//  GET  /api/pacientes.php       → Listar pacientes
//  POST /api/pacientes.php       → Crear paciente
// ============================================================

require_once __DIR__ . '/config.php';

$pdo    = getDB();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $stmt = $pdo->query(
        'SELECT id, nombre, apellido, telefono, email, fecha_nacimiento
         FROM pacientes
         ORDER BY nombre ASC'
    );
    echo json_encode($stmt->fetchAll());
    exit;
}

if ($method === 'POST') {
    $body = json_decode(file_get_contents('php://input'), true);

    if (empty($body['nombre']) || empty($body['apellido'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Nombre y apellido son requeridos']);
        exit;
    }

    // --- VERIFICACIÓN DE DUPLICADOS ---
    // Buscamos si ya existe alguien con mismo nombre, apellido y (email o teléfono o fecha de nacimiento)
    $sqlCheck = 'SELECT id FROM pacientes WHERE nombre = :nombre AND apellido = :apellido';
    $checkParams = [':nombre' => $body['nombre'], ':apellido' => $body['apellido']];

    if (!empty($body['email'])) {
        $sqlCheck .= ' AND email = :email';
        $checkParams[':email'] = $body['email'];
    } elseif (!empty($body['telefono'])) {
        $sqlCheck .= ' AND telefono = :telefono';
        $checkParams[':telefono'] = $body['telefono'];
    } elseif (!empty($body['fecha_nacimiento'])) {
        $sqlCheck .= ' AND fecha_nacimiento = :f_nac';
        $checkParams[':f_nac'] = $body['fecha_nacimiento'];
    }
    
    // Si no hay ninguno de los campos extras, al menos validamos nombre y apellido exactos
    $stmtCheck = $pdo->prepare($sqlCheck);
    $stmtCheck->execute($checkParams);
    
    if ($stmtCheck->fetch()) {
        http_response_code(409); // Conflict
        echo json_encode(['error' => 'Ya existe un paciente registrado con estos datos (nombre, apellido y contacto o fecha de nacimiento coinciden)']);
        exit;
    }
    // ---------------------------------

    $stmt = $pdo->prepare(
        'INSERT INTO pacientes (nombre, apellido, telefono, email, fecha_nacimiento, direccion, notas)
         VALUES (:nombre, :apellido, :telefono, :email, :fecha_nacimiento, :direccion, :notes)'
    );
    $stmt->execute([
        ':nombre'           => $body['nombre'],
        ':apellido'         => $body['apellido'],
        ':telefono'         => $body['telefono']          ?? null,
        ':email'            => $body['email']             ?? null,
        ':fecha_nacimiento' => $body['fecha_nacimiento']  ?? null,
        ':direccion'        => $body['direccion']         ?? null,
        ':notes'            => $body['notas']             ?? null,
    ]);

    echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Método no permitido']);
