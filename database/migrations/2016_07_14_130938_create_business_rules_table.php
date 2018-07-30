<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBusinessRulesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('business_rules', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('invoice_id');
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
        Schema::drop('business_rules');
    }
}
