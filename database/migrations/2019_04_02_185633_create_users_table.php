<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name',80)->nullable();
            $table->string('email',80)->unique();
            $table->string('role',20)->nullable();
            $table->boolean('active')->default(true);
            $table->string('facebook_id', 64)->nullable();
            $table->string('google_id', 64)->nullable();
            $table->string('linkedin_id', 64)->nullable();
            $table->string('github_id', 64)->nullable();
            $table->string('twitter_id', 64)->nullable();
            $table->string('thumb_url',200)->nullable();
            $table->string('image_url',200)->nullable();
            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
    }
}
