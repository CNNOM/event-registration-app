<?php
session_start();

// Настройки
define('SITE_NAME', 'Event Management System');
define('ADMIN_EMAIL', 'admin@events.com');

// Роли пользователей
define('ROLE_ADMIN', 'admin');
define('ROLE_ORGANIZER', 'organizer');
define('ROLE_PARTICIPANT', 'participant');

// Проверка авторизации
function checkAuth() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit();
    }
}

// Проверка роли
function checkRole($allowedRoles) {
    checkAuth();
    if (!in_array($_SESSION['role'], $allowedRoles)) {
        header('Location: index.php');
        exit();
    }
}

// Получить текущего пользователя
function getCurrentUser() {
    return [
        'id' => $_SESSION['user_id'] ?? null,
        'email' => $_SESSION['email'] ?? null,
        'role' => $_SESSION['role'] ?? null,
        'name' => $_SESSION['name'] ?? null
    ];
}
?>