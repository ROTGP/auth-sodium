<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAuthSodiumTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->string('public_key', 44);
            $table->timestamp('verified')->nullable();
            $table->boolean('enabled')->default(true);
            $table->timestamps();
        });

        Schema::create('email_verifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onUpdate('cascade')->onDelete('cascade')->unique();
            $table->string('email')->unique();
            $table->string('code', 32)->unique();
            $table->timestamps();
        });

        Schema::create('nonces', function (Blueprint $table) {  
            $table->id();
            $table->string('value', 24)->unique(); // 32 base64 encoded bytes
            $table->timestamp('created_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('nonces');
        Schema::dropIfExists('email_verifications');
        Schema::dropIfExists('users');
    }
}
