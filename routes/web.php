<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\Controller;
Route::get('/', function () {
    return view('welcome');
});

Route::get('/search', [SearchController::class, 'search']);
Route::post('link', [SearchController::class, 'link']);
Route::get('/', [SearchController::class, 'index']);
