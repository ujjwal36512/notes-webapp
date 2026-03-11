<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';

// Auto-login from remember-me cookie (runs on every page load)
loginUserWithToken($pdo);

// Handle theme toggle
if (isset($_GET['toggle_theme'])) {
    $currentTheme = isset($_COOKIE['theme']) ? $_COOKIE['theme'] : 'light';
    $newTheme = $currentTheme === 'dark' ? 'light' : 'dark';
    setcookie('theme', $newTheme, time() + (365 * 24 * 60 * 60), '/'); // 1 year expiry

    // Redirect back to the same page without the toggle_theme parameter
    $redirectUrl = strtok($_SERVER['REQUEST_URI'], '?');
    $queryParams = $_GET;
    unset($queryParams['toggle_theme']);
    if (!empty($queryParams)) {
        $redirectUrl .= '?' . http_build_query($queryParams);
    }
    header('Location: ' . $redirectUrl);
    exit;
}

// Get current theme from cookie
$theme = isset($_COOKIE['theme']) ? $_COOKIE['theme'] : 'light';

// Check if flash message exists for meta refresh
$hasFlash = isset($_SESSION['flash']);
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?= htmlspecialchars($theme) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php if ($hasFlash): ?>
    <meta http-equiv="refresh" content="5">
    <?php endif; ?>
    <title><?= isset($pageTitle) ? sanitize($pageTitle) . ' - ' : '' ?>Simple Notes App</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <div class="logo-group">
                <a href="index.php" class="logo">Simple Notes</a>
                <a href="?toggle_theme=1" class="theme-toggle" title="Toggle theme">
                    <span class="icon-sun">&#9728;</span>
                    <span class="icon-moon">&#9790;</span>
                </a>
            </div>
            <!-- $_SERVER['PHP_SELF'] - Returns the full path of the current script (e.g., /notesapp/login.php) -->
            <?php $currentPage = basename($_SERVER['PHP_SELF']); ?>
            <?php if ($currentPage !== 'login.php' && $currentPage !== 'register.php'): ?>
            <div class="nav-links">
                <a href="index.php" class="<?= $currentPage === 'index.php' ? 'active' : '' ?>">All Notes</a>
                <a href="note.php?action=create" class="<?= $currentPage === 'note.php' ? 'active' : '' ?>">+ New Note</a>
                <a href="archive.php" class="<?= $currentPage === 'archive.php' ? 'active' : '' ?>">Archive</a>
            </div>
            <form action="search.php" method="GET" class="search-form">
                <input type="text" name="q" placeholder="Search notes..." value="<?= isset($_GET['q']) ? sanitize($_GET['q']) : '' ?>">
                <button type="submit">Search</button>
            </form>
            <?php endif; ?>
            <div class="auth-links">
                <?php if (isLoggedIn()): ?>
                    <div class="user-menu">
                        <?php 
                        $currentUser = getCurrentUser();
                        $displayName = sanitize($currentUser['username']);
                        $avatarUrl = $currentUser['profile_image'] ? sanitize($currentUser['profile_image']) : null;
                        ?>
                        <a href="profile.php" class="profile-link">
                            <?php if ($avatarUrl): ?>
                                <img src="<?= $avatarUrl ?>" alt="<?= $displayName ?>" class="nav-avatar">
                            <?php else: ?>
                                <span class="nav-avatar-placeholder"><?= strtoupper(substr($displayName, 0, 1)) ?></span>
                            <?php endif; ?>
                            <span class="username"><?= $displayName ?></span>
                        </a>
                        <a href="logout.php" class="btn btn-sm btn-logout">Logout</a>
                    </div>
                <?php else: ?>
                    <a href="login.php" class="btn btn-sm">Login</a>
                    <a href="register.php" class="btn btn-sm btn-register">Register</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <main class="container">
        <?php
        $flash = getFlashMessage();
        if ($flash):
        ?>
        <div class="alert alert-<?= $flash['type'] ?>">
            <?= sanitize($flash['message']) ?>
        </div>
        <?php endif; ?>
