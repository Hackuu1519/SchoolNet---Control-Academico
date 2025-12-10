<?php
require_once "Conexion.php";

class ADGrupo {

    /* ============================================================
       🔵 1. LISTAR GRUPOS DEL DOCENTE
       ✔ estado = 1 → Activo, 2 → De baja
       ✔ inscripciones = SOLO aceptadas (estado = 2)
    ============================================================ */
    public static function listar($idDocente) {

        $stmt = Conexion::conectar()->prepare("
            SELECT 
                g.id_grupo,
                g.clave,
                g.periodo,
                g.carrera,
                g.Asignatura,
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
                                LPAD(h.inicio,4,'0'),'-',
                                LPAD(h.fin,4,'0'),
                                ' ',
                                h.aula
                            ) SEPARATOR ' | ')
                 FROM horario h
                 WHERE h.id_grupo = g.id_grupo
                ) AS horarios

            FROM grupo g
            WHERE g.id_docente = :id
            ORDER BY g.periodo DESC, g.clave ASC
        ");

        $stmt->bindParam(":id", $idDocente, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /* ============================================================
       🔵 2. CONSULTAR GRUPOS ACTIVOS (APP ALUMNO)
       ✔ g.estado = 1 → Activo
       ✔ inscripciones = SOLO aceptadas
    ============================================================ */
    public static function consultarGruposActivos() {

        try {
            $stmt = Conexion::conectar()->prepare("
                SELECT 
                    g.id_grupo,
                    g.clave,
                    g.periodo,
                    g.carrera,
                    g.Asignatura,

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
                                    LPAD(h.inicio,4,'0'),'-',
                                    LPAD(h.fin,4,'0'),
                                    ' ',
                                    h.aula
                                ) SEPARATOR ' | ')
                     FROM horario h
                     WHERE h.id_grupo = g.id_grupo
                    ) AS horarios

                FROM grupo g
                WHERE g.estado = 1       -- ✔ SOLO GRUPOS ACTIVOS
                ORDER BY g.Asignatura ASC, g.clave ASC
            ");

            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            return [];
        }
    }

    /* ============================================================
       🔵 3. LISTAR ALUMNOS DEL GRUPO
       ✔ Solo inscripciones aceptadas (2)
    ============================================================ */
    public static function listarAlumnosGrupo($id_grupo)
    {
        try {
            $pdo = Conexion::conectar();

            $sql = "
                SELECT
                    e.id_estudiante,
                    e.matricula,
                    e.nombre,
                    e.app,
                    e.apm,
                    e.correo,
                    e.estado,
                    e.genero,
                    i.id_inscripcion,
                    i.modalidad,
                    i.estado AS estado_inscripcion
                FROM inscripcion i
                INNER JOIN estudiante e ON e.id_estudiante = i.id_estudiante
                WHERE i.id_grupo = :grupo
                AND i.estado = 2     -- ✔ SOLO ACEPTADAS
            ";

            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(":grupo", $id_grupo, PDO::PARAM_INT);
            $stmt->execute();

            return ["ok" => true, "alumnos" => $stmt->fetchAll(PDO::FETCH_ASSOC)];

        } catch (PDOException $e) {
            return ["ok" => false, "mensaje" => $e->getMessage()];
        }
    }

}
