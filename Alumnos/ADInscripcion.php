<?php
require_once "Conexion.php";

class ADInscripcion {

    /* ============================================================
       1. INSCRIBIR ALUMNO
       - estado = 1 → Solicitud enviada
       - estado = 2 → Aceptado por docente
    ============================================================ */
public static function inscribir($id_estudiante, $id_grupo, $modalidad) {

    try {
        $cnx = Conexion::conectar();

        // Revisar si ya existe inscripción previa
        $stmt = $cnx->prepare("
            SELECT estado 
            FROM inscripcion 
            WHERE id_estudiante = :id_est AND id_grupo = :id_gpo
        ");
        $stmt->execute([
            ":id_est" => $id_estudiante,
            ":id_gpo" => $id_grupo
        ]);

        if ($stmt->rowCount() > 0) {

            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($row["estado"] == 1)
                return "ya_aceptado";        // alumno ya dentro

            if ($row["estado"] == 0)
                return "ya_solicitado";      // solicitud enviada antes

            if ($row["estado"] == 0)
                return "ya_existe_inactivo"; // no eliminar duplicados
        }

        // Registrar NUEVA solicitud
        $stmt2 = $cnx->prepare("
            INSERT INTO inscripcion (id_estudiante, id_grupo, modalidad, estado)
            VALUES (:id_est, :id_gpo, :mod, 1)
        ");
        $stmt2->execute([
            ":id_est" => $id_estudiante,
            ":id_gpo" => $id_grupo,
            ":mod"    => $modalidad
        ]);

        return "solicitud_enviada";

    } catch (PDOException $e) {
        return "error";
    }
}

    /* ============================================================
       🔔 CREAR NOTIFICACIÓN PARA EL DOCENTE (SIN BD)
       - Crea / actualiza archivo JSON:
         Docentes/notificaciones_docente/{id_docente}.json
    ============================================================ */
 public static function crearNotificacionDocenteJSON($id_estudiante, $id_grupo) {

    try {
        $pdo = Conexion::conectar();

        // Obtener el docente del grupo
        $g = $pdo->prepare("SELECT id_docente, clave, carrera, Asignatura 
                           FROM grupo WHERE id_grupo = :g");
        $g->execute([":g" => $id_grupo]);
        $grupo = $g->fetch(PDO::FETCH_ASSOC);

        if (!$grupo) return;

        $idDocente   = $grupo["id_docente"];
        $claveGrupo  = $grupo["clave"];
        $materia     = $grupo["Asignatura"];
        $carrera     = $grupo["carrera"];

        // Obtener nombre COMPLETO del alumno
        $a = $pdo->prepare("
            SELECT CONCAT(nombre, ' ', app, ' ', apm) AS nombre
            FROM estudiante 
            WHERE id_estudiante = :id
        ");
        $a->execute([":id" => $id_estudiante]);
        $nombreAlumno = $a->fetchColumn();

        // Carpeta donde se guardan los JSON
        $carpeta = "../Docentes/notificaciones_docente/";
        if (!is_dir($carpeta)) {
            mkdir($carpeta, 0777, true);
        }

        $ruta = $carpeta . $idDocente . ".json";

        // Crear array de notificación
        $notificacion = [
            "titulo" => "Nueva solicitud",
            "mensaje" => $nombreAlumno . " quiere unirse al grupo $claveGrupo ($materia)",
            "id_grupo" => $id_grupo,
            "fecha" => date("Y-m-d H:i:s")
        ];

        // Guardar como ARRAY (para soportar más notificaciones a futuro)
        file_put_contents($ruta, json_encode([$notificacion], JSON_UNESCAPED_UNICODE));

    } catch(Exception $e) {}
}


    /* ============================================================
       2. LISTAR MIS GRUPOS (solo aceptados)
    ============================================================ */
    public static function listarMisGruposActivos($id_estudiante) {

        $pdo = Conexion::conectar();

        $sql = "
            SELECT 
                i.id_inscripcion,
                g.id_grupo, 
                g.clave,
                g.Asignatura,
                g.periodo
            FROM inscripcion i
            INNER JOIN grupo g ON g.id_grupo = i.id_grupo
            WHERE i.id_estudiante = :id
              AND i.estado = 2
            ORDER BY g.Asignatura ASC, g.clave ASC
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':id', $id_estudiante, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    /* ============================================================
       3. BAJA DEL GRUPO
    ============================================================ */
    public static function darseDeBaja($id_inscripcion) {

        try {
            $stmt = Conexion::conectar()->prepare("
                UPDATE inscripcion 
                SET estado = 0
                WHERE id_inscripcion = :id
            ");

            return $stmt->execute([":id" => $id_inscripcion]);

        } catch (PDOException $e) {
            return false;
        }
    }


    /* ============================================================
       4. HISTORIAL COMPLETO DE ASISTENCIAS
    ============================================================ */
    public static function consultarMiAsistencia($id_estudiante) {

        try {
            $stmt = Conexion::conectar()->prepare("
                SELECT 
                    g.Asignatura,
                    p.fecha,
                    CASE p.valor
                        WHEN 1 THEN 'Asistencia'
                        WHEN 2 THEN 'Permiso'
                        WHEN 3 THEN 'Falta'
                        WHEN 4 THEN 'Justificada'
                        ELSE 'Desconocido'
                    END AS estatus_asistencia
                FROM pase p
                JOIN inscripcion i ON p.id_inscripcion = i.id_inscripcion
                JOIN grupo g ON i.id_grupo = g.id_grupo
                WHERE i.id_estudiante = :id_estudiante
                ORDER BY g.Asignatura ASC, p.fecha DESC
            ");

            $stmt->execute([":id_estudiante" => $id_estudiante]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            return [];
        }
    }

    /* ============================================================
       5. RESUMEN GENERAL
    ============================================================ */
    public static function resumenPasesPorEstudiante($id_estudiante) {
        try {
            $stmt = Conexion::conectar()->prepare("
                SELECT 
                    g.clave,
                    g.Asignatura,
                    g.periodo,
                    i.id_inscripcion,
                    SUM(CASE WHEN p.valor = 1 THEN 1 ELSE 0 END) AS asistencias,
                    SUM(CASE WHEN p.valor = 2 THEN 1 ELSE 0 END) AS permisos,
                    SUM(CASE WHEN p.valor = 3 THEN 1 ELSE 0 END) AS faltas,
                    SUM(CASE WHEN p.valor = 4 THEN 1 ELSE 0 END) AS justificantes
                FROM inscripcion i
                JOIN grupo g ON i.id_grupo = g.id_grupo
                LEFT JOIN pase p ON i.id_inscripcion = p.id_inscripcion
                WHERE i.id_estudiante = :id_est
                  AND i.estado = 2
                  AND g.estado = 1
                GROUP BY g.clave, g.Asignatura, g.periodo, i.id_inscripcion
                ORDER BY g.periodo DESC, g.Asignatura ASC
            ");

            $stmt->execute([":id_est" => $id_estudiante]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            return [];
        }
    }

    /* ============================================================
       6. RESUMEN POR INSCRIPCIÓN
    ============================================================ */
    public static function resumenPorInscripcion($id_inscripcion) {
        try {
            $stmt = Conexion::conectar()->prepare("
                SELECT 
                    SUM(CASE WHEN p.valor = 1 THEN 1 ELSE 0 END) AS asistencias,
                    SUM(CASE WHEN p.valor = 2 THEN 1 ELSE 0 END) AS permisos,
                    SUM(CASE WHEN p.valor = 3 THEN 1 ELSE 0 END) AS faltas,
                    SUM(CASE WHEN p.valor = 4 THEN 1 ELSE 0 END) AS justificantes
                FROM pase p
                WHERE p.id_inscripcion = :id
            ");

            $stmt->execute([":id" => $id_inscripcion]);
            return $stmt->fetch(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            return [];
        }
    }

    /* ============================================================
       7. RESUMEN MENSUAL
    ============================================================ */
    public static function resumenMensualPorInscripcion($id_inscripcion) {

        try {
            $stmt = Conexion::conectar()->prepare("
                SELECT 
                    CASE MONTH(p.fecha)
                        WHEN 1 THEN 'Enero'
                        WHEN 2 THEN 'Febrero'
                        WHEN 3 THEN 'Marzo'
                        WHEN 4 THEN 'Abril'
                        WHEN 5 THEN 'Mayo'
                        WHEN 6 THEN 'Junio'
                            WHEN 7 THEN 'Julio'
                        WHEN 8 THEN 'Agosto'
                        WHEN 9 THEN 'Septiembre'
                        WHEN 10 THEN 'Octubre'
                        WHEN 11 THEN 'Noviembre'
                        WHEN 12 THEN 'Diciembre'
                    END AS mes,
                    SUM(CASE WHEN p.valor = 1 THEN 1 ELSE 0 END) AS asistencias,
                    SUM(CASE WHEN p.valor = 2 THEN 1 ELSE 0 END) AS permisos,
                    SUM(CASE WHEN p.valor = 3 THEN 1 ELSE 0 END) AS faltas,
                    SUM(CASE WHEN p.valor = 4 THEN 1 ELSE 0 END) AS justificantes
                FROM pase p
                WHERE p.id_inscripcion = :id
                GROUP BY MONTH(p.fecha)
                ORDER BY MONTH(p.fecha)
            ");

            $stmt->execute([":id" => $id_inscripcion]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            return [];
        }
    }
}
?>
