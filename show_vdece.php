<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

$config = include __DIR__ . '/config.php';
$dsn    = "mysql:host={$config['db_host']};"
        . "dbname={$config['db_name']};"
        . "charset={$config['db_charset']}";
$pdo    = new PDO($dsn, $config['db_user'], $config['db_pass']);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Pagination des VdECE (5 par page)
$perPage    = 5;
$page       = isset($_GET['page']) && is_numeric($_GET['page'])
              ? (int)$_GET['page']
              : 1;
$stmtTotal  = $pdo->query('SELECT COUNT(*) FROM vdece');
$total      = (int)$stmtTotal->fetchColumn();
$totalPages = (int)ceil($total / $perPage);

$offset     = ($page - 1) * $perPage;
$stmt       = $pdo->prepare(
    'SELECT * FROM vdece ORDER BY created_at DESC
     LIMIT :limit OFFSET :offset'
);
$stmt->bindValue(':limit',  $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset,  PDO::PARAM_INT);
$stmt->execute();
$vdeList = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Vie d’ECE</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"
        rel="stylesheet">
</head>
<body class="bg-light">

  <!-- Navbar -->
  <nav class="navbar navbar-light bg-white shadow-sm mb-4">
    <div class="container">
      <a class="navbar-brand d-flex align-items-center" href="index.php">
        <img src="logo.png" alt="Logo" width="40" height="40" class="me-2">
        <span class="fs-4 fw-bold text-primary">Vie d’ECE</span>
      </a>
      <div>
        <a href="add_vdece.php" class="btn btn-primary">Publier</a>
      </div>
    </div>
  </nav>

  <div class="container" style="max-width:680px;">
    <?php foreach ($vdeList as $vde): ?>
      <?php
        // Compte des réponses
        $cStmt = $pdo->prepare('SELECT COUNT(*) FROM comments WHERE vde_id = ?');
        $cStmt->execute([$vde['id']]);
        $count = (int)$cStmt->fetchColumn();
      ?>
      <div class="bg-white rounded-3 shadow-sm p-4 mb-4" id="vde_<?= $vde['id'] ?>">
        <!-- Auteur + date -->
        <div class="d-flex justify-content-between align-items-center">
          <h5 class="mb-0"><?= htmlspecialchars($vde['pseudo']) ?></h5>
          <small class="text-muted"><?= $vde['created_at'] ?></small>
        </div>
        <!-- Démarcation -->
        <hr>
        <!-- Contenu -->
        <p class="mt-3"><?= nl2br(htmlspecialchars($vde['content'])) ?></p>
        <!-- Like / Dislike -->
        <div class="d-flex align-items-center mb-3">
          <button class="btn btn-outline-success me-3 likeBtn"
                  data-id="<?= $vde['id'] ?>">
            👍 <span class="likeCount">0</span>
          </button>
          <button class="btn btn-outline-danger dislikeBtn"
                  data-id="<?= $vde['id'] ?>">
            👎 <span class="dislikeCount">0</span>
          </button>
          <a href="show_vdece.php?id=<?= $vde['id'] ?>"
             class="ms-auto text-decoration-none fw-medium text-primary">
            <?= $count ?> <?= $count>1 ? 'réponses' : 'réponse' ?>
          </a>
        </div>
      </div>
    <?php endforeach; ?>

    <!-- Pagination Bootstrap -->
    <?php if ($totalPages > 1): ?>
      <nav>
        <ul class="pagination justify-content-center">
          <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <li class="page-item <?= $i === $page ? 'active' : '' ?>">
              <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
            </li>
          <?php endfor; ?>
        </ul>
      </nav>
    <?php endif; ?>
  </div>

  <!-- Bootstrap JS + logique Like/Dislike -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
  document.addEventListener('DOMContentLoaded', function() {
    // Pour chaque bouton Like/Dislike, charger et gérer le compteur
    document.querySelectorAll('.likeBtn').forEach(btn => {
      const id = btn.dataset.id;
      const span = btn.querySelector('.likeCount');
      let count = parseInt(localStorage.getItem('vde_'+id+'_likes') || '0');
      span.textContent = count;
      btn.addEventListener('click', () => {
        count++;
        localStorage.setItem('vde_'+id+'_likes', count);
        span.textContent = count;
      });
    });
    document.querySelectorAll('.dislikeBtn').forEach(btn => {
      const id = btn.dataset.id;
      const span = btn.querySelector('.dislikeCount');
      let count = parseInt(localStorage.getItem('vde_'+id+'_dislikes') || '0');
      span.textContent = count;
      btn.addEventListener('click', () => {
        count++;
        localStorage.setItem('vde_'+id+'_dislikes', count);
        span.textContent = count;
      });
    });
  });
  </script>
</body>
</html>
