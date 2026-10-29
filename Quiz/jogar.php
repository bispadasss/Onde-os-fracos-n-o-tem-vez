<?php
/* ============================================================
   JOGAR — roda um quiz salvo no banco (estilo Kahoot)
   Uso: jogar.php?id=1
   ============================================================ */

// ---------- CONFIGURAÇÃO DO BANCO (mesma das outras páginas) ----------
 $host    = 'localhost';
 $dbname  = 'quiz_db';
 $usuario = 'root';
 $senha   = '';

function v(?string $s): string {
    return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8');
}

// Página de erro bonitinha (mesmo tema)
function paginaErro(string $titulo, string $msg) {
    echo '<!DOCTYPE html><html lang="pt-BR"><head><meta charset="UTF-8">
    <title>QUIZ!</title><link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;700;900&display=swap" rel="stylesheet">
    <style>body{background:#0b0b0d;color:#fff;font-family:Poppins,sans-serif;display:grid;place-items:center;min-height:100vh;margin:0}
    .cx{background:#131316;border:1px solid rgba(255,214,10,.5);border-radius:24px;padding:50px 40px;text-align:center;max-width:440px}
    h1{color:#FFD60A;font-size:1.6rem}p{color:#b9b9c0;margin:14px 0 26px}
    a{background:#FFD60A;color:#0b0b0d;text-decoration:none;font-weight:800;padding:12px 30px;border-radius:50px}</style></head>
    <body><div class="cx"><h1>⚠️ ' . htmlspecialchars($titulo) . '</h1><p>' . htmlspecialchars($msg) . '</p>
    <a href="catalogo.php">← Voltar ao catálogo</a></div></body></html>';
    exit;
}

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $usuario,
        $senha,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    paginaErro('Erro de conexão', 'Não foi possível conectar ao banco de dados.');
}

// ---------- CARREGA O QUIZ ----------
 $id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    paginaErro('Quiz não informado', 'Volte ao catálogo e escolha um quiz para jogar.');
}

 $stmt = $pdo->prepare('SELECT id, titulo, descricao FROM quizzes WHERE id = :id');
 $stmt->execute([':id' => $id]);
 $quiz = $stmt->fetch();

if (!$quiz) {
    paginaErro('Quiz não encontrado', 'Este quiz não existe ou foi removido.');
}

// ---------- CARREGA PERGUNTAS + ALTERNATIVAS ----------
 $st = $pdo->prepare('SELECT id, enunciado FROM perguntas WHERE quiz_id = :id ORDER BY id');
 $st->execute([':id' => $id]);
 $pergs = $st->fetchAll();

 $altsMap = [];
if ($pergs) {
    $marcas = implode(',', array_fill(0, count($pergs), '?'));
    $st = $pdo->prepare("SELECT pergunta_id, texto, correta
                         FROM alternativas
                         WHERE pergunta_id IN ($marcas)
                         ORDER BY id");
    $st->execute(array_column($pergs, 'id'));
    foreach ($st->fetchAll() as $a) {
        $altsMap[$a['pergunta_id']][] = [
            'texto'   => $a['texto'],
            'correta' => (bool)$a['correta'],
        ];
    }
}

// Monta o array que o JavaScript vai usar (alternativas embaralhadas)
 $dados = [];
foreach ($pergs as $p) {
    $ops = $altsMap[$p['id']] ?? [];
    shuffle($ops); // evita que a correta fique sempre na mesma posição
    $dados[] = [
        'pergunta'     => $p['enunciado'],
        'alternativas' => $ops,
    ];
}

if (count($dados) === 0) {
    paginaErro('Quiz vazio', 'Este quiz ainda não tem perguntas cadastradas.');
}

 $jsonFlags = JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= v($quiz['titulo']) ?> — QUIZ!</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700;800;900&display=swap" rel="stylesheet">
<style>
  :root {
    --preto: #0b0b0d; --preto-2: #131316; --preto-3: #1b1b1f;
    --amarelo: #FFD60A; --amarelo-2: #FFC300; --amarelo-3: #f5a623;
    --cinza-texto: #b9b9c0; --branco: #fff;
    --verde: #2ecc71; --vermelho: #ff4d4d;
  }
  * { margin: 0; padding: 0; box-sizing: border-box; }

  body {
    font-family: 'Poppins', sans-serif;
    background: var(--preto);
    color: var(--branco);
    min-height: 100vh;
    position: relative;
  }
  body::before, body::after {
    content: ""; position: fixed; width: 500px; height: 500px;
    border-radius: 50%; filter: blur(160px); opacity: .12;
    z-index: 0; pointer-events: none;
  }
  body::before { background: var(--amarelo);   top: -150px; right: -100px; }
  body::after  { background: var(--amarelo-3); bottom: -200px; left: -150px; }

  header {
    position: sticky; top: 0; z-index: 10;
    display: flex; align-items: center; justify-content: space-between;
    padding: 18px 6%;
    background: rgba(11,11,13,.75); backdrop-filter: blur(12px);
    border-bottom: 1px solid #222226;
  }
  .logo { font-size: 1.9rem; font-weight: 900; color: var(--branco); text-decoration: none; }
  .logo span { color: var(--amarelo); }
  header nav { display: flex; gap: 30px; }
  header nav a { color: var(--cinza-texto); text-decoration: none; font-weight: 600; font-size: .95rem; transition: color .25s; }
  header nav a:hover { color: var(--amarelo); }

  .palco {
    position: relative; z-index: 1;
    max-width: 900px; margin: 0 auto;
    padding: 50px 6% 80px;
  }

  /* ------ Tela inicial ------ */
  #tela-inicio { text-align: center; }
  .tag {
    display: inline-block; background: rgba(255,214,10,.1);
    border: 1px solid rgba(255,214,10,.4); color: var(--amarelo);
    padding: 8px 18px; border-radius: 50px; font-size: .8rem;
    font-weight: 700; letter-spacing: 2px; text-transform: uppercase; margin-bottom: 22px;
  }
  #tela-inicio h1 { font-size: clamp(2rem, 5vw, 3.4rem); font-weight: 900; letter-spacing: -1px; margin-bottom: 14px; }
  #tela-inicio > p { color: var(--cinza-texto); max-width: 520px; margin: 0 auto 26px; line-height: 1.6; }
  .infos {
    display: inline-flex; gap: 10px; flex-wrap: wrap; justify-content: center; margin-bottom: 36px;
  }
  .infos span {
    background: var(--preto-2); border: 1px solid #26262b; border-radius: 50px;
    padding: 10px 20px; font-size: .85rem; font-weight: 700; color: var(--cinza-texto);
  }
  .infos strong { color: var(--amarelo); }

  /* ------ Tela de jogo ------ */
  .oculto { display: none !important; }

  .topo { display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; gap: 14px; }
  #progresso { font-weight: 800; font-size: .95rem; letter-spacing: 1px; }
  #pontos-atual { color: var(--amarelo); font-weight: 900; font-size: 1.05rem; }
  #tempo-num { background: var(--amarelo); color: var(--preto); font-weight: 900;
               padding: 8px 18px; border-radius: 12px; min-width: 64px; text-align: center; }
  #tempo-num.urgente { background: var(--vermelho); color: #fff; }

  .barra { height: 10px; background: var(--preto-3); border-radius: 10px; overflow: hidden; margin-bottom: 30px; }
  #barra-fill { height: 100%; width: 100%; background: var(--amarelo); border-radius: 10px; }
  #barra-fill.urgente { background: var(--vermelho); }

  #enunciado { font-size: clamp(1.4rem, 3.5vw, 2rem); font-weight: 800; line-height: 1.35;
               text-align: center; margin-bottom: 34px; min-height: 70px; }

  .opcoes { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
  .opcao {
    display: flex; align-items: center; gap: 14px;
    padding: 20px; border-radius: 18px; border: none;
    font-family: inherit; font-weight: 800; font-size: 1.05rem; color: var(--preto);
    text-align: left; cursor: pointer;
    transition: transform .15s, box-shadow .2s, opacity .3s;
  }
  .opcao:hover { transform: scale(1.03); box-shadow: 0 12px 28px rgba(255,214,10,.25); }
  .opcao .icone {
    flex: none; width: 38px; height: 38px; display: grid; place-items: center;
    background: rgba(0,0,0,.18); border-radius: 11px; font-size: 1.1rem;
  }
  .cor-1 { background: var(--amarelo); }
  .cor-2 { background: var(--amarelo-2); }
  .cor-3 { background: var(--amarelo-3); }
  .cor-4 { background: var(--branco); }

  /* Estados depois de responder */
  .opcoes.travada .opcao { cursor: default; opacity: .3; transform: none; box-shadow: none; }
  .opcoes.travada .opcao.revelada-correta { opacity: 1; outline: 4px solid var(--verde); box-shadow: 0 0 30px rgba(46,204,112,.45); }
  .opcoes.travada .opcao.escolhida-errada { opacity: .85; outline: 4px solid var(--vermelho); }

  /* ------ Flash central (+pontos / tempo esgotado) ------ */
  #flash { position: fixed; inset: 0; display: grid; place-items: center; pointer-events: none; z-index: 50; }
  #flash span { font-size: clamp(2rem, 6vw, 3.4rem); font-weight: 900; color: var(--amarelo);
                text-shadow: 0 8px 40px rgba(0,0,0,.9); opacity: 0; }
  #flash.animar span { animation: pop 1.7s ease forwards; }
  @keyframes pop {
    0%   { opacity: 0; transform: scale(.6); }
    20%  { opacity: 1; transform: scale(1.12); }
    75%  { opacity: 1; transform: scale(1); }
    100% { opacity: 0; transform: translateY(-50px); }
  }

  /* ------ Tela final ------ */
  #tela-fim { text-align: center; }
  #tela-fim h2 { font-size: clamp(1.8rem, 4vw, 2.6rem); font-weight: 900; margin-bottom: 26px; }
  #pontuacao-final {
    font-size: clamp(3.5rem, 10vw, 6rem); font-weight: 900; color: var(--amarelo);
    line-height: 1; text-shadow: 0 0 60px rgba(255,214,10,.35); margin-bottom: 10px;
  }
  #pontuacao-final small { font-size: .35em; color: var(--cinza-texto); }
  #placar { color: var(--cinza-texto); font-weight: 700; margin-bottom: 8px; }
  #mensagem { font-size: 1.2rem; font-weight: 800; margin-bottom: 38px; }
  .fim-botoes { display: flex; gap: 14px; justify-content: center; flex-wrap: wrap; }

  .btn {
    display: inline-block; padding: 14px 34px; border-radius: 50px; border: none;
    font-family: inherit; font-weight: 800; font-size: 1rem; text-decoration: none;
    cursor: pointer; transition: transform .2s, box-shadow .2s, background .2s, color .2s;
  }
  .btn-amarelo  { background: var(--amarelo); color: var(--preto); }
  .btn-amarelo:hover  { transform: translateY(-3px) scale(1.03); box-shadow: 0 10px 30px rgba(255,214,10,.35); }
  .btn-contorno { border: 2px solid var(--amarelo); color: var(--amarelo); background: transparent; }
  .btn-contorno:hover { background: var(--amarelo); color: var(--preto); transform: translateY(-3px); }

  footer {
    position: relative; z-index: 1; border-top: 1px solid #222226;
    padding: 24px 6%; text-align: center; background: var(--preto-2);
  }
  footer p { color: var(--cinza-texto); font-size: .85rem; }

  @media (max-width: 700px) {
    header nav { display: none; }
    .opcoes { grid-template-columns: 1fr; }
  }
</style>
</head>
<body>

  <header>
    <a class="logo" href="index.html">QUIZ<span>!</span></a>
    <nav>
      <a href="index.html">Início</a>
      <a href="catalogo.php">Quizzes</a>
      <a href="criar_quiz.php">Criar quiz</a>
    </nav>
    <a class="btn btn-amarelo" href="catalogo.php">Catálogo</a>
  </header>

  <main class="palco">

    <!-- ====== TELA INICIAL ====== -->
    <section id="tela-inicio">
      <span class="tag">Modo jogo</span>
      <h1><?= v($quiz['titulo']) ?></h1>
      <p><?= v($quiz['descricao'] ?? 'Responda o mais rápido possível: quanto menos tempo você levar, mais pontos ganha!') ?></p>
      <div class="infos">
        <span><strong id="info-qtd"></strong> perguntas</span>
        <span><strong>20s</strong> por pergunta</span>
        <span>até <strong id="info-max"></strong> pts</span>
      </div>
      <br>
      <button id="btn-iniciar" class="btn btn-amarelo">▶ Iniciar</button>
    </section>

    <!-- ====== TELA DO JOGO ====== -->
    <section id="tela-jogo" class="oculto">
      <div class="topo">
        <span id="progresso"></span>
        <span id="pontos-atual">0 pts</span>
        <span id="tempo-num">20s</span>
      </div>
      <div class="barra"><div id="barra-fill"></div></div>
      <h2 id="enunciado"></h2>
      <div id="alternativas" class="opcoes"></div>
    </section>

    <!-- ====== TELA FINAL ====== -->
    <section id="tela-fim" class="oculto">
      <h2>Fim de jogo! 🎉</h2>
      <div id="pontuacao-final"></div>
      <p id="placar"></p>
      <p id="mensagem"></p>
      <div class="fim-botoes">
        <button class="btn btn-amarelo" onclick="location.reload()">🔄 Jogar de novo</button>
        <a class="btn btn-contorno" href="catalogo.php">📚 Ver catálogo</a>
      </div>
    </section>

  </main>

  <!-- Flash de feedback (+pontos / tempo esgotado) -->
  <div id="flash"><span id="flash-txt"></span></div>

  <footer>
    <p>© 2025 QUIZ! — Todos os direitos reservados.</p>
  </footer>

<script>
  // ===== Dados vindos do PHP =====
  const QUIZ      = <?= json_encode($quiz, $jsonFlags) ?>;
  const PERGUNTAS = <?= json_encode($dados, $jsonFlags) ?>;

  const TEMPO   = 20;          // segundos por pergunta (mude aqui se quiser)
  const ICONES  = ['▲', '◆', '●', '■'];

  let atual    = 0;
  let pontos   = 0;
  let acertos  = 0;
  let restante = TEMPO;
  let timer    = null;
  let travado  = false;

  const $ = id => document.getElementById(id);

  // ===== Tela inicial =====
  $('info-qtd').textContent = PERGUNTAS.length;
  $('info-max').textContent = (PERGUNTAS.length * 1000).toLocaleString('pt-BR');

  $('btn-iniciar').addEventListener('click', () => {
    $('tela-inicio').classList.add('oculto');
    $('tela-jogo').classList.remove('oculto');
    mostrarPergunta();
  });

  // ===== Loop do jogo =====
  function mostrarPergunta() {
    travado = false;
    const q = PERGUNTAS[atual];

    $('progresso').textContent = `PERGUNTA ${atual + 1}/${PERGUNTAS.length}`;
    $('enunciado').textContent = q.pergunta;

    const caixa = $('alternativas');
    caixa.innerHTML = '';
    caixa.classList.remove('travada');

    q.alternativas.forEach((a, i) => {
      const btn = document.createElement('button');
      btn.className = 'opcao cor-' + ((i % 4) + 1);

      const icone = document.createElement('span');
      icone.className = 'icone';
      icone.textContent = ICONES[i % 4];

      const txt = document.createElement('span');
      txt.textContent = a.texto;

      btn.append(icone, txt);
      btn.addEventListener('click', () => responder(a, btn));
      caixa.appendChild(btn);
    });

    restante = TEMPO;
    atualizaTempo();
    clearInterval(timer);
    timer = setInterval(tick, 1000);
  }

  function tick() {
    restante--;
    atualizaTempo();
    if (restante <= 0) tempoEsgotado();
  }

  function atualizaTempo() {
    const t = Math.max(restante, 0);
    $('tempo-num').textContent = t + 's';
    $('barra-fill').style.width = (t / TEMPO * 100) + '%';

    const urgente = t <= 5;
    $('tempo-num').classList.toggle('urgente', urgente);
    $('barra-fill').classList.toggle('urgente', urgente);
  }

  // ===== Responder =====
  function responder(alt, btn) {
    if (travado) return;
    travado = true;
    clearInterval(timer);

    const caixa   = $('alternativas');
    const correta = caixa.querySelector('.correta-do-jogo') || revelarCorreta(caixa);

    if (alt.correta) {
      const pts = 500 + Math.round(500 * restante / TEMPO); // pontos caem conforme o tempo passa
      pontos  += pts;
      acertos++;
      btn.classList.add('revelada-correta');
      $('pontos-atual').textContent = pontos.toLocaleString('pt-BR') + ' pts';
      flash('+' + pts.toLocaleString('pt-BR'), 'var(--amarelo)');
    } else {
      btn.classList.add('escolhida-errada');
      flash('Errou! 😬', 'var(--vermelho)');
    }

    setTimeout(proxima, 1800);
  }

  function tempoEsgotado() {
    if (travado) return;
    travado = true;
    clearInterval(timer);
    revelarCorreta($('alternativas'));
    flash('Tempo esgotado! ⏰', 'var(--vermelho)');
    setTimeout(proxima, 1800);
  }

  function revelarCorreta(caixa) {
    caixa.classList.add('travada');
    const botoes = [...caixa.children];
    const correta = PERGUNTAS[atual].alternativas[botoes.indexOf(
      botoes.find(b => b.style.display !== 'none')
    )];
    // marca visualmente a alternativa correta
    botoes.forEach((b, i) => {
      if (PERGUNTAS[atual].alternativas[i] && PERGUNTAS[atual].alternativas[i].correta) {
        b.classList.add('revelada-correta');
      }
    });
    return correta;
  }

  function proxima() {
    atual++;
    if (atual < PERGUNTAS.length) {
      mostrarPergunta();
    } else {
      fimDeJogo();
    }
  }

  // ===== Flash animado =====
  function flash(texto, cor) {
    const f = $('flash');
    $('flash-txt').textContent = texto;
    $('flash-txt').style.color = cor;
    f.classList.remove('animar');
    void f.offsetWidth;           // força reinício da animação
    f.classList.add('animar');
  }

  // ===== Tela final =====
  function fimDeJogo() {
    $('tela-jogo').classList.add('oculto');
    $('tela-fim').classList.remove('oculto');

    $('pontuacao-final').innerHTML =
      pontos.toLocaleString('pt-BR') + ' <small>pts</small>';
    $('placar').textContent =
      `Você acertou ${acertos} de ${PERGUNTAS.length} perguntas`;

    const taxa = acertos / PERGUNTAS.length;
    $('mensagem').textContent =
      taxa === 1        ? '🏆 PERFEITO! Acertou tudo!' :
      taxa >= 0.7       ? '🎉 Muito bem! Quase impecável!' :
      taxa >= 0.4       ? '🙂 Nada mal! Dá pra melhorar!' :
                          '💪 Bora treinar e tentar de novo!';

    atual = 0; pontos = 0; acertos = 0;
  }
</script>
</body>
</html>
