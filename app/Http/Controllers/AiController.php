<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Services\DeepSeekClient;

class AiController extends Controller
{
    // Suggest achievement using DeepSeek API with graceful fallback
    public function suggestAchievement(Request $request, DeepSeekClient $deepSeek)
    {
        $data = $request->validate([
            'prompt' => ['nullable','string','max:2000'],
        ]);

        $prompt = trim((string)($data['prompt'] ?? ''));
        $fallback = $this->fallbackSuggest($prompt);

        // If no API key configured, return fallback immediately
        if (!$deepSeek->hasApiKey()) {
            return response()->json($fallback);
        }

        // Build chat payload to request strict JSON output
        $messages = [
            [
                'role' => 'system',
                'content' => 'You are an API that outputs ONLY compact JSON. Do not include explanations. Respond with an object {"name":"...","category":"...","note":"..."}. Category must be one of: Reading, Math, Science, Coding, Puzzle, Music, Art, Sports, Chores, Language, General. Keep text concise and kid-friendly.'
            ],
            [
                'role' => 'user',
                'content' => ($prompt !== '' ? $prompt : 'Generate a generic kid achievement suggestion.')
            ],
        ];

        $resp = $deepSeek->chatCompletions($messages, [
            'temperature' => 0.7,
            'max_tokens' => 300,
        ]);

        if (!$resp['success']) {
            return response()->json($fallback);
        }

        $content = $resp['content'];
        if (!is_string($content) || $content === '') {
            return response()->json($fallback);
        }

        // Try parse JSON directly
        $parsed = json_decode($content, true);
        if (!is_array($parsed)) {
            // Attempt to extract JSON object from the content
            if (preg_match('/\{[\s\S]*}/', $content, $m)) {
                $parsed = json_decode($m[0], true);
            }
        }

        // Normalize and validate fields
        $name = isset($parsed['name']) && is_string($parsed['name']) ? trim($parsed['name']) : null;
        $category = isset($parsed['category']) && is_string($parsed['category']) ? trim($parsed['category']) : null;
        $note = isset($parsed['note']) && is_string($parsed['note']) ? trim($parsed['note']) : null;

        $allowedCats = ['Reading','Math','Science','Coding','Puzzle','Music','Art','Sports','Chores','Language','General'];
        if ($category && !in_array($category, $allowedCats)) {
            $category = 'General';
        }

        $result = [
            'name' => $name ?: $fallback['name'],
            'category' => $category ?: $fallback['category'],
            'note' => $note ?: $fallback['note'],
        ];

        return response()->json($result);
    }

    protected function fallbackSuggest(string $prompt): array
    {
        $lower = Str::lower($prompt);
        $category = 'General';
        $map = [
            'read' => 'Reading', 'book' => 'Reading', 'novel' => 'Reading',
            'math' => 'Math', 'algebra' => 'Math', 'geometry' => 'Math',
            'science' => 'Science', 'stem' => 'Science', 'experiment' => 'Science',
            'code' => 'Coding', 'program' => 'Coding', 'scratch' => 'Coding',
            'puzzle' => 'Puzzle', 'chess' => 'Puzzle', 'logic' => 'Puzzle',
            'music' => 'Music', 'piano' => 'Music', 'guitar' => 'Music', 'sing' => 'Music',
            'art' => 'Art', 'draw' => 'Art', 'paint' => 'Art',
            'sport' => 'Sports', 'soccer' => 'Sports', 'basketball' => 'Sports', 'run' => 'Sports',
            'clean' => 'Chores', 'chores' => 'Chores', 'laundry' => 'Chores', 'dish' => 'Chores',
            'language' => 'Language', 'english' => 'Language', 'vietnamese' => 'Language', 'spanish' => 'Language',
        ];
        foreach ($map as $key => $val) {
            if (Str::contains($lower, $key)) { $category = $val; break; }
        }

        $base = $prompt !== '' ? ucfirst(Str::of($prompt)->replaceMatches('/\s+/', ' ')) : null;
        $name = $base ? Str::limit($base, 60) : match ($category) {
            'Reading' => 'Weekly Reading Challenge',
            'Math' => 'Daily Math Practice',
            'Science' => 'Home Science Explorer',
            'Coding' => 'Mini Coding Quest',
            'Puzzle' => 'Logic Puzzle Streak',
            'Music' => 'Practice Your Instrument',
            'Art' => 'Creative Art Time',
            'Sports' => 'Active Sports Session',
            'Chores' => 'Help Around the House',
            'Language' => 'Language Learning Goal',
            default => 'New Achievement',
        };

        $noteTemplates = [
            'Reading' => 'Read at least 20–30 minutes each day this week. Track pages and share your favorite part.',
            'Math' => 'Complete a short set of practice problems daily. Focus on accuracy and neat work.',
            'Science' => 'Do one simple experiment or watch a science video and summarize what you learned.',
            'Coding' => 'Spend 20 minutes coding (e.g., Scratch) and demo what you built at the end of the week.',
            'Puzzle' => 'Solve one logic puzzle or chess tactic per day to build problem‑solving skills.',
            'Music' => 'Practice your instrument for 15–20 minutes daily. Record one piece at week’s end.',
            'Art' => 'Create a drawing or craft 3 times this week. Share the final piece on Sunday.',
            'Sports' => 'Be active for 30 minutes at least 4 days this week. Note activity and duration.',
            'Chores' => 'Complete assigned chores properly without reminders. Parents verify completion.',
            'Language' => 'Learn 10 new words and practice speaking daily. Share a short sentence for each.',
            'General' => 'Work on this goal consistently this week. Parents can adjust points/reward accordingly.',
        ];
        $note = $noteTemplates[$category] ?? $noteTemplates['General'];

        return [
            'name' => $name,
            'category' => $category,
            'note' => $note,
        ];
    }

    // General AI answer endpoint
    public function answer(Request $request, DeepSeekClient $deepSeek)
    {
        $data = $request->validate([
            'question' => ['required','string','max:4000'],
        ]);

        $question = trim((string)$data['question']);
        if ($question === '') {
            return response()->json(['answer' => 'Please enter a question.'], 422);
        }

        // Fallback if no API key
        if (!$deepSeek->hasApiKey()) {
            return response()->json([
                'answer' => 'AI service is not configured. Here is a basic response to your question: "' . Str::limit($question, 200) . '". Consider refining the question or enabling AI for detailed answers.'
            ]);
        }

        $messages = [
            ['role' => 'system', 'content' => 'You are a helpful assistant. Answer concisely, in the same language as the user question. Keep it under 120 words unless strictly necessary.'],
            ['role' => 'user', 'content' => $question],
        ];

        $resp = $deepSeek->chatCompletions($messages, [
            'temperature' => 0.5,
            'max_tokens' => 400,
        ]);

        if (!$resp['success'] || !is_string($resp['content']) || $resp['content'] === '') {
            return response()->json([
                'answer' => 'Sorry, the AI could not generate an answer right now. Please try again.'
            ], 200);
        }

        return response()->json([
            'answer' => trim($resp['content'])
        ]);
    }
}
