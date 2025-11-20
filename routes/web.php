<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\Controller;
use App\Http\Controllers\WatchController;

Route::get('/search', [SearchController::class, 'search']);
Route::post('link', [SearchController::class, 'link']);
Route::get('/', [WatchController::class, 'index']);
Route::get('/watches', [SearchController::class, 'getWatches']);
Route::get('/similarities', [SearchController::class, 'findSimilarities']);
Route::get('/watch/find', [WatchController::class, 'findAndShow']);
Route::resource('watch', WatchController::class);
