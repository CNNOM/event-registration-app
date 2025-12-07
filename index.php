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
    <title>Мероприятия</title>
    <style>
        body { font-family: Arial; margin: 20px; }
        .event-card { border: 1px solid #ddd; padding: 15px; margin: 10px 0; border-radius: 5px; }
        .header { display: flex; justify-content: space-between; align-items: center; }
        nav a { margin: 0 10px; }
        .places-info { margin: 5px 0; font-size: 0.9em; }
        .places-full { color: #dc3545; font-weight: bold; }
        .places-available { color: #28a745; }
        .message { padding: 10px; margin: 10px 0; border-radius: 5px; }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Список мероприятий</h1>
        <nav>
            <?php if (isset($_SESSION['user_id'])): ?>
                <?php $user = getCurrentUser(); ?>
                <span>Привет, <?= htmlspecialchars($user['name']) ?> (<?= $user['role'] ?>)</span>
                <a href="logout.php">Выйти</a>
                <?php if ($user['role'] === ROLE_ADMIN): ?>
                    <a href="admin.php">Панель администратора</a>
                <?php elseif ($user['role'] === ROLE_ORGANIZER): ?>
                    <a href="organizer.php">Панель организатора</a>
                <?php else: ?>
                    <a href="participant.php">Личный кабинет</a>
                <?php endif; ?>
            <?php else: ?>
                <a href="login.php">Войти</a>
                <a href="register.php">Регистрация</a>
            <?php endif; ?>
        </nav>
    </div>
    
    <?php if (isset($_SESSION['success'])): ?>
        <div class="message success"><?= htmlspecialchars($_SESSION['success']) ?></div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="message error"><?= htmlspecialchars($_SESSION['error']) ?></div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>
    
    <div class="events">
        <?php while ($event = $events->fetchArray(SQLITE3_ASSOC)): 
            $isFull = ($event['max_participants'] !== null && $event['registered_count'] >= $event['max_participants']);
            ?>
            <div class="event-card">
                <h3><?= htmlspecialchars($event['title']) ?></h3>
                <p><?= htmlspecialchars($event['description']) ?></p>
                <p><strong>Дата:</strong> <?= $event['date'] ?> <?= $event['time'] ?></p>
                <p><strong>Место:</strong> <?= htmlspecialchars($event['location']) ?></p>
                <p><strong>Организатор:</strong> <?= htmlspecialchars($event['organizer_name']) ?></p>
                
                <div class="places-info">
                    <?php if ($event['max_participants'] !== null): ?>
                        <?php if ($isFull): ?>
                            <span class="places-full">✗ Мест нет: <?= $event['registered_count'] ?>/<?= $event['max_participants'] ?></span>
                        <?php else: ?>
                            <span class="places-available">✓ Доступно мест: <?= ($event['max_participants'] - $event['registered_count']) ?> из <?= $event['max_participants'] ?></span>
                        <?php endif; ?>
                    <?php else: ?>
                        <span>✅ Участников: <?= $event['registered_count'] ?> (без ограничений)</span>
                    <?php endif; ?>
                </div>
                
                <?php if (isset($_SESSION['user_id'])): ?>
                    <?php
                    // Проверяем зарегистрирован ли пользователь
                    $userId = $_SESSION['user_id'];
                    $checkStmt = $db->prepare("SELECT id FROM registrations WHERE user_id = :user_id AND event_id = :event_id");
                    $checkStmt->bindValue(':user_id', $userId);
                    $checkStmt->bindValue(':event_id', $event['id']);
                    $result = $checkStmt->execute();
                    $isRegistered = $result->fetchArray();
                    ?>
                    
                    <?php if (!$isRegistered): ?>
                        <?php if (!$isFull): ?>
                            <form action="register_event.php" method="POST" style="display: inline;">
                                <input type="hidden" name="event_id" value="<?= $event['id'] ?>">
                                <button type="submit">Зарегистрироваться</button>
                            </form>
                        <?php else: ?>
                            <button disabled style="background: #6c757d; color: white;">Регистрация закрыта</button>
                        <?php endif; ?>
                    <?php else: ?>
                        <span style="color: green;">✓ Вы зарегистрированы</span>
                        <form action="cancel_registration.php" method="POST" style="display: inline;">
                            <input type="hidden" name="event_id" value="<?= $event['id'] ?>">
                            <button type="submit" style="background: #dc3545;"
                                onclick="return confirm('Вы уверены, что хотите отменить регистрацию?')">
                                Отменить регистрацию
                            </button>
                        </form>
                    <?php endif; ?>
                <?php else: ?>
                    <p><em>Для регистрации <a href="login.php">войдите в систему</a></em></p>
                <?php endif; ?>
            </div>
        <?php endwhile; ?>
    </div>
</body>
</html>