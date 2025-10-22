<?php

use App\Http\Controllers\GridController;
use Illuminate\Support\Facades\Route;

Route::get('/', [GridController::class, 'show'])->name('home');
Route::put('/grid/{position}', [GridController::class, 'update'])->name('grid.update');
