<?php
$currentPage = basename($_SERVER['PHP_SELF']);
$user = currentUser();
$isCurrentAdmin = ($user['role'] ?? '') === 'admin';
$navItems = $isCurrentAdmin
    ? [['dashboard.php', 'Dashboard'], ['users.php', 'Users'], ['new-task.php', 'New Task'], ['new-announcement.php', 'New Announcement'], ['profile.php', 'My Profile']]
    : [['work.php', 'My Work'], ['profile.php', 'My Profile']];
$navItems = array_merge($navItems, [
    ['people.php', 'People'],
    ['projects.php', 'Projects'],
    ['onboarding.php', 'Onboarding'],
    ['weekly-report.php', 'Weekly Reports'],
    ['announcements.php', 'Announcements'],
    ['feedback.php', 'Complaints'],
]);

if (!$isCurrentAdmin) {
    array_splice($navItems, 3, 0, [['learning.php', 'Learning']]);
}

$newTaskCount = 0;
$newAnnouncementCount = 0;
if (($user['role'] ?? '') === 'intern' || !$isCurrentAdmin) {
    $pdo = getDbConnection();
}

if (($user['role'] ?? '') === 'intern') {
    $taskCount = $pdo->prepare(
        'SELECT COUNT(*)
         FROM tasks t
         LEFT JOIN task_reads tr ON tr.task_id = t.id AND tr.user_id = :user_id
         WHERE t.assignee_id = :assignee_id AND tr.task_id IS NULL'
    );
    $taskCount->execute([
        'user_id' => (int) $user['id'],
        'assignee_id' => (int) $user['id'],
    ]);
    $newTaskCount = (int) $taskCount->fetchColumn();
}

if (!$isCurrentAdmin) {
    $announcementCount = $pdo->prepare(
        'SELECT COUNT(*)
         FROM announcements a
         LEFT JOIN announcement_reads ar ON ar.announcement_id = a.id AND ar.user_id = :user_id
         WHERE a.organization_id = :organization_id
           AND a.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
           AND ar.announcement_id IS NULL'
    );
    $announcementCount->execute([
        'user_id' => (int) $user['id'],
        'organization_id' => (int) $user['organization_id'],
    ]);
    $newAnnouncementCount = (int) $announcementCount->fetchColumn();
}
?>
<aside class="sidebar">
    <div class="brand">
        <div class="brand-mark">W</div>
        <h1>WAVES</h1>
    </div>

    <nav class="nav">
        <?php foreach ($navItems as $item): ?>
            <?php
                $href = $item[0];
                $label = $item[1];
                if ($href === 'work.php' && $newTaskCount > 0) {
                    $label .= ' <span class="nav-badge">' . $newTaskCount . ' new</span>';
                } elseif ($href === 'announcements.php' && $newAnnouncementCount > 0) {
                    $label .= ' <span class="nav-badge">' . $newAnnouncementCount . ' new</span>';
                }
                $active = ($currentPage === $href) ? 'active' : '';
                $link = '<a class="' . $active . '" href="' . $href . '">' . $label . '</a>';
                echo $link;
            ?>
        <?php endforeach; ?>
    </nav>

    <div class="sidebar-logout">
        <a href="logout.php">Log out</a>
    </div>
</aside>
