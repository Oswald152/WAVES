<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

requireLogin();

$pdo = getDbConnection();
$user = currentUser();
$isCurrentAdmin = isAdmin();
$csrfSessionKey = 'complaint_csrf_token';

if (empty($_SESSION[$csrfSessionKey])) {
    $_SESSION[$csrfSessionKey] = bin2hex(random_bytes(32));
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';

    if (!is_string($csrfToken) || !hash_equals($_SESSION[$csrfSessionKey], $csrfToken)) {
        $error = 'Your session has expired. Please try again.';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'submit' && !$isCurrentAdmin) {
            $message = trim($_POST['message'] ?? '');

            if ($message === '') {
                $error = 'Enter your complaint before submitting.';
            } else {
                $statement = $pdo->prepare(
                    'INSERT INTO complaints (worker_id, message) VALUES (:worker_id, :message)'
                );
                $statement->execute([
                    'worker_id' => (int) $user['id'],
                    'message' => $message,
                ]);
                redirect('feedback.php?submitted=1');
            }
        } elseif ($action === 'reply' && $isCurrentAdmin) {
            $complaintId = filter_input(INPUT_POST, 'complaint_id', FILTER_VALIDATE_INT);
            $reply = trim($_POST['admin_feedback'] ?? '');

            if (!$complaintId || $reply === '') {
                $error = 'Enter a response before submitting.';
            } else {
                $statement = $pdo->prepare(
                    'UPDATE complaints
                     SET admin_feedback = :admin_feedback, replied_at = CURRENT_TIMESTAMP
                     WHERE id = :id'
                );
                $statement->execute([
                    'admin_feedback' => $reply,
                    'id' => $complaintId,
                ]);

                if ($statement->rowCount() === 0) {
                    $exists = $pdo->prepare('SELECT id FROM complaints WHERE id = :id');
                    $exists->execute(['id' => $complaintId]);
                    if (!$exists->fetch()) {
                        $error = 'That complaint could not be found.';
                    }
                }

                if ($error === '') {
                    redirect('feedback.php?id=' . $complaintId . '&replied=1');
                }
            }
        } else {
            $error = 'That action is not available.';
        }
    }
}

$selectedComplaint = null;
$complaints = [];

if ($isCurrentAdmin && isset($_GET['id'])) {
    $complaintId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

    if ($complaintId) {
        $markSeen = $pdo->prepare(
            'UPDATE complaints SET seen_at = CURRENT_TIMESTAMP WHERE id = :id AND seen_at IS NULL'
        );
        $markSeen->execute(['id' => $complaintId]);

        $statement = $pdo->prepare(
            'SELECT c.id, c.worker_id, c.message, c.admin_feedback, c.seen_at, c.replied_at, c.created_at,
                    p.full_name AS worker_name, u.email AS worker_email
             FROM complaints c
             JOIN users u ON u.id = c.worker_id
             LEFT JOIN profiles p ON p.user_id = c.worker_id
             WHERE c.id = :id'
        );
        $statement->execute(['id' => $complaintId]);
        $selectedComplaint = $statement->fetch();
    }

    if (!$selectedComplaint) {
        $error = $error ?: 'That complaint could not be found.';
    }
}

if ($isCurrentAdmin) {
    $statement = $pdo->query(
        'SELECT c.id, c.message, c.seen_at, c.replied_at, c.created_at,
                p.full_name AS worker_name, u.email AS worker_email
         FROM complaints c
         JOIN users u ON u.id = c.worker_id
         LEFT JOIN profiles p ON p.user_id = c.worker_id
         ORDER BY c.created_at DESC'
    );
    $complaints = $statement->fetchAll();
} else {
    $statement = $pdo->prepare(
        'SELECT id, message, admin_feedback, seen_at, replied_at, created_at
         FROM complaints
         WHERE worker_id = :worker_id
         ORDER BY created_at DESC'
    );
    $statement->execute(['worker_id' => (int) $user['id']]);
    $complaints = $statement->fetchAll();
}

include __DIR__ . '/includes/header.php';
?>
<?php if ($isCurrentAdmin): ?>
    <?php if ($selectedComplaint): ?>
        <section class="form-panel">
            <p><a href="feedback.php" class="btn btn-secondary">Back to complaints</a></p>
            <h3>Complaint from <?= htmlspecialchars($selectedComplaint['worker_name'] ?: $selectedComplaint['worker_email']) ?></h3>
            <p class="muted"><?= htmlspecialchars($selectedComplaint['worker_email']) ?> · <?= htmlspecialchars($selectedComplaint['created_at']) ?></p>
            <div class="quick-item">
                <p><?= nl2br(htmlspecialchars($selectedComplaint['message'])) ?></p>
            </div>

            <?php if ($selectedComplaint['admin_feedback'] !== null): ?>
                <h4>Your response</h4>
                <div class="quick-item">
                    <p><?= nl2br(htmlspecialchars($selectedComplaint['admin_feedback'])) ?></p>
                    <small class="muted">Replied <?= htmlspecialchars($selectedComplaint['replied_at']) ?></small>
                </div>
            <?php endif; ?>

            <?php if ($error !== ''): ?>
                <div class="alert"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="post" action="feedback.php?id=<?= (int) $selectedComplaint['id'] ?>">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION[$csrfSessionKey]) ?>">
                <input type="hidden" name="action" value="reply">
                <input type="hidden" name="complaint_id" value="<?= (int) $selectedComplaint['id'] ?>">
                <div class="field">
                    <label for="admin_feedback">Your feedback to the worker</label>
                    <textarea id="admin_feedback" name="admin_feedback" rows="5" required><?= htmlspecialchars($selectedComplaint['admin_feedback'] ?? '') ?></textarea>
                </div>
                <button type="submit" class="btn btn-primary" style="margin-top: 16px;">Send feedback</button>
            </form>
        </section>
    <?php else: ?>
        <section class="list-panel">
            <h3>Worker complaints</h3>
            <?php if ($error !== ''): ?>
                <div class="alert"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <?php if ($complaints): ?>
                <div class="quick-list">
                    <?php foreach ($complaints as $complaint): ?>
                        <article class="quick-item">
                            <div class="complaint-heading">
                                <strong><?= htmlspecialchars($complaint['worker_name'] ?: $complaint['worker_email']) ?></strong>
                                <span class="status-pill <?= $complaint['seen_at'] === null ? 'status-open' : 'status-progress' ?>">
                                    <?= $complaint['seen_at'] === null ? 'Not seen' : 'Seen' ?>
                                </span>
                            </div>
                            <small class="muted"><?= htmlspecialchars($complaint['created_at']) ?> · <?= $complaint['replied_at'] === null ? 'Awaiting feedback' : 'Feedback sent' ?></small>
                            <p><?= htmlspecialchars(mb_strimwidth($complaint['message'], 0, 220, '…', 'UTF-8')) ?></p>
                            <a class="btn btn-primary" href="feedback.php?id=<?= (int) $complaint['id'] ?>">Open complaint</a>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="muted">There are no worker complaints yet.</p>
            <?php endif; ?>
        </section>
    <?php endif; ?>
<?php else: ?>
    <section class="form-panel">
        <h3>Send a complaint to the admin</h3>
        <p class="muted">Your complaint is private and can only be viewed by you and the admin.</p>
        <?php if (isset($_GET['submitted'])): ?>
            <div class="notice">Your complaint has been sent to the admin.</div>
        <?php endif; ?>
        <?php if ($error !== ''): ?>
            <div class="alert"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="post" action="feedback.php">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION[$csrfSessionKey]) ?>">
            <input type="hidden" name="action" value="submit">
            <div class="field">
                <label for="message">What would you like the admin to know?</label>
                <textarea id="message" name="message" rows="5" required></textarea>
            </div>
            <button type="submit" class="btn btn-primary" style="margin-top: 16px;">Send complaint</button>
        </form>
    </section>

    <section class="list-panel" style="margin-top: 24px;">
        <h3>Your complaints</h3>
        <?php if ($complaints): ?>
            <div class="quick-list">
                <?php foreach ($complaints as $complaint): ?>
                    <article class="quick-item">
                        <div class="complaint-heading">
                            <strong><?= htmlspecialchars($complaint['created_at']) ?></strong>
                            <span class="status-pill <?= $complaint['seen_at'] === null ? 'status-open' : 'status-progress' ?>">
                                <?= $complaint['seen_at'] === null ? 'Not seen yet' : 'Seen by admin' ?>
                            </span>
                        </div>
                        <p><?= nl2br(htmlspecialchars($complaint['message'])) ?></p>
                        <?php if ($complaint['admin_feedback'] !== null): ?>
                            <div class="complaint-response">
                                <strong>Admin feedback</strong>
                                <p><?= nl2br(htmlspecialchars($complaint['admin_feedback'])) ?></p>
                            </div>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="muted">You haven't sent any complaints yet.</p>
        <?php endif; ?>
    </section>
<?php endif; ?>
<?php include __DIR__ . '/includes/footer.php'; ?>
