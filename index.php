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

// Récupérer la page courante de VdECE
$offset     = ($page - 1) * $perPage;
$stmt       = $pdo->prepare(
    'SELECT * FROM vdece ORDER BY created_at DESC
     LIMIT :limit OFFSET :offset'
);
$stmt->bindValue(':limit',  $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset,  PDO::PARAM_INT);
$stmt->execute();
$vdeList = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pseudoDefault = $_SESSION['pseudo'] ?? '';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Vie d’ECE</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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

    <?php foreach ($vdeList as $vde):
      // Récupérer les réponses de cette VdE
      $cStmt    = $pdo->prepare(
        'SELECT * FROM comments WHERE vde_id = ? ORDER BY created_at ASC'
      );
      $cStmt->execute([$vde['id']]);
      $comments = $cStmt->fetchAll(PDO::FETCH_ASSOC);
      $count    = count($comments);
    ?>
      <div class="bg-white rounded-3 shadow-sm p-4 mb-4" id="vde_<?= $vde['id'] ?>">
        <!-- Auteur et date -->
        <div class="d-flex justify-content-between align-items-center">
          <h5 class="mb-0"><?= htmlspecialchars($vde['pseudo']) ?></h5>
          <small class="text-muted"><?= $vde['created_at'] ?></small>
        </div>
        <hr>
        <p class="mt-3"><?= nl2br(htmlspecialchars($vde['content'])) ?></p>

        <!-- Like / Dislike + toggle réponses -->
        <div class="d-flex align-items-center mb-3">
          <button type="button" class="btn btn-outline-success me-3 likeBtn" data-id="<?= $vde['id'] ?>">
            👍 <span class="likeCount">0</span>
          </button>
          <button type="button" class="btn btn-outline-danger me-3 dislikeBtn" data-id="<?= $vde['id'] ?>">
            👎 <span class="dislikeCount">0</span>
          </button>
          <button type="button"
                  class="btn btn-link ms-auto toggleResponses"
                  data-id="<?= $vde['id'] ?>"
                  data-count="<?= $count ?>">
            Voir <?= $count ?> <?= $count>1 ? 'réponses' : 'réponse' ?>
          </button>
        </div>

        <!-- Liste des réponses (cachée par défaut) -->
        <div id="responses_<?= $vde['id'] ?>" class="responses-list ps-3 border-start mb-3 d-none">
          <?php if ($count === 0): ?>
            <p class="text-muted fst-italic">Aucune réponse.</p>
          <?php else: ?>
            <?php foreach ($comments as $c): ?>
              <div class="mb-2">
                <small class="text-secondary">
                  <?= htmlspecialchars($c['pseudo']) ?> — <?= $c['created_at'] ?>
                </small>
                <p class="m-1"><?= nl2br(htmlspecialchars($c['comment'])) ?></p>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>

        <!-- Formulaire AJAX de réponse inline -->
        <div id="form_<?= $vde['id'] ?>" class="comment-form bg-light rounded p-3">
          <form class="ajaxCommentForm" data-id="<?= $vde['id'] ?>" method="post" action="add_comment.php">
            <input type="hidden" name="vde_id" value="<?= $vde['id'] ?>">
            <div id="error_<?= $vde['id'] ?>" class="alert alert-danger d-none"></div>
            <div class="mb-2">
              <input type="text"
                     name="pseudo"
                     class="form-control"
                     placeholder="Votre pseudo"
                     value="<?= htmlspecialchars($pseudoDefault) ?>"
                     maxlength="50"
                     required>
            </div>
            <div class="mb-2">
              <textarea name="comment"
                        class="form-control"
                        rows="2"
                        placeholder="Votre réponse"
                        required></textarea>
            </div>
            <button type="submit" class="btn btn-sm btn-primary">Envoyer</button>
          </form>
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

  <!-- Bootstrap JS + script personnalisé AJAX + toggle + like/dislike -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
  document.addEventListener('DOMContentLoaded', function() {
    // Like / Dislike
    document.querySelectorAll('.likeBtn').forEach(btn => {
      const id   = btn.dataset.id;
      const span = btn.querySelector('.likeCount');
      let count  = parseInt(localStorage.getItem('vde_' + id + '_likes') || '0');
      span.textContent = count;
      btn.addEventListener('click', e => {
        e.preventDefault();
        count++;
        localStorage.setItem('vde_' + id + '_likes', count);
        span.textContent = count;
      });
    });
    document.querySelectorAll('.dislikeBtn').forEach(btn => {
      const id   = btn.dataset.id;
      const span = btn.querySelector('.dislikeCount');
      let count  = parseInt(localStorage.getItem('vde_' + id + '_dislikes') || '0');
      span.textContent = count;
      btn.addEventListener('click', e => {
        e.preventDefault();
        count++;
        localStorage.setItem('vde_' + id + '_dislikes', count);
        span.textContent = count;
      });
    });

    // Toggle affichage des réponses
    document.querySelectorAll('.toggleResponses').forEach(btn => {
      btn.addEventListener('click', e => {
        e.preventDefault();
        const id    = btn.dataset.id;
        const cnt   = btn.dataset.count;
        const list  = document.getElementById('responses_' + id);
        list.classList.toggle('d-none');
        btn.textContent = list.classList.contains('d-none')
          ? 'Voir ' + cnt + (cnt>1 ? ' réponses' : ' réponse')
          : 'Cacher les réponses';
      });
    });

    // AJAX pour les formulaires inline
    document.querySelectorAll('.ajaxCommentForm').forEach(form => {
      form.addEventListener('submit', function(e) {
        e.preventDefault();
        const id      = this.dataset.id;
        const data    = new FormData(this);
        const errorEl = document.getElementById('error_' + id);
        fetch(this.action, {
          method: 'POST',
          headers: { 'X-Requested-With': 'XMLHttpRequest' },
          body: data
        })
        .then(res => res.json())
        .then(json => {
          if (json.error) {
            errorEl.textContent = json.error;
            errorEl.classList.remove('d-none');
          } else if (json.html) {
            // insérer la nouvelle réponse juste après
            const list = document.getElementById('responses_' + id);
            // si caché, on affiche
            if (list.classList.contains('d-none')) {
              list.classList.remove('d-none');
            }
            list.insertAdjacentHTML('beforeend', json.html);
            this.reset();
          }
        })
        .catch(() => {
          errorEl.textContent = 'Erreur lors de l’envoi.';
          errorEl.classList.remove('d-none');
        });
      });
    });
  });
  </script>
</body>
</html>
