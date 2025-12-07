<?php
require_once 'config.php';
require_once 'db.php';

checkAuth();
$user = getCurrentUser();

if (isset($_POST['event_id'])) {
    $eventId = (int)$_POST['event_id'];
    
    // Удаляем регистрацию пользователя
    $stmt = $db->prepare("DELETE FROM registrations WHERE user_id = :user_id AND event_id = :event_id");
    $stmt->bindValue(':user_id', $user['id']);
    $stmt->bindValue(':event_id', $eventId);
    $stmt->execute();
    
    // Отправка email подтверждения отмены (заглушка)
    /*
    $event = $db->query("SELECT title FROM events WHERE id = $eventId")->fetchArray(SQLITE3_ASSOC);
    mail($user['email'], "Отмена регистрации", 
         "Вы отменили регистрацию на мероприятие: " . $event['title']);
    */
}

// Возвращаемся на предыдущую страницу
if (isset($_SERVER['HTTP_REFERER'])) {
    header('Location: ' . $_SERVER['HTTP_REFERER']);
} else {
    header('Location: index.php');
}
exit();
?>