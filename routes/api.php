<?php

use App\Http\Controllers\RewardPunishmentController;
use App\Http\Controllers\ParentChildController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\KidController;
use App\Http\Controllers\KidRequestController;
use App\Http\Controllers\GameScoreController;
use App\Http\Controllers\StockController; // added
use App\Http\Controllers\AnimalQuizController; // added
use App\Http\Controllers\AchievementController; // added
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
Route::post('auth/simple-login', [AuthController::class, 'simpleLogin']);
Route::middleware('auth:sanctum')->group(function(){
    Route::get('auth/me', [AuthController::class, 'me']);
    Route::post('auth/logout', [AuthController::class, 'logout']);

    // Parent routes
    Route::middleware('parent')->prefix('parent')->group(function() {
        Route::get('profile', [ParentChildController::class, 'profile']);
        Route::post('profile', [ParentChildController::class, 'updateProfile']); // Add POST route for profile updates
        Route::get('kids', [ParentChildController::class, 'currentKids']);
        Route::post('kids', [ParentChildController::class, 'storeKid']);
        Route::put('kids/{kid}', [ParentChildController::class, 'updateKid']);
        Route::delete('kids/{kid}', [ParentChildController::class, 'destroyKid']);
        Route::get('kids/{kid}/details', [ParentChildController::class, 'kidDetails']);

        // Request management routes
        Route::get('requests', [KidRequestController::class, 'parentRequests']);
        Route::get('kid/{kid}/requests', [KidRequestController::class, 'kidRequests']);
        Route::put('requests/{id}/process', [KidRequestController::class, 'processRequest']);
        Route::put('requests/{id}/complete', [KidRequestController::class, 'completeRequest']);

        // Points management routes
        Route::get('kid/{kid}/points-history', [RewardPunishmentController::class, 'kidPointsHistory']);
        Route::post('kid/{kid}/points', [RewardPunishmentController::class, 'addPoints']);
        // Add route for kids/{kid}/points to handle the current client requests
        Route::post('kids/{kid}/points', [RewardPunishmentController::class, 'addPoints']);

        // Acoin funding route
        Route::post('kids/{kid}/acoin-fund', [ParentChildController::class, 'fundAcoin']);
        Route::get('kids/{kid}/acoin-transactions', [ParentChildController::class, 'acoinTransactions']);

        // Achievement routes (parent)
        Route::get('achievements', [AchievementController::class, 'parentIndex']);
        Route::post('achievements', [AchievementController::class, 'store']);
        Route::put('achievements/{achievement}', [AchievementController::class, 'update']);
        Route::delete('achievements/{achievement}', [AchievementController::class, 'destroy']);
        Route::post('achievements/{achievement}/toggle-kid/{kid}', [AchievementController::class, 'toggleKid']);

        // Reward items CRUD
        Route::get('rewards', [\App\Http\Controllers\RewardItemController::class,'index']);
        Route::post('rewards', [\App\Http\Controllers\RewardItemController::class,'store']);
        Route::put('rewards/{reward}', [\App\Http\Controllers\RewardItemController::class,'update']);
        Route::delete('rewards/{reward}', [\App\Http\Controllers\RewardItemController::class,'destroy']);
        Route::post('rewards/{reward}/toggle', [\App\Http\Controllers\RewardItemController::class,'toggleActive']);
    });

    // Kid routes
    Route::middleware('kid')->prefix('kid')->group(function() {
        Route::get('profile', [KidController::class, 'profile']);
        Route::get('dashboard', [KidController::class, 'dashboard']);
        Route::put('profile', [KidController::class, 'updateProfile']);
        Route::post('profile/update', [KidController::class, 'updateProfile']); // Add POST route for file upload
        Route::put('change-password', [KidController::class, 'changePassword']);

        // Kid request routes
        Route::get('requests', [KidRequestController::class, 'index']);
        Route::post('requests', [KidRequestController::class, 'store']);
        Route::get('requests/{id}', [KidRequestController::class, 'show']);
        Route::post('requests/{id}/classify', [KidRequestController::class, 'classify']); // moved here so path = /api/kid/requests/{id}/classify

        // Game score routes
        Route::post('game-scores', [GameScoreController::class, 'store']);

        // Stock trading routes
        Route::get('stocks', [StockController::class, 'index']);
        Route::get('stocks/holdings', [StockController::class, 'holdings']);
        Route::get('stocks/trades', [StockController::class, 'trades']); // added
        Route::get('stocks/summary', [StockController::class, 'summary']); // added
        Route::post('stocks/buy', [StockController::class, 'buy']);
        Route::post('stocks/sell', [StockController::class, 'sell']);
        Route::post('stocks/refresh', [StockController::class, 'refresh']);
        Route::get('stocks/{stock}/prices', [StockController::class, 'prices']); // price history
        // Animal quiz
        Route::get('animal-quiz/questions', [AnimalQuizController::class, 'questions']);

        // Kid achievements list
        Route::get('achievements', [AchievementController::class, 'kidIndex']);
        Route::post('achievements/{achievement}/kid-note', [AchievementController::class, 'updateKidNote']);
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
