<?php
require_once 'config.php';
require_once 'db.php';

checkRole([ROLE_ADMIN]);

if (isset($_GET['delete_user'])) {
    $db->exec("DELETE FROM users WHERE id = " . (int) $_GET['delete_user']);
}

$usersCount = $db->querySingle("SELECT COUNT(*) FROM users");
$eventsCount = $db->querySingle("SELECT COUNT(*) FROM events");
$registrationsCount = $db->querySingle("SELECT COUNT(*) FROM registrations");
$activeEvents = $db->querySingle("SELECT COUNT(*) FROM events WHERE date >= date('now')");
$users = $db->query("SELECT * FROM users ORDER BY created_at DESC");
?>

<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <title>Панель администратора</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .dashboard-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 40px;
            border-radius: var(--border-radius);
            margin-bottom: 30px;
            box-shadow: var(--shadow);
        }

        .dashboard-title {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 10px;
        }

        .quick-actions {
            display: flex;
            gap: 15px;
            margin: 30px 0;
            flex-wrap: wrap;
        }

        .action-card {
            flex: 1;
            min-width: 200px;
            background: white;
            border-radius: var(--border-radius);
            padding: 20px;
            text-align: center;
            cursor: pointer;
            transition: var(--transition);
            border: 2px solid transparent;
        }

        .action-card:hover {
            border-color: var(--primary-color);
            transform: translateY(-5px);
        }

        .action-icon {
            font-size: 32px;
            margin-bottom: 15px;
            color: var(--primary-color);
        }

        .chart-container {
            background: white;
            border-radius: var(--border-radius);
            padding: 25px;
            margin: 30px 0;
            box-shadow: var(--shadow);
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            margin-right: 10px;
        }

        .status-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 8px;
        }

        .status-active {
            background: var(--success-color);
        }

        .status-inactive {
            background: var(--gray-color);
        }

        .search-box {
            margin: 20px 0;
        }

        .search-input {
            width: 100%;
            padding: 12px 45px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 16px;
        }

        .search-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray-color);
        }
    </style>
</head>

<body>
    <div class="container fade-in">
        <!-- Шапка админ-панели -->
        <div class="dashboard-header">
            <div class="dashboard-title">
                <i class="fas fa-crown" style="font-size: 32px;"></i>
                <div>
                    <h1 style="margin: 0; font-size: 28px;">Панель администратора</h1>
                    <p style="opacity: 0.9; margin: 5px 0 0 0;">Полный контроль над системой мероприятий</p>
                </div>
            </div>

            <div style="display: flex; gap: 20px; margin-top: 20px;">
                <a href="index.php" class="btn " style="background: rgba(255,255,255,0.2);">
                    <i class="fas fa-home"></i> На главную
                </a>
                <a href="organizer.php" class="btn" style="background: rgba(255,255,255,0.2);">
                    <i class="fas fa-plus"></i> Создать мероприятие
                </a>
                <a href="logout.php" class="btn" style="background: rgba(255,255,255,0.2);">
                    <i class="fas fa-sign-out-alt"></i> Выйти
                </a>
            </div>
        </div>

        <!-- Статистика -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-users" style="font-size: 32px; color: var(--primary-color);"></i>
                </div>
                <div class="stat-number"><?= $usersCount ?></div>
                <div class="stat-label">Пользователей</div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-calendar" style="font-size: 32px; color: var(--success-color);"></i>
                </div>
                <div class="stat-number"><?= $eventsCount ?></div>
                <div class="stat-label">Мероприятий</div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-clipboard-check" style="font-size: 32px; color: var(--accent-color);"></i>
                </div>
                <div class="stat-number"><?= $registrationsCount ?></div>
                <div class="stat-label">Регистраций</div>
            </div>

            <!-- <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-calendar-day" style="font-size: 32px; color: var(--warning-color);"></i>
                </div>
                <div class="stat-number"><?= $activeEvents ?></div>
                <div class="stat-label">Активных</div>
            </div> -->
            <div class="action-card" onclick="location.href='organizer.php?create=1'">
                <div class="action-icon">
                    <i class="fas fa-plus-circle"></i>
                </div>
                <h3>Новое мероприятие</h3>
                <p>Создать новое мероприятие</p>
            </div>
        </div>

        <!-- Быстрые действия -->
        <!-- <div class="quick-actions">
            <div class="action-card" onclick="location.href='organizer.php?create=1'">
                <div class="action-icon">
                    <i class="fas fa-plus-circle"></i>
                </div>
                <h3>Новое мероприятие</h3>
                <p>Создать новое мероприятие</p>
            </div>

            <div class="action-card" onclick="location.href='register.php'">
                <div class="action-icon">
                    <i class="fas fa-user-plus"></i>
                </div>
                <h3>Добавить пользователя</h3>
                <p>Создать новую учетную запись</p>
            </div>

            <div class="action-card" onclick="generateReport()">
                <div class="action-icon">
                    <i class="fas fa-chart-bar"></i>
                </div>
                <h3>Отчет</h3>
                <p>Сгенерировать полный отчет</p>
            </div>

            <div class="action-card" onclick="openSettings()">
                <div class="action-icon">
                    <i class="fas fa-cog"></i>
                </div>
                <h3>Настройки</h3>
                <p>Настройки системы</p>
            </div>
        </div> -->

        <!-- Поиск -->
        <div class="search-box">
            <div style="position: relative;">
                <i class="fas fa-search search-icon"></i>
                <input type="text" class="search-input" placeholder="Поиск пользователей, мероприятий...">
            </div>
        </div>

        <!-- Таблица пользователей -->
        <div class="table-container">
            <div style="padding: 20px; border-bottom: 1px solid #e2e8f0;">
                <h2 style="margin: 0; color: var(--primary-color);">
                    <i class="fas fa-user-cog"></i> Управление пользователями
                </h2>
            </div>
            <table class="table">
                <thead>
                    <tr>
                        <th>Пользователь</th>
                        <th>Роль</th>
                        <th>Дата регистрации</th>
                        <!-- <th>Статус</th> -->
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($user = $users->fetchArray(SQLITE3_ASSOC)):
                        $initials = getInitials($user['name']);
                        ?>
                        <tr>
                            <td style="display: flex; align-items: center;">
                                <div class="user-avatar">
                                    <?= $initials ?>
                                </div>
                                <div>
                                    <div style="font-weight: 600;"><?= htmlspecialchars($user['name']) ?></div>
                                    <div style="color: var(--gray-color); font-size: 14px;">
                                        <?= htmlspecialchars($user['email']) ?>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="role-badge role-<?= $user['role'] ?>">
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
                                </span>
                            </td>
                            <td><?= date('d.m.Y H:i', strtotime($user['created_at'])) ?></td>
                            <!-- <td>
                                <span class="status-dot status-active"></span>
                                Активен
                            </td> -->
                            <td>
                                <?php if ($user['role'] !== ROLE_ADMIN): ?>
                                    <div style="display: flex; gap: 10px;">
                                        <!-- <button class="btn" style="padding: 8px 16px; font-size: 14px;"
                                            onclick="editUser(<?= $user['id'] ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button> -->
                                        <a href="?delete_user=<?= $user['id'] ?>" class="btn btn-danger"
                                            style="padding: 8px 16px; font-size: 14px;"
                                            onclick="return confirm('Удалить пользователя <?= htmlspecialchars($user['name']) ?>?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                <?php else: ?>
                                    <span style="color: var(--gray-color); font-size: 14px;">
                                        <i class="fas fa-shield-alt"></i> Администратор
                                    </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <!-- Статистика мероприятий -->
        <div class="chart-container">
            <h2 style="margin-bottom: 20px; color: var(--primary-color);">
                <i class="fas fa-chart-line"></i> Статистика посещаемости
            </h2>

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

            <table class="table">
                <thead>
                    <tr>
                        <th>Мероприятие</th>
                        <th>Дата</th>
                        <th>Участники</th>
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
                            <td style="font-weight: 600;"><?= htmlspecialchars($stat['title']) ?></td>
                            <td><?= date('d.m.Y', strtotime($stat['date'])) ?></td>
                            <td>
                                <span style="font-weight: 600; color: var(--primary-color);">
                                    <?= $stat['participants'] ?>
                                </span>
                            </td>
                            <td><?= $stat['max_participants'] ?: '∞' ?></td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <div
                                        style="flex: 1; height: 8px; background: #e2e8f0; border-radius: 4px; overflow: hidden;">
                                        <div style="height: 100%; width: <?= min($percentage, 100) ?>%; 
                                          background: linear-gradient(90deg, var(--success-color), var(--accent-color));
                                          border-radius: 4px;"></div>
                                    </div>
                                    <span
                                        style="font-size: 14px; color: var(--gray-color); min-width: 40px; text-align: right;">
                                        <?= $percentage ?>%
                                    </span>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        function getInitials(name) {
            return name.split(' ').map(n => n[0]).join('').toUpperCase();
        }

        function generateReport() {
            alert('Генерация отчета... Функция в разработке');
        }

        function openSettings() {
            alert('Настройки системы... Функция в разработке');
        }

        function editUser(userId) {
            alert('Редактирование пользователя ID: ' + userId + '... Функция в разработке');
        }

        // Поиск
        document.querySelector('.search-input').addEventListener('input', function (e) {
            const searchTerm = e.target.value.toLowerCase();
            const rows = document.querySelectorAll('.table tbody tr');

            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });
    </script>
</body>

</html>

<?php
function getInitials($name)
{
    $initials = '';
    $words = explode(' ', $name);
    foreach ($words as $word) {
        if (!empty($word)) {
            $initials .= strtoupper($word[0]);
        }
    }
    return substr($initials, 0, 2);
}
?>