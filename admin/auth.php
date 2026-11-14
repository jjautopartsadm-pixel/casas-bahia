<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/session_handler.php';

startSecureSession();

function requireLogin() {
    if (empty($_SESSION['admin_logged_in'])) {
        header('Location: login.php');
        exit;
    }
}

function isLoggedIn() {
    return !empty($_SESSION['admin_logged_in']);
}
