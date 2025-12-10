<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once "Conexion.php";

// ===============================================
// Respuesta estándar
// ===============================================
$response = ["error" => false];

try {
    $pdo = Conexion::conectar();

    // ===============================================
    // Función para obtener conteos de manera segura
    // ===============================================
    function contar($pdo, $tabla) {
        $stmt = $pdo->prepare("SELECT COUNT(*) AS total FROM $tabla WHERE estado = 1");
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return isset($row['total']) ? (int)$row['total'] : 0;
    }

    // ===============================================
    // Ejecutar los conteos
    // ===============================================
    $docentes    = contar($pdo, "docente");
    $estudiantes = contar($pdo, "estudiante");
    $grupos      = contar($pdo, "grupo");

    // ===============================================
    // Respuesta final
    // ===============================================
    $response["msg"] = [
        "docentes"    => $docentes,
        "estudiantes" => $estudiantes,
        "grupos"      => $grupos
    ];

} catch (PDOException $e) {

    $response["error"] = true;
    $response["msg"] = "Error: " . $e->getMessage();
}

echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
?>
