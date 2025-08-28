<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RewardPunishmentController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// New homepage route
Route::get('/', function () {
    return view('homepage');
});

// Changed parent dashboard route
Route::get('/user-parent', function () {
    return view('home');
});

Route::view('/login', 'home');

Route::get('/{any}', function () {
    return view('home');
})->where('any', '^(?!reward-punishments|user-parent).*$');

Route::resource('reward-punishments', RewardPunishmentController::class);

Route::get('/test-avatar', function () {
    $user = \App\Models\User::where('type', 'child')->first();
    if ($user && $user->avatar) {
        return [
            'avatar_path' => $user->avatar,
            'avatar_url' => $user->avatar_url,
            'asset_url' => asset('storage/' . $user->avatar),
            'storage_url' => \Storage::disk('public')->url($user->avatar),
            'app_url' => config('app.url'),
            'file_exists' => file_exists(public_path('storage/' . $user->avatar))
        ];
    }
    return ['message' => 'No user with avatar found'];
});
