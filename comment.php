<?php
session_start();
header('Content-Type: application/json');

$host = 'localhost';
$dbname = 'photoport';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Ошибка подключения: ' . $e->getMessage()]);
    exit();
}

// Проверка авторизации
if (!isset($_SESSION['user_name'])) {
    echo json_encode(['success' => false, 'error' => 'Вы не авторизованы!']);
    exit();
}

$user_name = $_SESSION['user_name'];
$photo_id = $_POST['photo_id'] ?? null;
$comment = trim($_POST['comment'] ?? '');

if (!$photo_id || empty($comment)) {
    echo json_encode(['success' => false, 'error' => 'Ошибка: некорректные данные']);
    exit();
}

// Получаем ID пользователя
$query = $pdo->prepare("SELECT id FROM users WHERE name = :name");
$query->bindParam(':name', $user_name, PDO::PARAM_STR);
$query->execute();
$user_id = $query->fetchColumn();

if (!$user_id) {
    echo json_encode(['success' => false, 'error' => 'Ошибка: пользователь не найден']);
    exit();
}

// Добавляем комментарий
$insert_query = $pdo->prepare("INSERT INTO comments (user_id, photo_id, comment) VALUES (:user_id, :photo_id, :comment)");
$insert_query->bindParam(':user_id', $user_id, PDO::PARAM_INT);
$insert_query->bindParam(':photo_id', $photo_id, PDO::PARAM_INT);
$insert_query->bindParam(':comment', $comment, PDO::PARAM_STR);
$insert_query->execute();

// Возвращаем новый комментарий
echo json_encode([
    'success' => true,
    'comment' => [
        'name' => $user_name,
        'text' => $comment
    ]
]);
exit();
