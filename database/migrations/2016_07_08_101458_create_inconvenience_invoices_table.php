<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateInconvenienceInvoicesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('inconvenience_invoices', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('invoice_id');
            $table->double('physical_weekday_min', 8, 2);
            $table->double('physical_weekday_after', 8, 2);
            $table->double('phone_weekday_min', 8, 2);
            $table->double('phone_weekday_after', 8, 2);
            $table->double('physical_weekend_min', 8, 2);
            $table->double('physical_weekend_after', 8, 2);
            $table->double('phone_weekend_min', 8, 2);
            $table->double('phone_weekend_after', 8, 2);
            $table->double('physical_holiday_min', 8, 2);
            $table->double('physical_holiday_after', 8, 2);
            $table->double('phone_holiday_min', 8, 2);
            $table->double('phone_holiday_after', 8, 2);
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
        Schema::drop('inconvenience_invoices');
    }
}
