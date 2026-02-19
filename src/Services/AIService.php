<?php

namespace App\Services;

class AIService
{
    private string $provider;
    private string $apiKey;
    private string $model;
    private string $baseUrl;

    public function __construct()
    {
        $this->provider = $_ENV['AI_PROVIDER'] ?? 'zai';
        
        $this->apiKey = match($this->provider) {
            'zai' => $_ENV['ZAI_API_KEY'] ?? '',
            'groq' => $_ENV['GROQ_API_KEY'] ?? '',
            'gemini' => $_ENV['GEMINI_API_KEY'] ?? '',
            'anthropic' => $_ENV['ANTHROPIC_API_KEY'] ?? '',
            'openai' => $_ENV['OPENAI_API_KEY'] ?? '',
            default => ''
        };
        
        $this->model = match($this->provider) {
            'zai' => $_ENV['ZAI_MODEL'] ?? 'GLM-4.5-Flash',
            'groq' => $_ENV['GROQ_MODEL'] ?? 'llama-3.3-70b-versatile',
            'gemini' => $_ENV['GEMINI_MODEL'] ?? 'gemini-1.5-flash',
            'anthropic' => $_ENV['ANTHROPIC_MODEL'] ?? 'claude-sonnet-4-20250514',
            'openai' => $_ENV['OPENAI_MODEL'] ?? 'gpt-4-turbo-preview',
            default => ''
        };

        $this->baseUrl = match($this->provider) {
            'zai' => $_ENV['ZAI_BASE_URL'] ?? 'https://api.z.ai/api/paas/v4',
            default => ''
        };
    }

    public function chat(array $messages, ?string $systemPrompt = null): array
    {
        return match($this->provider) {
            'zai' => $this->chatWithZai($messages, $systemPrompt),
            'groq' => $this->chatWithGroq($messages, $systemPrompt),
            'gemini' => $this->chatWithGemini($messages, $systemPrompt),
            'anthropic' => $this->chatWithClaude($messages, $systemPrompt),
            'openai' => $this->chatWithOpenAI($messages, $systemPrompt),
            default => throw new \Exception("Provider {$this->provider} no soportado")
        };
    }

    private function chatWithZai(array $messages, ?string $systemPrompt = null): array
    {
        // Z.ai usa formato compatible con OpenAI
        $messagesPayload = $messages;

        // Si hay system prompt, agregarlo al inicio
        if ($systemPrompt) {
            array_unshift($messagesPayload, [
                'role' => 'system',
                'content' => $systemPrompt
            ]);
        }

        $payload = [
            'model' => $this->model,
            'messages' => $messagesPayload,
            'temperature' => 0.7,
        ];

        $endpoint = rtrim($this->baseUrl, '/') . '/chat/completions';

        $ch = curl_init($endpoint);
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
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            error_log("Z.ai cURL Error: " . $curlError);
            throw new \Exception("Error de conexión con Z.ai API: " . $curlError);
        }

        if ($httpCode !== 200) {
            error_log("Z.ai API Error (HTTP $httpCode): " . $response);
            throw new \Exception("Error al comunicarse con Z.ai API: HTTP $httpCode");
        }

        $data = json_decode($response, true);

        // Verificar el formato de respuesta de Z.ai
        if (!isset($data['choices'][0]['message']['content'])) {
            error_log("Z.ai unexpected response: " . json_encode($data));
            throw new \Exception("Formato de respuesta inválido de Z.ai API");
        }

        return [
            'content' => $data['choices'][0]['message']['content'],
            'usage' => $data['usage'] ?? [],
            'model' => $data['model'] ?? $this->model,
        ];
    }

    private function chatWithGroq(array $messages, ?string $systemPrompt = null): array
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

        $ch = curl_init('https://api.groq.com/openai/v1/chat/completions');
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
            error_log("Groq API Error: " . $response);
            throw new \Exception("Error al comunicarse con Groq API: HTTP $httpCode");
        }

        $data = json_decode($response, true);

        if (!isset($data['choices'][0]['message']['content'])) {
            throw new \Exception("Respuesta inválida de Groq API");
        }

        return [
            'content' => $data['choices'][0]['message']['content'],
            'usage' => $data['usage'] ?? [],
            'model' => $data['model'] ?? $this->model,
        ];
    }

    private function chatWithGemini(array $messages, ?string $systemPrompt = null): array
    {
        $contents = [];
        
        if ($systemPrompt) {
            $contents[] = [
                'role' => 'user',
                'parts' => [['text' => "Instrucciones del sistema: $systemPrompt"]]
            ];
            $contents[] = [
                'role' => 'model',
                'parts' => [['text' => 'Entendido. Seguiré estas instrucciones.']]
            ];
        }

        foreach ($messages as $message) {
            $role = $message['role'] === 'assistant' ? 'model' : 'user';
            $contents[] = [
                'role' => $role,
                'parts' => [['text' => $message['content']]]
            ];
        }

        $payload = [
            'contents' => $contents,
            'generationConfig' => [
                'temperature' => 0.7,
                'maxOutputTokens' => 1024,
            ]
        ];

        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent?key={$this->apiKey}";

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
            ],
            CURLOPT_POSTFIELDS => json_encode($payload),
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            error_log("Gemini API Error: " . $response);
            throw new \Exception("Error al comunicarse con Gemini API: HTTP $httpCode");
        }

        $data = json_decode($response, true);

        if (!isset($data['candidates'][0]['content']['parts'][0]['text'])) {
            throw new \Exception("Respuesta inválida de Gemini API");
        }

        return [
            'content' => $data['candidates'][0]['content']['parts'][0]['text'],
            'usage' => $data['usageMetadata'] ?? [],
            'model' => $this->model,
        ];
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