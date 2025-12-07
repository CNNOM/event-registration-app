<?php
require_once 'db.php';
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    $stmt = $db->prepare("SELECT * FROM users WHERE email = :email");
    $stmt->bindValue(':email', $email);
    $result = $stmt->execute();
    $user = $result->fetchArray(SQLITE3_ASSOC);
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['name'] = $user['name'];
        $_SESSION['role'] = $user['role'];
        
        switch ($user['role']) {
            case ROLE_ADMIN: header('Location: admin.php'); break;
            case ROLE_ORGANIZER: header('Location: organizer.php'); break;
            default: header('Location: participant.php');
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
    <title>Вход в систему</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .login-container {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }
        
        .login-card {
            background: white;
            border-radius: var(--border-radius);
            padding: 40px;
            width: 100%;
            max-width: 450px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            animation: fadeIn 0.8s ease-out;
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .login-header h1 {
            color: var(--primary-color);
            font-size: 32px;
            margin-bottom: 10px;
        }
        
        .login-header p {
            color: var(--gray-color);
            font-size: 16px;
        }
        
        .social-login {
            display: flex;
            gap: 15px;
            margin: 30px 0;
        }
        
        .social-btn {
            flex: 1;
            padding: 12px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
        }
        
        .social-btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow);
        }
        
        .social-btn.google { color: #DB4437; }
        .social-btn.facebook { color: #4267B2; }
        .social-btn.github { color: #333; }
        
        .divider {
            text-align: center;
            margin: 30px 0;
            position: relative;
            color: var(--gray-color);
        }
        
        .divider::before {
            content: "";
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 1px;
            background: #e2e8f0;
        }
        
        .divider span {
            background: white;
            padding: 0 20px;
            position: relative;
        }
        
        .form-footer {
            text-align: center;
            margin-top: 30px;
            color: var(--gray-color);
        }
        
        .form-footer a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
        }
        
        .password-toggle {
            position: relative;
        }
        
        .toggle-password {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--gray-color);
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <h1><i class="fas fa-calendar-alt"></i> EventManager</h1>
                <p>Войдите в свою учетную запись</p>
            </div>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            
            <!-- Быстрый вход для демонстрации -->
            <!-- <div style="background: #f0f9ff; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #bae6fd;">
                <p style="margin: 0; font-size: 14px; color: #0369a1;">
                    <i class="fas fa-info-circle"></i> Демо-доступы:<br>
                    Админ: admin@events.com / admin123<br>
                    Организатор: organizer@example.com / organizer123<br>
                    Участник: participant@example.com / participant123
                </p>
            </div> -->
            
            <form method="POST" id="loginForm">
                <div class="form-group">
                    <label class="form-label">Email</label>
                    <div class="input-with-icon">
                        <input type="email" name="email" class="form-control" 
                               placeholder="Введите ваш email" required
                               style="padding-left: 45px;">
                        <i class="fas fa-envelope" style="position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: var(--gray-color);"></i>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Пароль</label>
                    <div class="password-toggle">
                        <input type="password" name="password" id="password" 
                               class="form-control" placeholder="Введите ваш пароль" required
                               style="padding-left: 45px;">
                        <i class="fas fa-lock" style="position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: var(--gray-color);"></i>
                        <button type="button" class="toggle-password" onclick="togglePassword()">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                
                <!-- <div style="display: flex; justify-content: space-between; align-items: center; margin: 20px 0;">
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                        <input type="checkbox" name="remember" style="width: auto;">
                        <span style="color: var(--gray-color);">Запомнить меня</span>
                    </label>
                    <a href="#" style="color: var(--primary-color); text-decoration: none; font-size: 14px;">
                        Забыли пароль?
                    </a>
                </div> -->
                
                <button type="submit" class="btn" style="width: 100%;">
                    <i class="fas fa-sign-in-alt"></i> Войти в систему
                </button>
            </form>
            
            <div class="form-footer">
                <p>Нет учетной записи? <a href="register.php">Зарегистрироваться</a></p>
                <p style="margin-top: 10px; font-size: 14px;">
                    <a href="index.php"><i class="fas fa-arrow-left"></i> Вернуться на главную</a>
                </p>
            </div>
        </div>
    </div>

    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.querySelector('.toggle-password i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.className = 'fas fa-eye-slash';
            } else {
                passwordInput.type = 'password';
                toggleIcon.className = 'fas fa-eye';
            }
        }
        
        // Анимация формы
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('loginForm');
            form.style.opacity = '0';
            form.style.transform = 'translateY(20px)';
            
            setTimeout(() => {
                form.style.opacity = '1';
                form.style.transform = 'translateY(0)';
                form.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
            }, 300);
        });
    </script>
</body>
</html>