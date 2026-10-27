<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>403 – Acesso negado</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/Adaptis_N8N/n8n-chat/assets/css/403.css">
</head>
<body>
<main>
  <section>
    <span class="code">Erro 403</span>
    <h1>Acesso negado</h1>
    <div class="bar" aria-hidden="true"><i></i><i></i><i></i></div>
    <p class="lead">Você não tem permissão para abrir esta página. Seu login funcionou, mas esta área é restrita a outros perfis de acesso.</p>
    <ul>
      <li>Confirme se entrou com a conta correta.</li>
      <li>Peça a liberação ao administrador do sistema.</li>
      <li>Se o acesso já foi liberado, saia e entre novamente.</li>
    </ul>
    <div class="actions">
      <button class="btn primary" onclick="history.length>1?history.back():location.href='/Adaptis_N8N/n8n-chat/pages/chat.php'">Voltar à página anterior</button>
      <a class="btn ghost" href="/Adaptis_N8N/n8n-chat/pages/chat.php">Ir para o início</a>
    </div>
  </section>

  <div class="art" aria-hidden="true">
  <div class="stage">
    <svg class="orbit o1" viewBox="0 0 400 400" xmlns="http://www.w3.org/2000/svg">
      <circle cx="200" cy="200" r="165" fill="none" stroke="var(--line)" stroke-width="2" opacity=".6"/>
      <circle cx="200" cy="200" r="165" pathLength="100" fill="none" stroke="var(--orange)" stroke-width="12" stroke-linecap="round" stroke-dasharray="34 66" stroke-dashoffset="-0"/>
    </svg>
    <svg class="orbit o2" viewBox="0 0 400 400" xmlns="http://www.w3.org/2000/svg">
      <circle cx="200" cy="200" r="140" fill="none" stroke="var(--line)" stroke-width="2" opacity=".6"/>
      <circle cx="200" cy="200" r="140" pathLength="100" fill="none" stroke="var(--teal)" stroke-width="12" stroke-linecap="round" stroke-dasharray="30 70" stroke-dashoffset="-50"/>
    </svg>
    <svg class="orbit o3" viewBox="0 0 400 400" xmlns="http://www.w3.org/2000/svg">
      <circle cx="200" cy="200" r="115" fill="none" stroke="var(--line)" stroke-width="2" opacity=".6"/>
      <circle cx="200" cy="200" r="115" pathLength="100" fill="none" stroke="var(--green)" stroke-width="12" stroke-linecap="round" stroke-dasharray="26 74" stroke-dashoffset="-20"/>
    </svg>
    <svg viewBox="0 0 400 400" xmlns="http://www.w3.org/2000/svg" role="img">
      <polygon points="200,118 268,159 268,241 200,282 132,241 132,159" fill="var(--navy)"/>
      <g stroke="var(--cube-line)" stroke-width="7" stroke-linecap="round" fill="none">
        <polyline points="132,159 200,200 268,159"/>
        <line x1="200" y1="200" x2="200" y2="282"/>
      </g>
      <path class="shackle" d="M182 206 v-20 a18 18 0 0 1 36 0 v20" fill="none" stroke="var(--cube-line)" stroke-width="9" stroke-linecap="round"/>
      <rect x="168" y="204" width="64" height="52" rx="10" fill="var(--orange)"/>
      <circle cx="200" cy="226" r="7" fill="var(--navy)"/>
      <rect x="197" y="228" width="6" height="14" rx="3" fill="var(--navy)"/>
    </svg>
  </div>
  </div>
</main>
</body>
</html>