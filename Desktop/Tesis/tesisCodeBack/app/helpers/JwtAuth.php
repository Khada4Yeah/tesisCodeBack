<?php

namespace App\Helpers;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Support\Facades\DB;
use App\Models\Usuario;

use Illuminate\Http\Request;

class JwtAuth
{
    private $key;

    public function __construct()
    {
        $this->key = "esto_es_una_clave_super_secreta-99887766";
    }

    public function singup($responseLoginUtm, $getToken = null)
    {
        // Generar el token con los datos del usuario identificado
        if ($responseLoginUtm["error"] != true) {
            $token = [
                "sub" => $responseLoginUtm["id_personal"],
                "cedula" => $responseLoginUtm["cedula"],
                "name" => $responseLoginUtm["nombres"],
                "rol_sistema" => $responseLoginUtm["rol_sistema"],
                "iat" => time(),
                "exp" => time() + 7 * 24 * 60 * 60, //cuando va a caducar el token
            ];

            $jwt = JWT::encode($token, $this->key, "HS256");
            $decoded = JWT::decode($jwt, new Key($this->key, "HS256"));

            // Devolver los datos decodificados o el token, en funcion de un parametro
            if (is_null($getToken)) {
                $data = $jwt;
            } else {
                $data = $decoded;
            }
        } else {
            $data = [
                "status" => "error",
                "message" => "Login incorrecto",
            ];
        }

        return $data;
    }

    public function checkToken($jwt, $getIdentity = false)
    {
        $auth = false;

        try {
            $jwt = str_replace('"', "", $jwt);
            $decoded = JWT::decode($jwt, new Key($this->key, "HS256"));
        } catch (\UnexpectedValueException $e) {
            $auth = false;
        } catch (\DomainException $e) {
            $auth = false;
        }

        if (!empty($decoded) && is_object($decoded) && isset($decoded->sub)) {
            $auth = true;
        } else {
            $auth = false;
        }

        if ($getIdentity) {
            return $decoded;
        }

        return $auth;
    }
}