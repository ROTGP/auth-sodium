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
        
        // dd(['userForeignKey' => $userForeignKey, 'usersTable' => $usersTable, 'userKeyName' => $userKeyName]);

        Schema::create('nonces', function (Blueprint $table) {  

            $userModel = authSodium()->authUserModel();
            $userForeignKey = $userModel->getForeignKey();
            $usersTable = $userModel->getTable();
            $userKeyName = $userModel->getKeyName();

            $table->id();
            $table->string('value', 44); // 32 base64 encoded bytes

            // foreign key for user
            $table->unsignedBigInteger($userForeignKey);
            $table
                ->foreign($userForeignKey)
                ->references($userKeyName)
                ->on($usersTable)
                ->onUpdate('cascade')
                ->onDelete('cascade');

            $table->unique(['value', $userForeignKey]);
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
        Schema::dropIfExists('nonces');
    }
}
