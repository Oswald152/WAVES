<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

requireLogin();

if (isAdmin()) {
    redirect('dashboard.php');
}

$pdo = getDbConnection();
$user = currentUser();
$taskQuery =
    'SELECT t.id, t.title, t.status, t.due_date, t.created_at, p.name AS project_name, pr.full_name AS assignee,
            CASE WHEN tr.task_id IS NULL THEN 1 ELSE 0 END AS is_new
     FROM tasks t
     LEFT JOIN projects p ON p.id = t.project_id
     LEFT JOIN profiles pr ON pr.user_id = t.assignee_id
     LEFT JOIN task_reads tr ON tr.task_id = t.id AND tr.user_id = :read_user_id ';

$parameters = ['read_user_id' => (int) $user['id']];
if (($user['role'] ?? '') === 'intern') {
    $taskQuery .= 'WHERE t.assignee_id = :user_id ';
    $parameters['user_id'] = (int) $user['id'];
}
$taskQuery .= 'ORDER BY t.due_date ASC';
$taskStmt = $pdo->prepare($taskQuery);
$taskStmt->execute($parameters);
$tasks = $taskStmt->fetchAll();

include __DIR__ . '/includes/header.php';
?>
<section class="list-panel">
    <h3>Assigned Work</h3>
    <table class="table">
        <thead>
            <tr>
                <th>Title</th>
                <th>Project</th>
                <th>Due Date</th>
                <th>Assignee</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($tasks as $task): ?>
                <tr>
                    <td>
                        <a href="task.php?id=<?= (int) $task['id'] ?>"><?= htmlspecialchars($task['title']) ?></a>
                        <?php if ((int) $task['is_new'] === 1): ?>
                            <span class="nav-badge">New</span>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($task['project_name'] ?? 'General') ?></td>
                    <td><?= htmlspecialchars($task['due_date'] ?? 'No date') ?></td>
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
<?php include __DIR__ . '/includes/footer.php'; ?>
