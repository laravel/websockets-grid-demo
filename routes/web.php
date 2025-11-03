<?php

use App\Http\Controllers\GridController;
use Illuminate\Support\Facades\Route;

Route::get('/', [GridController::class, 'show'])->name('home');
Route::get('/api/user-count', [GridController::class, 'getUserCount'])->name('api.user-count');
Route::put('/grid/{position}', [GridController::class, 'update'])->name('grid.update');
Route::delete('/grid/{position}/clear', [GridController::class, 'clear'])->name('grid.clear');
