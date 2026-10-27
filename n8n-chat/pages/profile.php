<?php
    require_once __DIR__ . '/../config/auth.php';
    requireLogin();

    // Garante que a conexão $pdo está ativa
    $pdo = getDBConnection();

    $themeClass = getThemeClass();
    $userId = $_SESSION['user_id'];
    $success = '';
    $error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if (empty($full_name) || empty($email)) {
        $error = "Nome e e-mail são obrigatórios.";
    } else {
        // Verifica se o e-mail já pertence a outro usuário
        $stmt = $pdo->prepare("SELECT id, avatar FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$email, $userId]);
        if ($stmt->fetch()) {
            $error = "Este e-mail já está em uso por outra conta.";
        } else {
            // Processamento de Upload de Foto de Perfil
            $avatarPath = null;
            if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
                $fileTmpPath = $_FILES['avatar']['tmp_name'];
                $fileName = $_FILES['avatar']['name'];
                $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                
                $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
                if (in_array($fileExtension, $allowedExtensions)) {
                    $uploadDir = __DIR__ . '/../uploads/avatars/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }
                    
                    $newFileName = 'user_' . $userId . '_' . time() . '.' . $fileExtension;
                    $destPath = $uploadDir . $newFileName;
                    $avatarPath = 'uploads/avatars/' . $newFileName;
                    
                    if (move_uploaded_file($fileTmpPath, $destPath)) {
                        // O banco guarda uma URL relativa ao projeto; o sistema usa o caminho físico acima.
                    } else {
                        $error = "Erro ao mover o arquivo de imagem enviado.";
                    }
                } else {
                    $error = "Formato de imagem inválido. Use JPG, PNG ou WEBP.";
                }
            }

            if (empty($error)) {
                // Atualiza senha se preenchida
                if (!empty($new_password)) {
                    if ($new_password !== $confirm_password) {
                        $error = "As novas senhas não coincidem.";
                    } elseif (strlen($new_password) < 6) {
                        $error = "A nova senha deve ter pelo menos 6 caracteres.";
                    } else {
                        $hashedPassword = password_hash($new_password, PASSWORD_BCRYPT);
                        if ($avatarPath !== null) {
                            $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, password = ?, avatar = ? WHERE id = ?");
                            $stmt->execute([$full_name, $email, $hashedPassword, $avatarPath, $userId]);
                        } else {
                            $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, password = ? WHERE id = ?");
                            $stmt->execute([$full_name, $email, $hashedPassword, $userId]);
                        }
                        $success = "Perfil e senha atualizados com sucesso!";
                    }
                } else {
                    if ($avatarPath !== null) {
                        $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, avatar = ? WHERE id = ?");
                        $stmt->execute([$full_name, $email, $avatarPath, $userId]);
                    } else {
                        $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ? WHERE id = ?");
                        $stmt->execute([$full_name, $email, $userId]);
                    }
                    $success = "Perfil atualizado com sucesso!";
                }

                // Atualiza as variáveis de sessão
                $_SESSION['user_name'] = $full_name;
                $_SESSION['user_email'] = $email;
            }
        }
    }
}
// Busca dados atuais do banco (incluindo avatar, se a coluna existir)
// Nota: Se a coluna 'avatar' ainda não existir no seu banco, adicione-a com: ALTER TABLE users ADD COLUMN avatar VARCHAR(255) NULL;
try {
    $stmt = $pdo->prepare("SELECT full_name, email, role, created_at, avatar FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $currentUser = $stmt->fetch();
} catch (PDOException $e) {
    // Fallback caso a coluna avatar ainda não tenha sido criada na tabela
    $stmt = $pdo->prepare("SELECT full_name, email, role, created_at FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $currentUser = $stmt->fetch();
    $currentUser['avatar'] = null;
}
?>
<!DOCTYPE html>
<html lang="pt-BR" class="<?= $themeClass ?>">
<head>
    <meta charset="UTF-8">
    <title>Meu Perfil</title>
    <link rel="stylesheet" href="/Adaptis_N8N/n8n-chat/assets/css/profile.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="shortcut icon" href="/Adaptis_N8N/n8n-chat/assets/images/Adaptis_logo2.ico" type="image/x-icon">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.2/font/bootstrap-icons.min.css">
    <script>
      tailwind.config = {
        darkMode: 'class', // Essencial para o Tailwind reconhecer a classe 'dark'
      }
    </script>
</head>
<body class="bg-gradient-to-br from-gray-50 to-gray-100 flex h-screen overflow-hidden">
    
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="flex-1 flex flex-col h-full overflow-y-auto">
        <header class="bg-white/80 backdrop-blur-md border-b px-8 py-5 shadow-xs flex justify-between items-center sticky top-0 z-10">
            <div>
                <h1 class="text-2xl font-extrabold text-gray-800 tracking-tight">Meu Perfil</h1>
                <p class="text-xs text-gray-500 mt-0.5">Gerencie suas informações pessoais, foto de avatar e credenciais de acesso</p>
            </div>
            <div class="hidden sm:flex items-center space-x-2 bg-indigo-50 border border-indigo-100 px-3 py-1.5 rounded-full text-indigo-700 text-xs font-medium">
                <i class="bi bi-shield-check text-sm"></i>
                <span class="verify">Conta Verificada</span>
            </div>
        </header>

        <div class="p-8 max-w-4xl w-full mx-auto">
            
            <?php if ($error): ?>
                <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 rounded-r-lg mb-6 shadow-xs flex items-center space-x-3 text-sm">
                    <i class="bi bi-exclamation-triangle-fill text-lg"></i>
                    <span><?= htmlspecialchars($error) ?></span>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="bg-emerald-50 border-l-4 border-emerald-500 text-emerald-700 p-4 rounded-r-lg mb-6 shadow-xs flex items-center space-x-3 text-sm">
                    <i class="bi bi-check-circle-fill text-lg"></i>
                    <span class="sucess"><?= htmlspecialchars($success) ?></span>
                </div>
            <?php endif; ?>

            <form action="/Adaptis_N8N/n8n-chat/pages/profile.php" method="POST" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-3 gap-6">
                
                <!-- Coluna Lateral: Foto e Resumo -->
                <div class="md:col-span-1 space-y-6">
                    <div class="bg-white p-6 rounded-2xl shadow-xs border border-gray-200/80 text-center relative overflow-hidden">
                        <div class="absolute top-0 left-0 right-0 h-20 "></div>
                        
                        <div class="relative mt-6 inline-block">
                            <div class="w-28 h-28 rounded-full overflow-hidden border-4 border-white shadow-md mx-auto bg-gray-200 flex items-center justify-center">
                                <?php if (!empty($currentUser['avatar']) && file_exists($currentUser['avatar'])): ?>
                                    <img src="<?= htmlspecialchars($currentUser['avatar']) ?>" alt="Avatar" class="w-full h-full object-cover" id="avatar-preview">
                                <?php else: ?>
                                    <span class="text-3xl font-bold text-gray-400" id="avatar-initials">
                                        <?= strtoupper(substr($currentUser['full_name'], 0, 2)) ?>
                                    </span>
                                    <img id="avatar-preview-img" class="w-full h-full object-cover hidden" alt="Avatar Preview">
                                <?php endif; ?>
                            </div>
                            
                            <!-- Botão flutuante para trocar foto -->
                            <label for="avatar-input" class="absolute bottom-0 right-0 bg-indigo-600 hover:bg-indigo-700 text-white p-2 rounded-full shadow-lg cursor-pointer transition transform hover:scale-105" title="Alterar foto">
                                <i class="bi bi-camera-fill text-xs"></i>
                            </label>
                            <input type="file" name="avatar" id="avatar-input" class="hidden" accept="image/png, image/jpeg, image/webp" onchange="previewImage(event)">
                        </div>

                        <h2 class="mt-4 font-bold text-gray-800 text-lg truncate"><?= htmlspecialchars($currentUser['full_name']) ?></h2>
                        <p class="text-xs text-gray-500 truncate"><?= htmlspecialchars($currentUser['email']) ?></p>

                        <div class="mt-6 pt-6 border-t border-gray-100 text-left space-y-3">
                            <div class="flex justify-between text-xs">
                                <span class="text-gray-400 font-medium">Cargo/Função:</span>
                                <span class="font-semibold text-gray-700 uppercase"><?= htmlspecialchars($currentUser['role'] ?? 'Usuário') ?></span>
                            </div>
                            <div class="flex justify-between text-xs">
                                <span class="text-gray-400 font-medium">Membro desde:</span>
                                <span class="font-semibold text-gray-700"><?= date('d/m/Y', strtotime($currentUser['created_at'])) ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Coluna Principal: Formulário de Edição -->
                <div class="md:col-span-2">
                    <div class="bg-white p-8 rounded-2xl shadow-xs border border-gray-200/80 space-y-6">
                        <h3 class="text-base font-bold text-gray-800 flex items-center space-x-2 border-b pb-3">
                            <i class="bi bi-person-gear text-indigo-600"></i>
                            <span>Informações Pessoais</span>
                        </h3>

                        <div class="space-y-4">
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wider mb-1">Nome Completo</label>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                                        <i class="bi bi-person"></i>
                                    </span>
                                    <input type="text" name="full_name" required value="<?= htmlspecialchars($currentUser['full_name']) ?>" class="w-full pl-10 pr-4 py-2.5 bg-gray-50/50 border border-gray-300 rounded-xl focus:bg-white focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 text-sm transition">
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wider mb-1">E-mail</label>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                                        <i class="bi bi-envelope"></i>
                                    </span>
                                    <input type="email" name="email" required value="<?= htmlspecialchars($currentUser['email']) ?>" class="w-full pl-10 pr-4 py-2.5 bg-gray-50/50 border border-gray-300 rounded-xl focus:bg-white focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 text-sm transition">
                                </div>
                            </div>
                        </div>

                        <div class="pt-4 border-t border-gray-100">
                            <h3 class="text-base font-bold text-gray-800 flex items-center space-x-2 mb-4">
                                <i class="bi bi-lock text-indigo-600"></i>
                                <span>Segurança (Alterar Senha)</span>
                            </h3>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wider mb-1">Nova Senha</label>
                                    <input type="password" name="new_password" placeholder="••••••••" class="w-full px-4 py-2.5 bg-gray-50/50 border border-gray-300 rounded-xl focus:bg-white focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 text-sm transition">
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wider mb-1">Confirmar Senha</label>
                                    <input type="password" name="confirm_password" placeholder="••••••••" class="w-full px-4 py-2.5 bg-gray-50/50 border border-gray-300 rounded-xl focus:bg-white focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 text-sm transition">
                                </div>
                            </div>
                            <p class="text-xs text-gray-400 mt-2">Deixe os campos de senha em branco caso não deseje alterá-la.</p>
                        </div>

                        <div class="pt-6 border-t border-gray-100 flex justify-end">
                            <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-2.5 rounded-xl font-semibold text-sm shadow-md shadow-indigo-200 transition-all transform hover:-translate-y-0.5 flex items-center space-x-2">
                                <i class="bi bi-check2-all"></i>
                                <span>Salvar Alterações</span>
                            </button>
                        </div>
                    </div>
                </div>

            </form>
        </div>
    </main>

    <!-- Script para pré-visualização da imagem de perfil ao selecionar -->
    <script>
        function previewImage(event) {
            const reader = new FileReader();
            reader.onload = function() {
                const output = document.getElementById('avatar-preview') || document.getElementById('avatar-preview-img');
                if (output) {
                    output.src = reader.result;
                    output.classList.remove('hidden');
                }
                const initials = document.getElementById('avatar-initials');
                if (initials) {
                    initials.style.display = 'none';
                }
            }
            if(event.target.files[0]) {
                reader.readAsDataURL(event.target.files[0]);
            }
        }
    </script>
</body>
</html>