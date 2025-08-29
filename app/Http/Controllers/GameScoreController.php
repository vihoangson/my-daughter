<?php
namespace App\Http\Controllers;

use App\Models\GameScore;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GameScoreController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'game_id' => 'required|string|max:100',
            'score' => 'required|integer|min:0'
        ]);

        $user = Auth::user();
        if(!$user){
            return response()->json(['message' => 'Unauthenticated'], 401);
        }
        $record = GameScore::create([
            'user_id' => $user->id,
            'game_id' => $data['game_id'],
            'score' => $data['score']
        ]);

        // Optionally return best score summary
        $best = GameScore::where('user_id', $user->id)
            ->where('game_id', $data['game_id'])
            ->max('score');

        return response()->json([
            'status' => 'ok',
            'saved' => $record,
            'best_score' => $best
        ]);
    }
}
