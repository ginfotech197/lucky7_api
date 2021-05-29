<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class CreateClaimDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('claim_details', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->bigInteger('game_id')->unsigned()->nullable(false);
            $table->foreign('game_id')->references('id')->on('games');

            $table->bigInteger('play_master_id')->unsigned();
            $table ->foreign('play_master_id')->references('id')->on('play_masters');

            $table->bigInteger('terminal_id')->unsigned()->nullable(false);
            $table ->foreign('terminal_id')->references('id')->on('people');

            $table->double('prize_value')->default(0);

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
        Schema::dropIfExists('claim_details');
    }
}
