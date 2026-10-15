<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

requireLogin();

$pdo = getDbConnection();
$user = currentUser();
$userId = (int) $user['id'];
$error = '';
$success = '';

$profileQuery = $pdo->prepare(
    'SELECT u.email, p.full_name, p.job_title, p.department, p.team_name, p.age, p.profile_photo
     FROM users u
     LEFT JOIN profiles p ON p.user_id = u.id
     WHERE u.id = :user_id'
);
$profileQuery->execute(['user_id' => $userId]);
$profile = $profileQuery->fetch() ?: [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $jobTitle = trim($_POST['job_title'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $teamName = trim($_POST['team_name'] ?? '');
    $ageValue = trim($_POST['age'] ?? '');
    $photoPath = $profile['profile_photo'] ?? null;

    if ($fullName === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Enter your name and a valid email address.';
    } elseif ($ageValue !== '' && (!ctype_digit($ageValue) || (int) $ageValue < 1 || (int) $ageValue > 120)) {
        $error = 'Age must be a number between 1 and 120.';
    } elseif (strlen($jobTitle) > 120 || strlen($department) > 120 || strlen($teamName) > 120) {
        $error = 'Work, department, and team details must be 120 characters or fewer.';
    } else {
        $emailCheck = $pdo->prepare('SELECT id FROM users WHERE email = :email AND id <> :user_id');
        $emailCheck->execute(['email' => $email, 'user_id' => $userId]);

        if ($emailCheck->fetch()) {
            $error = 'That email address is already in use.';
        }
    }

    if ($error === '' && isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] !== UPLOAD_ERR_NO_FILE) {
        $upload = $_FILES['profile_photo'];
        if ($upload['error'] !== UPLOAD_ERR_OK || $upload['size'] > 2 * 1024 * 1024) {
            $error = 'Choose an image smaller than 2 MB.';
        } else {
            $fileInfo = new finfo(FILEINFO_MIME_TYPE);
            $mimeType = $fileInfo->file($upload['tmp_name']);
            $allowedTypes = [
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/webp' => 'webp',
            ];

            if (!isset($allowedTypes[$mimeType]) || !is_uploaded_file($upload['tmp_name'])) {
                $error = 'Profile pictures must be JPG, PNG, or WebP images.';
            } else {
                $directory = __DIR__ . '/assets/uploads/profile-pictures';
                if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
                    $error = 'The profile picture could not be saved.';
                } else {
                    $fileName = bin2hex(random_bytes(16)) . '.' . $allowedTypes[$mimeType];
                    if (!move_uploaded_file($upload['tmp_name'], $directory . '/' . $fileName)) {
                        $error = 'The profile picture could not be saved.';
                    } else {
                        $photoPath = 'assets/uploads/profile-pictures/' . $fileName;
                    }
                }
            }
        }
    }

    if ($error === '') {
        $pdo->beginTransaction();
        $userUpdate = $pdo->prepare('UPDATE users SET email = :email WHERE id = :user_id');
        $userUpdate->execute(['email' => $email, 'user_id' => $userId]);
        $profileUpdate = $pdo->prepare(
            'UPDATE profiles
             SET full_name = :full_name, job_title = :job_title, department = :department,
                 team_name = :team_name, age = :age, profile_photo = :profile_photo
             WHERE user_id = :user_id'
        );
        $profileUpdate->execute([
            'full_name' => $fullName,
            'job_title' => $jobTitle !== '' ? $jobTitle : null,
            'department' => $department !== '' ? $department : null,
            'team_name' => $teamName !== '' ? $teamName : null,
            'age' => $ageValue !== '' ? (int) $ageValue : null,
            'profile_photo' => $photoPath,
            'user_id' => $userId,
        ]);
        $pdo->commit();

        $_SESSION['user']['email'] = $email;
        $_SESSION['user']['full_name'] = $fullName;
        $success = 'Your profile has been updated.';
        $profileQuery->execute(['user_id' => $userId]);
        $profile = $profileQuery->fetch() ?: [];
    }
}

include __DIR__ . '/includes/header.php';
?>
<section class="form-panel">
    <h3>My Profile</h3>

    <?php if ($error !== ''): ?>
        <div class="alert"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if ($success !== ''): ?>
        <div class="alert" style="background: rgba(16, 185, 129, 0.08); color: #047857; border-color: rgba(16, 185, 129, 0.2);">
            <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($profile['profile_photo'])): ?>
        <img class="profile-photo" src="<?= htmlspecialchars($profile['profile_photo']) ?>" alt="Profile picture for <?= htmlspecialchars($profile['full_name'] ?? 'user') ?>">
    <?php endif; ?>

    <form method="post" action="profile.php" enctype="multipart/form-data">
        <div class="form-grid">
            <div class="field">
                <label for="full_name">Full name</label>
                <input id="full_name" name="full_name" type="text" maxlength="150" value="<?= htmlspecialchars($profile['full_name'] ?? '') ?>" required>
            </div>
            <div class="field">
                <label for="email">Email</label>
                <input id="email" name="email" type="email" maxlength="150" value="<?= htmlspecialchars($profile['email'] ?? '') ?>" required>
            </div>
            <div class="field">
                <label for="job_title">Work / job title</label>
                <input id="job_title" name="job_title" type="text" maxlength="120" value="<?= htmlspecialchars($profile['job_title'] ?? '') ?>">
            </div>
            <div class="field">
                <label for="age">Age</label>
                <input id="age" name="age" type="number" min="1" max="120" value="<?= htmlspecialchars((string) ($profile['age'] ?? '')) ?>">
            </div>
            <div class="field">
                <label for="department">Department</label>
                <input id="department" name="department" type="text" maxlength="120" value="<?= htmlspecialchars($profile['department'] ?? '') ?>">
            </div>
            <div class="field">
                <label for="team_name">Team</label>
                <input id="team_name" name="team_name" type="text" maxlength="120" value="<?= htmlspecialchars($profile['team_name'] ?? '') ?>">
            </div>
            <div class="field" style="grid-column: 1 / -1;">
                <label for="profile_photo">Profile picture (optional, JPG/PNG/WebP, max 2 MB)</label>
                <input id="profile_photo" name="profile_photo" type="file" accept="image/jpeg,image/png,image/webp">
            </div>
        </div>
        <div class="action-row" style="margin-top: 20px;">
            <button type="submit" class="btn btn-primary">Save Profile</button>
        </div>
    </form>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>