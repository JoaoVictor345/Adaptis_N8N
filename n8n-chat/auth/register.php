<?php
require_once __DIR__ . '/../config/auth.php';

if (isLoggedIn()) {
    header("Location: " . APP_BASE_PATH . (isAdmin() ? '/admin/dashboard.php' : '/pages/chat.php'));
    exit;
}

$error = '';
$success = '';
$themeClass = getThemeClass();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name']);
    $email     = trim($_POST['email']);
    $password  = trim($_POST['password']);
    $confirm   = trim($_POST['confirm_password']);

    if (empty($full_name) || empty($email) || empty($password)) {
        $error = "Por favor, preencha todos os campos.";
    } elseif ($password !== $confirm) {
        $error = "As senhas não coincidem.";
    } elseif (strlen($password) < 6) {
        $error = "A senha deve ter pelo menos 6 caracteres.";
    } else {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        
        if ($stmt->fetch()) {
            $error = "Este e-mail já está cadastrado.";
        } else {
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("INSERT INTO users (full_name, email, password, role) VALUES (?, ?, ?, 'user')");
            if ($stmt->execute([$full_name, $email, $hashedPassword])) {
                $success = "Conta criada com sucesso!";
            } else {
                $error = "Erro ao criar a conta. Tente novamente.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cadastro - ADAPTIS</title>
    <link rel="shortcut icon" href="/Adaptis_N8N/n8n-chat/assets/images/Adaptis_logo2.ico" type="image/x-icon">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/Adaptis_N8N/n8n-chat/assets/css/register.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.2/font/bootstrap-icons.min.css">
    <style>
        /* Estilo para alinhar o ícone do olho nos campos de senha */
        .password-container {
            position: relative;
            display: flex;
            align-items: center;
        }
        .password-container input {
            width: 100%;
            padding-right: 2.5rem;
        }
        .toggle-password {
            position: absolute;
            right: 0;
            background: none;
            border: none;
            cursor: pointer;
            color: var(--texto-suave);
            font-size: 1.2rem;
            padding: 0.2rem;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: color 0.2s;
        }
        .toggle-password:hover {
            color: var(--azul);
        }
    </style>
</head>
<body>
    <!-- Formas decorativas -->
    <div class="blob blob-azul-topo"></div>
    <div class="blob blob-laranja-direita"></div>
    <div class="blob blob-laranja-esquerda"></div>
    <div class="blob blob-azul-baixo"></div>

    <div class="register-wrapper">
        <div class="register-card">
            <div class="register-header">
                <img src="/Adaptis_N8N/n8n-chat/assets/images/Adaptis_logo2-removebg.png" alt="IDETE">
                <h2>Criar Conta</h2>
                <p>Adaptis · Assistente Virtual Adaptativo</p>
            </div>

            <?php if ($error): ?>
                <div class="alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert-success">
                    <?= htmlspecialchars($success) ?><br>
                    <a href="/Adaptis_N8N/n8n-chat/auth/login.php" class="btn-link">Ir para o Login</a>
                </div>
            <?php else: ?>

            <form action="/Adaptis_N8N/n8n-chat/auth/register.php" method="POST">
                <div class="form-group">
                    <label>Nome Completo</label>
                    <input type="text" name="full_name" required value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>" placeholder="Seu nome">
                </div>
                <div class="form-group">
                    <label>E-mail</label>
                    <input type="email" name="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" placeholder="seu.email@exemplo.com">
                </div>
                
                <div class="form-group">
                    <label>Senha</label>
                    <div class="password-container">
                        <input type="password" name="password" id="passwordInput" required placeholder="Mínimo 6 caracteres">
                        <button type="button" id="togglePasswordBtn" class="toggle-password" title="Mostrar senha">
                            <i class="bi-eye-slash" id="eyeIcon"></i>
                        </button>
                    </div>
                </div>

                <div class="form-group">
                    <label>Confirmar Senha</label>
                    <div class="password-container">
                        <input type="password" name="confirm_password" id="confirmPasswordInput" required placeholder="Repita a senha">
                        <button type="button" id="toggleConfirmBtn" class="toggle-password" title="Mostrar senha">
                            <i class="bi-eye-slash" id="eyeConfirmIcon"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn-submit">Cadastrar</button>
            </form>

            <?php endif; ?>

            <div class="register-footer">
                Já tem uma conta? <a href="/Adaptis_N8N/n8n-chat/auth/login.php">Fazer login</a>
            </div>
        </div>

        <div class="register-illustration" aria-hidden="true">
            <svg viewBox="0 0 480 380" xmlns="http://www.w3.org/2000/svg">
                <g fill="none" stroke="#c9d6e6" stroke-width="1.5">
                    <rect x="40" y="22" width="26" height="26" rx="3" fill="#e3ebf5"/>
                    <rect x="52" y="34" width="26" height="26" rx="3" fill="#e3ebf5"/>
                    <rect x="395" y="40" width="22" height="22" rx="3" fill="#e3ebf5"/>
                    <rect x="300" y="20" width="9" height="9" transform="rotate(45 304 24)"/>
                    <rect x="420" y="150" width="9" height="9" transform="rotate(45 424 154)"/>
                    <rect x="14" y="170" width="9" height="9" transform="rotate(45 18 174)"/>
                </g>
                <circle cx="240" cy="14" r="3" fill="#dbe5f1"/>
                <circle cx="440" cy="60" r="3" fill="#dbe5f1"/>
                <circle cx="20" cy="120" r="3" fill="#dbe5f1"/>

                <g fill="#e1e9f3">
                    <ellipse cx="38" cy="250" rx="16" ry="42" transform="rotate(-25 38 250)"/>
                    <ellipse cx="62" cy="228" rx="14" ry="38" transform="rotate(20 62 228)"/>
                    <ellipse cx="445" cy="265" rx="16" ry="42" transform="rotate(25 445 265)"/>
                    <ellipse cx="420" cy="245" rx="14" ry="36" transform="rotate(-20 420 245)"/>
                </g>

                <rect x="100" y="90" width="300" height="215" rx="10" fill="#c3d3e6"/>
                <rect x="55" y="300" width="390" height="14" rx="7" fill="#b4c7de"/>

                <rect x="42" y="62" width="350" height="230" rx="4" fill="#ffffff" stroke="#0f4c8a" stroke-width="4"/>
                <rect x="42" y="62" width="350" height="44" fill="#0f4c8a"/>
                <circle cx="66" cy="84" r="6" fill="#fff"/>
                <circle cx="90" cy="84" r="6" fill="#fff"/>
                <circle cx="114" cy="84" r="6" fill="#fff"/>

                <!-- ícone de usuário -->
                <circle cx="217" cy="140" r="17" fill="#9fb6d2"/>
                <path d="M186 198 a31 28 0 0 1 62 0 Z" fill="#9fb6d2"/>
                <circle cx="248" cy="152" r="13" fill="#f26b1d"/>
                <path d="M248 145 v14 M241 152 h14" stroke="#fff" stroke-width="3" stroke-linecap="round"/>

                <text x="217" y="248" text-anchor="middle" font-family="Manrope, sans-serif" font-weight="800" font-size="30" letter-spacing="5" fill="#f26b1d">CADASTRO</text>

                <rect x="165" y="296" width="62" height="6" rx="3" fill="#dfe8f3"/>
                <path d="M165 296 L180 280 H225 L222 296 Z" fill="#eaf0f8" stroke="#c3d3e6" stroke-width="1.5"/>
                <path d="M235 296 q30 -34 60 -22 l12 22 Z" fill="#f26b1d"/>
                <path d="M300 296 L395 296 L440 262 l-14 -10 l-30 24 l-90 4 Z" fill="#0f4c8a"/>
                <path d="M430 262 l22 -6 l-4 14 Z" fill="#f26b1d"/>
                <ellipse cx="262" cy="248" rx="19" ry="26" fill="#0f4c8a"/>
                <circle cx="266" cy="262" r="13" fill="#f6d7b8"/>
                <path d="M248 240 q16 -22 36 -4 q-4 16 -10 22 q-14 -14 -26 -18 Z" fill="#0f4c8a"/>
            </svg>
        </div>
    </div>

    <!-- Script para alternar a visibilidade de ambos os campos de senha -->
    <script>
        function setupPasswordToggle(btnId, inputId, iconId) {
            const btn = document.getElementById(btnId);
            const input = document.getElementById(inputId);
            const icon = document.getElementById(iconId);

            if (!btn || !input || !icon) return;

            btn.addEventListener('click', function () {
                const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
                input.setAttribute('type', type);

                if (type === 'text') {
                    icon.classList.remove('bi-eye-slash');
                    icon.classList.add('bi-eye');
                    btn.setAttribute('title', 'Ocultar senha');
                } else {
                    icon.classList.remove('bi-eye');
                    icon.classList.add('bi-eye-slash');
                    btn.setAttribute('title', 'Mostrar senha');
                }
            });
        }

        setupPasswordToggle('togglePasswordBtn', 'passwordInput', 'eyeIcon');
        setupPasswordToggle('toggleConfirmBtn', 'confirmPasswordInput', 'eyeConfirmIcon');
    </script>
</body>
</html>