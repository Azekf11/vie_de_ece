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

$vde_id  = (int)($_POST['vde_id'] ?? 0);
$pseudo  = trim($_POST['pseudo']  ?? '');
$comment = trim($_POST['comment'] ?? '');

if ($vde_id <= 0 || $pseudo === '' || $comment === '' || mb_strlen($pseudo) > 50) {
    http_response_code(400);
    echo json_encode(['error' => 'Données invalides']);
    exit;
}

$stmt = $pdo->prepare(
  'INSERT INTO comments (vde_id, pseudo, comment)
   VALUES (?, ?, ?)'
);
$stmt->execute([$vde_id, $pseudo, $comment]);
$_SESSION['pseudo'] = $pseudo;

if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])
    && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    $id = $pdo->lastInsertId();
    $cStmt = $pdo->prepare('SELECT * FROM comments WHERE id = ?');
    $cStmt->execute([$id]);
    $c = $cStmt->fetch(PDO::FETCH_ASSOC);
    $html = "<div class='card mb-2'><div class='card-body p-2'>"
          . "<small class='text-muted'>" . htmlspecialchars($c['pseudo'])
          . " — " . $c['created_at'] . "</small>"
          . "<p class='mb-0'>" . nl2br(htmlspecialchars($c['comment']))
          . "</p></div></div>";
    echo json_encode(['html' => $html]);
    exit;
}

header('Location: show_vdece.php?id=' . $vde_id);
exit;
