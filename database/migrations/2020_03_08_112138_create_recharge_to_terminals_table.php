<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class CreateRechargeToTerminalsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('recharge_to_terminals', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->double('amount')->default(0);

            $table->bigInteger('recharge_master_id')->unsigned();
            $table ->foreign('recharge_master_id')->references('id')->on('people');


            $table->bigInteger('terminal_id')->unsigned();
            $table ->foreign('terminal_id')->references('id')->on('people');

            $table->bigInteger('recharge_master_cat_id')->unsigned();
            $table ->foreign('recharge_master_cat_id')->references('id')->on('person_categories');

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
        Schema::dropIfExists('recharge_to_terminals');
    }
}
