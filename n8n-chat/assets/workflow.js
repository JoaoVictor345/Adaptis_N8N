// URL do Webhook do n8n
    const N8N_WEBHOOK_URL = 'http://localhost:5678/webhook/adaptis';

    // Utiliza o ID do usuário logado no PHP como chat_id / user_id
    const userId = window.PHP_USER ? window.PHP_USER.id : 'guest';
    const userEmail = window.PHP_USER ? window.PHP_USER.email : '';
    const chatId = 'user_' + userId;

    const chatBox = document.getElementById('chatBox');
    const chatForm = document.getElementById('chatForm');
    const userInput = document.getElementById('userInput');
    const typingIndicator = document.getElementById('typing');

    const now = () => new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    document.getElementById('initTime').innerText = now();

    function escapeHtml(s) {
      return s.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }

    function formatText(text) {
      if (!text) return '';
      let formatted = escapeHtml(text).replace(/\*\*(.*?)\*\*/g, '<b>$1</b>');
      formatted = formatted.replace(/\*(.*?)\*/g, '<i>$1</i>');
      return formatted.replace(/\n/g, '<br>');
    }

    function addMessage(text, sender) {
      const msgDiv = document.createElement('div');
      msgDiv.classList.add('message', sender);

      const contentDiv = document.createElement('div');
      contentDiv.innerHTML = formatText(text);

      const timeDiv = document.createElement('div');
      timeDiv.classList.add('time');
      timeDiv.innerText = now();

      msgDiv.append(contentDiv, timeDiv);
      chatBox.appendChild(msgDiv);
      chatBox.scrollTop = chatBox.scrollHeight;
    }

    async function sendMessage(messageText) {
      addMessage(messageText, 'user');
      typingIndicator.style.display = 'flex';
      chatBox.scrollTop = chatBox.scrollHeight;

      try {
        const response = await fetch(N8N_WEBHOOK_URL, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
          body: JSON.stringify({ 
            message: messageText, 
            chat_id: chatId,
            user_id: userId,
            email: userEmail 
          })
        });

        if (!response.ok) throw new Error(`Servidor retornou status HTTP ${response.status}`);

        const data = await response.json();
        addMessage(data && data.text ? data.text : '⚠️ Nenhuma resposta retornada pelo fluxo.', 'bot');

      } catch (error) {
        addMessage(`❌ Erro de conexão: ${error.message}. Verifique se o n8n está ativo e aceitando chamadas na porta 5678.`, 'bot');
        console.error('Detalhes do erro no console:', error);
      } finally {
        typingIndicator.style.display = 'none';
        chatBox.scrollTop = chatBox.scrollHeight;
      }
    }

    chatForm.addEventListener('submit', (e) => {
      e.preventDefault();
      const text = userInput.value.trim();
      if (text) {
        sendMessage(text);
        userInput.value = '';
      }
    });

    function sendQuickCommand(cmd) { sendMessage(cmd); }

    // Typewriter effect for initial message
    function typeWriterEffect(element, text, speed = 50) {
      let i = 0;
      element.innerHTML = ''; // Clear the element

      function type() {
        if (i < text.length) {
          const char = text.charAt(i);
          if (char === '\n') {
            element.innerHTML += '<br>';
          } else {
            const escapedChar = char
              .replace(/&/g, '&amp;')
              .replace(/</g, '&lt;')
              .replace(/>/g, '&gt;')
              .replace(/"/g, '&quot;')
              .replace(/'/g, '&#039;');
            element.innerHTML += escapedChar;
          }
          i++;
          chatBox.scrollTop = chatBox.scrollHeight;
          setTimeout(type, speed);
        } else {
          typingIndicator.style.display = 'none';
          document.getElementById('initTime').innerText = now();
        }
      }

      type();
    }

    document.addEventListener('DOMContentLoaded', function() {
      const initialMessage = `Olá, ${window.PHP_USER ? window.PHP_USER.name : ''}! Sou o agente de IA do IDETE. Como posso te ajudar hoje? Use os atalhos abaixo ou digite /help para ver as opções.`;
      const messageElement = document.getElementById('initialMessage');
      typeWriterEffect(messageElement, initialMessage, 15);
    });