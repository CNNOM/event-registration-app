<?php
require_once 'db.php';
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    // Находим пользователя
    $stmt = $db->prepare("SELECT * FROM users WHERE email = :email");
    $stmt->bindValue(':email', $email);
    $result = $stmt->execute();
    $user = $result->fetchArray(SQLITE3_ASSOC);
    
    if ($user && password_verify($password, $user['password'])) {
        // Успешный вход
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['name'] = $user['name'];
        $_SESSION['role'] = $user['role'];
        
        // Перенаправляем в зависимости от роли
        switch ($user['role']) {
            case ROLE_ADMIN:
                header('Location: admin.php');
                break;
            case ROLE_ORGANIZER:
                header('Location: organizer.php');
                break;
            default:
                header('Location: participant.php');
        }
        exit();
    } else {
        $error = "Неверный email или пароль";
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Вход</title>
    <style>
        body { font-family: Arial; max-width: 400px; margin: 50px auto; padding: 20px; }
        .form-group { margin-bottom: 15px; }
        input { width: 100%; padding: 8px; }
        button { background: #007bff; color: white; padding: 10px; border: none; width: 100%; }
        .error { color: red; }
    </style>
</head>
<body>
    <h2>Вход в систему</h2>
    
    <?php if (isset($error)): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    
    <form method="POST">
        <div class="form-group">
            <label>Email:</label>
            <input type="email" name="email" required>
        </div>
        
        <div class="form-group">
            <label>Пароль:</label>
            <input type="password" name="password" required>
        </div>
        
        <button type="submit">Войти</button>
    </form>
    
    <p>Нет аккаунта? <a href="register.php">Зарегистрироваться</a></p>
</body>
</html>