<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

requireLogin();

$pdo = getDbConnection();
$userId = currentUser()['id'];

$items = $pdo->prepare(
    'SELECT item_name, status, due_date FROM onboarding_checklists WHERE user_id = :user_id ORDER BY due_date ASC'
);
$items->execute(['user_id' => $userId]);
$checklist = $items->fetchAll();

include __DIR__ . '/includes/header.php';
?>
<section class="list-panel">
    <h3>Onboarding Checklist</h3>
    <table class="table">
        <thead>
            <tr>
                <th>Item</th>
                <th>Due Date</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($checklist as $item): ?>
                <tr>
                    <td><?= htmlspecialchars($item['item_name']) ?></td>
                    <td><?= htmlspecialchars($item['due_date'] ?? 'No date') ?></td>
                    <td>
                        <span class="status-pill <?= $item['status'] === 'completed' ? 'status-done' : 'status-open' ?>">
                            <?= htmlspecialchars($item['status']) ?>
                        </span>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
