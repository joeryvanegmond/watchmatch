<?php

use App\Http\Controllers\JobController;
use Illuminate\Support\Facades\Route;

Route::get('/similizator', [JobController::class, 'similizator']);
Route::get('/imagenator', [JobController::class, 'imagenator']);
Route::get('/imagekit', [JobController::class, 'imagekit']);
Route::get('/garbagecleaner', [JobController::class, 'garbageCleaner']);

