<?php

//require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../../src/bootstrap.php';

use App\Models\Conversation;
use Dotenv\Dotenv;

// Cargar variables de entorno
// $dotenv = Dotenv::createImmutable(__DIR__ . '/../..');
// $dotenv->load();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (empty($input['session_id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Session ID requerido']);
        exit;
    }
    
    $conversationModel = new Conversation();
    $conversation = $conversationModel->findBySessionId($input['session_id']);
    
    if (!$conversation) {
        http_response_code(404);
        echo json_encode(['error' => 'Conversación no encontrada']);
        exit;
    }
    
    $messages = $conversationModel->getAllMessages($conversation['id']);
    
    echo json_encode([
        'success' => true,
        'messages' => array_map(function($msg) {
            return [
                'role' => $msg['role'],
                'content' => $msg['content'],
                'created_at' => date('H:i', strtotime($msg['created_at'])),
            ];
        }, $messages)
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error al obtener el historial',
        'details' => $_ENV['APP_DEBUG'] === 'true' ? $e->getMessage() : null
    ]);
}