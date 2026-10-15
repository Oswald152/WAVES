<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

requireLogin();

if ((currentUser()['role'] ?? '') === 'intern') {
    redirect('work.php');
}

$pdo = getDbConnection();

$stats = [
    'people' => (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn(),
    'projects' => (int) $pdo->query('SELECT COUNT(*) FROM projects')->fetchColumn(),
    'tasks' => (int) $pdo->query('SELECT COUNT(*) FROM tasks')->fetchColumn(),
    'reports' => (int) $pdo->query('SELECT COUNT(*) FROM weekly_reports')->fetchColumn(),
];

$recentTasks = $pdo->query(
    'SELECT t.id, t.title, t.status, p.name AS project_name, u.full_name AS assignee
     FROM tasks t
     LEFT JOIN projects p ON p.id = t.project_id
     LEFT JOIN profiles u ON u.user_id = t.assignee_id
     ORDER BY t.created_at DESC
     LIMIT 5'
)->fetchAll();

$announcements = $pdo->query(
    'SELECT a.title, a.content, a.created_at, p.full_name AS author
     FROM announcements a
     LEFT JOIN profiles p ON p.user_id = a.created_by
    WHERE a.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
     ORDER BY a.created_at DESC
     LIMIT 3'
)->fetchAll();

include __DIR__ . '/includes/header.php';
?>
<div class="card-grid">
    <div class="card">
        <div class="stat">
            <div>
                <p class="number"><?= $stats['people'] ?></p>
                <span class="label">People</span>
            </div>
            <div class="badge">P</div>
        </div>
    </div>

    <div class="card">
        <div class="stat">
            <div>
                <p class="number"><?= $stats['projects'] ?></p>
                <span class="label">Projects</span>
            </div>
            <div class="badge">Pr</div>
        </div>
    </div>

    <div class="card">
        <div class="stat">
            <div>
                <p class="number"><?= $stats['tasks'] ?></p>
                <span class="label">Tasks</span>
            </div>
            <div class="badge">T</div>
        </div>
    </div>

    <div class="card">
        <div class="stat">
            <div>
                <p class="number"><?= $stats['reports'] ?></p>
                <span class="label">Reports</span>
            </div>
            <div class="badge">R</div>
        </div>
    </div>
</div>

<?php if (isAdmin()): ?>
<div class="card" style="margin-bottom: 24px;">
    <h3>Quick Actions</h3>
    <div class="action-row">
        <a class="btn btn-primary" href="new-task.php">Create Task</a>
        <a class="btn btn-secondary" href="new-announcement.php">Post Announcement</a>
    </div>
</div>
<?php endif; ?>

<div class="row">
    <section class="list-panel">
        <h3>Recent Tasks</h3>
        <table class="table">
            <thead>
                <tr>
                    <th>Task</th>
                    <th>Project</th>
                    <th>Assignee</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recentTasks as $task): ?>
                    <tr>
                        <td><?= htmlspecialchars($task['title']) ?></td>
                        <td><?= htmlspecialchars($task['project_name'] ?? 'General') ?></td>
                        <td><?= htmlspecialchars($task['assignee'] ?? 'Unassigned') ?></td>
                        <td>
                            <span class="status-pill <?= $task['status'] === 'completed' ? 'status-done' : ($task['status'] === 'in_progress' ? 'status-progress' : 'status-open') ?>">
                                <?= htmlspecialchars($task['status']) ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </section>

    <section class="list-panel">
        <h3>Announcements</h3>
        <div class="quick-list">
            <?php foreach ($announcements as $announcement): ?>
                <div class="quick-item">
                    <strong><?= htmlspecialchars($announcement['title']) ?></strong>
                    <div class="muted"><?= htmlspecialchars($announcement['author'] ?? 'WAVES Team') ?></div>
                    <p><?= htmlspecialchars($announcement['content']) ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
