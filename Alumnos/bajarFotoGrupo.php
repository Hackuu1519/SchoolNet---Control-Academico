<?php
$carpeta = "../Docentes/archivos/";

$id_grupo = isset($_POST["id_grupo"]) ? $_POST["id_grupo"] : null;

$response = ["error" => false, "msg" => ""];

if ($id_grupo === null) {
    $response["error"] = true;
    $response["msg"] = "Falta id_grupo";
    header("Content-Type: application/json");
    echo json_encode($response);
    exit;
}

$archivo = $carpeta . "grupo_" . $id_grupo . ".jpg";

if (!file_exists($archivo)) {
    $response["error"] = true;
    $response["msg"] = "Sin foto";
    header("Content-Type: application/json");
    echo json_encode($response);
    exit;
}

$imagen = file_get_contents($archivo);
$base64 = base64_encode($imagen);

$response["msg"] = "data:image/jpeg;base64," . $base64;

header("Content-Type: application/json");
echo json_encode($response);
exit;
