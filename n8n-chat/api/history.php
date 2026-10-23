<?php
require_once __DIR__ . '/../config/auth.php';
requireLogin();
header('Content-Type: application/json; charset=utf-8');

try {
    require_once __DIR__ . '/../config/auth.php';
    
    // Se não estiver logado, em vez de carregar HTML, devolve um erro JSON limpo
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['ok' => false, 'error' => 'Sessão expirada. Faça login novamente.']);
        exit;
    }
    
    requireLogin();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Erro de autenticação: ' . $e->getMessage()]);
    exit;
}

function db(): PDO {
    global $pdo;
    // Se o seu config/database.php já cria a variável $pdo global:
    if (file_exists(__DIR__ . '/../config/database.php')) {
        require_once __DIR__ . '/../config/database.php';
    }
    if (isset($pdo) && $pdo instanceof PDO) {
        return $pdo;
    }
    
    // Fallback caso o $pdo global não venha preenchido:
    static $instance = null;
    if (!$instance) {
        $instance = new PDO('mysql:host=127.0.0.1;dbname=n8n_chat_db;charset=utf8mb4', 'USUARIO', 'SENHA', [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
    }
    return $instance;
}
/* ============================================================ */

function out(array $d, int $code = 200): never {
    http_response_code($code);
    echo json_encode($d + ['ok' => $code < 400], JSON_UNESCAPED_UNICODE);
    exit;
}
function fail(string $msg, int $code = 400): never { out(['error' => $msg], $code); }

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') !== 'fetch') {
    fail('Requisição inválida');
}

$in  = json_decode(file_get_contents('php://input'), true) ?: [];
$uid = (string)$_SESSION['user_id'];
$pdo = db();

function ownConversation(PDO $pdo, $id, string $uid): array {
    $st = $pdo->prepare('SELECT * FROM chat_conversations WHERE id = ? AND user_id = ?');
    $st->execute([(int)$id, $uid]);
    return $st->fetch() ?: fail('Conversa não encontrada', 404);
}
function messagesOf(PDO $pdo, int $convId): array {
    $st = $pdo->prepare('SELECT id, role, content, created_at FROM chat_messages WHERE conversation_id = ? ORDER BY id');
    $st->execute([$convId]);
    return $st->fetchAll();
}
function sharedConversation(PDO $pdo, $token): array {
    $st = $pdo->prepare('SELECT * FROM chat_conversations WHERE share_token = ?');
    $st->execute([(string)$token]);
    return $st->fetch() ?: fail('Link inválido ou deixou de ser compartilhado', 404);
}

switch ($in['action'] ?? '') {

    case 'list':
        $st = $pdo->prepare('SELECT id, title, share_token IS NOT NULL AS shared, updated_at
                             FROM chat_conversations WHERE user_id = ? ORDER BY updated_at DESC LIMIT 200');
        $st->execute([$uid]);
        out(['items' => $st->fetchAll()]);

    case 'get':
        $c = ownConversation($pdo, $in['id'] ?? 0, $uid);
        out(['conversation' => ['id' => (int)$c['id'], 'title' => $c['title'], 'shared' => $c['share_token'] !== null],
             'messages' => messagesOf($pdo, (int)$c['id'])]);

    case 'create':
        $title = trim(mb_substr((string)($in['title'] ?? 'Nova conversa'), 0, 80)) ?: 'Nova conversa';
        $pdo->prepare('INSERT INTO chat_conversations (user_id, title) VALUES (?, ?)')->execute([$uid, $title]);
        out(['id' => (int)$pdo->lastInsertId(), 'title' => $title]);

    case 'add_message':
        $c = ownConversation($pdo, $in['conversation_id'] ?? 0, $uid);
        $role = ($in['role'] ?? '') === 'user' ? 'user' : 'bot';
        $content = mb_substr((string)($in['content'] ?? ''), 0, 20000);
        if (trim($content) === '') fail('Mensagem vazia');
        $pdo->prepare('INSERT INTO chat_messages (conversation_id, role, content) VALUES (?, ?, ?)')
            ->execute([$c['id'], $role, $content]);
        $id = (int)$pdo->lastInsertId();
        $pdo->prepare('UPDATE chat_conversations SET updated_at = NOW() WHERE id = ?')->execute([$c['id']]);
        out(['id' => $id]);

    case 'update_message':
        $content = mb_substr((string)($in['content'] ?? ''), 0, 20000);
        $st = $pdo->prepare('UPDATE chat_messages m JOIN chat_conversations c ON c.id = m.conversation_id
                             SET m.content = ?, c.updated_at = NOW() WHERE m.id = ? AND c.user_id = ?');
        $st->execute([$content, (int)($in['id'] ?? 0), $uid]);
        out([]);

    case 'rename':
        $c = ownConversation($pdo, $in['id'] ?? 0, $uid);
        $title = trim(mb_substr((string)($in['title'] ?? ''), 0, 80));
        if ($title === '') fail('Título vazio');
        $pdo->prepare('UPDATE chat_conversations SET title = ? WHERE id = ?')->execute([$title, $c['id']]);
        out(['title' => $title]);

    case 'delete':
        $c = ownConversation($pdo, $in['id'] ?? 0, $uid);
        $pdo->prepare('DELETE FROM chat_conversations WHERE id = ?')->execute([$c['id']]);
        out([]);

    case 'share':
        $c = ownConversation($pdo, $in['id'] ?? 0, $uid);
        $token = $c['share_token'] ?: bin2hex(random_bytes(16));
        $pdo->prepare('UPDATE chat_conversations SET share_token = ? WHERE id = ?')->execute([$token, $c['id']]);
        out(['token' => $token]);

    case 'unshare':
        $c = ownConversation($pdo, $in['id'] ?? 0, $uid);
        $pdo->prepare('UPDATE chat_conversations SET share_token = NULL WHERE id = ?')->execute([$c['id']]);
        out([]);

    case 'shared':
        $c = sharedConversation($pdo, $in['token'] ?? '');
        out(['conversation' => ['id' => (int)$c['id'], 'title' => $c['title']],
             'is_owner' => (string)$c['user_id'] === $uid,
             'messages' => messagesOf($pdo, (int)$c['id'])]);

    case 'clone':
        $c = sharedConversation($pdo, $in['token'] ?? '');
        $pdo->beginTransaction();
        $pdo->prepare('INSERT INTO chat_conversations (user_id, title) VALUES (?, ?)')
            ->execute([$uid, mb_substr('Cópia: ' . $c['title'], 0, 80)]);
        $newId = (int)$pdo->lastInsertId();
        $pdo->prepare('INSERT INTO chat_messages (conversation_id, role, content, created_at)
                       SELECT ?, role, content, created_at FROM chat_messages WHERE conversation_id = ? ORDER BY id')
            ->execute([$newId, $c['id']]);
        $pdo->commit();
        out(['id' => $newId]);

    default:
        fail('Ação desconhecida');
}