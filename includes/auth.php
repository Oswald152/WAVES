<?php
require_once __DIR__ . '/db.php';

function currentUser(): ?array
{
    return $_SESSION['user'] ?? null;
}

function requireLogin(): void
{
    if (!currentUser()) {
        redirect('login.php');
    }
}

function isAdmin(): bool
{
    $user = currentUser();
    return $user !== null && ($user['role'] ?? '') === 'admin';
}

function requireAdmin(string $redirectPath = 'dashboard.php'): void
{
    requireLogin();

    if (!isAdmin()) {
        redirect($redirectPath);
    }
}

function loginUser(string $email, string $password, ?string $requiredRole = null): ?array
{
    $pdo = getDbConnection();

    $stmt = $pdo->prepare(
        'SELECT u.id, u.email, u.password_hash, u.role, u.organization_id, p.full_name
         FROM users u
         LEFT JOIN profiles p ON p.user_id = u.id
         WHERE u.email = :email'
    );
    $stmt->execute(['email' => trim($email)]);
    $user = $stmt->fetch();

    if (!$user || !isset($user['id'])) {
        return null;
    }

    if (!password_verify($password, $user['password_hash'] ?? '')) {
        return null;
    }

    if ($requiredRole === 'non-admin' && ($user['role'] ?? '') === 'admin') {
        return null;
    }

    if ($requiredRole !== null && $requiredRole !== 'non-admin' && ($user['role'] ?? '') !== $requiredRole) {
        return null;
    }

    $_SESSION['user'] = [
        'id' => (int) $user['id'],
        'email' => $user['email'],
        'role' => $user['role'],
        'organization_id' => (int) $user['organization_id'],
        'full_name' => $user['full_name'] ?: 'Oswald',
    ];

    return $_SESSION['user'];
}

function registerUser(string $fullName, string $email, string $password, string $role = 'intern', int $organizationId = 1, bool $signIn = true): ?array
{
    $pdo = getDbConnection();
    $email = trim($email);
    $fullName = trim($fullName);

    if ($email === '' || $fullName === '' || strlen($password) < 6) {
        return null;
    }

    $check = $pdo->prepare('SELECT id FROM users WHERE email = :email');
    $check->execute(['email' => $email]);

    if ($check->fetch()) {
        return null;
    }

    $stmt = $pdo->prepare(
        'INSERT INTO users (organization_id, email, password_hash, role) VALUES (:organization_id, :email, :password_hash, :role)'
    );
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    $stmt->execute([
        'organization_id' => $organizationId,
        'email' => $email,
        'password_hash' => $passwordHash,
        'role' => $role,
    ]);

    $userId = (int) $pdo->lastInsertId();

    $profileStmt = $pdo->prepare(
        'INSERT INTO profiles (user_id, full_name, team_name, department, job_title, mentor_id, start_date)
         VALUES (:user_id, :full_name, :team_name, :department, :job_title, :mentor_id, :start_date)'
    );
    $profileStmt->execute([
        'user_id' => $userId,
        'full_name' => $fullName,
        'team_name' => 'General',
        'department' => 'Operations',
        'job_title' => ucfirst($role),
        'mentor_id' => null,
        'start_date' => date('Y-m-d'),
    ]);

    if ($signIn) {
        return loginUser($email, $password);
    }

    return [
        'id' => $userId,
        'email' => $email,
        'role' => $role,
        'organization_id' => $organizationId,
        'full_name' => $fullName,
    ];
}

function logoutUser(): void
{
    session_unset();
    session_destroy();
    redirect('login.php');
}
