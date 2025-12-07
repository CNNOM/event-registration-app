<?php
require_once 'config.php';
require_once 'db.php';

// Получаем все мероприятия с количеством зарегистрированных
$events = $db->query("
    SELECT e.*, u.name as organizer_name,
           COUNT(r.id) as registered_count
    FROM events e 
    LEFT JOIN users u ON e.organizer_id = u.id 
    LEFT JOIN registrations r ON e.id = r.event_id
    WHERE e.date >= date('now') 
    GROUP BY e.id
    ORDER BY e.date ASC
");
?>

<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <title>Система управления мероприятиями</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>
    <div class="container fade-in">
        <!-- Навигация -->
        <nav class="navbar">
            <a href="index.php" class="nav-brand">
                <i class="fas fa-calendar-alt"></i> EventManager
            </a>

            <div class="nav-links">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <?php $user = getCurrentUser(); ?>
                    <?php

                    $role_russian = '';
                    switch ($user['role']) {
                        case 'admin':
                            $role_russian = 'Администратор';
                            break;
                        case 'organizer':
                            $role_russian = 'Организатор';
                            break;
                        case 'participant':
                            $role_russian = 'Участник';
                            break;
                        default:
                            $role_russian = $user['role'];
                    }
                    ?>
                    <?= $role_russian ?>
                    <div class="user-info">
                        <i class="fas fa-user-circle"></i>
                        <span><?= htmlspecialchars($user['name']) ?></span>
                        <span class="role-badge role-<?= $user['role'] ?>">
                            <?= $role_russian ?>
                        </span>
                    </div>

                    <?php if ($user['role'] === ROLE_ADMIN): ?>
                        <a href="admin.php" class="nav-link">
                            <i class="fas fa-crown"></i> Админ-панель
                        </a>
                    <?php elseif ($user['role'] === ROLE_ORGANIZER): ?>
                        <a href="organizer.php" class="nav-link">
                            <i class="fas fa-tasks"></i> Организатор
                        </a>
                    <?php else: ?>
                        <a href="participant.php" class="nav-link">
                            <i class="fas fa-user"></i> Кабинет
                        </a>
                    <?php endif; ?>

                    <a href="logout.php" class="btn btn-outline">
                        <i class="fas fa-sign-out-alt"></i> Выйти
                    </a>
                <?php else: ?>
                    <a href="login.php" class="nav-link">
                        <i class="fas fa-sign-in-alt"></i> Вход
                    </a>
                    <a href="register.php" class="btn">
                        <i class="fas fa-user-plus"></i> Регистрация
                    </a>
                <?php endif; ?>
            </div>
        </nav>

        <!-- Заголовок -->
        <div class="card text-center">
            <h1 style="color: var(--primary-color); margin-bottom: 10px;">
                <i class="fas fa-calendar-check"></i> Предстоящие мероприятия
            </h1>
            <p style="color: var(--gray-color);">Выберите интересующее вас мероприятие и зарегистрируйтесь</p>
        </div>

        <!-- Сообщения -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?= htmlspecialchars($_SESSION['success']) ?>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($_SESSION['error']) ?>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <!-- Список мероприятий -->
        <div class="grid">
            <?php while ($event = $events->fetchArray(SQLITE3_ASSOC)):
                $isFull = ($event['max_participants'] !== null && $event['registered_count'] >= $event['max_participants']);
                $percentage = $event['max_participants'] ? round(($event['registered_count'] / $event['max_participants']) * 100) : 0;
                $dateTime = new DateTime($event['date'] . ' ' . $event['time']);
                $formattedDate = $dateTime->format('d.m.Y');
                $formattedTime = $dateTime->format('H:i');
                $now = new DateTime();
                $daysLeft = $now->diff($dateTime)->days;
                ?>

                <div class="card" style="position: relative;">
                    <!-- Бейдж дней до мероприятия -->
                    <?php if ($daysLeft <= 3): ?>
                        <div
                            style="position: absolute; top: -10px; right: -10px; background: linear-gradient(135deg, #f59e0b, #d97706); color: white; padding: 5px 15px; border-radius: 20px; font-size: 12px; font-weight: bold; z-index: 1;">
                            <i class="fas fa-bolt"></i> Через <?= $daysLeft ?> дн.
                        </div>
                    <?php endif; ?>

                    <div style="display: flex; align-items: start; gap: 15px; margin-bottom: 15px;">
                        <div
                            style="background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); color: white; padding: 15px; border-radius: 10px; text-align: center; min-width: 80px;">
                            <div style="font-size: 24px; font-weight: bold;"><?= $dateTime->format('d') ?></div>
                            <div style="font-size: 12px; text-transform: uppercase;"><?= $dateTime->format('M') ?></div>
                        </div>

                        <div style="flex: 1;">
                            <h3 style="color: var(--primary-color); margin-bottom: 5px;">
                                <?= htmlspecialchars($event['title']) ?>
                            </h3>
                            <div style="color: var(--gray-color); font-size: 14px;">
                                <i class="fas fa-clock"></i> <?= $formattedTime ?>
                                <i class="fas fa-map-marker-alt" style="margin-left: 15px;"></i>
                                <?= htmlspecialchars($event['location']) ?>
                            </div>
                        </div>
                    </div>

                    <p style="margin-bottom: 15px; color: var(--dark-color);">
                        <?= htmlspecialchars($event['description']) ?>
                    </p>

                    <div style="background: rgba(67, 97, 238, 0.1); padding: 15px; border-radius: 8px; margin: 15px 0;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                            <span style="font-weight: 600;">
                                <i class="fas fa-users"></i> Участники:
                            </span>
                            <span>
                                <?php if ($event['max_participants']): ?>
                                    <?= $event['registered_count'] ?>/<?= $event['max_participants'] ?>
                                <?php else: ?>
                                    <?= $event['registered_count'] ?> (без ограничений)
                                <?php endif; ?>
                            </span>
                        </div>

                        <?php if ($event['max_participants']): ?>
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: <?= min($percentage, 100) ?>%;"></div>
                            </div>
                            <div
                                style="display: flex; justify-content: space-between; font-size: 12px; color: var(--gray-color);">
                                <span><?= $percentage ?>% заполнено</span>
                                <span>
                                    <?php if ($isFull): ?>
                                        <span style="color: var(--danger-color);">
                                            <i class="fas fa-times-circle"></i> Мест нет
                                        </span>
                                    <?php else: ?>
                                        <span style="color: var(--success-color);">
                                            <i class="fas fa-check-circle"></i> Осталось
                                            <?= $event['max_participants'] - $event['registered_count'] ?> мест
                                        </span>
                                    <?php endif; ?>
                                </span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 20px;">
                        <div style="color: var(--gray-color); font-size: 14px;">
                            <i class="fas fa-user-tie"></i> Организатор: <?= htmlspecialchars($event['organizer_name']) ?>
                        </div>

                        <?php if (isset($_SESSION['user_id'])): ?>
                            <?php
                            $userId = $_SESSION['user_id'];
                            $checkStmt = $db->prepare("SELECT id FROM registrations WHERE user_id = :user_id AND event_id = :event_id");
                            $checkStmt->bindValue(':user_id', $userId);
                            $checkStmt->bindValue(':event_id', $event['id']);
                            $result = $checkStmt->execute();
                            $isRegistered = $result->fetchArray();
                            ?>

                            <div>
                                <?php if (!$isRegistered): ?>
                                    <?php if (!$isFull): ?>
                                        <form action="register_event.php" method="POST" style="display: inline;">
                                            <input type="hidden" name="event_id" value="<?= $event['id'] ?>">
                                            <button type="submit" class="btn btn-success">
                                                <i class="fas fa-user-plus"></i> Зарегистрироваться
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <button disabled class="btn" style="background: var(--gray-color);">
                                            <i class="fas fa-ban"></i> Регистрация закрыта
                                        </button>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <div style="display: flex; flex-direction: column; gap: 10px; align-items: flex-start;">
                                        <div
                                            style="display: flex; align-items: center; gap: 8px; color: var(--success-color); font-weight: 600;">
                                            <i class="fas fa-check-circle"></i>
                                            <span>Вы зарегистрированы</span>
                                        </div>
                                        <form action="cancel_registration.php" method="POST" style="width: 100%;">
                                            <input type="hidden" name="event_id" value="<?= $event['id'] ?>">
                                            <button type="submit" class="btn btn-danger"
                                                style="width: 100%; padding: 10px; font-size: 14px;"
                                                onclick="return confirm('Вы уверены, что хотите отменить регистрацию?')">
                                                <i class="fas fa-user-minus"></i> Отменить регистрацию
                                            </button>
                                        </form>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <a href="login.php" class="btn">
                                <i class="fas fa-sign-in-alt"></i> Войти для регистрации
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>

    </div>

    <script>
        // Анимация при прокрутке
        document.addEventListener('DOMContentLoaded', function () {
            const cards = document.querySelectorAll('.card');

            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.style.opacity = '1';
                        entry.target.style.transform = 'translateY(0)';
                    }
                });
            }, { threshold: 0.1 });

            cards.forEach(card => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                observer.observe(card);
            });

            // Подсветка активной страницы в навигации
            const currentPage = window.location.pathname.split('/').pop();
            document.querySelectorAll('.nav-link').forEach(link => {
                if (link.getAttribute('href') === currentPage) {
                    link.style.background = 'var(--primary-color)';
                    link.style.color = 'white';
                }
            });
        });
    </script>
</body>

</html>