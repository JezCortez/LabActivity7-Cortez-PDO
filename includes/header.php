<?php
/** Expects: $pageTitle (string). Optional: $bodyClass (string). */
$pageTitle = $pageTitle ?? 'Blog Site';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> · Blog Site</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="<?= e($bodyClass ?? '') ?>">
<header class="site-header">
    <div class="container header-inner">
        <a class="brand" href="<?= is_logged_in() ? 'index.php' : 'login.php' ?>">&#9998; Blog Site</a>
        <nav class="nav">
            <?php if (is_logged_in()): ?>
                <span class="nav-user">Hi, <strong><?= e($_SESSION['user_name'] ?? '') ?></strong></span>
                <form method="post" action="logout.php" class="inline-form">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-ghost btn-sm">Log out</button>
                </form>
            <?php else: ?>
                <a href="login.php">Log in</a>
                <a href="register.php">Register</a>
            <?php endif; ?>
        </nav>
    </div>
</header>
<main class="container">
<?php foreach (pull_flashes() as $f): ?>
    <div class="alert alert-<?= e($f['type']) ?>" role="alert"><?= e($f['message']) ?></div>
<?php endforeach; ?>
