<?php
require_once "Conexion.php";

class ADAsistencia {

    private $pdo;

    public function __construct() {
        $this->pdo = Conexion::conectar();
    }

    // ================================
    // 🔵 Registrar asistencia por GPS
    // ================================
    public function registrarGPSAlumno($idGrupo, $idAlumno, $latAlumno, $lonAlumno) {

        date_default_timezone_set('America/Mexico_City');
        $fecha = date("Y-m-d");

        // 📌 Validar inscripción activa
        $sql = $this->pdo->prepare("
            SELECT id_inscripcion
            FROM inscripcion
            WHERE id_grupo = ? AND id_estudiante = ? AND estado = 2
            LIMIT 1
        ");
        $sql->execute([$idGrupo, $idAlumno]);
        $ins = $sql->fetch(PDO::FETCH_ASSOC);

        if (!$ins) {
            return ["error" => true, "msg" => "No estás aceptado en este grupo."];
        }

        $idIns = $ins["id_inscripcion"];

        // 📌 Ver si ya tiene asistencia hoy
        $sql = $this->pdo->prepare("
            SELECT id_pase FROM pase
            WHERE id_inscripcion = ? AND fecha = ?
        ");
        $sql->execute([$idIns, $fecha]);

        if ($sql->rowCount() > 0) {
            return ["error" => false, "msg" => "Ya registraste tu asistencia hoy"];
        }

        // 🔵 Leer ubicación del docente (creada por el profe)
        $ruta = "ubicacion_docente/$idGrupo.json";

        if (!file_exists($ruta)) {
            return ["error" => true, "msg" => "El docente no ha iniciado el pase por GPS."];
        }

        $json = json_decode(file_get_contents($ruta), true);

        $latDoc = $json["lat"];
        $lonDoc = $json["lon"];

        // Calcular distancia
        $dist = $this->distanciaGPS($latAlumno, $lonAlumno, $latDoc, $lonDoc);

        if ($dist > 25) { // metros
            return ["error" => true, "msg" => "Debes estar cerca del profesor (". round($dist)." m)"];
        }

        // Registrar asistencia
        $sql = $this->pdo->prepare("
            INSERT INTO pase (id_inscripcion, fecha, valor)
            VALUES (?, ?, 1)
        ");
        $sql->execute([$idIns, $fecha]);

        return ["error" => false, "msg" => "Asistencia registrada correctamente"];
    }


    // ===========================================
    // 🔵 Fórmula Haversine para medir distancia
    // ===========================================
    private function distanciaGPS($lat1, $lon1, $lat2, $lon2) {
        $radioTierra = 6371000; // metros
        $lat1 = deg2rad($lat1);
        $lon1 = deg2rad($lon1);
        $lat2 = deg2rad($lat2);
        $lon2 = deg2rad($lon2);

        $difLat = $lat2 - $lat1;
        $difLon = $lon2 - $lon1;

        $a = sin($difLat/2) * sin($difLat/2) +
             cos($lat1) * cos($lat2) *
             sin($difLon/2) * sin($difLon/2);

        $c = 2 * atan2(sqrt($a), sqrt(1-$a));

        return $radioTierra * $c;
    }
}
