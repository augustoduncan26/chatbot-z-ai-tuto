<?php
session_start();

require_once __DIR__ . '/../../src/bootstrap.php';

use App\Models\Conversation;
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
    
    if (empty($input['session_id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Session ID requerido']);
        exit;
    }
    
    $conversationModel = new Conversation();
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
    error_log('Error en history.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error al obtener el historial',
        'details' => $_ENV['APP_DEBUG'] === 'true' ? $e->getMessage() : null
    ]);
}