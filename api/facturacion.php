<?php
require_once 'config.php';


$pdo = getDB();
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // Obtener todas las facturas con información de la cita y el paciente
        $stmt = $pdo->prepare('
            SELECT f.*, c.fecha, c.procedimiento, CONCAT(p.nombre, " ", p.apellido) as paciente
            FROM facturas f
            JOIN citas c ON f.cita_id = c.id
            JOIN pacientes p ON c.paciente_id = p.id
            ORDER BY f.fecha_emision DESC
        ');
        $stmt->execute();
        echo json_encode($stmt->fetchAll());
        break;

    case 'POST':
        // Crear una nueva factura
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['cita_id']) || !isset($data['monto'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Faltan datos obligatorios']);
            exit;
        }

        try {
            $stmt = $pdo->prepare('
                INSERT INTO facturas (cita_id, monto, metodo_pago, estado_pago)
                VALUES (:cita_id, :monto, :metodo_pago, :estado_pago)
            ');
            $success = $stmt->execute([
                ':cita_id' => $data['cita_id'],
                ':monto' => $data['monto'],
                ':metodo_pago' => $data['metodo_pago'] ?? 'efectivo',
                ':estado_pago' => $data['estado_pago'] ?? 'pagado'
            ]);
            
            echo json_encode(['success' => $success, 'id' => $pdo->lastInsertId()]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;

    case 'DELETE':
        // Eliminar factura
        $id = $_GET['id'] ?? null;
        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'ID no proporcionado']);
            exit;
        }
        $stmt = $pdo->prepare('DELETE FROM facturas WHERE id = :id');
        $success = $stmt->execute([':id' => $id]);
        echo json_encode(['success' => $success]);
        break;

    default:
        http_response_code(405);
        echo json_encode(['error' => 'Método no permitido']);
        break;
}
