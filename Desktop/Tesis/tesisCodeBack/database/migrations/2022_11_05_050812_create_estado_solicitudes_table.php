<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEstadoSolicitudesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create("estado_solicitudes", function (Blueprint $table) {
            $table->id();

            $table
                ->foreign("solicitudes_id")
                ->references("id")
                ->on("solicitudes")
                ->onDelete("no action")
                ->onUpdate("no action");
            $table->unsignedBigInteger("solicitudes_id");

            $table->enum("estado", [
                "APROBADA",
                "APROBADA PARCIALMENTE",
                "RECHAZADA",
                "EN REVISIÓN RC",
                "EN REVISIÓN CM",
                "EN REVISIÓN D",
                "DEV DOC A CM",
                "DEV CM A RC",
                "GENERADA",
                "ANULADA",
                "PENDIENTE",
                "CORRECIONES",
            ]);
            $table->string("observaciones")->nullable(true);
            $table->dateTime("fecha_actualizacion");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists("estado_solicitudes");
    }
}