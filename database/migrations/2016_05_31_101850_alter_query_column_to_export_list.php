<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterQueryColumnToExportList extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('export_lists', function (Blueprint $table) {
            $table->addColumn('text', 'query', ['after' => 'comment']);
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
            $table->dropColumn('query');
        });
    }
}
