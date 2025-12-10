<?php

require_once "Conexion.php";

class ADEstudiante {

    public static function buscarEstudiante($correo, $pass) {

    // 1. Buscar estudiante por correo
    $stmt = Conexion::conectar()->prepare("
        SELECT id_estudiante, nombre, app, apm, correo, pass 
        FROM estudiante 
        WHERE correo = :correo
    ");
    $stmt->bindParam(":correo", $correo, PDO::PARAM_STR);
    $stmt->execute();

    // No existe
    if ($stmt->rowCount() == 0) {
        return 0;
    }

    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    // Verificar contraseña
    if (!password_verify($pass, $row["pass"])) {
        return 1;
    }

    // 2. Si todo bien, devolver datos completos
    return [
        "id_estudiante" => $row["id_estudiante"],
        "nombre"        => $row["nombre"],
        "app"           => $row["app"],
        "apm"           => $row["apm"],
        "correo"        => $row["correo"]
    ];
}

        public static function guardar($matricula, $nombre, $app, $apm, $correo, $pass, $estado, $genero) {
        
        $stmt = Conexion::conectar()->prepare("SELECT 1 FROM estudiante 
                                            WHERE correo = :correo OR matricula = :matricula");
        
        $stmt->bindParam(":correo", $correo, PDO::PARAM_STR);
        $stmt->bindParam(":matricula", $matricula, PDO::PARAM_STR);
        $stmt->execute();
        $existe = $stmt->fetchColumn(); 
        
        if ($existe !== false) {
            return false; 
        } else {
           
            $sql = "INSERT INTO estudiante (matricula, nombre, app, apm, correo, pass, estado, genero)
                    VALUES (:matricula, :nombre, :app, :apm, :correo, :pass, :estado, :genero)";
            
            $result = Conexion::conectar()->prepare($sql);
            
            $hashed = password_hash($pass, PASSWORD_DEFAULT);

            $result->bindParam(":matricula", $matricula, PDO::PARAM_STR);
            $result->bindParam(":nombre", $nombre, PDO::PARAM_STR);
            $result->bindParam(":app", $app, PDO::PARAM_STR);
            $result->bindParam(":apm", $apm, PDO::PARAM_STR);
            $result->bindParam(":correo", $correo, PDO::PARAM_STR);
            $result->bindParam(":pass", $hashed, PDO::PARAM_STR);
            $result->bindParam(":estado", $estado, PDO::PARAM_INT);
            $result->bindParam(":genero", $genero, PDO::PARAM_INT);

            return $result->execute();
        }
       
    }

    public static function consultarPorCorreo($correo) {
        try {
            $stmt = Conexion::conectar()->prepare("SELECT id_estudiante, matricula, nombre, app, apm, correo, estado, genero 
                                                   FROM estudiante 
                                                   WHERE correo = :correo");
            
            $stmt->bindParam(":correo", $correo, PDO::PARAM_STR);
            $stmt->execute();

            if ($stmt->rowCount() == 0) {
                return false; 
            }
            return $stmt->fetch(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            return false;
        }
    }

    public static function actualizarPerfil($correo, $genero, $nuevoPass) {
        try {
            $sql = "UPDATE estudiante SET genero = :genero";
            
            if (!empty($nuevoPass)) {
                $sql .= ", pass = :pass";
            }
            $sql .= " WHERE correo = :correo";
            
            $stmt = Conexion::conectar()->prepare($sql);
            
            $stmt->bindParam(":genero", $genero, PDO::PARAM_INT);
            $stmt->bindParam(":correo", $correo, PDO::PARAM_STR);
            if (!empty($nuevoPass)) {
                $hashed = password_hash($nuevoPass, PASSWORD_DEFAULT);
                $stmt->bindParam(":pass", $hashed, PDO::PARAM_STR);
            }
            
            return $stmt->execute();

        } catch (Exception $e) {
            return false;
        }
    }

    public static function existe($correo) {
        $stmt = Conexion::conectar()->prepare("SELECT * FROM estudiante WHERE correo = :correo");
        $stmt->bindParam(":correo", $correo, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetchColumn();
    }
 
    public static function actualizarPassword($correo, $nuevoPass) {
    try {
        $sql = "UPDATE estudiante SET pass = :pass WHERE correo = :correo";
        $stmt = Conexion::conectar()->prepare($sql);

        // Usa PASSWORD_DEFAULT (normalmente bcrypt en tu versión de PHP)
        $hashed = password_hash($nuevoPass, PASSWORD_DEFAULT);

        $stmt->bindParam(":pass", $hashed, PDO::PARAM_STR);
        $stmt->bindParam(":correo", $correo, PDO::PARAM_STR);

        return $stmt->execute();
    } catch (Exception $e) {
        return false;
    }
}

}
?>