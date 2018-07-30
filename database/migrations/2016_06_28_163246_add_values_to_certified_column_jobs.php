<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddValuesToCertifiedColumnJobs extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('jobs', function ($table) {
            $table->enum('certified2', ['yes', 'no' ,'normal','both' , 'law' , 'health', 'n_law', 'n_health'])->nullable()->after('certified');
        });
        $jobs = \DTApi\Models\Job::all();

        foreach($jobs as $job)
        {
            $job->certified2 = $job->certified;
            $job->save();
        }
        Schema::table('jobs', function ($table) {
            $table->dropColumn('certified');
        });
        Schema::table('jobs', function ($table) {
            $table->enum('certified', ['yes', 'no' ,'normal','both' , 'law' , 'health', 'n_law', 'n_health'])->nullable()->after('gender');
        });
        $jobs = \DTApi\Models\Job::all();

        foreach($jobs as $job)
        {
            $job->certified = $job->certified2;
            $job->save();
        }
        Schema::table('jobs', function ($table) {
            $table->dropColumn('certified2');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
