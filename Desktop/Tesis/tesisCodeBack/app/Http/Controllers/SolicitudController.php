<?php

namespace App\Http\Controllers;

use App\Models\Estado_Solicitud;
use App\Models\Solicitud;

use Carbon\Carbon;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\File;

use Illuminate\Support\Facades\Mail;
use App\Mail\NotificacionEmailEstudiantes;
use App\Mail\NotificacionEmailVicedecanos;
use App\Models\Materia_Homologar;

class SolicitudController extends Controller
{
    public function indexPorEstudiante($idPersonal)
    {
        $solicitudes = DB::select("SELECT so.id AS id_solicitud, esOrigen.nombre AS escuela_origen, esDestino.nombre AS escuela_destino, mo.tipo_modalidad AS modalidad, 
        so.tipo AS tipo, so.pdf AS documento, so.pdf_completo AS documento_completo, so.created_at AS fc, so.updated_at AS fa, es.estado, es.observaciones
        FROM esq_homo.solicitudes so
        JOIN esq_inscripciones.escuela esOrigen ON so.escuelas_origen_id = esOrigen.idescuela
        JOIN esq_inscripciones.escuela esDestino ON esDestino.idescuela = so.escuelas_destino_id
        JOIN esq_homo.modalidades mo ON mo.id = so.modalidad1_id
        JOIN esq_homo.estado_solicitudes es ON es.solicitudes_id = so.id
        WHERE so.personal_id = $idPersonal
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

    public function indexPorEstudianteExterno($idPersona)
    {
        $solicitudes = DB::select("SELECT so.id AS id_solicitud, u.carrera_origen AS escuela_origen, esDestino.nombre AS escuela_destino, mo.tipo_modalidad AS modalidad, 
        so.tipo AS tipo, so.pdf AS documento, so.pdf_completo AS documento_completo, so.created_at AS fc, so.updated_at AS fa, es.estado, es.observaciones
        FROM esq_homo.solicitudes so
		JOIN esq_homo.usuarios_foraneos u ON u.id = so.usuarios_foraneos_id
        JOIN esq_homo.modalidades mo ON mo.id = so.modalidad1_id
		JOIN esq_inscripciones.escuela esDestino ON esDestino.idescuela = so.escuelas_destino_id
        JOIN esq_homo.estado_solicitudes es ON es.solicitudes_id = so.id
        WHERE so.usuarios_foraneos_id = $idPersona
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

    public function obtenerUltimaSolicitud($idPersona, $tipo)
    {
        if (isset($idPersona) && isset($tipo)) {
            // OBTENER LA ULTIMA SOLICITUD SIEMPRE Y CUANDO NO ESTE RECHAZADA O ANULADA

            if (intval($tipo) === 1) {
                $ultima_solicitud = DB::table("solicitudes AS so")
                    ->join(
                        "estado_solicitudes AS es",
                        "so.id",
                        "es.solicitudes_id"
                    )
                    ->where("so.personal_id", $idPersona)
                    ->whereNotIn("es.estado", ["RECHAZADA", "ANULADA"])
                    ->orderBy("so.id", "desc")
                    ->first(["so.id", "es.estado"]);
            } elseif (intval($tipo) === 2) {
                $ultima_solicitud = DB::table("solicitudes AS so")
                    ->join(
                        "estado_solicitudes AS es",
                        "so.id",
                        "es.solicitudes_id"
                    )
                    ->where("so.usuarios_foraneos_id", $idPersona)
                    ->whereNotIn("es.estado", ["RECHAZADA", "ANULADA"])
                    ->orderBy("so.id", "desc")
                    ->first(["so.id", "es.estado"]);
            }

            if ($ultima_solicitud != null) {
                return response()->json(
                    [
                        "code" => 200,
                        "status" => "success",
                        "u_solicitud" => $ultima_solicitud,
                    ],
                    200
                );
            } else {
                return response()->json(
                    [
                        "code" => 200,
                        "status" => "empty",
                    ],
                    200
                );
            }
        }
    }

    public function obtenerUniversidades()
    {
        $universidades = DB::select("SELECT iduniversidad, nombre
        FROM esq_datos_personales.p_universidad
        ORDER BY nombre");

        return response()->json(
            [
                "code" => 200,
                "status" => "success",
                "universidades" => $universidades,
            ],
            200
        );
    }

    public function obtenerEscuelas($id = null)
    {
        if ($id) {
            $escuelas = DB::select(
                "SELECT es.idescuela, es.nombre
                FROM esq_datos_personales.personal pe 
                INNER JOIN esq_mallas.malla_estudiante_escuela mee ON mee.idpersonal = pe.idpersonal
                INNER JOIN esq_inscripciones.escuela es ON es.idescuela = mee.idescuela
                INNER JOIN esq_mallas.malla_escuela me ON me.idmalla = mee.idmalla
                WHERE pe.idpersonal = $id AND es.habilitado = 'S'
                AND NOT es.nombre LIKE 'MAESTRÍA%'
                AND NOT es.nombre LIKE 'NIV.%'
                AND NOT es.nombre LIKE 'GENERAL'
                AND NOT es.nombre LIKE 'NBU%' AND NOT es.nombre LIKE '%(INTERNADO)%'
                AND NOT es.nombre LIKE 'INGENIERIA BASICA'
				AND NOT es.nombre LIKE 'ESPECIALIZAC%'
                GROUP BY es.idescuela, es.nombre"
            );
        } else {
            $escuelas = DB::select(
                "SELECT es.idescuela, es.nombre
                FROM esq_inscripciones.escuela es 
                JOIN esq_periodos_academicos.periodo_escuela pe ON es.idescuela = pe.idescuela
                WHERE pe.idperido = (SELECT pa.idperiodo 
                    FROM esq_periodos_academicos.periodo_academico pa
                    WHERE pa.idtipo_periodo = 1
                    ORDER BY pa.idperiodo DESC
                    LIMIT 1)
                ORDER BY es.nombre"
            );
        }

        return response()->json(
            [
                "code" => 200,
                "status" => "success",
                "escuelas" => $escuelas,
            ],
            200
        );
    }

    public function obtenerMallas(Request $request)
    {
        $ip = intval($request->q[0]);
        $ie = intval($request->q[1]);
        $mallas = DB::select("SELECT es.idescuela, es.nombre AS escuela, me.idmalla, me.nombre AS malla
        FROM esq_datos_personales.personal pe 
        INNER JOIN esq_mallas.malla_estudiante_escuela mee ON mee.idpersonal = pe.idpersonal
        INNER JOIN esq_inscripciones.escuela es ON es.idescuela = mee.idescuela
        INNER JOIN esq_mallas.malla_escuela me ON me.idmalla = mee.idmalla
        AND NOT es.nombre LIKE 'MAESTRÍA%'
        AND NOT es.nombre LIKE 'NIV.%'
        WHERE pe.idpersonal = $ip AND me.idescuela = $ie");

        return response()->json(
            [
                "code" => 200,
                "status" => "success",
                "mallas" => $mallas,
            ],
            200
        );
    }

    public function obtenerMateriasEscuelaMalla($idEscuela = 0)
    {
        $consultaMallaActual = DB::select("SELECT me.idmalla
        FROM esq_mallas.malla_escuela me JOIN esq_inscripciones.escuela es ON es.idescuela = me.idescuela
        WHERE es.idescuela = $idEscuela
        ORDER BY me.idmalla DESC
        LIMIT 1");

        $consultaMaterias = DB::select("SELECT me.idmalla as malla_id, es.idescuela as escuela_id, ma.idmateria as materia_id, ma.nombre as materia
        FROM esq_inscripciones.escuela es 
        JOIN esq_mallas.malla_materia_nivel mmn ON es.idescuela = mmn.idescuela
        JOIN esq_mallas.malla_escuela me ON me.idmalla = mmn.idmalla
        JOIN esq_mallas.materia ma ON ma.idmateria = mmn.idmateria
        JOIN esq_mallas.nivel n ON n.idnivel = mmn.idnivel
        AND NOT es.nombre LIKE 'MAESTRÍA%'
        AND NOT es.nombre LIKE 'NIV.%'
        WHERE mmn.idescuela = $idEscuela AND mmn.idmalla = {$consultaMallaActual[0]->idmalla}
        ORDER BY n.idnivel");

        if ($idEscuela != 0) {
            return response()->json([
                "code" => 200,
                "status" => "success",
                "materias" => $consultaMaterias,
            ]);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // RECOGER LOS DATOS POR POST
        $json = $request->input("json", null);
        $params_array = json_decode($json, true);

        if (
            !empty($params_array["solicitud"]) &&
            !empty($params_array["materias_homologar"])
        ) {
            // VALIDAS LOS DATOS DE LA SOLICITUD
            $validar_datos = Validator::make($params_array["solicitud"], [
                "escuelas_destino_id" => "required",
                "tipo" => "required",
                "modalidad1_id" => "required",
            ]);

            if ($validar_datos->fails()) {
                return response()->json(
                    [
                        "code" => 400,
                        "status" => "error",
                        "message" => "Error al enviar los datos",
                        "errores" => $validar_datos->errors(),
                    ],
                    200
                );
            }

            for (
                $i = 0;
                $i < count($params_array["materias_homologar"]);
                $i++
            ) {
                $validar_materias = Validator::make(
                    $params_array["materias_homologar"][$i],
                    [
                        "escuela_id" => "required",
                        "malla_id" => "required",
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
                        200
                    );
                }
            }

            try {
                DB::beginTransaction();

                // CREANDO LA SOLICITUD
                $solicitud = new Solicitud();

                // GUARDANDO LOS DATOS DE LA SOLICITUD
                $solicitud->personal_id =
                    $params_array["solicitud"]["personal_id"];
                $solicitud->modalidad1_id =
                    $params_array["solicitud"]["modalidad1_id"];
                $solicitud->usuarios_foraneos_id =
                    $params_array["solicitud"]["usuarios_foraneos_id"];
                $solicitud->universidades_id =
                    $params_array["solicitud"]["universidades_id"];
                $solicitud->escuelas_origen_id =
                    $params_array["solicitud"]["escuelas_origen_id"];
                $solicitud->malla_origen_id =
                    $params_array["solicitud"]["malla_origen_id"];
                $solicitud->escuelas_destino_id =
                    $params_array["solicitud"]["escuelas_destino_id"];
                $solicitud->departamento_destino_id = null;
                $solicitud->tipo = $params_array["solicitud"]["tipo"];

                // ASIGNAR EL NOMBRE DEL PDF
                $nombre_pdf = time() . ".pdf";
                $solicitud->pdf = $nombre_pdf;
                $solicitud->save();

                // CREANDO EL ESTADO DE LA SOLICITUD
                $estado_solicitud = new Estado_Solicitud();

                // GUARDANDO LOS DATOS DEL ESTADO DE LA SOLICITUD
                $estado_solicitud->solicitudes_id = $solicitud->id;
                $estado_solicitud->estado = "GENERADA";
                $estado_solicitud->observaciones = null;
                $estado_solicitud->fecha_actualizacion = $solicitud->created_at;
                $estado_solicitud->save();

                // GUARDANDO LAS MATERIAS PARA HOMOLOGACION
                for (
                    $i = 0;
                    $i < count($params_array["materias_homologar"]);
                    $i++
                ) {
                    // CREANDO LA MATERIA A HOMOLOGAR
                    $materia_homologar = new Materia_Homologar();

                    // GUARDANDO LOS DATOS DE LA MATERIA A HOMOLOGAR
                    $materia_homologar->solicitudes_id = $solicitud->id;
                    $materia_homologar->escuela_id =
                        $params_array["materias_homologar"][$i]["escuela_id"];
                    $materia_homologar->malla_id =
                        $params_array["materias_homologar"][$i]["malla_id"];
                    $materia_homologar->materia_id =
                        $params_array["materias_homologar"][$i]["materia_id"];
                    $materia_homologar->save();
                }

                try {
                    $this->creaPDFSolicitud(
                        $solicitud->id,
                        $solicitud->personal_id,
                        $solicitud->escuelas_origen_id,
                        $solicitud->tipo,
                        $nombre_pdf
                    );
                } catch (\Exception $ex) {
                    DB::rollBack();

                    if (Storage::disk("ftpHomologacion")->exists($nombre_pdf)) {
                        Storage::disk("ftpHomologacion")->delete($nombre_pdf);
                    }

                    return response()->json(
                        [
                            "code" => 400,
                            "status" => "error",
                            "message" => "Error al generar la solicitud",
                            "errores" => $ex,
                        ],
                        200
                    );
                }

                DB::commit();

                $tipo = $solicitud->tipo;

                $abreviatura = $tipo == "CAMBIO CARRERA" ? "SGCC" : "SGCU";

                Mail::to("paul_xd29@hotmail.com")->send(
                    new NotificacionEmailEstudiantes($abreviatura)
                );

                return response()->json(
                    [
                        "code" => 200,
                        "status" => "success",
                        "solicitud" => $solicitud,
                        "estado_solicitud" => $estado_solicitud,
                        "materias_homologar" =>
                            $params_array["materias_homologar"],
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
                    200
                );
            }
        } else {
            return response()->json(
                [
                    "code" => 400,
                    "status" => "error",
                    "message" => "Error al enviar los datos",
                ],
                200
            );
        }
    }

    public function creaPDFSolicitud(
        $idSolicitud,
        $idPersona,
        $idEscuela,
        $tipoSolicitud,
        $nombrePdfSolicitud
    ) {
        if ($tipoSolicitud === "CAMBIO CARRERA") {
            $datos_carrera = DB::select("SELECT CONCAT(pe.apellido1,' ', pe.apellido2) AS apellidos_e, pe.nombres AS nombres_e, pe.cedula AS cedula_e,
            es1.nombre AS escuelao_e, fa.nombre AS facultad_e, es2.nombre AS escuelad_e, mo.tipo_modalidad AS modalidad_e, 'UNIVERSIDAD TÉCNICA DE MANABÍ' AS universidad_e
            FROM esq_datos_personales.personal pe 
            JOIN esq_homo.solicitudes so ON pe.idpersonal = so.personal_id
            JOIN esq_inscripciones.escuela es1 ON es1.idescuela = so.escuelas_origen_id
            JOIN esq_inscripciones.escuela es2 ON es2.idescuela = so.escuelas_destino_id
            JOIN esq_inscripciones.facultad fa ON fa.idfacultad = es1.idfacultad
            JOIN esq_homo.modalidades mo ON so.modalidad1_id = mo.id
            WHERE so.id = $idSolicitud");

            $nivel = DB::select("SELECT ni.nombre AS nivel
            FROM esq_inscripciones.inscripcion i JOIN esq_mallas.nivel ni ON ni.idnivel = i.idnivel
            WHERE i.idpersonal = $idPersona AND i.idescuela = $idEscuela
            ORDER BY i.idnivel DESC");

            $nivel = $nivel[0]->nivel;
        } elseif ($tipoSolicitud === "CAMBIO UNIVERSIDAD") {
            $datos_carrera = DB::select("SELECT CONCAT(uf.apellido_p, ' ', uf.apellido_m) AS apellidos_e, uf.nombres AS nombres_e, uf.cedula AS cedula_e,
            uf.carrera_origen AS escuelao_e, uf.facultad_origen AS facultad_e, es2.nombre AS escuelad_e, mo.tipo_modalidad AS modalidad_e,
            pu.nombre AS universidad_e, uf.nivel_origen AS nivel_e
            FROM esq_homo.usuarios_foraneos uf
            JOIN esq_homo.solicitudes so ON so.usuarios_foraneos_id = uf.id
            JOIN esq_inscripciones.escuela es2 ON es2.idescuela = so.escuelas_destino_id
            JOIN esq_homo.modalidades mo ON mo.id = so.modalidad1_id
            JOIN esq_datos_personales.p_universidad pu ON pu.iduniversidad = so.universidades_id
            WHERE so.id = $idSolicitud");

            $nivel = $datos_carrera[0]->nivel_e;
        }

        $perido_academico = DB::table(
            "esq_periodos_academicos.periodo_academico"
        )
            ->where("idtipo_periodo", 1)
            ->select("nombre")
            ->orderBy("idperiodo", "desc")
            ->first();

        $materias_solicitud = DB::select("SELECT es.nombre as escuela, me.nombre as malla, m.nombre as materia, n.nombre AS nivel
        FROM esq_homo.solicitudes so
        JOIN esq_homo.materias_homologar mh ON mh.solicitudes_id = so.id
        JOIN esq_inscripciones.escuela es ON es.idescuela = mh.escuela_id
        JOIN esq_mallas.malla_escuela me ON me.idmalla = mh.malla_id
        JOIN esq_mallas.malla_materia_nivel mmn ON mmn.idmateria = mh.materia_id
        JOIN esq_mallas.materia m ON m.idmateria = mmn.idmateria
        JOIN esq_mallas.nivel n ON n.idnivel = mmn.idnivel
        WHERE so.id = $idSolicitud
        ORDER BY n.idnivel");

        // CREANDO EL PDF DE LA SOLICITUD CON DOMPDF
        $fecha_actual = Carbon::now()->format("d/m/Y");
        $pdf = app("dompdf.wrapper");
        $pdf->loadView(
            "SolicitudPdf.solicitudHomologacion",
            compact(
                "fecha_actual",
                "datos_carrera",
                "nivel",
                "materias_solicitud",
                "perido_academico"
            )
        )->setPaper("a4");
        $pdf_solicitud_creado = $pdf->output();

        // GUARDANDO EL PDF CREADO
        Storage::disk("ftpHomologacion")->put(
            $nombrePdfSolicitud,
            $pdf_solicitud_creado
        );
    }

    public function update(Request $request)
    {
        // RECOGER LOS DATOS POR POST
        $json = $request->input("json", null);
        $params_array = json_decode($json, true);

        if (
            !empty($params_array["solicitud"]) &&
            !empty($params_array["materias_homologar"])
        ) {
            // VALIDAS LOS DATOS DE LA SOLICITUD
            $validar_datos = Validator::make($params_array["solicitud"], [
                "id_solicitud" => "required",
                "escuelas_destino_id" => "required",
                "tipo" => "required",
                "modalidad1_id" => "required",
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
            }

            for (
                $i = 0;
                $i < count($params_array["materias_homologar"]);
                $i++
            ) {
                $validar_materias = Validator::make(
                    $params_array["materias_homologar"][$i],
                    [
                        "escuela_id" => "required",
                        "malla_id" => "required",
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

                // ASIGNAR EL NOMBRE DEL PDF
                $nombre_pdf = time() . ".pdf";
                $fecha_hora = now();

                // CONSULTANDO EL ANTERIOR ARCHIVO PDF GUARDADO
                $pdf_anterior = DB::table("solicitudes")
                    ->where("id", $params_array["solicitud"]["id_solicitud"])
                    ->value("pdf");

                // ACTUALIZANDO LOS DATOS DE LA SOLICITUD

                DB::table("solicitudes")
                    ->where("id", $params_array["solicitud"]["id_solicitud"])
                    ->update([
                        "personal_id" =>
                            $params_array["solicitud"]["personal_id"],
                        "modalidad1_id" =>
                            $params_array["solicitud"]["modalidad1_id"],
                        "usuarios_foraneos_id" =>
                            $params_array["solicitud"]["usuarios_foraneos_id"],
                        "universidades_id" =>
                            $params_array["solicitud"]["universidades_id"],
                        "escuelas_origen_id" =>
                            $params_array["solicitud"]["escuelas_origen_id"],
                        "malla_origen_id" =>
                            $params_array["solicitud"]["malla_origen_id"],
                        "escuelas_destino_id" =>
                            $params_array["solicitud"]["escuelas_destino_id"],
                        "departamento_destino_id" => null,
                        "tipo" => $params_array["solicitud"]["tipo"],
                        "pdf" => $nombre_pdf,
                        "updated_at" => $fecha_hora,
                    ]);

                // ACTUALIZANDO LOS DATOS DEL ESTADO DE LA SOLICITUD
                DB::table("estado_solicitudes")
                    ->where(
                        "solicitudes_id",
                        $params_array["solicitud"]["id_solicitud"]
                    )
                    ->update([
                        "estado" => "GENERADA",
                        "observaciones" => null,
                        "fecha_actualizacion" => $fecha_hora,
                    ]);

                // BORRANDO LAS MATERIAS ANTES GUARDADAS
                DB::table("materias_homologar")
                    ->where(
                        "solicitudes_id",
                        $params_array["solicitud"]["id_solicitud"]
                    )
                    ->delete();

                // ACTUALIZANDO LAS MATERIAS PARA HOMOLOGACION
                for (
                    $i = 0;
                    $i < count($params_array["materias_homologar"]);
                    $i++
                ) {
                    // CREANDO LA MATERIA A HOMOLOGAR
                    $materia_homologar = new Materia_Homologar();

                    // GUARDANDO LOS DATOS DE LA(S) MATERIA(S) A HOMOLOGAR
                    $materia_homologar->solicitudes_id =
                        $params_array["solicitud"]["id_solicitud"];
                    $materia_homologar->escuela_id =
                        $params_array["materias_homologar"][$i]["escuela_id"];
                    $materia_homologar->malla_id =
                        $params_array["materias_homologar"][$i]["malla_id"];
                    $materia_homologar->materia_id =
                        $params_array["materias_homologar"][$i]["materia_id"];
                    $materia_homologar->save();
                }

                try {
                    $this->creaPDFSolicitud(
                        $params_array["solicitud"]["solicitudes_id"],
                        $params_array["solicitud"]["personal_id"],
                        $params_array["solicitud"]["escuelas_origen_id"],
                        $params_array["solicitud"]["tipo"],
                        $nombre_pdf
                    );
                } catch (\Exception $ex) {
                    DB::rollBack();

                    if (Storage::disk("ftpHomologacion")->exists($nombre_pdf)) {
                        Storage::disk("ftpHomologacion")->delete($nombre_pdf);
                    }

                    return response()->json(
                        [
                            "code" => 400,
                            "status" => "error",
                            "message" => "Error al generar el archivo PDF",
                            "errores" => $ex,
                        ],
                        400
                    );
                }

                DB::commit();

                if (Storage::disk("ftpHomologacion")->exists($pdf_anterior)) {
                    Storage::disk("ftpHomologacion")->delete($pdf_anterior);
                }

                return response()->json(
                    [
                        "code" => 200,
                        "status" => "success",
                        "solicitud" => $params_array["solicitud"],
                        "materias_homologar" =>
                            $params_array["materias_homologar"],
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

    public function obtenerMateriasSolicitud($idPersona, $tipo)
    {
        if (!isset($idPersona) && !isset($tipo)) {
            return response()->json(
                [
                    "code" => 400,
                    "status" => "error",
                    "message" => "Error al enviar los datos",
                ],
                400
            );
        }

        if (intval($tipo) == 1) {
            $id_soliciutd = DB::table("solicitudes AS so")
                ->join("estado_solicitudes AS es", "so.id", "es.solicitudes_id")
                ->where("personal_id", intval($idPersona))
                ->where("es.estado", "GENERADA")
                ->orderByDesc("so.id")
                ->first("so.id");
        } elseif (intval($tipo) == 2) {
            $id_soliciutd = DB::table("solicitudes AS so")
                ->join("estado_solicitudes AS es", "so.id", "es.solicitudes_id")
                ->where("usuarios_foraneos_id", intval($idPersona))
                ->where("es.estado", "GENERADA")
                ->orderByDesc("so.id")
                ->first("so.id");
        }

        if (!isset($id_soliciutd)) {
            return response()->json(
                [
                    "code" => 200,
                    "status" => "empty",
                ],
                200
            );
        }

        $materias = DB::select("SELECT m.idmateria, m.nombre AS materia, n.nombre AS nivel, so.id AS id_solicitud, esd.estado
		FROM esq_homo.solicitudes so
		JOIN esq_homo.materias_homologar mh ON so.id = mh.solicitudes_id 
        JOIN esq_inscripciones.escuela es ON es.idescuela = mh.escuela_id
        JOIN esq_mallas.malla_escuela me ON me.idmalla = mh.malla_id
        JOIN esq_mallas.malla_materia_nivel mmn ON mmn.idmateria = mh.materia_id
        JOIN esq_mallas.materia m ON m.idmateria = mmn.idmateria
        JOIN esq_mallas.nivel n ON n.idnivel = mmn.idnivel
        JOIN esq_homo.estado_solicitudes esd ON so.id = esd.solicitudes_id
        WHERE so.id = $id_soliciutd->id
        ORDER BY n.idnivel, materia");

        if (count($materias)) {
            return response()->json(
                ["code" => 200, "status" => "success", "materias" => $materias],
                200
            );
        } else {
            return response()->json(
                [
                    "code" => 400,
                    "status" => "error",
                    "message" => "Error al consultar los datos",
                ],
                400
            );
        }
    }

    public function cargarPdfMateriasSolicitud(Request $request)
    {
        // RECOGER LOS DATOS POR POST
        $solicitudes_id = json_decode($request->input("solicitudes_id", null));

        $archivos = $request->allFiles();

        // VERIFICAR SI EXISTEN LOS ARCHIVOS
        foreach ($archivos as $archivo) {
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
        }

        // VERIFICAR SI EXISTEN LOS DATOS
        if (!isset($solicitudes_id)) {
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

        try {
            DB::beginTransaction();

            // FECHA Y HORA PARA ACTUALIZAR LOS CAMPOS CORRESPONDIENTES
            $fecha_hora = now();
            $nombres_pdf = [];
            $nombre_pdf_completo = time() . ".pdf";

            // ACTUALIZAR LA FECHA-HORA DE ACTULIZACION DE LAS SOLICITUDES
            DB::table("solicitudes")
                ->where("id", $solicitudes_id)
                ->update(["updated_at" => $fecha_hora]);

            DB::table("estado_solicitudes")
                ->where("solicitudes_id", $solicitudes_id)
                ->update([
                    "estado" => "PENDIENTE",
                    "fecha_actualizacion" => $fecha_hora,
                ]);

            DB::table("solicitudes")
                ->where("id", $solicitudes_id)
                ->update(["pdf_completo" => $nombre_pdf_completo]);

            foreach ($archivos as $key => $archivo) {
                if ($key == "pdf_completo") {
                    // GUARDANDO EL PDF EN EL SERVIDOR FTP
                    Storage::disk("ftpHomologacion")->put(
                        $nombre_pdf_completo,
                        File::get($archivo)
                    );
                } else {
                    sleep(1);
                    // NOMBRE DEL ARHCIVO PDF
                    $nombre_pdf = time() . ".pdf";
                    sleep(1);

                    DB::table("materias_homologar")
                        ->where("solicitudes_id", $solicitudes_id)
                        ->where("materia_id", $key)
                        ->update(["pdf" => $nombre_pdf]);

                    $exito = false;

                    array_push($nombres_pdf, $nombre_pdf);

                    try {
                        // GUARDANDO EL PDF EN EL SERVIDOR FTP
                        Storage::disk("ftpHomologacion")->put(
                            $nombre_pdf,
                            File::get($archivo)
                        );

                        $exito = true;
                    } catch (\Exception $ex) {
                        $exito = false;

                        foreach ($nombres_pdf as $nombre) {
                            if (
                                Storage::disk("ftpHomologacion")->exists(
                                    $nombre
                                )
                            ) {
                                Storage::disk("ftpHomologacion")->delete(
                                    $nombre
                                );
                            }
                        }

                        DB::rollBack();

                        // DEVOLVER DATOS
                        return response()->json(
                            [
                                "code" => 400,
                                "status" => "error",
                                "message" => "Error al guardar el/los PDF",
                                "error" => $ex,
                            ],
                            200
                        );
                    }
                }
            }

            if ($exito) {
                DB::commit();

                $tipo = DB::table("solicitudes")
                    ->where("id", $solicitudes_id)
                    ->value("tipo");

                $abreviatura = $tipo == "CAMBIO CARRERA" ? "SECC" : "SECU";

                Mail::to("paul_xd29@hotmail.com")->send(
                    new NotificacionEmailEstudiantes($abreviatura)
                );

                Mail::to("paul_xd29@hotmail.com")->send(
                    new NotificacionEmailVicedecanos("NS")
                );

                // DEVOLVER DATOS
                return response()->json(
                    [
                        "code" => 200,
                        "status" => "success",
                    ],
                    200
                );
            }
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(
                [
                    "code" => 400,
                    "status" => "error",
                    "message" => "Error al guardar los datos",
                    "errores" => $e,
                ],
                200
            );
        }
    }

    public function obtenerSolicitudMateriasCorrecion($idPersona, $tipo)
    {
        if (!isset($idPersona) && !isset($tipo)) {
            return response()->json(
                [
                    "code" => 400,
                    "status" => "error",
                    "message" => "Error al enviar los datos",
                ],
                400
            );
        }

        if (intval($tipo) == 1) {
            $solicitud = DB::table("solicitudes AS so")
                ->join("estado_solicitudes AS es", "so.id", "es.solicitudes_id")
                ->where("personal_id", intval($idPersona))
                ->where("es.estado", "CORRECIONES")
                ->orderByDesc("so.id")
                ->first(["so.id", "so.pdf_completo"]);
        } elseif (intval($tipo) == 2) {
            $solicitud = DB::table("solicitudes AS so")
                ->join("estado_solicitudes AS es", "so.id", "es.solicitudes_id")
                ->where("usuarios_foraneos_id", intval($idPersona))
                ->where("es.estado", "CORRECIONES")
                ->orderByDesc("so.id")
                ->first(["so.id", "so.pdf_completo"]);
        }

        $materias = DB::select("SELECT m.idmateria, m.nombre AS materia, n.nombre AS nivel, so.id AS id_solicitud, esd.estado, mh.pdf
		FROM esq_homo.solicitudes so
		JOIN esq_homo.materias_homologar mh ON so.id = mh.solicitudes_id 
        JOIN esq_inscripciones.escuela es ON es.idescuela = mh.escuela_id
        JOIN esq_mallas.malla_escuela me ON me.idmalla = mh.malla_id
        JOIN esq_mallas.malla_materia_nivel mmn ON mmn.idmateria = mh.materia_id
        JOIN esq_mallas.materia m ON m.idmateria = mmn.idmateria
        JOIN esq_mallas.nivel n ON n.idnivel = mmn.idnivel
        JOIN esq_homo.estado_solicitudes esd ON so.id = esd.solicitudes_id
        WHERE so.id = $solicitud->id AND mh.pdf IS NULL
        ORDER BY n.idnivel, materia");

        if (is_object($solicitud)) {
            return response()->json(
                [
                    "code" => 200,
                    "status" => "success",
                    "solicitud" => $solicitud,
                    "materias" => $materias,
                ],
                200
            );
        } else {
            return response()->json(
                [
                    "code" => 400,
                    "status" => "error",
                    "message" => "Error al consultar los datos",
                ],
                400
            );
        }
    }

    public function cargarPdfCorreciones(Request $request)
    {
        // RECOGER LOS DATOS POR POST
        $solicitudes_id = json_decode($request->input("solicitudes_id", null));

        $archivos = $request->allFiles();

        // VERIFICAR SI EXISTEN LOS ARCHIVOS
        foreach ($archivos as $archivo) {
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
        }

        // VERIFICAR SI EXISTEN LOS DATOS
        if (!isset($solicitudes_id)) {
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

        try {
            DB::beginTransaction();

            // FECHA Y HORA PARA ACTUALIZAR LOS CAMPOS CORRESPONDIENTES
            $fecha_hora = now();
            $nombres_pdf = [];
            $nombre_pdf_completo = time() . ".pdf";
            $exito = true;

            // ACTUALIZAR LA FECHA-HORA DE ACTULIZACION DE LAS SOLICITUDES
            DB::table("solicitudes")
                ->where("id", $solicitudes_id)
                ->update(["updated_at" => $fecha_hora]);

            DB::table("estado_solicitudes")
                ->where("solicitudes_id", $solicitudes_id)
                ->update([
                    "estado" => "PENDIENTE",
                    "fecha_actualizacion" => $fecha_hora,
                    "observaciones" => null,
                ]);

            foreach ($archivos as $key => $archivo) {
                if ($key == "pdf_completo") {
                    // GUARDANDO EL PDF EN EL SERVIDOR FTP
                    DB::table("solicitudes")
                        ->where("id", $solicitudes_id)
                        ->update(["pdf_completo" => $nombre_pdf_completo]);

                    Storage::disk("ftpHomologacion")->put(
                        $nombre_pdf_completo,
                        File::get($archivo)
                    );
                } elseif ($key != "pdf_completo") {
                    sleep(1);
                    // NOMBRE DEL ARHCIVO PDF
                    $nombre_pdf = time() . ".pdf";
                    sleep(1);

                    DB::table("materias_homologar")
                        ->where("solicitudes_id", $solicitudes_id)
                        ->where("materia_id", $key)
                        ->update(["pdf" => $nombre_pdf]);

                    $exito = false;

                    array_push($nombres_pdf, $nombre_pdf);

                    try {
                        // GUARDANDO EL PDF EN EL SERVIDOR FTP
                        Storage::disk("ftpHomologacion")->put(
                            $nombre_pdf,
                            File::get($archivo)
                        );

                        $exito = true;
                    } catch (\Exception $ex) {
                        $exito = false;

                        foreach ($nombres_pdf as $nombre) {
                            if (
                                Storage::disk("ftpHomologacion")->exists(
                                    $nombre
                                )
                            ) {
                                Storage::disk("ftpHomologacion")->delete(
                                    $nombre
                                );
                            }
                        }

                        DB::rollBack();

                        // DEVOLVER DATOS
                        return response()->json(
                            [
                                "code" => 400,
                                "status" => "error",
                                "message" => "Error al guardar el/los PDF",
                                "error" => $ex,
                            ],
                            200
                        );
                    }
                }
            }

            if ($exito) {
                DB::commit();

                $tipo = DB::table("solicitudes")
                    ->where("id", $solicitudes_id)
                    ->value("tipo");

                $abreviatura = $tipo == "CAMBIO CARRERA" ? "SECC" : "SECU";

                Mail::to("paul_xd29@hotmail.com")->send(
                    new NotificacionEmailEstudiantes($abreviatura)
                );

                // DEVOLVER DATOS
                return response()->json(
                    [
                        "code" => 200,
                        "status" => "success",
                    ],
                    200
                );
            }
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(
                [
                    "code" => 400,
                    "status" => "error",
                    "message" => "Error al guardar los datos",
                    "errores" => $e,
                ],
                200
            );
        }
    }
}
