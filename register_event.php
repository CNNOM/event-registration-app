<?php
require_once 'config.php';
require_once 'db.php';

checkAuth();
$user = getCurrentUser();

if (isset($_POST['event_id'])) {
    $eventId = (int)$_POST['event_id'];
    
    // 1. Проверяем не зарегистрирован ли уже
    $check = $db->prepare("SELECT id FROM registrations WHERE user_id = :user_id AND event_id = :event_id");
    $check->bindValue(':user_id', $user['id']);
    $check->bindValue(':event_id', $eventId);
    
    if (!$check->execute()->fetchArray()) {
        // 2. Проверяем лимит участников
        $event = $db->prepare("SELECT max_participants FROM events WHERE id = :event_id");
        $event->bindValue(':event_id', $eventId);
        $eventData = $event->execute()->fetchArray(SQLITE3_ASSOC);
        
        if ($eventData['max_participants'] !== null) {
            // Считаем текущее количество зарегистрированных
            $countStmt = $db->prepare("SELECT COUNT(*) as count FROM registrations WHERE event_id = :event_id");
            $countStmt->bindValue(':event_id', $eventId);
            $countResult = $countStmt->execute()->fetchArray(SQLITE3_ASSOC);
            $currentCount = $countResult['count'];
            
            if ($currentCount >= $eventData['max_participants']) {
                // Мест нет!
                $_SESSION['error'] = "К сожалению, все места на это мероприятие уже заняты.";
                header('Location: index.php');
                exit();
            }
        }
        
        // 3. Регистрируем
        $stmt = $db->prepare("INSERT INTO registrations (user_id, event_id) VALUES (:user_id, :event_id)");
        $stmt->bindValue(':user_id', $user['id']);
        $stmt->bindValue(':event_id', $eventId);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Вы успешно зарегистрированы на мероприятие!";
            
            // Отправка email (заглушка)
            /*
            $eventInfo = $db->query("SELECT title, date, time FROM events WHERE id = $eventId")->fetchArray(SQLITE3_ASSOC);
            mail($user['email'], "Регистрация на мероприятие", 
                 "Вы успешно зарегистрированы на мероприятие: " . $eventInfo['title']);
            */
        } else {
            $_SESSION['error'] = "Ошибка при регистрации";
        }
    } else {
        $_SESSION['error'] = "Вы уже зарегистрированы на это мероприятие";
    }
}

header('Location: index.php');
exit();
?>