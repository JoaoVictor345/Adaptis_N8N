<?php
require_once 'config/auth.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (empty($email)) {
        $error = "Por favor, digite o seu e-mail.";
    } else {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        
        if ($stmt->fetch()) {
            // Gera um token seguro único
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

            // Remove tokens anteriores pendentes para este e-mail
            $pdo->prepare("DELETE FROM password_resets WHERE email = ?")->execute([$email]);

            // Insere o novo token
            $stmt = $pdo->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)");
            $stmt->execute([$email, $token, $expires]);

            // Link de redefinição
            $resetLink = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/reset-password.php?token=" . $token;

            // Nota: Em produção, enviarias isto por e-mail (PHPMailer / mail()). 
            // Para testes locais no XAMPP, exibimos o link diretamente na tela:
            $success = "Link de recuperação gerado com sucesso! (Modo Local): <br><a href='$resetLink' style='word-break: break-all; color: var(--azul);'><b>Clique aqui para redefinir sua senha</b></a>";
        } else {
            // Por segurança, não revelamos se o e-mail existe ou não na base de dados
            $success = "Se o e-mail estiver cadastrado, você receberá as instruções de recuperação.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Recuperar Senha - ADAPTIS</title>
    <link rel="shortcut icon" href="Imagens/Adaptis_logo2.ico" type="image/x-icon">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/login.css">
</head>
<body>
    <div class="blob blob-azul-topo"></div>
    <div class="blob blob-laranja-direita"></div>
    <div class="login-wrapper" style="grid-template-columns: 1fr; max-width: 500px;">
        <div class="login-card">
            <div class="login-header">
                <img src="Imagens/Adaptis_logo2-removebg.png" alt="IDETE">
                <h2>Recuperar Senha</h2>
                <p>Informe seu e-mail para receber o link</p>
            </div>

            <?php if ($error): ?>
                <div class="alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert-success" style="background: #def7ec; color: #03543f; padding: 1rem; border-radius: 8px; font-size: 0.9rem; margin-bottom: 1.25rem; border: 1px solid #bcf0da;">
                    <?= $success ?>
                </div>
            <?php else: ?>
                <form action="forgot-password.php" method="POST">
                    <div class="form-group">
                        <label>E-mail cadastrado</label>
                        <input type="email" name="email" required placeholder="seu.email@idete.com">
                    </div>
                    <button type="submit" class="btn-submit">Enviar Link</button>
                </form>
            <?php endif; ?>

            <div class="login-footer">
                Lembrou a senha? <a href="login.php">Fazer login</a>
            </div>
        </div>
    </div>
</body>
</html>