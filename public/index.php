<?php
session_start();

require_once __DIR__ . '/../src/bootstrap.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chatbot IA - Asistente de Tecnología</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div id="app">
        <!-- Pantalla de Login/Registro -->
        <div id="authScreen" class="auth-screen">
            <div class="auth-container">
                <h1>🤖 Chatbot de Tecnología con IA</h1>
                <p class="subtitle">Asistente especializado en temas tecnológicos</p>
                
                <div class="auth-tabs">
                    <button class="auth-tab active" onclick="showLogin()">Iniciar Sesión</button>
                    <button class="auth-tab" onclick="showRegister()">Registrarse</button>
                </div>

                <!-- Formulario de Login -->
                <form id="loginForm" class="auth-form">
                    <div class="form-group">
                        <label for="loginEmail">Email</label>
                        <input type="email" id="loginEmail" required placeholder="tu@email.com">
                    </div>
                    
                    <div class="form-group">
                        <label for="loginPassword">Contraseña</label>
                        <input type="password" id="loginPassword" required placeholder="••••••••">
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Iniciar Sesión</button>
                </form>

                <!-- Formulario de Registro -->
                <form id="registerForm" class="auth-form hidden">
                    <div class="form-group">
                        <label for="registerName">Nombre</label>
                        <input type="text" id="registerName" required placeholder="Tu nombre">
                    </div>
                    
                    <div class="form-group">
                        <label for="registerEmail">Email</label>
                        <input type="email" id="registerEmail" required placeholder="tu@email.com">
                    </div>
                    
                    <div class="form-group">
                        <label for="registerPassword">Contraseña</label>
                        <input type="password" id="registerPassword" required placeholder="Mínimo 6 caracteres">
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Crear Cuenta</button>
                </form>

                <p id="authError" class="error-message hidden"></p>
            </div>
        </div>

        <!-- Pantalla Principal del Chat -->
        <div id="chatScreen" class="chat-screen hidden">
            <!-- Sidebar con historial -->
            <div class="sidebar">
                <div class="sidebar-header">
                    <h3>💬 Conversaciones</h3>
                    <button id="newChatBtn" class="btn btn-new">+ Nuevo Chat</button>
                </div>

                <div id="conversationsList" class="conversations-list">
                    <!-- Las conversaciones se cargarán aquí -->
                </div>

                <div class="sidebar-footer">
                    <div class="user-info">
                        <span id="userName">Usuario</span>
                        <button id="logoutBtn" class="btn btn-secondary btn-small">Salir</button>
                    </div>
                </div>
            </div>

            <!-- Área de chat -->
            <div class="chat-main">
                <div class="chat-header">
                    <h2>🤖 Asistente de Tecnología</h2>
                    <p class="chat-subtitle">Especializado en temas tecnológicos</p>
                </div>

                <div class="chat-messages" id="messagesContainer">
                    <!-- Los mensajes se cargarán aquí -->
                </div>

                <div class="chat-input-area">
                    <form id="messageForm">
                        <input 
                            type="text" 
                            id="messageInput"
                            placeholder="Escribe tu pregunta sobre tecnología..."
                            required
                        >
                        <button type="submit" id="sendButton">
                            <span>Enviar</span>
                        </button>
                    </form>
                    <p class="status-text" id="statusText">Listo para chatear sobre tecnología</p>
                </div>
            </div>
        </div>
    </div>

    <script src="js/chat.js"></script>
</body>
</html>