<?php
require_once 'db.php';
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $role = $_POST['role'] ?? ROLE_PARTICIPANT;

    if (empty($name) || empty($email) || empty($password)) {
        $error = "Все поля обязательны";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Некорректный email";
    } else {
        $stmt = $db->prepare("SELECT id FROM users WHERE email = :email");
        $stmt->bindValue(':email', $email);
        $result = $stmt->execute();
        
        if ($result->fetchArray()) {
            $error = "Пользователь с таким email уже существует";
        } else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("INSERT INTO users (email, password, name, role) VALUES (:email, :password, :name, :role)");
            $stmt->bindValue(':email', $email);
            $stmt->bindValue(':password', $hashedPassword);
            $stmt->bindValue(':name', $name);
            $stmt->bindValue(':role', $role);
            
            if ($stmt->execute()) {
                $userId = $db->lastInsertRowID();
                $_SESSION['user_id'] = $userId;
                $_SESSION['email'] = $email;
                $_SESSION['name'] = $name;
                $_SESSION['role'] = $role;
                
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
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .register-container {
            display: flex;
            min-height: 100vh;
            padding: 20px;
        }
        
        .register-left {
            flex: 1;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border-radius: var(--border-radius);
            padding: 40px;
            color: white;
            display: flex;
            flex-direction: column;
            justify-content: center;
            box-shadow: var(--shadow);
        }
        
        .register-right {
            flex: 1;
            padding: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .register-card {
            width: 100%;
            max-width: 450px;
            animation: fadeIn 0.8s ease-out;
        }
        
        .features-list {
            list-style: none;
            padding: 0;
            margin: 40px 0;
        }
        
        .features-list li {
            margin: 15px 0;
            display: flex;
            align-items: center;
            gap: 15px;
            font-size: 16px;
        }
        
        .feature-icon {
            width: 40px;
            height: 40px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }
        
        .role-options {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }
        
        .role-option {
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            transition: var(--transition);
        }
        
        .role-option:hover {
            border-color: var(--primary-color);
            transform: translateY(-2px);
        }
        
        .role-option.selected {
            border-color: var(--primary-color);
            background: rgba(67, 97, 238, 0.1);
        }
        
        .role-icon {
            font-size: 24px;
            margin-bottom: 10px;
            color: var(--primary-color);
        }
        
        .password-strength {
            height: 4px;
            background: #e2e8f0;
            border-radius: 2px;
            margin: 10px 0;
            overflow: hidden;
        }
        
        .strength-bar {
            height: 100%;
            width: 0;
            border-radius: 2px;
            transition: width 0.3s ease;
        }
        
        @media (max-width: 768px) {
            .register-container {
                flex-direction: column;
            }
            
            .register-left {
                margin-bottom: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="register-container">
        <!-- Левая часть с информацией -->
        <div class="register-left">
            <div>
                <h1 style="font-size: 32px; margin-bottom: 10px;">
                    <i class="fas fa-calendar-alt"></i> EventManager
                </h1>
                <p style="opacity: 0.9; font-size: 18px;">
                    Присоединяйтесь к нашему сообществу и участвуйте в лучших мероприятиях
                </p>
                
                <ul class="features-list">
                    <li>
                        <div class="feature-icon">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <span>Регистрация на мероприятия в один клик</span>
                    </li>
                    <li>
                        <div class="feature-icon">
                            <i class="fas fa-bell"></i>
                        </div>
                        <span>Уведомления о новых мероприятиях</span>
                    </li>
                    <li>
                        <div class="feature-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <span>Отслеживание вашей активности</span>
                    </li>
                    <li>
                        <div class="feature-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <span>Сообщество единомышленников</span>
                    </li>
                </ul>
                
                <div style="margin-top: 40px;">
                    <p style="opacity: 0.8; font-size: 14px;">
                        <i class="fas fa-shield-alt"></i> Ваши данные защищены и не передаются третьим лицам
                    </p>
                </div>
            </div>
        </div>

        <!-- Правая часть с формой -->
        <div class="register-right">
            <div class="register-card">
                <div style="text-align: center; margin-bottom: 40px;">
                    <h1 style="color: var(--primary-color); margin-bottom: 10px;">
                        Создать аккаунт
                    </h1>
                    <p style="color: var(--gray-color);">
                        Заполните форму ниже для регистрации
                    </p>
                </div>
                
                <?php if (isset($error)): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" id="registerForm">
                    <div class="form-group">
                        <label class="form-label">ФИО</label>
                        <div style="position: relative;">
                            <input type="text" name="name" class="form-control" 
                                   placeholder="Иванов Иван Иванович" required
                                   style="padding-left: 45px;">
                            <i class="fas fa-user" style="position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: var(--gray-color);"></i>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <div style="position: relative;">
                            <input type="email" name="email" class="form-control" 
                                   placeholder="example@mail.com" required
                                   style="padding-left: 45px;">
                            <i class="fas fa-envelope" style="position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: var(--gray-color);"></i>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Пароль</label>
                        <div style="position: relative;">
                            <input type="password" name="password" id="regPassword" 
                                   class="form-control" placeholder="Минимум 6 символов" required
                                   style="padding-left: 45px;"
                                   oninput="checkPasswordStrength(this.value)">
                            <i class="fas fa-lock" style="position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: var(--gray-color);"></i>
                            <button type="button" class="toggle-password" 
                                    style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); background: none; border: none; color: var(--gray-color); cursor: pointer;"
                                    onclick="toggleRegPassword()">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div class="password-strength">
                            <div class="strength-bar" id="strengthBar"></div>
                        </div>
                        <div id="passwordTips" style="font-size: 12px; color: var(--gray-color); margin-top: 5px;"></div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Выберите роль</label>
                        <div class="role-options">
                            <div class="role-option" onclick="selectRole('participant')">
                                <div class="role-icon">
                                    <i class="fas fa-user"></i>
                                </div>
                                <h4 style="margin: 5px 0;">Участник</h4>
                                <p style="font-size: 12px; color: var(--gray-color);">
                                    Регистрация на мероприятия
                                </p>
                            </div>
                            
                            <div class="role-option" onclick="selectRole('organizer')">
                                <div class="role-icon">
                                    <i class="fas fa-tasks"></i>
                                </div>
                                <h4 style="margin: 5px 0;">Организатор</h4>
                                <p style="font-size: 12px; color: var(--gray-color);">
                                    Создание мероприятий
                                </p>
                            </div>
                        </div>
                        <input type="hidden" name="role" id="selectedRole" value="participant">
                    </div>
                    
                    <div style="margin: 30px 0;">
                        <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                            <input type="checkbox" required>
                            <span style="color: var(--gray-color); font-size: 14px;">
                                Я соглашаюсь с <a href="#" style="color: var(--primary-color);">правилами использования</a> 
                                и <a href="#" style="color: var(--primary-color);">политикой конфиденциальности</a>
                            </span>
                        </label>
                    </div>
                    
                    <button type="submit" class="btn" style="width: 100%;">
                        <i class="fas fa-user-plus"></i> Зарегистрироваться
                    </button>
                    
                    <div style="text-align: center; margin-top: 30px;">
                        <p style="color: var(--gray-color);">
                            Уже есть аккаунт? 
                            <a href="login.php" style="color: var(--primary-color); font-weight: 600; text-decoration: none;">
                                Войти
                            </a>
                        </p>
                        <p style="margin-top: 10px; font-size: 14px;">
                            <a href="index.php" style="color: var(--primary-color);">
                                <i class="fas fa-arrow-left"></i> Вернуться на главную
                            </a>
                        </p>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        let selectedRole = 'participant';
        
        function selectRole(role) {
            selectedRole = role;
            document.getElementById('selectedRole').value = role;
            
            // Убираем выделение со всех
            document.querySelectorAll('.role-option').forEach(el => {
                el.classList.remove('selected');
            });
            
            // Добавляем выделение выбранному
            document.querySelectorAll('.role-option').forEach(el => {
                if (el.textContent.includes(role === 'participant' ? 'Участник' : 'Организатор')) {
                    el.classList.add('selected');
                }
            });
        }
        
        function toggleRegPassword() {
            const passwordInput = document.getElementById('regPassword');
            const toggleIcon = document.querySelector('.toggle-password i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.className = 'fas fa-eye-slash';
            } else {
                passwordInput.type = 'password';
                toggleIcon.className = 'fas fa-eye';
            }
        }
        
        function checkPasswordStrength(password) {
            const bar = document.getElementById('strengthBar');
            const tips = document.getElementById('passwordTips');
            
            let strength = 0;
            let tipsText = [];
            
            if (password.length >= 8) strength++;
            else tipsText.push('минимум 8 символов');
            
            if (/[A-Z]/.test(password)) strength++;
            else tipsText.push('заглавные буквы');
            
            if (/[0-9]/.test(password)) strength++;
            else tipsText.push('цифры');
            
            if (/[^A-Za-z0-9]/.test(password)) strength++;
            else tipsText.push('спецсимволы');
            
            // Обновляем полосу
            const width = strength * 25;
            bar.style.width = width + '%';
            
            // Цвет в зависимости от силы
            if (strength <= 1) {
                bar.style.background = '#ef4444';
                bar.style.color = '#ef4444';
            } else if (strength <= 2) {
                bar.style.background = '#f59e0b';
                bar.style.color = '#f59e0b';
            } else if (strength <= 3) {
                bar.style.background = '#10b981';
                bar.style.color = '#10b981';
            } else {
                bar.style.background = '#3b82f6';
                bar.style.color = '#3b82f6';
            }
            
            // Советы
            if (tipsText.length > 0) {
                tips.innerHTML = 'Добавьте: ' + tipsText.join(', ');
            } else {
                tips.innerHTML = '✓ Отличный пароль!';
                tips.style.color = '#10b981';
            }
        }
        
        // Инициализация
        document.addEventListener('DOMContentLoaded', function() {
            selectRole('participant');
            
            const form = document.getElementById('registerForm');
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