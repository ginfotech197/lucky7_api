<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class CreateStockistsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('stockists', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('stockist_unique_id',50)->unique();
            $table->string('stockist_name','100');
            $table->string('user_id','255');
            $table->string('user_password','255');
            $table->integer('serial_number');
            $table->double('current_balance')->default(0);

            $table->bigInteger('person_category_id')->unsigned();
            $table ->foreign('person_category_id')->references('id')->on('person_categories');

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
        Schema::dropIfExists('stockists');
    }
}
