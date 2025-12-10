<?php
require_once "Conexion.php";

class ADInscri {

    // ============================================================
    // 1️⃣ Actualizar estado de la inscripción (1=Solicitud, 2=Aceptada)
    // ============================================================
    public static function actualizarEstado($id_inscripcion, $estado)
    {
        try {
            $pdo = Conexion::conectar();

            $stmt = $pdo->prepare("
                UPDATE inscripcion
                SET estado = :estado
                WHERE id_inscripcion = :id
            ");

            $stmt->bindParam(":estado", $estado, PDO::PARAM_INT);
            $stmt->bindParam(":id", $id_inscripcion, PDO::PARAM_INT);

            return $stmt->execute()
                ? ["ok" => true, "mensaje" => "Estado de la solicitud actualizado"]
                : ["ok" => false, "mensaje" => "Error al actualizar el estado"];

        } catch (PDOException $e) {
            return [
                "ok" => false,
                "mensaje" => "Error interno: " . $e->getMessage()
            ];
        }
    }


    // ============================================================
    // 2️⃣ Listar solicitudes pendientes (estado = 1)
    // ============================================================
    public static function listarPendientes()
    {
        try {
            $pdo = Conexion::conectar();

            $stmt = $pdo->prepare("
                SELECT 
                    i.id_inscripcion,
                    i.id_estudiante,
                    i.id_grupo,
                    i.modalidad,

                    e.nombre AS estudiante_nombre,
                    e.app AS estudiante_app,
                    e.apm AS estudiante_apm,

                    g.clave AS grupo_clave,
                    g.Asignatura AS asignatura

                FROM inscripcion i
                INNER JOIN estudiante e 
                    ON e.id_estudiante = i.id_estudiante
                INNER JOIN grupo g 
                    ON g.id_grupo = i.id_grupo
                WHERE i.estado = 1
            ");

            $stmt->execute();
           return $stmt->fetchAll(PDO::FETCH_ASSOC);


        } catch (PDOException $e) {
            return ["ok" => false, "mensaje" => $e->getMessage()];
        }
    }


    // ============================================================
    // 3️⃣ Eliminar una inscripción
    // ============================================================
    public static function eliminarInscripcion($id_inscripcion)
    {
        try {
            $pdo = Conexion::conectar();

            $stmt = $pdo->prepare("
                DELETE FROM inscripcion 
                WHERE id_inscripcion = :id
            ");

            $stmt->bindParam(":id", $id_inscripcion, PDO::PARAM_INT);

            return $stmt->execute()
                ? ["ok" => true, "mensaje" => "Inscripción eliminada"]
                : ["ok" => false, "mensaje" => "No se pudo eliminar"];

        } catch (PDOException $e) {
            return ["ok" => false, "mensaje" => $e->getMessage()];
        }
    }

}
?>
