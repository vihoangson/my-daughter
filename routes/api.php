<?php

use App\Http\Controllers\RewardPunishmentController;
use App\Http\Controllers\ParentChildController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\KidController;
use App\Http\Controllers\KidRequestController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('auth/login', [AuthController::class, 'login']);
Route::middleware('auth:sanctum')->group(function(){
    Route::get('auth/me', [AuthController::class, 'me']);
    Route::post('auth/logout', [AuthController::class, 'logout']);
    Route::get('parent/kids', [ParentChildController::class, 'currentKids']);
    Route::post('parent/kids', [ParentChildController::class, 'storeKid']);
    Route::put('parent/kids/{kid}', [ParentChildController::class, 'updateKid']);
    Route::delete('parent/kids/{kid}', [ParentChildController::class, 'destroyKid']);
    Route::get('parent/kids/{kid}/details', [ParentChildController::class, 'kidDetails']);

    // Kid routes
    Route::middleware('kid')->prefix('kid')->group(function() {
        Route::get('profile', [KidController::class, 'profile']);
        Route::get('dashboard', [KidController::class, 'dashboard']);

        // Kid request routes
        Route::get('requests', [KidRequestController::class, 'index']);
        Route::post('requests', [KidRequestController::class, 'store']);
        Route::get('requests/{id}', [KidRequestController::class, 'show']);
    });
});

// Kid user routes
Route::middleware(['auth:sanctum', 'kid'])->group(function(){
    Route::get('kid/dashboard', [\App\Http\Controllers\KidUserController::class, 'dashboard']);
    Route::get('kid/profile', [\App\Http\Controllers\KidUserController::class, 'profile']);
});

Route::apiResource('reward-punishments', RewardPunishmentController::class);
Route::get('children', [RewardPunishmentController::class, 'children']);
// Parent-Kid management
Route::get('parents', [ParentChildController::class, 'parents']);
Route::get('kids', [ParentChildController::class, 'kids']);
Route::post('parents/{parent}/attach-kids', [ParentChildController::class, 'attachKids']);
Route::delete('parents/{parent}/kids/{kid}', [ParentChildController::class, 'detachKid']);

// Parent routes for kid requests
Route::prefix('parent')->group(function() {
    Route::get('requests/pending', [KidRequestController::class, 'parentPendingRequests']);
    Route::get('requests/all', [KidRequestController::class, 'parentAllRequests']);
    Route::put('requests/{id}/status', [KidRequestController::class, 'updateStatus']);
});
