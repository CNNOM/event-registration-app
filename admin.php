<?php
require_once 'config.php';
require_once 'db.php';

// Только админ
checkRole([ROLE_ADMIN]);

// Управление пользователями
if (isset($_GET['delete_user'])) {
    $db->exec("DELETE FROM users WHERE id = " . (int)$_GET['delete_user']);
}

// Получаем статистику
$usersCount = $db->querySingle("SELECT COUNT(*) FROM users");
$eventsCount = $db->querySingle("SELECT COUNT(*) FROM events");
$registrationsCount = $db->querySingle("SELECT COUNT(*) FROM registrations");

// Получаем всех пользователей для таблицы
$users = $db->query("SELECT * FROM users ORDER BY created_at DESC");
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Панель администратора</title>
    <style>
        body { font-family: Arial; margin: 20px; }
        .stats { display: flex; gap: 20px; margin: 20px 0; }
        .stat-card { 
            background: #f8f9fa; 
            padding: 20px; 
            border-radius: 5px; 
            text-align: center;
            min-width: 150px;
        }
        .stat-card h3 { margin-top: 0; }
        .stat-card p { 
            font-size: 24px; 
            font-weight: bold; 
            margin: 10px 0 0 0;
            color: #007bff;
        }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 10px; }
        th { background: #343a40; color: white; }
        .danger { 
            color: #dc3545; 
            text-decoration: none;
        }
        .danger:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <h1>Панель администратора</h1>
    <a href="index.php">← На главную</a>
    
    <div class="stats">
        <div class="stat-card">
            <h3>Пользователей</h3>
            <p><?= $usersCount ?></p>
        </div>
        <div class="stat-card">
            <h3>Мероприятий</h3>
            <p><?= $eventsCount ?></p>
        </div>
        <div class="stat-card">
            <h3>Регистраций</h3>
            <p><?= $registrationsCount ?></p>
        </div>
    </div>
    
    <h2>Управление пользователями</h2>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Имя</th>
                <th>Email</th>
                <th>Роль</th>
                <th>Дата регистрации</th>
                <th>Действия</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($user = $users->fetchArray(SQLITE3_ASSOC)): ?>
            <tr>
                <td><?= $user['id'] ?></td>
                <td><?= htmlspecialchars($user['name']) ?></td>
                <td><?= htmlspecialchars($user['email']) ?></td>
                <td>
                    <?php 
                    switch($user['role']) {
                        case 'admin': echo '👑 Администратор'; break;
                        case 'organizer': echo '🎪 Организатор'; break;
                        case 'participant': echo '👤 Участник'; break;
                        default: echo $user['role'];
                    }
                    ?>
                </td>
                <td><?= date('d.m.Y H:i', strtotime($user['created_at'])) ?></td>
                <td>
                    <?php if ($user['role'] !== ROLE_ADMIN): ?>
                        <a href="?delete_user=<?= $user['id'] ?>" class="danger" 
                           onclick="return confirm('Удалить пользователя <?= htmlspecialchars($user['name']) ?>?')">
                            Удалить
                        </a>
                    <?php else: ?>
                        <span style="color: #6c757d; font-size: 0.9em;">Администратор</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    
    <h2>Статистика посещаемости мероприятий</h2>
    <?php
    $stats = $db->query("
        SELECT e.id, e.title, e.date, 
               COUNT(r.id) as participants,
               e.max_participants
        FROM events e 
        LEFT JOIN registrations r ON e.id = r.event_id 
        GROUP BY e.id
        ORDER BY e.date DESC
    ");
    ?>
    <table>
        <thead>
            <tr>
                <th>Мероприятие</th>
                <th>Дата</th>
                <th>Зарегистрировано</th>
                <th>Лимит</th>
                <th>Заполнение</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($stat = $stats->fetchArray(SQLITE3_ASSOC)): 
                $percentage = $stat['max_participants'] > 0 
                    ? round(($stat['participants'] / $stat['max_participants']) * 100, 1)
                    : 0;
                ?>
            <tr>
                <td><?= htmlspecialchars($stat['title']) ?></td>
                <td><?= date('d.m.Y', strtotime($stat['date'])) ?></td>
                <td><?= $stat['participants'] ?></td>
                <td><?= $stat['max_participants'] ?: '∞' ?></td>
                <td>
                    <?php if ($stat['max_participants'] > 0): ?>
                        <div style="background: #e9ecef; border-radius: 3px; height: 20px;">
                            <div style="background: #28a745; height: 100%; width: <?= min($percentage, 100) ?>%; 
                                      border-radius: 3px; text-align: center; color: white; font-size: 12px; 
                                      line-height: 20px;">
                                <?= $percentage ?>%
                            </div>
                        </div>
                    <?php else: ?>
                        —
                    <?php endif; ?>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    
    <h2>Быстрые действия</h2>
    <div style="margin: 20px 0;">
        <a href="organizer.php?create=1" style="background: #28a745; color: white; padding: 10px 20px; 
           text-decoration: none; border-radius: 5px; margin-right: 10px;">
            + Создать мероприятие
        </a>
    </div>
</body>
</html>