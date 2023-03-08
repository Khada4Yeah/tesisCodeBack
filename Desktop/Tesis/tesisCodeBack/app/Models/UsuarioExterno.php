<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UsuarioExterno extends Model
{
    use HasFactory;
    protected $table = "usuarios_foraneos";

    protected $fillable = [
        "cedula",
        "apellido_p",
        "apellido_m",
        "nombres",
        "fecha_nacimiento",
        "celular",
        "telefono_convencional",
        "direccion",
        "correo_personal",
        "clave",
    ];

    protected $hidden = ["clave"];
}