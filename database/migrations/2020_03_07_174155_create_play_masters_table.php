<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class CreatePlayMastersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('play_masters', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('barcode_number',50);
            $table->tinyInteger('is_claimed')->default(0);
            $table->string('activity_done_by')->default('self');


            $table->bigInteger('terminal_id')->unsigned()->nullable(false);
            $table ->foreign('terminal_id')->references('id')->on('people');


            $table->bigInteger('draw_master_id')->unsigned()->nullable(false);
            $table ->foreign('draw_master_id')->references('id')->on('draw_masters');


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
        Schema::dropIfExists('play_masters');
    }
}
