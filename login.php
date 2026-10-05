<?php
declare(strict_types=1);

require __DIR__ . '/db.php';
require __DIR__ . '/includes/helpers.php';

require_guest(); // GUEST-only page

$errors = [];
$email  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valid()) {
        $errors['form'] = 'Your session expired. Please try again.';
    }

    $email    = strtolower(trim((string) ($_POST['email'] ?? '')));
    $password = (string) ($_POST['password'] ?? '');

    // ---- Server-side validation ----
    if ($email === '') {
        $errors['email'] = 'Email is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Enter a valid email address.';
    }

    if ($password === '') {
        $errors['password'] = 'Password is required.';
    }

    // ---- Authenticate ----
    if (!$errors) {
        $stmt = $pdo->prepare('SELECT id, name, password FROM users WHERE email = :email');
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true); // prevent session fixation
            $_SESSION['user_id']   = (int) $user['id'];
            $_SESSION['user_name'] = $user['name'];

            redirect('index.php');
        }

        // Same message for "no such email" and "wrong password" (don't leak which one)
        $errors['form'] = 'Invalid email or password.';
    }
}

$pageTitle = 'Log in';
$bodyClass = 'page-auth';
require __DIR__ . '/includes/header.php';
?>
<section class="card auth-card">
    <h1>Welcome back</h1>
    <?php if (isset($errors['form'])): ?>
        <div class="alert alert-error"><?= e($errors['form']) ?></div>
    <?php endif; ?>

    <form method="post" action="login.php" novalidate data-validate>
        <?= csrf_field() ?>

        <div class="field">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="<?= e($email) ?>"
                   required maxlength="255" data-label="Email" data-type="email" autocomplete="email">
            <p class="field-error" id="err-email"><?= e($errors['email'] ?? '') ?></p>
        </div>

        <div class="field">
            <label for="password">Password</label>
            <input type="password" id="password" name="password"
                   required data-label="Password" autocomplete="current-password">
            <p class="field-error" id="err-password"><?= e($errors['password'] ?? '') ?></p>
        </div>

        <button type="submit" class="btn btn-primary btn-block">Log in</button>
    </form>

    <p class="auth-switch">No account yet? <a href="register.php">Register</a></p>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
