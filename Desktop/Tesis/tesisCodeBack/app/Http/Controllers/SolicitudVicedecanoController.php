<?php

namespace App\Http\Controllers;

use App\Mail\NotificacionEmailEstudiantes;
use App\Mail\NotificacionEmailReComision;
use App\Models\Estado_Solicitud;
use App\Models\Solicitud;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use stdClass;

class SolicitudVicedecanoController extends Controller
{
    public function indexCambioCarreraPorVicedecano($idPersonal)
    {
        $id_escuela = $this->obtenerEscuelaVicedecano($idPersonal);

        $solicitudes = DB::select("SELECT so.id AS id_solicitud, pe.nombres, CONCAT(pe.apellido1,' ', pe.apellido2) AS apellidos, esOrigen.nombre AS escuela_origen, esDestino.nombre AS escuela_destino,
        mo.tipo_modalidad AS modalidad, so.created_at AS fc, eso.estado AS estado_solicitud, so.tipo AS tipo
        FROM esq_datos_personales.personal pe
        JOIN esq_homo.solicitudes so ON so.personal_id = pe.idpersonal
        JOIN esq_inscripciones.escuela esOrigen ON so.escuelas_origen_id = esOrigen.idescuela
        JOIN esq_inscripciones.escuela esDestino ON esDestino.idescuela = so.escuelas_destino_id
        JOIN esq_homo.modalidades mo ON mo.id = so.modalidad1_id
		JOIN esq_homo.estado_solicitudes eso ON eso.solicitudes_id = so.id
        WHERE esDestino.idescuela = $id_escuela AND so.tipo = 'CAMBIO CARRERA'
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

    public function indexCambioUniversidadPorVicedecano($idPersonal)
    {
        $id_escuela = $this->obtenerEscuelaVicedecano($idPersonal);

        $solicitudes = DB::select("SELECT so.id AS id_solicitud, u.nombres, CONCAT(u.apellido_p, ' ', u.apellido_m) AS apellidos, u.carrera_origen AS escuela_origen, esDestino.nombre AS escuela_destino, 
        mo.tipo_modalidad AS modalidad, so.created_at AS fc, eso.estado AS estado_solicitud, so.tipo AS tipo 
        FROM esq_homo.solicitudes so 
        JOIN esq_inscripciones.escuela esDestino ON esDestino.idescuela = so.escuelas_destino_id
        JOIN esq_homo.modalidades mo ON mo.id = so.modalidad1_id
		JOIN esq_homo.estado_solicitudes eso ON eso.solicitudes_id = so.id
        JOIN esq_homo.usuarios_foraneos u ON u.id = so.usuarios_foraneos_id
        WHERE esDestino.idescuela = $id_escuela AND so.tipo = 'CAMBIO UNIVERSIDAD'
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

    public function obtenerEscuelaVicedecano($idPersonal)
    {
        $id_escuela = DB::select("SELECT pe.idpersonal, pe.nombres, CONCAT(pe.apellido1, ' ', pe.apellido2) AS apellidos,
        pe.correo_personal_institucional, tpr.idescuela AS escuelas_id,
        split_part(tpr.idescuela, '|', 1) AS idescuela_numero 
        FROM esq_datos_personales.personal pe
        JOIN esq_roles.tbl_personal_rol tpr ON pe.idpersonal = tpr.id_personal
        JOIN esq_roles.tbl_rol tr ON tr.id_rol = tpr.id_rol
        JOIN esq_inscripciones.escuela es ON es.idescuela = CAST(split_part(tpr.idescuela, '|', 1) AS INTEGER)
        WHERE tr.id_rol = 17 AND tpr.idescuela IS NOT NULL AND tpr.estado = 'S'
        AND tpr.id_conexion = 1 AND pe.idpersonal = $idPersonal");

        return $id_escuela[0]->escuelas_id;
    }

    public function detalleSolicitud($idPersonal, $idSolicitud, $tipo)
    {
        if (isset($idPersonal) && isset($idSolicitud) && isset($tipo)) {
            $id_escuela = $this->obtenerEscuelaVicedecano($idPersonal);

            if (intval($tipo) == 1) {
                $detalle_solicitud = DB::select("SELECT so.id AS id_solicitud, so.personal_id, so.malla_origen_id AS malla, pe.nombres, CONCAT(pe.apellido1,' ', pe.apellido2) as apellidos, 
                esOrigen.idescuela AS idescuela, esOrigen.nombre AS escuela_origen, esDestino.nombre AS escuela_destino, mo.tipo_modalidad AS modalidad, so.created_at AS fc, eso.fecha_actualizacion AS fa, 
                eso.estado AS estado_solicitud, eso.observaciones AS observaciones, so.pdf AS documentos, so.pdf_completo AS documento_completo, so.tipo, eso.id AS id_esso, de.nombre AS departamento
                FROM esq_datos_personales.personal pe
                JOIN esq_homo.solicitudes so ON so.personal_id = pe.idpersonal
                JOIN esq_inscripciones.escuela esOrigen ON so.escuelas_origen_id = esOrigen.idescuela
                JOIN esq_inscripciones.escuela esDestino ON esDestino.idescuela = so.escuelas_destino_id
                JOIN esq_homo.modalidades mo ON mo.id = so.modalidad1_id
                JOIN esq_homo.estado_solicitudes eso ON eso.solicitudes_id = so.id
                LEFT JOIN esq_distributivos.departamento de ON de.iddepartamento = so.departamento_destino_id
                WHERE esDestino.idescuela = $id_escuela AND so.tipo = 'CAMBIO CARRERA' AND so.id = $idSolicitud");
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
                WHERE esDestino.idescuela = $id_escuela AND so.tipo = 'CAMBIO UNIVERSIDAD' AND so.id = $idSolicitud");
            }

            $materias_solicitud = DB::select("SELECT mh.materia_id, es.nombre as escuela, me.nombre as malla, m.nombre as materia, n.nombre AS nivel, 
                mh.pdf AS documento_materia
                FROM esq_homo.solicitudes so
                JOIN esq_homo.materias_homologar mh ON mh.solicitudes_id = so.id
                JOIN esq_inscripciones.escuela es ON es.idescuela = mh.escuela_id
                JOIN esq_mallas.malla_escuela me ON me.idmalla = mh.malla_id
                JOIN esq_mallas.malla_materia_nivel mmn ON mmn.idmateria = mh.materia_id
                JOIN esq_mallas.materia m ON m.idmateria = mmn.idmateria
                JOIN esq_mallas.nivel n ON n.idnivel = mmn.idnivel
                WHERE so.id = $idSolicitud
                ORDER BY n.idnivel;");

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

    public function obtenerMateriasAprobadas(Request $request)
    {
        $ip = intval($request->q[0]);
        $ie = intval($request->q[1]);
        $im = intval($request->q[2]);

        $malla = DB::select("SELECT me.nombre as malla
        FROM esq_mallas.malla_escuela me
        INNER JOIN esq_homo.solicitudes s ON s.malla_origen_id = me.idmalla
        WHERE s.personal_id = $ip");

        $datosCrudo = DB::select(
            "SELECT esq_homo.fnc_historial_estudiante(" .
                $ip .
                ", " .
                $ie .
                "," .
                $im .
                ") AS data"
        );

        $array_limpio = [];

        for ($i = 0; $i < sizeof($datosCrudo); $i++) {
            array_push(
                $array_limpio,
                str_replace(['"', "(", ")"], "", $datosCrudo[$i]->data)
            );
        }

        $array_listo = [];

        for ($i = 0; $i < sizeof($array_limpio); $i++) {
            $obM = new stdClass();
            $obM->malla = $malla[0]->malla;
            $obM->materia = explode(",", $array_limpio[$i])[1];
            $obM->idnivel = explode(",", $array_limpio[$i])[2];
            $obM->creditos = explode(",", $array_limpio[$i])[3];
            $obM->nivel = explode(",", $array_limpio[$i])[4];

            $array_listo[] = $obM;
        }

        usort($array_listo, $this->object_sorter("idnivel"));

        return response()->json(
            [
                "code" => 200,
                "status" => "success",
                "historialEstudiante" => $array_listo,
            ],
            200
        );
    }

    public function object_sorter($clave, $orden = null)
    {
        return function ($a, $b) use ($clave, $orden) {
            $result =
                $orden == "DESC"
                    ? strnatcmp($b->$clave, $a->$clave)
                    : strnatcmp($a->$clave, $b->$clave);
            return $result;
        };
    }

    public function obtenerDepartamentos()
    {
        $departamentos = DB::select("SELECT de.iddepartamento, de.nombre
        FROM esq_distributivos.departamento de
        JOIN esq_inscripciones.facultad fa ON fa.idfacultad = de.idfacultad
        WHERE de.habilitado = 'S'
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
        ORDER BY de.nombre");

        return response()->json([
            "code" => 200,
            "status" => "success",
            "departamentos" => $departamentos,
        ]);
    }

    public function asignarDepartamentoSolicitud($idSolicitud, $idDepartamento)
    {
        if (isset($idSolicitud) && isset($idDepartamento)) {
            try {
                DB::beginTransaction();

                DB::table("solicitudes")
                    ->where("id", $idSolicitud)
                    ->update(["departamento_destino_id" => $idDepartamento]);

                DB::commit();

                return response()->json([
                    "code" => 200,
                    "status" => "success",
                ]);
            } catch (\Exception $e) {
                DB::rollBack();

                return response()->json([
                    "code" => 400,
                    "status" => "error",
                    "message" => "Error al guardar los datos",
                    "errores" => $e,
                ]);
            }
        } else {
            return response()->json([
                "code" => 400,
                "status" => "error",
                "message" => "Error al enviar los datos",
            ]);
        }
    }

    public function enviarSolicitudReComision($idSolicitud)
    {
        if (isset($idSolicitud)) {
            try {
                DB::beginTransaction();

                $fecha_hora = now();

                DB::table("solicitudes")
                    ->where("id", $idSolicitud)
                    ->update(["updated_at" => $fecha_hora]);

                DB::table("estado_solicitudes")
                    ->where("solicitudes_id", $idSolicitud)
                    ->update([
                        "fecha_actualizacion" => $fecha_hora,
                        "estado" => "EN REVISIÓN RC",
                    ]);

                DB::commit();

                Mail::to("paul_xd29@hotmail.com")->send(
                    new NotificacionEmailEstudiantes("SA")
                );

                Mail::to("paul_xd29@hotmail.com")->send(
                    new NotificacionEmailReComision("SC")
                );

                return response()->json([
                    "code" => 200,
                    "status" => "success",
                ]);
            } catch (\Exception $e) {
                DB::rollBack();

                return response()->json([
                    "code" => 400,
                    "status" => "success",
                    "message" => "Error al guardar los datos",
                    "errores" => $e,
                ]);
            }
        } else {
            return response()->json([
                "code" => 400,
                "status" => "success",
                "message" => "Error al enviar los datos",
            ]);
        }
    }

    public function updateParaCorrecion(Request $request)
    {
        // Recoger los datos por POST
        $json = $request->input("json", null);
        $params_array = json_decode($json, true);

        if (
            !empty($params_array["materias"]) &&
            !empty($params_array["solicitudes_id"]) &&
            !empty($params_array["observaciones"])
        ) {
            for ($i = 0; $i < count($params_array["materias"]); $i++) {
                $validar_materias = Validator::make(
                    $params_array["materias"][$i],
                    [
                        "materia_id" => "required",
                    ]
                );

                if ($validar_materias->fails()) {
                    return response()->json(
                        [
                            "code" => 400,
                            "status" => "error",
                            "message" => "Error al enviar los datos",
                            "errores" => $validar_materias->errors(),
                        ],
                        400
                    );
                }
            }

            try {
                DB::beginTransaction();

                $fecha_hora = now();

                DB::table("solicitudes")
                    ->where("id", $params_array["solicitudes_id"])
                    ->update([
                        "updated_at" => $fecha_hora,
                    ]);

                DB::table("estado_solicitudes")
                    ->where("solicitudes_id", $params_array["solicitudes_id"])
                    ->update([
                        "estado" => "CORRECIONES",
                        "fecha_actualizacion" => $fecha_hora,
                        "observaciones" => $params_array["observaciones"],
                    ]);

                foreach ($params_array["materias"] as $clave => $valor) {
                    if ($valor["materia_id"] === -1) {
                        $pdf_general_anterior = DB::table("solicitudes")
                            ->where("id", $params_array["solicitudes_id"])
                            ->value("pdf_completo");

                        DB::table("solicitudes")
                            ->where("id", $params_array["solicitudes_id"])
                            ->update([
                                "pdf_completo" => null,
                            ]);
                        unset($params_array["materias"][$clave]);
                        break;
                    }
                }

                $nombres_pdf_anteriores = [];

                foreach ($params_array["materias"] as $clave => $valor) {
                    $valor_anterior = DB::table("materias_homologar")
                        ->where(
                            "solicitudes_id",
                            $params_array["solicitudes_id"]
                        )
                        ->where("materia_id", $valor["materia_id"])
                        ->value("pdf");

                    array_push($nombres_pdf_anteriores, $valor_anterior);

                    DB::table("materias_homologar")
                        ->where(
                            "solicitudes_id",
                            $params_array["solicitudes_id"]
                        )
                        ->where("materia_id", $valor["materia_id"])
                        ->update(["pdf" => null]);
                }

                DB::commit();

                if (isset($pdf_general_anterior)) {
                    if (
                        Storage::disk("ftpHomologacion")->exists(
                            $pdf_general_anterior
                        )
                    ) {
                        Storage::disk("ftpHomologacion")->delete(
                            $pdf_general_anterior
                        );
                    }
                }

                foreach ($nombres_pdf_anteriores as $nombre) {
                    if (Storage::disk("ftpHomologacion")->exists($nombre)) {
                        Storage::disk("ftpHomologacion")->delete($nombre);
                    }
                }

                Mail::to("paul_xd29@hotmail.com")->send(
                    new NotificacionEmailEstudiantes("SC")
                );

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
                        "message" => "Error al actualizar los datos",
                        "errores" => $e,
                    ],
                    400
                );
            }
        } else {
            return response()->json(
                [
                    "code" => 400,
                    "message" => "Error al enviar los datos",
                ],
                400
            );
        }
    }

    public function updateRechazoSolicitud(Request $request, $id)
    {
        // Recoger los datos por POST
        $json = $request->input("json", null);
        $params_array = json_decode($json, true);

        // DATOS PARA RESPUESTA
        $data = [
            "code" => 400,
            "status" => "error",
            "error_text" => "Datos enviados incorrectamente",
        ];

        if (!empty($params_array)) {
            $validate = Validator::make($params_array, [
                "observaciones" => "required",
            ]);

            if ($validate->fails()) {
                $data["errors"] = $validate->errors();
                return response()->json($data, $data["code"]);
            }

            $solicitud = Solicitud::where("id", $id)->first();
            $estado_solicitud = Estado_Solicitud::where(
                "solicitudes_id",
                $id
            )->first();

            if (!empty($solicitud) && is_object($solicitud)) {
                // Actualizar el registro en concreto
                $solicitud->updated_at = now();
                $solicitud->save();

                $estado_solicitud->estado = "RECHAZADA";
                $estado_solicitud->fecha_actualizacion = $solicitud->updated_at;
                $estado_solicitud->observaciones =
                    $params_array["observaciones"];
                $estado_solicitud->save();

                Mail::to("paul_xd29@hotmail.com")->send(
                    new NotificacionEmailEstudiantes("SR")
                );

                // Devolver respuesta
                $data = [
                    "code" => 200,
                    "status" => "success",
                ];
            }
        }
        return response()->json($data, $data["code"]);
    }
}
