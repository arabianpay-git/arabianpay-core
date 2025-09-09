<?php

use App\Http\Controllers\ApiTesterController;
use Illuminate\Support\Facades\Route;

Route::get('/api-tester', [ApiTesterController::class, 'index'])->name('api.tester');
Route::post('/api-tester/send', [ApiTesterController::class, 'send'])->name('api.tester.send');
