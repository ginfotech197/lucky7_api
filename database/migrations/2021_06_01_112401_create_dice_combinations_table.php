<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDiceCombinationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('dice_combination', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->bigInteger('play_series_id')->unsigned();
            $table ->foreign('play_series_id')->references('id')->on('play_series');
            $table->integer('dice1');
            $table->integer('dice2');
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
        Schema::dropIfExists('dice_combination');
    }
}
