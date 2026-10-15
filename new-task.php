<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

requireAdmin();

$pdo = getDbConnection();

$users = $pdo->query(
    'SELECT u.id, u.email, p.full_name FROM users u LEFT JOIN profiles p ON p.user_id = u.id ORDER BY p.full_name ASC'
)->fetchAll();

$projects = $pdo->query(
    'SELECT id, name FROM projects ORDER BY name ASC'
)->fetchAll();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $projectId = $_POST['project_id'] ?? null;
    $assigneeId = $_POST['assignee_id'] ?? null;
    $dueDate = $_POST['due_date'] ?? null;
    $status = $_POST['status'] ?? 'open';

    if ($title === '') {
        $error = 'Task title is required.';
    } else {
        $stmt = $pdo->prepare(
            'INSERT INTO tasks (project_id, assignee_id, title, description, due_date, status)
             VALUES (:project_id, :assignee_id, :title, :description, :due_date, :status)'
        );
        $stmt->execute([
            'project_id' => $projectId !== '' ? (int) $projectId : null,
            'assignee_id' => $assigneeId !== '' ? (int) $assigneeId : null,
            'title' => $title,
            'description' => $description,
            'due_date' => $dueDate !== '' ? $dueDate : null,
            'status' => $status,
        ]);

        $success = 'Task created successfully.';
    }
}

include __DIR__ . '/includes/header.php';
?>
<section class="form-panel">
    <h3>Create Task</h3>

    <?php if ($error !== ''): ?>
        <div class="alert"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if ($success !== ''): ?>
        <div class="alert" style="background: rgba(16, 185, 129, 0.08); color: #047857; border-color: rgba(16, 185, 129, 0.2);">
            <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>

    <form method="post" action="new-task.php">
        <div class="form-grid">
            <div class="field">
                <label for="title">Task title</label>
                <input id="title" name="title" type="text" required>
            </div>

            <div class="field">
                <label for="status">Status</label>
                <select id="status" name="status">
                    <option value="open">Open</option>
                    <option value="in_progress">In progress</option>
                    <option value="completed">Completed</option>
                </select>
            </div>

            <div class="field">
                <label for="project_id">Project</label>
                <select id="project_id" name="project_id">
                    <option value="">General</option>
                    <?php foreach ($projects as $project): ?>
                        <option value="<?= (int) $project['id'] ?>"><?= htmlspecialchars($project['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="field">
                <label for="assignee_id">Assignee</label>
                <select id="assignee_id" name="assignee_id">
                    <option value="">Unassigned</option>
                    <?php foreach ($users as $user): ?>
                        <option value="<?= (int) $user['id'] ?>"><?= htmlspecialchars($user['full_name'] ?: ($user['email'] ?? 'User')) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="field" style="grid-column: 1 / -1;">
                <label for="description">Description</label>
                <textarea id="description" name="description" placeholder="Describe the task, expected outcome, and details..."></textarea>
            </div>

            <div class="field">
                <label for="due_date">Due date</label>
                <input id="due_date" name="due_date" type="date">
            </div>
        </div>

        <div class="action-row" style="margin-top: 20px;">
            <button type="submit" class="btn btn-primary">Save Task</button>
            <a class="btn btn-secondary" href="work.php">Back to Work</a>
        </div>
    </form>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
