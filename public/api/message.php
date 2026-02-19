<?php
session_start();

require_once __DIR__ . '/../../src/bootstrap.php';

use App\Models\Conversation;
use App\Models\Message;
use App\Services\AIService;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/../..');
$dotenv->load();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

// Verificar autenticación
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'No autenticado']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (empty($input['session_id']) || empty($input['message'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Datos incompletos']);
        exit;
    }
    
    $conversationModel = new Conversation();
    $messageModel = new Message();
    $aiService = new AIService();
    
    $conversation = $conversationModel->findBySessionId($input['session_id']);
    
    if (!$conversation) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Conversación no encontrada']);
        exit;
    }
    
    // Verificar que la conversación pertenezca al usuario
    if ($conversation['user_id'] != $_SESSION['user_id']) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'No autorizado']);
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
    
    // Actualizar título si es el primer mensaje
    if ($conversation['title'] === 'Nueva conversación') {
        $title = mb_substr($input['message'], 0, 50);
        if (strlen($input['message']) > 50) {
            $title .= '...';
        }
        $conversationModel->updateTitle($conversation['id'], $title);
    }
    
    echo json_encode([
        'success' => true,
        'message' => $aiResponse['content'],
        'metadata' => [
            'response_time' => $responseTime . 'ms',
            'model' => $aiResponse['model'],
        ]
    ]);
    
} catch (Exception $e) {
    error_log('Error en message.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Lo siento, hubo un error procesando tu mensaje. Por favor intenta de nuevo.',
        'details' => $_ENV['APP_DEBUG'] === 'true' ? $e->getMessage() : null
    ]);
}