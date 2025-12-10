<?php
/**
 * Autor: Alberto Cruz Gaspar
 * Fecha: 14/11/25
 * Descripción: Gestiona las transacciones de la tabla Estudiante
 */

require_once "Conexion.php";

class ADEstudiante {

    // ============================================================
    // 1️⃣ Actualizar estado y contraseña (opcional)
    // ============================================================
    public static function actualizarEstadoPass($id_estudiante, $estado, $pass)
    {
        try {
            $pdo = Conexion::conectar();

            // Sin contraseña → solo actualiza estado
            if ($pass == "" || $pass === null) {

                $sql = "UPDATE estudiante 
                        SET estado = :estado
                        WHERE id_estudiante = :id";

                $stmt = $pdo->prepare($sql);
                $stmt->bindParam(":estado", $estado, PDO::PARAM_INT);
                $stmt->bindParam(":id", $id_estudiante, PDO::PARAM_INT);

                return $stmt->execute()
                    ? ["ok" => true, "mensaje" => "Estado actualizado correctamente"]
                    : ["ok" => false, "mensaje" => "Error al actualizar estado"];
            }

            // Sí enviaron contraseña → actualizar estado + pass
            $passHash = password_hash($pass, PASSWORD_ARGON2ID);

            $sql = "UPDATE estudiante 
                    SET estado = :estado, pass = :pass
                    WHERE id_estudiante = :id";

            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(":estado", $estado, PDO::PARAM_INT);
            $stmt->bindParam(":pass", $passHash, PDO::PARAM_STR);
            $stmt->bindParam(":id", $id_estudiante, PDO::PARAM_INT);

            return $stmt->execute()
                ? ["ok" => true, "mensaje" => "Estado y contraseña actualizados correctamente"]
                : ["ok" => false, "mensaje" => "Error al actualizar datos"];

        } catch (PDOException $e) {
            return ["ok" => false, "mensaje" => $e->getMessage()];
        }
    }



    // ============================================================
    // 2️⃣ Editar alumno dentro de un grupo (estado + pass opcional)
    // ============================================================
    public function editarAlumnoGrupo($id, $estado, $pass)
    {
        try {
            $pdo = Conexion::conectar();

            // Sin pass → solo estado
            if ($pass == "" || $pass === null) {

                $sql = "UPDATE estudiante
                        SET estado = :estado
                        WHERE id_estudiante = :id";

                $stmt = $pdo->prepare($sql);
                $stmt->bindParam(":estado", $estado, PDO::PARAM_INT);
                $stmt->bindParam(":id", $id, PDO::PARAM_INT);

                return $stmt->execute()
                    ? ["ok" => true, "msg" => "Estado actualizado"]
                    : ["ok" => false, "msg" => "Error al actualizar"];
            }

            // Con password
            $passHash = password_hash($pass, PASSWORD_ARGON2ID);

            $sql = "UPDATE estudiante
                    SET estado = :estado, pass = :pass
                    WHERE id_estudiante = :id";

            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(":estado", $estado, PDO::PARAM_INT);
            $stmt->bindParam(":pass", $passHash, PDO::PARAM_STR);
            $stmt->bindParam(":id", $id, PDO::PARAM_INT);

            return $stmt->execute()
                ? ["ok" => true, "msg" => "Actualizado correctamente"]
                : ["ok" => false, "msg" => "Error al actualizar"];

        } catch (PDOException $e) {
            return ["ok" => false, "msg" => $e->getMessage()];
        }
    }



    // ============================================================
    // 3️⃣ Eliminar inscripción de un grupo
    // ============================================================
    public function eliminarInscripcion($id_estudiante, $id_grupo)
    {
        try {
            $pdo = Conexion::conectar();

            $sql = "DELETE FROM inscripcion 
                    WHERE id_estudiante = :id_est 
                    AND id_grupo = :id_grupo";

            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(":id_est", $id_estudiante, PDO::PARAM_INT);
            $stmt->bindParam(":id_grupo", $id_grupo, PDO::PARAM_INT);

            return $stmt->execute()
                ? ["ok" => true]
                : ["ok" => false, "msg" => "No se pudo eliminar la inscripción"];

        } catch (PDOException $e) {
            return ["ok" => false, "msg" => $e->getMessage()];
        }
    }

}

?>
