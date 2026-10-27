<?php
require_once 'config/auth.php';
requireLogin();

$themeClass = getThemeClass();
?>
<!DOCTYPE html>
<html lang="pt-BR" class="<?= $themeClass ?>">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Assistente Virtual - ADAPTIS</title>
  <link rel="shortcut icon" href="Imagens/Adaptis_logo2.ico" type="image/x-icon">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;800&display=swap" rel="stylesheet">
  <!-- Tailwind para a Sidebar e estrutura de layout -->
   <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.2/font/bootstrap-icons.min.css">
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
      tailwind.config = {
        darkMode: 'class', // Essencial para o Tailwind reconhecer a classe 'dark'
      }
    </script>
  <!-- CSS customizado do Chat IDETE -->
  <link rel="stylesheet" href="assets/workflow.css">
  <style>
    body {
      display: flex;
      flex-direction: row;
      height: 100vh;
      overflow: hidden;
      margin: 0;
      background: var(--bg, #f3f6fa);
    }
    .main-wrapper {
      flex: 1;
      display: flex;
      flex-direction: column;
      height: 100vh;
      overflow: hidden;
    }
    /* Limita o tamanho do logótipo no cabeçalho */
    .brand img {
      max-height: 50px;
      width: auto;
      object-fit: contain;
      background: #eeeded;
      padding: 4px 8px;
      border-radius: 8px;
    }
    /* Ajusta a caixa do chat para ocupar todo o espaço restante */
    .chat-container {
      flex: 1;
      height: 100%;
      max-width: 900px;
      width: 100%;
      margin: 1rem auto;
      display: flex;
      flex-direction: column;
    }

    /* Fundo geral da página e texto padrão no modo escuro */
      html.dark body {
          background-color: #0c0a09 !important;
          color: #f5f5f4 !important;
      }

      /* Transforma fundos brancos, cinzentos, cabeçalhos e painéis principais em escuros */
      html.dark main,
      html.dark header,
      html.dark .bg-white, 
      html.dark .bg-stone-50,
      html.dark .rounded-xl,
      html.dark .rounded-lg {
          background-color: #1c1917 !important;
          color: #f5f5f4 !important;
          border-color: #292524 !important;
      }

      /* Caixas de chat e áreas de mensagens internas */
      html.dark .chat-container,
      html.dark div[class*="bg-white"] {
          background-color: #1c1917 !important;
          color: #f5f5f4 !important;
      }

      /* Campos de input, selects e caixas de texto */
      html.dark input, 
      html.dark select, 
      html.dark textarea {
          background-color: #292524 !important;
          color: #f5f5f4 !important;
          border-color: #44403c !important;
      }

      /* Ajuste de todos os títulos e textos para ficarem legíveis */
      html.dark h1, html.dark h2, html.dark h3, html.dark label, html.dark span {
          color: #f5f5f4 !important;
      }

      /* Textos secundários (descrições) */
      html.dark p, html.dark .text-stone-500, html.dark .text-stone-600 {
          color: #a8a29e !important;
      }

      /* Corrige o texto e fundo do balão de mensagem da IA no modo escuro */
    html.dark .chat-message, 
    html.dark .bg-blue-50, /* ajuste caso use essa classe no balão */
    html.dark div[class*="rounded-2xl"], 
    html.dark div[class*="rounded-xl"] p {
        color: #1c1917 !important; /* Texto escuro dentro da mensagem para contraste */
    }

    /* Se o balão da mensagem for claro por padrão, força o fundo dele a ficar escuro com texto claro */
    html.dark .bg-white.text-stone-800,
    html.dark .bg-slate-100 {
        background-color: #292524 !important;
        color: #f5f5f4 !important;
    }

    /* Corrige os botões de atalho (/list, /current, /help) no modo escuro */
    html.dark button, 
    html.dark .shortcut-btn,
    html.dark a[class*="rounded-full"] {
        color: #f5f5f4 !important;
        border-color: #44403c !important;
        background-color: #1c1917 !important;
    }
    
    html.dark button:hover {
        background-color: #292524 !important;
    }

      /* Mensagem da IA */
.message.bot {
    background: var(--bot-bg);
    color: var(--text) !important;
}

/* Modo escuro */
html.dark .message.bot {
    background: #292524 !important;
    color: #f5f5f4 !important;
}

html.dark .message.bot #initialMessage {
    color: #f5f5f4 !important;
}

.message.bot #initialMessage {
    color: var(--text) !important;
}
  </style>
  <link rel="stylesheet" href="assets/history.css">
</head>
<body>

  <!-- Menu Lateral (Sidebar) -->
  <?php include 'includes/sidebar.php'; ?>

  <!-- Conteúdo Principal do Chat -->
  <div class="main-wrapper">
    <header>
      <div class="brand">
        <img src="Imagens/Adaptis_logo2-removebg.png" alt="IDETE">
        <div class="brand-title">
          <h1>ADAPTIS</h1>
          <p>Assistente Virtual Adaptativo — Logado como: <b><?= htmlspecialchars($_SESSION['user_email']) ?></b></p>
        </div>
      </div>
    </header>

    <div class="chat-layout">
    <!-- Painel de conversas anteriores -->
    <aside class="history-panel" id="historyPanel">
      <div class="history-head">
        <strong>Conversas</strong>
        <button class="hist-new" id="newChatBtn" type="button"><i class="bi bi-plus-lg"></i> Nova</button>
      </div>
      <input type="search" id="historySearch" placeholder="Buscar conversa" autocomplete="off">
      <div class="history-list" id="historyList"></div>
    </aside>

    <main class="chat-container">
      <div class="chat-toolbar">
        <span class="conv-title" id="convTitle">Nova conversa</span>
        <div class="toolbar-btns">
          <button type="button" class="history-toggle" id="historyToggle"><i class="bi bi-clock-history"></i> Histórico</button>
          <button type="button" id="shareCurrent" disabled><i class="bi bi-share"></i> Compartilhar</button>
        </div>
      </div>

      <div class="chat-box" id="chatBox">
        <div class="message bot">
          <div id="initialMessage"></div>
          <div class="time" id="initTime"></div>
        </div>
      </div>

      <div class="typing-indicator" id="typing" aria-label="Escrevendo">
        <span></span><span></span><span></span>
      </div>

      <div class="quick-commands">
        <button class="cmd-btn" onclick="sendQuickCommand('/list')">/list</button>
        <button class="cmd-btn" onclick="sendQuickCommand('/current')">/current</button>
        <button class="cmd-btn" onclick="sendQuickCommand('/help')">/help</button>
      </div>

      <form class="input-container" id="chatForm">
        <input type="text" id="userInput" placeholder="Digite sua mensagem ou comando..." autocomplete="off" required />
        <button type="submit" class="btn-send">Enviar</button>
      </form>
    </main>
  </div>

    <footer class="footer-bottom" style="padding-bottom: 0.5rem;">
      <div>&copy; Centro Educacional IDETE. Todos os direitos reservados</div>
    </footer>
  </div>
  
  <!-- Passando os dados da sessão PHP para o JS do chat -->
  <script>
    window.PHP_USER = {
      id: "<?= $_SESSION['user_id'] ?>",
      name: "<?= htmlspecialchars($_SESSION['user_name']) ?>",
      email: "<?= htmlspecialchars($_SESSION['user_email']) ?>",
      role: "<?= $_SESSION['user_role'] ?>"
    };
  </script>
  <script src="assets/JS/workflow.js"></script>
  <script src="assets/JS/History.js"></script>
</body>
</html>