<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;

class FtpArchivosController extends Controller
{
    //
    public function getPDF($filename)
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
                    "inline; filename=" . $filename
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
}
