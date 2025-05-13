<?php
session_start();
$host = 'localhost';
$dbname = 'photoport';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die(json_encode(['success' => false, 'error' => 'Ошибка подключения: ' . $e->getMessage()]));
}

if (!isset($_SESSION['user_name'])) {
    die(json_encode(['success' => false, 'error' => 'Вы не авторизованы']));
}

// Получаем ID пользователя
$query = $pdo->prepare("SELECT id FROM users WHERE name = :name");
$query->execute(['name' => $_SESSION['user_name']]);
$user_id = $query->fetchColumn();

if (!$user_id) {
    die(json_encode(['success' => false, 'error' => 'Пользователь не найден']));
}

// Получаем ID фото
$photo_id = $_POST['photo_id'] ?? null;
if (!$photo_id) {
    die(json_encode(['success' => false, 'error' => 'Нет ID фото']));
}

// Проверяем, ставил ли пользователь лайк
$check_like = $pdo->prepare("SELECT COUNT(*) FROM likes WHERE photo_id = :photo_id AND user_id = :user_id");
$check_like->execute(['photo_id' => $photo_id, 'user_id' => $user_id]);
$liked = $check_like->fetchColumn() > 0;

if ($liked) {
    // Если лайк уже есть - удаляем
    $delete_like = $pdo->prepare("DELETE FROM likes WHERE photo_id = :photo_id AND user_id = :user_id");
    $delete_like->execute(['photo_id' => $photo_id, 'user_id' => $user_id]);
} else {
    // Если лайка нет - добавляем
    $insert_like = $pdo->prepare("INSERT INTO likes (photo_id, user_id) VALUES (:photo_id, :user_id)");
    $insert_like->execute(['photo_id' => $photo_id, 'user_id' => $user_id]);
}

// Получаем обновленное количество лайков
$likes_query = $pdo->prepare("SELECT COUNT(*) FROM likes WHERE photo_id = :photo_id");
$likes_query->execute(['photo_id' => $photo_id]);
$like_count = $likes_query->fetchColumn();

// Отправляем JSON-ответ
echo json_encode([
    'success' => true,
    'likes' => $like_count,
    'liked' => !$liked // Меняем статус лайка
]);
