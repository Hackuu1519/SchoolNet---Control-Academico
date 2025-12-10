<?php

/**
 * Autor: Mario Pérez Bautista
 * Fecha: 28/09/25
 * Descripción: Gestiona las transacciones de la tabla Docente
 */

require_once "Conexion.php";

class ADDocente
{

    // ============================================================
    // 1️⃣ LOGIN: Buscar docente por correo y verificar contraseña
    // ============================================================
    public static function buscarDoc($correo, $pass)
    {
        try {
            $stmt = Conexion::conectar()->prepare("
                SELECT pass 
                FROM docente
                WHERE correo = :correo
            ");

            $stmt->bindParam(":correo", $correo);
            $stmt->execute();

            // No existe el correo
            if ($stmt->rowCount() == 0) {
                return 0;
            }

            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $hash = $row["pass"];

            return password_verify($pass, $hash) ? 2 : 1;

        } catch (PDOException $e) {
            return 0;
        }
    }


    // ============================================================
    // 2️⃣ Registrar nuevo docente
    // ============================================================
    public static function guardar($numero, $nombre, $app, $apm, $correo, $pass, $estado, $genero, $grado)
    {
        try {
            // Verificar si ya existe
            $stmt = Conexion::conectar()->prepare("
                SELECT id_docente 
                FROM docente
                WHERE correo = :correo
            ");
            $stmt->bindParam(":correo", $correo);
            $stmt->execute();

            if ($stmt->fetchColumn()) {
                return false;
            }

            // Insertar nuevo docente
            $sql = Conexion::conectar()->prepare("
                INSERT INTO docente (
                    numero, nombre, app, apm, correo, pass, estado, genero, grado
                ) VALUES (
                    :numero, :nombre, :app, :apm, :correo, :pass, :estado, :genero, :grado
                )
            ");

            $hashed = password_hash($pass, PASSWORD_ARGON2ID);

            $sql->bindParam(":numero", $numero);
            $sql->bindParam(":nombre", $nombre);
            $sql->bindParam(":app", $app);
            $sql->bindParam(":apm", $apm);
            $sql->bindParam(":correo", $correo);
            $sql->bindParam(":pass", $hashed);
            $sql->bindParam(":estado", $estado);
            $sql->bindParam(":genero", $genero);
            $sql->bindParam(":grado", $grado);

            return $sql->execute();

        } catch (PDOException $e) {
            return false;
        }
    }


    // ============================================================
    // 3️⃣ Verifica si existe un correo
    // ============================================================
    public static function existe($correo)
    {
        try {
            $stmt = Conexion::conectar()->prepare("
                SELECT id_docente
                FROM docente
                WHERE correo = :correo
            ");
            $stmt->bindParam(":correo", $correo);
            $stmt->execute();

            return $stmt->fetchColumn();

        } catch (PDOException $e) {
            return false;
        }
    }


    // ============================================================
    // 4️⃣ Consultar docente por correo
    // ============================================================
    public static function consultarPorCorreo($correo)
    {
        try {
            $stmt = Conexion::conectar()->prepare("
                SELECT 
                    id_docente,
                    numero,
                    nombre,
                    app,
                    apm,
                    correo,
                    estado,
                    genero,
                    grado
                FROM docente
                WHERE correo = :correo
            ");

            $stmt->bindParam(":correo", $correo);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            return false;
        }
    }


    // ============================================================
    // 5️⃣ Actualizar género o contraseña
    // ============================================================
    public static function actualizar($id_docente, $genero, $pass)
    {
        try {
            $cnx = Conexion::conectar();

            if (trim($pass) === "") {

                // Solo se actualiza género
                $stmt = $cnx->prepare("
                    UPDATE docente
                    SET genero = :genero
                    WHERE id_docente = :id
                ");

                $stmt->bindParam(":genero", $genero);
                $stmt->bindParam(":id", $id_docente);

            } else {

                // Actualiza género y contraseña
                $hashed = password_hash($pass, PASSWORD_ARGON2ID);

                $stmt = $cnx->prepare("
                    UPDATE docente
                    SET genero = :genero, pass = :pass
                    WHERE id_docente = :id
                ");

                $stmt->bindParam(":genero", $genero);
                $stmt->bindParam(":pass", $hashed);
                $stmt->bindParam(":id", $id_docente);
            }

            return $stmt->execute();

        } catch (PDOException $e) {
            return false;
        }
    }

    // 3️⃣ CONSULTAR TODOS + FOTO

    public static function consultarTodosConCarrera() {
    try {

        $pdo = Conexion::conectar();

        $sql = $pdo->prepare("
            SELECT 
                d.id_docente,
                d.numero,
                d.nombre,
                d.app,
                d.apm,
                d.correo,
                d.estado,
                d.genero,
                d.grado,

                (
                    SELECT g.carrera 
                    FROM grupo g
                    WHERE g.id_docente = d.id_docente
                    ORDER BY g.id_grupo ASC
                    LIMIT 1
                ) AS carrera,

                CONCAT('http://192.168.1.236/wsescuela2/Docentes/archivos/docente_', d.id_docente, '.jpg') AS foto

            FROM docente d
            ORDER BY d.app, d.apm, d.nombre
        ");

        $sql->execute();

        $lista = [];
        $result = $sql->fetchAll(PDO::FETCH_ASSOC);

        foreach ($result as $row) {

            $modelo = [];
            $modelo["id_docente"] = (int)$row["id_docente"];
            $modelo["numero"]     = $row["numero"];
            $modelo["nombre"]     = $row["nombre"];
            $modelo["app"]        = $row["app"];
            $modelo["apm"]        = $row["apm"];
            $modelo["correo"]     = $row["correo"];
            $modelo["genero"]     = $row["genero"];
            $modelo["grado"]      = (int)$row["grado"];

            // Convertir estado a texto
            $modelo["estado"]     = ($row["estado"] == 1) ? "Activo" : "De baja";

            // Carrera
            $modelo["carrera"]    = $row["carrera"] ? $row["carrera"] : "Sin asignar";

            // Foto
            $modelo["foto"]       = $row["foto"];

            $lista[] = $modelo;
        }

        return $lista;

    } catch (PDOException $e) {
        return false;
    }
}


    // ============================================================
    // 7️⃣ Actualizar estado + contraseña (opcional)
    // ============================================================
    public static function actualizarEstadoPass($id_docente, $estado, $pass)
    {
        try {
            $pdo = Conexion::conectar();

            if ($pass == "" || $pass == null) {

                $stmt = $pdo->prepare("
                    UPDATE docente
                    SET estado = :estado
                    WHERE id_docente = :id
                ");

                $stmt->bindParam(":estado", $estado);
                $stmt->bindParam(":id", $id_docente);

                return $stmt->execute();
            }

            // Con password
            $hashed = password_hash($pass, PASSWORD_ARGON2ID);

            $stmt = $pdo->prepare("
                UPDATE docente
                SET estado = :estado, pass = :pass
                WHERE id_docente = :id
            ");

            $stmt->bindParam(":estado", $estado);
            $stmt->bindParam(":pass", $hashed);
            $stmt->bindParam(":id", $id_docente);

            return $stmt->execute();

        } catch (PDOException $e) {
            return false;
        }
    }


    // ============================================================
    // 8️⃣ Actualizar contraseña por correo
    // ============================================================
    public static function actualizarPasswordPorCorreo($correo, $pass)
    {
        try {
            $pdo = Conexion::conectar();

            $hash = password_hash($pass, PASSWORD_ARGON2ID);

            $stmt = $pdo->prepare("
                UPDATE docente
                SET pass = :pass
                WHERE correo = :correo
            ");

            $stmt->bindParam(":pass", $hash);
            $stmt->bindParam(":correo", $correo);

            return $stmt->execute();

        } catch (PDOException $e) {
            return false;
        }
    }


    // ============================================================
    // 9️⃣ Consultar por ID
    // ============================================================
    public static function consultarPorId($id_docente)
    {
        try {
            $stmt = Conexion::conectar()->prepare("
                SELECT 
                    id_docente,
                    numero,
                    nombre,
                    app,
                    apm,
                    correo,
                    estado,
                    genero,
                    grado
                FROM docente
                WHERE id_docente = :id
            ");

            $stmt->bindParam(":id", $id_docente);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            return false;
        }
    }


    // ============================================================
    // 🔟 Eliminar docente
    // ============================================================
    public static function eliminar($id_docente)
    {
        try {
            $stmt = Conexion::conectar()->prepare("
                DELETE FROM docente 
                WHERE id_docente = :id
            ");

            $stmt->bindParam(":id", $id_docente);

            return $stmt->execute();

        } catch (PDOException $e) {
            return false;
        }
    }
}

?>
