<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateInvoicesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id');
            $table->float('physical_layman')->nullable();
            $table->float('phone_layman')->nullable();
            $table->float('physical_certified')->nullable();
            $table->float('phone_certified')->nullable();
            $table->float('physical_specialised')->nullable();
            $table->float('phone_specialised')->nullable();
            $table->float('travel_time_layman')->nullable();
            $table->float('travel_time_certified')->nullable();
            $table->float('travel_time_specialised')->nullable();
            $table->float('km_price')->nullable();
            $table->float('inconvenient_layman')->nullable();
            $table->float('inconvenient_certified')->nullable();
            $table->float('inconvenient_specialised')->nullable();
            $table->float('transaction_layman')->nullable();
            $table->float('transaction_certified')->nullable();
            $table->float('transaction_specialised')->nullable();
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
        Schema::drop('invoices');
    }
}
