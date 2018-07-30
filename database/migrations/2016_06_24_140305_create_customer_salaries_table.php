<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCustomerSalariesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('customer_salaries', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id');
            $table->integer('physical_layman');
            $table->integer('phone_layman');
            $table->integer('physical_certified');
            $table->integer('phone_certified');
            $table->integer('physical_specialised');
            $table->integer('phone_specialised');
            $table->integer('travel_time_layman');
            $table->integer('travel_time_certified');
            $table->integer('travel_time_specialised');
            $table->integer('km_price');
            $table->integer('inconvenient_layman');
            $table->integer('inconvenient_certified');
            $table->integer('inconvenient_specialised');
            $table->integer('transaction_layman');
            $table->integer('transaction_certified');
            $table->integer('transaction_specialised');
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
        Schema::drop('customer_salaries');
    }
}
