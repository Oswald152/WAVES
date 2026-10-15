<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';

$currentUser = currentUser();
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= APP_NAME ?> | <?= ucfirst(str_replace(['.php', '-'], ['', ' '], $currentPage)) ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php if ($currentUser && $currentPage !== 'login.php'): ?>
        <div class="app-shell">
            <?php include __DIR__ . '/sidebar.php'; ?>
            <main class="main">
                <header class="topbar">
                    <div class="page-title">
                        <h2><?= ucfirst(str_replace(['.php', '-'], ['', ' '], $currentPage)) ?></h2>
                        <p>Welcome back, <?= htmlspecialchars($currentUser['full_name']) ?>.</p>
                    </div>
                    <div class="user-chip">
                        <?= htmlspecialchars($currentUser['full_name']) ?> · <?= htmlspecialchars($currentUser['role']) ?>
                    </div>
                </header>
    <?php else: ?>
        <div class="auth-wrap">
    <?php endif; ?>
