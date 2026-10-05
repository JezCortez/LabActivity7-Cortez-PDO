<?php
declare(strict_types=1);

require __DIR__ . '/db.php';
require __DIR__ . '/includes/helpers.php';

require_auth(); // AUTHENTICATED page

const CMT_MIN = 1;   const CMT_MAX = 1000;

$id = get_id() ?? filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if (!$id) {
    flash('error', 'Invalid comment.');
    redirect('index.php');
}

$stmt = $pdo->prepare(
    'SELECT c.id, c.post_id, c.user_id, c.body, p.title AS post_title
       FROM comments c
       INNER JOIN posts p ON p.id = c.post_id
      WHERE c.id = :id'
);
$stmt->execute([':id' => $id]);
$comment = $stmt->fetch();

if (!$comment) {
    flash('error', 'That comment no longer exists.');
    redirect('index.php');
}
if ((int) $comment['user_id'] !== current_user_id()) {
    http_response_code(403);
    flash('error', 'You can only edit your own comments.');
    redirect('index.php#post-' . (int) $comment['post_id']);
}

$errors = [];
$body   = $comment['body'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valid()) {
        $errors['form'] = 'Your session expired. Please try again.';
    }

    $body = trim((string) ($_POST['body'] ?? ''));

    if ($body === '') {
        $errors['body'] = 'Comment cannot be empty.';
    } elseif (mb_strlen($body) < CMT_MIN || mb_strlen($body) > CMT_MAX) {
        $errors['body'] = 'Comment must be ' . CMT_MIN . '–' . CMT_MAX . ' characters.';
    }

    if (!$errors) {
        if ($body === $comment['body']) {
            flash('info', 'No changes were made.');
        } else {
            $upd = $pdo->prepare(
                'UPDATE comments SET body = :body, edited_at = NOW()
                  WHERE id = :id AND user_id = :uid'
            );
            $upd->execute([':body' => $body, ':id' => $id, ':uid' => current_user_id()]);
            flash('success', 'Comment updated.');
        }
        redirect('index.php#comment-' . $id);
    }
}

$pageTitle = 'Edit comment';
require __DIR__ . '/includes/header.php';
?>
<section class="card">
    <h1>Edit comment</h1>
    <p class="meta">On post: <strong><?= e($comment['post_title']) ?></strong></p>

    <?php if (isset($errors['form'])): ?>
        <div class="alert alert-error"><?= e($errors['form']) ?></div>
    <?php endif; ?>

    <form method="post" action="comment_edit.php?id=<?= (int) $id ?>" novalidate data-validate>
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= (int) $id ?>">

        <div class="field">
            <label for="body">Comment</label>
            <textarea id="body" name="body" rows="4"
                      required maxlength="<?= CMT_MAX ?>" data-label="Comment"
                      data-min="<?= CMT_MIN ?>" data-max="<?= CMT_MAX ?>"><?= e($body) ?></textarea>
            <p class="field-error" id="err-body"><?= e($errors['body'] ?? '') ?></p>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Save changes</button>
            <a class="btn btn-ghost" href="index.php#comment-<?= (int) $id ?>">Cancel</a>
        </div>
    </form>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
