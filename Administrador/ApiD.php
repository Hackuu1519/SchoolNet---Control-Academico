<?php
 /**
     * Autor: Alberto Cruz Gaspar
     * Fecha: 28/09/25
     * Descripción: Gestiona las peticiones a la API para DOCENTE
     */

require_once 'ADDocente.php';
require_once 'Conexion.php';


//una matriz para mostrar las respuestas de nuestro api
$response = array();

//si se trata de una llamada api
//que significa que un parametro get llamado se establece un la URL
//y con estos parametros estamos concluyendo que es una llamada api

if(isset($_GET['api'])){

	//Aqui iran todos los llamados de nuestra api
	switch ($_GET['api']) {	
		
		case 'validar':
			$db = new ADDocente();
			$response['msg'] = $db->buscarDoc($_POST['correo'],$_POST['pass']);
		break;
		
		case 'guardar':
			$db = new ADDocente();
			$result=$db->guardar($_POST['numero'],$_POST['nombre'],
								 $_POST['app'],$_POST['apm'],
								 $_POST['correo'],$_POST['pass'],
								 $_POST['estado'],$_POST['genero'],
								 $_POST['grado']);
			$response['msg']=$result;
			
		break;

		case 'consultarPorCorreo':
            $db = new ADDocente();
            $result=$db->consultarPorCorreo($_POST['correo']);
            $response['msg']=$result;
            
        break;

        case 'actualizar':
    		$db = new ADDocente();
    		$result = $db->actualizar($_POST['id_docente'], $_POST['genero'], $_POST['pass']);
    		$response['msg'] = $result ? "true" : "false";
    	break;
		
		case 'consultarTodosConCarrera':
    		$db = new ADDocente();
    		$result = $db->consultarTodosConCarrera();
    		$response['msg'] = $result;
		break;

		case "actualizarEstadoPass":
    		$id_docente = $_POST["id_docente"];
   		  	$estado     = $_POST["estado"];
    		$pass       = $_POST["pass"] ?? "";

   	 		echo json_encode( ADDocente::actualizarEstadoPass($id_docente, $estado, $pass) );
		break;

		case 'actualizarPassword':
    		$db = new ADDocente();
    		$correo = $_POST['correo'];
    		$pass   = $_POST['pass'];
    		$response['msg'] = $db->actualizarPasswordPorCorreo($correo, $pass);
		break;

		case 'consultarPorId':
    		$db = new ADDocente();
    		$result = $db->consultarPorId($_POST['id_docente']);
    		$response['msg'] = $result;
    	break;

		case 'eliminar':
            $db = new ADDocente();
            $result = $db->eliminar($_POST['id_docente']);
            // Regresamos "true" o "false" como en actualizar
            $response['msg'] = $result ? "true" : "false";
        break;

	}
	

}else{
	//si no es un api el que se esta invocando
	//empujar los valores apropiados en la estructura json
	$response['error'] = true;
	$response['aviso'] = 'No se llamo a Api';
}

echo json_encode($response);

?>