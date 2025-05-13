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
    die("Ошибка подключения: " . $e->getMessage());
}

// Проверяем, вошел ли пользователь в систему
if (!isset($_SESSION['user_name'])) {
    die(json_encode(['status' => 'error', 'message' => 'Вы должны войти в систему, чтобы добавлять в избранное.']));
}

$user_name = $_SESSION['user_name'];
$photographer_id = $_POST['photographer_id'] ?? null;

if (!$photographer_id) {
    die(json_encode(['status' => 'error', 'message' => 'Не указан фотограф.']));
}

// Получаем user_id текущего пользователя
$query = $pdo->prepare("SELECT id FROM users WHERE name = :user_name");
$query->execute(['user_name' => $user_name]);
$user = $query->fetch();

if (!$user) {
    die(json_encode(['status' => 'error', 'message' => 'Пользователь не найден.']));
}

$user_id = $user['id'];

// Проверяем, есть ли уже запись в избранном
$query = $pdo->prepare("SELECT id FROM favorites WHERE user_id = :user_id AND photographer_id = :photographer_id");
$query->execute([
    'user_id' => $user_id,
    'photographer_id' => $photographer_id
]);
$exists = $query->fetch();

if ($exists) {
    // Удаляем фотографа из избранного
    $query = $pdo->prepare("DELETE FROM favorites WHERE user_id = :user_id AND photographer_id = :photographer_id");
    $query->execute([
        'user_id' => $user_id,
        'photographer_id' => $photographer_id
    ]);
    echo json_encode(['status' => 'removed']);
} else {
    // Добавляем фотографа в избранное
    $query = $pdo->prepare("INSERT INTO favorites (user_id, photographer_id) VALUES (:user_id, :photographer_id)");
    $query->execute([
        'user_id' => $user_id,
        'photographer_id' => $photographer_id
    ]);
    echo json_encode(['status' => 'added']);
}
?>
