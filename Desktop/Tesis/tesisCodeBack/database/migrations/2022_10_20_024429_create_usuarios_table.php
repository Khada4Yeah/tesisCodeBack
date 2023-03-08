<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsuariosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create("usuarios", function (Blueprint $table) {
            $table->engine = "InnoDB";
            $table->id();

            $table
                ->foreign("personal_id", "constrainfk")
                ->references("idpersonal")
                ->on("esq_datos_personales.personal")
                ->onDelete("no action")
                ->onUpdate("no action");
            $table->unsignedBigInteger("personal_id");

            $table
                ->foreign("cargos_id")
                ->references("id")
                ->on("cargos")
                ->onDelete("cascade")
                ->onUpdate("cascade");
            $table->unsignedBigInteger("cargos_id");

            $table
                ->foreign("departamento_id")
                ->references("iddepartamento")
                ->on("esq_distributivos.departamento")
                ->onUpdate("no action")
                ->onDelete("no action");
            $table->unsignedBigInteger("departamento_id")->nullable(true);

            $table->enum("estado", ["H", "D"]);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists("usuarios");
    }
}