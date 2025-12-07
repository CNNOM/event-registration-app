<?php
require_once 'config.php';
require_once 'db.php';

checkRole([ROLE_ORGANIZER, ROLE_ADMIN]);

if (isset($_POST['event_id'])) {
    $eventId = (int)$_POST['event_id'];
    
    // Получаем информацию о мероприятии
    $event = $db->query("SELECT * FROM events WHERE id = $eventId")->fetchArray(SQLITE3_ASSOC);
    
    // Получаем участников мероприятия
    $participants = $db->query("
        SELECT u.email, u.name 
        FROM registrations r 
        JOIN users u ON r.user_id = u.id 
        WHERE r.event_id = $eventId
    ");
    
    // Отправляем уведомления (заглушка)
    while ($p = $participants->fetchArray(SQLITE3_ASSOC)) {
        $subject = "Напоминание: " . $event['title'];
        $message = "Здравствуйте, {$p['name']}!\n\n";
        $message .= "Напоминаем о мероприятии:\n";
        $message .= "Название: {$event['title']}\n";
        $message .= "Дата: {$event['date']} {$event['time']}\n";
        $message .= "Место: {$event['location']}\n\n";
        $message .= "Ждем вас!";
        
        // Реальная отправка email:
        // mail($p['email'], $subject, $message);
        
        // Для демонстрации выводим в лог
        error_log("Отправлено уведомление для {$p['email']}: $subject");
    }
    
    $_SESSION['notification'] = "Уведомления отправлены участникам";
}

header('Location: organizer.php');
?>