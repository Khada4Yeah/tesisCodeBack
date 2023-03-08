<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSolicitudesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create("solicitudes", function (Blueprint $table) {
            $table->id();

            $table
                ->foreign("personal_id")
                ->references("idpersonal")
                ->on("esq_datos_personales.personal")
                ->onDelete("no action")
                ->onUpdate("no action");
            $table->unsignedBigInteger("personal_id")->nullable(true);

            $table
                ->foreign("modalidad1_id")
                ->references("id")
                ->on("modalidades")
                ->onDelete("no action")
                ->onUpdate("no action");
            $table->unsignedBigInteger("modalidad1_id")->nullable(true);

            $table
                ->foreign("usuarios_foraneos_id")
                ->references("id")
                ->on("usuarios_foraneos")
                ->onDelete("no action")
                ->onUpdate("no action");
            $table->unsignedBigInteger("usuarios_foraneos_id")->nullable(true);

            $table
                ->foreign("universidades_id")
                ->references("iduniversidad")
                ->on("esq_datos_personales.p_universidad")
                ->onDelete("no action")
                ->onUpdate("no action");
            $table->unsignedBigInteger("universidades_id")->nullable(true);

            $table
                ->foreign("escuelas_origen_id")
                ->references("idescuela")
                ->on("esq_inscripciones.escuela")
                ->onDelete("no action")
                ->onUpdate("no action");
            $table->unsignedBigInteger("escuelas_origen_id")->nullable(true);

            $table
                ->foreign("malla_origen_id")
                ->references("idmalla")
                ->on("esq_mallas.malla_escuela")
                ->onDelete("no action")
                ->onUpdate("no action");
            $table->unsignedBigInteger("malla_origen_id")->nullable(true);

            $table
                ->foreign("escuelas_destino_id")
                ->references("idescuela")
                ->on("esq_inscripciones.escuela")
                ->onDelete("no action")
                ->onUpdate("no action");
            $table->unsignedBigInteger("escuelas_destino_id");

            $table
                ->foreign("departamento_destino_id")
                ->references("iddepartamento")
                ->on("esq_distributivos.departamento")
                ->onDelete("no action")
                ->onUpdate("no action");
            $table
                ->unsignedBigInteger("departamento_destino_id")
                ->nullable(true);

            $table->enum("tipo", ["CAMBIO CARRERA", "CAMBIO UNIVERSIDAD"]);
            $table->string("pdf");
            $table->string("pdf_completo")->nullable(true);

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
        Schema::dropIfExists("solicitudes");
    }
}
