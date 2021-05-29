<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class CreateBarcodeMaxesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('barcode_maxes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('subject_name',50)->unique()->nullable(false);
            $table->integer('current_value')->nullable(false);
            $table->string('prefix',10)->nullable(true);
            $table->string('suffix',10)->nullable(true);
            $table->smallInteger('financial_year');

            $table->unique(['subject_name']);

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
        Schema::dropIfExists('barcode_maxes');
    }
}
