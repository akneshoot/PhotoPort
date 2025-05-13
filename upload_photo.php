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

// Получаем данные пользователя
$query = $pdo->prepare("SELECT * FROM users WHERE name = :name");
$query->execute(['name' => $user_name]);
$user_data = $query->fetch(PDO::FETCH_ASSOC);
$user_id = $user_data['id'] ?? null;
$user_role = $user_data['role_id'] ?? null; // 1 - клиент, 2 - фотограф

if (!$user_id) {
    die("Ошибка: пользователь не найден.");
}

// Проверяем, была ли отправлена форма
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['new_photo'])) {
    $photo_user_id = !empty($_POST['photo_user']) ? $_POST['photo_user'] : null; // ID выбранного фотографа/клиента
    $alt_text = $_POST['alt_text'] ?? '';

    // Загружаем файл
    if ($_FILES['new_photo']['error'] !== UPLOAD_ERR_OK) {
        die("Ошибка загрузки файла.");
    }

    // Генерируем имя файла
    $file_ext = pathinfo($_FILES['new_photo']['name'], PATHINFO_EXTENSION);
    $new_file_name = $user_name . '_фото_' . time() . '.' . $file_ext;
    $upload_dir = 'uploads/';
    
    // Создаем папку, если ее нет
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $photo_url = $upload_dir . $new_file_name;

    // Перемещаем загруженный файл
    if (!move_uploaded_file($_FILES['new_photo']['tmp_name'], $photo_url)) {
        die("Ошибка сохранения файла.");
    }

    // Сохраняем данные в БД
    $stmt = $pdo->prepare("INSERT INTO photos (url, alt, user_id, photo_user_id) VALUES (:url, :alt, :user_id, :photo_user_id)");
    $stmt->execute([
        'url' => $photo_url,
        'alt' => $alt_text,
        'user_id' => $user_id,
        'photo_user_id' => $photo_user_id // Можем передать NULL, если не выбрали пользователя
    ]);

    // Перенаправляем обратно в портфолио
    header("Location: edit_portfolio.php");
    exit;
} else {
    die("Ошибка: некорректный запрос.");
}


?>