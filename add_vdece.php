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

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pseudo  = trim($_POST['pseudo'] ?? '');
    $content = trim($_POST['content'] ?? '');

    if ($pseudo === '' || $content === '') {
        $error = 'Tous les champs sont obligatoires.';
    } elseif (mb_strlen($pseudo) > 50) {
        $error = 'Le pseudo ne doit pas dépasser 50 caractères.';
    } else {
        $stmt = $pdo->prepare(
          'INSERT INTO vdece (pseudo, content) VALUES (?, ?)'
        );
        $stmt->execute([$pseudo, $content]);
        $_SESSION['pseudo'] = $pseudo;
        header('Location: index.php');
        exit;
    }
} else {
    $pseudo = $_SESSION['pseudo'] ?? '';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Ajouter VdECE</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

  <nav class="navbar navbar-dark bg-dark mb-4">
    <div class="container">
      <a class="navbar-brand" href="index.php">Vie d’ECE</a>
    </div>
  </nav>

  <div class="container">
    <div class="card mx-auto" style="max-width: 500px;">
      <div class="card-body">
        <h5 class="card-title">Ajouter une VdECE</h5>
        <?php if ($error): ?>
          <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="post">
          <div class="mb-3">
            <label class="form-label">Pseudo</label>
            <input type="text" name="pseudo" class="form-control"
                   value="<?= htmlspecialchars($pseudo) ?>"
                   maxlength="50" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Votre anecdote</label>
            <textarea name="content" class="form-control" rows="4" required></textarea>
          </div>
          <button class="btn btn-primary">Valider</button>
          <a href="index.php" class="btn btn-secondary">Annuler</a>
        </form>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
