<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateInconvenienceSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('inconvenience_settings', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('company_id')->default('0');
            $table->integer('department_id')->default('0');
            $table->integer('user_id')->default('0');
            $table->enum('weekends_day_before_after', ['yes', 'no']);
            $table->enum('holiday_day_before_after', ['yes', 'no']);
            $table->enum('inconvenience_standard_rate', ['yes', 'no']);
            $table->string('standard_rate');
            $table->string('weekdays_start');
            $table->string('weekdays_end');
            $table->string('weekend_start');
            $table->string('weekend_end');
            $table->string('holiday_start');
            $table->string('holiday_end');
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
        Schema::drop('inconvenience_settings');
    }
}
