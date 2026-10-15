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
$taskId = (int) ($_GET['id'] ?? 0);
$taskQuery =
    'SELECT t.id, t.title, t.description, t.status, t.due_date, t.created_at,
            p.name AS project_name, pr.full_name AS assignee
     FROM tasks t
     LEFT JOIN projects p ON p.id = t.project_id
     LEFT JOIN profiles pr ON pr.user_id = t.assignee_id
     WHERE t.id = :task_id';
$parameters = ['task_id' => $taskId];

if (($user['role'] ?? '') === 'intern') {
    $taskQuery .= ' AND t.assignee_id = :user_id';
    $parameters['user_id'] = (int) $user['id'];
}

$taskStmt = $pdo->prepare($taskQuery);
$taskStmt->execute($parameters);
$task = $taskStmt->fetch();

if (!$task) {
    http_response_code(404);
    exit('Task not found.');
}

$markRead = $pdo->prepare('INSERT IGNORE INTO task_reads (task_id, user_id) VALUES (:task_id, :user_id)');
$markRead->execute([
    'task_id' => $taskId,
    'user_id' => (int) $user['id'],
]);

include __DIR__ . '/includes/header.php';
?>
<section class="list-panel">
    <h3><?= htmlspecialchars($task['title']) ?></h3>
    <p><?= nl2br(htmlspecialchars($task['description'] ?? 'No additional details.')) ?></p>
    <dl class="profile-details">
        <div><dt>Project</dt><dd><?= htmlspecialchars($task['project_name'] ?? 'General') ?></dd></div>
        <div><dt>Due date</dt><dd><?= htmlspecialchars($task['due_date'] ?? 'No date') ?></dd></div>
        <div><dt>Assignee</dt><dd><?= htmlspecialchars($task['assignee'] ?? 'Unassigned') ?></dd></div>
        <div><dt>Status</dt><dd><?= htmlspecialchars($task['status']) ?></dd></div>
    </dl>
    <a class="btn btn-secondary" href="work.php">Back to Work</a>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>