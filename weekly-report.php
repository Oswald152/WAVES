<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

requireLogin();

$pdo = getDbConnection();
$user = currentUser();
$userId = (int) $user['id'];
$isAdmin = isAdmin();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isAdmin && isset($_POST['comment_submit'])) {
    $reportId = (int) ($_POST['report_id'] ?? 0);
    $comment = trim($_POST['comment'] ?? '');

    if ($reportId < 1 || $comment === '') {
        $error = 'Enter a comment before submitting.';
    } else {
        $reportCheck = $pdo->prepare('SELECT id FROM weekly_reports WHERE id = :report_id');
        $reportCheck->execute(['report_id' => $reportId]);

        if (!$reportCheck->fetch()) {
            $error = 'The selected report could not be found.';
        } else {
            $commentStmt = $pdo->prepare(
                'INSERT INTO weekly_report_comments (report_id, user_id, comment)
                 VALUES (:report_id, :user_id, :comment)'
            );
            $commentStmt->execute([
                'report_id' => $reportId,
                'user_id' => $userId,
                'comment' => $comment,
            ]);
            $success = 'Comment added to the report.';
        }
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && !$isAdmin && isset($_POST['report_submit'])) {
    $summary = trim($_POST['summary'] ?? '');
    $blockers = trim($_POST['blockers'] ?? '');
    $nextWeek = trim($_POST['next_week'] ?? '');
    $weekStart = trim($_POST['week_start'] ?? date('Y-m-d'));

    if ($summary === '') {
        $error = 'Please share a summary of your work for the week.';
    } else {
        $stmt = $pdo->prepare(
            'INSERT INTO weekly_reports (user_id, week_start, summary, blockers, next_week, status)
             VALUES (:user_id, :week_start, :summary, :blockers, :next_week, :status)'
        );

        $stmt->execute([
            'user_id' => $userId,
            'week_start' => $weekStart,
            'summary' => $summary,
            'blockers' => $blockers !== '' ? $blockers : null,
            'next_week' => $nextWeek !== '' ? $nextWeek : null,
            'status' => 'submitted',
        ]);

        $success = 'Your weekly report has been submitted successfully.';
    }
}

$reports = $isAdmin
    ? $pdo->query(
        'SELECT wr.id, wr.user_id, wr.summary, wr.blockers, wr.next_week, wr.status, wr.week_start,
                p.full_name AS report_author
         FROM weekly_reports wr
         INNER JOIN users u ON u.id = wr.user_id
         LEFT JOIN profiles p ON p.user_id = wr.user_id
         WHERE u.role <> \'admin\'
         ORDER BY wr.week_start DESC, wr.created_at DESC'
    )
    : $pdo->prepare(
        'SELECT id, user_id, summary, blockers, next_week, status, week_start, NULL AS report_author
         FROM weekly_reports WHERE user_id = :user_id ORDER BY week_start DESC'
    );
if (!$isAdmin) {
    $reports->execute(['user_id' => $userId]);
}
$weeklyReports = $reports->fetchAll();
$reportComments = [];
$commentQuery = $pdo->prepare(
    'SELECT c.comment, c.created_at, p.full_name AS commenter
     FROM weekly_report_comments c
     LEFT JOIN profiles p ON p.user_id = c.user_id
     WHERE c.report_id = :report_id
     ORDER BY c.created_at ASC'
);
foreach ($weeklyReports as $report) {
    $commentQuery->execute(['report_id' => (int) $report['id']]);
    $reportComments[(int) $report['id']] = $commentQuery->fetchAll();
}

include __DIR__ . '/includes/header.php';
?>
<?php if (!$isAdmin): ?>
<section class="form-panel" style="margin-bottom: 24px;">
    <h3>Submit Weekly Report</h3>

    <?php if ($error !== ''): ?>
        <div class="alert"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if ($success !== ''): ?>
        <div class="alert" style="background: rgba(16, 185, 129, 0.08); color: #047857; border-color: rgba(16, 185, 129, 0.2);">
            <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>

    <form method="post" action="weekly-report.php">
        <div class="form-grid">
            <div class="field">
                <label for="week_start">Week starting</label>
                <input id="week_start" name="week_start" type="date" value="<?= htmlspecialchars(date('Y-m-d')) ?>" required>
            </div>

            <div class="field" style="grid-column: 1 / -1;">
                <label for="summary">What I did this week</label>
                <textarea id="summary" name="summary" required placeholder="Describe the tasks, work, and progress you completed this week."></textarea>
            </div>

            <div class="field" style="grid-column: 1 / -1;">
                <label for="blockers">Challenges or blockers</label>
                <textarea id="blockers" name="blockers" placeholder="Share anything that slowed progress or needs support."></textarea>
            </div>

            <div class="field" style="grid-column: 1 / -1;">
                <label for="next_week">What I plan to do next week</label>
                <textarea id="next_week" name="next_week" placeholder="List the tasks and goals for the next week."></textarea>
            </div>
        </div>

        <div class="action-row" style="margin-top: 20px;">
            <button type="submit" name="report_submit" value="1" class="btn btn-primary">Submit Report</button>
        </div>
    </form>
</section>
<?php endif; ?>

<section class="list-panel">
    <h3><?= $isAdmin ? 'Intern Weekly Reports' : 'My Weekly Reports' ?></h3>
    <?php if ($isAdmin && $error !== ''): ?>
        <div class="alert"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if ($isAdmin && $success !== ''): ?>
        <div class="alert" style="background: rgba(16, 185, 129, 0.08); color: #047857; border-color: rgba(16, 185, 129, 0.2);">
            <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>
    <table class="table">
        <thead>
            <tr>
                <?php if ($isAdmin): ?><th>User</th><?php endif; ?>
                <th>Week</th>
                <th>Summary</th>
                <th>Blockers</th>
                <th>Next Week</th>
                <th>Status</th>
                <th>Comments</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($weeklyReports as $report): ?>
                <tr>
                    <?php if ($isAdmin): ?><td><?= htmlspecialchars($report['report_author'] ?? 'Unknown') ?></td><?php endif; ?>
                    <td><?= htmlspecialchars($report['week_start']) ?></td>
                    <td><?= htmlspecialchars($report['summary']) ?></td>
                    <td><?= htmlspecialchars($report['blockers'] ?? 'None') ?></td>
                    <td><?= htmlspecialchars($report['next_week'] ?? 'Not provided') ?></td>
                    <td>
                        <span class="status-pill <?= $report['status'] === 'approved' ? 'status-done' : 'status-progress' ?>">
                            <?= htmlspecialchars($report['status']) ?>
                        </span>
                    </td>
                    <td>
                        <?php foreach ($reportComments[(int) $report['id']] as $comment): ?>
                            <p><strong><?= htmlspecialchars($comment['commenter'] ?? 'WAVES Admin') ?></strong> <span class="muted"><?= htmlspecialchars($comment['created_at']) ?></span><br><?= nl2br(htmlspecialchars($comment['comment'])) ?></p>
                        <?php endforeach; ?>
                        <?php if ($isAdmin): ?>
                            <form method="post" action="weekly-report.php">
                                <input type="hidden" name="report_id" value="<?= (int) $report['id'] ?>">
                                <div class="field">
                                    <label for="comment-<?= (int) $report['id'] ?>">Add comment</label>
                                    <textarea id="comment-<?= (int) $report['id'] ?>" name="comment" required></textarea>
                                </div>
                                <button type="submit" name="comment_submit" value="1" class="btn btn-secondary" style="margin-top: 8px;">Comment</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
