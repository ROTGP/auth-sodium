<?php

use Illuminate\Support\Facades\Route;
use ROTGP\AuthSodium\Http\Controllers\AuthSodiumController;

Route::get(config('authsodium.routes.validate'), [AuthSodiumController::class, 'validate']);