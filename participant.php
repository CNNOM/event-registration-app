<?php
require_once 'config.php';
require_once 'db.php';

checkRole([ROLE_PARTICIPANT, ROLE_ORGANIZER, ROLE_ADMIN]);
$user = getCurrentUser();

// Получаем регистрации пользователя
$myRegistrations = $db->query("
    SELECT e.*, r.registered_at, r.status 
    FROM registrations r 
    JOIN events e ON r.event_id = e.id 
    WHERE r.user_id = {$user['id']} 
    ORDER BY e.date ASC
");
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Личный кабинет</title>
    <style>
        body { font-family: Arial; margin: 20px; }
        .profile { background: #f8f9fa; padding: 20px; margin-bottom: 20px; }
        .events-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; }
        .event-card { border: 1px solid #ddd; padding: 15px; }
    </style>
</head>
<body>
    <h1>Личный кабинет</h1>
    <a href="index.php">← На главную</a>
    
    <div class="profile">
        <h2>Мой профиль</h2>
        <p><strong>ФИО:</strong> <?= htmlspecialchars($user['name']) ?></p>
        <p><strong>Email:</strong> <?= htmlspecialchars($user['email']) ?></p>
        <p><strong>Роль:</strong> <?= $user['role'] ?></p>
    </div>
    
    <h2>Мои регистрации на мероприятия</h2>
    <div class="events-grid">
        <?php while ($event = $myRegistrations->fetchArray(SQLITE3_ASSOC)): ?>
            <div class="event-card">
                <h3><?= htmlspecialchars($event['title']) ?></h3>
                <p><?= htmlspecialchars($event['description']) ?></p>
                <p><strong>Дата:</strong> <?= $event['date'] ?> <?= $event['time'] ?></p>
                <p><strong>Место:</strong> <?= htmlspecialchars($event['location']) ?></p>
                <p><strong>Статус:</strong> <?= $event['status'] ?></p>
                <p><strong>Зарегистрирован:</strong> <?= $event['registered_at'] ?></p>
                
                <form action="cancel_registration.php" method="POST">
                    <input type="hidden" name="event_id" value="<?= $event['id'] ?>">
                    <button type="submit" style="background: #dc3545;">Отменить регистрацию</button>
                </form>
            </div>
        <?php endwhile; ?>
    </div>
</body>
</html>