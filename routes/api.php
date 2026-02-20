<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'message' => 'API Financiera',
        'endpoints' => [
            'POST /api/register' => 'Crear usuario (name, email, password, password_confirmation, initial_balance)',
            'POST /api/login' => 'Obtener token (email, password)',
            'GET /api/users' => 'Listar usuarios (requiere token)',
            'POST /api/transactions' => 'Crear transferencia (requiere token)',
        ],
        'docs' => 'REQUERIMIENTOS.md | API_README.md | docs/openapi.yaml',
    ]);
});

Route::get('register', function () {
    return response()->json([
        'message' => 'El registro es por POST, no por GET.',
        'howto' => 'Use Postman o similar: POST /api/register con Body JSON: { "name": "Tu nombre", "email": "tu@email.com", "password": "tu_password", "password_confirmation": "tu_password", "initial_balance": 0 }',
    ]);
})->name('api.register.get');

Route::get('login', function () {
    return response()->json([
        'message' => 'El login es por POST, no por GET.',
        'howto' => 'Use Postman o similar: POST /api/login con Body JSON { "email": "tu@email.com", "password": "tu_password" }',
    ]);
})->name('api.login.get');

Route::post('register', [AuthController::class, 'register'])->name('api.register');
Route::post('login', [AuthController::class, 'login'])->name('api.login');

Route::middleware('auth:sanctum')->group(function () {
    Route::post('logout', [AuthController::class, 'logout'])->name('api.logout');
    Route::get('me', [AuthController::class, 'me'])->name('api.me');

    Route::apiResource('users', UserController::class);

    Route::post('transactions', [TransactionController::class, 'store'])->name('api.transactions.store');
    Route::get('transactions', [TransactionController::class, 'index'])->name('api.transactions.index');
    Route::get('transactions/export/csv', [TransactionController::class, 'exportCsv'])->name('api.transactions.export.csv');
    Route::get('transactions/stats/total-by-sender', [TransactionController::class, 'statsTotalBySender'])->name('api.transactions.stats.total');
    Route::get('transactions/stats/average-by-user', [TransactionController::class, 'statsAverageByUser'])->name('api.transactions.stats.average');
});
