<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';

if (currentUser()) {
    redirect(isAdmin() ? 'dashboard.php' : 'work.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    $user = loginUser($email, $password, 'non-admin');

    if ($user) {
        redirect(($user['role'] ?? '') === 'admin' ? 'dashboard.php' : 'work.php');
    }

    $error = 'Invalid email or password.';
}

include __DIR__ . '/includes/header.php';
?>
<div class="auth-card">
    <h2>Intern Sign In</h2>
    <p>Work. Learn. Engage. Advance.</p>

    <?php if ($error !== ''): ?>
        <div class="alert"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="post" action="login.php">
        <div class="field">
            <label for="email">Email</label>
            <input id="email" name="email" type="email" required>
        </div>

        <div class="field" style="margin-top: 16px;">
            <label for="password">Password</label>
            <input id="password" name="password" type="password" required>
        </div>

        <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 20px;">Sign In</button>
    </form>

    <div class="auth-actions">
        <a class="btn btn-primary" href="register.php">Create a new account</a>
        <a class="btn btn-secondary" href="admin-login.php">Admin sign in</a>
    </div>
</div>
<?php include __DIR__ . '/includes/footer.php';
