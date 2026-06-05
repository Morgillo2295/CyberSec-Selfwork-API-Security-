<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookController;

Route::controller(AuthController::class)->group(function(){
    Route::post('register', 'register')->middleware('throttle:5,1');
    Route::post('login', 'login')->middleware('throttle:5,1');
    Route::post('passwordRecover', 'passwordRecovery')->middleware('throttle:5,1');
    // Route::get('/user/{id}','getUserInfo'); // UNSECURE
    // Route::put('/email-change',[AuthController::class,'updateEmail']); // UNSECURE

});

Route::middleware('auth:sanctum')->group(function () {
    Route::put('/email-change',[AuthController::class,'updateEmail'])->middleware('throttle:10,1');
    Route::get('/user/books',[BookController::class, 'showByUser']);
    Route::get('/user',[AuthController::class,'getUserInfo']); // SECURE
    Route::get('/books', [BookController::class, 'index'])->middleware('throttle:10,1');
    Route::get('/books/{id}', [BookController::class, 'show']);
    Route::post('/books', [BookController::class, 'store'])->middleware('throttle:20,1');
    Route::put('/books/{id}', [BookController::class, 'update'])->middleware('throttle:20,1');
    Route::delete('/books/{id}', [BookController::class, 'destroy'])->middleware('throttle:20,1');

});


