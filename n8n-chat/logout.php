<?php
require_once 'config/auth.php';

// Limpa todas as variáveis de sessão
$_SESSION = array();

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();

    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

session_destroy();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Encerrando sessão | IDETE</title>

    <link rel="stylesheet" href="assets/logout.css">
</head>

<body>

    <main class="logout-card">

        <div class="logout-logo">

            <div class="logout-ring"></div>

            <img src="Imagens/Adaptis_logo2-removebg.png" alt="Logo">

        </div>

        <h1 class="title">
            Encerrando sessão
        </h1>

        <p class="description">
            Estamos desconectando você com segurança.
        </p>

        <div class="progress-container">
            <div class="progress"></div>
        </div>

        <div class="brand">
            ADAPTIS
        </div>

    </main>

    <script>
        setTimeout(() => {
            window.location.href = "login.php";
        }, 1800);
    </script>

</body>
</html>