<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

requireLogin();

if (isAdmin()) {
    redirect('dashboard.php');
}

$pdo = getDbConnection();
$userId = currentUser()['id'];

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['learning_submit'])) {
    $title = trim($_POST['title'] ?? '');
    $type = trim($_POST['type'] ?? 'course');
    $notes = trim($_POST['notes'] ?? '');
    $dueDate = trim($_POST['due_date'] ?? '');
    $status = trim($_POST['status'] ?? 'assigned');

    if ($title === '') {
        $error = 'Please add a learning topic or activity title.';
    } else {
        $stmt = $pdo->prepare(
            'INSERT INTO learning_items (user_id, title, type, notes, status, due_date)
             VALUES (:user_id, :title, :type, :notes, :status, :due_date)'
        );

        $stmt->execute([
            'user_id' => $userId,
            'title' => $title,
            'type' => $type !== '' ? $type : 'course',
            'notes' => $notes !== '' ? $notes : null,
            'status' => $status,
            'due_date' => $dueDate !== '' ? $dueDate : null,
        ]);

        $success = 'Your learning update has been saved.';
    }
}

$learning = $pdo->prepare(
    'SELECT title, type, notes, status, due_date FROM learning_items WHERE user_id = :user_id ORDER BY due_date ASC'
);
$learning->execute(['user_id' => $userId]);
$items = $learning->fetchAll();

include __DIR__ . '/includes/header.php';
?>
<section class="form-panel" style="margin-bottom: 24px;">
    <h3>Add Learning Update</h3>

    <?php if ($error !== ''): ?>
        <div class="alert"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if ($success !== ''): ?>
        <div class="alert" style="background: rgba(16, 185, 129, 0.08); color: #047857; border-color: rgba(16, 185, 129, 0.2);">
            <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>

    <form method="post" action="learning.php">
        <div class="form-grid">
            <div class="field">
                <label for="title">What I learned</label>
                <input id="title" name="title" type="text" required placeholder="Example: PHP session handling / SEO basics">
            </div>

            <div class="field" style="grid-column: 1 / -1;">
                <label for="notes">Notes</label>
                <textarea id="notes" name="notes" placeholder="Record what you learned, key takeaways, or useful links."></textarea>
            </div>

            <div class="field">
                <label for="type">Learning type</label>
                <select id="type" name="type">
                    <option value="course">Course</option>
                    <option value="training">Training</option>
                    <option value="self_study">Self study</option>
                    <option value="project">Project work</option>
                    <option value="meeting">Meeting</option>
                </select>
            </div>

            <div class="field">
                <label for="due_date">Due date</label>
                <input id="due_date" name="due_date" type="date">
            </div>

            <div class="field">
                <label for="status">Status</label>
                <select id="status" name="status">
                    <option value="assigned">Assigned</option>
                    <option value="in_progress">In progress</option>
                    <option value="completed">Completed</option>
                </select>
            </div>
        </div>

        <div class="action-row" style="margin-top: 20px;">
            <button type="submit" name="learning_submit" value="1" class="btn btn-primary">Save Learning</button>
        </div>
    </form>
</section>

<section class="list-panel">
    <h3>My Learning Log</h3>
    <table class="table">
        <thead>
            <tr>
                <th>Title</th>
                <th>Notes</th>
                <th>Type</th>
                <th>Due Date</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $item): ?>
                <tr>
                    <td><?= htmlspecialchars($item['title']) ?></td>
                    <td><?= nl2br(htmlspecialchars($item['notes'] ?? '')) ?></td>
                    <td><?= htmlspecialchars($item['type']) ?></td>
                    <td><?= htmlspecialchars($item['due_date'] ?? 'No date') ?></td>
                    <td>
                        <span class="status-pill <?= $item['status'] === 'completed' ? 'status-done' : ($item['status'] === 'in_progress' ? 'status-progress' : 'status-open') ?>">
                            <?= htmlspecialchars($item['status']) ?>
                        </span>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
