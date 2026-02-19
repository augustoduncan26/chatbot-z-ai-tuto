<?php
require_once __DIR__ . '/../../src/bootstrap.php';

use App\Models\User;
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

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (empty($input['email']) || empty($input['password'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Email y contraseña son requeridos']);
        exit;
    }

    $userModel = new User();
    $user = $userModel->findByEmail($input['email']);

    if (!$user || !$userModel->verifyPassword($input['password'], $user['password'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Credenciales incorrectas']);
        exit;
    }

    // Iniciar sesión
    session_start();
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['user_email'] = $user['email'];

    echo json_encode([
        'success' => true,
        'user' => [
            'id' => $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
        ]
    ]);

} catch (Exception $e) {
    error_log('Error en login.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error al iniciar sesión',
        'details' => $_ENV['APP_DEBUG'] === 'true' ? $e->getMessage() : null
    ]);
}