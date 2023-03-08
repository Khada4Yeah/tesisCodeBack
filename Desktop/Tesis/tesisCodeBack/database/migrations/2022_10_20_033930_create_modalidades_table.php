<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateModalidadesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create("modalidades", function (Blueprint $table) {
            $table->engine = "InnoDB";

            $table->id();
            $table->enum("tipo_modalidad", [
                "PRESENCIAL",
                "VIRTUAL",
                "HIBRIDA",
            ]);
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
        Schema::dropIfExists("modalidades");
    }
}