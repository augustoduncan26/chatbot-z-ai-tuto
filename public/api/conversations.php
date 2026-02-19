<?php
session_start();

require_once __DIR__ . '/../../src/bootstrap.php';

use App\Models\Conversation;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/../..');
$dotenv->load();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

// Verificar autenticación
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'No autenticado']);
    exit;
}

$conversationModel = new Conversation();

// GET: Listar conversaciones del usuario
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $conversations = $conversationModel->getAllByUserId($_SESSION['user_id']);
        
        echo json_encode([
            'success' => true,
            'conversations' => array_map(function($conv) {
                // Generar título automático si no existe
                $title = $conv['title'];
                if ($title === 'Nueva conversación' && $conv['first_message']) {
                    $title = mb_substr($conv['first_message'], 0, 50) . '...';
                }
                
                return [
                    'id' => $conv['id'],
                    'session_id' => $conv['session_id'],
                    'title' => $title,
                    'created_at' => $conv['created_at'],
                    'updated_at' => $conv['updated_at'],
                ];
            }, $conversations)
        ]);
        
    } catch (Exception $e) {
        error_log('Error listando conversaciones: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Error al listar conversaciones']);
    }
    exit;
}

// DELETE: Eliminar conversación
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (empty($input['conversation_id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'ID de conversación requerido']);
            exit;
        }
        
        // Verificar que la conversación pertenezca al usuario
        $conversation = $conversationModel->findBySessionId($input['session_id'] ?? '');
        if (!$conversation || $conversation['user_id'] != $_SESSION['user_id']) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'No autorizado']);
            exit;
        }
        
        $conversationModel->delete($input['conversation_id']);
        
        echo json_encode(['success' => true]);
        
    } catch (Exception $e) {
        error_log('Error eliminando conversación: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Error al eliminar conversación']);
    }
    exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'error' => 'Método no permitido']);