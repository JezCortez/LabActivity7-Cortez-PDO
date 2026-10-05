<?php
declare(strict_types=1);

require __DIR__ . '/db.php';
require __DIR__ . '/includes/helpers.php';

require_guest(); // GUEST-only page

$errors = [];
$old    = ['name' => '', 'email' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valid()) {
        $errors['form'] = 'Your session expired. Please try again.';
    }

    $name     = trim((string) ($_POST['name'] ?? ''));
    $email    = strtolower(trim((string) ($_POST['email'] ?? '')));
    $password = (string) ($_POST['password'] ?? '');
    $confirm  = (string) ($_POST['confirm_password'] ?? '');
    $old      = ['name' => $name, 'email' => $email];

    // ---- Server-side validation ----
    if ($name === '') {
        $errors['name'] = 'Name is required.';
    } elseif (mb_strlen($name) < 2 || mb_strlen($name) > 100) {
        $errors['name'] = 'Name must be 2–100 characters.';
    }

    if ($email === '') {
        $errors['email'] = 'Email is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 255) {
        $errors['email'] = 'Enter a valid email address.';
    }

    if ($password === '') {
        $errors['password'] = 'Password is required.';
    } elseif (strlen($password) < 8) {
        $errors['password'] = 'Password must be at least 8 characters.';
    } elseif (strlen($password) > 72) {
        $errors['password'] = 'Password must be at most 72 characters.';
    } elseif (!preg_match('/[A-Za-z]/', $password) || !preg_match('/\d/', $password)) {
        $errors['password'] = 'Password must contain at least one letter and one number.';
    }

    if ($confirm === '') {
        $errors['confirm_password'] = 'Please confirm your password.';
    } elseif ($password !== $confirm) {
        $errors['confirm_password'] = 'Passwords do not match.';
    }

    // ---- Email must be unique ----
    if (!isset($errors['email'])) {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = :email');
        $stmt->execute([':email' => $email]);
        if ($stmt->fetch()) {
            $errors['email'] = 'That email is already registered.';
        }
    }

    // ---- Insert ----
    if (!$errors) {
        try {
            $stmt = $pdo->prepare(
                'INSERT INTO users (name, email, password) VALUES (:name, :email, :password)'
            );
            $stmt->execute([
                ':name'     => $name,
                ':email'    => $email,
                ':password' => password_hash($password, PASSWORD_DEFAULT),
            ]);

            flash('success', 'Account created! You can now log in.');
            redirect('login.php');
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') { // duplicate key (race condition on UNIQUE email)
                $errors['email'] = 'That email is already registered.';
            } else {
                error_log($e->getMessage());
                $errors['form'] = 'Something went wrong. Please try again.';
            }
        }
    }
}

$pageTitle = 'Register';
$bodyClass = 'page-auth';
require __DIR__ . '/includes/header.php';
?>
<section class="card auth-card">
    <h1>Create an account</h1>
    <?php if (isset($errors['form'])): ?>
        <div class="alert alert-error"><?= e($errors['form']) ?></div>
    <?php endif; ?>

    <form method="post" action="register.php" novalidate data-validate>
        <?= csrf_field() ?>

        <div class="field">
            <label for="name">Name</label>
            <input type="text" id="name" name="name" value="<?= e($old['name']) ?>"
                   required maxlength="100" data-label="Name" data-min="2" data-max="100" autocomplete="name">
            <p class="field-error" id="err-name"><?= e($errors['name'] ?? '') ?></p>
        </div>

        <div class="field">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="<?= e($old['email']) ?>"
                   required maxlength="255" data-label="Email" data-type="email" autocomplete="email">
            <p class="field-error" id="err-email"><?= e($errors['email'] ?? '') ?></p>
        </div>

        <div class="field">
            <label for="password">Password</label>
            <input type="password" id="password" name="password"
                   required maxlength="72" data-label="Password" data-min="8" data-max="72" data-strong
                   autocomplete="new-password">
            <p class="hint">At least 8 characters, with a letter and a number.</p>
            <p class="field-error" id="err-password"><?= e($errors['password'] ?? '') ?></p>
        </div>

        <div class="field">
            <label for="confirm_password">Confirm password</label>
            <input type="password" id="confirm_password" name="confirm_password"
                   required maxlength="72" data-label="Confirm password" data-match="#password"
                   autocomplete="new-password">
            <p class="field-error" id="err-confirm_password"><?= e($errors['confirm_password'] ?? '') ?></p>
        </div>

        <button type="submit" class="btn btn-primary btn-block">Register</button>
    </form>

    <p class="auth-switch">Already have an account? <a href="login.php">Log in</a></p>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
