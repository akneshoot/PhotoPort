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

$search_name = isset($_GET['name']) ? $_GET['name'] : '';
$search_country = isset($_GET['country']) ? $_GET['country'] : '';
$search_city = isset($_GET['city']) ? $_GET['city'] : '';

// Подготовка запроса с фильтрацией
$query = $pdo->prepare("
    SELECT users.name, users.country, users.city
    FROM users
    JOIN roles ON users.role_id = roles.id
    WHERE roles.name = 'photographer'
    AND users.name LIKE :name
    AND users.country LIKE :country
    AND users.city LIKE :city
");

$query->execute([
    'name' => "%$search_name%",
    'country' => "%$search_country%",
    'city' => "%$search_city%",
]);

$photographers = $query->fetchAll(PDO::FETCH_ASSOC);

// Возвращаем результаты в формате JSON
echo json_encode($photographers);
?>
