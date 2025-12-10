<?php
/**
 * Autor: Mario Pérez Bautista (versión corregida)
 * API para GRUPO
 */

require_once "ADGrupo.php";
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
$db  = new ADGrupo();

if ($api == "listarTodos") {
    $response["msg"] = $db->listarTodos();
    echo json_encode($response);
    exit;
}


// ======================================================
// 1️⃣ LISTAR GRUPOS POR DOCENTE
// ======================================================
if ($api == "listar") {

    $id = post("id_docente");
    if (!$id) {
        echo json_encode(["error"=>true,"msg"=>"Falta id_docente"]);
        exit;
    }

    $response["msg"] = $db->listar($id);
    echo json_encode($response);
    exit;
}


// ======================================================
// 2️⃣ ACTUALIZAR ESTADO DE GRUPO
// ======================================================
if ($api == "actualizarEstado") {

    $id_grupo = post("id_grupo");
    $estado   = post("estado");

    if (!$id_grupo || $estado === null) {
        echo json_encode(["error"=>true,"msg"=>"Faltan parámetros"]);
        exit;
    }

    $response["msg"] = $db->actualizarEstado($id_grupo, $estado);
    echo json_encode($response);
    exit;
}


// ======================================================
// 3️⃣ ELIMINAR GRUPO
// ======================================================
if ($api == "eliminar") {

    $id = post("id_grupo");
    if (!$id) {
        echo json_encode(["error"=>true,"msg"=>"Falta id_grupo"]);
        exit;
    }

    $response["msg"] = $db->eliminar($id);
    echo json_encode($response);
    exit;
}


// ======================================================
// 4️⃣ OBTENER GRUPO POR ID
// ======================================================
if ($api == "obtener") {

    $id = post("id_grupo");
    if (!$id) {
        echo json_encode(["error"=>true,"msg"=>"Falta id_grupo"]);
        exit;
    }

    $response["msg"] = $db->obtenerGrupo($id);
    echo json_encode($response);
    exit;
}


// ======================================================
// 5️⃣ EDITAR GRUPO
// ======================================================
if ($api == "editar") {

    $required = ["id_grupo","clave","periodo","carrera","asignatura","estado"];
    foreach ($required as $r) if (post($r) === null) {
        echo json_encode(["error"=>true,"msg"=>"Falta $r"]);
        exit;
    }

    $response["msg"] = $db->actualizarGrupo(
        post("id_grupo"),
        post("clave"),
        post("periodo"),
        post("carrera"),
        post("asignatura"),
        post("estado")
    );

    echo json_encode($response);
    exit;
}


// ======================================================
// 6️⃣ LISTAR ALUMNOS ACEPTADOS EN GRUPO
// ======================================================
if ($api == "listarAlumnosGrupo") {

    $id = post("id_grupo");
    if (!$id) {
        echo json_encode(["error"=>true,"msg"=>"Falta id_grupo"]);
        exit;
    }

    $response["msg"] = $db->listarAlumnosGrupo($id);
    echo json_encode($response);
    exit;
}


// ======================================================
// ❌ DEFAULT: Operación no válida
// ======================================================
echo json_encode([
    "error" => true,
    "msg"   => "Acción no válida"
]);
exit;

?>
