<?php
require_once 'config.php';
require_once 'db.php';

checkRole([ROLE_ORGANIZER, ROLE_ADMIN]);
$user = getCurrentUser();

// Создание мероприятия
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_event'])) {
    $stmt = $db->prepare("INSERT INTO events (title, description, date, time, location, max_participants, organizer_id) 
                         VALUES (:title, :desc, :date, :time, :location, :max, :org)");
    $stmt->bindValue(':title', $_POST['title']);
    $stmt->bindValue(':desc', $_POST['description']);
    $stmt->bindValue(':date', $_POST['date']);
    $stmt->bindValue(':time', $_POST['time']);
    $stmt->bindValue(':location', $_POST['location']);
    $stmt->bindValue(':max', $_POST['max_participants'] ?: null);
    $stmt->bindValue(':org', $user['id']);
    $stmt->execute();
}

// Получаем мероприятия организатора
$myEvents = $db->query("
    SELECT e.*, COUNT(r.id) as registered 
    FROM events e 
    LEFT JOIN registrations r ON e.id = r.event_id 
    WHERE e.organizer_id = {$user['id']} 
    GROUP BY e.id
");
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Панель организатора</title>
    <style>
        body { font-family: Arial; margin: 20px; }
        .form-group { margin: 10px 0; }
        input, textarea { width: 100%; padding: 8px; }
        .events-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; }
        .event-card { border: 1px solid #ddd; padding: 15px; }
    </style>
</head>
<body>
    <h1>Панель организатора</h1>
    <a href="index.php">← На главную</a>
    
    <h2>Создать мероприятие</h2>
    <form method="POST">
        <input type="hidden" name="create_event" value="1">
        
        <div class="form-group">
            <label>Название:</label>
            <input type="text" name="title" required>
        </div>
        
        <div class="form-group">
            <label>Описание:</label>
            <textarea name="description" rows="3"></textarea>
        </div>
        
        <div class="form-group">
            <label>Дата:</label>
            <input type="date" name="date" required>
        </div>
        
        <div class="form-group">
            <label>Время:</label>
            <input type="time" name="time" required>
        </div>
        
        <div class="form-group">
            <label>Место:</label>
            <input type="text" name="location" required>
        </div>
        
        <div class="form-group">
            <label>Макс. участников (необязательно):</label>
            <input type="number" name="max_participants" min="1">
        </div>
        
        <button type="submit">Создать мероприятие</button>
    </form>
    
    <h2>Мои мероприятия</h2>
    <div class="events-grid">
        <?php while ($event = $myEvents->fetchArray(SQLITE3_ASSOC)): ?>
            <div class="event-card">
                <h3><?= htmlspecialchars($event['title']) ?></h3>
                <p><?= htmlspecialchars($event['description']) ?></p>
                <p><strong>Дата:</strong> <?= $event['date'] ?> <?= $event['time'] ?></p>
                <p><strong>Место:</strong> <?= htmlspecialchars($event['location']) ?></p>
                <p><strong>Зарегистрировано:</strong> <?= $event['registered'] ?>
                   <?= $event['max_participants'] ? "/{$event['max_participants']}" : '' ?></p>
                
                <!-- Кнопка для отправки уведомлений -->
                <form action="send_notifications.php" method="POST">
                    <input type="hidden" name="event_id" value="<?= $event['id'] ?>">
                    <button type="submit">Отправить напоминание участникам</button>
                </form>
                
                <!-- Список участников -->
                <details>
                    <summary>Участники (<?= $event['registered'] ?>)</summary>
                    <?php
                    $participants = $db->query("
                        SELECT u.name, u.email, r.registered_at 
                        FROM registrations r 
                        JOIN users u ON r.user_id = u.id 
                        WHERE r.event_id = {$event['id']}
                    ");
                    ?>
                    <ul>
                        <?php while ($p = $participants->fetchArray(SQLITE3_ASSOC)): ?>
                            <li><?= htmlspecialchars($p['name']) ?> (<?= $p['email'] ?>)</li>
                        <?php endwhile; ?>
                    </ul>
                </details>
            </div>
        <?php endwhile; ?>
    </div>
</body>
</html>