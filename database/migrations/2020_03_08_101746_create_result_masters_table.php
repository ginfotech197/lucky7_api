<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class CreateResultMastersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('result_masters', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->date('game_date');
//            $table->integer('single_result');
//            $table->integer('jumble_number');

            $table->bigInteger('play_series_id')->unsigned();
            $table ->foreign('play_series_id')->references('id')->on('play_series');

            $table->bigInteger('draw_master_id')->unsigned();
            $table ->foreign('draw_master_id')->references('id')->on('draw_masters');

            $table->integer('dice_combination_id');
            $table->string('payout_status');

            $table->unique(['draw_master_id', 'game_date']);


            $table->timestamp('created_at')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('updated_at')->default(DB::raw('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'));
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('result_masters');
    }
}
