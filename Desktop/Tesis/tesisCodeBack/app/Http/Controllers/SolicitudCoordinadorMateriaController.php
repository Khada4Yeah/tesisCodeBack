<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class SolicitudCoordinadorMateriaController extends Controller
{
    public function indexCambioCarreraPorCoMaterias($idPersonal)
    {
        if (isset($idPersonal)) {
            $id_departamento = $this->obtenerDepartamentoCoMaterias(
                $idPersonal
            );

            $solicitudes = DB::select("SELECT so.id AS id_solicitud, pe.nombres, CONCAT(pe.apellido1, ' ', pe.apellido2) as apellidos, 
            esOrigen.nombre AS escuela_origen, esDestino.nombre AS escuela_destino, mo.tipo_modalidad AS modalidad, 
            so.created_at AS fc, eso.estado as estado_solicitud, so.tipo
            FROM esq_datos_personales.personal pe
            JOIN esq_homo.solicitudes so on pe.idpersonal = so.personal_id
            JOIN esq_inscripciones.escuela esOrigen ON esOrigen.idescuela = so.escuelas_origen_id
            JOIN esq_inscripciones.escuela esDestino ON esDestino.idescuela = so.escuelas_destino_id
            JOIN esq_homo.modalidades mo ON mo.id = so.modalidad1_id
            JOIN esq_homo.materias_homologar mh ON mh.solicitudes_id = so.id
            JOIN esq_homo.estado_solicitudes eso ON eso.solicitudes_id = so.id
            JOIN esq_mallas.malla_materia_nivel mmn ON mmn.idmateria = mh.materia_id
            JOIN esq_distributivos.materia_unica mu ON mu.idmateria_unica = mmn.idmateria_unica
            JOIN esq_distributivos.departamento de ON de.iddepartamento = mu.iddepartamento
            WHERE de.iddepartamento = $id_departamento AND so.tipo = 'CAMBIO CARRERA'
            GROUP BY so.id, pe.nombres, CONCAT(pe.apellido1, ' ', pe.apellido2), esOrigen.nombre,
            esDestino.nombre, mo.tipo_modalidad, so.created_at, eso.estado, so.tipo
            ORDER BY so.id");

            return response()->json(
                [
                    "code" => 200,
                    "status" => "success",
                    "solicitudes" => $solicitudes,
                    "datos" => $idPersonal,
                    "datos2" => $id_departamento,
                ],
                200
            );
        }
    }

    public function indexCambioUniversidadPorCoMaterias($idPersonal)
    {
        if (isset($idPersonal)) {
            $id_departamento = $this->obtenerDepartamentoCoMaterias(
                $idPersonal
            );

            $solicitudes = DB::select("SELECT so.id AS id_solicitud, u.nombres, CONCAT(u.apellido_p, ' ', u.apellido_m) AS apellidos, 
            u.carrera_origen AS escuela_origen, esDestino.nombre AS escuela_destino, mo.tipo_modalidad AS modalidad, so.created_at AS fc, 
            eso.estado as estado_solicitud, so.tipo
            FROM esq_homo.solicitudes so
            JOIN esq_inscripciones.escuela esDestino ON esDestino.idescuela = so.escuelas_destino_id
            JOIN esq_homo.modalidades mo ON mo.id = so.modalidad1_id
            JOIN esq_homo.estado_solicitudes eso ON eso.solicitudes_id = so.id
            JOIN esq_homo.materias_homologar mh ON mh.solicitudes_id = so.id
            JOIN esq_homo.usuarios_foraneos u ON u.id = so.usuarios_foraneos_id
            JOIN esq_mallas.malla_materia_nivel mmn ON mmn.idmateria = mh.materia_id
            JOIN esq_distributivos.materia_unica_configuracion muc ON muc.id_materia_unica = mmn.idmateria_unica
            JOIN esq_distributivos.departamento de ON de.iddepartamento = muc.id_departamento
            WHERE de.iddepartamento = $id_departamento AND so.tipo = 'CAMBIO UNIVERSIDAD'
            GROUP BY so.id, u.nombres, CONCAT(u.apellido_p, ' ', u.apellido_m), u.carrera_origen,
            esDestino.nombre, mo.tipo_modalidad, so.created_at, eso.estado, de.iddepartamento, so.tipo
            ORDER BY so.id");

            return response()->json(
                [
                    "code" => 200,
                    "status" => "success",
                    "solicitudes" => $solicitudes,
                    "datos" => $idPersonal,
                    "datos2" => $id_departamento,
                ],
                200
            );
        }
    }

    public function obtenerDepartamentoCoMaterias($idPersonal, $flag = false)
    {
        if (isset($idPersonal)) {
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

            if ($flag) {
                return response()->json(
                    [
                        "code" => 200,
                        "status" => "success",
                        "id_departamento" =>
                            $id_departamento[0]->iddepartamento_numero,
                    ],
                    200
                );
            } else {
                return $id_departamento[0]->iddepartamento_numero;
            }
        }
    }

    public function detalleSolicitud($idPersonal, $idSolicitud, $tipo)
    {
        if (isset($idPersonal) && isset($idSolicitud) && isset($tipo)) {
            $id_departamento = $this->obtenerDepartamentoCoMaterias(
                $idPersonal
            );

            if (intval($tipo) == 1) {
                $detalle_solicitud = DB::select("SELECT so.id AS id_solicitud, pe.nombres, CONCAT(pe.apellido1, ' ', pe.apellido2) as apellidos, 
                esOrigen.nombre AS escuela_origen, esDestino.nombre AS escuela_destino, mo.tipo_modalidad AS modalidad, 
                so.created_at AS fc, so.updated_at AS fa, eso.estado as estado_solicitud, eso.observaciones AS observaciones, so.pdf AS documentos, 
                so.pdf_completo AS documento_completo, so.tipo
                FROM esq_datos_personales.personal pe
                JOIN esq_homo.solicitudes so on pe.idpersonal = so.personal_id
                JOIN esq_inscripciones.escuela esOrigen ON esOrigen.idescuela = so.escuelas_origen_id
                JOIN esq_inscripciones.escuela esDestino ON esDestino.idescuela = so.escuelas_destino_id
                JOIN esq_homo.modalidades mo ON mo.id = so.modalidad1_id
                JOIN esq_homo.materias_homologar mh ON mh.solicitudes_id = so.id
                JOIN esq_homo.estado_solicitudes eso ON eso.solicitudes_id = so.id
                JOIN esq_mallas.malla_materia_nivel mmn ON mmn.idmateria = mh.materia_id
                JOIN esq_distributivos.materia_unica mu ON mu.idmateria_unica = mmn.idmateria_unica
                JOIN esq_distributivos.departamento de ON de.iddepartamento = mu.iddepartamento
                WHERE so.id = $idSolicitud
                GROUP BY so.id, pe.nombres, CONCAT(pe.apellido1, ' ', pe.apellido2), esOrigen.nombre,
                esDestino.nombre, mo.tipo_modalidad, so.created_at, eso.estado, eso.observaciones,
                so.pdf, so.pdf_completo, so.tipo");
            } elseif (intval($tipo) == 2) {
                $detalle_solicitud = DB::select("SELECT so.id AS id_solicitud, u.nombres, CONCAT(u.apellido_p, ' ', u.apellido_m) AS apellidos, 
                u.carrera_origen AS escuela_origen, esDestino.nombre AS escuela_destino, mo.tipo_modalidad AS modalidad, so.created_at AS fc, 
                so.updated_at AS fa, eso.estado as estado_solicitud, eso.observaciones AS observaciones, so.pdf AS documentos, 
                so.pdf_completo AS documento_completo, so.tipo
                FROM esq_homo.solicitudes so
                JOIN esq_inscripciones.escuela esDestino ON esDestino.idescuela = so.escuelas_destino_id
                JOIN esq_homo.modalidades mo ON mo.id = so.modalidad1_id
                JOIN esq_homo.estado_solicitudes eso ON eso.solicitudes_id = so.id
                JOIN esq_homo.materias_homologar mh ON mh.solicitudes_id = so.id
                JOIN esq_homo.usuarios_foraneos u ON u.id = so.usuarios_foraneos_id
                JOIN esq_mallas.malla_materia_nivel mmn ON mmn.idmateria = mh.materia_id
                JOIN esq_distributivos.materia_unica_configuracion muc ON muc.id_materia_unica = mmn.idmateria_unica
                JOIN esq_distributivos.departamento de ON de.iddepartamento = muc.id_departamento
                WHERE de.iddepartamento = $id_departamento
                GROUP BY so.id, u.nombres, CONCAT(u.apellido_p, ' ', u.apellido_m), u.carrera_origen,
                esDestino.nombre, mo.tipo_modalidad, so.created_at, eso.estado, de.iddepartamento, eso.observaciones,
                eso.estado, eso.observaciones, so.pdf, so.pdf_completo, so.tipo");
            }

            $materias_solicitud = DB::select("SELECT es.nombre as escuela, me.nombre as malla, m.nombre as materia, n.nombre AS nivel, nombre_materia_procedencia,
                numero_creditos_procedencia, anio_aprobacion_materia, porcentaje_similiutd_contenidos, puntaje_asentar, observaciones, mh.pdf, pdf_analisis, mh.personal_id,
                aprobada, mh.materia_id, de.iddepartamento, pe2.apellido1 || ' ' || pe2.apellido2 || ' ' || pe2.nombres n_docente, mh.estado AS estado_materia, check_cm
                FROM esq_homo.solicitudes so
                JOIN esq_homo.materias_homologar mh ON mh.solicitudes_id = so.id
                JOIN esq_inscripciones.escuela es ON es.idescuela = mh.escuela_id
                JOIN esq_mallas.malla_escuela me ON me.idmalla = mh.malla_id
                JOIN esq_mallas.malla_materia_nivel mmn ON mmn.idmateria = mh.materia_id
                JOIN esq_mallas.materia m ON m.idmateria = mmn.idmateria
                JOIN esq_mallas.nivel n ON n.idnivel = mmn.idnivel
				JOIN esq_distributivos.materia_unica mu ON mu.idmateria_unica = mmn.idmateria_unica
				JOIN esq_distributivos.departamento de ON de.iddepartamento = mu.iddepartamento
                FULL JOIN esq_datos_personales.personal pe2 ON pe2.idpersonal = mh.personal_id
                WHERE so.id = $idSolicitud AND de.iddepartamento = $id_departamento
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
                    "code" => 200,
                    "status" => "success",
                    "message" => "Error al enviar los datos",
                ],
                400
            );
        }
    }

    public function devolverReComision(Request $request)
    {
        $json = $request->input("json", null);

        $params_array = json_decode($json, true);

        if (
            !empty($params_array["solicitudes_id"]) &&
            !empty($params_array["materias_id"])
        ) {
            try {
                DB::beginTransaction();
                $fecha_hora = now();

                DB::table("estado_solicitudes")
                    ->where("solicitudes_id", $params_array["solicitudes_id"])
                    ->update(["fecha_actualizacion" => $fecha_hora]);

                DB::table("solicitudes")
                    ->where("id", $params_array["solicitudes_id"])
                    ->update(["updated_at" => $fecha_hora]);

                foreach ($params_array["materias_id"] as $materia_id) {
                    DB::table("materias_homologar")
                        ->where(
                            "solicitudes_id",
                            $params_array["solicitudes_id"]
                        )
                        ->where("materia_id", $materia_id)
                        ->update(["check_cm" => true]);
                }

                $materias_revisar = DB::table("materias_homologar")
                    ->where("solicitudes_id", $params_array["solicitudes_id"])
                    ->get("check_cm");

                $completado_cm = false;

                foreach ($materias_revisar as $materia) {
                    if ($materia->check_cm === false) {
                        $completado_cm = false;
                        break;
                    } else {
                        $completado_cm = true;
                    }
                }

                if ($completado_cm) {
                    DB::table("estado_solicitudes")
                        ->where(
                            "solicitudes_id",
                            $params_array["solicitudes_id"]
                        )
                        ->update(["estado" => "DEV CM A RC"]);
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
                        "errores" => $e,
                    ],
                    400
                );
            }
        } else {
            return response()->json(
                [
                    "code" => 200,
                    "status" => "error",
                    "mensaje" => "Error al enviar los datos",
                ],
                200
            );
        }
    }
}
