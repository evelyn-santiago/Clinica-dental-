<?php
// ============================================================
//  API de Citas  –  CRUD completo
//  GET    /api/citas.php              → Listar citas
//  GET    /api/citas.php?historial=1  → Solo citas completadas
//  GET    /api/citas.php?resumen=1    → Estadísticas del panel
//  POST   /api/citas.php              → Crear cita
//  PUT    /api/citas.php              → Actualizar cita  { id, ... }
//  DELETE /api/citas.php?id=X         → Eliminar cita
// ============================================================

require_once __DIR__ . '/config.php';

$pdo    = getDB();
$method = $_SERVER['REQUEST_METHOD'];

// ─────────────────────────────────────────────────────────────
//  GET — Listar citas
// ─────────────────────────────────────────────────────────────
if ($method === 'GET') {

    // Estadísticas para el panel resumen
    if (isset($_GET['resumen'])) {
        $hoy = date('Y-m-d');

        $citasHoy = $pdo->prepare(
            'SELECT COUNT(*) FROM citas WHERE fecha = :hoy AND estado != "cancelada"'
        );
        $citasHoy->execute([':hoy' => $hoy]);

        $pacientesAtendidos = $pdo->prepare(
            'SELECT COUNT(DISTINCT paciente_id) FROM citas WHERE estado = "completada"'
        );
        $pacientesAtendidos->execute();

        echo json_encode([
            'citas_hoy'           => (int) $citasHoy->fetchColumn(),
            'pacientes_atendidos' => (int) $pacientesAtendidos->fetchColumn(),
        ]);
        exit;
    }

    // Historial: citas completadas
    if (isset($_GET['historial'])) {
        $stmt = $pdo->prepare(
            'SELECT c.id, c.fecha, c.hora, c.procedimiento, c.estado, c.notas,
                    CONCAT(p.nombre, " ", p.apellido) AS paciente,
                    u.nombre AS odontologo
             FROM citas c
             JOIN pacientes p ON p.id = c.paciente_id
             JOIN usuarios  u ON u.id = c.odontologo_id
             WHERE c.estado = "completada"
             ORDER BY c.fecha DESC, c.hora DESC
             LIMIT 20'
        );
        $stmt->execute();
        echo json_encode($stmt->fetchAll());
        exit;
    }

    // Citas pendientes / confirmadas
    $stmt = $pdo->prepare(
        'SELECT c.id, c.fecha, c.hora, c.procedimiento, c.estado, c.notas,
                CONCAT(p.nombre, " ", p.apellido) AS paciente,
                u.nombre AS odontologo
         FROM citas c
         JOIN pacientes p ON p.id = c.paciente_id
         JOIN usuarios  u ON u.id = c.odontologo_id
         WHERE c.estado IN ("pendiente","confirmada")
         ORDER BY c.fecha ASC, c.hora ASC'
    );
    $stmt->execute();
    echo json_encode($stmt->fetchAll());
    exit;
}

// ─────────────────────────────────────────────────────────────
//  POST — Crear nueva cita
// ─────────────────────────────────────────────────────────────
if ($method === 'POST') {
    $body = json_decode(file_get_contents('php://input'), true);

    $required = ['paciente_id', 'odontologo_id', 'fecha', 'hora', 'procedimiento'];
    foreach ($required as $campo) {
        if (empty($body[$campo])) {
            http_response_code(400);
            echo json_encode(['error' => "Campo requerido: $campo"]);
            exit;
        }
    }

    // Validar que el paciente no tenga ya una cita activa para este día
    $check = $pdo->prepare(
        'SELECT id FROM citas 
         WHERE paciente_id = :paciente_id 
           AND fecha = :fecha 
           AND estado IN ("pendiente", "confirmada")
         LIMIT 1'
    );
    $check->execute([
        ':paciente_id' => $body['paciente_id'],
        ':fecha'       => $body['fecha']
    ]);

    if ($check->fetch()) {
        http_response_code(400);
        echo json_encode(['error' => 'El paciente ya tiene una cita activa para este día.']);
        exit;
    }

    $stmt = $pdo->prepare(
        'INSERT INTO citas (paciente_id, odontologo_id, fecha, hora, procedimiento, estado, notas)
         VALUES (:paciente_id, :odontologo_id, :fecha, :hora, :procedimiento, :estado, :notas)'
    );
    $stmt->execute([
        ':paciente_id'   => $body['paciente_id'],
        ':odontologo_id' => $body['odontologo_id'],
        ':fecha'         => $body['fecha'],
        ':hora'          => $body['hora'],
        ':procedimiento' => $body['procedimiento'],
        ':estado'        => $body['estado'] ?? 'pendiente',
        ':notas'         => $body['notas']  ?? '',
    ]);

    echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
    exit;
}

// ─────────────────────────────────────────────────────────────
//  PUT — Actualizar cita (estado, notas, etc.)
// ─────────────────────────────────────────────────────────────
if ($method === 'PUT') {
    $body = json_decode(file_get_contents('php://input'), true);

    if (empty($body['id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Se requiere el id de la cita']);
        exit;
    }

    $campos  = [];
    $params  = [':id' => $body['id']];
    $allowed = ['fecha', 'hora', 'procedimiento', 'estado', 'notas'];

    foreach ($allowed as $campo) {
        if (array_key_exists($campo, $body)) {
            $campos[] = "$campo = :$campo";
            $params[":$campo"] = $body[$campo];
        }
    }

    if (empty($campos)) {
        http_response_code(400);
        echo json_encode(['error' => 'No hay campos para actualizar']);
        exit;
    }

    $sql  = 'UPDATE citas SET ' . implode(', ', $campos) . ' WHERE id = :id';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    echo json_encode(['success' => true, 'filas' => $stmt->rowCount()]);
    exit;
}

// ─────────────────────────────────────────────────────────────
//  DELETE — Cancelar / eliminar cita
// ─────────────────────────────────────────────────────────────
if ($method === 'DELETE') {
    $id = intval($_GET['id'] ?? 0);

    if (!$id) {
        http_response_code(400);
        echo json_encode(['error' => 'Se requiere el id de la cita']);
        exit;
    }

    // Marcar como cancelada en vez de borrar físicamente
    $stmt = $pdo->prepare('UPDATE citas SET estado = "cancelada" WHERE id = :id');
    $stmt->execute([':id' => $id]);

    echo json_encode(['success' => true]);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Método no permitido']);
