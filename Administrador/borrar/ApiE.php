<?php
 /**
     * Autor: Alberto Cruz Gaspar
     * Fecha: 28/09/25
     * Descripción: Gestiona las peticiones a la API para DOCENTE
     */

require_once 'ADDEstudinate.php';
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
			$db = new ADDEstudinate();
			$response['msg'] = $db->buscarDoc($_POST['correo'],$_POST['pass']);
		break;
		
		case 'guardar':
			$db = new ADDEstudinate();
			$result=$db->guardar($_POST['matricula'], $_POST['nombre'],
								 $_POST['app'],$_POST['apm'],
								 $_POST['correo'],$_POST['pass'],
								 $_POST['estado'],$_POST['genero']);
			$response['msg']=$result;
			
		break;

		case 'consultarPorCorreo':
            $db = new ADDEstudinate();
            $result=$db->consultarPorCorreo($_POST['correo']);
            $response['msg']=$result;
            
        break;
        case 'actualizar':
            $db = new ADDEstudinate();
            $result=$db->actualizar($_POST['id_estudiante'],$_POST['matricula'],$_POST['nombre'],
                                 $_POST['app'],$_POST['apm'],
                                 $_POST['correo'],$_POST['pass'],
                                 $_POST['estado'],$_POST['genero']);
            $response['msg']=$result;
            
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