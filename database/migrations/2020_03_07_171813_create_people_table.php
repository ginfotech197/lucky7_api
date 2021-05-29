<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class CreatePeopleTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('people', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('people_unique_id',50)->unique()->nullable(false);
            $table->string('people_name',250);
            $table->string('mobile_number',250)->nullable(true);
            $table->string('email_id',250)->nullable(true);
            $table->string('user_id',250);
            $table->string('user_password',250);
            $table->string('default_password',250);
            $table->string('uuid',255)->nullable(true);
            $table->string('address',250)->nullable(true);
            $table->string('city',250)->nullable(true);
            $table->string('pin',250)->nullable(true);

            $table->bigInteger('person_category_id')->unsigned();
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
        Schema::dropIfExists('people');
    }
}
