<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Estado_Solicitud extends Model
{
    use HasFactory;
    protected $table = "estado_solicitudes";
    public $timestamps = false;
    protected $fillable = [
        "solicitudes_id",
        "estado",
        "observaciones",
        "fecha_actualizacion",
    ];
}