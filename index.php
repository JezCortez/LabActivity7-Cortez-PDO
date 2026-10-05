<?php
declare(strict_types=1);

require __DIR__ . '/db.php';
require __DIR__ . '/includes/helpers.php';

require_auth(); // AUTHENTICATED page

const TITLE_MIN = 3;   const TITLE_MAX = 150;
const POST_MIN  = 3;   const POST_MAX  = 5000;
const CMT_MIN   = 1;   const CMT_MAX   = 1000;

$postErrors    = [];
$postOld       = ['title' => '', 'body' => ''];
$commentErrors = []; // keyed by post id
$commentOld    = []; // keyed by post id

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? '');

    if (!csrf_valid()) {
        flash('error', 'Your session expired. Please try again.');
        redirect('index.php');
    }

    /* ---------- Create a blog post ---------- */
    if ($action === 'create_post') {
        $title = trim((string) ($_POST['title'] ?? ''));
        $body  = trim((string) ($_POST['body'] ?? ''));
        $postOld = ['title' => $title, 'body' => $body];

        if ($title === '') {
            $postErrors['title'] = 'Title is required.';
        } elseif (mb_strlen($title) < TITLE_MIN || mb_strlen($title) > TITLE_MAX) {
            $postErrors['title'] = 'Title must be ' . TITLE_MIN . '–' . TITLE_MAX . ' characters.';
        }

        if ($body === '') {
            $postErrors['body'] = 'Post content is required.';
        } elseif (mb_strlen($body) < POST_MIN || mb_strlen($body) > POST_MAX) {
            $postErrors['body'] = 'Post must be ' . POST_MIN . '–' . POST_MAX . ' characters.';
        }

        if (!$postErrors) {
            $stmt = $pdo->prepare('INSERT INTO posts (user_id, title, body) VALUES (:uid, :title, :body)');
            $stmt->execute([':uid' => current_user_id(), ':title' => $title, ':body' => $body]);

            flash('success', 'Your post was published.');
            redirect('index.php');
        }
    }

    /* ---------- Add a comment ---------- */
    if ($action === 'add_comment') {
        $postId = filter_var($_POST['post_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $body   = trim((string) ($_POST['body'] ?? ''));

        if ($postId === false) {
            flash('error', 'Invalid post.');
            redirect('index.php');
        }

        $commentOld[$postId] = $body;

        // The post must exist
        $check = $pdo->prepare('SELECT id FROM posts WHERE id = :id');
        $check->execute([':id' => $postId]);
        if (!$check->fetch()) {
            flash('error', 'That post no longer exists.');
            redirect('index.php');
        }

        if ($body === '') {
            $commentErrors[$postId] = 'Comment cannot be empty.';
        } elseif (mb_strlen($body) < CMT_MIN || mb_strlen($body) > CMT_MAX) {
            $commentErrors[$postId] = 'Comment must be ' . CMT_MIN . '–' . CMT_MAX . ' characters.';
        }

        if (!isset($commentErrors[$postId])) {
            $stmt = $pdo->prepare('INSERT INTO comments (post_id, user_id, body) VALUES (:pid, :uid, :body)');
            $stmt->execute([':pid' => $postId, ':uid' => current_user_id(), ':body' => $body]);

            flash('success', 'Comment added.');
            redirect('index.php#post-' . $postId);
        }
    }
}

/* ---------- News feed: all posts, most recent first (one JOIN, no N+1) ---------- */
$posts = $pdo->query(
    'SELECT p.id, p.user_id, p.title, p.body, p.created_at, p.edited_at, u.name AS author
       FROM posts p
       INNER JOIN users u ON u.id = p.user_id
      ORDER BY p.created_at DESC, p.id DESC'
)->fetchAll();

/* ---------- Comments for the posts above (single IN query) ---------- */
$commentsByPost = [];
if ($posts) {
    $ids = array_column($posts, 'id');
    $in  = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare(
        "SELECT c.id, c.post_id, c.user_id, c.body, c.created_at, c.edited_at, u.name AS author
           FROM comments c
           INNER JOIN users u ON u.id = c.user_id
          WHERE c.post_id IN ($in)
          ORDER BY c.created_at ASC, c.id ASC"
    );
    $stmt->execute($ids);
    foreach ($stmt->fetchAll() as $c) {
        $commentsByPost[$c['post_id']][] = $c;
    }
}

$me        = current_user_id();
$pageTitle = 'News Feed';
require __DIR__ . '/includes/header.php';
?>
<h1>News Feed</h1>

<section class="card">
    <h2>Write a post</h2>
    <form method="post" action="index.php" novalidate data-validate>
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="create_post">

        <div class="field">
            <label for="title">Title</label>
            <input type="text" id="title" name="title" value="<?= e($postOld['title']) ?>"
                   required maxlength="<?= TITLE_MAX ?>" data-label="Title"
                   data-min="<?= TITLE_MIN ?>" data-max="<?= TITLE_MAX ?>">
            <p class="field-error" id="err-title"><?= e($postErrors['title'] ?? '') ?></p>
        </div>

        <div class="field">
            <label for="body">What's on your mind?</label>
            <textarea id="body" name="body" rows="4"
                      required maxlength="<?= POST_MAX ?>" data-label="Post"
                      data-min="<?= POST_MIN ?>" data-max="<?= POST_MAX ?>"><?= e($postOld['body']) ?></textarea>
            <p class="field-error" id="err-body"><?= e($postErrors['body'] ?? '') ?></p>
        </div>

        <button type="submit" class="btn btn-primary">Publish</button>
    </form>
</section>

<?php if (!$posts): ?>
    <p class="empty">No posts yet. Be the first to write one!</p>
<?php endif; ?>

<?php foreach ($posts as $post): ?>
    <?php $pid = (int) $post['id']; $comments = $commentsByPost[$pid] ?? []; ?>
    <article class="card post" id="post-<?= $pid ?>">
        <header class="post-head">
            <div>
                <h2 class="post-title"><?= e($post['title']) ?></h2>
                <p class="meta">
                    by <strong><?= e($post['author']) ?></strong> · <?= e(format_date($post['created_at'])) ?>
                    <?php if ($post['edited_at'] !== null): ?>
                        <span class="tag-edited" title="Edited <?= e(format_date($post['edited_at'])) ?>">Edited</span>
                    <?php endif; ?>
                </p>
            </div>

            <?php if ((int) $post['user_id'] === $me): // only the owner sees these ?>
                <div class="actions">
                    <a class="btn btn-ghost btn-sm" href="post_edit.php?id=<?= $pid ?>">Edit</a>
                    <form method="post" action="post_delete.php" class="inline-form"
                          data-confirm="Delete this post and all of its comments?">
                        <?= csrf_field() ?>
                        <input type="hidden" name="id" value="<?= $pid ?>">
                        <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                    </form>
                </div>
            <?php endif; ?>
        </header>

        <div class="post-body"><?= nl2br(e($post['body'])) ?></div>

        <section class="comments">
            <h3><?= count($comments) ?> comment<?= count($comments) === 1 ? '' : 's' ?></h3>

            <?php foreach ($comments as $c): ?>
                <div class="comment" id="comment-<?= (int) $c['id'] ?>">
                    <div class="comment-head">
                        <p class="meta">
                            <strong><?= e($c['author']) ?></strong> · <?= e(format_date($c['created_at'])) ?>
                            <?php if ($c['edited_at'] !== null): ?>
                                <span class="tag-edited" title="Edited <?= e(format_date($c['edited_at'])) ?>">Edited</span>
                            <?php endif; ?>
                        </p>
                        <?php if ((int) $c['user_id'] === $me): ?>
                            <div class="actions">
                                <a class="btn btn-ghost btn-xs" href="comment_edit.php?id=<?= (int) $c['id'] ?>">Edit</a>
                                <form method="post" action="comment_delete.php" class="inline-form"
                                      data-confirm="Delete this comment?">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
                                    <button type="submit" class="btn btn-danger btn-xs">Delete</button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>
                    <p class="comment-body"><?= nl2br(e($c['body'])) ?></p>
                </div>
            <?php endforeach; ?>

            <form method="post" action="index.php#post-<?= $pid ?>" class="comment-form" novalidate data-validate>
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="add_comment">
                <input type="hidden" name="post_id" value="<?= $pid ?>">

                <div class="field">
                    <label for="comment-<?= $pid ?>" class="sr-only">Add a comment</label>
                    <textarea id="comment-<?= $pid ?>" name="body" rows="2" placeholder="Write a comment…"
                              required maxlength="<?= CMT_MAX ?>" data-label="Comment"
                              data-min="<?= CMT_MIN ?>" data-max="<?= CMT_MAX ?>"><?= e($commentOld[$pid] ?? '') ?></textarea>
                    <p class="field-error" id="err-comment-<?= $pid ?>"><?= e($commentErrors[$pid] ?? '') ?></p>
                </div>
                <button type="submit" class="btn btn-primary btn-sm">Comment</button>
            </form>
        </section>
    </article>
<?php endforeach; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
