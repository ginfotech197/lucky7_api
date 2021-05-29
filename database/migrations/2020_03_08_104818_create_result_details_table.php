<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class CreateResultDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('result_details', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('result_box');
            $table->float('payout')->default(0)->nullable(false);

            $table->bigInteger('result_master_id')->unsigned()->nullable(false);
            $table ->foreign('result_master_id')->references('id')->on('result_masters');

            $table->bigInteger('play_series_id')->unsigned()->nullable(false);
            $table ->foreign('play_series_id')->references('id')->on('play_series');

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
        Schema::dropIfExists('result_details');
    }
}
