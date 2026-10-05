<?php
declare(strict_types=1);

require __DIR__ . '/db.php';
require __DIR__ . '/includes/helpers.php';

require_auth(); // AUTHENTICATED page

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_valid()) {
    flash('error', 'Invalid request.');
    redirect('index.php');
}

$id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if (!$id) {
    flash('error', 'Invalid post.');
    redirect('index.php');
}

// Owner-only delete. ON DELETE CASCADE removes the post's comments automatically.
$stmt = $pdo->prepare('DELETE FROM posts WHERE id = :id AND user_id = :uid');
$stmt->execute([':id' => $id, ':uid' => current_user_id()]);

if ($stmt->rowCount() === 1) {
    flash('success', 'Post deleted.');
} else {
    flash('error', 'You can only delete your own posts.');
}
redirect('index.php');
