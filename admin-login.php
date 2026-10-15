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

    $user = loginUser($email, $password, 'admin');

    if ($user) {
        redirect('dashboard.php');
    }

    $error = 'Invalid administrator email or password.';
}

include __DIR__ . '/includes/header.php';
?>
<div class="auth-card">
    <h2>Admin Sign In</h2>
    <p>Sign in with an administrator account.</p>

    <?php if ($error !== ''): ?>
        <div class="alert"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="post" action="admin-login.php">
        <div class="field">
            <label for="email">Admin email</label>
            <input id="email" name="email" type="email" autocomplete="username" required>
        </div>

        <div class="field" style="margin-top: 16px;">
            <label for="password">Password</label>
            <input id="password" name="password" type="password" autocomplete="current-password" required>
        </div>

        <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 20px;">Admin Sign In</button>
    </form>

    <div class="auth-actions">
        <a class="btn btn-secondary" href="login.php">Intern sign in</a>
    </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>