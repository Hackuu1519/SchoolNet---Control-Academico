<?php
/**
 * Autor: Alberto Cruz Gaspar
 * API para consultas de ASISTENCIA GENERAL + ESTUDIANTES EN RIESGO
 */

require_once "ADAssiste.php";
require_once "Conexion.php";

header("Content-Type: application/json");

$response = [];

if (isset($_GET['api'])) {

    switch ($_GET['api']) {

        // 📊 1. Gráfica general por periodo
        case 'general':

            if (!isset($_POST['periodo'])) {
                $response['error'] = true;
                $response['msg'] = "Falta el parámetro 'periodo'";
                break;
            }

            $db = new ADAssiste();
            $result = $db->graficaGeneral($_POST['periodo']);
            $response['msg'] = $result;
        break;

        // 📅 2. Listar periodos globales
        case 'listarPeriodos':

            $db = new ADAssiste();
            $result = $db->listarPeriodos();
            $response['msg'] = $result;
        break;

        // 🔥 3. Estudiantes en riesgo (faltas >= 6 + fechas)
        case 'riesgo':

            if (!isset($_POST['periodo'])) {
                $response['error'] = true;
                $response['msg'] = "Falta el parámetro 'periodo'";
                break;
            }

            $db = new ADAssiste();
            $result = $db->estudiantesRiesgo($_POST['periodo']);

            // Por seguridad, siempre mandar el campo 'riesgo'
            if (!isset($result['riesgo'])) {
                $result['riesgo'] = [];
            }

            $response['msg'] = $result;
        break;

        case 'grupo':
            if (!isset($_POST['id_grupo'])) {
            $response['error'] = true;
            $response['msg'] = "Falta el parámetro id_grupo";
        break;
        }
            $db = new ADAssiste();
            $result = $db->graficaGrupo($_POST['id_grupo']);
            $response['msg'] = $result;
        break;

        // 📌 Listar grupos
case 'listarGrupos':

    $db = new ADAssiste();
    $result = $db->listarGrupos();
    $response['msg'] = $result;
break;


// 📌 Estadísticas por grupo
case 'generalGrupo':

    if (!isset($_POST['id_grupo'])) {
        $response['error'] = true;
        $response['msg'] = "Falta el parámetro id_grupo";
        break;
    }

    $db = new ADAssiste();
    $result = $db->graficaGrupo($_POST['id_grupo']);
    $response['msg'] = $result;
break;



        // ❌ Petición inválida
        default:
            $response['error'] = true;
            $response['msg'] = "Acción no válida en ApiAs.php";
        break;
    }

} else {

    $response['error'] = true;
    $response['msg'] = "No se llamó a Api";
}

echo json_encode($response);
?>