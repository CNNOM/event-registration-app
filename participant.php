<?php
require_once 'config.php';
require_once 'db.php';

checkRole([ROLE_PARTICIPANT, ROLE_ORGANIZER, ROLE_ADMIN]);
$user = getCurrentUser();

// Получаем регистрации пользователя с дополнительной информацией
$myRegistrations = $db->query("
    SELECT e.*, r.registered_at, r.status,
           u.name as organizer_name,
           COUNT(r2.id) as total_participants
    FROM registrations r 
    JOIN events e ON r.event_id = e.id 
    LEFT JOIN users u ON e.organizer_id = u.id
    LEFT JOIN registrations r2 ON e.id = r2.event_id
    WHERE r.user_id = {$user['id']} 
    GROUP BY e.id, r.registered_at, r.status
    ORDER BY e.date ASC
");

// Статистика участника
$totalRegistrations = $db->querySingle("SELECT COUNT(*) FROM registrations WHERE user_id = {$user['id']}");
$upcomingEvents = $db->querySingle("
    SELECT COUNT(*) FROM registrations r 
    JOIN events e ON r.event_id = e.id 
    WHERE r.user_id = {$user['id']} AND e.date >= date('now')
");
$completedEvents = $db->querySingle("
    SELECT COUNT(*) FROM registrations r 
    JOIN events e ON r.event_id = e.id 
    WHERE r.user_id = {$user['id']} AND e.date < date('now')
");

// Рекомендуемые мероприятия (в которых пользователь не участвует)
$recommendedEvents = $db->query("
    SELECT e.*, u.name as organizer_name, 
           COUNT(r.id) as registered_count
    FROM events e 
    LEFT JOIN users u ON e.organizer_id = u.id 
    LEFT JOIN registrations r ON e.id = r.event_id
    WHERE e.date >= date('now') 
    AND e.id NOT IN (
        SELECT event_id FROM registrations WHERE user_id = {$user['id']}
    )
    GROUP BY e.id
    ORDER BY e.date ASC
    LIMIT 3
");
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Личный кабинет</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .profile-header {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            padding: 40px;
            border-radius: var(--border-radius);
            margin-bottom: 30px;
            box-shadow: var(--shadow);
        }
        
        .profile-info {
            display: grid;
            grid-template-columns: auto 1fr;
            gap: 30px;
            align-items: center;
        }
        
        .profile-avatar {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 36px;
            font-weight: bold;
            border: 4px solid white;
            box-shadow: var(--shadow);
        }
        
        .profile-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 20px;
            margin: 30px 0;
        }
        
        .profile-stat {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            backdrop-filter: blur(10px);
        }
        
        .profile-stat-number {
            font-size: 28px;
            font-weight: bold;
            margin: 10px 0;
        }
        
        .badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
            margin-left: 10px;
        }
        
        .badge-upcoming { background: #dbeafe; color: #1d4ed8; }
        .badge-ongoing { background: #fef3c7; color: #d97706; }
        .badge-completed { background: #f3f4f6; color: #6b7280; }
        
        .event-card-participant {
            position: relative;
        }
        
        .calendar-view {
            background: white;
            border-radius: var(--border-radius);
            padding: 25px;
            margin: 30px 0;
            box-shadow: var(--shadow);
        }
        
        .calendar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .calendar-day {
            text-align: center;
            padding: 10px;
            border-radius: 8px;
            font-weight: 600;
            color: var(--gray-color);
        }
        
        .calendar-cell {
            text-align: center;
            padding: 15px;
            border-radius: 8px;
            background: #f8fafc;
            cursor: pointer;
            transition: var(--transition);
        }
        
        .calendar-cell.has-event {
            background: rgba(67, 97, 238, 0.1);
            color: var(--primary-color);
            font-weight: 600;
        }
        
        .calendar-cell.has-event:hover {
            background: rgba(67, 97, 238, 0.2);
        }
        
        .calendar-cell.today {
            background: var(--primary-color);
            color: white;
        }
        
        .recommended-section {
            background: linear-gradient(135deg, #f8fafc, #e2e8f0);
            border-radius: var(--border-radius);
            padding: 30px;
            margin: 40px 0;
        }
        
        .certificate {
            background: linear-gradient(135deg, #8b5cf6, #7c3aed);
            border-radius: var(--border-radius);
            padding: 30px;
            color: white;
            margin: 30px 0;
            text-align: center;
            box-shadow: var(--shadow);
        }
        
        .achievements {
            display: flex;
            gap: 15px;
            margin: 20px 0;
            flex-wrap: wrap;
        }
        
        .achievement {
            background: white;
            border-radius: 12px;
            padding: 15px;
            text-align: center;
            flex: 1;
            min-width: 150px;
            box-shadow: var(--shadow);
        }
        
        .achievement-icon {
            font-size: 24px;
            margin-bottom: 10px;
            color: var(--primary-color);
        }
        
        .empty-registrations {
            text-align: center;
            padding: 40px;
            color: var(--gray-color);
        }
        
        .empty-registrations i {
            font-size: 48px;
            margin-bottom: 15px;
            color: #e2e8f0;
        }
        
        .countdown {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 6px 12px;
            background: rgba(239, 68, 68, 0.1);
            border-radius: 20px;
            color: #dc2626;
            font-weight: 600;
            margin-left: 10px;
        }
    </style>
</head>
<body>
    <div class="container fade-in">
        <!-- Шапка профиля -->
        <div class="profile-header">
            <div class="profile-info">
                <div class="profile-avatar" id="userAvatar">
                    <?= getInitials($user['name']) ?>
                </div>
                <div>
                    <h1 style="margin: 0 0 5px 0;"><?= htmlspecialchars($user['name']) ?></h1>
                    <div style="opacity: 0.9; margin-bottom: 10px;">
                        <i class="fas fa-envelope"></i> <?= htmlspecialchars($user['email']) ?>
                        <span class="badge badge-upcoming" style="margin-left: 15px;">
                            <?= $user['role'] === ROLE_PARTICIPANT ? '👤 Участник' : 
                               ($user['role'] === ROLE_ORGANIZER ? '🎪 Организатор' : '👑 Администратор') ?>
                        </span>
                    </div>
                    <div style="opacity: 0.8; font-size: 14px;">
                        <i class="fas fa-user-clock"></i> Участник с <?= date('d.m.Y', strtotime($_SESSION['user_created_at'] ?? date('Y-m-d'))) ?>
                    </div>
                </div>
            </div>
            
            <!-- Статистика участника -->
            <div class="profile-stats">
                <div class="profile-stat">
                    <div style="font-size: 14px; opacity: 0.9;">Всего мероприятий</div>
                    <div class="profile-stat-number"><?= $totalRegistrations ?></div>
                </div>
                <div class="profile-stat">
                    <div style="font-size: 14px; opacity: 0.9;">Предстоящих</div>
                    <div class="profile-stat-number"><?= $upcomingEvents ?></div>
                </div>
                <div class="profile-stat">
                    <div style="font-size: 14px; opacity: 0.9;">Посещенных</div>
                    <div class="profile-stat-number"><?= $completedEvents ?></div>
                </div>
                <div class="profile-stat">
                    <div style="font-size: 14px; opacity: 0.9;">Уровень активности</div>
                    <div class="profile-stat-number">
                        <?php 
                        $activityLevel = $totalRegistrations > 0 ? 
                            ($upcomingEvents + $completedEvents) * 10 : 0;
                        echo min($activityLevel, 100);
                        ?>%
                    </div>
                </div>
            </div>
        </div>

        <!-- Навигация -->
        <div style="display: flex; gap: 10px; margin-bottom: 30px;">
            <a href="index.php" class="btn btn-outline">
                <i class="fas fa-home"></i> На главную
            </a>
            <a href="#registrations" class="btn">
                <i class="fas fa-calendar-check"></i> Мои регистрации
            </a>
            <a href="#recommended" class="btn">
                <i class="fas fa-star"></i> Рекомендации
            </a>
            <a href="#certificates" class="btn">
                <i class="fas fa-award"></i> Достижения
            </a>
        </div>

        <!-- Календарь мероприятий -->
        <div class="calendar-view">
            <div class="calendar-header">
                <h3 style="margin: 0; color: var(--primary-color);">
                    <i class="fas fa-calendar-alt"></i> Календарь мероприятий
                </h3>
                <div>
                    <span style="color: var(--gray-color);"><?= date('F Y') ?></span>
                </div>
            </div>
            
            <div class="calendar-grid">
                <div class="calendar-day">Пн</div>
                <div class="calendar-day">Вт</div>
                <div class="calendar-day">Ср</div>
                <div class="calendar-day">Чт</div>
                <div class="calendar-day">Пт</div>
                <div class="calendar-day">Сб</div>
                <div class="calendar-day">Вс</div>
                
                <?php
                $today = date('j');
                $daysInMonth = date('t');
                $firstDay = date('N', strtotime(date('Y-m-01')));
                
                // Пустые ячейки до первого дня месяца
                for ($i = 1; $i < $firstDay; $i++) {
                    echo '<div class="calendar-cell"></div>';
                }
                
                // Дни месяца
                for ($day = 1; $day <= $daysInMonth; $day++) {
                    $hasEvent = false;
                    // Здесь можно проверить, есть ли мероприятия в этот день
                    $isToday = $day == $today;
                    $classes = 'calendar-cell';
                    if ($isToday) $classes .= ' today';
                    if ($hasEvent) $classes .= ' has-event';
                    
                    echo "<div class='{$classes}' onclick='showDayEvents({$day})'>{$day}</div>";
                }
                ?>
            </div>
        </div>

        <!-- Достижения -->
        <div class="achievements">
            <div class="achievement">
                <div class="achievement-icon">
                    <i class="fas fa-fire"></i>
                </div>
                <div style="font-weight: 600; font-size: 14px;">Страсть к знаниям</div>
                <div style="font-size: 12px; color: var(--gray-color);">Посетил 5+ мероприятий</div>
            </div>
            <div class="achievement">
                <div class="achievement-icon">
                    <i class="fas fa-bolt"></i>
                </div>
                <div style="font-weight: 600; font-size: 14px;">Быстрая регистрация</div>
                <div style="font-size: 12px; color: var(--gray-color);">Регистрация за 24 часа до начала</div>
            </div>
            <div class="achievement">
                <div class="achievement-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div style="font-weight: 600; font-size: 14px;">Социальная активность</div>
                <div style="font-size: 12px; color: var(--gray-color);">Привел 3+ друзей</div>
            </div>
            <div class="achievement">
                <div class="achievement-icon">
                    <i class="fas fa-calendar-star"></i>
                </div>
                <div style="font-weight: 600; font-size: 14px;">Постоянный участник</div>
                <div style="font-size: 12px; color: var(--gray-color);">Участвует 3 месяца подряд</div>
            </div>
        </div>

        <!-- Мои регистрации -->
        <div id="registrations" style="margin: 40px 0;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2 style="color: var(--primary-color); margin: 0;">
                    <i class="fas fa-ticket-alt"></i> Мои регистрации
                </h2>
                <div style="color: var(--gray-color);">
                    <?= $totalRegistrations ?> мероприятий
                </div>
            </div>
            
            <?php if ($totalRegistrations > 0): ?>
                <div class="grid">
                    <?php while ($event = $myRegistrations->fetchArray(SQLITE3_ASSOC)): 
                        $eventDate = new DateTime($event['date'] . ' ' . $event['time']);
                        $now = new DateTime();
                        $daysDiff = $now->diff($eventDate)->days;
                        $isPast = $eventDate < $now;
                        $status = $isPast ? 'completed' : ($daysDiff <= 7 ? 'ongoing' : 'upcoming');
                        $statusText = $isPast ? 'Посещено' : ($daysDiff <= 7 ? 'Скоро' : 'Предстоит');
                        ?>
                        
                        <div class="card event-card-participant">
                            <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 15px;">
                                <div>
                                    <h3 style="margin: 0 0 5px 0; color: var(--primary-color);">
                                        <?= htmlspecialchars($event['title']) ?>
                                    </h3>
                                    <div style="font-size: 14px; color: var(--gray-color);">
                                        <i class="fas fa-user-tie"></i> <?= htmlspecialchars($event['organizer_name']) ?>
                                    </div>
                                </div>
                                <span class="badge badge-<?= $status ?>">
                                    <?= $statusText ?>
                                </span>
                            </div>
                            
                            <p style="color: var(--dark-color); margin-bottom: 15px; font-size: 14px;">
                                <?= htmlspecialchars($event['description']) ?>
                            </p>
                            
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin: 15px 0;">
                                <div style="background: rgba(67, 97, 238, 0.1); padding: 12px; border-radius: 8px;">
                                    <div style="font-size: 12px; color: var(--gray-color);">Дата и время</div>
                                    <div style="font-weight: 600;">
                                        <i class="far fa-calendar"></i> <?= date('d.m.Y', strtotime($event['date'])) ?>
                                        <br><i class="far fa-clock"></i> <?= $event['time'] ?>
                                    </div>
                                </div>
                                <div style="background: rgba(67, 97, 238, 0.1); padding: 12px; border-radius: 8px;">
                                    <div style="font-size: 12px; color: var(--gray-color);">Место</div>
                                    <div style="font-weight: 600;">
                                        <i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($event['location']) ?>
                                    </div>
                                </div>
                            </div>
                            
                            <div style="background: rgba(67, 97, 238, 0.1); padding: 15px; border-radius: 8px; margin: 15px 0;">
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <div>
                                        <div style="font-weight: 600;">Вы зарегистрированы</div>
                                        <div style="font-size: 14px; color: var(--gray-color);">
                                            <i class="far fa-clock"></i> <?= date('d.m.Y H:i', strtotime($event['registered_at'])) ?>
                                        </div>
                                    </div>
                                    <div style="text-align: right;">
                                        <div style="font-weight: 600;"><?= $event['total_participants'] ?> участников</div>
                                        <div style="font-size: 14px; color: var(--gray-color);">
                                            Статус: <?= $event['status'] ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <?php if (!$isPast): ?>
                                <div style="display: flex; gap: 10px; margin-top: 20px;">
                                    <?php if ($daysDiff <= 3): ?>
                                        <div class="countdown">
                                            <i class="fas fa-clock"></i> Через <?= $daysDiff ?> дн.
                                        </div>
                                    <?php endif; ?>
                                    
                                    <form action="cancel_registration.php" method="POST" style="flex: 1;">
                                        <input type="hidden" name="event_id" value="<?= $event['id'] ?>">
                                        <button type="submit" class="btn btn-danger" 
                                                onclick="return confirm('Вы уверены, что хотите отменить регистрацию?')">
                                            <i class="fas fa-user-minus"></i> Отменить регистрацию
                                        </button>
                                    </form>
                                    
                                    <button class="btn btn-outline" onclick="addToCalendar(<?= $event['id'] ?>)" style="flex: 1;">
                                        <i class="fas fa-calendar-plus"></i> В календарь
                                    </button>
                                </div>
                            <?php else: ?>
                                <div style="text-align: center; margin-top: 20px; padding-top: 15px; border-top: 1px solid #e2e8f0;">
                                    <button class="btn" onclick="requestCertificate(<?= $event['id'] ?>)">
                                        <i class="fas fa-award"></i> Получить сертификат
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="empty-registrations card">
                    <i class="fas fa-calendar-times"></i>
                    <h3>У вас пока нет регистраций</h3>
                    <p>Найдите интересующие мероприятия и зарегистрируйтесь</p>
                    <a href="index.php" class="btn">
                        <i class="fas fa-search"></i> Найти мероприятия
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Рекомендуемые мероприятия -->
        <?php if ($recommendedEvents->numColumns() > 0): ?>
            <div id="recommended" class="recommended-section">
                <h2 style="color: var(--primary-color); margin-bottom: 20px;">
                    <i class="fas fa-star"></i> Рекомендуем посетить
                </h2>
                <p style="color: var(--gray-color); margin-bottom: 20px;">
                    Мероприятия, которые могут вас заинтересовать
                </p>
                
                <div class="grid">
                    <?php while ($event = $recommendedEvents->fetchArray(SQLITE3_ASSOC)): ?>
                        <div class="card">
                            <h3 style="margin: 0 0 10px 0; color: var(--primary-color);">
                                <?= htmlspecialchars($event['title']) ?>
                            </h3>
                            <p style="color: var(--dark-color); font-size: 14px; margin-bottom: 15px;">
                                <?= htmlspecialchars($event['description']) ?>
                            </p>
                            <div style="font-size: 14px; color: var(--gray-color); margin-bottom: 15px;">
                                <i class="far fa-calendar"></i> <?= date('d.m.Y', strtotime($event['date'])) ?>
                                <i class="far fa-clock" style="margin-left: 15px;"></i> <?= $event['time'] ?>
                            </div>
                            <div style="font-size: 14px; color: var(--gray-color); margin-bottom: 15px;">
                                <i class="fas fa-user-tie"></i> <?= htmlspecialchars($event['organizer_name']) ?>
                            </div>
                            <a href="index.php#event-<?= $event['id'] ?>" class="btn" style="width: 100%;">
                                <i class="fas fa-info-circle"></i> Подробнее
                            </a>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Сертификаты и достижения -->
        <?php if ($completedEvents > 0): ?>
            <div id="certificates" class="certificate">
                <h2 style="margin: 0 0 10px 0;">
                    <i class="fas fa-award"></i> Ваши достижения
                </h2>
                <p style="opacity: 0.9; margin-bottom: 20px;">
                    Вы посетили <?= $completedEvents ?> мероприятий. Продолжайте в том же духе!
                </p>
                <button class="btn" style="background: rgba(255,255,255,0.2);">
                    <i class="fas fa-download"></i> Скачать все сертификаты
                </button>
            </div>
        <?php endif; ?>

        <!-- Footer -->
        <div class="footer">
            <p>© 2024 EventManager - Личный кабинет</p>
            <p style="font-size: 12px; margin-top: 10px; opacity: 0.8;">
                <i class="fas fa-user"></i> <?= htmlspecialchars($user['name']) ?>
                | <i class="fas fa-calendar-alt"></i> <?= $totalRegistrations ?> мероприятий
                | <i class="fas fa-trophy"></i> <?= $completedEvents ?> посещено
            </p>
        </div>
    </div>

    <script>
        function showDayEvents(day) {
            alert(`Мероприятия на ${day} число\nФункция в разработке`);
        }
        
        function addToCalendar(eventId) {
            alert(`Добавление мероприятия ID: ${eventId} в календарь\nФункция в разработке`);
        }
        
        function requestCertificate(eventId) {
            alert(`Запрос сертификата за мероприятие ID: ${eventId}\nФункция в разработке`);
        }
        
        // Генерация аватара
        document.addEventListener('DOMContentLoaded', function() {
            const avatar = document.getElementById('userAvatar');
            const name = '<?= $user['name'] ?>';
            const initials = getInitials(name);
            avatar.textContent = initials;
            
            // Цвет аватара на основе имени
            const colors = [
                'linear-gradient(135deg, #4361ee, #3a0ca3)',
                'linear-gradient(135deg, #4cc9f0, #3a86ff)',
                'linear-gradient(135deg, #7209b7, #560bad)',
                'linear-gradient(135deg, #f72585, #b5179e)',
                'linear-gradient(135deg, #4895ef, #4361ee)'
            ];
            const colorIndex = name.length % colors.length;
            avatar.style.background = colors[colorIndex];
            
            // Анимация карточек
            const cards = document.querySelectorAll('.card');
            cards.forEach((card, index) => {
                card.style.animationDelay = `${index * 0.1}s`;
            });
        });
        
        function getInitials(name) {
            return name.split(' ').map(n => n[0]).join('').toUpperCase().substring(0, 2);
        }
        
        // Плавная прокрутка по якорям
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();
                const targetId = this.getAttribute('href').substring(1);
                const targetElement = document.getElementById(targetId);
                if (targetElement) {
                    targetElement.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
    </script>
</body>
</html>

<?php
function getInitials($name) {
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