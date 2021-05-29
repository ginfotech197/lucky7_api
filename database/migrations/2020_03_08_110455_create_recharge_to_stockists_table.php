<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class CreateRechargeToStockistsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('recharge_to_stockists', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->double('amount')->default(0);

            $table->bigInteger('recharge_master')->unsigned();
            $table ->foreign('recharge_master')->references('id')->on('people');

            $table->bigInteger('stockist_id')->unsigned();
            $table ->foreign('stockist_id')->references('id')->on('stockists');

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
        Schema::dropIfExists('recharge_to_stockists');
    }
}
