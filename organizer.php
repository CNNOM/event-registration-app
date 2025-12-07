<?php
require_once 'config.php';
require_once 'db.php';

checkRole([ROLE_ORGANIZER, ROLE_ADMIN]);
$user = getCurrentUser();

// Создание мероприятия
$success = false;
$error = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_event'])) {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $date = $_POST['date'];
    $time = $_POST['time'];
    $location = trim($_POST['location']);
    $max_participants = $_POST['max_participants'] ?: null;
    
    if (empty($title) || empty($date) || empty($time) || empty($location)) {
        $error = "Заполните все обязательные поля";
    } elseif (strtotime($date . ' ' . $time) < time()) {
        $error = "Дата мероприятия не может быть в прошлом";
    } else {
        $stmt = $db->prepare("INSERT INTO events (title, description, date, time, location, max_participants, organizer_id) 
                             VALUES (:title, :desc, :date, :time, :location, :max, :org)");
        $stmt->bindValue(':title', $title);
        $stmt->bindValue(':desc', $description);
        $stmt->bindValue(':date', $date);
        $stmt->bindValue(':time', $time);
        $stmt->bindValue(':location', $location);
        $stmt->bindValue(':max', $max_participants);
        $stmt->bindValue(':org', $user['id']);
        
        if ($stmt->execute()) {
            $success = "Мероприятие успешно создано!";
        } else {
            $error = "Ошибка при создании мероприятия";
        }
    }
}

// Получаем мероприятия организатора
$myEvents = $db->query("
    SELECT e.*, COUNT(r.id) as registered 
    FROM events e 
    LEFT JOIN registrations r ON e.id = r.event_id 
    WHERE e.organizer_id = {$user['id']} 
    GROUP BY e.id
    ORDER BY e.date ASC
");

// Статистика организатора
$totalEvents = $db->querySingle("SELECT COUNT(*) FROM events WHERE organizer_id = {$user['id']}");
$activeEvents = $db->querySingle("SELECT COUNT(*) FROM events WHERE organizer_id = {$user['id']} AND date >= date('now')");
$totalParticipants = $db->querySingle("
    SELECT COUNT(r.id) FROM registrations r 
    JOIN events e ON r.event_id = e.id 
    WHERE e.organizer_id = {$user['id']}
");
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Панель организатора</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .organizer-header {
            background: linear-gradient(135deg, #8b5cf6, #7c3aed);
            color: white;
            padding: 40px;
            border-radius: var(--border-radius);
            margin-bottom: 30px;
            box-shadow: var(--shadow);
        }
        
        .organizer-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 20px;
            margin: 30px 0;
        }
        
        .organizer-stat {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            backdrop-filter: blur(10px);
        }
        
        .organizer-stat-number {
            font-size: 32px;
            font-weight: bold;
            margin: 10px 0;
        }
        
        .create-event-form {
            background: white;
            border-radius: var(--border-radius);
            padding: 30px;
            margin: 30px 0;
            box-shadow: var(--shadow);
        }
        
        .form-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 30px;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 15px;
        }
        
        .tab {
            padding: 12px 24px;
            background: none;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            color: var(--gray-color);
            transition: var(--transition);
        }
        
        .tab.active {
            background: var(--primary-color);
            color: white;
        }
        
        .event-actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        
        .participants-list {
            background: #f8fafc;
            border-radius: 8px;
            padding: 15px;
            margin-top: 15px;
        }
        
        .participant-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .participant-item:last-child {
            border-bottom: none;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px;
            color: var(--gray-color);
        }
        
        .empty-state i {
            font-size: 48px;
            margin-bottom: 15px;
            color: #e2e8f0;
        }
        
        .event-status {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
            margin-left: 10px;
        }
        
        .status-upcoming { background: #dbeafe; color: #1d4ed8; }
        .status-ongoing { background: #fef3c7; color: #d97706; }
        .status-completed { background: #f3f4f6; color: #6b7280; }
        
        .qr-code {
            width: 100px;
            height: 100px;
            background: #f3f4f6;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 15px auto;
        }
        
        .download-btn {
            background: #10b981;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .date-picker {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        
        @media (max-width: 768px) {
            .date-picker {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container fade-in">
        <!-- Шапка панели организатора -->
        <div class="organizer-header">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <div>
                    <h1 style="margin: 0; font-size: 28px;">
                        <i class="fas fa-tasks"></i> Панель организатора
                    </h1>
                    <p style="opacity: 0.9; margin: 5px 0 0 0;">
                        Управляйте своими мероприятиями и участниками
                    </p>
                </div>
                <div>
                    <a href="index.php" class="btn btn-outline" style="background: rgba(255,255,255,0.1);">
                        <i class="fas fa-home"></i> На главную
                    </a>
                </div>
            </div>
            
            <!-- Статистика организатора -->
            <div class="organizer-stats">
                <div class="organizer-stat">
                    <div style="font-size: 14px; opacity: 0.9;">Всего мероприятий</div>
                    <div class="organizer-stat-number"><?= $totalEvents ?></div>
                </div>
                <div class="organizer-stat">
                    <div style="font-size: 14px; opacity: 0.9;">Активных</div>
                    <div class="organizer-stat-number"><?= $activeEvents ?></div>
                </div>
                <div class="organizer-stat">
                    <div style="font-size: 14px; opacity: 0.9;">Всего участников</div>
                    <div class="organizer-stat-number"><?= $totalParticipants ?></div>
                </div>
                <div class="organizer-stat">
                    <div style="font-size: 14px; opacity: 0.9;">Средняя посещаемость</div>
                    <div class="organizer-stat-number">
                        <?= $totalEvents > 0 ? round($totalParticipants / $totalEvents) : 0 ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Сообщения -->
        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?= $success ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?= $error ?>
            </div>
        <?php endif; ?>

        <!-- Форма создания мероприятия -->
        <div class="create-event-form">
            <h2 style="color: var(--primary-color); margin-bottom: 30px;">
                <i class="fas fa-plus-circle"></i> Создать новое мероприятие
            </h2>
            
            <form method="POST" id="createEventForm">
                <input type="hidden" name="create_event" value="1">
                
                <div class="form-group">
                    <label class="form-label">Название мероприятия *</label>
                    <input type="text" name="title" class="form-control" 
                           placeholder="Введите название мероприятия" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Описание</label>
                    <textarea name="description" class="form-control" rows="4" 
                              placeholder="Опишите детали мероприятия..."></textarea>
                </div>
                
                <div class="date-picker">
                    <div class="form-group">
                        <label class="form-label">Дата проведения *</label>
                        <input type="date" name="date" class="form-control" required
                               min="<?= date('Y-m-d') ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Время *</label>
                        <input type="time" name="time" class="form-control" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Место проведения *</label>
                    <input type="text" name="location" class="form-control" 
                           placeholder="Укажите адрес или онлайн-платформу" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Максимальное количество участников</label>
                    <input type="number" name="max_participants" class="form-control" 
                           min="1" placeholder="Оставьте пустым для неограниченного количества">
                    <div style="font-size: 12px; color: var(--gray-color); margin-top: 5px;">
                        <i class="fas fa-info-circle"></i> Необязательное поле
                    </div>
                </div>
                
                <div style="display: flex; gap: 15px; margin-top: 30px;">
                    <button type="submit" class="btn" style="flex: 1;">
                        <i class="fas fa-calendar-plus"></i> Создать мероприятие
                    </button>
                    <button type="reset" class="btn btn-outline" style="flex: 1;">
                        <i class="fas fa-redo"></i> Очистить форму
                    </button>
                </div>
            </form>
        </div>

        <!-- Мои мероприятия -->
        <div style="margin: 40px 0;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2 style="color: var(--primary-color); margin: 0;">
                    <i class="fas fa-calendar"></i> Мои мероприятия
                </h2>
                <div style="color: var(--gray-color);">
                    <?= $totalEvents ?> мероприятий
                </div>
            </div>
            
            <?php if ($totalEvents > 0): ?>
                <div class="grid">
                    <?php while ($event = $myEvents->fetchArray(SQLITE3_ASSOC)): 
                        $eventDate = new DateTime($event['date'] . ' ' . $event['time']);
                        $now = new DateTime();
                        $daysDiff = $now->diff($eventDate)->days;
                        $isPast = $eventDate < $now;
                        $isFull = $event['max_participants'] && $event['registered'] >= $event['max_participants'];
                        $percentage = $event['max_participants'] ? round(($event['registered'] / $event['max_participants']) * 100) : 0;
                        
                        // Определяем статус
                        if ($isPast) {
                            $status = 'completed';
                            $statusText = 'Завершено';
                        } elseif ($daysDiff <= 7) {
                            $status = 'ongoing';
                            $statusText = 'Скоро';
                        } else {
                            $status = 'upcoming';
                            $statusText = 'Предстоит';
                        }
                        ?>
                        
                        <div class="card">
                            <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 15px;">
                                <h3 style="margin: 0; color: var(--primary-color);">
                                    <?= htmlspecialchars($event['title']) ?>
                                </h3>
                                <span class="event-status status-<?= $status ?>">
                                    <?= $statusText ?>
                                </span>
                            </div>
                            
                            <p style="color: var(--dark-color); margin-bottom: 15px;">
                                <?= htmlspecialchars($event['description']) ?>
                            </p>
                            
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin: 15px 0;">
                                <div style="background: rgba(67, 97, 238, 0.1); padding: 12px; border-radius: 8px;">
                                    <div style="font-size: 12px; color: var(--gray-color);">Дата</div>
                                    <div style="font-weight: 600;">
                                        <i class="far fa-calendar"></i> <?= date('d.m.Y', strtotime($event['date'])) ?>
                                    </div>
                                </div>
                                <div style="background: rgba(67, 97, 238, 0.1); padding: 12px; border-radius: 8px;">
                                    <div style="font-size: 12px; color: var(--gray-color);">Время</div>
                                    <div style="font-weight: 600;">
                                        <i class="far fa-clock"></i> <?= $event['time'] ?>
                                    </div>
                                </div>
                            </div>
                            
                            <div style="background: rgba(67, 97, 238, 0.1); padding: 15px; border-radius: 8px; margin: 15px 0;">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                                    <span style="font-weight: 600;">
                                        <i class="fas fa-map-marker-alt"></i> Место
                                    </span>
                                    <span><?= htmlspecialchars($event['location']) ?></span>
                                </div>
                                
                                <?php if ($event['max_participants']): ?>
                                    <div style="margin: 10px 0;">
                                        <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                                            <span>Участники: <?= $event['registered'] ?>/<?= $event['max_participants'] ?></span>
                                            <span><?= $percentage ?>%</span>
                                        </div>
                                        <div class="progress-bar">
                                            <div class="progress-fill" style="width: <?= min($percentage, 100) ?>%;"></div>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <div style="margin: 10px 0;">
                                        <span>Участников: <?= $event['registered'] ?> (без ограничений)</span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Действия -->
                            <div class="event-actions">
                                <?php if (!$isPast): ?>
                                    <form action="send_notifications.php" method="POST" style="flex: 1;">
                                        <input type="hidden" name="event_id" value="<?= $event['id'] ?>">
                                        <button type="submit" class="btn" style="width: 100%;">
                                            <i class="fas fa-bell"></i> Уведомить
                                        </button>
                                    </form>
                                    
                                    <button class="btn btn-outline" onclick="editEvent(<?= $event['id'] ?>)" style="flex: 1;">
                                        <i class="fas fa-edit"></i> Редактировать
                                    </button>
                                <?php endif; ?>
                                
                                <button class="btn btn-outline" onclick="showParticipants(<?= $event['id'] ?>)" style="flex: 1;">
                                    <i class="fas fa-users"></i> Участники
                                </button>
                            </div>
                            
                            <!-- QR код для проверки -->
                            <?php if (!$isPast): ?>
                                <div style="text-align: center; margin-top: 20px; padding-top: 15px; border-top: 1px solid #e2e8f0;">
                                    <div style="font-size: 12px; color: var(--gray-color); margin-bottom: 10px;">
                                        QR код для быстрой регистрации
                                    </div>
                                    <div class="qr-code">
                                        <i class="fas fa-qrcode" style="font-size: 40px; color: var(--gray-color);"></i>
                                    </div>
                                    <button class="download-btn" onclick="downloadQR(<?= $event['id'] ?>)">
                                        <i class="fas fa-download"></i> Скачать QR
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="empty-state card">
                    <i class="fas fa-calendar-plus"></i>
                    <h3>У вас еще нет мероприятий</h3>
                    <p>Создайте свое первое мероприятие с помощью формы выше</p>
                    <button class="btn" onclick="document.getElementById('createEventForm').scrollIntoView({behavior: 'smooth'})">
                        <i class="fas fa-plus"></i> Создать мероприятие
                    </button>
                </div>
            <?php endif; ?>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>© 2024 EventManager - Панель организатора</p>
            <p style="font-size: 12px; margin-top: 10px; opacity: 0.8;">
                <i class="fas fa-user-tie"></i> Организатор: <?= htmlspecialchars($user['name']) ?>
                | <i class="fas fa-envelope"></i> <?= htmlspecialchars($user['email']) ?>
            </p>
        </div>
    </div>

    <!-- Модальное окно для участников -->
    <div id="participantsModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; 
         background: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center;">
        <div style="background: white; border-radius: var(--border-radius); padding: 30px; 
             max-width: 600px; width: 90%; max-height: 80vh; overflow-y: auto;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h3 style="margin: 0; color: var(--primary-color);">
                    <i class="fas fa-users"></i> Список участников
                </h3>
                <button onclick="closeModal()" style="background: none; border: none; font-size: 20px; 
                        color: var(--gray-color); cursor: pointer;">&times;</button>
            </div>
            <div id="participantsList"></div>
        </div>
    </div>

    <script>
        let currentEventId = null;
        
        function showParticipants(eventId) {
            currentEventId = eventId;
            const modal = document.getElementById('participantsModal');
            const list = document.getElementById('participantsList');
            
            // Здесь можно загрузить данные через AJAX
            // Для демонстрации покажем заглушку
            list.innerHTML = `
                <div style="text-align: center; padding: 40px;">
                    <i class="fas fa-spinner fa-spin" style="font-size: 40px; color: var(--primary-color);"></i>
                    <p>Загрузка списка участников...</p>
                </div>
            `;
            
            modal.style.display = 'flex';
            
            // Симуляция загрузки данных
            setTimeout(() => {
                list.innerHTML = `
                    <div class="participants-list">
                        <div class="participant-item">
                            <div>
                                <div style="font-weight: 600;">Иван Иванов</div>
                                <div style="font-size: 14px; color: var(--gray-color);">ivan@example.com</div>
                            </div>
                            <div style="font-size: 14px; color: var(--gray-color);">
                                Зарегистрирован: 05.12.2024
                            </div>
                        </div>
                        <div class="participant-item">
                            <div>
                                <div style="font-weight: 600;">Мария Петрова</div>
                                <div style="font-size: 14px; color: var(--gray-color);">maria@example.com</div>
                            </div>
                            <div style="font-size: 14px; color: var(--gray-color);">
                                Зарегистрирован: 06.12.2024
                            </div>
                        </div>
                        <div class="participant-item">
                            <div>
                                <div style="font-weight: 600;">Алексей Сидоров</div>
                                <div style="font-size: 14px; color: var(--gray-color);">alex@example.com</div>
                            </div>
                            <div style="font-size: 14px; color: var(--gray-color);">
                                Зарегистрирован: 07.12.2024
                            </div>
                        </div>
                    </div>
                    <div style="margin-top: 20px; text-align: center;">
                        <button class="download-btn" onclick="exportParticipants(${eventId})">
                            <i class="fas fa-file-export"></i> Экспорт в Excel
                        </button>
                    </div>
                `;
            }, 1000);
        }
        
        function closeModal() {
            document.getElementById('participantsModal').style.display = 'none';
        }
        
        function editEvent(eventId) {
            alert(`Редактирование мероприятия ID: ${eventId}\nФункция в разработке`);
        }
        
        function downloadQR(eventId) {
            alert(`Скачивание QR кода для мероприятия ID: ${eventId}\nФункция в разработке`);
        }
        
        function exportParticipants(eventId) {
            alert(`Экспорт участников мероприятия ID: ${eventId}\nФункция в разработке`);
        }
        
        // Закрытие модального окна по клику вне его
        document.getElementById('participantsModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
        
        // Настройка минимальной даты
        document.addEventListener('DOMContentLoaded', function() {
            const dateInput = document.querySelector('input[name="date"]');
            const today = new Date().toISOString().split('T')[0];
            dateInput.min = today;
            
            // Анимация карточек
            const cards = document.querySelectorAll('.card');
            cards.forEach((card, index) => {
                card.style.animationDelay = `${index * 0.1}s`;
            });
        });
    </script>
</body>
</html>