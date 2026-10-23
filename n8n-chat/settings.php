<?php
require_once 'config/auth.php';
requireLogin(); // Garante que apenas usuários logados acessem

$currentUser = getCurrentUser();
$themeClass = getThemeClass();
$success = '';
$error = '';

// Processamento do formulário quando enviado via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $notifications = isset($_POST['notifications']) ? 1 : 0;
    $theme = $_POST['theme'] ?? 'light';

    try {
        $pdo = getDBConnection();
        
        // Atualiza as preferências na tabela users
        $stmt = $pdo->prepare("UPDATE users SET notifications = ?, theme = ? WHERE id = ?");
        $stmt->execute([$notifications, $theme, $currentUser['id']]);
        
        $success = "Configurações salvas com sucesso!";
        
        // Recarrega os dados atualizados do utilizador
        $currentUser = getCurrentUser();
    } catch (Exception $e) {
        $error = "Erro ao salvar configurações: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR" class="<?= $themeClass ?>">
<head>
    <meta charset="UTF-8">
    <title>Configurações - Adaptis</title>
    <link rel="shortcut icon" href="Imagens/Adaptis_logo2.ico" type="image/x-icon">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.2/font/bootstrap-icons.min.css">
    <script>
      tailwind.config = {
        darkMode: 'class', // Essencial para o Tailwind reconhecer a classe 'dark'
      }
    </script>
<style>
      /* Fundo geral da página e texto padrão no modo escuro */
    html.dark body {
        background-color: #0c0a09 !important;
        color: #f5f5f4 !important;
    }

    /* Tra nsforma fundos brancos, cinzentos, cabeçalhos e painéis principais em escuros */
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

    
    </style>
</head>
<body class="bg-stone-100 text-stone-900 flex h-screen overflow-hidden">

    <!-- Menu Lateral -->
    <?php include 'includes/sidebar.php'; ?>

    <!-- Conteúdo Principal -->
    <main class="flex-1 flex flex-col min-w-0 overflow-y-auto">
        <header class="bg-white border-b border-stone-200 px-8 py-6">
            <h1 class="text-2xl font-bold tracking-tight text-stone-900">Configurações</h1>
            <p class="text-sm text-stone-500 mt-1">Gerencie as preferências da sua conta e do sistema adaptativo.</p>
        </header>

        <div class="p-8 max-w-4xl w-full mx-auto space-y-6">

            <?php if ($success): ?>
                <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-lg text-sm flex items-center gap-2">
                    <i class="bi bi-check-circle-fill text-emerald-600 text-base"></i>
                    <span><?= htmlspecialchars($success) ?></span>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="bg-rose-50 border border-rose-200 text-rose-800 px-4 py-3 rounded-lg text-sm flex items-center gap-2">
                    <i class="bi bi-exclamation-triangle-fill text-rose-600 text-base"></i>
                    <span><?= htmlspecialchars($error) ?></span>
                </div>
            <?php endif; ?>

            <!-- Formulário de Preferências -->
            <form action="settings.php" method="POST" class="bg-white rounded-xl border border-stone-200 shadow-sm overflow-hidden">
                <div class="p-6 border-b border-stone-200">
                    <h2 class="text-lg font-semibold text-stone-900">Preferências da Conta</h2>
                    <p class="text-xs text-stone-500 mt-0.5">Personalize o comportamento da plataforma para o seu usuário.</p>
                </div>

                <div class="p-6 space-y-6">
                    <!-- Notificações -->
                    <div class="flex items-center justify-between">
                        <div>
                            <label class="text-sm font-medium text-stone-900 block">Notificações por E-mail</label>
                            <p class="text-xs text-stone-500">Receba avisos importantes e atualizações do assistente virtual.</p>
                        </div>
                        <input type="checkbox" name="notifications" value="1" 
                               class="h-4 w-4 rounded border-stone-300 text-teal-700 focus:ring-teal-700 cursor-pointer" 
                               <?= (isset($currentUser['notifications']) && $currentUser['notifications'] == 1) ? 'checked' : '' ?>>
                    </div>

                    <hr class="border-stone-100">

                    <!-- Tema do Sistema -->
                    <div>
                        <label class="text-sm font-medium text-stone-900 block mb-1">Tema da Interface</label>
                        <p class="text-xs text-stone-500 mb-3">Escolha a aparência visual do painel.</p>
                        <select name="theme" class="w-full max-w-xs rounded-lg border border-stone-300 px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-teal-700">
                            <option value="light" <?= ($currentUser['theme'] ?? '') === 'light' ? 'selected' : '' ?>>Claro (Padrão)</option>
                            <option value="dark" <?= ($currentUser['theme'] ?? '') === 'dark' ? 'selected' : '' ?>>Escuro</option>
                            <option value="system" <?= ($currentUser['theme'] ?? '') === 'system' ? 'selected' : '' ?>>Seguir o sistema</option>
                        </select>
                    </div>
                </div>

                <div class="bg-stone-50 px-6 py-4 border-t border-stone-200 flex justify-end">
                    <button type="submit" class="bg-teal-700 hover:bg-teal-800 text-white font-medium text-sm px-5 py-2 rounded-lg transition-colors cursor-pointer">
                        Salvar Alterações
                    </button>
                </div>
            </form>

        </div>
    </main>

</body>
</html>