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
        // @TODO why are we doing this? 
        authSodium()->validateConfig();

        $userModel = authSodium()->authUserModel();
        $userForeignKey = $userModel->getForeignKey();
        $usersTable = $userModel->getTable();
        $userKeyName = $userModel->getKeyName();

        Schema::create('authsodium_nonces', function (Blueprint $table) use ($userForeignKey, $usersTable, $userKeyName) {
            
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

        Schema::create('authsodium_throttles', function (Blueprint $table)  use ($userForeignKey, $usersTable, $userKeyName) {
            
            $table->id();

            // foreign key for user
            $table->unsignedBigInteger($userForeignKey);
            $table
                ->foreign($userForeignKey)
                ->references($userKeyName)
                ->on($usersTable)
                ->onUpdate('cascade')
                ->onDelete('cascade');
            
            $table->string('ip_address', 45);
            $table->integer('attempts')->default(0);
            $table->timestamp('try_again')->nullable();
            $table->boolean('blocked');

            $table->unique([$userForeignKey, 'ip_address']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('authsodium_throttles');
        Schema::dropIfExists('authsodium_nonces');
    }
}
