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
        // @TODO
        // if (strlen((string) PHP_INT_MAX) < 19 && config('authsodium.use_milliseconds', true) === true) {
        //     // Looks like we're running on a 32-bit build of PHP.  This could cause problems because some of the numbers
        //     // we use (file sizes, quota, etc) can be larger than 32-bit ints can handle.
        //     throw new \Exception("The Dropbox SDK uses 64-bit integers, but it looks like we're running on a version of PHP that doesn't support 64-bit integers (PHP_INT_MAX=" . ((string) PHP_INT_MAX) . ").  Library: \"" . __FILE__ . "\"");
        // }

        Schema::create('nonces', function (Blueprint $table) {  

            $userModel = AuthSodium::authUserModel();
            $userForeignKey = $userModel->getForeignKey();
            $usersTable = $userModel->getTable();
            $userKeyName = $userModel->getKeyName();
            
            $table->id();
            $table->string('value', config('authsodium.schema.nonce.length', 44));
            
            /**
             * The timestamp for when the request was
             * made (and the nonce was used), which is
             * assumed to be in the timezone of
             */
            // $table->timestamp('timestamp');
            // @TODO $table->bigInteger
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
