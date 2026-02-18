<?php

namespace App\Services;

class AIService
{
    private string $provider;
    private string $apiKey;
    private string $model;

    public function __construct()
    {
        $this->provider = $_ENV['AI_PROVIDER'] ?? 'anthropic';
        $this->apiKey = $this->provider === 'anthropic' 
            ? $_ENV['ANTHROPIC_API_KEY'] 
            : $_ENV['OPENAI_API_KEY'];
        $this->model = $this->provider === 'anthropic'
            ? ($_ENV['ANTHROPIC_MODEL'] ?? 'claude-sonnet-4-20250514')
            : ($_ENV['OPENAI_MODEL'] ?? 'gpt-4-turbo-preview');
    }

    public function chat(array $messages, ?string $systemPrompt = null): array
    {
        return match($this->provider) {
            'anthropic' => $this->chatWithClaude($messages, $systemPrompt),
            'openai' => $this->chatWithOpenAI($messages, $systemPrompt),
            default => throw new \Exception("Provider {$this->provider} no soportado")
        };
    }

    private function chatWithClaude(array $messages, ?string $systemPrompt = null): array
    {
        $payload = [
            'model' => $this->model,
            'max_tokens' => 1024,
            'messages' => $messages,
        ];

        if ($systemPrompt) {
            $payload['system'] = $systemPrompt;
        }

        $ch = curl_init('https://api.anthropic.com/v1/messages');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'x-api-key: ' . $this->apiKey,
                'anthropic-version: 2023-06-01',
                'content-type: application/json',
            ],
            CURLOPT_POSTFIELDS => json_encode($payload),
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            error_log("Claude API Error: " . $response);
            throw new \Exception("Error al comunicarse con Claude API: HTTP $httpCode");
        }

        $data = json_decode($response, true);

        if (!isset($data['content'][0]['text'])) {
            throw new \Exception("Respuesta inválida de Claude API");
        }

        return [
            'content' => $data['content'][0]['text'],
            'usage' => $data['usage'] ?? [],
            'model' => $data['model'] ?? $this->model,
        ];
    }

    private function chatWithOpenAI(array $messages, ?string $systemPrompt = null): array
    {
        $messagesPayload = $messages;

        if ($systemPrompt) {
            array_unshift($messagesPayload, [
                'role' => 'system',
                'content' => $systemPrompt
            ]);
        }

        $payload = [
            'model' => $this->model,
            'messages' => $messagesPayload,
            'max_tokens' => 1024,
            'temperature' => 0.7,
        ];

        $ch = curl_init('https://api.openai.com/v1/chat/completions');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->apiKey,
                'Content-Type: application/json',
            ],
            CURLOPT_POSTFIELDS => json_encode($payload),
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            error_log("OpenAI API Error: " . $response);
            throw new \Exception("Error al comunicarse con OpenAI API: HTTP $httpCode");
        }

        $data = json_decode($response, true);

        if (!isset($data['choices'][0]['message']['content'])) {
            throw new \Exception("Respuesta inválida de OpenAI API");
        }

        return [
            'content' => $data['choices'][0]['message']['content'],
            'usage' => $data['usage'] ?? [],
            'model' => $data['model'] ?? $this->model,
        ];
    }
}