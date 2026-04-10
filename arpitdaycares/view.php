<?php
// ─── CHANGE THIS PASSWORD ───
$PASSWORD = 'acpc2026nav';
// ─────────────────────────────

session_start();

$RESPONSES_DIR = __DIR__ . '/responses';

if (isset($_POST['password'])) {
    if ($_POST['password'] === $PASSWORD) {
        $_SESSION['authed'] = true;
    } else {
        $loginError = true;
    }
}

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: view.php');
    exit;
}

if (isset($_GET['download']) && !empty($_SESSION['authed'])) {
    $file = basename($_GET['download']);
    $path = $RESPONSES_DIR . '/' . $file;
    if (file_exists($path) && pathinfo($file, PATHINFO_EXTENSION) === 'json') {
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="' . $file . '"');
        readfile($path);
        exit;
    }
}

?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Survey Responses — ACPC</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
:root { --accent: #1a6b4a; --bg: #f1f3f6; --card: #fff; --border: #e2e5ea; --text: #1a1a2e; --muted: #6b7280; }
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: 'DM Sans', system-ui, sans-serif; background: var(--bg); color: var(--text); line-height: 1.5; }
.wrap { max-width: 800px; margin: 40px auto; padding: 0 20px; }
h1 { font-size: 24px; font-weight: 700; margin-bottom: 8px; }
.sub { color: var(--muted); font-size: 14px; margin-bottom: 24px; }
.card { background: var(--card); border-radius: 16px; padding: 32px; box-shadow: 0 1px 4px rgba(0,0,0,0.06); margin-bottom: 20px; }
input[type="password"], input[type="text"] {
  font-family: inherit; font-size: 14px; padding: 10px 14px; width: 100%; max-width: 300px;
  border: 1.5px solid var(--border); border-radius: 10px; outline: none; margin-bottom: 12px; display: block;
}
input:focus { border-color: var(--accent); }
.btn { font-family: inherit; font-size: 14px; font-weight: 600; padding: 10px 24px; border-radius: 10px; cursor: pointer; border: none; }
.btn-primary { background: var(--accent); color: #fff; }
.btn-primary:hover { background: #15573d; }
.btn-sm { font-size: 12px; padding: 6px 14px; border-radius: 8px; }
.btn-outline { background: #fff; border: 1.5px solid var(--border); color: var(--text); }
.err { color: #dc2626; font-size: 13px; margin-bottom: 12px; }
.resp-item { display: flex; justify-content: space-between; align-items: center; padding: 14px 0; border-bottom: 1px solid var(--border); }
.resp-item:last-child { border-bottom: none; }
.resp-name { font-weight: 600; font-size: 14px; }
.resp-date { color: var(--muted); font-size: 12px; }
.resp-actions { display: flex; gap: 8px; }
pre { background: #f8f9fb; border: 1px solid var(--border); border-radius: 10px; padding: 16px; font-size: 12px; overflow-x: auto; max-height: 600px; overflow-y: auto; white-space: pre-wrap; word-break: break-word; margin-top: 16px; }
.top-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
.logout { color: var(--muted); font-size: 13px; text-decoration: none; }
.logout:hover { color: var(--text); }
.empty { text-align: center; padding: 40px 20px; color: var(--muted); font-size: 15px; }
</style>
</head>
<body>
<div class="wrap">

<?php if (empty($_SESSION['authed'])): ?>

<div class="card" style="max-width:400px;margin:80px auto;">
  <h1>Survey Responses</h1>
  <p class="sub">Enter the password to view Arpit's submissions.</p>
  <?php if (!empty($loginError)): ?><p class="err">Wrong password.</p><?php endif; ?>
  <form method="POST">
    <input type="password" name="password" placeholder="Password" autofocus>
    <button type="submit" class="btn btn-primary">View Responses</button>
  </form>
</div>

<?php else: ?>

<div class="top-bar">
  <div>
    <h1>Arpit's Survey Responses</h1>
    <p class="sub">Submitted responses are saved as JSON files on your server.</p>
  </div>
  <a href="?logout=1" class="logout">Logout</a>
</div>

<?php
  $files = glob($RESPONSES_DIR . '/*.json');
  if (empty($files)):
?>
<div class="card empty">No responses yet. Waiting for Arpit to submit the survey.</div>
<?php else:
  rsort($files);
  foreach ($files as $f):
    $name = basename($f);
    $size = round(filesize($f) / 1024, 1);
    $mtime = date('M j, Y \a\t g:i A', filemtime($f));
?>

<div class="card">
  <div class="resp-item" style="border:none;padding:0;">
    <div>
      <div class="resp-name"><?= htmlspecialchars($name) ?></div>
      <div class="resp-date"><?= $mtime ?> &middot; <?= $size ?> KB</div>
    </div>
    <div class="resp-actions">
      <a href="?download=<?= urlencode($name) ?>" class="btn btn-sm btn-primary">Download</a>
    </div>
  </div>

<?php
    if (isset($_GET['view']) && $_GET['view'] === $name):
      $json = file_get_contents($f);
?>
  <pre><?= htmlspecialchars($json) ?></pre>
  <div style="margin-top:12px;"><a href="view.php" class="btn btn-sm btn-outline">Hide</a></div>
<?php else: ?>
  <div style="margin-top:8px;"><a href="?view=<?= urlencode($name) ?>" class="btn btn-sm btn-outline">View JSON</a></div>
<?php endif; ?>

</div>

<?php endforeach; endif; ?>

<?php endif; ?>

</div>
</body>
</html>
