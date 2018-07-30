<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterColumnTypeToExportLyst extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('export_lists', function (Blueprint $table) {
            $table->string('type')->after('comment')->default('salaries');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('export_lists', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
}
