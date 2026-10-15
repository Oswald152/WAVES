<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';

if (currentUser()) {
    redirect(isAdmin() ? 'dashboard.php' : 'work.php');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if ($fullName === '' || $email === '' || $password === '' || $confirmPassword === '') {
        $error = 'Please complete all fields.';
    } elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } else {
        $user = registerUser($fullName, $email, $password, 'intern');

        if ($user) {
            $_SESSION['user'] = $user;
            $success = 'Account created successfully. You are now signed in.';
            redirect('work.php');
        } else {
            $error = 'This email already exists or the account could not be created.';
        }
    }
}

include __DIR__ . '/includes/header.php';
?>
<div class="auth-card">
    <h2>Create Account</h2>
    <p>Join WAVES and start contributing to your organization.</p>

    <?php if ($error !== ''): ?>
        <div class="alert"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if ($success !== ''): ?>
        <div class="alert" style="background: rgba(16, 185, 129, 0.08); color: #047857; border-color: rgba(16, 185, 129, 0.2);">
            <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>

    <form method="post" action="register.php">
        <div class="field">
            <label for="full_name">Full name</label>
            <input id="full_name" name="full_name" type="text" required>
        </div>

        <div class="field" style="margin-top: 16px;">
            <label for="email">Email</label>
            <input id="email" name="email" type="email" required>
        </div>

        <div class="field" style="margin-top: 16px;">
            <label for="password">Password</label>
            <input id="password" name="password" type="password" required>
        </div>

        <div class="field" style="margin-top: 16px;">
            <label for="confirm_password">Confirm password</label>
            <input id="confirm_password" name="confirm_password" type="password" required>
        </div>

        <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 20px;">Create Account</button>
    </form>

    <p style="margin-top: 18px;"><a href="login.php">Back to login</a></p>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
