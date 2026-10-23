<?php
// Garante que auth.php está carregado se for usado isoladamente
if (!function_exists('isLoggedIn')) {
    require_once __DIR__ . '/../config/auth.php';
}

// 1. Garante que temos os dados do utilizador atual carregados (incluindo o avatar)
if (function_exists('getCurrentUser')) {
    $loggedInUser = getCurrentUser();
} else {
    $loggedInUser = null;
}

$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';

$isChat      = $currentPath === '/Adaptis_N8N/n8n-chat/chat.php';
$isProfile   = $currentPath === '/Adaptis_N8N/n8n-chat/profile.php';
$isAdminArea = strpos($currentPath, '/Adaptis_N8N/n8n-chat/admin/') !== false;
$isSettings = $currentPath === '/Adaptis_N8N/n8n-chat/settings.php';

$linkBase = 'relative flex items-center gap-3 rounded-md px-3 py-2 text-sm transition-colors';
$linkOn   = 'bg-stone-200/70 font-medium text-stone-900';
$linkOff  = 'text-stone-600 hover:bg-stone-200/50 hover:text-stone-900';

// 2. Define as variáveis com fallback seguro para a sessão ou base de dados
$userName  = $loggedInUser['full_name'] ?? ($_SESSION['user_name'] ?? 'Usuário');
$userEmail = $loggedInUser['email'] ?? ($_SESSION['user_email'] ?? '');
$roleLabel = isAdmin() ? 'Administrador' : 'Usuário';

// 3. Trata o caminho da imagem de forma segura e absoluta para funcionar em qualquer página/subpasta
$rawAvatar = $loggedInUser['avatar'] ?? '';
if (!empty($rawAvatar)) {
    if (strpos($rawAvatar, 'http') === 0) {
        $avatarUrl = $rawAvatar;
    } else {
        $cleanAvatar = ltrim($rawAvatar, '/');
        // Se começar com n8n-chat/, ajusta para não duplicar
        if (strpos($cleanAvatar, '/Adaptis_N8N/n8n-chat/') === 0) {
            $avatarUrl = '/' . $cleanAvatar;
        } else {
            $avatarUrl = '/Adaptis_N8N/n8n-chat/' . $cleanAvatar;
        }
    }
} else {
    $avatarUrl = '/n8n-chat/Imagens/default-avatar.png'; // Caminho padrão se não houver avatar
}

$initial = mb_strtoupper(mb_substr(trim($userName) ?: 'U', 0, 1));
?>
<aside class="sticky top-0 flex h-screen w-60 shrink-0 flex-col justify-between border-r border-stone-200 bg-stone-50 p-3 text-stone-800">
    <div>
        <!-- Marca -->
        <a href="/n8n-chat/chat.php" class="flex items-center gap-2.5 px-3 py-3">
            <span class="h-2.5 w-2.5 rounded-full bg-teal-700"></span>
            <span class="text-[15px] font-semibold tracking-tight text-stone-900">Chat Adaptativo</span>
        </a>

        <!-- Navegação -->
        <nav class="mt-4 space-y-0.5" aria-label="Navegação principal">
            <a href="/Adaptis_N8N/n8n-chat/chat.php"
               class="<?= $linkBase ?> <?= $isChat ? $linkOn : $linkOff ?>"
               <?= $isChat ? 'aria-current="page"' : '' ?>>
                <?php if ($isChat): ?><span class="absolute inset-y-1.5 left-0 w-0.5 rounded-full bg-teal-700"></span><?php endif; ?>
                <i class="bi bi-chat-left-text text-base"></i>
                Conversas
            </a>

            <a href="/Adaptis_N8N/n8n-chat/profile.php"
               class="<?= $linkBase ?> <?= $isProfile ? $linkOn : $linkOff ?>"
               <?= $isProfile ? 'aria-current="page"' : '' ?>>
                <?php if ($isProfile): ?><span class="absolute inset-y-1.5 left-0 w-0.5 rounded-full bg-teal-700"></span><?php endif; ?>
                <i class="bi bi-person text-base"></i>
                Meu perfil
            </a>

            <a href="/Adaptis_N8N/n8n-chat/settings.php"
                class="<?= $linkBase ?> <?= $isSettings ? $linkOn : $linkOff ?>"
                <?= $isSettings ? 'aria-current="page"' : '' ?>>
                <?php if ($isSettings): ?><span class="absolute inset-y-1.5 left-0 w-0.5 rounded-full bg-teal-700"></span><?php endif; ?>
                <i class="bi bi-gear text-base"></i>
                Configurações
            </a>

            <?php if (isAdmin()): ?>
                <div class="mx-3 my-3 border-t border-stone-200"></div>

                <a href="/Adaptis_N8N/n8n-chat/admin/dashboard.php"
                   class="<?= $linkBase ?> <?= $isAdminArea ? $linkOn : $linkOff ?>"
                   <?= $isAdminArea ? 'aria-current="page"' : '' ?>>
                    <?php if ($isAdminArea): ?><span class="absolute inset-y-1.5 left-0 w-0.5 rounded-full bg-teal-700"></span><?php endif; ?>
                    <i class="bi bi-sliders text-base"></i>
                    Administração
                </a>
            <?php endif; ?>
        </nav>
    </div>

    <!-- Usuário -->
    <div class="flex items-center gap-3 rounded-md border border-stone-200 bg-white p-2.5">
        <a href="/Adaptis_N8N/n8n-chat/profile.php"><img src="<?= htmlspecialchars($avatarUrl) ?>" alt="Avatar" class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full object-cover bg-stone-900 text-sm font-medium text-stone-50" id="avatar-preview"></a>

        <div class="min-w-0 flex-1 leading-tight">
            <p class="truncate text-sm font-medium text-stone-900"><?= htmlspecialchars($userName) ?></p>
            <p class="truncate text-xs text-stone-500" title="<?= htmlspecialchars($userEmail) ?>">
                <?= htmlspecialchars($userEmail ?: $roleLabel) ?>
            </p>
        </div>

        <a href="/Adaptis_N8N/n8n-chat/logout.php"
           title="Sair"
           aria-label="Sair"
           class="flex h-8 w-8 shrink-0 items-center justify-center rounded-md text-stone-500 transition-colors hover:bg-stone-100 hover:text-red-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-teal-700">
            <i class="bi bi-box-arrow-right text-base"></i>
        </a>
    </div>
</aside>