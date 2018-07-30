<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTravelInvoicesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('travel_invoices', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('invoice_id');
            $table->enum('km_reimbursement', ['yes', 'no']);
            $table->enum('travel_time', ['yes', 'no']);
            $table->enum('minimum_time_to_eligible', ['yes', 'no']);
            $table->double('maximum_km');
            $table->double('per_km');
            $table->double('per_hour');
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
        Schema::drop('travel_invoices');
    }
}
