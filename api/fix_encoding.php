<?php
require_once 'config.php';

$pdo = getDB();

try {
    // 1. Asegurar que las tablas tengan la colación correcta
    $pdo->exec("ALTER DATABASE clinicadental CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci");
    $pdo->exec("ALTER TABLE usuarios CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci");
    $pdo->exec("ALTER TABLE pacientes CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci");
    $pdo->exec("ALTER TABLE citas CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci");

    // 2. Corregir datos de usuarios
    $usuarios = [
        ['id' => 1, 'nombre' => 'Dr. Juan Pérez'],
        ['id' => 3, 'nombre' => 'Laura Gómez']
    ];
    
    $stmtUser = $pdo->prepare("UPDATE usuarios SET nombre = :nombre WHERE id = :id");
    foreach ($usuarios as $u) {
        $stmtUser->execute($u);
    }

    // 3. Corregir datos de pacientes
    $pacientes = [
        ['id' => 1, 'nombre' => 'María',  'apellido' => 'González'],
        ['id' => 2, 'nombre' => 'Carlos', 'apellido' => 'Rodríguez'],
        ['id' => 3, 'nombre' => 'Ana',    'apellido' => 'Martínez'],
        ['id' => 4, 'nombre' => 'Luis',   'apellido' => 'Fernández'],
        ['id' => 5, 'nombre' => 'Sofía',  'apellido' => 'Ramírez'],
        ['id' => 6, 'nombre' => 'Jorge',  'apellido' => 'Sánchez']
    ];

    $stmtPac = $pdo->prepare("UPDATE pacientes SET nombre = :nombre, apellido = :apellido WHERE id = :id");
    foreach ($pacientes as $p) {
        $stmtPac->execute($p);
    }

    // 4. Corregir datos de citas
    $citas = [
        ['id' => 2, 'procedimiento' => 'Extracción'],
        ['id' => 5, 'procedimiento' => 'Revisión']
    ];

    $stmtCita = $pdo->prepare("UPDATE citas SET procedimiento = :procedimiento WHERE id = :id");
    foreach ($citas as $c) {
        $stmtCita->execute($c);
    }

    echo json_encode(["status" => "success", "message" => "Nombres corregidos con éxito"]);

} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>
