<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class CreateDrawMastersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('draw_masters', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('serial_number');
            $table->string('draw_name',100)->nullable(true);
            $table->time('start_time');
            $table->time('end_time');
            $table->string('meridiem',2);
            $table->tinyInteger('active')->default(0);
            $table->integer('diff')->default(0);


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
        Schema::dropIfExists('draw_masters');
    }
}
