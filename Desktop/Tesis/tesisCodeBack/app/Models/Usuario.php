<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Usuario extends Model
{
    use HasFactory;
    protected $table = "usuarios";
    public $timestamps = false;

    // CAMPOS ASIGNABLES EN MASA
    protected $fillable = ["personal_id", "cargos_id", "estado"];

    // RELACIONES

    // RELACION DE UNO A MUCHOS (BELONGS TO MANY)
    public function Personal()
    {
        return $this->belongsToMany("esq_datos_personales.personal");
    }

    public function Cargos()
    {
        return $this->belongsToMany(Cargo::class);
    }
}