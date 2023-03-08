<?php

namespace App\Http\Controllers;

use App\Models\Cargo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CargoController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $cargos = DB::select("SELECT id, cargo, estado
        FROM esq_homo.cargos");

        return response()->json(
            [
                "code" => 200,
                "status" => "success",
                "cargos" => $cargos,
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
        // RECOGER LOS DATOS POR POST
        $json = $request->input("json", null);
        $params_array = json_decode($json, true);

        if (!empty($params_array)) {
            //VALIDAR LOS DATOS
            $validate = Validator::make($params_array, [
                "cargo" => "required",
                "estado" => "required",
            ]);

            // GUARDAR EL CARGO
            if ($validate->fails()) {
                $data = [
                    "code" => 400,
                    "status" => "error",
                    "message" => "No se ha guardado el cargo.",
                ];
            } else {
                $cargo = new Cargo();
                $cargo->cargo = strtoupper($params_array["cargo"]);
                $cargo->estado = $params_array["estado"];
                $cargo->save();

                $data = [
                    "code" => 200,
                    "status" => "success",
                    "cargo" => $cargo,
                ];
            }
        } else {
            $data = [
                "code" => 400,
                "status" => "error",
                "message" => "No has enviado ningún cargo.",
            ];
        }

        // DEVOLVER RESULTADO
        return response()->json($data, $data["code"]);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Cargo  $cargo
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $cargo = Cargo::find($id);

        if (is_object($cargo)) {
            $data = [
                "code" => 200,
                "status" => "success",
                "cargo" => $cargo,
            ];
        } else {
            $data = [
                "code" => 400,
                "status" => "error",
                "message" => "El cargo no existe.",
            ];
        }
        return response()->json($data, $data["code"]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Cargo  $cargo
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        // RECOGER LOS DATOS POR POST
        $json = $request->input("json", null);
        $params_array = json_decode($json, true);

        if (!empty($params_array)) {
            // VALIDAR LOS DATOS
            $validate = Validator::make($params_array, [
                "cargo" => "required",
                "estado" => "required",
            ]);

            if ($validate->fails()) {
                $data = [
                    "code" => 400,
                    "status" => "error",
                    "message" => "No se ha actualizado el cargo.",
                ];
            } else {
                // QUITAR LO QUE NO SE QUIERE ACTUALIZAR
                unset($params_array["id"]);

                // ACTUALIZAR EL REGISTRO (CARGO)
                Cargo::where("id", $id)->update($params_array);

                $data = [
                    "code" => 200,
                    "status" => "success",
                    "cargo" => $params_array,
                ];
            }
        } else {
            $data = [
                "code" => 400,
                "status" => "error",
                "message" => "No has enviado ningún cargo",
            ];
        }

        // DEVOLVER RESULTADO
        return response()->json($data, $data["code"]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Cargo  $cargo
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        // CONSEGUIR EL REGISTRO
        $cargo = Cargo::where("id", $id);

        if (!empty($cargo)) {
            // BORRARLO
            $cargo->delete();

            $data = [
                "code" => 200,
                "status" => "success",
                "message" => "Cargo eliminado",
            ];
        } else {
            $data = [
                "code" => 404,
                "status" => "error",
                "message" => "El cargo no existe.",
            ];
        }

        // DEVOLVER RESULTADO
        return response()->json($data, $data["code"]);
    }
}