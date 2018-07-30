<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterColumnTypeIdInconvenienceInvoices extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('inconvenience_invoices', function (Blueprint $table) {
            $table->integer('type_id')->after('invoice_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('inconvenience_invoices', function (Blueprint $table) {
            $table->dropColumn('type_id');
        });
    }
}
