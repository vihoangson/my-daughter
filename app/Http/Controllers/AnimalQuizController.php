<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AnimalQuizQuestion;

class AnimalQuizController extends Controller
{
    public function questions(Request $request)
    {
        $mode = $request->query('mode','mixed');
        $limit = min(50, max(1, (int)$request->query('limit', 20)));

        $query = AnimalQuizQuestion::query();
        if ($mode !== 'mixed') {
            $query->where('difficulty', $mode);
        }
        // Get all, shuffle in memory (portable across DB drivers) then slice
        $all = $query->get()->all();
        shuffle($all);
        $slice = array_slice($all, 0, $limit);

        return response()->json([
            'mode' => $mode,
            'count' => count($slice),
            'questions' => array_map(function($q){
                return [
                    'id' => $q->id,
                    'difficulty' => $q->difficulty,
                    'topic' => $q->topic,
                    'text' => $q->text,
                    'options' => $q->options,
                    'correct' => $q->correct,
                    'explanation' => $q->explanation,
                ];
            }, $slice)
        ]);
    }
}

