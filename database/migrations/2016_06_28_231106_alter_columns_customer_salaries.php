<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterColumnsCustomerSalaries extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('customer_salaries', function (Blueprint $table) {
            $table->integer('transaction_phone_layman');
            $table->integer('transaction_phone_certified');
            $table->integer('transaction_phone_specialised');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('customer_salaries', function (Blueprint $table) {
            $table->dropColumn('transaction_phone_layman');
            $table->dropColumn('transaction_phone_certified');
            $table->dropColumn('transaction_phone_specialised');
        });
    }
}
