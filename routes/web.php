<?php

use App\Http\Controllers\BookController;
use App\Http\Controllers\ReviewController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('books.index');
});


// Register resource routes for the BookController with only "index" and "show" actions.
Route::resource('books', BookController::class)
    ->only(['index', 'show'])
;

// Register nested resource routes for the ReviewController with "create", "store", and "show" actions.
Route::resource('books.reviews', ReviewController::class)
    ->scoped(["review" => 'id'])
    ->only(['create', 'store', 'show'])
;
