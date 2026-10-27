<?php
require_once 'config/auth.php';

$error = '';
$success = '';
$token = $_GET['token'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['token'] ?? '';
    $password = trim($_POST['password'] ?? '');
    $confirm = trim($_POST['confirm_password'] ?? '');

    if (empty($password) || empty($confirm)) {
        $error = "Preencha todos os campos.";
    } elseif ($password !== $confirm) {
        $error = "As senhas não coincidem.";
    } elseif (strlen($password) < 6) {
        $error = "A senha deve ter pelo menos 6 caracteres.";
    } else {
        $pdo = getDBConnection();
        
        // Verifica se o token é válido e não expirou
        $stmt = $pdo->prepare("SELECT email FROM password_resets WHERE token = ? AND expires_at > NOW()");
        $stmt->execute([$token]);
        $reset = $stmt->fetch();

        if ($reset) {
            $email = $reset['email'];
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

            // Atualiza a senha do usuário
            $pdo->prepare("UPDATE users SET password = ? WHERE email = ?")->execute([$hashedPassword, $email]);

            // Apaga o token usado
            $pdo->prepare("DELETE FROM password_resets WHERE token = ?")->execute([$token]);

            $success = "Senha alterada com sucesso!";
        } else {
            $error = "Link inválido ou expirado.";
        }
    }
} else {
    // Verifica se o token GET é válido antes de mostrar o form
    if (empty($token)) {
        header("Location: login.php");
        exit;
    }
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT email FROM password_resets WHERE token = ? AND expires_at > NOW()");
    $stmt->execute([$token]);
    if (!$stmt->fetch()) {
        $error = "Este link de recuperação é inválido ou já expirou.";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Nova Senha - ADAPTIS</title>
    <link rel="shortcut icon" href="Imagens/Adaptis_logo2.ico" type="image/x-icon">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/login.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.2/font/bootstrap-icons.min.css">
    <style>
        .password-container { position: relative; display: flex; align-items: center; }
        .password-container input { width: 100%; padding-right: 2.5rem; }
        .toggle-password { position: absolute; right: 0; background: none; border: none; cursor: pointer; color: var(--texto-suave); font-size: 1.2rem; padding: 0.2rem; }
    </style>
</head>
<body>
    <div class="blob blob-azul-topo"></div>
    <div class="login-wrapper" style="grid-template-columns: 1fr; max-width: 500px;">
        <div class="login-card">
            <div class="login-header">
                <img src="Imagens/Adaptis_logo2-removebg.png" alt="IDETE">
                <h2>Nova Senha</h2>
                <p>Insira a sua nova senha de acesso</p>
            </div>

            <?php if ($error && !$success): ?>
                <div class="alert-error"><?= htmlspecialchars($error) ?></div>
                <?php if (strpos($error, 'inválido') !== false): ?>
                    <div style="text-align: center; margin-top: 1rem;"><a href="forgot-password.php" style="color: var(--azul); font-weight: 700;">Solicitar novo link</a></div>
                <?php endif; ?>
            <?php endif; ?>

            <?php if ($success): ?>
                <div style="background: #def7ec; color: #03543f; padding: 1rem; border-radius: 8px; font-size: 0.9rem; text-align: center; margin-bottom: 1.25rem;">
                    <?= htmlspecialchars($success) ?><br><br>
                    <a href="login.php" class="btn-link" style="background: var(--azul); color: #fff; padding: 0.5rem 1rem; text-decoration: none; border-radius: 4px; font-weight: 700;">Ir para o Login</a>
                </div>
            <?php elseif (empty($error) || strpos($error, 'expirado') === false): ?>
                <form action="reset-password.php" method="POST">
                    <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                    
                    <div class="form-group">
                        <label>Nova Senha</label>
                        <div class="password-container">
                            <input type="password" name="password" id="pass1" required placeholder="Mínimo 6 caracteres">
                            <button type="button" class="toggle-password" onclick="togglePass('pass1', this)"><i class="bi bi-eye"></i></button>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Confirmar Nova Senha</label>
                        <div class="password-container">
                            <input type="password" name="confirm_password" id="pass2" required placeholder="Repita a senha">
                            <button type="button" class="toggle-password" onclick="togglePass('pass2', this)"><i class="bi bi-eye"></i></button>
                        </div>
                    </div>

                    <button type="submit" class="btn-submit">Redefinir Senha</button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function togglePass(id, btn) {
            const input = document.getElementById(id);
            const icon = btn.querySelector('i');
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.replace('bi-eye', 'bi-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.replace('bi-eye-slash', 'bi-eye');
            }
        }
    </script>
</body>
</html>