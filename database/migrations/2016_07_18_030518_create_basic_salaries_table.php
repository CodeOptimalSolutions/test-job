<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBasicSalariesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('basic_salaries', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('salary_id');
            $table->integer('type_id');
            $table->double('physical_min', 8, 2);
            $table->double('physical_after', 8, 2);
            $table->double('phone_min', 8, 2);
            $table->double('phone_after', 8, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('basic_salaries');
    }
}
