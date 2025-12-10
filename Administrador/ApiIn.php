<?php
/**
 * API para Inscripciones (versión corregida)
 */

require_once "ADInscri.php";
require_once "Conexion.php";

header("Content-Type: application/json; charset=utf-8");

// ======================================================
// Normalizar POST + JSON Body
// ======================================================
if (empty($_POST)) {
    $json = file_get_contents("php://input");
    $data = json_decode($json, true);
    if (is_array($data)) {
        foreach ($data as $k => $v) $_POST[$k] = $v;
    }
}

function post($key, $default = null) {
    return isset($_POST[$key]) ? $_POST[$key] : $default;
}

$response = ["error" => false];

// ======================================================
// Validar parámetro API
// ======================================================
if (!isset($_GET["api"]) || $_GET["api"] == "") {
    echo json_encode(["error"=>true, "msg"=>"No se llamó a Api"]);
    exit;
}

$api = $_GET["api"];
$db  = new ADInscri();


// ======================================================
// 1️⃣ ACTUALIZAR ESTADO DE INSCRIPCIÓN
// ======================================================
if ($api == "actualizarEstado") {

    $id  = post("id_inscripcion");
    $est = post("estado");

    if (!$id || $est === null) {
        echo json_encode(["error"=>true, "msg"=>"Faltan parámetros"]);
        exit;
    }

    $response["msg"] = $db->actualizarEstado($id, $est);
    echo json_encode($response);
    exit;
}


// ======================================================
// 2️⃣ LISTAR SOLICITUDES PENDIENTES
// ======================================================
if ($api == "listarPendientes") {
    
$result = $db->listarPendientes();

echo json_encode([
    "error" => false,
    "inscripciones" => $result  // ← ahora es un array directo
]);
exit;

}


// ======================================================
// 3️⃣ ELIMINAR INSCRIPCIÓN
// ======================================================
if ($api == "eliminarInscripcion") {

    $id = post("id_inscripcion");

    if (!$id) {
        echo json_encode(["error"=>true, "msg"=>"Falta id_inscripcion"]);
        exit;
    }

    $response["msg"] = $db->eliminarInscripcion($id);
    echo json_encode($response);
    exit;
}


// ======================================================
// ❌ DEFAULT
// ======================================================
echo json_encode([
    "error" => true,
    "msg"   => "Acción no válida"
]);
exit;

?>
