<?php
$db = new SQLite3('events.db');

// Создание таблиц при первом запуске
$db->exec("CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    email TEXT UNIQUE NOT NULL,
    password TEXT NOT NULL,
    name TEXT NOT NULL,
    role TEXT DEFAULT 'participant',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");

$db->exec("CREATE TABLE IF NOT EXISTS events (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL,
    description TEXT,
    date DATE NOT NULL,
    time TIME NOT NULL,
    location TEXT NOT NULL,
    max_participants INTEGER,
    organizer_id INTEGER,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (organizer_id) REFERENCES users(id)
)");

$db->exec("CREATE TABLE IF NOT EXISTS registrations (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    event_id INTEGER NOT NULL,
    status TEXT DEFAULT 'pending',
    registered_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (event_id) REFERENCES events(id)
)");

// Создаем тестового админа если его нет
$adminExists = $db->querySingle("SELECT COUNT(*) FROM users WHERE email = 'admin@events.com'");
if (!$adminExists) {
    $db->exec("INSERT INTO users (email, password, name, role) 
               VALUES ('admin@events.com', '" . password_hash('admin123', PASSWORD_DEFAULT) . "', 'Администратор', 'admin')");
}


// $users = [
//         ['email' => 'user1@example.com', 'password' => 'password123', 'name' => 'Иван Петров', 'role' => 'participant'],
//         ['email' => 'user2@example.com', 'password' => 'password123', 'name' => 'Мария Сидорова', 'role' => 'participant'],
//         ['email' => 'user3@example.com', 'password' => 'password123', 'name' => 'Алексей Иванов', 'role' => 'organizer'],
//         ['email' => 'user4@example.com', 'password' => 'password123', 'name' => 'Екатерина Смирнова', 'role' => 'participant'],
//         ['email' => 'user5@example.com', 'password' => 'password123', 'name' => 'Дмитрий Кузнецов', 'role' => 'organizer']
//     ];
?>