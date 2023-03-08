<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Modalidad extends Model
{
    use HasFactory;

    protected $fillable = ["tipo_modalidad", "estado"];
    public $timestamps = false;

    public function Solicitud()
    {
        return $this->hasMany(Solicitud::class);
    }
}