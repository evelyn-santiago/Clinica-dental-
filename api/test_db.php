<?php
require_once 'config.php';
try {
    $pdo = getDB();
    echo json_encode(["status" => "success", "message" => "Conexión exitosa a la base de datos"]);
} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>
