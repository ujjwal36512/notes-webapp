<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Clear remember-me cookie & DB token, then destroy session
clearRememberMeToken($pdo);
logoutUser();

session_start(); // Restart session for flash message
setFlashMessage('success', 'You have been logged out successfully.');
redirect('login.php');
