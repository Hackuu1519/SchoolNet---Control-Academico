<?php

require_once "Conexion.php";

class ADAssiste {

    /**
     * 📊 1. Gráfica general por periodo (Asistencia, Permiso, Falta, Justificación)
     * Usando tabla PASE: valor = 1 (asistencia), 2 (permiso), 3 (falta), 4 (justificación)
     */
    public function graficaGeneral($periodo)
    {
        try {
            $pdo = Conexion::conectar();

            $sql = "
                SELECT 
                    SUM(CASE WHEN p.valor = 1 THEN 1 ELSE 0 END) AS asistencia,
                    SUM(CASE WHEN p.valor = 2 THEN 1 ELSE 0 END) AS permiso,
                    SUM(CASE WHEN p.valor = 3 THEN 1 ELSE 0 END) AS falta,
                    SUM(CASE WHEN p.valor = 4 THEN 1 ELSE 0 END) AS justificacion
                FROM pase p
                INNER JOIN inscripcion i ON p.id_inscripcion = i.id_inscripcion
                INNER JOIN grupo g ON g.id_grupo = i.id_grupo
                WHERE g.periodo = :periodo
            ";

            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(":periodo", $periodo, PDO::PARAM_STR);
            $stmt->execute();

            $data = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$data) {
                return [
                    "ok" => false,
                    "mensaje" => "No hay datos de asistencia para este periodo"
                ];
            }

            return [
                "ok" => true,
                "data" => [
                    "asistencia"    => (int)($data['asistencia'] ?? 0),
                    "permiso"       => (int)($data['permiso'] ?? 0),
                    "falta"         => (int)($data['falta'] ?? 0),
                    "justificacion" => (int)($data['justificacion'] ?? 0)
                ]
            ];

        } catch (PDOException $e) {
            return [
                "ok" => false,
                "mensaje" => $e->getMessage()
            ];
        }
    }

    /**
     * 📅 2. Listar periodos globales
     */
    public function listarPeriodos()
    {
        try {
            $pdo = Conexion::conectar();

            $sql = "
                SELECT DISTINCT periodo
                FROM grupo
                ORDER BY periodo DESC
            ";

            $stmt = $pdo->prepare($sql);
            $stmt->execute();

            $periodos = $stmt->fetchAll(PDO::FETCH_COLUMN);

            return [
                "ok" => true,
                "periodos" => $periodos
            ];

        } catch (PDOException $e) {
            return [
                "ok" => false,
                "mensaje" => $e->getMessage()
            ];
        }
    }

    /**
     * 🔥 3. Estudiantes en riesgo (faltas >= 6) + fechas de cada falta
     */
    public function estudiantesRiesgo($periodo)
    {
        try {
            $pdo = Conexion::conectar();

            $sql = "
                SELECT 
                    e.id_estudiante,
                    CONCAT(e.nombre, ' ', e.app, ' ', e.apm) AS nombre_completo,
                    e.correo,
                    COUNT(*) AS faltas
                FROM pase p
                INNER JOIN inscripcion i ON p.id_inscripcion = i.id_inscripcion
                INNER JOIN estudiante e ON e.id_estudiante = i.id_estudiante
                INNER JOIN grupo g ON g.id_grupo = i.id_grupo
                WHERE p.valor = 3
                AND g.periodo = :periodo
                GROUP BY e.id_estudiante
                HAVING faltas >= 1
                ORDER BY faltas DESC
            ";

            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(":periodo", $periodo, PDO::PARAM_STR);
            $stmt->execute();

            $alumnos = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (!$alumnos) {
                return [
                    "ok" => true,
                    "riesgo" => []
                ];
            }

            // Fechas de faltas
            $sqlFechas = "
                SELECT p.fecha
                FROM pase p
                INNER JOIN inscripcion i ON p.id_inscripcion = i.id_inscripcion
                INNER JOIN grupo g ON g.id_grupo = i.id_grupo
                WHERE p.valor = 3
                AND g.periodo = :periodo
                AND i.id_estudiante = :id_estudiante
                ORDER BY p.fecha ASC
            ";

            $stmtFechas = $pdo->prepare($sqlFechas);

            foreach ($alumnos as &$a) {
                $stmtFechas->bindParam(":periodo", $periodo, PDO::PARAM_STR);
                $stmtFechas->bindParam(":id_estudiante", $a['id_estudiante'], PDO::PARAM_INT);
                $stmtFechas->execute();

                $fechas = $stmtFechas->fetchAll(PDO::FETCH_COLUMN);

                $a["fechas"] = $fechas ?: [];
            }

            return [
                "ok" => true,
                "riesgo" => $alumnos
            ];

        } catch (PDOException $e) {
            return [
                "ok" => false,
                "msg" => $e->getMessage(),
                "riesgo" => []
            ];
        }
    }

    /**
     * 📊 4. Estadísticas por grupo (Asistencia, Permiso, Falta, Justificación)
     */
    public function graficaGrupo($id_grupo)
    {
        try {
            $pdo = Conexion::conectar();

            $sql = "
                SELECT 
                    SUM(CASE WHEN p.valor = 1 THEN 1 ELSE 0 END) AS asistencia,
                    SUM(CASE WHEN p.valor = 2 THEN 1 ELSE 0 END) AS permiso,
                    SUM(CASE WHEN p.valor = 3 THEN 1 ELSE 0 END) AS falta,
                    SUM(CASE WHEN p.valor = 4 THEN 1 ELSE 0 END) AS justificacion
                FROM pase p
                INNER JOIN inscripcion i ON p.id_inscripcion = i.id_inscripcion
                WHERE i.id_grupo = :grupo
            ";

            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(":grupo", $id_grupo, PDO::PARAM_INT);
            $stmt->execute();

            $data = $stmt->fetch(PDO::FETCH_ASSOC);

            return [
                "ok" => true,
                "data" => [
                    "asistencia"    => (int)($data["asistencia"] ?? 0),
                    "permiso"       => (int)($data["permiso"] ?? 0),
                    "falta"         => (int)($data["falta"] ?? 0),
                    "justificacion" => (int)($data["justificacion"] ?? 0)
                ]
            ];

        } catch (PDOException $e) {
            return [
                "ok" => false,
                "msg" => $e->getMessage()
            ];
        }
    }

    /**
     * 📃 5. Listar grupos
     */
    public function listarGrupos()
    {
        try {
            $pdo = Conexion::conectar();

            $sql = "
                SELECT 
                    id_grupo,
                    CONCAT(clave, ' - ', asignatura) AS nombre
                FROM grupo
                ORDER BY clave ASC
            ";

            $stmt = $pdo->prepare($sql);
            $stmt->execute();

            $grupos = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return [
                "ok" => true,
                "grupos" => $grupos
            ];

        } catch (PDOException $e) {
            return [
                "ok" => false,
                "msg" => $e->getMessage()
            ];
        }
    }
}
?>