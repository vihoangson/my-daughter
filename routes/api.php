<?php

use App\Http\Controllers\RewardPunishmentController;
use App\Http\Controllers\ParentChildController;
use App\Http\Controllers\AuthController;
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
});

Route::apiResource('reward-punishments', RewardPunishmentController::class);
Route::get('children', [RewardPunishmentController::class, 'children']);
// Parent-Kid management
Route::get('parents', [ParentChildController::class, 'parents']);
Route::get('kids', [ParentChildController::class, 'kids']);
Route::post('parents/{parent}/attach-kids', [ParentChildController::class, 'attachKids']);
Route::delete('parents/{parent}/kids/{kid}', [ParentChildController::class, 'detachKid']);
