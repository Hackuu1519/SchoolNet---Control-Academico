<?php

require_once "Conexion.php";

class ADGrupo {

    // ============================================================
    // 1️⃣ LISTAR GRUPOS DEL DOCENTE
    // ============================================================
    public static function listar($idDocente)
    {
        $pdo = Conexion::conectar();

        $stmt = $pdo->prepare("
            SELECT 
                g.id_grupo,
                g.clave,
                g.periodo,
                g.carrera,
                g.Asignatura AS asignatura,
                g.estado,

                -- ✔ contar solo aceptadas
                (SELECT COUNT(*)
                 FROM inscripcion i
                 WHERE i.id_grupo = g.id_grupo
                 AND i.estado = 2) AS inscripciones,

                -- ✔ horarios reales con inicio/fin INT
                            (SELECT GROUP_CONCAT(
                CONCAT(
                    CASE h.dia
                        WHEN 1 THEN 'LU'
                        WHEN 2 THEN 'MA'
                        WHEN 3 THEN 'MI'
                        WHEN 4 THEN 'JU'
                        WHEN 5 THEN 'VI'
                        WHEN 6 THEN 'SA'
                    END,
                    ' ',
                    SUBSTRING(LPAD(h.inicio, 4, '0'), 1, 2), ':',
                    SUBSTRING(LPAD(h.inicio, 4, '0'), 3, 2),
                    '-',
                    SUBSTRING(LPAD(h.fin, 4, '0'), 1, 2), ':',
                    SUBSTRING(LPAD(h.fin, 4, '0'), 3, 2),
                    ' Aula ',
                    h.aula
                )
                SEPARATOR ' | ')
            FROM horario h
            WHERE h.id_grupo = g.id_grupo) AS horarios


                        FROM grupo g
                        WHERE g.id_docente = :doc
                        ORDER BY g.periodo DESC, g.clave ASC
                    ");

        $stmt->bindParam(":doc", $idDocente, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function listarTodos() {
    $pdo = Conexion::conectar();

    $sql = $pdo->prepare("
        SELECT 
            g.id_grupo,
            g.clave,
            g.periodo,
            g.carrera,
            g.Asignatura AS asignatura,
            g.estado,

            (SELECT COUNT(*)
             FROM inscripcion i
             WHERE i.id_grupo = g.id_grupo
             AND i.estado = 2) AS inscripciones,

            (SELECT GROUP_CONCAT(
                    CONCAT(
                        CASE h.dia
                            WHEN 1 THEN 'LU'
                            WHEN 2 THEN 'MA'
                            WHEN 3 THEN 'MI'
                            WHEN 4 THEN 'JU'
                            WHEN 5 THEN 'VI'
                            WHEN 6 THEN 'SA'
                        END,
                        ' ',
                        SUBSTRING(LPAD(h.inicio, 4, '0'), 1, 2), ':',
                        SUBSTRING(LPAD(h.inicio, 4, '0'), 3, 2),
                        '-',
                        SUBSTRING(LPAD(h.fin, 4, '0'), 1, 2), ':',
                        SUBSTRING(LPAD(h.fin, 4, '0'), 3, 2),
                        ' Aula ',
                        h.aula
                    )
                SEPARATOR ' | ')
             FROM horario h
             WHERE h.id_grupo = g.id_grupo) AS horarios,

            d.nombre AS docente_nombre,
            d.app AS docente_app,
            d.apm AS docente_apm

        FROM grupo g
        INNER JOIN docente d ON d.id_docente = g.id_docente
        ORDER BY g.periodo DESC, g.clave ASC
    ");

    $sql->execute();
    return $sql->fetchAll(PDO::FETCH_ASSOC);
}


    // ============================================================
    // 2️⃣ ACTUALIZAR ESTADO DEL GRUPO
    // ============================================================
    public static function actualizarEstado($id_grupo, $estado)
    {
        try {
            $pdo = Conexion::conectar();

            $stmt = $pdo->prepare("
                UPDATE grupo 
                SET estado = :estado
                WHERE id_grupo = :id
            ");

            $stmt->bindParam(":estado", $estado);
            $stmt->bindParam(":id", $id_grupo);

            return $stmt->execute()
                ? ["ok" => true, "mensaje" => "Estado actualizado"]
                : ["ok" => false, "mensaje" => "No se pudo actualizar"];

        } catch (PDOException $e) {
            return ["ok" => false, "mensaje" => $e->getMessage()];
        }
    }


    // ============================================================
    // 3️⃣ ELIMINAR GRUPO
    // ============================================================
    public static function eliminar($id_grupo)
    {
        try {
            $pdo = Conexion::conectar();

            $stmt = $pdo->prepare("
                DELETE FROM grupo 
                WHERE id_grupo = :id
            ");

            $stmt->bindParam(":id", $id_grupo);

            return $stmt->execute()
                ? ["ok" => true, "mensaje" => "Grupo eliminado"]
                : ["ok" => false, "mensaje" => "No se pudo eliminar"];

        } catch (PDOException $e) {
            return ["ok" => false, "mensaje" => $e->getMessage()];
        }
    }


    // ============================================================
    // 4️⃣ OBTENER GRUPO POR ID
    // ============================================================
    public static function obtenerGrupo($id_grupo)
    {
        try {
            $pdo = Conexion::conectar();

            $stmt = $pdo->prepare("
                SELECT
                    g.id_grupo,
                    g.clave,
                    g.periodo,
                    g.carrera,
                    g.Asignatura AS asignatura,
                    g.estado,

                    (SELECT GROUP_CONCAT(
                            CONCAT(
                                CASE h.dia
                                    WHEN 0 THEN '??'
                                    WHEN 1 THEN 'LU'
                                    WHEN 2 THEN 'MA'
                                    WHEN 3 THEN 'MI'
                                    WHEN 4 THEN 'JU'
                                    WHEN 5 THEN 'VI'
                                    WHEN 6 THEN 'SA'
                                END,
                                ' ',
                                LPAD(h.inicio, 4, '0'),
                                '-',
                                LPAD(h.fin, 4, '0'),
                                ' Aula ',
                                h.aula
                            )
                        SEPARATOR ' | ')
                     FROM horario h
                     WHERE h.id_grupo = g.id_grupo) AS horarios

                FROM grupo g
                WHERE g.id_grupo = :id
                LIMIT 1
            ");

            $stmt->bindParam(":id", $id_grupo);
            $stmt->execute();

            $res = $stmt->fetch(PDO::FETCH_ASSOC);

            return $res
                ? ["ok" => true, "data" => $res]
                : ["ok" => false, "mensaje" => "Grupo no encontrado"];

        } catch (PDOException $e) {
            return ["ok" => false, "mensaje" => $e->getMessage()];
        }
    }


    // ============================================================
    // 5️⃣ ACTUALIZAR DATOS DEL GRUPO
    // ============================================================
    public static function actualizarGrupo($id, $clave, $periodo, $carrera, $asignatura, $estado)
    {
        try {
            $pdo = Conexion::conectar();

            $stmt = $pdo->prepare("
                UPDATE grupo SET
                    clave = :clave,
                    periodo = :periodo,
                    carrera = :carrera,
                    Asignatura = :asignatura,
                    estado = :estado
                WHERE id_grupo = :id
            ");

            $stmt->bindParam(":clave", $clave);
            $stmt->bindParam(":periodo", $periodo);
            $stmt->bindParam(":carrera", $carrera);
            $stmt->bindParam(":asignatura", $asignatura);
            $stmt->bindParam(":estado", $estado);
            $stmt->bindParam(":id", $id);

            return $stmt->execute()
                ? ["ok" => true, "mensaje" => "Grupo actualizado"]
                : ["ok" => false, "mensaje" => "No se pudo actualizar"];

        } catch (PDOException $e) {
            return ["ok" => false, "mensaje" => $e->getMessage()];
        }
    }


    // ============================================================
    // 6️⃣ LISTAR ALUMNOS ACEPTADOS DEL GRUPO
    // ============================================================
    public static function listarAlumnosGrupo($id_grupo)
    {
        try {
            $pdo = Conexion::conectar();

            $stmt = $pdo->prepare("
                SELECT
    e.id_estudiante,
    e.matricula,
    e.nombre,
    e.app,
    e.apm,
    CONCAT(e.nombre, ' ', e.app, ' ', e.apm) AS nombre_completo,
    e.correo,
    e.genero,
    e.estado,
    i.id_inscripcion,
    i.modalidad,
    i.estado AS estado_inscripcion

                FROM inscripcion i
                INNER JOIN estudiante e 
                    ON e.id_estudiante = i.id_estudiante
                WHERE i.id_grupo = :id
                AND i.estado = 2
                ORDER BY e.app ASC, e.nombre ASC
            ");

            $stmt->bindParam(":id", $id_grupo);
            $stmt->execute();

            return [
                "ok" => true,
                "alumnos" => $stmt->fetchAll(PDO::FETCH_ASSOC)
            ];

        } catch (PDOException $e) {
            return ["ok" => false, "mensaje" => $e->getMessage()];
        }
    }

}

?>
