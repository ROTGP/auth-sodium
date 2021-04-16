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
        authSodium()->validateConfig();

        Schema::create('nonces', function (Blueprint $table) {  

            $userModel = authSodium()->authUserModel();
            $userForeignKey = $userModel->getForeignKey();
            $usersTable = $userModel->getTable();
            $userKeyName = $userModel->getKeyName();
            
            $table->id();
            $table->string('value', authSodium()->getNonceMaxLength());
            $table->unsignedBigInteger('timestamp');

            // foreign key for user
            $table->unsignedBigInteger($userForeignKey);
            $table
                ->foreign($userForeignKey)
                ->references($userKeyName)
                ->on($usersTable)
                ->onUpdate('cascade')
                ->onDelete('cascade');
            
            $uniqueConstraints = ['value', $userForeignKey];
            if (config('authsodium.schema.nonce_unique_per_timestamp', false)) {
                $uniqueConstraints[] = 'timestamp';
            }
            $table->unique($uniqueConstraints);
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
    }
}
