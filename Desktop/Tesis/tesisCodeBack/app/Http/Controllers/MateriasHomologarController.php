<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\File;

class MateriasHomologarController extends Controller
{
    public function uploadPdfMateriaHomologar(Request $request)
    {
        // RECOGER LOS DATOS POR POST
        $json = $request->input("json", null);

        $params_array = json_decode($json, true);

        $pdf = $request->file("file0");

        // VALIDAR PDF
        $validarPdf = Validator::make($request->all("file0"), [
            "file0" => "required|file|mimes:pdf|max:2048",
        ]);

        if ($validarPdf->fails()) {
            return response()->json(
                [
                    "code" => 400,
                    "status" => "error",
                    "message" => "Error al subir el archivo PDF",
                    "errores" => $validarPdf->errors(),
                ],
                400
            );
        }

        if (!empty($params_array)) {
            // VALIDAR DATOS
            $validarDatos = Validator::make($params_array, [
                "solicitudes_id" => "required",
                "materia_id" => "required",
            ]);

            if ($validarDatos->fails()) {
                return response()->json(
                    [
                        "code" => 400,
                        "status" => "error",
                        "message" => "Error al enviar los datos",
                        "errores" => $validarDatos->errors(),
                    ],
                    400
                );
            }
        } else {
            return response()->json(
                [
                    "code" => 400,
                    "status" => "error",
                    "message" => "Error al enviar los datos",
                ],
                400
            );
        }

        try {
            DB::beginTransaction();

            $pdf_name = time() . ".pdf";

            // OBTENER EL NOMBRE DEL PDF ANTERIOR
            $pdf_anterior = DB::table("materias_homologar")
                ->where("solicitudes_id", $params_array["solicitudes_id"])
                ->where("materia_id", $params_array["materia_id"])
                ->value("pdf");

            // ACTUALIZAR LOS REGISTROS DE LA BD
            DB::table("materias_homologar")
                ->where("solicitudes_id", $params_array["solicitudes_id"])
                ->where("materia_id", $params_array["materia_id"])
                ->update([
                    "pdf" => $pdf_name,
                ]);

            // GUARDANDO EL PDF EN EL SERVIDOR FTP
            Storage::disk("ftpHomologacion")->put($pdf_name, File::get($pdf));

            if (Storage::disk("ftpHomologacion")->exists($pdf_anterior)) {
                Storage::disk("ftpHomologacion")->delete($pdf_anterior);
            }

            DB::commit();

            return response()->json(
                [
                    "code" => 200,
                    "status" => "success",
                ],
                200
            );
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(
                [
                    "code" => 400,
                    "status" => "error",
                    "message" => "Error al guardar los datos.",
                    "errores" => $e,
                ],
                400
            );
        }
    }

    public function getPdfMateriaHomologar($filename)
    {
        // COMPROBAR SI EXISTE EL FICHERO
        $isset = Storage::disk("ftpHomologacion")->exists($filename);

        if ($isset) {
            // CONSEGUIR EL PDF
            $file = Storage::disk("ftpHomologacion")->get($filename);

            return response($file)
                ->header("Content-Type", "application/pdf")
                ->header(
                    "Content-Disposition",
                    "inline; filename='" . $filename . "'"
                );
        } else {
            $data = [
                "code" => 404,
                "status" => "error",
                "message" => "El PDF no existe.",
            ];
        }

        // MOSTRAR ERRORES
        return response()->json($data, $data["code"]);
    }

    public function obtenerDocentes($idDepartamento)
    {
        $docentes = DB::select("SELECT dd.idpersonal, p.nombres || ' ' || p.apellido1 || ' ' || p.apellido2 AS nombres
		FROM esq_distributivos.departamento d
        JOIN esq_inscripciones.facultad f ON d.idfacultad = f.idfacultad
        AND NOT f.nombre = 'POSGRADO'
        AND NOT f.nombre = 'CENTRO DE PROMOCIÓN Y APOYO AL INGRESO'
        AND NOT f.nombre = 'INSTITUTO DE INVESTIGACIÓN'
        AND d.habilitado = 'S'
        JOIN esq_distributivos.departamento_docente dd ON dd.iddepartamento = d.iddepartamento
        JOIN esq_datos_personales.personal p ON dd.idpersonal = p.idpersonal
        AND NOT nombres LIKE 'PROF T%'
        AND NOT nombres LIKE 'PROF M%'
		WHERE dd.iddepartamento = $idDepartamento
        ORDER BY p.nombres");

        $data = [
            "code" => 200,
            "status" => "success",
            "docentes" => $docentes,
        ];

        return response()->json($data, $data["code"]);
    }

    public function actualizarDocenteAnalisis(Request $request)
    {
        $json = $request->input("json", null);

        $params_array = json_decode($json, true);

        if (!empty($params_array)) {
            //VALIDAR LOS DATOS
            $validarDatos = Validator::make($params_array, [
                "personal_id" => "required",
                "solicitudes_id" => "required",
                "materia_id" => "required",
            ]);

            if ($validarDatos->fails()) {
                return response()->json(
                    [
                        "code" => 400,
                        "status" => "error",
                        "message" => "Error al enviar los datos.",
                        "errores" => $validarDatos->errors(),
                    ],
                    400
                );
            } else {
                $update = DB::table("materias_homologar")
                    ->where("solicitudes_id", $params_array["solicitudes_id"])
                    ->where("materia_id", $params_array["materia_id"])
                    ->update([
                        "personal_id" => $params_array["personal_id"],
                        "estado" => "DOCENTE ASIGNADO",
                    ]);

                if ($update) {
                    return response()->json([
                        "code" => 200,
                        "status" => "success",
                        "message" => "Registro actualizado con éxito.",
                    ]);
                } else {
                    return response()->json([
                        "code" => 400,
                        "status" => "error",
                        "message" => "Registro no actualizado.",
                    ]);
                }
            }
        }
    }

    public function obtenerDepartamento($idPersonal, $flag = false)
    {
        if ($flag) {
            $id_departamento = DB::select("SELECT u.departamento_id
            FROM esq_homo.usuarios u
            WHERE u.personal_id = $idPersonal AND u.cargos_id = 2
            ");

            return $id_departamento[0]->departamento_id;
        } else {
            $id_departamento = DB::select("SELECT pe.idpersonal, pe.nombres, CONCAT(pe.apellido1, ' ', pe.apellido2) AS apellidos,
            pe.correo_personal_institucional, de.nombre,
            CAST(split_part(tpr.iddepartamento, '|', 1) AS INTEGER) AS iddepartamento_numero 
            FROM esq_datos_personales.personal pe
            JOIN esq_roles.tbl_personal_rol tpr ON pe.idpersonal = tpr.id_personal
            JOIN esq_roles.tbl_rol tr ON tr.id_rol = tpr.id_rol
            JOIN esq_distributivos.departamento de ON de.iddepartamento = CAST(split_part(tpr.iddepartamento, '|', 1) AS INTEGER)
            JOIN esq_inscripciones.facultad fa ON fa.idfacultad = de.idfacultad
            AND NOT fa.nombre LIKE 'EDIFICIO%'
            AND NOT fa.nombre LIKE 'UNI%'
            AND NOT fa.nombre LIKE 'CENTRO%'
            AND NOT fa.nombre = 'INSTITUTO DE LENGUAS'
            AND NOT fa.nombre = 'INSTITUTO DE INVESTIGACIÓN'
            AND NOT fa.nombre = 'HONORABLE CONSEJO UNIVERSITARIO'
            AND NOT fa.nombre = 'BIBLIOTECA CENTRAL'                                                                                  
            AND NOT fa.nombre = 'RECTORADO'
            AND NOT fa.nombre = 'POSGRADO'
            AND NOT fa.nombre = 'GENERAL'
            AND NOT fa.nombre = 'DEPARTAMENTO DE FINANCIERO'
            AND NOT fa.nombre = 'FEDERACIÓN DE ESTUDIANTES UNIVERSITARIOS - FEUE'
            WHERE tr.id_rol = 19 AND tpr.estado = 'S' AND de.habilitado = 'S'
            AND tpr.id_conexion = 1 AND pe.idpersonal = $idPersonal");

            return $id_departamento[0]->iddepartamento_numero;
        }
    }

    public function closeActualizarDocenteAnalisis(Request $request)
    {
        $json = $request->input("json", null);

        $params_array = json_decode($json, true);

        if (!empty($params_array)) {
            //VALIDAR LOS DATOS
            $validar_datos = Validator::make($params_array, [
                "solicitudes_id" => "required",
                "personal_id" => "required",
            ]);

            if ($validar_datos->fails()) {
                return response()->json(
                    [
                        "code" => 400,
                        "status" => "error",
                        "message" => "Error al enviar los datos",
                        "errores" => $validar_datos->errors(),
                    ],
                    400
                );
            } else {
                $id_solicitud = $params_array["solicitudes_id"];
                $id_personal = $params_array["personal_id"];

                $con = array_key_exists("flag", $params_array) ? true : false;

                $id_departamento = $this->obtenerDepartamento(
                    $id_personal,
                    $con
                );

                // CONSULTAR LAS MATERIAS PARA ACTUALIZAR EL ESTADO A REVISION DOCENTE
                $materias_solicitud = DB::select("SELECT so.id, mh.materia_id, de.iddepartamento
                FROM esq_homo.solicitudes so
                JOIN esq_homo.materias_homologar mh ON mh.solicitudes_id = so.id
                JOIN esq_mallas.malla_materia_nivel mmn ON mmn.idmateria = mh.materia_id
                JOIN esq_distributivos.materia_unica mu ON mu.idmateria_unica = mmn.idmateria_unica
                JOIN esq_distributivos.departamento de ON de.iddepartamento = mu.iddepartamento
                WHERE so.id = $id_solicitud AND de.iddepartamento = $id_departamento");

                if (count($materias_solicitud) > 0) {
                    try {
                        DB::beginTransaction();

                        for ($i = 0; $i < count($materias_solicitud); $i++) {
                            DB::table("materias_homologar")
                                ->join(
                                    "solicitudes",
                                    "solicitudes.id",
                                    "materias_homologar.solicitudes_id"
                                )
                                ->where(
                                    "materias_homologar.solicitudes_id",
                                    $id_solicitud
                                )
                                ->where(
                                    "materias_homologar.materia_id",
                                    $materias_solicitud[$i]->materia_id
                                )
                                ->update([
                                    "materias_homologar.estado" =>
                                        "EN REVISIÓN DOCENTE",
                                ]);
                        }

                        // PROCESO PARA VERIFICAR SI TODAS LAS MATERIAS ESTAN EN REVISION DOCENTE
                        // DE SER ASI, SE CAMBIA EL ESTADADO DE LA SOLICITUD A REVISION D
                        $materias = DB::select("SELECT so.id, mh.materia_id, de.iddepartamento, mh.estado
                        FROM esq_homo.solicitudes so
                        JOIN esq_homo.materias_homologar mh ON mh.solicitudes_id = so.id
                        JOIN esq_mallas.malla_materia_nivel mmn ON mmn.idmateria = mh.materia_id
                        JOIN esq_distributivos.materia_unica mu ON mu.idmateria_unica = mmn.idmateria_unica
                        JOIN esq_distributivos.departamento de ON de.iddepartamento = mu.iddepartamento
                        WHERE so.id = $id_solicitud");

                        $completado = false;
                        foreach ($materias as $materia) {
                            if (
                                $materia->estado === "DOCENTE ASIGNADO" ||
                                $materia->estado === null
                            ) {
                                $completado = false;
                                break;
                            } else {
                                $completado = true;
                            }
                        }
                        if ($completado) {
                            DB::table("estado_solicitudes")
                                ->where("solicitudes_id", $id_solicitud)
                                ->update(["estado" => "EN REVISIÓN D"]);
                        }

                        DB::commit();

                        return response()->json(
                            [
                                "code" => 200,
                                "status" => "success",
                            ],
                            200
                        );
                    } catch (\Exception $e) {
                        DB::rollBack();

                        return response()->json(
                            [
                                "code" => 400,
                                "status" => "error",
                                "message" => "Error al guardar los datos",
                                "errores" => $e,
                            ],
                            400
                        );
                    }
                }
            }
        } else {
            return response()->json(
                [
                    "code" => 400,
                    "status" => "error",
                    "message" => "Error al enviar los datos",
                ],
                400
            );
        }
    }

    public function indexCambioCarreraPorDoAnalisis($idPersonal)
    {
        if (isset($idPersonal)) {
            $solicitudes = DB::select("SELECT so.id AS id_solicitud, pe.nombres, CONCAT(pe.apellido1,' ', pe.apellido2) as apellidos, esOrigen.nombre AS escuela_origen, esDestino.nombre AS escuela_destino,
            mo.tipo_modalidad AS modalidad, so.created_at AS fc, eso.estado as estado_solicitud
            FROM esq_datos_personales.personal pe
            JOIN esq_homo.solicitudes so ON so.personal_id = pe.idpersonal
            JOIN esq_inscripciones.escuela esOrigen ON so.escuelas_origen_id = esOrigen.idescuela
            JOIN esq_inscripciones.escuela esDestino ON esDestino.idescuela = so.escuelas_destino_id
            JOIN esq_homo.modalidades mo ON mo.id = so.modalidad1_id
            JOIN esq_homo.estado_solicitudes eso ON eso.solicitudes_id = so.id
            JOIN esq_homo.materias_homologar mh ON mh.solicitudes_id = so.id
            WHERE mh.personal_id = $idPersonal AND so.tipo = 'CAMBIO CARRERA' AND NOT mh.estado = 'DOCENTE ASIGNADO' AND mh.estado IS NOT NULL
            GROUP BY so.id, pe.nombres, CONCAT(pe.apellido1,' ', pe.apellido2), esOrigen.nombre, esDestino.nombre,
            mo.tipo_modalidad, so.created_at, eso.estado, mh.estado
            ORDER BY so.id");

            return response()->json([
                "code" => 200,
                "status" => "success",
                "solicitudes" => $solicitudes,
            ]);
        }
    }

    public function indexCambioUniversidadPorDoAnalisis($idPersonal)
    {
        $solicitudes = DB::select("SELECT so.id AS id_solicitud, u.nombres, CONCAT(u.apellido_p, ' ', u.apellido_m) AS apellidos, 
        u.carrera_origen AS escuela_origen, esDestino.nombre AS escuela_destino, mo.tipo_modalidad AS modalidad, so.created_at AS fc, 
        eso.estado as estado_solicitud, so.tipo
        FROM esq_homo.solicitudes so
        JOIN esq_inscripciones.escuela esDestino ON esDestino.idescuela = so.escuelas_destino_id
        JOIN esq_homo.modalidades mo ON mo.id = so.modalidad1_id
        JOIN esq_homo.estado_solicitudes eso ON eso.solicitudes_id = so.id
        JOIN esq_homo.materias_homologar mh ON mh.solicitudes_id = so.id
        JOIN esq_homo.usuarios_foraneos u ON u.id = so.usuarios_foraneos_id
        WHERE mh.personal_id = $idPersonal AND so.tipo = 'CAMBIO UNIVERSIDAD' AND NOT mh.estado = 'DOCENTE ASIGNADO' AND mh.estado IS NOT NULL
        GROUP BY so.id, u.nombres, CONCAT(u.apellido_p, ' ', u.apellido_m), u.carrera_origen, esDestino.nombre,
        mo.tipo_modalidad, so.created_at, eso.estado, so.tipo, mh.estado
        ORDER BY so.id");

        return response()->json([
            "code" => 200,
            "status" => "success",
            "solicitudes" => $solicitudes,
        ]);
    }

    public function detalleSolicitud($idPersonal, $idSolicitud, $tipo)
    {
        if (isset($idPersonal) && isset($idSolicitud) && isset($tipo)) {
            if (intval($tipo) == 1) {
                $detalle_solicitud = DB::select("SELECT so.id AS id_solicitud, so.personal_id, so.malla_origen_id AS malla, pe.nombres, CONCAT(pe.apellido1,' ', pe.apellido2) as apellidos, 
                esOrigen.idescuela AS idescuela, esOrigen.nombre AS escuela_origen, esDestino.nombre AS escuela_destino, mo.tipo_modalidad AS modalidad, so.created_at AS fc, eso.fecha_actualizacion AS fa, 
                eso.id AS id_esso, eso.estado AS estado_solicitud, eso.observaciones AS observaciones, so.pdf AS documentos, so.pdf_completo AS documento_completo
                FROM esq_datos_personales.personal pe
                JOIN esq_homo.solicitudes so ON so.personal_id = pe.idpersonal
                JOIN esq_inscripciones.escuela esOrigen ON so.escuelas_origen_id = esOrigen.idescuela
                JOIN esq_inscripciones.escuela esDestino ON esDestino.idescuela = so.escuelas_destino_id
                JOIN esq_homo.modalidades mo ON mo.id = so.modalidad1_id
                JOIN esq_homo.estado_solicitudes eso ON eso.solicitudes_id = so.id
                WHERE so.id = $idSolicitud");
            } elseif (intval($tipo) == 2) {
                $detalle_solicitud = DB::select("SELECT so.id AS id_solicitud, u.nombres, CONCAT(u.apellido_p, ' ', u.apellido_m) AS apellidos, 
                u.carrera_origen AS escuela_origen, esDestino.nombre AS escuela_destino, mo.tipo_modalidad AS modalidad, so.created_at AS fc, eso.fecha_actualizacion AS fa,
                eso.id AS id_esso, eso.estado AS estado_solicitud, eso.observaciones AS observaciones, so.pdf AS documentos, so.pdf_completo AS documento_completo
                FROM esq_homo.solicitudes so
                JOIN esq_inscripciones.escuela esDestino ON esDestino.idescuela = so.escuelas_destino_id
                JOIN esq_homo.modalidades mo ON mo.id = so.modalidad1_id
                JOIN esq_homo.estado_solicitudes eso ON eso.solicitudes_id = so.id
                JOIN esq_homo.materias_homologar mh ON mh.solicitudes_id = so.id
                JOIN esq_homo.usuarios_foraneos u ON u.id = so.usuarios_foraneos_id
                WHERE so.id = $idSolicitud
                GROUP BY id_solicitud, u.nombres, CONCAT(u.apellido_p, ' ', u.apellido_m), u.carrera_origen, esDestino.nombre, mo.tipo_modalidad, so.created_at, eso.fecha_actualizacion,
				eso.id, eso.estado, eso.observaciones, so.pdf, so.pdf_completo");
            }

            $materias_solicitud = DB::select("SELECT es.nombre as escuela, me.nombre as malla, m.nombre as materia, n.nombre AS nivel, nombre_materia_procedencia,
                numero_creditos_procedencia, anio_aprobacion_materia, porcentaje_similiutd_contenidos, puntaje_asentar, observaciones, mh.pdf, pdf_analisis, mh.personal_id,
                aprobada, mh.materia_id, mh.escuela_id, pe2.apellido1 || ' ' || pe2.apellido2 || ' ' || pe2.nombres n_docente, mh.estado
                FROM esq_homo.solicitudes so
                JOIN esq_homo.materias_homologar mh ON mh.solicitudes_id = so.id
                JOIN esq_inscripciones.escuela es ON es.idescuela = mh.escuela_id
                JOIN esq_mallas.malla_escuela me ON me.idmalla = mh.malla_id
                JOIN esq_mallas.malla_materia_nivel mmn ON mmn.idmateria = mh.materia_id
                JOIN esq_mallas.materia m ON m.idmateria = mmn.idmateria
                JOIN esq_mallas.nivel n ON n.idnivel = mmn.idnivel
                JOIN esq_datos_personales.personal pe2 ON pe2.idpersonal = mh.personal_id
                WHERE so.id = $idSolicitud AND mh.personal_id = $idPersonal AND NOT mh.estado = 'DOCENTE ASIGNADO' AND mh.estado IS NOT NULL
                ORDER BY n.idnivel");

            return response()->json(
                [
                    "code" => 200,
                    "status" => "success",
                    "detalleSolicitud" => $detalle_solicitud,
                    "materiasSolicitud" => $materias_solicitud,
                ],
                200
            );
        } else {
            return response()->json(
                [
                    "code" => 400,
                    "status" => "error",
                    "message" => "Error al enviar los datos.",
                ],
                400
            );
        }
    }

    public function getSolicitudMateriaDoAnalisis(Request $request)
    {
        if (
            !empty($request->query("solicitudes_id")) &&
            !empty($request->query("personal_id")) &&
            !empty($request->query("materia_id")) &&
            !empty($request->query("tipo"))
        ) {
            $idSolicitud = $request->query("solicitudes_id");
            $idPersonal = $request->query("personal_id");
            $idMateria = $request->query("materia_id");
            $tipo = intval($request->query("tipo"));

            if ($tipo == 1) {
                $detalleSolicitud = DB::select("SELECT so.id AS id_solicitud, so.personal_id, so.malla_origen_id AS malla, pe.nombres, CONCAT(pe.apellido1,' ', pe.apellido2) as apellidos, 
                esOrigen.idescuela AS idescuela, esOrigen.nombre AS escuela_origen, esDestino.nombre AS escuela_destino, mo.tipo_modalidad AS modalidad, so.created_at AS fc, eso.fecha_actualizacion AS fa, 
                eso.id AS id_esso, eso.estado AS estado_solicitud, eso.observaciones AS observaciones, so.pdf AS documentos, so.pdf_completo AS documento_completo
                FROM esq_datos_personales.personal pe
                JOIN esq_homo.solicitudes so ON so.personal_id = pe.idpersonal
                JOIN esq_inscripciones.escuela esOrigen ON so.escuelas_origen_id = esOrigen.idescuela
                JOIN esq_inscripciones.escuela esDestino ON esDestino.idescuela = so.escuelas_destino_id
                JOIN esq_homo.modalidades mo ON mo.id = so.modalidad1_id
                JOIN esq_homo.estado_solicitudes eso ON eso.solicitudes_id = so.id
                WHERE so.id = $idSolicitud");
            } elseif ($tipo == 2) {
                $detalleSolicitud = DB::select("SELECT so.id AS id_solicitud, u.nombres, CONCAT(u.apellido_p, ' ', u.apellido_m) AS apellidos, 
                u.carrera_origen AS escuela_origen, esDestino.nombre AS escuela_destino, mo.tipo_modalidad AS modalidad, so.created_at AS fc, eso.fecha_actualizacion AS fa,
                eso.id AS id_esso, eso.estado AS estado_solicitud, eso.observaciones AS observaciones, so.pdf AS documentos, so.pdf_completo AS documento_completo
                FROM esq_homo.solicitudes so
                JOIN esq_inscripciones.escuela esDestino ON esDestino.idescuela = so.escuelas_destino_id
                JOIN esq_homo.modalidades mo ON mo.id = so.modalidad1_id
                JOIN esq_homo.estado_solicitudes eso ON eso.solicitudes_id = so.id
                JOIN esq_homo.materias_homologar mh ON mh.solicitudes_id = so.id
                JOIN esq_homo.usuarios_foraneos u ON u.id = so.usuarios_foraneos_id
                WHERE so.id = $idSolicitud
				GROUP BY id_solicitud, u.nombres, CONCAT(u.apellido_p, ' ', u.apellido_m), u.carrera_origen, esDestino.nombre, mo.tipo_modalidad, so.created_at, eso.fecha_actualizacion,
				eso.id, eso.estado, eso.observaciones, so.pdf, so.pdf_completo;");
            }

            $materiasSolicitud = DB::select("SELECT es.nombre as escuela, me.nombre as malla, m.nombre as materia, n.nombre AS nivel, nombre_materia_procedencia,
            numero_creditos_procedencia, anio_aprobacion_materia, porcentaje_similiutd_contenidos, puntaje_asentar, observaciones, mh.pdf, pdf_analisis, mh.personal_id,
            aprobada, mh.materia_id, mh.escuela_id, pe2.apellido1 || ' ' || pe2.apellido2 || ' ' || pe2.nombres n_docente, mh.estado
            FROM esq_homo.solicitudes so
            JOIN esq_homo.materias_homologar mh ON mh.solicitudes_id = so.id
            JOIN esq_inscripciones.escuela es ON es.idescuela = mh.escuela_id
            JOIN esq_mallas.malla_escuela me ON me.idmalla = mh.malla_id
            JOIN esq_mallas.malla_materia_nivel mmn ON mmn.idmateria = mh.materia_id
            JOIN esq_mallas.materia m ON m.idmateria = mmn.idmateria
            JOIN esq_mallas.nivel n ON n.idnivel = mmn.idnivel
            JOIN esq_datos_personales.personal pe2 ON pe2.idpersonal = mh.personal_id
            WHERE so.id = $idSolicitud AND mh.personal_id = $idPersonal AND NOT mh.estado = 'DOCENTE ASIGNADO' AND mh.estado IS NOT NULL AND mh.materia_id = $idMateria");

            return response()->json(
                [
                    "code" => 200,
                    "status" => "success",
                    "detalleSolicitud" => $detalleSolicitud,
                    "materiasSolicitud" => $materiasSolicitud,
                ],
                200
            );
        } else {
            return response()->json([
                "code" => 400,
                "status" => "error",
                "message" => "Error al enviar los datos",
                "datos_enviados" => [
                    $request->query("solicitudes_id"),
                    $request->query("personal_id"),
                    $request->query("materia_id"),
                    $request->query("tipo"),
                ],
            ]);
        }
    }

    public function updateMateriaHomologar(Request $request)
    {
        $json = $request->input("json", null);

        $params_array = json_decode($json, true);

        if (!empty($params_array)) {
            // VALIDAR LOS DATOS

            $validarDatos = Validator::make($params_array, [
                "solicitudes_id" => "required",
                "materia_id" => "required",
                "nombre_materia_procedencia" => "required",
                "numero_creditos_procedencia" => "required",
                "anio_aprobacion_materia" => "required",
                "porcentaje_similiutd_contenidos" => "required",
                "puntaje_asentar" => "required",
                "observaciones" => "required",
            ]);

            if ($validarDatos->fails()) {
                return response()->json(
                    [
                        "code" => 400,
                        "status" => "error",
                        "message" => "Error al enviar los datos.",
                        "errores" => $validarDatos->errors(),
                    ],
                    400
                );
            } else {
                try {
                    DB::beginTransaction();

                    DB::table("materias_homologar")
                        ->where(
                            "solicitudes_id",
                            $params_array["solicitudes_id"]
                        )
                        ->where("materia_id", $params_array["materia_id"])
                        ->update([
                            "nombre_materia_procedencia" => strtoupper(
                                $params_array["nombre_materia_procedencia"]
                            ),
                            "numero_creditos_procedencia" =>
                                $params_array["numero_creditos_procedencia"],
                            "anio_aprobacion_materia" =>
                                $params_array["anio_aprobacion_materia"],
                            "porcentaje_similiutd_contenidos" =>
                                $params_array[
                                    "porcentaje_similiutd_contenidos"
                                ],
                            "puntaje_asentar" =>
                                $params_array["puntaje_asentar"],
                            "observaciones" => strtoupper(
                                $params_array["observaciones"]
                            ),
                            "estado" => "DATOS ESTABLECIDOS",
                        ]);

                    DB::commit();

                    return response()->json(
                        [
                            "code" => 200,
                            "status" => "success",
                        ],
                        200
                    );
                } catch (\Exception $e) {
                    DB::rollBack();

                    return response()->json(
                        [
                            "code" => 400,
                            "status" => "error",
                            "message" => "Error al guardar los datos.",
                            "errores" => $e,
                        ],
                        400
                    );
                }
            }
        } else {
            return response()->json(
                [
                    "code" => 400,
                    "status" => "error",
                    "message" => "Error al enviar los datos.",
                ],
                400
            );
        }
    }

    public function createPdfAnalisisMateria(Request $request)
    {
        if (
            !empty($request->input("datos")) &&
            !empty($request->input("solicitudes_id")) &&
            !empty($request->input("docente_id")) &&
            !empty($request->input("tipo"))
        ) {
            $jsonMaterias = $request->input("datos", null);
            $params_array = json_decode($jsonMaterias);
            $idSolicitud = intval($request->input("solicitudes_id"));
            $idDocente = intval($request->input("docente_id"));
            $tipo = intval($request->input("tipo"));

            if ($tipo == 1) {
                $detalleSolicitud = DB::select("SELECT so.id AS id_solicitud, so.personal_id, so.malla_origen_id AS malla, pe.nombres, CONCAT(pe.apellido1,' ', pe.apellido2) as apellidos, 
                esOrigen.idescuela AS idescuela, esOrigen.nombre AS escuela_origen, esDestino.nombre AS escuela_destino, mo.tipo_modalidad AS modalidad, so.created_at AS fc, eso.fecha_actualizacion AS fa, 
                eso.id AS id_esso, eso.estado AS estado_solicitud, eso.observaciones AS observaciones, so.pdf AS documentos, so.pdf_completo AS documento_completo, pe.cedula,
                'UNIVERSIDAD TÉCNICA DE MANABÍ' AS universidad
                FROM esq_datos_personales.personal pe
                JOIN esq_homo.solicitudes so ON so.personal_id = pe.idpersonal
                JOIN esq_inscripciones.escuela esOrigen ON so.escuelas_origen_id = esOrigen.idescuela
                JOIN esq_inscripciones.escuela esDestino ON esDestino.idescuela = so.escuelas_destino_id
                JOIN esq_homo.modalidades mo ON mo.id = so.modalidad1_id
                JOIN esq_homo.estado_solicitudes eso ON eso.solicitudes_id = so.id
                WHERE so.id = $idSolicitud");
            } elseif ($tipo == 2) {
                $detalleSolicitud = DB::select("SELECT so.id AS id_solicitud, u.nombres, CONCAT(u.apellido_p, ' ', u.apellido_m) AS apellidos, 
                u.carrera_origen AS escuela_origen, esDestino.nombre AS escuela_destino, mo.tipo_modalidad AS modalidad, so.created_at AS fc, eso.fecha_actualizacion AS fa,
                eso.id AS id_esso, eso.estado AS estado_solicitud, eso.observaciones AS observaciones, so.pdf AS documentos, so.pdf_completo AS documento_completo, u.cedula,
                pu.nombre AS universidad
                FROM esq_homo.solicitudes so
                JOIN esq_inscripciones.escuela esDestino ON esDestino.idescuela = so.escuelas_destino_id
                JOIN esq_homo.modalidades mo ON mo.id = so.modalidad1_id
                JOIN esq_homo.estado_solicitudes eso ON eso.solicitudes_id = so.id
                JOIN esq_homo.materias_homologar mh ON mh.solicitudes_id = so.id
                JOIN esq_homo.usuarios_foraneos u ON u.id = so.usuarios_foraneos_id
                JOIN esq_datos_personales.p_universidad pu ON pu.iduniversidad = so.universidades_id
                WHERE so.id = $idSolicitud");
            }

            $facultad = DB::select(
                "SELECT d.idfacultad, f.nombre
                FROM esq_distributivos.departamento_docente dd
                JOIN esq_distributivos.departamento d ON d.iddepartamento = dd.iddepartamento
                AND NOT d.habilitado = 'N'
                JOIN esq_inscripciones.facultad f ON d.idfacultad = f.idfacultad
                WHERE dd.idpersonal = $idDocente"
            );

            $materiasSolicitud = [];

            for ($i = 0; $i < count($params_array); $i++) {
                $ms = DB::select(
                    "SELECT es.nombre as escuela, me.nombre as malla, m.nombre as materia, n.nombre AS nivel, nombre_materia_procedencia,
                    numero_creditos_procedencia, anio_aprobacion_materia, porcentaje_similiutd_contenidos, puntaje_asentar, observaciones, mh.pdf, pdf_analisis, mh.personal_id,
                    aprobada, mh.materia_id, mh.escuela_id, pe2.apellido1 || ' ' || pe2.apellido2 || ' ' || pe2.nombres n_docente, mmn.creditos
                    FROM esq_homo.solicitudes so
                    JOIN esq_homo.materias_homologar mh ON mh.solicitudes_id = so.id
                    JOIN esq_inscripciones.escuela es ON es.idescuela = mh.escuela_id
                    JOIN esq_mallas.malla_escuela me ON me.idmalla = mh.malla_id
                    JOIN esq_mallas.malla_materia_nivel mmn ON mmn.idmateria = mh.materia_id
                    JOIN esq_mallas.materia m ON m.idmateria = mmn.idmateria
                    JOIN esq_mallas.nivel n ON n.idnivel = mmn.idnivel
                    JOIN esq_datos_personales.personal pe2 ON pe2.idpersonal = mh.personal_id
                    WHERE so.id = $idSolicitud AND mh.materia_id = " .
                        $params_array[$i]
                );

                array_push($materiasSolicitud, $ms[0]);
            }

            $docente = DB::select("SELECT CONCAT(p.nombres, ' ', p.apellido1, ' ', p.apellido2) AS nombre_docente
            FROM esq_datos_personales.personal p
            WHERE p.idpersonal = $idDocente");

            // CREANDO EL PDF DE LA SOLICITUD CON DOMPDF
            $fecha_actual = explode("/", now()->format("d/m/Y"));

            $pdf = app("dompdf.wrapper");
            $pdf->loadView(
                "analisismateriapdf",
                compact(
                    "fecha_actual",
                    "detalleSolicitud",
                    "materiasSolicitud",
                    "facultad",
                    "docente"
                )
            )->setPaper("a4");
            $pdf_solicitud_creado = $pdf->output();

            return response($pdf_solicitud_creado)
                ->header("Content-Type", "application/pdf")
                ->header(
                    "Content-Disposition",
                    "inline; filename=" . time() . ".pdf"
                );
        } else {
            return response()->json([
                "code" => 400,
                "status" => "error",
                "message" => "Error al enviar los datos",
                "respuesta" => $request->input("datos"),
            ]);
        }
    }

    public function subirPdfAnalisisFirmado(Request $request)
    {
        // RECOGER LOS DATOS POR POST
        $solicitudes_id = json_decode($request->input("solicitudes_id", null));
        $materias_id = json_decode($request->input("materias_id", null));

        $archivo = $request->file("file0");

        if (!isset($solicitudes_id) && empty($materias_id)) {
            return response()->json(
                [
                    "code" => 400,
                    "status" => "error",
                    "message" => "Error al enviar los datos",
                    "so" => $solicitudes_id,
                ],
                400
            );
        }

        if (!is_file($archivo)) {
            return response()->json(
                [
                    "code" => 400,
                    "status" => "error",
                    "message" => "Error al enviar los archivos PDF",
                ],
                400
            );
        }

        try {
            DB::beginTransaction();

            // FECHA Y HORA PARA ACTUALIZAR LOS CAMPOS CORRESPONDIENTES
            $fecha_hora = now();
            $nombre_pdf_materia = time() . ".pdf";

            // ACTUALIZAR LA FECHA-HORA DE ACTULIZACION DE LAS SOLICITUDES
            DB::table("solicitudes")
                ->where("id", $solicitudes_id)
                ->update(["updated_at" => $fecha_hora]);

            DB::table("estado_solicitudes")
                ->where("solicitudes_id", $solicitudes_id)
                ->update([
                    "fecha_actualizacion" => $fecha_hora,
                ]);

            foreach ($materias_id as $materia_id) {
                DB::table("materias_homologar")
                    ->where("solicitudes_id", $solicitudes_id)
                    ->where("materia_id", $materia_id)
                    ->update([
                        "pdf_analisis" => $nombre_pdf_materia,
                        "estado" => "INFORME COMPLETADO",
                    ]);
            }

            // PROCESO PARA VERIFICAR SI TODAS LAS MATERIAS ESTAN EN REVISION DOCENTE
            // DE SER ASI, SE CAMBIA EL ESTADADO DE LA SOLICITUD A REVISION D
            $materias = DB::select("SELECT so.id, mh.materia_id, de.iddepartamento, mh.estado
                        FROM esq_homo.solicitudes so
                        JOIN esq_homo.materias_homologar mh ON mh.solicitudes_id = so.id
                        JOIN esq_mallas.malla_materia_nivel mmn ON mmn.idmateria = mh.materia_id
                        JOIN esq_distributivos.materia_unica mu ON mu.idmateria_unica = mmn.idmateria_unica
                        JOIN esq_distributivos.departamento de ON de.iddepartamento = mu.iddepartamento
                        WHERE so.id = $solicitudes_id");

            $completado = false;
            foreach ($materias as $materia) {
                if ($materia->estado !== "INFORME COMPLETADO") {
                    $completado = false;
                    break;
                } else {
                    $completado = true;
                }
            }
            if ($completado) {
                DB::table("estado_solicitudes")
                    ->where("solicitudes_id", $solicitudes_id)
                    ->update(["estado" => "DEV DOC A CM"]);
            }

            $so_verificar = DB::table("solicitudes")
                ->where("id", $solicitudes_id)
                ->value("departamento_destino_id");

            if ($so_verificar === 15) {
                $materias_dep_15 = DB::select("SELECT so.id, mh.materia_id, de.iddepartamento, mh.estado
                    FROM esq_homo.solicitudes so
                    JOIN esq_homo.materias_homologar mh ON mh.solicitudes_id = so.id
                    JOIN esq_mallas.malla_materia_nivel mmn ON mmn.idmateria = mh.materia_id
                    JOIN esq_distributivos.materia_unica mu ON mu.idmateria_unica = mmn.idmateria_unica
                    JOIN esq_distributivos.departamento de ON de.iddepartamento = mu.iddepartamento
                    WHERE so.id = $solicitudes_id AND de.iddepartamento = 15");

                foreach ($materias_dep_15 as $materia) {
                    if (
                        $so_verificar === 15 &&
                        $materia->iddepartamento === 15 &&
                        $materia->estado === "INFORME COMPLETADO"
                    ) {
                        DB::table("materias_homologar")
                            ->where("materia_id", $materia->materia_id)
                            ->update(["check_cm" => true]);
                    }
                }

                $materias_only_dep_15 = DB::table("materias_homologar")
                    ->where("solicitudes_id", $solicitudes_id)
                    ->get();

                $completado_2 = false;

                foreach ($materias_only_dep_15 as $materia) {
                    if ($materia->check_cm === false) {
                        $completado_2 = false;
                        break;
                    } else {
                        $completado_2 = true;
                    }
                }

                if ($completado_2) {
                    DB::table("estado_solicitudes")
                        ->where("solicitudes_id", $solicitudes_id)
                        ->update(["estado" => "DEV CM A RC"]);
                }
            }

            try {
                Storage::disk("ftpHomologacion")->put(
                    $nombre_pdf_materia,
                    File::get($archivo)
                );
            } catch (\Exception $ex) {
                if (
                    Storage::disk("ftpHomologacion")->exists(
                        $nombre_pdf_materia
                    )
                ) {
                    Storage::disk("ftpHomologacion")->delete(
                        $nombre_pdf_materia
                    );
                }

                DB::rollBack();

                // DEVOLVER DATOS
                return response()->json(
                    [
                        "code" => 400,
                        "status" => "error",
                        "message" => "Error al guardar el PDF",
                        "error" => $ex,
                    ],
                    400
                );
            }

            DB::commit();

            // DEVOLVER DATOS
            return response()->json(
                [
                    "code" => 200,
                    "status" => "success",
                ],
                200
            );
        } catch (\Exception $e) {
            DB::rollBack();

            // DEVOLVER DATOS
            return response()->json(
                [
                    "code" => 400,
                    "status" => "error",
                    "message" => "Error al guardar los datos",
                    "error" => $e,
                ],
                400
            );
        }
    }
}
