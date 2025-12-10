<?php
/**
 * Autor: Alberto Cruz Gaspar (versión corregida)
 * Fecha: 14/11/25
 * API para ESTUDIANTE
 */

require_once "ADEstudiante.php";
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
$db = new ADEstudiante();


// ======================================================
// 1️⃣ ACTUALIZAR ESTADO + PASS (opcional)
// ======================================================
if ($api == "actualizarEstadoPass") {

    $id_estudiante = post("id_estudiante");
    $estado        = post("estado");
    $pass          = post("pass", "");

    if (!$id_estudiante || $estado === null) {
        echo json_encode(["error"=>true, "msg"=>"Faltan parámetros"]);
        exit;
    }

    $response["msg"] = $db->actualizarEstadoPass($id_estudiante, $estado, $pass);
    echo json_encode($response);
    exit;
}



// ======================================================
// 2️⃣ EDITAR ALUMNO EN GRUPO (estado + pass opcional)
// ======================================================
if ($api == "editarAlumnoGrupo") {

    $id = post("id_estudiante");
    $estado = post("estado");
    $pass = post("pass", "");

    if (!$id || $estado === null) {
        echo json_encode(["error"=>true, "msg"=>"Faltan parámetros"]);
        exit;
    }

    $response["msg"] = $db->editarAlumnoGrupo($id, $estado, $pass);
    echo json_encode($response);
    exit;
}



// ======================================================
// 3️⃣ ELIMINAR INSCRIPCIÓN
// ======================================================
if ($api == "eliminarInscripcion") {

    $id_est = post("id_estudiante");
    $id_gpo = post("id_grupo");

    if (!$id_est || !$id_gpo) {
        echo json_encode(["error"=>true, "msg"=>"Faltan parámetros"]);
        exit;
    }

    $response["msg"] = $db->eliminarInscripcion($id_est, $id_gpo);
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
