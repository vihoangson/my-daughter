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
use App\Http\Controllers\FamilyController; // added
use App\Http\Controllers\AiController; // added
use App\Http\Controllers\AdminController; // added
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

    // AI utilities
    Route::post('ai/suggest-achievement', [AiController::class, 'suggestAchievement']);
    Route::post('ai/answer', [AiController::class, 'answer']); // new: general Q&A endpoint

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
        // Alias to match frontend expectation (/api/parent/kids/{kid}/points/history)
        Route::get('kids/{kid}/points/history', [RewardPunishmentController::class, 'kidPointsHistory']);
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
        Route::post('achievements/{achievement}', [AchievementController::class, 'update']); // allow POST for multipart update
        Route::delete('achievements/{achievement}', [AchievementController::class, 'destroy']);
        Route::post('achievements/{achievement}/toggle-kid/{kid}', [AchievementController::class, 'toggleKid']);

        // Reward items CRUD
        Route::get('rewards', [\App\Http\Controllers\RewardItemController::class,'index']);
        Route::post('rewards', [\App\Http\Controllers\RewardItemController::class,'store']);
        Route::put('rewards/{reward}', [\App\Http\Controllers\RewardItemController::class,'update']);
        Route::delete('rewards/{reward}', [\App\Http\Controllers\RewardItemController::class,'destroy']);
        Route::post('rewards/{reward}/toggle', [\App\Http\Controllers\RewardItemController::class,'toggleActive']);

        // Parent stock management
        Route::get('stocks', [StockController::class,'parentIndex']);
        Route::post('stocks/refresh', [StockController::class,'parentRefresh']);
        Route::get('stocks/{stock}/prices', [StockController::class,'parentPrices']);
        Route::post('stocks/{stock}/adjust', [StockController::class,'parentAdjust']);
        Route::get('kids/{kid}/stocks/summary', [StockController::class,'parentKidSummary']);
        Route::get('kids/{kid}/stocks/holdings', [StockController::class,'parentKidHoldings']);
        Route::get('kids/{kid}/stocks/trades', [StockController::class,'parentKidTrades']);

        // Loan (Bank) management
        Route::get('loans', [\App\Http\Controllers\LoanController::class,'parentIndex']);
        Route::post('loans', [\App\Http\Controllers\LoanController::class,'parentStore']);
        Route::get('loans/{loan}', [\App\Http\Controllers\LoanController::class,'parentShow']);
        Route::post('loans/{loan}/accrue', [\App\Http\Controllers\LoanController::class,'parentAccrue']);

        // Family management
        Route::get('family', [FamilyController::class, 'show']);
        Route::put('family', [FamilyController::class, 'update']);
        Route::post('family/regenerate-invite', [FamilyController::class, 'regenerateInvite']);
        Route::post('family/members', [FamilyController::class, 'addMember']);
        Route::delete('family/members/{member}', [FamilyController::class, 'removeMember']);

        // Family Blog routes
        Route::get('family/blog-posts', [\App\Http\Controllers\BlogFamilyController::class,'index']);
        Route::post('family/blog-posts', [\App\Http\Controllers\BlogFamilyController::class,'store']);
        Route::get('family/blog-posts/{post}', [\App\Http\Controllers\BlogFamilyController::class,'show']);
        Route::put('family/blog-posts/{post}', [\App\Http\Controllers\BlogFamilyController::class,'update']);
        Route::delete('family/blog-posts/{post}', [\App\Http\Controllers\BlogFamilyController::class,'destroy']);
        // Family Blog image upload
        Route::post('family/blog-images', [\App\Http\Controllers\BlogMediaController::class,'store']);
    });

    // Kid routes
    Route::middleware('kid')->prefix('kid')->group(function() {
        Route::get('profile', [KidController::class, 'profile']);
        Route::get('dashboard', [KidController::class, 'dashboard']);
        Route::put('profile', [KidController::class, 'updateProfile']);
        Route::post('profile/update', [KidController::class, 'updateProfile']); // Add POST route for file upload
        Route::put('change-password', [KidController::class, 'changePassword']);
        Route::get('points', [KidController::class, 'points']); // added points history endpoint

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

        // Loan endpoints for kid
        Route::get('loans', [\App\Http\Controllers\LoanController::class,'kidIndex']);
        Route::post('loans/{loan}/repay', [\App\Http\Controllers\LoanController::class,'kidRepay']);
        Route::get('loans/history', [\App\Http\Controllers\LoanController::class,'kidHistory']);
        Route::post('loans', [\App\Http\Controllers\LoanController::class,'kidStore']);
        // Bank alias paths expected by frontend BankLoan.vue
        Route::prefix('bank')->group(function(){
            Route::get('loans', [\App\Http\Controllers\LoanController::class,'kidIndex']);
            Route::post('loans', [\App\Http\Controllers\LoanController::class,'kidStore']);
            Route::get('loans/history', [\App\Http\Controllers\LoanController::class,'kidHistory']);
            Route::post('loans/{loan}/repay', [\App\Http\Controllers\LoanController::class,'kidRepay']);
        });
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
Route::middleware(['auth:sanctum','parent'])->prefix('parent')->group(function() {
    Route::get('requests/pending', [KidRequestController::class, 'parentPendingRequests']);
    Route::get('requests/all', [KidRequestController::class, 'parentAllRequests']);
    Route::put('requests/{id}/status', [KidRequestController::class, 'updateStatus']);
});

// Public blog post view route
Route::get('public/blog-posts/{post}', [\App\Http\Controllers\BlogFamilyController::class,'publicShow']);

// Alias: parent achievements without /parent prefix
Route::middleware('auth:sanctum')->group(function(){
    Route::middleware('parent')->group(function() {
        Route::get('achievements', [AchievementController::class, 'parentIndex']);
        Route::post('achievements', [AchievementController::class, 'store']);
        Route::put('achievements/{achievement}', [AchievementController::class, 'update']);
        Route::post('achievements/{achievement}', [AchievementController::class, 'update']); // allow POST for multipart update
        Route::delete('achievements/{achievement}', [AchievementController::class, 'destroy']);
        Route::post('achievements/{achievement}/toggle-kid/{kid}', [AchievementController::class, 'toggleKid']);
    });
});

// Admin routes
Route::middleware('auth:sanctum')->group(function(){
    // Admin overview stats
    Route::get('admin/system-stats', [AdminController::class, 'systemStats']);
});
