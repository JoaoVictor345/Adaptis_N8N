<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>404 – Página não encontrada</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/404.css">
</head>
<body>
<main>
  <section>
    <span class="code">Erro 404</span>
    <h1>Página não encontrada</h1>
    <div class="bar" aria-hidden="true"><i></i><i></i><i></i></div>
    <p class="lead">Procuramos por todo lado, mas o endereço que você tentou abrir não existe ou foi movido para outro lugar.</p>
    <ul>
      <li>Confira se o endereço foi digitado corretamente.</li>
      <li>Volte ao início e navegue pelo menu do sistema.</li>
      <li>Se o link veio de um favorito antigo, atualize-o.</li>
    </ul>
    <div class="actions">
      <button class="btn primary" onclick="history.length>1?history.back():location.href='/'">Voltar à página anterior</button>
      <a class="btn ghost" href="/">Ir para o início</a>
    </div>
  </section>

  <div class="art" aria-hidden="true">
    <svg viewBox="0 0 400 400" xmlns="http://www.w3.org/2000/svg" role="img">
      <g fill="none" stroke-width="8" stroke-linecap="round">
        <path class="trail" d="M30 350 C 80 300, 110 360, 150 318 S 200 330, 230 300" stroke="var(--teal)"/>
        <path class="trail" d="M370 60 C 320 90, 300 40, 262 74" stroke="var(--green)" style="animation-direction:reverse"/>
      </g>
      <g fill="none" stroke-width="22" stroke-linejoin="miter" stroke-linecap="butt">
        <polyline points="40,70 40,40 70,40" stroke="var(--orange)"/>
        <polyline points="360,330 360,360 330,360" stroke="var(--orange)"/>
      </g>
      <polygon points="200,92 268,133 268,215 200,256 132,215 132,133" fill="var(--navy)"/>
      <g stroke="var(--cube-line)" stroke-width="7" stroke-linecap="round" fill="none" opacity=".55">
        <polyline points="132,133 200,174 268,133"/>
        <line x1="200" y1="174" x2="200" y2="256"/>
      </g>
      <g class="lens">
        <line x1="296" y1="288" x2="346" y2="338" stroke="var(--teal)" stroke-width="20" stroke-linecap="round"/>
        <circle cx="250" cy="242" r="58" fill="var(--bg)" fill-opacity=".9" stroke="var(--orange)" stroke-width="14"/>
        <text class="q" x="250" y="266" text-anchor="middle" font-family="Sora,system-ui,sans-serif" font-weight="800" font-size="72" fill="var(--green)">?</text>
      </g>
    </svg>
  </div>
</main>
</body>
</html>