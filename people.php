<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

requireLogin();

$pdo = getDbConnection();
$people = $pdo->query(
    'SELECT p.full_name, p.team_name, p.department, p.job_title, u.email, u.role
     FROM profiles p
     JOIN users u ON u.id = p.user_id
     ORDER BY p.full_name ASC'
)->fetchAll();

include __DIR__ . '/includes/header.php';
?>
<section class="list-panel">
    <h3>People</h3>
    <table class="table">
        <thead>
            <tr>
                <th>Name</th>
                <th>Role</th>
                <th>Team</th>
                <th>Department</th>
                <th>Email</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($people as $person): ?>
                <tr>
                    <td><?= htmlspecialchars($person['full_name']) ?></td>
                    <td><?= htmlspecialchars($person['role']) ?></td>
                    <td><?= htmlspecialchars($person['team_name'] ?? 'N/A') ?></td>
                    <td><?= htmlspecialchars($person['department'] ?? 'N/A') ?></td>
                    <td><?= htmlspecialchars($person['email']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
