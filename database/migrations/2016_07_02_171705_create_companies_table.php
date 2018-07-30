<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCompaniesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('type_id');
            $table->string('name');
            $table->string('email');
            $table->string('phone');
            $table->string('mobile');
            $table->string('address');
            $table->string('address_2');
            $table->string('city');
            $table->string('town');
            $table->string('country');
            $table->string('post_code');
            $table->string('organization_number');
            $table->string('cost_place')->nullable();
            $table->text('additional_info')->nullable();
            $table->enum('fee', ['yes', 'no']);
            $table->enum('charge_ob', ['yes', 'no']);
            $table->enum('charge_km', ['yes', 'no']);
            $table->integer('time_to_charge')->nullable();
            $table->integer('time_to_pay')->nullable();
            $table->integer('maximum_km')->nullable();
            $table->string('reference_person')->nullable();
            $table->integer('payment_terms')->default('30');
            $table->enum('email_invoice', ['yes', 'no'])->default('yes');
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
        Schema::drop('companies');
    }
}
