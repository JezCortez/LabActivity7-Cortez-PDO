<?php
declare(strict_types=1);

require __DIR__ . '/db.php';
require __DIR__ . '/includes/helpers.php';

require_auth(); // AUTHENTICATED page

const TITLE_MIN = 3;   const TITLE_MAX = 150;
const POST_MIN  = 3;   const POST_MAX  = 5000;

$id = get_id() ?? filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if (!$id) {
    flash('error', 'Invalid post.');
    redirect('index.php');
}

// Load the post, then check OWNERSHIP before showing or saving anything
$stmt = $pdo->prepare('SELECT id, user_id, title, body FROM posts WHERE id = :id');
$stmt->execute([':id' => $id]);
$post = $stmt->fetch();

if (!$post) {
    flash('error', 'That post no longer exists.');
    redirect('index.php');
}
if ((int) $post['user_id'] !== current_user_id()) {
    http_response_code(403);
    flash('error', 'You can only edit your own posts.');
    redirect('index.php');
}

$errors = [];
$title  = $post['title'];
$body   = $post['body'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valid()) {
        $errors['form'] = 'Your session expired. Please try again.';
    }

    $title = trim((string) ($_POST['title'] ?? ''));
    $body  = trim((string) ($_POST['body'] ?? ''));

    if ($title === '') {
        $errors['title'] = 'Title is required.';
    } elseif (mb_strlen($title) < TITLE_MIN || mb_strlen($title) > TITLE_MAX) {
        $errors['title'] = 'Title must be ' . TITLE_MIN . '–' . TITLE_MAX . ' characters.';
    }

    if ($body === '') {
        $errors['body'] = 'Post content is required.';
    } elseif (mb_strlen($body) < POST_MIN || mb_strlen($body) > POST_MAX) {
        $errors['body'] = 'Post must be ' . POST_MIN . '–' . POST_MAX . ' characters.';
    }

    if (!$errors) {
        if ($title === $post['title'] && $body === $post['body']) {
            flash('info', 'No changes were made.');
        } else {
            // WHERE includes user_id so a user can never update someone else's post
            $upd = $pdo->prepare(
                'UPDATE posts SET title = :title, body = :body, edited_at = NOW()
                  WHERE id = :id AND user_id = :uid'
            );
            $upd->execute([
                ':title' => $title,
                ':body'  => $body,
                ':id'    => $id,
                ':uid'   => current_user_id(),
            ]);
            flash('success', 'Post updated.');
        }
        redirect('index.php#post-' . $id);
    }
}

$pageTitle = 'Edit post';
require __DIR__ . '/includes/header.php';
?>
<section class="card">
    <h1>Edit post</h1>
    <?php if (isset($errors['form'])): ?>
        <div class="alert alert-error"><?= e($errors['form']) ?></div>
    <?php endif; ?>

    <form method="post" action="post_edit.php?id=<?= (int) $id ?>" novalidate data-validate>
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= (int) $id ?>">

        <div class="field">
            <label for="title">Title</label>
            <input type="text" id="title" name="title" value="<?= e($title) ?>"
                   required maxlength="<?= TITLE_MAX ?>" data-label="Title"
                   data-min="<?= TITLE_MIN ?>" data-max="<?= TITLE_MAX ?>">
            <p class="field-error" id="err-title"><?= e($errors['title'] ?? '') ?></p>
        </div>

        <div class="field">
            <label for="body">Content</label>
            <textarea id="body" name="body" rows="8"
                      required maxlength="<?= POST_MAX ?>" data-label="Post"
                      data-min="<?= POST_MIN ?>" data-max="<?= POST_MAX ?>"><?= e($body) ?></textarea>
            <p class="field-error" id="err-body"><?= e($errors['body'] ?? '') ?></p>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Save changes</button>
            <a class="btn btn-ghost" href="index.php#post-<?= (int) $id ?>">Cancel</a>
        </div>
    </form>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
