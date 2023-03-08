<?php

namespace App\Http\Controllers;

use App\Helpers\JwtAuth;
use App\Models\User;
use App\Models\Usuario;

use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UsuarioController extends Controller
{
    public function pruebaxd(Request $request)
    {
        $json = $request->input("json", null);
        $params_array = json_decode($json, true);

        $info = DB::select(
            "SELECT esq_roles.fnc_login_2_desarrollo(
        '" .
                $params_array["correo"] .
                "',
        '" .
                $params_array["contrasena"] .
                "',
        '',
        '',
        '',
        '',
        '',
        0,
        '') as data"
        );

        $data = str_replace('"', "", $info[0]->data);
        $data = str_replace("(", "", $data);
        $data = str_replace(")", "", $data);

        $info_split = explode(",", $data);

        if ($info_split[0] != "Ok.") {
            return response()->json([
                "error" => true,
                "error_text" => $info[0]->data,
            ]);
        }

        $r_error = false;
        $r_idpersonal = $info_split[1];
        $r_cedula = $info_split[2];
        $r_nombres = $info_split[3];
        $r_password_changed = $info_split[4];
        $r_mail_alternativo = $info_split[5];
        $r_fecha = $info_split[6];
        $r_idfichero_hoja_vida_foto = $info_split[7];
        $r_conexion = $info_split[8];
        //UTMCRED|Facultades Portoviejo Modalidad Creditos
        $r_roles = explode("*", $r_conexion);
        // var_dump($r_roles);
        // die();

        $r_roles = explode("*", $r_conexion);

        $r_roles_proc = [];

        foreach ($r_roles as $rol) {
            $r_rol = explode("|", $rol);
            $id_rol_raw = explode(":", $r_rol[count($r_rol) - 2]);
            $id_rol = end($id_rol_raw);

            $escuelas_raw = explode(":", $rol);

            $rolJson = [
                "id" => $id_rol,
                "nombre" => $r_rol[count($r_rol) - 1],
                "escuelas" => explode("|", $escuelas_raw[1]),
                "departamentos" => explode("|", $escuelas_raw[2]),
                "modalidad" => $id_rol_raw[0],
                "conexion" => $r_rol[0],
            ];
            array_push($r_roles_proc, $rolJson);
        }
        // BETTY 1. 88 Y 2. 47 PSICOLOGIA CLINICA
        // LUIS 1. 15 y 2. 42   EDUCACION BASICA
        $jsonRespuesta = [
            "error" => $r_error,
            "id_personal" => $r_idpersonal,
            "cedula" => $r_cedula,
            "nombres" => $r_nombres,
            "password_changed" => $r_password_changed,
            "mail_alternativo" => $r_mail_alternativo,
            "fecha" => $r_error,
            "fichero_hoja_de_vida_foto" => $r_idfichero_hoja_vida_foto,
            "conexion" => $r_conexion,
            "roles" => $r_roles_proc,
        ];
        return response()->json($jsonRespuesta);
    }

    // FUNCION QUE CONTROLA EL LOGIN DE LOS USUARIOS
    public function login(Request $request)
    {
        // RECOGEMOS LOS DATOS POR POST
        $json = $request->input("json", null);
        $params = json_decode($json);
        $params_array = json_decode($json, true);
        $cargosDesglosado = [];

        // EXTRAER EL DOMINIO DEL CORREO
        $flag_correo = stristr($params_array["correo"], "@");

        // EN CASO DE QUE NO SEA UN CORREO DE LA UTM ES UN USUARIO EXTERNO
        if ($flag_correo !== "@utm.edu.ec") {
            // CIFRAMOS LA CONTRASEÑA
            $params_array["contrasena"] = hash(
                "sha256",
                $params_array["contrasena"]
            );

            // BUSCAMOS EL REGISTRO EN LA BD/TABLA usuarios_foraneos
            $usr = DB::table("usuarios_foraneos")
                ->where("correo_personal", $params_array["correo"])
                ->where("clave", $params_array["contrasena"])
                ->select([
                    "id AS id_personal",
                    "cedula",
                    "nombres",
                    "apellido_p",
                    "apellido_m",
                    "correo_personal",
                    "clave",
                ])
                ->get();

            // EN CASO DE QUE LAS CREDENCIALES COINCIDAN CON LA BD
            if (count($usr) > 0) {
                array_push($cargosDesglosado, "ESTUDIANTE FORANEO");

                $jsonRespuesta = [
                    "error" => false,
                    "id_personal" => $usr[0]->id_personal,
                    "cedula" => $usr[0]->cedula,
                    "nombres" =>
                        $usr[0]->apellido_p .
                        " " .
                        $usr[0]->apellido_m .
                        " " .
                        $usr[0]->nombres,
                    "rol_sistema" => $cargosDesglosado,
                ];
            } else {
                return response()->json([
                    "error" => true,
                    "error_text" => "Correo o contraseña incorrectos",
                ]);
            }
        } else {
            $info = DB::select(
                "SELECT esq_roles.fnc_login_2_desarrollo(
            '" .
                    $params_array["correo"] .
                    "',
            '" .
                    $params_array["contrasena"] .
                    "',
            '',
            '',
            '',
            '',
            '',
            0,
            '') as data"
            );

            // Quitar comillas y parentesis de la consulta
            $data = str_replace('"', "", $info[0]->data);
            $data = str_replace("(", "", $data);
            $data = str_replace(")", "", $data);

            // Separar los campos recibios en un array
            $info_split = explode(",", $data);

            // Verificar si la consulta devolvio un registro existente
            if ($info_split[0] != "Ok.") {
                return response()->json([
                    "error" => true,
                    "error_text" => $info[0]->data,
                ]);
            }

            // SEPRANDO LA INFORMACION RECIBIDA
            $r_error = false;
            $r_idpersonal = $info_split[1];
            $r_cedula = $info_split[2];
            $r_nombres = $info_split[3];
            $r_password_changed = $info_split[4];
            $r_mail_alternativo = $info_split[5];
            $r_fecha = $info_split[6];
            $r_idfichero_hoja_vida_foto = $info_split[7];
            $r_conexion = $info_split[8];

            $r_roles = explode("*", $r_conexion);

            $r_roles_proc = [];

            foreach ($r_roles as $rol) {
                $r_rol = explode("|", $rol);
                $id_rol_raw = explode(":", $r_rol[count($r_rol) - 2]);
                $id_rol = end($id_rol_raw);

                $escuelas_raw = explode(":", $rol);

                $rolJson = [
                    "id" => $id_rol,
                    "nombre" => $r_rol[count($r_rol) - 1],
                    "escuelas" => explode("|", $escuelas_raw[1]),
                    "departamentos" => explode("|", $escuelas_raw[2]),
                    "modalidad" => $id_rol_raw[0],
                    "conexion" => $r_rol[0],
                ];
                array_push($r_roles_proc, $rolJson);
            }

            foreach ($r_roles_proc as $roles) {
                if (
                    $roles["nombre"] === "COORDINADOR/A DEPARTAMENTAL" &&
                    $roles["conexion"] === "UTMCRED"
                ) {
                    array_push($cargosDesglosado, "COORDINADOR DE MATERIAS");
                } elseif (
                    $roles["nombre"] === "VICEDECANATO DE CARRERA" &&
                    $roles["conexion"] === "UTMCRED"
                ) {
                    array_push($cargosDesglosado, "VICEDECANO");
                }
            }

            // BUSCAMOS SI EL USUARIO ES UN USUARIO DEL SISTEMA DE HOMOLOGACION
            $cargosUsuarioHomo = DB::select("SELECT c.cargo
                FROM esq_homo.cargos c
                JOIN esq_homo.usuarios u ON c.id = u.cargos_id
                WHERE u.personal_id = $r_idpersonal
                ORDER BY c.id
            ");

            // SI ES ASI CONSULTAMOS SUS CARGOS Y LO GUARDAMOS EN UN ARREGLO
            if (count($cargosUsuarioHomo) > 0) {
                foreach ($cargosUsuarioHomo as $cargoU) {
                    array_push($cargosDesglosado, $cargoU->cargo);
                }
            }

            // BUSCAMOS SI EL USUARIO ES UN DOCENTE DE ANALISIS
            $docenteAnalsis = DB::table("materias_homologar")
                ->where("personal_id", $r_idpersonal)
                ->first();
            $flag_docente_analisis = false;

            if ($docenteAnalsis !== null) {
                array_push($cargosDesglosado, "DOCENTE ANÁLISIS");
                $flag_docente_analisis = true;
            }

            if (
                count($this->verificarDocente($r_idpersonal)) > 0 &&
                $flag_docente_analisis == false &&
                count($cargosDesglosado) === 0
            ) {
                return response()->json([
                    "error" => true,
                    "error_text" =>
                        "Usted no puede entrar a este sistema - DOCENTE",
                ]);
            } elseif (
                $this->consultarPeriodo($r_idpersonal) &&
                count($cargosDesglosado) === 0
            ) {
                $cargosDesglosado[] = "ESTUDIANTE";
            } elseif (
                $this->consultarPeriodo($r_idpersonal) === false &&
                count($cargosDesglosado) === 0
            ) {
                return response()->json([
                    "error" => true,
                    "error_text" =>
                        "Usted no puede entrar a este sistema - ESTUDIANTE NO MATRICULADO",
                ]);
            }

            $jsonRespuesta = [
                "error" => $r_error,
                "id_personal" => $r_idpersonal,
                "cedula" => $r_cedula,
                "nombres" => $r_nombres,
                "password_changed" => $r_password_changed,
                "mail_alternativo" => $r_mail_alternativo,
                "fecha" => $r_error,
                "fichero_hoja_de_vida_foto" => $r_idfichero_hoja_vida_foto,
                "conexion" => $r_conexion,
                "roles" => $r_roles_proc,
                "rol_sistema" => $cargosDesglosado,
            ];
        }

        $jwtAuth = new JwtAuth();

        $singup = $jwtAuth->singup($jsonRespuesta);

        if (!empty($params->gettoken)) {
            $singup = $jwtAuth->singup($jsonRespuesta, true);
        }

        return response()->json($singup, 200);
    }

    // FUNCION PARA DECODIFICAR EL TOKEN
    public function decodeToken(Request $request)
    {
        $json = $request->input("json", null);
        $params_array = json_decode($json, true);
        $jwtAuth = new JwtAuth();
        $usuario = $jwtAuth->checkToken($params_array["token"], true);

        return response()->json([
            "code" => 200,
            "status" => "success",
            "usuario" => $usuario,
        ]);
    }

    // FUNCION PARA VERIFICAR SI EL USUARIO ES DOCENTE
    public function verificarDocente($id)
    {
        $consultaver = DB::select(
            "SELECT f.idfacultad, f.nombre facultad, d.iddepartamento, d.nombre departamento, dd.idpersonal, p.apellido1 || ' ' || p.apellido2 || ' ' || p.nombres nombres
            from esq_distributivos.departamento d
            join esq_inscripciones.facultad f 
            on d.idfacultad = f.idfacultad
            and not f.nombre = 'POSGRADO'
            and not f.nombre = 'CENTRO DE PROMOCIÓN Y APOYO AL INGRESO'
            and not f.nombre = 'INSTITUTO DE INVESTIGACIÓN'
            and d.habilitado = 'S'
            join esq_distributivos.departamento_docente dd
            on dd.iddepartamento = d.iddepartamento
            join esq_datos_personales.personal p 
            on dd.idpersonal = p.idpersonal
            where p.idpersonal = " .
                $id .
                "
            ORDER BY d.idfacultad, d.iddepartamento, p.idpersonal"
        );
        return $consultaver;
    }

    // FUNCION PARA VERIFICAR SI EL USUARIO EN CASO DE SER ESTUDIANTE ESTA MATRICULADO
    public function consultarPeriodo($idpersonal)
    {
        $consulta = DB::select(
            "select es.idescuela,es.nombre as Escuela_Nombre,pa.nombre as PERIODO ,i.prom_s as Promedio, m.nombre as Semestre
            from esq_inscripciones.inscripcion i
            join  esq_inscripciones.escuela es on  i.idescuela = es.idescuela 
            join esq_periodos_academicos.periodo_academico pa on pa.idperiodo=i.idperiodo 
            join esq_mallas.nivel m on i.idnivel=m.idnivel 
            where i.idpersonal = " .
                $idpersonal .
                " and pa.actual  = 'S'
            order by pa.idperiodo DESC"
        );

        if ($consulta) {
            return true;
        } else {
            return false;
        }
    }

    public function consultarPersonalCedula($cedula)
    {
        $persona = DB::select(
            "SELECT pe.idpersonal, CONCAT(pe.apellido1, ' ', pe.apellido2, ' ', pe.nombres) AS nombres
            FROM esq_datos_personales.personal pe
            WHERE pe.cedula = '$cedula'"
        );

        if ($persona) {
            return response()->json(
                [
                    "code" => 200,
                    "status" => "success",
                    "persona" => $persona,
                ],
                200
            );
        } else {
            return response()->json(
                [
                    "code" => 200,
                    "status" => "error",
                    "error_text" => "Persona no encontrada!",
                ],
                200
            );
        }
    }

    public function consultarDepartamentos()
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

        if (count($departamentos) > 0) {
            return response()->json(
                [
                    "code" => 200,
                    "status" => "success",
                    "departamentos" => $departamentos,
                ],
                200
            );
        } else {
            return response()->json(
                [
                    "code" => 200,
                    "status" => "error",
                    "message" => "Error al consultar los departamentos",
                ],
                200
            );
        }
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $usuarios = DB::select(
            "SELECT u.id, p.nombres, CONCAT(p.apellido1, ' ',p.apellido2) AS Apellidos, c.cargo, u.estado, d.nombre AS departamento
            FROM esq_homo.usuarios u
            JOIN esq_datos_personales.personal p ON u.personal_id = p.idpersonal
            JOIN esq_homo.cargos c ON c.id = u.cargos_id
            LEFT JOIN esq_distributivos.departamento d ON d.iddepartamento = u.departamento_id
            ORDER BY u.cargos_id"
        );

        return response()->json(
            [
                "code" => 200,
                "status" => "success",
                "usuarios" => $usuarios,
            ],
            200
        );
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $json = $request->input("json", null);
        $params_array = json_decode($json, true);

        if (!empty($params_array)) {
            //VALIDAR LOS DATOS
            $validar_datos = Validator::make($params_array, [
                "personal_id" => "required",
                "cargos_id" => "required",
                "estado" => "required",
            ]);

            // GUARDAR EL CARGO
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
                try {
                    DB::beginTransaction();

                    $usuario = new Usuario();
                    $usuario->personal_id = $params_array["personal_id"];
                    $usuario->cargos_id = $params_array["cargos_id"];

                    if (array_key_exists("departamento_id", $params_array)) {
                        $params_array["departamento_id"] == 0
                            ? ($usuario->departamento_id = null)
                            : ($usuario->departamento_id =
                                $params_array["departamento_id"]);
                    }

                    $usuario->estado = $params_array["estado"];
                    $usuario->save();

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
                    "message" => "Error al enviar los datos",
                ],
                400
            );
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Usuario  $usuario
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $usuario = DB::select(
            "SELECT u.id, p.nombres, CONCAT(p.apellido1, ' ',p.apellido2) AS Apellidos, c.cargo, u.estado, d.nombre AS departamento
            FROM esq_homo.usuarios u
            JOIN esq_datos_personales.personal p ON u.personal_id = p.idpersonal
            JOIN esq_homo.cargos c ON c.id = u.cargos_id
            JOIN esq_distributivos.departamento d ON d.iddepartamento = u.departamento_id
            WHERE u.id = $id
            ORDER BY u.cargos_id"
        );

        if (count($usuario) > 0) {
            $data = [
                "code" => 200,
                "status" => "success",
                "usuario" => $usuario,
            ];
        } else {
            $data = [
                "code" => 400,
                "status" => "error",
                "message" => "El usuario no existe.",
            ];
        }
        return response()->json($data, $data["code"]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Usuario  $usuario
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $json = $request->input("json", null);
        $params_array = json_decode($json, true);

        if (!empty($params_array)) {
            // VALIDAR LOS DATOS
            $validar_datos = Validator::make($params_array, [
                "cargos_id" => "required",
                "estado" => "required",
            ]);

            // GUARDAR EL USUARIO
            if ($validar_datos->fails()) {
                return response()->json(
                    [
                        "code" => 400,
                        "status" => "error",
                        "message" => "Error al enviar los datos",
                        "errors" => $validar_datos->errors(),
                    ],
                    400
                );
            } else {
                try {
                    DB::beginTransaction();

                    // ACTUALIZAR EL REGISTRO (CARGO)
                    DB::table("usuarios")
                        ->where("id", $id)
                        ->update([
                            "cargos_id" => $params_array["cargos_id"],
                            "departamento_id" =>
                                $params_array["departamento_id"],
                            "estado" => $params_array["estado"],
                        ]);

                    DB::commit();

                    return response()->json(
                        [
                            "code" => 200,
                            "status" => "success",
                            "usuario" => $params_array,
                        ],
                        200
                    );
                } catch (\Exception $e) {
                    DB::rollBack();

                    return response()->json(
                        [
                            "code" => 400,
                            "status" => "error",
                            "message" => "Error al actualizar los datos",
                            "errores" => $e,
                        ],
                        400
                    );
                }
            }
        } else {
            return response()->json([
                "code" => 400,
                "status" => "error",
                "message" => "Error al enviar los datos.",
            ]);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Usuario  $usuario
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        // CONSEGUIR EL REGISTRO
        $usuario = Usuario::where("id", $id);

        if (!empty($usuario)) {
            // BORRARLO
            $usuario->delete();

            $data = [
                "code" => 200,
                "status" => "success",
                "message" => "Usuario eliminado",
            ];
        } else {
            $data = [
                "code" => 404,
                "status" => "error",
                "message" => "El usuario no existe.",
            ];
        }

        // DEVOLVER RESULTADO
        return response()->json($data, $data["code"]);
    }

    public function indexEstudiantesExternos()
    {
        $estudiantes = DB::table("usuarios_foraneos")
            ->select([
                "id",
                "cedula",
                DB::raw("CONCAT(apellido_p, ' ', apellido_m) AS apellidos"),
                "nombres",
                "correo_personal",
                "estado",
            ])
            ->orderBy("apellidos")
            ->get();

        return response()->json([
            "code" => 200,
            "status" => "success",
            "estudiantes" => $estudiantes,
        ]);
    }

    public function cambiarEstadoEstudianteExterno($idUsuario, $estado)
    {
        if (isset($idUsuario) && isset($estado)) {
            try {
                DB::beginTransaction();

                DB::table("usuarios_foraneos")
                    ->where("id", $idUsuario)
                    ->update(["estado" => $estado]);

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

    public function eliminarEstudianteExterno($idPersona)
    {
        if (isset($idPersona)) {
            try {
                DB::beginTransaction();

                DB::table("usuarios_foraneos")
                    ->where("id", $idPersona)
                    ->delete();

                DB::commit();

                return response()->json([
                    "code" => 200,
                    "status" => "success",
                ]);
            } catch (\Exception $e) {
                DB::rollBack();

                return response()->json([
                    "code" => 200,
                    "status" => "success",
                    "message" => "Error al eliminar el registro",
                    "errores" => $e,
                ]);
            }
        } else {
            return response()->json([
                "code" => 200,
                "status" => "success",
                "message" => "Error al enviar los datos",
            ]);
        }
    }
}
