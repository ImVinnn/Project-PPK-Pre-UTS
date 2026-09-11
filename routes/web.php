<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'facilities.index');
Route::view('/login', 'auth.login');
