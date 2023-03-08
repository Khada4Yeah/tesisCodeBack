<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Materia_Homologar extends Model
{
    use HasFactory;
    protected $table = "materias_homologar";

    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        "solicitudes_id",
        "malla_id",
        "escuela_id",
        "materia_id",
        "nombre_materia_procedencia",
        "numero_creditos_procedencia",
        "anio_aprobacion_materia",
        "porcentaje_similiutd_contenidos",
        "puntaje_asentar",
        "observaciones",
        "pdf",
        "pdf_analisis",
        "estado",
        "personal_id",
        "aprobada",
    ];
}