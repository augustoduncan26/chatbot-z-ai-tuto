// Estado de la aplicación
let currentUser = null;
let currentSessionId = null;
let currentConversationId = null;
let conversations = [];

// ========== INICIALIZACIÓN ==========
document.addEventListener('DOMContentLoaded', () => {
    checkSession();
    setupEventListeners();
});

async function checkSession() {
    try {
        const response = await fetch('/api/check-session.php');
        const data = await response.json();
        
        console.log('Check session result:', data);
        
        if (data.authenticated) {
            currentUser = data.user;
            showChatScreen();
            await loadConversations();
        } else {
            // No autenticado - mostrar pantalla de login
            currentUser = null;
            currentSessionId = null;
            currentConversationId = null;
            conversations = [];
            showAuthScreen();
        }
    } catch (error) {
        console.error('Error checking session:', error);
        showAuthScreen();
    }
}

function setupEventListeners() {
    // Auth forms
    document.getElementById('loginForm').addEventListener('submit', handleLogin);
    document.getElementById('registerForm').addEventListener('submit', handleRegister);
    
    // Chat
    document.getElementById('messageForm').addEventListener('submit', handleSendMessage);
    document.getElementById('newChatBtn').addEventListener('click', startNewChat);
    document.getElementById('logoutBtn').addEventListener('click', handleLogout);
}

// ========== AUTENTICACIÓN ==========
function showLogin() {
    document.getElementById('loginForm').classList.remove('hidden');
    document.getElementById('registerForm').classList.add('hidden');
    document.querySelectorAll('.auth-tab')[0].classList.add('active');
    document.querySelectorAll('.auth-tab')[1].classList.remove('active');
    hideError();
}

function showRegister() {
    document.getElementById('loginForm').classList.add('hidden');
    document.getElementById('registerForm').classList.remove('hidden');
    document.querySelectorAll('.auth-tab')[0].classList.remove('active');
    document.querySelectorAll('.auth-tab')[1].classList.add('active');
    hideError();
}

async function handleLogin(e) {
    e.preventDefault();
    
    const email = document.getElementById('loginEmail').value;
    const password = document.getElementById('loginPassword').value;
    
    const submitBtn = e.target.querySelector('button[type="submit"]');
    submitBtn.disabled = true;
    submitBtn.textContent = 'Iniciando sesión...';
    
    try {
        const response = await fetch('/api/login.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ email, password })
        });
        
        const data = await response.json();
        
        if (data.success) {
            console.log('Login exitoso:', data.user);
            currentUser = data.user;
            
            // Forzar cambio de pantalla
            showChatScreen();
            
            // Cargar conversaciones
            await loadConversations();
            
            // Limpiar formulario
            document.getElementById('loginForm').reset();
        } else {
            showError(data.error || 'Error al iniciar sesión');
        }
    } catch (error) {
        console.error('Error:', error);
        showError('Error de conexión');
    } finally {
        submitBtn.disabled = false;
        submitBtn.textContent = 'Iniciar Sesión';
    }
}

async function handleRegister(e) {
    e.preventDefault();
    
    const name = document.getElementById('registerName').value;
    const email = document.getElementById('registerEmail').value;
    const password = document.getElementById('registerPassword').value;
    
    const submitBtn = e.target.querySelector('button[type="submit"]');
    submitBtn.disabled = true;
    submitBtn.textContent = 'Creando cuenta...';
    
    try {
        const response = await fetch('/api/register.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ name, email, password })
        });
        
        const data = await response.json();
        
        if (data.success) {
            console.log('Registro exitoso:', data.user);
            currentUser = data.user;
            
            // Forzar cambio de pantalla
            showChatScreen();
            
            // Cargar conversaciones (estará vacío para nuevo usuario)
            await loadConversations();
            
            // Limpiar formulario
            document.getElementById('registerForm').reset();
        } else {
            showError(data.error || 'Error al registrarse');
        }
    } catch (error) {
        console.error('Error:', error);
        showError('Error de conexión');
    } finally {
        submitBtn.disabled = false;
        submitBtn.textContent = 'Crear Cuenta';
    }
}

async function handleLogout() {
    if (!confirm('¿Estás seguro que deseas cerrar sesión?')) {
        return;
    }
    
    try {
        await fetch('/api/logout.php', { method: 'POST' });
        
        console.log('Logout exitoso');
        
        // Limpiar estado
        currentUser = null;
        currentSessionId = null;
        currentConversationId = null;
        conversations = [];
        
        // Limpiar chat
        document.getElementById('messagesContainer').innerHTML = '';
        
        // Mostrar pantalla de auth
        showAuthScreen();
    } catch (error) {
        console.error('Error:', error);
        alert('Error al cerrar sesión');
    }
}

function showError(message) {
    const errorEl = document.getElementById('authError');
    errorEl.textContent = message;
    errorEl.classList.remove('hidden');
}

function hideError() {
    document.getElementById('authError').classList.add('hidden');
}

function showAuthScreen() {
    console.log('Mostrando pantalla de autenticación');
    document.getElementById('authScreen').classList.remove('hidden');
    document.getElementById('chatScreen').classList.add('hidden');
    
    // Limpiar formularios
    document.getElementById('loginForm').reset();
    document.getElementById('registerForm').reset();
    hideError();
}

function showChatScreen() {
    console.log('Mostrando pantalla de chat');
    document.getElementById('authScreen').classList.add('hidden');
    document.getElementById('chatScreen').classList.remove('hidden');
    
    // Actualizar nombre de usuario
    if (currentUser) {
        document.getElementById('userName').textContent = currentUser.name;
    }
    
    // Mostrar estado inicial si no hay conversación activa
    if (!currentSessionId) {
        showEmptyChat();
    }
}

// ========== CONVERSACIONES ==========
async function loadConversations() {
    try {
        const response = await fetch('/api/conversations.php');
        const data = await response.json();
        
        if (data.success) {
            conversations = data.conversations;
            renderConversations();
        }
    } catch (error) {
        console.error('Error loading conversations:', error);
    }
}

function renderConversations() {
    const container = document.getElementById('conversationsList');
    
    if (conversations.length === 0) {
        container.innerHTML = '<p style="padding: 20px; text-align: center; color: #999;">No hay conversaciones</p>';
        return;
    }
    
    container.innerHTML = conversations.map(conv => `
        <div class="conversation-item ${conv.session_id === currentSessionId ? 'active' : ''}" 
             data-session-id="${conv.session_id}"
             data-conversation-id="${conv.id}"
             onclick="loadConversation('${conv.session_id}', ${conv.id})">
            <div class="conversation-title">${conv.title}</div>
            <div class="conversation-date">${formatDate(conv.updated_at)}</div>
        </div>
    `).join('');
}

async function loadConversation(sessionId, conversationId) {
    try {
        currentSessionId = sessionId;
        currentConversationId = conversationId;
        
        // Actualizar UI
        renderConversations();
        
        // Cargar mensajes
        const response = await fetch('/api/history.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ session_id: sessionId })
        });
        
        const data = await response.json();
        
        if (data.success) {
            const container = document.getElementById('messagesContainer');
            container.innerHTML = '';
            
            data.messages.forEach(msg => {
                addMessage(msg.role, msg.content);
            });
        }
    } catch (error) {
        console.error('Error loading conversation:', error);
    }
}

async function startNewChat() {
    try {
        const response = await fetch('/api/start.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({})
        });
        
        const data = await response.json();
        
        if (data.success) {
            currentSessionId = data.session_id;
            currentConversationId = data.conversation_id;
            
            // Limpiar chat
            const container = document.getElementById('messagesContainer');
            container.innerHTML = '';
            
            // Agregar mensaje de bienvenida
            addMessage('assistant', data.message);
            
            // Recargar lista de conversaciones
            loadConversations();
        } else {
            alert('Error al crear nueva conversación');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Error de conexión');
    }
}

// ========== MENSAJES ==========
async function handleSendMessage(e) {
    e.preventDefault();
    
    const input = document.getElementById('messageInput');
    const message = input.value.trim();
    
    if (!message) return;
    
    // Verificar que hay una conversación activa
    if (!currentSessionId) {
        await startNewChat();
    }
    
    // Mostrar mensaje del usuario
    addMessage('user', message);
    input.value = '';
    setLoading(true);
    
    try {
        const response = await fetch('/api/message.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                session_id: currentSessionId,
                message: message
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            addMessage('assistant', data.message);
            updateStatus(`Respondido en ${data.metadata.response_time}`);
            
            // Actualizar lista de conversaciones
            loadConversations();
        } else {
            addMessage('assistant', data.error || 'Error desconocido', true);
            updateStatus('Error en la respuesta');
        }
    } catch (error) {
        console.error('Error:', error);
        addMessage('assistant', 'Error de conexión. Intenta de nuevo.', true);
        updateStatus('Error de conexión');
    } finally {
        setLoading(false);
    }
}

function addMessage(role, content, isError = false) {
    const container = document.getElementById('messagesContainer');
    
    // Remover mensaje de empty state si existe
    const emptyChat = container.querySelector('.empty-chat');
    if (emptyChat) {
        emptyChat.remove();
    }
    
    const messageDiv = document.createElement('div');
    messageDiv.className = `message ${role} ${isError ? 'error' : ''}`;
    
    const bubble = document.createElement('div');
    bubble.className = 'message-bubble';
    bubble.textContent = content;
    
    const timestamp = document.createElement('div');
    timestamp.className = 'message-time';
    timestamp.textContent = new Date().toLocaleTimeString('es-AR', {
        hour: '2-digit',
        minute: '2-digit'
    });
    
    messageDiv.appendChild(bubble);
    messageDiv.appendChild(timestamp);
    container.appendChild(messageDiv);
    
    // Scroll al final
    container.scrollTop = container.scrollHeight;
}

function setLoading(loading) {
    const input = document.getElementById('messageInput');
    const button = document.getElementById('sendButton');
    
    input.disabled = loading;
    button.disabled = loading;
    button.querySelector('span').textContent = loading ? 'Enviando...' : 'Enviar';
    
    if (loading) {
        updateStatus('La IA está pensando...');
    }
}

function updateStatus(text) {
    document.getElementById('statusText').textContent = text;
}

function showEmptyChat() {
    const container = document.getElementById('messagesContainer');
    container.innerHTML = `
        <div class="empty-chat">
            <div class="empty-chat-icon">💬</div>
            <h3>¡Bienvenido!</h3>
            <p>Soy tu asistente especializado en tecnología. Puedo ayudarte con programación, hardware, software y cualquier tema relacionado con tecnología.</p>
            <p style="margin-top: 10px; font-weight: 600;">Haz clic en "Nuevo Chat" para comenzar</p>
        </div>
    `;
}

// ========== UTILIDADES ==========
function formatDate(dateString) {
    const date = new Date(dateString);
    const now = new Date();
    const diffTime = Math.abs(now - date);
    const diffDays = Math.floor(diffTime / (1000 * 60 * 60 * 24));
    
    if (diffDays === 0) {
        return 'Hoy ' + date.toLocaleTimeString('es-AR', { hour: '2-digit', minute: '2-digit' });
    } else if (diffDays === 1) {
        return 'Ayer';
    } else if (diffDays < 7) {
        return diffDays + ' días';
    } else {
        return date.toLocaleDateString('es-AR', { day: '2-digit', month: '2-digit' });
    }
}