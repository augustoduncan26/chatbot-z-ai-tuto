let sessionId = null;

// Iniciar conversación
document.getElementById('startForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const name = document.getElementById('name').value;
    const email = document.getElementById('email').value;
    await startChat(name, email);
});

async function startAnonymous() {
    await startChat('', '');
}

async function startChat(name, email) {
    try {
        console.log('Iniciando chat...');
        
        const response = await fetch('/api/start.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ name, email })
        });

        console.log('Response status:', response.status);
        console.log('Response headers:', response.headers);

        // Leer la respuesta como texto primero
        const text = await response.text();
        console.log('Response text:', text);

        // Intentar parsear como JSON
        let data;
        try {
            data = JSON.parse(text);
        } catch (e) {
            console.error('Error parseando JSON:', e);
            console.error('Texto recibido:', text);
            throw new Error('El servidor no devolvió JSON válido');
        }
        
        if (!data.success) {
            throw new Error(data.error || 'Error al iniciar el chat');
        }

        sessionId = data.session_id;

        document.getElementById('sessionId').textContent = sessionId;
        document.getElementById('startModal').classList.add('hidden');
        document.getElementById('chatContainer').classList.remove('hidden');

        // Agregar mensaje de bienvenida
        addMessage('assistant', data.message);

    } catch (error) {
        console.error('Error completo:', error);
        alert('Error al iniciar el chat: ' + error.message);
    }
}

// Enviar mensaje
document.getElementById('messageForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const input = document.getElementById('messageInput');
    const message = input.value.trim();
    
    if (!message) return;

    // Mostrar mensaje del usuario
    addMessage('user', message);
    input.value = '';

    // Deshabilitar input mientras se procesa
    setLoading(true);

    try {
        console.log('Enviando mensaje...');
        
        const response = await fetch('/api/message.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                session_id: sessionId,
                message: message
            })
        });

        console.log('Response status:', response.status);

        const text = await response.text();
        console.log('Response text:', text);

        let data;
        try {
            data = JSON.parse(text);
        } catch (e) {
            console.error('Error parseando JSON:', e);
            throw new Error('El servidor no devolvió JSON válido');
        }

        if (data.success) {
            addMessage('assistant', data.message);
            updateStatus(`Respondido en ${data.metadata.response_time}`);
        } else {
            addMessage('assistant', data.error || 'Error desconocido', true);
            updateStatus('Error en la respuesta');
        }

    } catch (error) {
        console.error('Error:', error);
        addMessage('assistant', 'Lo siento, hubo un error de conexión. Por favor intenta de nuevo.', true);
        updateStatus('Error de conexión');
    } finally {
        setLoading(false);
    }
});

function addMessage(role, content, isError = false) {
    const container = document.getElementById('messagesContainer');
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
    button.textContent = loading ? 'Enviando...' : 'Enviar';

    if (loading) {
        updateStatus('La IA está pensando...');
    }
}

function updateStatus(text) {
    document.getElementById('statusText').textContent = text;
}