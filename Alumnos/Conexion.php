<?php

/**
 * 
 */
class Conexion
{
	
	public static function conectar(){

		$localhost = "localhost";
		$database = "escuela2";
		$user = "root";
		$password = "";

		$link = new PDO("mysql:host=$localhost;dbname=$database",$user,$password);

		return $link;
	}
}

?>