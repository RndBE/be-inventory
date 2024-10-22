<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLogActivitiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('log_activities', function (Blueprint $table) {
            $table->id();
            $table->string('description'); // Description of the activity
            $table->unsignedBigInteger('user_id')->nullable(); // User who performed the activity
            $table->string('method')->nullable(); // HTTP method (GET, POST, etc.)
            $table->string('ip_address')->nullable(); // IP address
            $table->string('url'); // URL that was accessed
            $table->text('data')->nullable(); // Any additional data (optional)
            $table->timestamps();

            // Optional foreign key constraint to the users table
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('log_activities');
    }
}
