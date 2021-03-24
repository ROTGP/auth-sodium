<?php

use Illuminate\Support\Facades\Route;
use ROTGP\AuthSodium\Http\Controllers\AuthSodiumController;

Route::get(
    '/' . config('authsodium.routes.email_verification'), [
        AuthSodiumController::class, 
        'verifyEmail'
    ])->name('authsodium.verify-email');