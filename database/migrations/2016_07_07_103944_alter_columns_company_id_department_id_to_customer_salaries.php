<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterColumnsCompanyIdDepartmentIdToCustomerSalaries extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('customer_salaries', function (Blueprint $table) {
            $table->integer('company_id')->after('user_id');
            $table->integer('department_id')->after('user_id');
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
            $table->dropColumn('company_id');
            $table->dropColumn('department_id');
        });
    }
}
