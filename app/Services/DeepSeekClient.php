<?php
namespace App\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;

class DeepSeekClient
{
    protected string $baseUrl;
    protected ?string $apiKey;
    protected string $model;

    public function __construct()
    {
        $cfg = config('services.deepseek', []);
        $this->baseUrl = rtrim((string)($cfg['base_url'] ?? 'https://api.deepseek.com'), '/');
        $this->apiKey = $cfg['api_key'] ?? null;
        if (!$this->apiKey) {
            // Backward compatibility with existing OPENAI_API_KEY usage
            $this->apiKey = env('OPENAI_API_KEY');
        }
        $this->model = (string)($cfg['model'] ?? 'deepseek-chat');
    }

    public function hasApiKey(): bool
    {
        return !empty($this->apiKey);
    }

    /**
     * Perform a chat completion request against DeepSeek API.
     *
     * @param array $messages  OpenAI-compatible messages array
     * @param array $options   Additional payload options like temperature, max_tokens
     * @return array  {success:bool, content:?string, status:?int, raw:?array, error:?string}
     */
    public function chatCompletions(array $messages, array $options = []): array
    {
        if (!$this->hasApiKey()) {
            return [
                'success' => false,
                'content' => null,
                'status' => null,
                'raw' => null,
                'error' => 'missing_api_key',
            ];
        }

        $payload = array_merge([
            'model' => $this->model,
            'messages' => $messages,
        ], $options);

        try {
            $response = Http::withToken($this->apiKey)
                ->acceptJson()
                ->asJson()
                ->timeout(15)
                ->connectTimeout(10)
                ->retry(1, 200)
                ->post($this->baseUrl . '/chat/completions', $payload);

            $status = $response->status();
            if ($response->successful()) {
                $json = $response->json();
                $content = Arr::get($json, 'choices.0.message.content');
                return [
                    'success' => is_string($content) && $content !== '',
                    'content' => is_string($content) ? $content : null,
                    'status' => $status,
                    'raw' => $json,
                    'error' => is_string($content) && $content !== '' ? null : 'empty_content',
                ];
            }

            return [
                'success' => false,
                'content' => null,
                'status' => $status,
                'raw' => $response->json(),
                'error' => 'http_' . $status,
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'content' => null,
                'status' => null,
                'raw' => null,
                'error' => 'exception:' . $e->getMessage(),
            ];
        }
    }
}
