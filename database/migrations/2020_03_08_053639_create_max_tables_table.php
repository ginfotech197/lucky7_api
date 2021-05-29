<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class CreateMaxTablesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('max_tables', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('subject_name',50)->unique()->nullable(false);
            $table->bigInteger('current_value')->default(0);
            $table->string('prefix',10)->nullable(true);
            $table->string('suffix',10)->nullable(true);
            $table->smallInteger('financial_year');

            $table->bigInteger('person_category_id')->unsigned()->nullable(false);
            $table ->foreign('person_category_id')->references('id')->on('person_categories');

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
        Schema::dropIfExists('max_tables');
    }
}
