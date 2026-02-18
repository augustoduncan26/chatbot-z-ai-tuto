<?php

//require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../../src/bootstrap.php';

use App\Models\Conversation;
use App\Models\Message;
use App\Services\AIService;
use Dotenv\Dotenv;

// Cargar variables de entorno
$dotenv = Dotenv::createImmutable(__DIR__ . '/../..');
$dotenv->load();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (empty($input['session_id']) || empty($input['message'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Datos incompletos']);
        exit;
    }
    
    $conversationModel = new Conversation();
    $messageModel = new Message();
    $aiService = new AIService();
    
    $conversation = $conversationModel->findBySessionId($input['session_id']);
    
    if (!$conversation) {
        http_response_code(404);
        echo json_encode(['error' => 'Conversación no encontrada']);
        exit;
    }
    
    // Guardar mensaje del usuario
    $messageModel->create([
        'conversation_id' => $conversation['id'],
        'role' => 'user',
        'content' => $input['message'],
    ]);
    
    // Obtener historial
    $history = $conversationModel->getHistory($conversation['id'], 10);
    
    // Obtener system prompt
    $systemMessages = [];
    $stmt = \App\Database::getConnection()->prepare(
        "SELECT content FROM messages WHERE conversation_id = :id AND role = 'system' LIMIT 1"
    );
    $stmt->execute(['id' => $conversation['id']]);
    $systemMessage = $stmt->fetch();
    $systemPrompt = $systemMessage ? $systemMessage['content'] : null;
    
    // Llamar a la IA
    $startTime = microtime(true);
    $aiResponse = $aiService->chat($history, $systemPrompt);
    $responseTime = round((microtime(true) - $startTime) * 1000);
    
    // Guardar respuesta de la IA
    $messageModel->create([
        'conversation_id' => $conversation['id'],
        'role' => 'assistant',
        'content' => $aiResponse['content'],
        'metadata' => [
            'model' => $aiResponse['model'],
            'usage' => $aiResponse['usage'],
            'response_time_ms' => $responseTime,
        ],
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => $aiResponse['content'],
        'metadata' => [
            'response_time' => $responseTime . 'ms',
            'model' => $aiResponse['model'],
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Lo siento, hubo un error procesando tu mensaje. Por favor intenta de nuevo.',
        'details' => $_ENV['APP_DEBUG'] === 'true' ? $e->getMessage() : null
    ]);
}