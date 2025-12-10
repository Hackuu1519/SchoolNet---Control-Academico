<?php
/**
 * Autor: Mario Pérez Bautista (corrección final)
 * Descripción: API para gestión de DOCENTE (versión Android Friendly)
 */

require_once "ADDocente.php";
require_once "Conexion.php";

header("Content-Type: application/json; charset=utf-8");

// ======================================================
// Normalizar POST y JSON
// ======================================================
if (empty($_POST)) {
    $json = file_get_contents("php://input");
    $data = json_decode($json, true);
    if (is_array($data)) {
        foreach ($data as $k => $v) $_POST[$k] = $v;
    }
}

function post($k, $d = null) {
    return isset($_POST[$k]) ? $_POST[$k] : $d;
}

// ======================================================
// Validación API
// ======================================================
if (!isset($_GET["api"]) || $_GET["api"] == "") {
    echo json_encode([
        "error" => true,
        "msg" => "No se indicó el parámetro api"
    ]);
    exit;
}

$api = $_GET["api"];
$response = ["error" => false];

// ======================================================
// 1️⃣ VALIDAR LOGIN
// ======================================================
if ($api == "validar") {

    $correo = post("correo");
    $pass   = post("pass");

    if (!$correo || !$pass) {
        echo json_encode(["error" => true, "msg" => "correo y pass obligatorios"]);
        exit;
    }

    $result = ADDocente::buscarDoc($correo, $pass);

    echo json_encode(["error" => false, "msg" => $result]);
    exit;
}



// ======================================================
// 2️⃣ GUARDAR DOCENTE
// ======================================================
if ($api == "guardar") {

    $req = ["numero","nombre","app","apm","correo","pass","estado","genero","grado"];
    foreach ($req as $r) if (post($r) === null) {
        echo json_encode(["error"=>true,"msg"=>"Falta: $r"]);
        exit;
    }

    $result = ADDocente::guardar(
        post("numero"), post("nombre"), post("app"), post("apm"),
        post("correo"), post("pass"),
        (int)post("estado"), (int)post("genero"), (int)post("grado")
    );

    echo json_encode([
        "error" => !$result,
        "msg"   => $result ? "OK" : "Error al guardar (correo existente)"
    ]);
    exit;
}



// ======================================================
// 3️⃣ CONSULTAR POR CORREO (Android Friendly)
// ======================================================
if ($api == "consultarPorCorreo") {

    $correo = post("correo");
    if (!$correo) {
        echo json_encode(["error" => true, "msg" => "correo obligatorio"]);
        exit;
    }

    $r = ADDocente::consultarPorCorreo($correo);

    if (!$r || empty($r)) {
        echo json_encode(["error"=>true, "msg"=>"Docente no encontrado"]);
    } else {
        echo json_encode(["error"=>false, "msg"=>$r]);
    }
    exit;
}



// ======================================================
// 4️⃣ ACTUALIZAR DATOS (NUMERO, NOMBRE, APP…)
// ======================================================
if ($api == "actualizar") {

    $req = ["id_docente","genero","pass"];
    foreach ($req as $r) if (post($r) === null) {
        echo json_encode(["error"=>true,"msg"=>"Falta $r"]);
        exit;
    }

    $result = ADDocente::actualizar(
        (int)post("id_docente"),
        (int)post("genero"),
        post("pass")
    );

    echo json_encode(["error"=>!$result, "msg"=>$result ? "Actualizado" : "Error"]);
    exit;
}



// ======================================================
// 5️⃣ SUBIR FOTO (base64) guardada como id_docente.jpg
// ======================================================
if ($api == "foto") {

    if (!isset($_POST["archivo"]) || !isset($_POST["id_docente"])) {
        echo json_encode(["error"=>true, "msg"=>"archivo y id_docente obligatorios"]);
        exit;
    }

    $id = $_POST["id_docente"];
    $data = base64_decode($_POST["archivo"]);
    $carpeta = "archivos/";

    if (!file_exists($carpeta)) mkdir($carpeta,0777,true);

    $ruta = $carpeta . $id . ".jpg";

    if (file_put_contents($ruta, $data)) {
        echo json_encode(["error"=>false, "msg"=>"Foto guardada"]);
    } else {
        echo json_encode(["error"=>true, "msg"=>"Error al guardar"]);
    }
    exit;
}



// ======================================================
// 6️⃣ DESCARGAR FOTO (base64) de id_docente.jpg
// ======================================================
if ($api == "bajarfoto") {

    if (!isset($_POST["id_docente"])) {
        echo json_encode(["error"=>true, "msg"=>"id_docente obligatorio"]);
        exit;
    }

    $id = $_POST["id_docente"];
    $path = "archivos/".$id.".jpg";

    if (!file_exists($path)) {
        echo json_encode(["error"=>true, "msg"=>"Foto no existe"]);
        exit;
    }

    echo json_encode([
        "error" => false,
        "contenido" => base64_encode(file_get_contents($path))
    ]);
    exit;
}



// ======================================================
// ❌ API NO ENCONTRADA
// ======================================================
echo json_encode(["error" => true, "msg" => "Operación no soportada: $api"]);
exit;

