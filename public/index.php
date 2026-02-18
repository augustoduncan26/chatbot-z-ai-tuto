<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Chatbot - Soporte Inteligente</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <!-- Header -->
        <header class="header">
            <h1>🤖 Chatbot de Soporte con IA</h1>
            <p>Asistente virtual con memoria conversacional</p>
        </header>

        <!-- Modal de inicio -->
        <div id="startModal" class="modal">
            <div class="modal-content">
                <h2>¡Bienvenido!</h2>
                <p>Para comenzar, cuéntanos un poco sobre ti (opcional):</p>
                
                <form id="startForm">
                    <div class="form-group">
                        <label for="name">Nombre</label>
                        <input type="text" id="name" name="name" placeholder="Tu nombre">
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" placeholder="tu@email.com">
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Comenzar Chat</button>
                    <button type="button" class="btn btn-secondary" onclick="startAnonymous()">
                        Continuar sin datos
                    </button>
                </form>
            </div>
        </div>

        <!-- Chat Container -->
        <div id="chatContainer" class="chat-container hidden">
            <!-- Session Info -->
            <div class="session-info">
                <p>
                    <strong>Session ID:</strong> 
                    <code id="sessionId"></code>
                </p>
            </div>

            <!-- Messages Area -->
            <div class="chat-box">
                <div class="messages" id="messagesContainer">
                    <!-- Los mensajes se cargarán aquí -->
                </div>

                <!-- Input Area -->
                <div class="input-area">
                    <form id="messageForm">
                        <input 
                            type="text" 
                            id="messageInput"
                            placeholder="Escribe tu mensaje aquí..."
                            required
                        >
                        <button type="submit" id="sendButton">Enviar</button>
                    </form>
                    <p class="status-text" id="statusText">Listo para chatear</p>
                </div>
            </div>
        </div>
    </div>

    <script src="js/chat.js"></script>
</body>
</html>