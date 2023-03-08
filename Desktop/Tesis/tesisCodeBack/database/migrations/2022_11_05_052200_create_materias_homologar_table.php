<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMateriasHomologarTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create("materias_homologar", function (Blueprint $table) {
            $table->unsignedBigInteger("solicitudes_id");
            $table->unsignedBigInteger("malla_id");
            $table->unsignedBigInteger("escuela_id");
            $table->unsignedBigInteger("materia_id");

            $table
                ->foreign(["malla_id", "escuela_id", "materia_id"])
                ->references(["idmalla", "idescuela", "idmateria"])
                ->on("esq_mallas.malla_materia_nivel")
                ->onDelete("no action")
                ->onUpdate("no action");

            $table
                ->foreign("solicitudes_id")
                ->references("id")
                ->on("solicitudes")
                ->onDelete("no action")
                ->onUpdate("no action");

            $table->string("nombre_materia_procedencia")->nullable(true);
            $table->integer("numero_creditos_procedencia")->nullable(true);
            $table->year("anio_aprobacion_materia")->nullable(true);
            $table->integer("porcentaje_similiutd_contenidos")->nullable(true);
            $table->integer("puntaje_asentar")->nullable(true);
            $table->string("observaciones")->nullable(true);
            $table->string("pdf")->nullable(true);
            $table->string("pdf_analisis")->nullable(true);
            $table
                ->enum("estado", [
                    "DOCENTE ASIGNADO",
                    "EN REVISIÓN DOCENTE",
                    "DATOS ESTABLECIDOS",
                    "INFORME COMPLETADO",
                    null,
                ])
                ->nullable(true);

            $table->boolean("check_cm")->default(false);

            $table
                ->foreign("personal_id")
                ->references("idpersonal")
                ->on("esq_datos_personales.personal")
                ->onDelete("no action")
                ->onUpdate("no action");
            $table->unsignedBigInteger("personal_id")->nullable(true);

            $table->enum("aprobada", ["S", "N"])->nullable(true);

            $table->primary([
                "solicitudes_id",
                "escuela_id",
                "malla_id",
                "materia_id",
            ]);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists("materias_homologar");
    }
}