<?php

//require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../../src/bootstrap.php';

use App\Models\Conversation;
use App\Models\Message;
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
    
    $sessionId = bin2hex(random_bytes(16));
    
    $conversationModel = new Conversation();
    $messageModel = new Message();
    
    $conversation = $conversationModel->create([
        'session_id' => $sessionId,
        'user_name' => $input['name'] ?? null,
        'user_email' => $input['email'] ?? null,
    ]);
    
    // Crear mensaje del sistema
    $systemPrompt = getSystemPrompt($conversation);
    $messageModel->create([
        'conversation_id' => $conversation['id'],
        'role' => 'system',
        'content' => $systemPrompt,
    ]);
    
    echo json_encode([
        'success' => true,
        'session_id' => $sessionId,
        'message' => '¡Hola' . ($conversation['user_name'] ? ' ' . $conversation['user_name'] : '') . '! Soy tu asistente virtual. ¿En qué puedo ayudarte hoy?'
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error al iniciar la conversación',
        'details' => $_ENV['APP_DEBUG'] === 'true' ? $e->getMessage() : null
    ]);
}

function getSystemPrompt(array $conversation): string
{
    $userName = !empty($conversation['user_name']) ? "El usuario se llama {$conversation['user_name']}. " : '';
    
    return "Eres un asistente virtual amable y servicial para una empresa de soporte técnico. " .
           $userName .
           "Tu objetivo es ayudar a los usuarios con sus consultas de manera clara y concisa. " .
           "Recuerda el contexto de la conversación y proporciona respuestas personalizadas. " .
           "Si no sabes algo, admítelo honestamente. " .
           "Mantén un tono profesional pero cercano.";
}