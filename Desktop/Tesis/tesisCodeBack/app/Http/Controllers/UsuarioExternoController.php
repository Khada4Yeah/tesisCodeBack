<?php

namespace App\Http\Controllers;

use App\Models\UsuarioExterno;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Helpers\JwtAuth;

class UsuarioExternoController extends Controller
{
    public function register(Request $request)
    {
        // RECOGER LOS DATOS DEL USUARIO POR POST
        $json = $request->input("json", null);
        $params_array = json_decode($json, true);

        if (!empty($params_array)) {
            // LIMPIAR DATOS
            $params_array = array_map("trim", $params_array);

            // VALIDAR DATOS
            $validate = Validator::make($params_array, [
                "cedula" => "bail|required|size:10",
                "apellido_p" => "bail|required",
                "apellido_m" => "bail|required",
                "nombres" => "bail|required",
                "fecha_nacimiento" => "bail|required",
                "celular" => "bail|required|size:10",
                "telefono_convencional" => "bail|required",
                "direccion" => "bail|required",
                "correo_personal" =>
                    "bail|required|email|unique:usuarios_foraneos",
                "clave" => "bail|required",
            ]);

            if ($validate->fails()) {
                // LA VALIDACION HA FALLADO
                $data = [
                    "status" => "error",
                    "code" => "400",
                    "error_text" =>
                        "El usuario no se ha creado, verificar los campos.",
                    "errors" => $validate->errors(),
                ];
            } else {
                // VALIDACION PASADA CORRECTAMENTE
                // VALIDAR QUE NO EXISTA LA CEDULA EN LA BD
                if ($this->buscarUsuarioExterno($params_array["cedula"])) {
                    return response()->json(
                        [
                            "status" => "error",
                            "error_text" =>
                                "La cédula ya se encuentra registrada!",
                            "code" => 400,
                        ],
                        200
                    );
                }

                if ($this->buscarCedulaUtm($params_array["cedula"])) {
                    return response()->json(
                        [
                            "status" => "error",
                            "error_text" =>
                                "Usted ya pertenece a la Universidad Técnica de Manabí!",
                            "code" => 400,
                        ],
                        200
                    );
                }

                // CIFRAR LA CONTRASENA
                $pwd = hash("sha256", $params_array["clave"]);

                // CREAR EL USUARIO
                $usuario = new UsuarioExterno();
                $usuario->cedula = $params_array["cedula"];
                $usuario->apellido_p = strtoupper($params_array["apellido_p"]);
                $usuario->apellido_m = strtoupper($params_array["apellido_m"]);
                $usuario->nombres = strtoupper($params_array["nombres"]);
                $usuario->fecha_nacimiento = $params_array["fecha_nacimiento"];
                $usuario->celular = $params_array["celular"];
                $usuario->telefono_convencional =
                    $params_array["telefono_convencional"];
                $usuario->direccion = $params_array["direccion"];
                $usuario->correo_personal = $params_array["correo_personal"];
                $usuario->clave = $pwd;
                $usuario->estado = "H";

                // GUARDAR EL USUARIO
                $usuario->save();

                $data = [
                    "status" => "success",
                    "code" => 200,
                    "message" => "El usuario se ha creado correctamente",
                    "usuario" => $usuario,
                ];
            }
        } else {
            $data = [
                "status" => "error",
                "code" => "400",
                "error_text" => "Los datos enviandos no son correctos",
            ];
        }

        return response()->json($data);
    }

    public function buscarUsuarioExterno($cedula)
    {
        $usuario = DB::select(
            "SELECT * 
        FROM esq_homo.usuarios_foraneos uf
        WHERE uf.cedula = '" .
                $cedula .
                "'"
        );

        if (!empty($usuario)) {
            return true;
        } else {
            return false;
        }
    }

    public function buscarCedulaUtm($cedula)
    {
        $usuarioUtm = DB::select(
            "SELECT pe.cedula
        FROM esq_datos_personales.personal pe
        WHERE pe.cedula = '" .
                $cedula .
                "'"
        );

        if (!empty($usuarioUtm)) {
            return true;
        } else {
            return false;
        }
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\UsuarioExterno  $usuarioExterno
     * @return \Illuminate\Http\Response
     */
    public function show($idPersona)
    {
        if (isset($idPersona)) {
            $usuario = DB::table("usuarios_foraneos")
                ->where("id", $idPersona)
                ->first();

            return response()->json([
                "code" => 200,
                "status" => "success",
                "usuario" => $usuario,
            ]);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\UsuarioExterno  $usuarioExterno
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        $json = $request->input("json", null);

        $params_array = json_decode($json, true);

        if (!empty($params_array)) {
            $validar_datos = Validator::make($params_array, [
                "id" => "required",
                "facultad_origen" => "required",
                "carrera_origen" => "required",
                "nivel_origen" => "required",
            ]);

            if ($validar_datos->fails()) {
                return response()->json([
                    "code" => 400,
                    "status" => "error",
                    "message" => "Error al enviar los datos",
                    "errores" => $validar_datos->errors(),
                ]);
            }

            try {
                DB::beginTransaction();

                DB::table("usuarios_foraneos")
                    ->where("id", $params_array["id"])
                    ->update([
                        "facultad_origen" => strtoupper(
                            $params_array["facultad_origen"]
                        ),
                        "carrera_origen" => strtoupper(
                            $params_array["carrera_origen"]
                        ),
                        "nivel_origen" => strtoupper(
                            $params_array["nivel_origen"]
                        ),
                    ]);

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

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\UsuarioExterno  $usuarioExterno
     * @return \Illuminate\Http\Response
     */
    public function destroy(UsuarioExterno $usuarioExterno)
    {
        //
    }
}
