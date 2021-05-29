<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class CreatePlaySeriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('play_series', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('series_name',20)->nullable(true);
            $table->string('game_initial',10)->nullable(true);
            $table->decimal('mrp',10,2)->default(0);
            $table->decimal('winning_price',10,2)->default(0);
            $table->decimal('commision',10,2)->default(0);
            $table->decimal('payout',10,2)->default(0);
            $table->decimal('default_payout',10,2)->default(0);

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
        Schema::dropIfExists('play_series');
    }
}
