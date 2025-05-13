<?php
session_start();
$is_logged_in = isset($_SESSION['user_name']);
if ($is_logged_in) {
    $user_id = $_SESSION['user_id']; // Полагаем, что id пользователя хранится в сессии
    $photographer_id = $_GET['photographer_id'];
    
    // Проверяем, добавлен ли фотограф в избранное
    $query = $pdo->prepare("SELECT id FROM favorites WHERE user_id = :user_id AND photographer_id = :photographer_id");
    $query->execute(['user_id' => $user_id, 'photographer_id' => $photographer_id]);
    $is_favorite = $query->fetch();

    echo json_encode(['is_favorite' => (bool)$is_favorite]);
}
?>
