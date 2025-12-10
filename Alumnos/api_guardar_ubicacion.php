<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");

if (!isset($_POST["id_estudiante"]) || !isset($_POST["latitud"]) || !isset($_POST["longitud"])) {
    echo json_encode(["error" => true, "msg" => "Faltan datos"]);
    exit;
}

$id = $_POST["id_estudiante"];
$lat = $_POST["latitud"];
$lon = $_POST["longitud"];

$carpeta = "ubicacion_estudiante/";

if (!file_exists($carpeta)) {
    mkdir($carpeta, 0777, true);
}

$data = [
    "id" => $id,
    "latitud" => floatval($lat),
    "longitud" => floatval($lon),
    "fecha" => date("Y-m-d H:i:s")
];

file_put_contents($carpeta . $id . ".json", json_encode($data));

echo json_encode(["error" => false, "msg" => "Ubicación guardada"]);
