<?php
/* ============================================================
   CRIAR QUIZ — salva no MySQL (banco quiz_db)
   ============================================================ */

// ---------- CONFIGURAÇÃO DO BANCO (ajuste aqui) ----------
 $host    = 'localhost';
 $dbname  = 'quiz_db';
 $usuario = 'root';
 $senha   = '';

 $erros   = [];
 $sucesso = false;
 $quizId  = null;

function v(?string $s): string {
    return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8');
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
    die('<h1 style="color:#FFD60A;font-family:sans-serif">Erro ao conectar no banco: '
        . htmlspecialchars($e->getMessage()) . '</h1>');
}

// ---------- PROCESSA O FORMULÁRIO ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $titulo    = trim((string)($_POST['titulo'] ?? ''));
    $descricao = trim((string)($_POST['descricao'] ?? ''));
    $perguntas = $_POST['perguntas'] ?? [];
    if (!is_array($perguntas)) $perguntas = [];
    $perguntas = array_values($perguntas);

    // ---- Validação ----
    if ($titulo === '')      $erros[] = 'Informe o título do quiz.';
    if (count($perguntas) === 0) $erros[] = 'Adicione pelo menos uma pergunta.';

    foreach ($perguntas as $i => $p) {
        $p = is_array($p) ? $p : [];
        $n   = $i + 1;
        if (trim((string)($p['enunciado'] ?? '')) === '') {
            $erros[] = "Pergunta $n: informe o enunciado.";
        }
        $alts = is_array($p['alternativas'] ?? null) ? $p['alternativas'] : [];
        foreach (array_map('trim', $alts) as $j => $texto) {
            if ($texto === '') $erros[] = "Pergunta $n: preencha a alternativa " . ($j + 1) . '.';
        }
        if (($p['correta'] ?? '') === '') {
            $erros[] = "Pergunta $n: marque qual alternativa é a correta.";
        }
    }

    // ---- Salva no banco (transação) ----
    if (empty($erros)) {
        try {
            $pdo->beginTransaction();

            $pdo->prepare('INSERT INTO quizzes (titulo, descricao) VALUES (:t, :d)')
                ->execute([':t' => $titulo, ':d' => $descricao !== '' ? $descricao : null]);
            $quizId = $pdo->lastInsertId();

            $sqlPerg = $pdo->prepare('INSERT INTO perguntas (quiz_id, enunciado) VALUES (:q, :e)');
            $sqlAlt  = $pdo->prepare('INSERT INTO alternativas (pergunta_id, texto, correta) VALUES (:p, :t, :c)');

            foreach ($perguntas as $p) {
                $p = is_array($p) ? $p : [];
                $sqlPerg->execute([':q' => $quizId, ':e' => trim((string)($p['enunciado'] ?? ''))]);
                $idPergunta = $pdo->lastInsertId();

                $alts = is_array($p['alternativas'] ?? null) ? $p['alternativas'] : [];
                foreach ($alts as $j => $texto) {
                    $sqlAlt->execute([
                        ':p' => $idPergunta,
                        ':t' => trim((string)$texto),
                        ':c' => ((string)($p['correta'] ?? '') === (string)$j) ? 1 : 0,
                    ]);
                }
            }

            $pdo->commit();
            $sucesso = true;
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $erros[] = 'Erro ao salvar o quiz: ' . $e->getMessage();
        }
    }
}

// ---------- DADOS PARA REEXIBIR O FORMULÁRIO (se der erro, nada é perdido) ----------
 $post     = ($_SERVER['REQUEST_METHOD'] === 'POST');
 $pergForm = $post ? array_values(is_array($_POST['perguntas'] ?? null) ? $_POST['perguntas'] : []) : [];

 $numBlocos = 3;
if ($post && count($pergForm) > 3) $numBlocos = count($pergForm);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Criar quiz — QUIZ!</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700;800;900&display=swap" rel="stylesheet">
<style>
  :root {
    --preto: #0b0b0d; --preto-2: #131316; --preto-3: #1b1b1f;
    --amarelo: #FFD60A; --amarelo-2: #FFC300; --amarelo-3: #f5a623;
    --cinza-texto: #b9b9c0; --branco: #fff;
  }
  * { margin: 0; padding: 0; box-sizing: border-box; }

  body {
    font-family: 'Poppins', sans-serif;
    background: var(--preto);
    color: var(--branco);
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    position: relative;
  }
  body::before, body::after {
    content: ""; position: fixed; width: 500px; height: 500px;
    border-radius: 50%; filter: blur(160px); opacity: .12;
    z-index: 0; pointer-events: none;
  }
  body::before { background: var(--amarelo);   top: -150px; right: -100px; }
  body::after  { background: var(--amarelo-3); bottom: -200px; left: -150px; }

  .shape { position: fixed; z-index: 0; opacity: .4; pointer-events: none;
           animation: flutuar 7s ease-in-out infinite; }
  .shape.circulo  { width: 34px; height: 34px; border-radius: 50%; background: var(--amarelo); top: 30%; left: 3%; }
  .shape.quadrado { width: 30px; height: 30px; border: 5px solid var(--amarelo); border-radius: 8px;
                    transform: rotate(20deg); top: 65%; right: 4%; animation-delay: 2s; }
  @keyframes flutuar { 0%,100% { translate: 0 0; } 50% { translate: 0 -25px; } }

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
  header nav a:hover, header nav a.ativo { color: var(--amarelo); }

  .btn {
    display: inline-block; padding: 12px 30px; border-radius: 50px;
    font-family: inherit; font-weight: 800; font-size: .95rem;
    text-decoration: none; cursor: pointer; transition: transform .2s, box-shadow .2s, background .2s, color .2s;
    border: none;
  }
  .btn-amarelo  { background: var(--amarelo); color: var(--preto); }
  .btn-amarelo:hover  { transform: translateY(-3px) scale(1.03); box-shadow: 0 10px 30px rgba(255,214,10,.35); }
  .btn-contorno { border: 2px solid var(--amarelo); color: var(--amarelo); background: transparent; }
  .btn-contorno:hover { background: var(--amarelo); color: var(--preto); transform: translateY(-3px); }

  .pagina { position: relative; z-index: 1; width: 100%; max-width: 840px; margin: 0 auto; padding: 55px 6% 90px; }

  .titulo-pagina h1 { font-size: clamp(2rem, 4.5vw, 3rem); font-weight: 900; letter-spacing: -1px; }
  .titulo-pagina em { font-style: normal; color: var(--amarelo); }
  .titulo-pagina p  { color: var(--cinza-texto); margin: 10px 0 36px; }

  /* ------ Blocos ------ */
  .bloco-pergunta {
    background: var(--preto-2); border: 1px solid #26262b; border-radius: 20px;
    padding: 24px; margin-bottom: 20px; transition: border-color .25s;
  }
  .bloco-pergunta:hover { border-color: #3a3a42; }
  .bloco-topo { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; }
  .num-pergunta { color: var(--amarelo); font-weight: 800; letter-spacing: 1px; }
  .btn-remover {
    background: transparent; border: 1px solid #2a2a30; color: var(--cinza-texto);
    border-radius: 10px; padding: 6px 14px; cursor: pointer;
    font-family: inherit; font-weight: 700; font-size: .8rem; transition: all .2s;
  }
  .btn-remover:hover { border-color: #ff5252; color: #ff8a8a; }

  input[type=text] {
    width: 100%; background: var(--preto-3); border: 1px solid #2a2a30;
    border-radius: 12px; padding: 13px 16px; color: var(--branco);
    font-family: inherit; font-size: 1rem; transition: border-color .2s, box-shadow .2s;
  }
  input[type=text]::placeholder { color: #6a6a72; }
  input[type=text]:focus { outline: none; border-color: var(--amarelo); box-shadow: 0 0 0 3px rgba(255,214,10,.15); }

  .campo { display: flex; flex-direction: column; gap: 8px; margin-bottom: 16px;
           font-size: .78rem; font-weight: 700; color: var(--cinza-texto);
           text-transform: uppercase; letter-spacing: 1px; }
  .bloco-pergunta > input[type=text] { margin-bottom: 18px; }

  .alternativas { display: flex; flex-direction: column; gap: 10px; }
  .alt-row { display: flex; align-items: center; gap: 12px; }
  .alt-row input[type=text] { flex: 1; }
  .badge { flex: none; width: 42px; height: 42px; display: grid; place-items: center;
           border-radius: 12px; font-weight: 900; color: var(--preto); }
  .b1 { background: var(--amarelo); } .b2 { background: var(--amarelo-2); }
  .b3 { background: var(--amarelo-3); } .b4 { background: var(--branco); }
  .correta { display: flex; align-items: center; gap: 7px; cursor: pointer;
             font-size: .75rem; font-weight: 700; color: var(--cinza-texto); white-space: nowrap; }
  .correta input { width: 18px; height: 18px; accent-color: var(--amarelo); cursor: pointer; }
  .correta:hover { color: var(--amarelo); }

  .acoes { display: flex; gap: 14px; flex-wrap: wrap; margin-top: 26px; }
  .dica { margin-top: 22px; color: var(--cinza-texto); font-size: .9rem; }
  .dica strong { color: var(--amarelo); }

  /* ------ Alertas ------ */
  .alerta-erro {
    background: rgba(255,82,82,.07); border: 1px solid rgba(255,82,82,.45);
    color: #ff9b9b; border-radius: 16px; padding: 20px 24px; margin-bottom: 26px; font-size: .95rem;
  }
  .alerta-erro ul { margin: 10px 0 0 20px; }
  .alerta-erro li { margin-bottom: 4px; }

  .sucesso { text-align: center; background: var(--preto-2);
             border: 1px solid rgba(255,214,10,.5); border-radius: 24px; padding: 60px 30px; }
  .sucesso .check { width: 84px; height: 84px; margin: 0 auto 24px; display: grid; place-items: center;
                    background: var(--amarelo); color: var(--preto); border-radius: 50%;
                    font-size: 2.6rem; font-weight: 900; }
  .sucesso h2 { font-weight: 900; margin-bottom: 8px; }
  .sucesso p  { color: var(--cinza-texto); margin-bottom: 30px; }
  .sucesso p strong { color: var(--amarelo); }
  .sucesso-botoes { display: flex; gap: 14px; justify-content: center; flex-wrap: wrap; }

  footer {
    position: relative; z-index: 1; border-top: 1px solid #222226;
    padding: 24px 6%; text-align: center; background: var(--preto-2);
  }
  footer p { color: var(--cinza-texto); font-size: .85rem; }

  @media (max-width: 700px) {
    header nav { display: none; }
    .alt-row { flex-wrap: wrap; }
    .alt-row input[type=text] { flex: 1 1 100%; order: 2; }
    .badge { order: 1; } .correta { order: 3; margin-left: 54px; }
  }
</style>
</head>
<body>

  <div class="shape circulo"></div>
  <div class="shape quadrado"></div>

  <header>
    <a class="logo" href="index.html">QUIZ<span>!</span></a>
    <nav>
      <a href="index.html">Início</a>
      <a href="criar_quiz.php" class="ativo">Criar quiz</a>
    </nav>
    <a class="btn btn-amarelo" href="index.html">Jogar</a>
  </header>

  <main class="pagina">
    <div class="titulo-pagina">
      <h1>Criar seu <em>quiz</em></h1>
      <p>Monte as perguntas, marque as respostas certas e publique.</p>
    </div>

    <?php if ($sucesso): ?>

      <div class="sucesso">
        <div class="check">✔</div>
        <h2>Quiz publicado com sucesso!</h2>
        <p>ID do quiz: <strong>#<?= (int)$quizId ?></strong></p>
        <div class="sucesso-botoes">
          <a class="btn btn-amarelo" href="criar_quiz.php">Criar outro quiz</a>
          <a class="btn btn-contorno" href="index.html">Voltar ao início</a>
        </div>
      </div>

    <?php else: ?>

      <?php if (!empty($erros)): ?>
        <div class="alerta-erro">
          <strong>Ops! Corrija os itens abaixo:</strong>
          <ul>
            <?php foreach ($erros as $e): ?><li><?= v($e) ?></li><?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <form method="post" action="criar_quiz.php">

        <!-- Dados gerais do quiz -->
        <div class="bloco-pergunta">
          <div class="bloco-topo"><span class="num-pergunta">DADOS DO QUIZ</span></div>
          <label class="campo">Título do quiz *
            <input type="text" name="titulo" maxlength="120" required
                   placeholder="Ex.: Conhecimentos gerais — nível fácil"
                   value="<?= v($_POST['titulo'] ?? '') ?>">
          </label>
          <label class="campo">Descrição (opcional)
            <input type="text" name="descricao" maxlength="200"
                   placeholder="Ex.: 10 perguntas para testar seus conhecimentos"
                   value="<?= v($_POST['descricao'] ?? '') ?>">
          </label>
        </div>

        <div id="perguntas">
          <?php for ($i = 0; $i < $numBlocos; $i++):
              $p = $pergForm[$i] ?? null;
              $p      = is_array($p) ? $p : [];
              $enun   = (string)($p['enunciado'] ?? '');
              $alts   = is_array($p['alternativas'] ?? null) ? $p['alternativas'] : ['', '', '', ''];
              $correta = $p['correta'] ?? null;
              $icones = ['▲', '◆', '●', '■'];
          ?>
          <div class="bloco-pergunta">
            <div class="bloco-topo">
              <span class="num-pergunta">PERGUNTA <?= $i + 1 ?></span>
              <button type="button" class="btn-remover" onclick="removerBloco(this)">✕ Remover</button>
            </div>
            <input type="text" name="perguntas[<?= $i ?>][enunciado]" maxlength="200"
                   placeholder="Digite a pergunta..." value="<?= v($enun) ?>" required>

            <div class="alternativas">
              <?php foreach ($alts as $j => $alt): ?>
              <div class="alt-row">
                <span class="badge b<?= $j + 1 ?>"><?= $icones[$j] ?? '▲' ?></span>
                <input type="text" name="perguntas[<?= $i ?>][alternativas][]" maxlength="120"
                       placeholder="Alternativa <?= $j + 1 ?>" value="<?= v($alt) ?>" required>
                <label class="correta">
                  <input type="radio" name="perguntas[<?= $i ?>][correta]" value="<?= $j ?>"
                         <?= ((string)$correta === (string)$j) ? 'checked' : '' ?> <?= $j === 0 ? 'required' : '' ?>>
                  Correta
                </label>
              </div>
              <?php endforeach; ?>
            </div>
          </div>
          <?php endfor; ?>
        </div>

        <div class="acoes">
          <button type="button" class="btn btn-contorno" onclick="addBloco()">+ Adicionar pergunta</button>
          <button type="submit" class="btn btn-amarelo">Publicar quiz ✔</button>
        </div>
        <p class="dica">💡 Em cada pergunta, marque o bolinho <strong>Correta</strong> na alternativa certa — é o que o sistema usa para pontuar os jogadores.</p>
      </form>

    <?php endif; ?>
  </main>

  <footer>
    <p>© 2025 QUIZ! — Todos os direitos reservados.</p>
  </footer>

<script>
  let proximo = <?= $numBlocos ?>;

  const MODELO = `
    <div class="bloco-pergunta">
      <div class="bloco-topo">
        <span class="num-pergunta">PERGUNTA</span>
        <button type="button" class="btn-remover" onclick="removerBloco(this)">✕ Remover</button>
      </div>
      <input type="text" name="perguntas[__I__][enunciado]" maxlength="200" placeholder="Digite a pergunta..." required>
      <div class="alternativas">
        <div class="alt-row">
          <span class="badge b1">▲</span>
          <input type="text" name="perguntas[__I__][alternativas][]" maxlength="120" placeholder="Alternativa 1" required>
          <label class="correta"><input type="radio" name="perguntas[__I__][correta]" value="0" required> Correta</label>
        </div>
        <div class="alt-row">
          <span class="badge b2">◆</span>
          <input type="text" name="perguntas[__I__][alternativas][]" maxlength="120" placeholder="Alternativa 2" required>
          <label class="correta"><input type="radio" name="perguntas[__I__][correta]" value="1"> Correta</label>
        </div>
        <div class="alt-row">
          <span class="badge b3">●</span>
          <input type="text" name="perguntas[__I__][alternativas][]" maxlength="120" placeholder="Alternativa 3" required>
          <label class="correta"><input type="radio" name="perguntas[__I__][correta]" value="2"> Correta</label>
        </div>
        <div class="alt-row">
          <span class="badge b4">■</span>
          <input type="text" name="perguntas[__I__][alternativas][]" maxlength="120" placeholder="Alternativa 4" required>
          <label class="correta"><input type="radio" name="perguntas[__I__][correta]" value="3"> Correta</label>
        </div>
      </div>
    </div>`;

  function addBloco() {
    document.getElementById('perguntas')
            .insertAdjacentHTML('beforeend', MODELO.replaceAll('__I__', proximo));
    proximo++;
    renumerar();
  }

  function removerBloco(btn) {
    if (document.querySelectorAll('#perguntas .bloco-pergunta').length <= 1) return;
    btn.closest('.bloco-pergunta').remove();
    renumerar();
  }

  function renumerar() {
    document.querySelectorAll('#perguntas .bloco-pergunta').forEach((b, i) => {
      b.querySelector('.num-pergunta').textContent = 'PERGUNTA ' + (i + 1);
    });
  }
</script>
</body>
</html>
