<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\FileController;

use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class , 'index'])->name('Home');

Route::controller(FileController::class)->group(function(){
    Route::post('/file-details', 'showFile')->name('file.details');
    Route::post('/encrypt'     , 'encrypt' )->name('file.encrypt');
    Route::post('/decrypt'     , 'decrypt' )->name('file.decrypt');
});

