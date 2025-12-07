<?php
require_once 'db.php';
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $role = $_POST['role'] ?? ROLE_PARTICIPANT;

    // Проверка
    if (empty($name) || empty($email) || empty($password)) {
        $error = "Все поля обязательны";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Некорректный email";
    } else {
        // Проверяем существует ли пользователь
        $stmt = $db->prepare("SELECT id FROM users WHERE email = :email");
        $stmt->bindValue(':email', $email);
        $result = $stmt->execute();
        
        if ($result->fetchArray()) {
            $error = "Пользователь с таким email уже существует";
        } else {
            // Создаем пользователя
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("INSERT INTO users (email, password, name, role) VALUES (:email, :password, :name, :role)");
            $stmt->bindValue(':email', $email);
            $stmt->bindValue(':password', $hashedPassword);
            $stmt->bindValue(':name', $name);
            $stmt->bindValue(':role', $role);
            
            if ($stmt->execute()) {
                // Автоматический вход после регистрации
                $userId = $db->lastInsertRowID();
                $_SESSION['user_id'] = $userId;
                $_SESSION['email'] = $email;
                $_SESSION['name'] = $name;
                $_SESSION['role'] = $role;
                
                // Отправка email (заглушка)
                // mail($email, "Добро пожаловать", "Вы успешно зарегистрированы!");
                
                header('Location: index.php');
                exit();
            } else {
                $error = "Ошибка при регистрации";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Регистрация</title>
    <style>
        body { font-family: Arial; max-width: 400px; margin: 50px auto; padding: 20px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; }
        input, select { width: 100%; padding: 8px; }
        button { background: #28a745; color: white; padding: 10px; border: none; width: 100%; }
        .error { color: red; margin-bottom: 10px; }
    </style>
</head>
<body>
    <h2>Регистрация</h2>
    
    <?php if (isset($error)): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    
    <form method="POST">
        <div class="form-group">
            <label>ФИО:</label>
            <input type="text" name="name" required>
        </div>
        
        <div class="form-group">
            <label>Email:</label>
            <input type="email" name="email" required>
        </div>
        
        <div class="form-group">
            <label>Пароль:</label>
            <input type="password" name="password" required>
        </div>
        
        <div class="form-group">
            <label>Роль:</label>
            <select name="role">
                <option value="participant">Участник</option>
                <option value="organizer">Организатор</option>
            </select>
        </div>
        
        <button type="submit">Зарегистрироваться</button>
    </form>
    
    <p>Уже есть аккаунт? <a href="login.php">Войти</a></p>
</body>
</html>