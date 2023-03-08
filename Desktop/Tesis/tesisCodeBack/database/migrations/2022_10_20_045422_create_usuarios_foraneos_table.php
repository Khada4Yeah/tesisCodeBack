<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsuariosForaneosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create("usuarios_foraneos", function (Blueprint $table) {
            $table->engine = "InnoDB";

            $table->id();
            $table->char("cedula", 10)->unique();
            $table->string("apellido_p", 50);
            $table->string("apellido_m", 50);
            $table->string("nombres", 100);
            $table->date("fecha_nacimiento");
            $table->char("celular", 10);
            $table->char("telefono_convencional", 10);
            $table->string("direccion");
            $table->string("correo_personal")->unique();
            $table->string("clave");
            $table->string("facultad_origen")->nullable();
            $table->string("carrera_origen")->nullable();
            $table->string("nivel_origen")->nullable();
            $table->enum("estado", ["H", "D"]);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists("usuarios_foraneos");
    }
}
