<?php
require_once '../config/auth.php';
requireAdmin(); // Protegido: apenas Admin ou Super Admin entram aqui

$pdo = getDBConnection();
$themeClass = getThemeClass();

/* ---------- Helpers ---------- */
function e($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

function initials(string $name): string {
    $parts = preg_split('/\s+/', trim($name)) ?: [];
    $first = mb_substr($parts[0] ?? '?', 0, 1);
    $last  = count($parts) > 1 ? mb_substr(end($parts), 0, 1) : '';
    return mb_strtoupper($first . $last);
}

/* ---------- CSRF ---------- */
if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

/* ---------- Ações (POST → redirect → GET) ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $flash = ['type' => 'error', 'text' => 'Não foi possível concluir a ação. Tente novamente.'];

    $token    = $_POST['csrf'] ?? '';
    $action   = $_POST['action'] ?? '';
    $targetId = (int)($_POST['user_id'] ?? 0);

    if (!hash_equals($_SESSION['csrf'], $token)) {
        $flash['text'] = 'Sessão expirada. Recarregue a página e tente de novo.';
    } elseif ($targetId === (int)$_SESSION['user_id']) {
        $flash = ['type' => 'warn', 'text' => 'Você não pode alterar a própria conta por aqui.'];
    } else {
        $stmt = $pdo->prepare("SELECT id, role FROM users WHERE id = ?");
        $stmt->execute([$targetId]);
        $target = $stmt->fetch();

        if (!$target) {
            $flash['text'] = 'Usuário não encontrado.';
        } elseif (($target['role'] === 'admin' || $target['role'] === 'super_admin') && !isSuperAdmin()) {
            // Trava de segurança: Apenas o Super Admin pode gerir outros administradores
            $flash = ['type' => 'error', 'text' => 'Acesso negado: Apenas o Super Admin pode gerir administradores.'];
        } elseif ($action === 'toggle_role') {
            $newRole = $target['role'] === 'admin' ? 'user' : 'admin';
            
            // Impede que administradores comuns promovam novos utilizadores a admin
            if ($newRole === 'admin' && !isSuperAdmin()) {
                $flash = ['type' => 'error', 'text' => 'Acesso negado: Apenas o Super Admin pode promover usuários a administradores.'];
            } else {
                $pdo->prepare("UPDATE users SET role = ? WHERE id = ?")->execute([$newRole, $targetId]);
                $flash = ['type' => 'success', 'text' => 'Permissão atualizada.'];
            }
        } elseif ($action === 'delete_user') {
            $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$targetId]);
            $flash = ['type' => 'success', 'text' => 'Usuário excluído.'];
        }
    }

    $_SESSION['flash'] = $flash;
    header('Location: dashboard.php');
    exit;
}

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

/* ---------- Dados ---------- */
$totalUsers   = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalAdmins  = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role IN ('admin', 'super_admin')")->fetchColumn();
$totalRegular = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn();
$adminPct     = $totalUsers > 0 ? round($totalAdmins / $totalUsers * 100) : 0;

$users = $pdo->query("SELECT id, full_name, email, role, created_at FROM users ORDER BY created_at DESC")->fetchAll();
$me    = (int)$_SESSION['user_id'];

$flashStyles = [
    'success' => ['bg-emerald-50 border-emerald-200 text-emerald-800', 'i-check'],
    'warn'    => ['bg-amber-50 border-amber-200 text-amber-800',       'i-alert'],
    'error'   => ['bg-red-50 border-red-200 text-red-800',             'i-alert'],
];
?>
<!DOCTYPE html>
<html lang="pt-BR" class="<?= $themeClass ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Usuários - Chat Adaptativo</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.2/font/bootstrap-icons.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: { extend: {
                fontFamily: { sans: ['Manrope', 'ui-sans-serif', 'system-ui', 'sans-serif'] },
                colors: { brand: { orange: '#FF5A00', navy: '#09056F' } }
            } }
        };
    </script>

    <style>
        body { font-family: 'Manrope', ui-sans-serif, system-ui, sans-serif; -webkit-font-smoothing: antialiased; }
        .ic { width: 1.125rem; height: 1.125rem; fill: none; stroke: currentColor; stroke-width: 1.75; stroke-linecap: round; stroke-linejoin: round; flex-shrink: 0; }
        .ic-sm { width: 0.95rem; height: 0.95rem; }
        .hex { clip-path: polygon(50% 0, 100% 25%, 100% 75%, 50% 100%, 0 75%, 0 25%); }
        :focus-visible { outline: 2px solid #FF5A00; outline-offset: 2px; border-radius: 6px; }
        @keyframes rise { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: none; } }
        .rise { animation: rise .45s cubic-bezier(.2,.7,.2,1) both; }
        .rise-1 { animation-delay: .05s; } .rise-2 { animation-delay: .12s; } .rise-3 { animation-delay: .19s; }
        @keyframes alertIn { from { opacity: 0; transform: translateY(-8px) scale(.98); } to { opacity: 1; transform: none; } }
        .alert-in { animation: alertIn .35s cubic-bezier(.2,.7,.2,1) both; }
        .alert-out { opacity: 0; transform: translateY(-6px); transition: all .25s ease; }
        .metric { transition: transform .2s ease, box-shadow .2s ease, border-color .2s ease; }
        .metric:hover { transform: translateY(-2px); box-shadow: 0 8px 24px -12px rgba(9,5,111,.25); border-color: #d6d8e8; }
        .row-user { transition: background-color .15s ease; }
        .row-user:hover { background-color: #F9FAFC; }
        .row-user:hover .row-actions { opacity: 1; }
        @media (hover: hover) { .row-actions { opacity: .75; transition: opacity .15s ease; } }
        #deleteModal { transition: opacity .2s ease; }
        #deleteModal .modal-card { transition: transform .2s cubic-bezier(.2,.7,.2,1); transform: translateY(8px) scale(.98); }
        #deleteModal.open .modal-card { transform: none; }
        @media (prefers-reduced-motion: reduce) { *, *::before, *::after { animation: none !important; transition: none !important; } }

        html.dark body { background-color: #0c0a09 !important; color: #f5f5f4 !important; }
        html.dark main, html.dark header, html.dark .bg-white, html.dark .modal-card { background-color: #1c1917 !important; color: #f5f5f4 !important; border-color: #292524 !important; }
        html.dark input[type="search"] { background-color: #292524 !important; color: #f5f5f4 !important; border-color: #44403c !important; }
        html.dark .row-user:hover { background-color: #292524 !important; }
        html.dark .text-slate-900{color: #ffffff !important;}
        html.dark th, html.dark td { border-color: #292524 !important; }
        html.dark .text-slate-500, html.dark .text-slate-600 { color: #a8a29e !important; }
        html.dark .bg-slate-50, html.dark .bg-slate-100 { background-color: #292524 !important; color: #f5f5f4 !important; border-color: #44403c !important; }
        html.dark aside, html.dark nav { background-color: #1c1917 !important; border-color: #292524 !important; color: #dfdfde !important; }
        html.dark aside a, html.dark nav a { color: #d6d3d1 !important; }
        html.dark .text-brand-navy {color: #f5f5f4 !important;}
        html.dark .text-stone-900 {color: #f5f5f4 !important;}
        html.dark aside a:hover, html.dark nav a:hover { background-color: #99919163 !important; color: #d8d0d0 !important; }
    </style>
</head>
<body class="bg-[#F5F6FA] text-slate-700 flex h-screen overflow-hidden font-sans">

    <svg width="0" height="0" style="position:absolute" aria-hidden="true">
        <symbol id="i-users" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></symbol>
        <symbol id="i-user" viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></symbol>
        <symbol id="i-shield" viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></symbol>
        <symbol id="i-search" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></symbol>
        <symbol id="i-trash" viewBox="0 0 24 24"><path d="M3 6h18"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></symbol>
        <symbol id="i-up" viewBox="0 0 24 24"><path d="M12 19V5"/><path d="m5 12 7-7 7 7"/></symbol>
        <symbol id="i-down" viewBox="0 0 24 24"><path d="M12 5v14"/><path d="m19 12-7 7-7-7"/></symbol>
        <symbol id="i-x" viewBox="0 0 24 24"><path d="M18 6 6 18M6 6l12 12"/></symbol>
        <symbol id="i-check" viewBox="0 0 24 24"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><path d="m22 4-10 10-3-3"/></symbol>
        <symbol id="i-alert" viewBox="0 0 24 24"><path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"/><path d="M12 9v4M12 17h.01"/></symbol>
    </svg>

    <?php include '../includes/sidebar.php'; ?>

    <main class="flex-1 flex flex-col h-full overflow-y-auto">

        <header class="bg-white border-b border-slate-200 px-4 sm:px-8 py-5 flex items-center justify-between gap-4">
            <div class="rise">
                <h1 class="text-xl font-extrabold text-brand-navy tracking-tight">Usuários</h1>
                <p class="text-sm text-slate-500 mt-0.5">Controle quem acessa o Chat n8n e com qual permissão.</p>
            </div>
            <span class="rise hidden sm:inline-flex items-center gap-1.5 text-xs font-semibold text-brand-navy bg-brand-navy/5 border border-brand-navy/10 px-3 py-1.5 rounded-lg">
                <svg class="ic ic-sm"><use href="#i-shield"/></svg> <?= isSuperAdmin() ? 'Super Admin' : 'Administrador' ?>
            </span>
        </header>

        <div class="p-4 sm:p-8 space-y-6 max-w-6xl w-full mx-auto">

            <?php if ($flash):
                [$cls, $icon] = $flashStyles[$flash['type']] ?? $flashStyles['error']; ?>
                <div id="flash" role="status" class="alert-in flex items-start gap-3 border rounded-xl px-4 py-3 text-sm <?= $cls ?>">
                    <svg class="ic mt-0.5"><use href="#<?= $icon ?>"/></svg>
                    <p class="flex-1 font-medium"><?= e($flash['text']) ?></p>
                    <button type="button" id="flashClose" aria-label="Fechar aviso" class="opacity-60 hover:opacity-100 transition">
                        <svg class="ic ic-sm"><use href="#i-x"/></svg>
                    </button>
                </div>
            <?php endif; ?>

            <!-- Métricas -->
            <section class="grid grid-cols-1 md:grid-cols-3 gap-4" aria-label="Resumo">
                <div class="metric rise rise-1 bg-white rounded-2xl border border-slate-200 p-5">
                    <div class="flex items-center justify-between">
                        <p class="text-sm font-semibold text-slate-500">Total de usuários</p>
                        <span class="hex w-8 h-8 bg-brand-navy text-white grid place-items-center"><svg class="ic ic-sm"><use href="#i-users"/></svg></span>
                    </div>
                    <p class="text-3xl font-extrabold text-brand-navy mt-2"><?= $totalUsers ?></p>
                    <div class="mt-4 h-1.5 rounded-full bg-slate-100 overflow-hidden" role="img" aria-label="<?= $adminPct ?>% administradores">
                        <div class="h-full bg-brand-orange rounded-full" style="width: <?= $adminPct ?>%"></div>
                    </div>
                    <p class="text-xs text-slate-500 mt-2"><?= $adminPct ?>% com acesso administrativo</p>
                </div>

                <div class="metric rise rise-2 bg-white rounded-2xl border border-slate-200 p-5">
                    <div class="flex items-center justify-between">
                        <p class="text-sm font-semibold text-slate-500">Usuários comuns</p>
                        <span class="hex w-8 h-8 bg-slate-200 text-brand-navy grid place-items-center"><svg class="ic ic-sm" ><use href="#i-user"/></svg></span>
                    </div>
                    <p class="text-3xl font-extrabold text-brand-navy mt-2"><?= $totalRegular ?></p>
                    <p class="text-xs text-slate-500 mt-4">Acessam apenas o chat</p>
                </div>

                <div class="metric rise rise-3 bg-white rounded-2xl border border-slate-200 p-5">
                    <div class="flex items-center justify-between">
                        <p class="text-sm font-semibold text-slate-500">Administradores</p>
                        <span class="hex w-8 h-8 bg-brand-orange text-white grid place-items-center"><svg class="ic ic-sm"><use href="#i-shield"/></svg></span>
                    </div>
                    <p class="text-3xl font-extrabold text-brand-navy mt-2"><?= $totalAdmins ?></p>
                    <p class="text-xs text-slate-500 mt-4">Admins e Super Admins</p>
                </div>
            </section>

            <!-- Lista de usuários -->
            <section class="rise rise-3 bg-white rounded-2xl border border-slate-200 overflow-hidden" aria-labelledby="listTitle">
                <div class="px-5 sm:px-6 py-4 border-b border-slate-200 flex flex-col md:flex-row md:items-center gap-3 md:justify-between">
                    <div>
                        <h2 id="listTitle" class="font-bold text-brand-navy">Todos os usuários</h2>
                        <p class="text-xs text-slate-500 mt-0.5">Mostrando <span id="count"><?= count($users) ?></span> de <?= count($users) ?></p>
                    </div>

                    <div class="flex flex-col sm:flex-row gap-2">
                        <label class="relative">
                            <span class="sr-only">Buscar por nome ou e-mail</span>
                            <svg class="ic ic-sm absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none"><use href="#i-search"/></svg>
                            <input id="q" type="search" placeholder="Buscar por nome ou e-mail"
                                   class="w-full sm:w-64 pl-9 pr-3 py-2 text-sm bg-slate-50 border border-slate-200 rounded-lg placeholder:text-slate-400 focus:bg-white focus:border-brand-orange focus:outline-none">
                        </label>

                        <div class="inline-flex p-0.5 bg-slate-100 rounded-lg text-xs font-semibold" role="group" aria-label="Filtrar por cargo">
                            <button type="button" data-filter="all"   aria-pressed="true"  class="filter-btn px-3 py-1.5 rounded-md transition">Todos</button>
                            <button type="button" data-filter="admin" aria-pressed="false" class="filter-btn px-3 py-1.5 rounded-md transition">Admins</button>
                            <button type="button" data-filter="user"  aria-pressed="false" class="filter-btn px-3 py-1.5 rounded-md transition">Usuários</button>
                        </div>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr class="text-xs font-semibold text-slate-500 border-b border-slate-200 bg-slate-50/60">
                                <th scope="col" class="py-3 px-5 sm:px-6">Usuário</th>
                                <th scope="col" class="py-3 px-4">Cargo</th>
                                <th scope="col" class="py-3 px-4 hidden md:table-cell">Cadastro</th>
                                <th scope="col" class="py-3 px-5 sm:px-6 text-right">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                        <?php foreach ($users as $u):
                            $isMe      = ((int)$u['id'] === $me);
                            $isSuper   = $u['role'] === 'super_admin';
                            $isAdmin   = $u['role'] === 'admin';
                            $isElevated = ($isSuper || $isAdmin);
                            $canManage = isSuperAdmin() || (!$isElevated);
                            $ts        = strtotime($u['created_at']);
                        ?>
                            <tr class="row-user"
                                data-role="<?= e($u['role']) ?>"
                                data-search="<?= e(mb_strtolower($u['full_name'] . ' ' . $u['email'])) ?>">
                                <td class="py-3.5 px-5 sm:px-6">
                                    <div class="flex items-center gap-3 min-w-0">
                                        <span class="hex w-10 h-10 shrink-0 grid place-items-center text-xs font-extrabold <?= $isElevated ? 'bg-brand-navy text-white' : 'bg-slate-200 text-brand-navy' ?>" aria-hidden="true">
                                            <?= e(initials($u['full_name'])) ?>
                                        </span>
                                        <div class="min-w-0">
                                            <p class="font-semibold text-slate-900 truncate">
                                                <?= e($u['full_name']) ?>
                                                <?php if ($isMe): ?>
                                                    <span class="ml-1.5 align-middle text-[11px] font-semibold text-brand-orange bg-brand-orange/10 px-1.5 py-0.5 rounded">Você</span>
                                                <?php endif; ?>
                                            </p>
                                            <p class="text-xs text-slate-500 truncate"><?= e($u['email']) ?></p>
                                        </div>
                                    </div>
                                </td>

                                <td class="py-3.5 px-4">
                                    <span class="inline-flex items-center gap-1.5 text-xs font-semibold px-2.5 py-1 rounded-full <?= $isSuper ? 'bg-brand-orange/10 text-brand-orange' : ($isAdmin ? 'bg-brand-navy/10 text-brand-navy' : 'bg-slate-100 text-slate-600') ?>">
                                        <span class="w-1.5 h-1.5 rounded-full <?= $isElevated ? 'bg-brand-orange' : 'bg-slate-400' ?>"></span>
                                        <?= $isSuper ? 'Super Admin' : ($isAdmin ? 'Admin' : 'Usuário') ?>
                                    </span>
                                </td>

                                <td class="py-3.5 px-4 hidden md:table-cell text-slate-500 whitespace-nowrap">
                                    <?= date('d/m/Y', $ts) ?>
                                    <span class="text-xs text-slate-400 ml-1"><?= date('H:i', $ts) ?></span>
                                </td>

                                <td class="py-3.5 px-5 sm:px-6">
                                    <?php if (!$isMe): ?>
                                        <?php if ($canManage): ?>
                                            <div class="row-actions flex items-center justify-end gap-2">
                                                <form action="dashboard.php" method="POST">
                                                    <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">
                                                    <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                                                    <input type="hidden" name="action" value="toggle_role">
                                                    <button type="submit" class="inline-flex items-center gap-1.5 text-xs font-semibold text-brand-navy bg-white border border-slate-200 px-3 py-1.5 rounded-lg hover:border-brand-navy/40 hover:bg-brand-navy/5 transition">
                                                        <svg class="ic ic-sm"><use href="#<?= $isAdmin ? 'i-down' : 'i-up' ?>"/></svg>
                                                        <span class="hidden sm:inline"><?= $isAdmin ? 'Remover admin' : 'Tornar admin' ?></span>
                                                    </button>
                                                </form>

                                                <button type="button"
                                                        data-delete
                                                        data-id="<?= (int)$u['id'] ?>"
                                                        data-name="<?= e($u['full_name']) ?>"
                                                        aria-label="Excluir <?= e($u['full_name']) ?>"
                                                        class="grid place-items-center w-8 h-8 text-red-600 bg-white border border-slate-200 rounded-lg hover:bg-red-50 hover:border-red-200 transition">
                                                    <svg class="ic ic-sm"><use href="#i-trash"/></svg>
                                                </button>
                                            </div>
                                        <?php else: ?>
                                            <p class="text-xs text-slate-400 text-right">Protegido</p>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <p class="text-xs text-slate-400 text-right">Sua conta</p>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>

                    <div id="empty" class="hidden px-6 py-12 text-center">
                        <p class="font-semibold text-brand-navy">Nenhum usuário encontrado</p>
                        <p class="text-sm text-slate-500 mt-1">Tente outro nome, e-mail ou cargo.</p>
                    </div>
                </div>
            </section>
        </div>
    </main>

    <!-- Modal de exclusão -->
    <div id="deleteModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-brand-navy/40 backdrop-blur-[2px] opacity-0 pointer-events-none"
         role="dialog" aria-modal="true" aria-labelledby="modalTitle" aria-hidden="true">
        <div class="modal-card bg-white rounded-2xl shadow-2xl w-full max-w-sm p-6">
            <span class="hex w-11 h-11 bg-red-50 text-red-600 grid place-items-center"><svg class="ic"><use href="#i-trash"/></svg></span>
            <h2 id="modalTitle" class="text-lg font-extrabold text-brand-navy mt-4">Excluir usuário?</h2>
            <p class="text-sm text-slate-600 mt-2">
                <strong id="modalName" class="text-slate-900"></strong> perderá o acesso ao chat e a conta será removida. Esta ação não pode ser desfeita.
            </p>

            <form action="dashboard.php" method="POST" class="mt-6 flex gap-2 justify-end">
                <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">
                <input type="hidden" name="action" value="delete_user">
                <input type="hidden" name="user_id" id="modalUserId" value="">
                <button type="button" id="modalCancel" class="text-sm font-semibold text-slate-600 px-4 py-2 rounded-lg hover:bg-slate-100 transition">Cancelar</button>
                <button type="submit" class="text-sm font-semibold text-white bg-red-600 px-4 py-2 rounded-lg hover:bg-red-700 transition">Excluir usuário</button>
            </form>
        </div>
    </div>

    <script>
    (function () {
        const flash = document.getElementById('flash');
        if (flash) {
            const dismiss = () => { flash.classList.add('alert-out'); setTimeout(() => flash.remove(), 260); };
            document.getElementById('flashClose').addEventListener('click', dismiss);
            setTimeout(dismiss, 5000);
        }

        const rows  = [...document.querySelectorAll('.row-user')];
        const q     = document.getElementById('q');
        const count = document.getElementById('count');
        const empty = document.getElementById('empty');
        const btns  = [...document.querySelectorAll('.filter-btn')];
        let role = 'all';

        const ON  = ['bg-white', 'text-brand-navy', 'shadow-sm'];
        const OFF = ['text-slate-500'];

        function paintButtons() {
            btns.forEach(b => {
                const active = b.dataset.filter === role;
                b.setAttribute('aria-pressed', active);
                b.classList.remove(...ON, ...OFF);
                b.classList.add(...(active ? ON : OFF));
            });
        }

        function apply() {
            const term = q.value.trim().toLowerCase();
            let shown = 0;
            rows.forEach(r => {
                // Se o filtro for 'admin', considera tanto 'admin' quanto 'super_admin'
                let roleMatch = true;
                if (role === 'admin') {
                    roleMatch = (r.dataset.role === 'admin' || r.dataset.role === 'super_admin');
                } else if (role === 'user') {
                    roleMatch = (r.dataset.role === 'user');
                }
                const ok = roleMatch && r.dataset.search.includes(term);
                r.classList.toggle('hidden', !ok);
                if (ok) shown++;
            });
            count.textContent = shown;
            empty.classList.toggle('hidden', shown !== 0);
        }

        btns.forEach(b => b.addEventListener('click', () => { role = b.dataset.filter; paintButtons(); apply(); }));
        q.addEventListener('input', apply);
        paintButtons();

        const modal  = document.getElementById('deleteModal');
        const cancel = document.getElementById('modalCancel');
        let lastTrigger = null;

        function openModal(btn) {
            lastTrigger = btn;
            document.getElementById('modalName').textContent = btn.dataset.name;
            document.getElementById('modalUserId').value = btn.dataset.id;
            modal.classList.remove('opacity-0', 'pointer-events-none');
            modal.classList.add('open');
            modal.setAttribute('aria-hidden', 'false');
            cancel.focus();
        }
        function closeModal() {
            modal.classList.add('opacity-0', 'pointer-events-none');
            modal.classList.remove('open');
            modal.setAttribute('aria-hidden', 'true');
            if (lastTrigger) lastTrigger.focus();
        }

        document.querySelectorAll('[data-delete]').forEach(b => b.addEventListener('click', () => openModal(b)));
        cancel.addEventListener('click', closeModal);
        modal.addEventListener('click', e => { if (e.target === modal) closeModal(); });
        document.addEventListener('keydown', e => { if (e.key === 'Escape' && modal.classList.contains('open')) closeModal(); });
    })();
    </script>
</body>
</html>