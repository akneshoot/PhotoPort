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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $photo_id = $_POST['photo_id'] ?? null;
    $alt_text = $_POST['alt_text'] ?? '';
    $photo_user_id = !empty($_POST['photo_user']) ? (int)$_POST['photo_user'] : null;

    if ($photo_id && $alt_text !== '') {
        $stmt = $pdo->prepare("UPDATE photos SET alt = :alt, photo_user_id = :photo_user_id WHERE id = :photo_id");
        $stmt->execute([
            'alt' => $alt_text,
            'photo_user_id' => $photo_user_id,
            'photo_id' => $photo_id
        ]);
    }
}

header("Location: edit_portfolio.php");
exit;
