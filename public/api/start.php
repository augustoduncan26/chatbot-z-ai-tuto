<?php
session_start();

require_once __DIR__ . '/../../src/bootstrap.php';

use App\Models\Conversation;
use App\Models\Message;
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
    $sessionId = bin2hex(random_bytes(16));
    
    $conversationModel = new Conversation();
    $messageModel = new Message();
    
    $conversation = $conversationModel->create([
        'session_id' => $sessionId,
        'user_id' => $_SESSION['user_id'],
        'user_name' => $_SESSION['user_name'],
        'user_email' => $_SESSION['user_email'],
        'title' => 'Nueva conversación',
    ]);
    
    // Crear mensaje del sistema con restricción de tecnología
    $systemPrompt = getSystemPrompt($conversation);
    $messageModel->create([
        'conversation_id' => $conversation['id'],
        'role' => 'system',
        'content' => $systemPrompt,
    ]);
    
    echo json_encode([
        'success' => true,
        'session_id' => $sessionId,
        'conversation_id' => $conversation['id'],
        'message' => '¡Hola ' . $_SESSION['user_name'] . '! Soy tu asistente de tecnología. ¿En qué puedo ayudarte hoy?'
    ]);
    
} catch (Exception $e) {
    error_log('Error en start.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error: ' . $e->getMessage()
    ]);
}

function getSystemPrompt(array $conversation): string
{
    $userName = !empty($conversation['user_name']) ? "El usuario se llama {$conversation['user_name']}. " : '';
    
    return "Eres un asistente virtual especializado EXCLUSIVAMENTE en tecnología. " .
           $userName .
           "Tu objetivo es ayudar SOLO con consultas relacionadas a:\n" .
           "- Programación y desarrollo de software\n" .
           "- Hardware y componentes de computadoras\n" .
           "- Sistemas operativos\n" .
           "- Redes e internet\n" .
           "- Inteligencia Artificial y Machine Learning\n" .
           "- Seguridad informática\n" .
           "- Aplicaciones y software\n" .
           "- Dispositivos electrónicos y gadgets\n\n" .
           "IMPORTANTE: Si el usuario pregunta sobre temas NO relacionados con tecnología (deportes, cocina, política, entretenimiento, etc.), " .
           "debes responder amablemente: 'Lo siento, soy un asistente especializado en tecnología. Solo puedo ayudarte con preguntas relacionadas a programación, hardware, software, y otros temas tecnológicos. ¿Tienes alguna consulta sobre tecnología?'\n\n" .
           "Mantén un tono profesional pero cercano y siempre da respuestas útiles dentro del ámbito tecnológico.";
}