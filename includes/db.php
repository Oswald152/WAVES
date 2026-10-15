<?php
require_once __DIR__ . '/config.php';

function runSqlScript(PDO $pdo, string $sql): void
{
    $statements = preg_split('/;\s*\R/', $sql);

    foreach ($statements as $statement) {
        $trimmed = trim($statement);
        if ($trimmed === '') {
            continue;
        }

        $pdo->exec($trimmed . ';');
    }
}

function initializeDatabase(PDO $pdo): void
{
    $tableCheck = $pdo->query("SHOW TABLES LIKE 'users'");
    if ($tableCheck && $tableCheck->rowCount() > 0) {
        $adminCheck = $pdo->prepare('SELECT id FROM users WHERE email = :email');
        $adminCheck->execute(['email' => 'oankumanyi@gmail.com']);

        if (!$adminCheck->fetch()) {
            $organizationId = (int) $pdo->query('SELECT id FROM organizations ORDER BY id LIMIT 1')->fetchColumn();
            $pdo->prepare(
                'INSERT INTO users (organization_id, email, password_hash, role) VALUES (:organization_id, :email, :password_hash, :role)'
            )->execute([
                'organization_id' => $organizationId ?: 1,
                'email' => 'oankumanyi@gmail.com',
                'password_hash' => '$2y$10$H.67omsZ/KV3/uGxdR3ct.YI59T/KBxGlrGoZL9Ua5l48B/PMiVeW',
                'role' => 'admin',
            ]);

            $userId = (int) $pdo->lastInsertId();
            $pdo->prepare(
                'INSERT INTO profiles (user_id, full_name, team_name, department, job_title, mentor_id, start_date)
                 VALUES (:user_id, :full_name, :team_name, :department, :job_title, :mentor_id, :start_date)'
            )->execute([
                'user_id' => $userId,
                'full_name' => 'Oswald',
                'team_name' => 'Product & Ops',
                'department' => 'Operations',
                'job_title' => 'Administrator',
                'mentor_id' => null,
                'start_date' => date('Y-m-d'),
            ]);
        }

        return;
    }

    $schemaPath = __DIR__ . '/../database/waves_schema.sql';
    if (!file_exists($schemaPath)) {
        return;
    }

    $sql = file_get_contents($schemaPath);
    if ($sql === false) {
        return;
    }

    $sql = str_replace('CREATE DATABASE IF NOT EXISTS waves;', '', $sql);
    $sql = str_replace('USE waves;', '', $sql);

    runSqlScript($pdo, $sql);
}

function ensureWorkspaceTables(PDO $pdo): void
{
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS complaints (
            id INT AUTO_INCREMENT PRIMARY KEY,
            worker_id INT NOT NULL,
            message TEXT NOT NULL,
            admin_feedback TEXT DEFAULT NULL,
            seen_at TIMESTAMP NULL DEFAULT NULL,
            replied_at TIMESTAMP NULL DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (worker_id) REFERENCES users(id) ON DELETE CASCADE
        )'
    );
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS task_reads (
            task_id INT NOT NULL,
            user_id INT NOT NULL,
            read_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (task_id, user_id),
            FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )'
    );
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS announcement_reads (
            announcement_id INT NOT NULL,
            user_id INT NOT NULL,
            read_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (announcement_id, user_id),
            FOREIGN KEY (announcement_id) REFERENCES announcements(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )'
    );
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS weekly_report_comments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            report_id INT NOT NULL,
            user_id INT NOT NULL,
            comment TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (report_id) REFERENCES weekly_reports(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )'
    );

    $profileColumns = $pdo->query('SHOW COLUMNS FROM profiles')->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('age', $profileColumns, true)) {
        $pdo->exec('ALTER TABLE profiles ADD COLUMN age TINYINT UNSIGNED DEFAULT NULL');
    }
    if (!in_array('profile_photo', $profileColumns, true)) {
        $pdo->exec('ALTER TABLE profiles ADD COLUMN profile_photo VARCHAR(255) DEFAULT NULL');
    }

    $learningColumns = $pdo->query('SHOW COLUMNS FROM learning_items')->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('notes', $learningColumns, true)) {
        $pdo->exec('ALTER TABLE learning_items ADD COLUMN notes TEXT DEFAULT NULL');
    }
}

function getDbConnection(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $baseDsn = 'mysql:host=' . DB_HOST . ';charset=utf8mb4';
    $databaseDsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';

    try {
        $pdo = new PDO($databaseDsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    } catch (PDOException $e) {
        try {
            $setupPdo = new PDO($baseDsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
            $setupPdo->exec('CREATE DATABASE IF NOT EXISTS `' . DB_NAME . '`');
            $setupPdo = null;

            $pdo = new PDO($databaseDsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $setupException) {
            die('Database connection failed. Please ensure MySQL is running and the credentials are correct.');
        }
    }

    initializeDatabase($pdo);
    ensureWorkspaceTables($pdo);

    return $pdo;
}
