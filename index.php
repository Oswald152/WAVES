<?php
require_once __DIR__ . '/includes/config.php';

if (isset($_SESSION['user'])) {
    redirect(($_SESSION['user']['role'] ?? '') === 'admin' ? 'dashboard.php' : 'work.php');
}

redirect('login.php');
