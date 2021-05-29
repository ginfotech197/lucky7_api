<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
class CreateStockistToTerminalsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('stockist_to_terminals', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->bigInteger('stockist_id')->unsigned();
            $table ->foreign('stockist_id')->references('id')->on('stockists');

            $table->bigInteger('terminal_id')->unsigned();
            $table ->foreign('terminal_id')->references('id')->on('people');

            $table->double('current_balance')->default(0);

            $table->tinyInteger('inforce')->default(1);
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
        Schema::dropIfExists('stockist_to_terminals');
    }
}
