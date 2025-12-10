<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");

if (!isset($_GET["id_docente"])) {
    echo json_encode(["error" => true, "msg" => "Falta id_docente"]);
    exit;
}

$id = $_GET["id_docente"];
$archivo = "../Docentes/ubicacion_docente/" . $id . ".json";

if (!file_exists($archivo)) {
    echo json_encode(["error" => true, "msg" => "Docente sin ubicación"]);
    exit;
}

$data = json_decode(file_get_contents($archivo), true);

echo json_encode([
    "error" => false,
    "ubicacion" => $data
]);
