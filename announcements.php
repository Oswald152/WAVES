<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

requireLogin();

$pdo = getDbConnection();
$user = currentUser();
if (!isAdmin()) {
    $markRead = $pdo->prepare(
        'INSERT IGNORE INTO announcement_reads (announcement_id, user_id)
         SELECT id, :user_id
         FROM announcements
         WHERE organization_id = :organization_id
           AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)'
    );
    $markRead->execute([
        'user_id' => (int) $user['id'],
        'organization_id' => (int) $user['organization_id'],
    ]);
}

$stmt = $pdo->prepare(
    'SELECT a.title, a.content, a.created_at, p.full_name AS author
     FROM announcements a
     LEFT JOIN profiles p ON p.user_id = a.created_by
     WHERE a.organization_id = :organization_id
       AND a.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
     ORDER BY a.created_at DESC'
);
$stmt->execute(['organization_id' => (int) $user['organization_id']]);
$announcements = $stmt->fetchAll();

include __DIR__ . '/includes/header.php';
?>
<section class="list-panel">
    <h3>Announcements</h3>
    <div class="quick-list">
        <?php foreach ($announcements as $item): ?>
            <div class="quick-item">
                <strong><?= htmlspecialchars($item['title']) ?></strong>
                <div class="muted"><?= htmlspecialchars($item['author']) ?> · <?= htmlspecialchars($item['created_at']) ?></div>
                <p><?= htmlspecialchars($item['content']) ?></p>
            </div>
        <?php endforeach; ?>
    </div>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
