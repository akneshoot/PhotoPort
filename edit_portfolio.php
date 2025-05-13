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

// Получаем имя пользователя из сессии
$user_name = $_SESSION['user_name'] ?? 'Гость';

// Получаем данные пользователя
$query = $pdo->prepare("SELECT * FROM users WHERE name = :name");
$query->execute(['name' => $user_name]);
$user_data = $query->fetch(PDO::FETCH_ASSOC);
$user_id = $user_data['id'] ?? null;
$user_role = $user_data['role_id'] ?? null; // 1 - клиент, 2 - фотограф

// Определяем, кого может выбирать текущий пользователь
if ($user_role == 2) {
    // Если пользователь — фотограф, он выбирает клиентов
    $query = $pdo->prepare("SELECT id, name FROM users WHERE role_id = 1"); 
} else {
    // Если пользователь — клиент, он выбирает фотографов
    $query = $pdo->prepare("SELECT id, name FROM users WHERE role_id = 2");
}

$query->execute();
$available_users = $query->fetchAll(PDO::FETCH_ASSOC);

// Получаем фотографии пользователя
$query = $pdo->prepare(
    "SELECT p.id, p.url, p.alt, 
            pu.name AS photographer_name, 
            pc.name AS client_name
     FROM photos p
     LEFT JOIN users pu ON (p.user_id = pu.id AND pu.role_id = 2) OR (p.photo_user_id = pu.id AND pu.role_id = 2)
     LEFT JOIN users pc ON (p.user_id = pc.id AND pc.role_id = 1) OR (p.photo_user_id = pc.id AND pc.role_id = 1)
     WHERE p.user_id = :user_id OR p.photo_user_id = :user_id"
);
$query->execute(['user_id' => $user_id]);
$photos = $query->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Мое портфолио</title>
    <link rel="icon" type="image/x-icon" href="images/1.ico" />
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="styles/css/edit.css">
</head>
<body>
    <header>
        <h1>Мое портфолио</h1>
        <a href="profile.php" class="invite-link invite-link-white">Назад</a>
    </header>

    <section>
        <h2>Добавить новую фотографию</h2>
        <form action="upload_photo.php" method="POST" enctype="multipart/form-data">
            <label for="new_photo">Фотография:</label>
            <input type="file" name="new_photo" id="new_photo" required>

            <label for="alt_text">Описание фотографии:</label>
            <input type="text" name="alt_text" id="alt_text" required>

            <label for="photo_user">
                <?php if ($user_role == 2): ?>
                    Выберите клиента (не обязательно):
                <?php else: ?>
                    Выберите фотографа (не обязательно):
                <?php endif; ?>
            </label>
            <select name="photo_user" id="photo_user">
                <option value="">-- Не указывать --</option>
                <?php foreach ($available_users as $user): ?>
                    <option value="<?= $user['id']; ?>"><?= htmlspecialchars($user['name']); ?></option>
                <?php endforeach; ?>
            </select>

            <button type="submit" class="btn btn-black">Добавить фотографию</button>
        </form>

    </section>

    <section>
        <h2>Ваши фотографии</h2>
        <div class="photo-grid">
            <?php foreach ($photos as $photo): ?>
                <div class="photo-item">
                    <img src="<?= htmlspecialchars($photo['url']); ?>" alt="<?= htmlspecialchars($photo['alt']); ?>" class="photo-img">
                    <p><?= htmlspecialchars($photo['alt']); ?></p>
                    <p>Фотограф: <?= htmlspecialchars($photo['photographer_name'] ?? 'Не указан'); ?></p>
                    <p>Клиент: <?= htmlspecialchars($photo['client_name'] ?? 'Не указан'); ?></p>
                    <form action="delete_photo.php" method="POST">
                        <input type="hidden" name="photo_id" value="<?= $photo['id']; ?>">
                        <button type="submit">Удалить</button>
                    </form>

                </div>
            <?php endforeach; ?>
        </div>
    </section>

</body>
</html>