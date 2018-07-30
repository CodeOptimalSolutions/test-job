<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterColumnsCompanyDepartmentHolidayToUser extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('holidays_to_users', function (Blueprint $table) {
            $table->integer('company_id');
            $table->string('holiday_code')->change();
            $table->integer('department_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('holidays_to_users', function (Blueprint $table) {
            $table->dropColumn('company_id');
            $table->dropColumn('department_id');
        });
    }
}
