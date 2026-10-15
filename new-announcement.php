<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

requireAdmin();

$pdo = getDbConnection();
$user = currentUser();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');

    if ($title === '' || $content === '') {
        $error = 'Title and content are both required.';
    } else {
        $stmt = $pdo->prepare(
            'INSERT INTO announcements (organization_id, title, content, created_by)
             VALUES (:organization_id, :title, :content, :created_by)'
        );
        $stmt->execute([
            'organization_id' => (int) $user['organization_id'],
            'title' => $title,
            'content' => $content,
            'created_by' => (int) $user['id'],
        ]);

        $success = 'Announcement posted successfully.';
    }
}

include __DIR__ . '/includes/header.php';
?>
<section class="form-panel">
    <h3>Post Announcement</h3>

    <?php if ($error !== ''): ?>
        <div class="alert"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if ($success !== ''): ?>
        <div class="alert" style="background: rgba(16, 185, 129, 0.08); color: #047857; border-color: rgba(16, 185, 129, 0.2);">
            <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>

    <form method="post" action="new-announcement.php">
        <div class="form-grid">
            <div class="field" style="grid-column: 1 / -1;">
                <label for="title">Announcement title</label>
                <input id="title" name="title" type="text" required>
            </div>

            <div class="field" style="grid-column: 1 / -1;">
                <label for="content">Message</label>
                <textarea id="content" name="content" required placeholder="Share important updates, reminders, or organizational news..."></textarea>
            </div>
        </div>

        <div class="action-row" style="margin-top: 20px;">
            <button type="submit" class="btn btn-primary">Publish</button>
            <a class="btn btn-secondary" href="announcements.php">Back to Announcements</a>
        </div>
    </form>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
