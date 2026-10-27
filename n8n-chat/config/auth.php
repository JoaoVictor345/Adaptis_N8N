<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/database.php';



function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Verifica se é estritamente Super Admin
function isSuperAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'super_admin';
}

// Verifica se tem privilégios administrativos (Admin ou Super Admin)
function isAdmin() {
    return isset($_SESSION['user_role']) && ($_SESSION['user_role'] === 'admin' || $_SESSION['user_role'] === 'super_admin');
}


function requireLogin() {
    if (!isLoggedIn()) {
        // Caminho absoluto para garantir que vai buscar o login na raiz, mesmo estando na pasta admin/
        header("Location: /n8n-chat/login.php");
        exit;
    }
}

function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        // Caminho absoluto para garantir que vai buscar o 403 na raiz, mesmo estando na pasta admin/
        header("Location: /n8n-chat/403.php");
        exit;
    }
}

/**
 * Retorna os dados completos do utilizador atual logado da base de dados.
 */
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }

    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();

        if ($user && empty($user['avatar'])) {
            $user['avatar'] = 'Imagens/default-avatar.png';
        }

        return $user ? $user : null;
    } catch (Exception $e) {
        return null;
    }
}

/**
 * Retorna a classe CSS do tema do utilizador atual ('dark' ou vazio '')
 */
function getThemeClass() {
    $user = getCurrentUser();
    if ($user && isset($user['theme']) && $user['theme'] === 'dark') {
        return 'dark';
    }
    return '';
}
?>