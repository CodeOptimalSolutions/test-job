<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateJobsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('jobs', function (Blueprint $table) {
            $table->increments('id');
            $table->string('description');
            $table->integer('from_language_id')->unsigned();
            $table->integer('to_language_id')->unsigned();
            $table->string('duration');
            $table->integer('user_id')->unsigned();
            $table->enum('status', ['pending', 'assigned' ,'started','withdrawbefore24','withdrawafter24' , 'completed' , 'timedout', 'compensation', 'not_carried_out_translator', 'not_carried_out_customer' ])->default('pending');
            $table->enum('immediate', ['yes', 'no'])->default('no');
            $table->enum('gender', ['male', 'female'])->nullable();
            $table->enum('certified', ['yes', 'no', 'normal' , 'both'])->nullable();
            $table->enum('job_type', ['paid', 'unpaid'])->nullable();
            $table->timestamp('due')->nullable();
            $table->tinyInteger('emailsent')->default('0');
            $table->tinyInteger('emailsenttovirpal')->default('0');
            $table->tinyInteger('endedemail')->default('0');
            $table->timestamp('withdraw_at');
            $table->timestamp('end_at');
            $table->timestamp('b_created_at');
            $table->enum('flagged', ['yes', 'no'])->default('no');;
            $table->string('user_email');
            $table->enum('customer_phone_type', ['yes', 'no'])->default('no');
            $table->enum('customer_physical_type', ['yes', 'no'])->default('no');
            $table->dateTime('immediate_check_date');
            $table->tinyInteger('cust_16_hour_email');
            $table->tinyInteger('cust_48_hour_email');
            $table->integer('specific_transaltor')->nullable();
            $table->text('admin_comments');
            $table->string('session_time', 500);
            $table->tinyInteger('sended_push_again')->default('0');
            $table->string('address', 200);
            $table->text('instructions', 200);
            $table->string('town', 200);
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('from_language_id')->references('id')->on('languages')->onDelete('cascade');
            $table->foreign('to_language_id')->references('id')->on('languages')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('jobs');
    }
}
