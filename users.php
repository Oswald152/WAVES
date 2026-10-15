<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

requireAdmin();

$pdo = getDbConnection();
$user = currentUser();
$error = '';
$success = '';

if (isset($_GET['delete'])) {
    $deleteId = (int) $_GET['delete'];

    if ($deleteId === (int) $user['id']) {
        $error = 'You cannot delete your own admin account.';
    } else {
        $stmt = $pdo->prepare('DELETE FROM users WHERE id = :id AND email != :admin_email');
        $stmt->execute(['id' => $deleteId, 'admin_email' => 'oankumanyi@gmail.com']);

        if ($stmt->rowCount() > 0) {
            $success = 'User removed successfully.';
        } else {
            $error = 'The selected user could not be deleted.';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'intern';

    if ($fullName === '' || $email === '' || $password === '') {
        $error = 'Full name, email, and password are required.';
    } else {
        $newUser = registerUser($fullName, $email, $password, $role, (int) $user['organization_id'], false);

        if ($newUser) {
            $success = 'New user created successfully.';
        } else {
            $error = 'Unable to create that user. The email may already be in use.';
        }
    }
}

$users = $pdo->query(
    'SELECT u.id, u.email, u.role, p.full_name, p.department, p.team_name
     FROM users u
     LEFT JOIN profiles p ON p.user_id = u.id
     ORDER BY p.full_name ASC'
)->fetchAll();

include __DIR__ . '/includes/header.php';
?>
<section class="row">
    <div class="form-panel">
        <h3>Create User</h3>

        <?php if ($error !== ''): ?>
            <div class="alert"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if ($success !== ''): ?>
            <div class="alert" style="background: rgba(16, 185, 129, 0.08); color: #047857; border-color: rgba(16, 185, 129, 0.2);">
                <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>

        <form method="post" action="users.php">
            <div class="form-grid">
                <div class="field">
                    <label for="full_name">Full name</label>
                    <input id="full_name" name="full_name" type="text" required>
                </div>

                <div class="field">
                    <label for="role">Role</label>
                    <select id="role" name="role">
                        <option value="admin">Admin</option>
                        <option value="mentor">Mentor</option>
                        <option value="intern" selected>Intern</option>
                        <option value="staff">Staff</option>
                        <option value="national_service">National Service</option>
                    </select>
                </div>

                <div class="field" style="grid-column: 1 / -1;">
                    <label for="email">Email</label>
                    <input id="email" name="email" type="email" required>
                </div>

                <div class="field" style="grid-column: 1 / -1;">
                    <label for="password">Password</label>
                    <input id="password" name="password" type="password" required>
                </div>
            </div>

            <div class="action-row" style="margin-top: 20px;">
                <button type="submit" class="btn btn-primary">Create User</button>
            </div>
        </form>
    </div>

    <div class="list-panel">
        <h3>Registered Users</h3>
        <table class="table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Department</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $person): ?>
                    <tr>
                        <td><?= htmlspecialchars($person['full_name'] ?? 'Unnamed') ?></td>
                        <td><?= htmlspecialchars($person['email']) ?></td>
                        <td><?= htmlspecialchars($person['role']) ?></td>
                        <td><?= htmlspecialchars($person['department'] ?? 'N/A') ?></td>
                        <td>
                            <?php if ((int) $person['id'] !== (int) $user['id']): ?>
                                <a class="btn btn-secondary" href="users.php?delete=<?= (int) $person['id'] ?>" onclick="return confirm('Delete this user?');">Delete</a>
                            <?php else: ?>
                                <span class="muted">Current</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
