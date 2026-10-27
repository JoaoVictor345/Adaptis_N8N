<?php
require_once __DIR__ . '/../config/auth.php';

if (isLoggedIn()) {
    header("Location: " . APP_BASE_PATH . (isAdmin() ? '/admin/dashboard.php' : '/pages/chat.php'));
    exit;
}

$error = '';
$themeClass = getThemeClass();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (empty($email) || empty($password)) {
        $error = "Por favor, preencha todos os campos.";
    } else {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['full_name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];

            header("Location: " . APP_BASE_PATH . ($user['role'] === 'admin' ? '/admin/dashboard.php' : '/pages/chat.php'));
            exit;
        } else {
            $error = "E-mail ou senha inválidos.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - ADAPTIS</title>
    <link rel="shortcut icon" href="/Adaptis_N8N/n8n-chat/assets/images/Adaptis_logo2.ico" type="image/x-icon">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/Adaptis_N8N/n8n-chat/assets/css/login.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.2/font/bootstrap-icons.min.css">
    <style>
        /* Estilo rápido para o contentor do input de senha com o ícone do olho */
        .password-container {
            position: relative;
            display: flex;
            align-items: center;
        }
        .password-container input {
            width: 100%;
            padding-right: 2.5rem; /* Espaço para o ícone não sobrepor o texto */
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

    <div class="login-wrapper">
        <div class="login-card">
            <div class="login-header">
                <img src="/Adaptis_N8N/n8n-chat/assets/images/Adaptis_logo2-removebg.png" alt="IDETE">
                <h2>Bem-vindo de Volta!</h2>
                <p>Adaptis · Assistente Virtual Adaptativo</p>
            </div>

            <?php if ($error): ?>
                <div class="alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form action="/Adaptis_N8N/n8n-chat/auth/login.php" method="POST">
                <div class="form-group">
                    <label>E-mail</label>
                    <input type="email" name="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" placeholder="seu.email@idete.com">
                </div>
                
                <div class="form-group">
                    <label>Senha</label>
                    <div class="password-container">
                        <input type="password" name="password" id="passwordInput" required placeholder="••••••••">
                        <button type="button" id="togglePasswordBtn" class="toggle-password" title="Mostrar senha">
                            <i class="bi-eye-slash" id="eyeIcon"></i>
                        </button>
                    </div>
                </div>

                <div class="form-extras">
                    <a href="/Adaptis_N8N/n8n-chat/auth/forgot-password.php">Esqueceu a senha?</a>
                </div>

                <button type="submit" class="btn-submit">Entrar</button>
            </form>

            <div class="login-footer">
                Não tem uma conta? <a href="/Adaptis_N8N/n8n-chat/auth/register.php">Cadastre-se</a>
            </div>
        </div>

        <div class="login-illustration" aria-hidden="true">
            <svg viewBox="0 0 480 380" xmlns="http://www.w3.org/2000/svg">
                <!-- decorações -->
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

                <!-- folhas -->
                <g fill="#e1e9f3">
                    <ellipse cx="38" cy="250" rx="16" ry="42" transform="rotate(-25 38 250)"/>
                    <ellipse cx="62" cy="228" rx="14" ry="38" transform="rotate(20 62 228)"/>
                    <ellipse cx="445" cy="265" rx="16" ry="42" transform="rotate(25 445 265)"/>
                    <ellipse cx="420" cy="245" rx="14" ry="36" transform="rotate(-20 420 245)"/>
                </g>

                <!-- laptop ao fundo -->
                <rect x="100" y="90" width="300" height="215" rx="10" fill="#c3d3e6"/>
                <rect x="55" y="300" width="390" height="14" rx="7" fill="#b4c7de"/>

                <!-- janela -->
                <rect x="42" y="62" width="350" height="230" rx="4" fill="#ffffff" stroke="#0f4c8a" stroke-width="4"/>
                <rect x="42" y="62" width="350" height="44" fill="#0f4c8a"/>
                <circle cx="66" cy="84" r="6" fill="#fff"/>
                <circle cx="90" cy="84" r="6" fill="#fff"/>
                <circle cx="114" cy="84" r="6" fill="#fff"/>

                <!-- cadeado -->
                <path d="M200 150 v-14 a17 17 0 0 1 34 0 v14" fill="none" stroke="#0f4c8a" stroke-width="7" stroke-linecap="round"/>
                <rect x="188" y="148" width="58" height="48" rx="6" fill="#9fb6d2"/>
                <circle cx="217" cy="168" r="5" fill="#0f4c8a"/>
                <rect x="215" y="170" width="4" height="12" fill="#0f4c8a"/>

                <!-- texto -->
                <text x="217" y="248" text-anchor="middle" font-family="Manrope, sans-serif" font-weight="800" font-size="30" letter-spacing="5" fill="#f26b1d">BEM-VINDO</text>

                <!-- pessoa deitada -->
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

    <script>
        const toggleBtn = document.getElementById('togglePasswordBtn');
        const passwordInput = document.getElementById('passwordInput');
        const eyeIcon = document.getElementById('eyeIcon');

        toggleBtn.addEventListener('click', function () {
            // Alterna o tipo do input entre password e text
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);

            // Alterna o ícone do olho (bi-eye / bi-eye-slash)
            if (type === 'text') {
                eyeIcon.classList.remove('bi-eye-slash');
                eyeIcon.classList.add('bi-eye');
                toggleBtn.setAttribute('title', 'Ocultar senha');
            } else {
                eyeIcon.classList.remove('bi-eye');
                eyeIcon.classList.add('bi-eye-slash');
                toggleBtn.setAttribute('title', 'Mostrar senha');
            }
        });
    </script>
</body>
</html>