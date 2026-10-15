<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

requireLogin();

$pdo = getDbConnection();
$projects = $pdo->query(
    'SELECT p.id, p.name, p.description, p.status, COUNT(pm.id) AS members
     FROM projects p
     LEFT JOIN project_members pm ON pm.project_id = p.id
     GROUP BY p.id, p.name, p.description, p.status
     ORDER BY p.created_at DESC'
)->fetchAll();

include __DIR__ . '/includes/header.php';
?>
<section class="list-panel">
    <h3>Projects</h3>
    <table class="table">
        <thead>
            <tr>
                <th>Name</th>
                <th>Description</th>
                <th>Members</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($projects as $project): ?>
                <tr>
                    <td><?= htmlspecialchars($project['name']) ?></td>
                    <td><?= htmlspecialchars($project['description']) ?></td>
                    <td><?= (int) $project['members'] ?></td>
                    <td>
                        <span class="status-pill <?= $project['status'] === 'completed' ? 'status-done' : ($project['status'] === 'active' ? 'status-progress' : 'status-open') ?>">
                            <?= htmlspecialchars($project['status']) ?>
                        </span>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
