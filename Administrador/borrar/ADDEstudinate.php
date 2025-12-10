<?php
    /**
     * Autor: Mario Pérez Bautista
     * Fecha: 28/09/25
     * Descripción: Gestiona las transacciones de la tabla Docente
     */
    require_once "Conexion.php";
    class ADDEstudinate{
        
        public static function buscarDoc($correo,$pass){
            
            $stmt = Conexion::conectar()->prepare("SELECT
                                                    pass
                                                    FROM estudiante
                                                    WHERE correo=:correo
                                                    ");
            $stmt->bindParam(":correo",$correo, PDO::PARAM_STR);
            $stmt->execute();
           // Si no existe el correo 
           if ($stmt->rowCount() == 0) { return 0; } 
           // Si existe, recuperamos el hash 
           $row = $stmt->fetch(PDO::FETCH_ASSOC); 
           $hashFromDb = $row['pass']; 
           // Verificamos la contraseña 
           if (password_verify($pass, $hashFromDb)) { 
            return 2; // correo existe y contraseña correcta
             } else {
             return 1; // correo existe pero contraseña incorrecta 
            }
        }
        

        public static function guardar($matricula,$nombre,$app,$apm,$correo,$pass,$estado,$genero){

             $stmt = Conexion::conectar()->prepare("SELECT
                                                    *
                                                    FROM estudiante
                                                    WHERE correo=:correo
                                                    ");
            $stmt->bindParam(":correo",$correo, PDO::PARAM_STR);
            $stmt->execute();
            $existe= $stmt->fetchColumn();
            if($existe>0){
                return false;
            }else{
                $result = Conexion::conectar()->prepare("INSERT INTO estudiante (
                                                                            matricula,
                                                                            nombre, 
                                                                            app,
                                                                            apm, 
                                                                            correo,
                                                                            pass,
                                                                            estado,
                                                                            genero)
                                                                            VALUES (
                                                                            :matricula,
                                                                            :nombre, 
                                                                            :app,
                                                                            :apm, 
                                                                            :correo,
                                                                            :pass,
                                                                            :estado,
                                                                            :genero)"
                                                        );
                $hashed=password_hash($pass, PASSWORD_ARGON2ID);
                $result->bindParam(":matricula", $matricula, PDO::PARAM_STR);
                $result->bindParam(":nombre", $nombre, PDO::PARAM_STR);
                $result->bindParam(":app", $app, PDO::PARAM_STR);
                $result->bindParam(":apm", $apm, PDO::PARAM_STR);
                $result->bindParam(":correo", $correo, PDO::PARAM_STR);
                $result->bindParam(":pass",$hashed, PDO::PARAM_STR);
                $result->bindParam(":estado", $estado, PDO::PARAM_INT);
                $result->bindParam(":genero", $genero, PDO::PARAM_INT);           
                return $result->execute();
            }

        }
        public static function existe($correo){
            
            $stmt = Conexion::conectar()->prepare("SELECT
                                                    *
                                                    FROM estudiante
                                                    WHERE correo=:correo
                                                    ");
            $stmt->bindParam(":correo",$correo, PDO::PARAM_STR);
            $stmt->execute();
           return $stmt->fetchColumn();
           
        }

        public static function consultarPorCorreo($correo)
    {
        try {
            $cnx = Conexion::conectar();
            $stmt = $cnx->prepare("SELECT 
                                    id_estudiante,
                                    matricula,
                                    nombre,
                                    app,
                                    apm,
                                    correo,
                                    estado,
                                    genero
                               FROM estudiante
                               WHERE correo = :correo");
            $stmt->bindParam(":correo", $correo, PDO::PARAM_STR);
            $stmt->execute();  
            
            $stmt->bindColumn("id_estudiante", $id_estudiante);
            $stmt->bindColumn("matricula", $matricula);
            $stmt->bindColumn("nombre", $nombre);
            $stmt->bindColumn("app", $app);
            $stmt->bindColumn("apm", $apm);
            $stmt->bindColumn("correo", $correo);
            $stmt->bindColumn("estado", $estado);
            $stmt->bindColumn("genero", $genero);
            $lista = array();
            while ($fila = $stmt->fetch(PDO::FETCH_BOUND)){
                $modelo = array();
                $modelo["id_estudiante"] = $id_estudiante;
                $modelo["matricula"] = $matricula;
                $modelo["nombre"] = $nombre;
                $modelo["app"] = $app;
                $modelo["apm"] = $apm;
                $modelo["correo"] = $correo;
                $modelo["estado"] = $estado;
                $modelo["genero"] = $genero;
                array_push($lista, $modelo);
            }

        return $lista;
        } catch (PDOException $e) {           
            return false;
        }
    }

        public static function actualizar($id, $matricula, $nombre, $app, $apm, $correo, $estado, $genero)
    {
        try {
            $cnx = Conexion::conectar();
            $stmt = $cnx->prepare("UPDATE estudiante 
                               SET matricula = :matricula,
                                   nombre = :nombre,
                                   app = :app,
                                   apm = :apm,
                                   correo = :correo,
                                   estado = :estado,
                                   genero = :genero
                               WHERE id_estudiante = :id");

            $stmt->bindParam(":id", $id, PDO::PARAM_INT);
            $stmt->bindParam(":matricula", $matricula, PDO::PARAM_STR);
            $stmt->bindParam(":nombre", $nombre, PDO::PARAM_STR);
            $stmt->bindParam(":app", $app, PDO::PARAM_STR);
            $stmt->bindParam(":apm", $apm, PDO::PARAM_STR);
            $stmt->bindParam(":correo", $correo, PDO::PARAM_STR);
            $stmt->bindParam(":estado", $estado, PDO::PARAM_INT);
            $stmt->bindParam(":genero", $genero, PDO::PARAM_INT);

            return $stmt->execute();
        } catch (PDOException $e) {
            
            return false;
        }
    }
       
    }
?>