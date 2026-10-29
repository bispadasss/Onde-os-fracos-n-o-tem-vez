<?php
/* ============================================================
   CATÁLOGO — lista os quizzes salvos no banco quiz_db
   ============================================================ */

// ---------- CONFIGURAÇÃO DO BANCO (mesma do criar_quiz.php) ----------
 $host    = 'localhost';
 $dbname  = 'quiz_db';
 $usuario = 'root';
 $senha   = '';

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

// ---------- BUSCA (opcional, via ?busca=...) ----------
 $busca  = trim((string)($_GET['busca'] ?? ''));
 $where  = '';
 $params = [];
if ($busca !== '') {
    $where = 'WHERE q.titulo LIKE :b OR q.descricao LIKE :b';
    $params[':b'] = "%{$busca}%";
}

// ---------- BUSCA OS QUIZZES + CONTAGEM DE PERGUNTAS ----------
 $sql = "SELECT q.id, q.titulo, q.descricao, q.criado_em, COUNT(p.id) AS total_perguntas
        FROM quizzes q
        LEFT JOIN perguntas p ON p.quiz_id = q.id
        $where
        GROUP BY q.id, q.titulo, q.descricao, q.criado_em
        ORDER BY q.criado_em DESC";

 $stmt = $pdo->prepare($sql);
 $stmt->execute($params);
 $quizzes = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Catálogo de quizzes — QUIZ!</title>
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

  .pagina { position: relative; z-index: 1; width: 100%; max-width: 1200px; margin: 0 auto; padding: 55px 6% 90px; }

  .titulo-pagina { display: flex; justify-content: space-between; align-items: flex-end; flex-wrap: wrap; gap: 20px; }
  .titulo-pagina h1 { font-size: clamp(2rem, 4.5vw, 3rem); font-weight: 900; letter-spacing: -1px; }
  .titulo-pagina em { font-style: normal; color: var(--amarelo); }
  .titulo-pagina p  { color: var(--cinza-texto); margin-top: 10px; }
  .titulo-pagina p strong { color: var(--amarelo); }

  /* ------ Busca ------ */
  .busca { display: flex; gap: 12px; margin: 36px 0 30px; }
  .busca input {
    flex: 1; background: var(--preto-2); border: 1px solid #2a2a30;
    border-radius: 50px; padding: 14px 22px; color: var(--branco);
    font-family: inherit; font-size: 1rem; transition: border-color .2s, box-shadow .2s;
  }
  .busca input::placeholder { color: #6a6a72; }
  .busca input:focus { outline: none; border-color: var(--amarelo); box-shadow: 0 0 0 3px rgba(255,214,10,.15); }

  /* ------ Grade de cards ------ */
  .grade {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(290px, 1fr));
    gap: 22px;
  }
  .quiz-card {
    display: flex; flex-direction: column;
    background: var(--preto-2); border: 1px solid #26262b; border-radius: 22px;
    padding: 26px; text-decoration: none; color: var(--branco);
    transition: transform .25s, border-color .25s, box-shadow .25s;
    animation: subir .5s ease both;
  }
  @keyframes subir { from { opacity: 0; transform: translateY(25px); } to { opacity: 1; transform: translateY(0); } }
  .quiz-card:hover {
    transform: translateY(-8px);
    border-color: var(--amarelo);
    box-shadow: 0 20px 50px rgba(255,214,10,.12);
  }
  .card-topo { display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px; }
  .badge-q {
    background: rgba(255,214,10,.12); border: 1px solid rgba(255,214,10,.4);
    color: var(--amarelo); font-size: .75rem; font-weight: 700;
    padding: 6px 14px; border-radius: 50px;
  }
  .data { color: #6a6a72; font-size: .78rem; font-weight: 600; }
  .quiz-card h3 { font-size: 1.2rem; font-weight: 800; line-height: 1.35; margin-bottom: 10px; }
  .quiz-card p  { color: var(--cinza-texto); font-size: .9rem; line-height: 1.6; flex: 1; }
  .jogar {
    margin-top: 22px; color: var(--amarelo); font-weight: 800; font-size: .95rem;
    transition: gap .2s; display: inline-flex; align-items: center; gap: 8px;
  }
  .quiz-card:hover .jogar { gap: 14px; }

  /* ------ Estado vazio ------ */
  .vazio {
    text-align: center; background: var(--preto-2);
    border: 1px dashed #3a3a42; border-radius: 24px; padding: 70px 30px;
  }
  .vazio .icone { font-size: 3rem; margin-bottom: 18px; }
  .vazio h3 { font-weight: 900; margin-bottom: 8px; }
  .vazio p { color: var(--cinza-texto); margin-bottom: 28px; }

  footer {
    position: relative; z-index: 1; border-top: 1px solid #222226;
    padding: 24px 6%; text-align: center; background: var(--preto-2);
    margin-top: auto;
  }
  footer p { color: var(--cinza-texto); font-size: .85rem; }

  @media (max-width: 700px) {
    header nav { display: none; }
    .titulo-pagina { flex-direction: column; align-items: flex-start; }
  }
</style>
</head>
<body>

  <header>
    <a class="logo" href="index.html">QUIZ<span>!</span></a>
    <nav>
      <a href="index.html">Início</a>
      <a href="catalogo.php" class="ativo">Quizzes</a>
      <a href="criar_quiz.php">Criar quiz</a>
    </nav>
    <a class="btn btn-amarelo" href="criar_quiz.php">+ Criar</a>
  </header>

  <main class="pagina">
    <div class="titulo-pagina">
      <div>
        <h1>Catálogo de <em>quizzes</em></h1>
        <p><strong><?= count($quizzes) ?></strong> quiz(ns) <?= $busca !== '' ? 'encontrado(s) para "' . v($busca) . '"' : 'disponíveis' ?></p>
      </div>
      <a class="btn btn-contorno" href="criar_quiz.php">+ Criar meu quiz</a>
    </div>

    <!-- Busca -->
    <form class="busca" method="get" action="catalogo.php">
      <input type="text" name="busca" placeholder="🔍  Buscar quiz pelo nome..." value="<?= v($busca) ?>">
      <button class="btn btn-amarelo" type="submit">Buscar</button>
    </form>

    <?php if (count($quizzes) === 0): ?>

      <!-- Nada encontrado -->
      <div class="vazio">
        <div class="icone">📭</div>
        <h3><?= $busca !== '' ? 'Nenhum quiz encontrado para essa busca' : 'Nenhum quiz criado ainda' ?></h3>
        <p><?= $busca !== '' ? 'Tente outra palavra ou crie um quiz novo.' : 'Seja o primeiro a criar um quiz e apareça aqui!' ?></p>
        <a class="btn btn-amarelo" href="criar_quiz.php">Criar o primeiro quiz</a>
      </div>

    <?php else: ?>

      <!-- Lista de quizzes -->
      <div class="grade">
        <?php foreach ($quizzes as $i => $q): ?>
        <a class="quiz-card" href="jogar.php?id=<?= (int)$q['id'] ?>" style="animation-delay: <?= $i * 0.06 ?>s">
          <div class="card-topo">
            <span class="badge-q"><?= (int)$q['total_perguntas'] ?> pergunta(s)</span>
            <span class="data"><?= date('d/m/Y', strtotime($q['criado_em'])) ?></span>
          </div>
          <h3><?= v($q['titulo']) ?></h3>
          <p><?= v($q['descricao'] ?? 'Sem descrição — clique e boa sorte! 😄') ?></p>
          <span class="jogar">▶ Jogar</span>
        </a>
        <?php endforeach; ?>
      </div>

    <?php endif; ?>
  </main>

  <footer>
    <p>© 2025 QUIZ! — Todos os direitos reservados.</p>
  </footer>

</body>
</html>
