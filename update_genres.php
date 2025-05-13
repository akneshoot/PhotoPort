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

if (!isset($_SESSION['user_name'])) {
    die("Ошибка: не авторизован");
}

$user_id = $_POST['user_id'] ?? null;
$genres = $_POST['genres'] ?? [];

if (!$user_id) {
    die("Ошибка: неверный пользователь");
}

// Удаляем старые жанры
$delete_query = $pdo->prepare("DELETE FROM photographer_genres WHERE photographer_id = :photographer_id");
$delete_query->execute(['photographer_id' => $user_id]);

// Добавляем новые жанры
$insert_query = $pdo->prepare("INSERT INTO photographer_genres (photographer_id, genre_id) VALUES (:photographer_id, :genre_id)");
foreach ($genres as $genre_id) {
    $insert_query->execute(['photographer_id' => $user_id, 'genre_id' => $genre_id]);
}

header("Location: profile.php");
exit();
?>
