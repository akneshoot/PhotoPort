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

// Проверяем, авторизован ли пользователь
if (!isset($_SESSION['user_name'])) {
    die("Ошибка: пользователь не авторизован.");
}

$user_name = $_SESSION['user_name'];

// Получаем ID пользователя
$query = $pdo->prepare("SELECT id FROM users WHERE name = :name");
$query->execute(['name' => $user_name]);
$user_data = $query->fetch(PDO::FETCH_ASSOC);
$user_id = $user_data['id'] ?? null;

if (!$user_id) {
    die("Ошибка: пользователь не найден.");
}

// Проверяем, передан ли ID фото на удаление
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['photo_id'])) {
    $photo_id = $_POST['photo_id'];

    // Проверяем, принадлежит ли фото пользователю
    $query = $pdo->prepare("SELECT url FROM photos WHERE id = :id AND (user_id = :user_id OR photo_user_id = :user_id)");
    $query->execute(['id' => $photo_id, 'user_id' => $user_id]);
    $photo = $query->fetch(PDO::FETCH_ASSOC);

    if ($photo) {
        $photo_path = $photo['url'];

        // Удаляем фото из базы данных
        $stmt = $pdo->prepare("DELETE FROM photos WHERE id = :id");
        $stmt->execute(['id' => $photo_id]);

        // Удаляем файл с сервера
        if (file_exists($photo_path)) {
            unlink($photo_path);
        }

        header("Location: edit_portfolio.php");
        exit;
    } else {
        die("Ошибка: фото не найдено или у вас нет прав для его удаления.");
    }
} else {
    die("Ошибка: неверный запрос.");
}
?>
