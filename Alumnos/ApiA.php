        <?php
        ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);
        error_reporting(E_ALL);

        require_once 'Conexion.php';
        require_once 'ADEstudiante.php';
        require_once 'ADGrupo.php';
        require_once 'ADInscripcion.php';

        function post($key) {
            return isset($_POST[$key]) ? $_POST[$key] : null;
        }

        // Carpeta para fotos de perfil
        $carpetaDestino = 'archivos/';
        if (!is_dir($carpetaDestino)) {
            mkdir($carpetaDestino, 0777, true);
        }

        function getNombreArchivo($correo) {
            return md5($correo) . '.jpg';
        }

        $response = [
            'error' => false,
            'msg'   => ''
        ];

        if (isset($_GET['api'])) {
            $op = $_GET['api'];

            switch ($op) {

                /* ==========================================================
                🔵 1. LOGIN / REGISTRO / PERFIL ESTUDIANTE
                ========================================================== */

                case 'validar':
                    $correo = post('correo');
                    $pass   = post('pass');

                    $datos = ADEstudiante::buscarEstudiante($correo, $pass);

                    if ($datos === 0) {
                        $response['error'] = true;
                        $response['msg']   = "no_encontrado";
                    }
                    else if ($datos === 1) {
                        $response['error'] = true;
                        $response['msg']   = "pass_incorrecto";
                    }
                    else {
                        // Todo bien → datos completos
                        $response['error'] = false;
                        $response['msg']   = $datos;
                    }
                break;
                

                case 'obtenerPerfil':

                    $correo = post('correo');

                    if ($correo === null) {
                        $response['error'] = true;
                        $response['msg']   = "Falta correo";
                        break;
                    }

                    $pdo = Conexion::conectar();
                    $sql = $pdo->prepare("
                        SELECT id_estudiante, nombre, app, apm, correo, estado, genero 
                        FROM estudiante
                        WHERE correo = ?
                    ");
                    $sql->execute([$correo]);

                    if ($sql->rowCount() == 0) {
                        $response['error'] = true;
                        $response['msg']   = "No encontrado";
                        break;
                    }

                    $data = $sql->fetch(PDO::FETCH_ASSOC);

                    // Traer foto
                    $archivoFoto = "archivos/" . $data["id_estudiante"] . ".jpg";

                    if (file_exists($archivoFoto)) {
                        $fotoBase64 = base64_encode(file_get_contents($archivoFoto));
                        $data["foto"] = "data:image/jpeg;base64," . $fotoBase64;
                    } else {
                        $data["foto"] = null;
                    }

                    $response["error"] = false;
                    $response["msg"]   = $data;
                break;



                case 'guardar':
                    $required = ['matricula', 'nombre', 'app', 'apm', 'correo', 'pass', 'estado', 'genero'];
                    $missing  = [];

                    foreach ($required as $r) {
                        if (post($r) === null) {
                            $missing[] = $r;
                        }
                    }

                    if (!empty($missing)) {
                        http_response_code(400);
                        $response['error'] = true;
                        $response['msg']   = 'Faltan parámetros: ' . implode(', ', $missing);
                        break;
                    }

                    $db = new ADEstudiante();
                    $result = $db->guardar(
                        post('matricula'),
                        post('nombre'),
                        post('app'),
                        post('apm'),
                        post('correo'),
                        post('pass'),
                        (int) post('estado'),
                        (int) post('genero')
                    );

                    if ($result === false) {
                        http_response_code(409);
                        $response['error'] = true;
                        $response['msg']   = 'No se pudo guardar.';
                    } else {
                        $response['msg'] = true;
                    }
                break;

                case 'consultar':
                    $correo = post('correo');
                    if ($correo === null) {
                        http_response_code(400);
                        $response['error'] = true;
                        $response['msg']   = 'Falta correo';
                        break;
                    }

                    $datosEstudiante = ADEstudiante::consultarPorCorreo($correo);
                    if ($datosEstudiante === false) {
                        $response['error'] = true;
                        $response['msg']   = 'No encontrado.';
                    } else {
                        $response['msg'] = $datosEstudiante;
                    }
                break;

                case 'actualizarPerfil':
                    $required = ['correo', 'genero'];
                    $missing  = [];
                    foreach ($required as $r) {
                        if (post($r) === null) {
                            $missing[] = $r;
                        }
                    }

                    if (!empty($missing)) {
                        http_response_code(400);
                        $response['error'] = true;
                        $response['msg']   = 'Faltan: ' . implode(', ', $missing);
                        break;
                    }

                    $result = ADEstudiante::actualizarPerfil(
                        post('correo'),
                        (int) post('genero'),
                        post('pass') // opcional
                    );

                    if ($result) {
                        $response['msg'] = 'Perfil actualizado.';
                    } else {
                        http_response_code(500);
                        $response['error'] = true;
                        $response['msg']   = 'Error BD.';
                    }
                break;

                case 'guardarFoto':
            $id = post('id_estudiante');
            $base64 = post('foto');

            if ($id === null || $base64 === null) {
                http_response_code(400);
                $response['error'] = true;
                $response['msg']   = 'Faltan parámetros.';
                break;
            }

            // Archivo: archivos/ID.jpg
            $rutaArchivo = $carpetaDestino . $id . ".jpg";

            $base64_limpio = preg_replace('#^data:image/\w+;base64,#i', '', $base64);
            $datosImagen   = base64_decode($base64_limpio);

            if ($datosImagen === false) {
                http_response_code(400);
                $response['error'] = true;
                $response['msg']   = 'Base64 no válido.';
                break;
            }

            if (file_put_contents($rutaArchivo, $datosImagen) !== false) {
                chmod($rutaArchivo, 0644);
                $response['msg'] = 'Foto guardada.';
            } else {
                http_response_code(500);
                $response['error'] = true;
                $response['msg']   = 'Error guardando archivo.';
            }
        break;

        case 'docente_por_grupo':
            $id_grupo = post('id_grupo');
            if ($id_grupo == null) {
                $response['error'] = true;
                $response['msg'] = 'Falta id_grupo';
                break;
            }

            $stmt = Conexion::conectar()->prepare("
                SELECT 
                    d.nombre, d.app, d.apm
                FROM grupo g
                JOIN docente d ON d.id_docente = g.id_docente
                WHERE g.id_grupo = :g
            ");
            $stmt->bindParam(":g", $id_grupo);
            $stmt->execute();

            if ($stmt->rowCount() == 0) {
                $response['error'] = true;
                $response['msg'] = "No encontrado";
            } else {
                $response['msg'] = $stmt->fetch(PDO::FETCH_ASSOC);
            }
        break;



                case 'bajarFoto':
            $id = post('id_estudiante');

            if ($id === null) {
                http_response_code(400);
                $response['error'] = true;
                $response['msg']   = 'Falta id_estudiante.';
                break;
            }

            $rutaArchivo = $carpetaDestino . $id . ".jpg";

            if (file_exists($rutaArchivo)) {
                $datosImagen = file_get_contents($rutaArchivo);
                $base64      = base64_encode($datosImagen);
                $response['msg'] = 'data:image/jpeg;base64,' . $base64;
            } else {
                $response['error'] = true;
                $response['msg']   = 'No hay foto.';
            }
        break;


                case 'cambiarPassword':
                    $correo  = post('correo');
                    $newPass = post('pass');

                    if ($correo === null || $newPass === null) {
                        http_response_code(400);
                        $response['error'] = true;
                        $response['msg']   = 'Falta correo o pass.';
                        break;
                    }

                    $result = ADEstudiante::actualizarPassword($correo, $newPass);
                    if ($result) {
                        $response['msg'] = 'Contraseña actualizada.';
                    } else {
                        $response['error'] = true;
                        $response['msg']   = 'Error BD.';
                    }
                break;


                /* ==========================================================
                🔵 2. API PARA GRUPOS Y ASISTENCIA (APP ALUMNO)
                ========================================================== */

                // Lista de todos los grupos activos (grupo.estado = 1)
                case 'consultar_grupos':
                    $response['msg'] = ADGrupo::consultarGruposActivos();
                break;

                // Enviar solicitud de inscripción a un grupo
           case 'inscribir_grupo':

    $id_estudiante = post('id_estudiante');
    $id_grupo      = post('id_grupo');
    $modalidad     = post('modalidad');

    if ($id_estudiante === null || $id_grupo === null || $modalidad === null) {
        $response['error'] = true;
        $response['status'] = 'error';
        $response['msg']   = 'Faltan parámetros para inscribir.';
        break;
    }

    $status = ADInscripcion::inscribir($id_estudiante, $id_grupo, $modalidad);

    switch ($status) {

        case 'solicitud_enviada':
            $response['status'] = 'registrado';
            $response['msg']    = 'Solicitud enviada.';
            break;

        case 'ya_aceptado':
            $response['status'] = 'ya_existe_activo';
            $response['msg']    = 'Ya estás inscrito en este grupo.';
            break;

        case 'ya_solicitado':
        case 'ya_existe_inactivo':
            $response['status'] = 'ya_existe_inactivo';
            $response['msg']    = 'Ya habías enviado solicitud.';
            break;

        default:
            $response['status'] = 'error';
            $response['error']  = true;
            $response['msg']    = 'Error al procesar la inscripción.';
    }
break;



    case 'eliminarFoto':
        $id = post('id_estudiante');

        if ($id === null) {
            http_response_code(400);
            $response['error'] = true;
            $response['msg']   = 'Falta id_estudiante.';
            break;
        }

        $rutaArchivo = $carpetaDestino . $id . ".jpg";

        if (file_exists($rutaArchivo)) {

            if (unlink($rutaArchivo)) {
                $response['error'] = false;
                $response['msg']   = 'Foto eliminada.';
            } else {
                $response['error'] = true;
                $response['msg']   = 'No se pudo eliminar el archivo.';
            }

        } else {
            // ❗ MUY IMPORTANTE:
            // SI NO HAY FOTO → error debe ser TRUE
            $response['error'] = true;
            $response['msg']   = 'No hay foto.';
        }
    break;


    case 'subirFoto':

        $id = post('id_estudiante');
        $base64 = post('foto');

        if ($id === null || $base64 === null) {
            http_response_code(400);
            $response['error'] = true;
            $response['msg']   = 'Faltan parámetros para subir foto.';
            break;
        }

        // Ruta: alumnos/archivos/ID.jpg
        $rutaArchivo = $carpetaDestino . $id . ".jpg";

        // Eliminar encabezado Base64 si lo trae
        $base64 = preg_replace('#^data:image/\w+;base64,#i', '', $base64);

        $datosImagen = base64_decode($base64);

        if ($datosImagen === false) {
            http_response_code(400);
            $response['error'] = true;
            $response['msg']   = 'Base64 inválido.';
            break;
        }

        if (file_put_contents($rutaArchivo, $datosImagen) !== false) {

            chmod($rutaArchivo, 0644);

            $response['error'] = false;
            $response['msg']   = 'Foto subida correctamente.';
        } else {
            http_response_code(500);
            $response['error'] = true;
            $response['msg']   = 'Error guardando imagen.';
        }

    break;


                case 'check_inscripcion':

                $id_estudiante = post('id_estudiante');
                $id_grupo      = post('id_grupo');

                if ($id_estudiante == null || $id_grupo == null) {
                    $response['error'] = true;
                    $response['msg']   = "Faltan parámetros";
                    break;
                }

                $pdo = Conexion::conectar();

                $stmt = $pdo->prepare("
                    SELECT estado 
                    FROM inscripcion 
                    WHERE id_estudiante = :est AND id_grupo = :gpo
                    LIMIT 1
                ");

                $stmt->execute([
                    ":est" => $id_estudiante,
                    ":gpo" => $id_grupo
                ]);

                if ($stmt->rowCount() == 0) {
                    // Nunca se ha inscrito ni enviado solicitud
                    $response['status'] = "libre";
                } 
                else {
                    $row = $stmt->fetch(PDO::FETCH_ASSOC);

                    if ($row["estado"] == 1) {
                        // Ya mandó solicitud
                        $response['status'] = "ya_solicitado";
                    }
                    else if ($row["estado"] == 2) {
                        // Ya está aceptado
                        $response['status'] = "activo";
                    }
                    else {
                        // Estado desconocido
                        $response['status'] = "otro";
                    }
                }
            break;


                // Lista de mis grupos (solo inscripciones aceptadas)
                case 'mis_grupos':
                    $id_estudiante = post('id_estudiante');

                    if ($id_estudiante === null) {
                        http_response_code(400);
                        $response['error'] = true;
                        $response['msg']   = 'Falta id_estudiante';
                        break;
                    }

                    $response['msg'] = ADInscripcion::listarMisGruposActivos($id_estudiante);
                break;

                // Historial de asistencias (detalle por pase)
                case 'mi_asistencia':
                    $id_estudiante = post('id_estudiante');

                    if ($id_estudiante === null) {
                        http_response_code(400);
                        $response['error'] = true;
                        $response['msg']   = 'Falta id_estudiante';
                        break;
                    }

                    $response['msg'] = ADInscripcion::consultarMiAsistencia($id_estudiante);
                break;

                 case 'eliminar_inscripcion':

    $id_inscripcion = post('id_inscripcion');
    $id_grupo       = post('id_grupo');     // necesario para eliminar QR
    $id_estudiante  = post('id_estudiante'); // necesario para QR y ubicación

    if ($id_inscripcion === null || $id_grupo === null || $id_estudiante === null) {
        $response['error'] = true;
        $response['msg']   = "Faltan parámetros";
        break;
    }

    $pdo = Conexion::conectar();

    // 1️⃣ ELIMINAR EL REGISTRO DE INSCRIPCIÓN (BORRA PASES AUTOMÁTICAMENTE)
    $sql = $pdo->prepare("DELETE FROM inscripcion WHERE id_inscripcion = ?");
    $ok  = $sql->execute([$id_inscripcion]);

    if (!$ok) {
        $response['error'] = true;
        $response['msg']   = "Error al eliminar inscripción";
        break;
    }

    // 2️⃣ ELIMINAR QR DEL ALUMNO
    $rutaQR = "qr/alumnos/qr_{$id_grupo}_{$id_estudiante}.png";
    if (file_exists($rutaQR)) {
        unlink($rutaQR);
    }

    // 3️⃣ ELIMINAR UBICACIÓN DEL ALUMNO (si existe)
    $rutaUbic = "ubicacion_alumno/{$id_estudiante}.json";
    if (file_exists($rutaUbic)) {
        unlink($rutaUbic);
    }

    $response['error'] = false;
    $response['msg']   = "Inscripción eliminada con éxito (incluye pases, QR y ubicación).";

break;
   


                // Baja de grupo (cambiar estado de inscripción)
                case 'baja_grupo':
                    $id_inscripcion = post('id_inscripcion');

                    if ($id_inscripcion === null) {
                        http_response_code(400);
                        $response['error'] = true;
                        $response['msg']   = 'Falta id_inscripcion';
                        break;
                    }

                    $response['msg'] = ADInscripcion::darseDeBaja($id_inscripcion);
                break;

                // Resumen de pases por cada inscripción (para cards)
                case 'mis_pases_lista':
                    $id_estudiante = post('id_estudiante');

                    if ($id_estudiante === null) {
                        http_response_code(400);
                        $response['error'] = true;
                        $response['msg']   = 'Falta id_estudiante';
                        break;
                    }

                    $response['msg'] = ADInscripcion::resumenPasesPorEstudiante($id_estudiante);
                break;

                // Resumen global de una sola inscripción
                case 'resumen_por_inscripcion':
                    $id_inscripcion = post('id_inscripcion');

                    if ($id_inscripcion === null) {
                        http_response_code(400);
                        $response['error'] = true;
                        $response['msg']   = 'Falta id_inscripcion';
                        break;
                    }

                    $response['msg'] = ADInscripcion::resumenPorInscripcion($id_inscripcion);
                break;

                // Resumen mensual (para gráficas)
                case 'resumen_mensual_por_inscripcion':
                    $id_inscripcion = post('id_inscripcion');

                    if ($id_inscripcion === null) {
                        http_response_code(400);
                        $response['error'] = true;
                        $response['msg']   = 'Falta id_inscripcion';
                        break;
                    }

                    $response['msg'] = ADInscripcion::resumenMensualPorInscripcion($id_inscripcion);
                break;
                    
                    case 'asistencia_gps':

                if (!isset($_POST['id_grupo'], $_POST['id_estudiante'], $_POST['latitud'], $_POST['longitud'])) {
                    echo json_encode(["error" => true, "msg" => "Faltan parámetros"]);
                    break;
                }

                $id_grupo = $_POST['id_grupo'];
                $id_estudiante = $_POST['id_estudiante'];
                $latAlumno = floatval($_POST['latitud']);
                $lonAlumno = floatval($_POST['longitud']);

                // Ruta al JSON del docente
                $ruta = "ubicacion_docente/$id_grupo.json";

                if (!file_exists($ruta)) {
                    echo json_encode(["error"=>true, "msg"=>"El docente no ha iniciado el pase por GPS"]);
                    break;
                }

                // Leer datos del JSON
                $json = json_decode(file_get_contents($ruta), true);

                $latDoc = floatval($json['lat']);
                $lonDoc = floatval($json['lon']);

                // Calcular distancia
                $dist = distanciaGPS($latAlumno, $lonAlumno, $latDoc, $lonDoc);

                if ($dist > 20) { // 20 metros
                    echo json_encode(["error"=>true, "msg"=>"Debes acercarte al profesor"]);
                    break;
                }

                // 🔵 Registrar asistencia (PASE = 1)
                require_once 'ADAsistencia.php';
                $asis = new ADAsistencia();
                $resp = $asis->registrarManual($id_grupo, $id_estudiante, 1);

                echo json_encode($resp);
                break;


                /* ========================================================== */

                default:
                    http_response_code(404);
                    $response['error'] = true;
                    $response['msg']   = 'API no válida.';
                break;
            }

        } else {
            http_response_code(400);
            $response['error'] = true;
            $response['msg']   = 'No se especificó API.';
        }

        header('Content-Type: application/json');
        echo json_encode($response);
        ?>
