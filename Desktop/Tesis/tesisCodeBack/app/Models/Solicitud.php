<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Solicitud extends Model
{
    use HasFactory;

    protected $table = "solicitudes";

    protected $fillable = [
        "personal_id",
        "modalidad1_id",
        "usuarios_foraneos_id",
        "universidades_id",
        "escuelas_origen_id",
        "malla_origen_id",
        "escuelas_destino_id",
        "departamento_destino_id",
        "tipo",
        "pdf",
        "updated_at",
    ];
}