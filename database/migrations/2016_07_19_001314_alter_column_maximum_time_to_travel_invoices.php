<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterColumnMaximumTimeToTravelInvoices extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('travel_invoices', function (Blueprint $table) {
            $table->double('maximum_time')->after('maximum_km');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('travel_invoices', function (Blueprint $table) {
            $table->dropColumn('maximum_time');
        });
    }
}
