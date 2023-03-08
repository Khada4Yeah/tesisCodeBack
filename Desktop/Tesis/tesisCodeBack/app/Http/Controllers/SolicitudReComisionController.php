<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;

class SolicitudReComisionController extends Controller
{
    public function indexCambioCarreraPorReComision($idPersonal)
    {
        $id_departamento = $this->obtenerDepartamentoReComision($idPersonal);

        $solicitudes = DB::select("SELECT so.id AS id_solicitud, pe.nombres, CONCAT(pe.apellido1,' ', pe.apellido2) as apellidos, 
        esOrigen.nombre AS escuela_origen, esDestino.nombre AS escuela_destino, mo.tipo_modalidad AS modalidad, so.created_at AS fc, 
        eso.estado as estado_solicitud
        FROM esq_datos_personales.personal pe
        JOIN esq_homo.solicitudes so ON so.personal_id = pe.idpersonal
        JOIN esq_inscripciones.escuela esOrigen ON so.escuelas_origen_id = esOrigen.idescuela
        JOIN esq_inscripciones.escuela esDestino ON esDestino.idescuela = so.escuelas_destino_id
        JOIN esq_homo.modalidades mo ON mo.id = so.modalidad1_id
		JOIN esq_homo.estado_solicitudes eso ON eso.solicitudes_id = so.id
        JOIN esq_distributivos.departamento de ON de.iddepartamento = so.departamento_destino_id
        WHERE so.departamento_destino_id = $id_departamento AND so.tipo = 'CAMBIO CARRERA'
        ORDER BY so.id");

        return response()->json(
            [
                "code" => 200,
                "status" => "success",
                "solicitudes" => $solicitudes,
            ],
            200
        );
    }

    public function indexCambioUniversidadPorReComision($idPersonal)
    {
        $id_departamento = $this->obtenerDepartamentoReComision($idPersonal);

        $solicitudes = DB::select("SELECT so.id AS id_solicitud, u.nombres, CONCAT(u.apellido_p, ' ', u.apellido_m) AS apellidos, 
        u.carrera_origen AS escuela_origen, esDestino.nombre AS escuela_destino, mo.tipo_modalidad AS modalidad, so.created_at AS fc, 
        eso.estado as estado_solicitud
        FROM esq_homo.solicitudes so
        JOIN esq_inscripciones.escuela esDestino ON esDestino.idescuela = so.escuelas_destino_id
        JOIN esq_homo.modalidades mo ON mo.id = so.modalidad1_id
		JOIN esq_homo.estado_solicitudes eso ON eso.solicitudes_id = so.id
        JOIN esq_distributivos.departamento de ON de.iddepartamento = so.departamento_destino_id
        JOIN esq_homo.usuarios_foraneos u ON u.id = so.usuarios_foraneos_id
        WHERE so.departamento_destino_id = $id_departamento AND so.tipo = 'CAMBIO UNIVERSIDAD'
        ORDER BY so.id");

        return response()->json(
            [
                "code" => 200,
                "status" => "success",
                "solicitudes" => $solicitudes,
            ],
            200
        );
    }

    public function obtenerDepartamentoReComision($idPersonal, $flag = false)
    {
        $id_departamento = DB::select("SELECT u.departamento_id
        FROM esq_homo.usuarios u
        WHERE u.personal_id = $idPersonal AND u.cargos_id = 2
        ");

        if ($flag) {
            return response()->json(
                [
                    "code" => 200,
                    "status" => "success",
                    "id_departamento" => $id_departamento[0]->departamento_id,
                ],
                200
            );
        } else {
            return $id_departamento[0]->departamento_id;
        }
    }

    public function detalleSolicitud($idPersonal, $idSolicitud, $tipo)
    {
        if (isset($idPersonal) && isset($idSolicitud) && isset($tipo)) {
            $id_departamento = $this->obtenerDepartamentoReComision(
                $idPersonal
            );

            if (intval($tipo) == 1) {
                $detalle_solicitud = DB::select("SELECT so.id AS id_solicitud, so.personal_id, so.malla_origen_id AS malla, pe.nombres, CONCAT(pe.apellido1,' ', pe.apellido2) as apellidos, 
                esOrigen.idescuela AS idescuela, esOrigen.nombre AS escuela_origen, esDestino.nombre AS escuela_destino, mo.tipo_modalidad AS modalidad, so.created_at AS fc, eso.fecha_actualizacion AS fa, 
                eso.id AS id_esso, eso.estado AS estado_solicitud, eso.observaciones AS observaciones, so.pdf AS documentos, so.pdf_completo AS documento_completo, so.tipo
                FROM esq_datos_personales.personal pe
                JOIN esq_homo.solicitudes so ON so.personal_id = pe.idpersonal
                JOIN esq_inscripciones.escuela esOrigen ON so.escuelas_origen_id = esOrigen.idescuela
                JOIN esq_inscripciones.escuela esDestino ON esDestino.idescuela = so.escuelas_destino_id
                JOIN esq_homo.modalidades mo ON mo.id = so.modalidad1_id
                JOIN esq_homo.estado_solicitudes eso ON eso.solicitudes_id = so.id
                JOIN esq_distributivos.departamento de ON de.iddepartamento = so.departamento_destino_id
                WHERE so.departamento_destino_id = $id_departamento AND so.id = $idSolicitud");
            } elseif (intval($tipo) == 2) {
                $detalle_solicitud = DB::select("SELECT so.id AS id_solicitud, so.usuarios_foraneos_id, 'N/A' AS malla, u.nombres, CONCAT(u.apellido_p,' ', u.apellido_m) as apellidos, 
                u.carrera_origen AS escuela_origen, esDestino.nombre AS escuela_destino, mo.tipo_modalidad AS modalidad, so.created_at AS fc, eso.fecha_actualizacion AS fa, 
                eso.estado AS estado_solicitud, eso.observaciones AS observaciones, so.pdf AS documentos, so.pdf_completo AS documento_completo, so.tipo, eso.id AS id_esso, de.nombre AS departamento
                FROM esq_homo.solicitudes so
				JOIN esq_homo.usuarios_foraneos u ON u.id = so.usuarios_foraneos_id
                JOIN esq_inscripciones.escuela esDestino ON esDestino.idescuela = so.escuelas_destino_id
                JOIN esq_homo.modalidades mo ON mo.id = so.modalidad1_id
                JOIN esq_homo.estado_solicitudes eso ON eso.solicitudes_id = so.id
                LEFT JOIN esq_distributivos.departamento de ON de.iddepartamento = so.departamento_destino_id
                WHERE so.departamento_destino_id = $id_departamento AND so.id = $idSolicitud");
            }

            $materias_solicitud = DB::select("SELECT es.nombre as escuela, me.nombre as malla, m.nombre as materia, n.nombre AS nivel, 
				mu.idmateria_unica, mu.nombre, mu.iddepartamento, nombre_materia_procedencia,
                numero_creditos_procedencia, anio_aprobacion_materia, porcentaje_similiutd_contenidos, puntaje_asentar, observaciones, mh.pdf, pdf_analisis, mh.personal_id,
                aprobada, mh.materia_id, mh.escuela_id, pe2.apellido1 || ' ' || pe2.apellido2 || ' ' || pe2.nombres n_docente,
                mh.estado
                FROM esq_homo.solicitudes so
                JOIN esq_homo.materias_homologar mh ON mh.solicitudes_id = so.id
                JOIN esq_inscripciones.escuela es ON es.idescuela = mh.escuela_id
                JOIN esq_mallas.malla_escuela me ON me.idmalla = mh.malla_id
                JOIN esq_mallas.malla_materia_nivel mmn ON mmn.idmateria = mh.materia_id
                JOIN esq_mallas.materia m ON m.idmateria = mmn.idmateria
                JOIN esq_mallas.nivel n ON n.idnivel = mmn.idnivel
				JOIN esq_distributivos.materia_unica mu ON mu.idmateria_unica = mmn.idmateria_unica
                FULL JOIN esq_datos_personales.personal pe2 ON pe2.idpersonal = mh.personal_id
                WHERE so.id = $idSolicitud
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
                200
            );
        }
    }

    public function enviarSolicitudCoMaterias(Request $request)
    {
        $json = $request->input("json", null);

        $params_array = json_decode($json, true);

        if (!empty($params_array)) {
            //VALIDAR LOS DATOS
            $validarDatos = Validator::make($params_array, [
                "solicitudes_id" => "required",
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
                $fecha_actualizar = now();

                try {
                    DB::beginTransaction();

                    DB::table("solicitudes")
                        ->where("id", $params_array["solicitudes_id"])
                        ->update(["updated_at" => $fecha_actualizar]);

                    DB::table("estado_solicitudes")
                        ->where(
                            "solicitudes_id",
                            $params_array["solicitudes_id"]
                        )
                        ->update([
                            "estado" => "EN REVISIÓN CM",
                            "fecha_actualizacion" => $fecha_actualizar,
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
}
