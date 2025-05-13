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

// Проверка авторизации
if (!isset($_SESSION['user_name'])) {
    header("Location: auth.php");
    exit;
}

// Получаем данные пользователя и его роль
$user_name = $_SESSION['user_name'];
$query = $pdo->prepare("
    SELECT users.*, roles.name as role_name 
    FROM users 
    JOIN roles ON users.role_id = roles.id 
    WHERE users.name = :name
");
$query->execute(['name' => $user_name]);
$user_data = $query->fetch(PDO::FETCH_ASSOC);

if (!$user_data) {
    die("Пользователь не найден.");
}

// Получаем список всех жанров
$all_genres_query = $pdo->query("SELECT * FROM genres");
$all_genres = $all_genres_query->fetchAll(PDO::FETCH_ASSOC);

// Получаем текущие жанры фотографа
$current_genres_query = $pdo->prepare("
    SELECT genre_id FROM photographer_genres WHERE photographer_id = :photographer_id
");
$current_genres_query->execute(['photographer_id' => $user_data['id']]);
$current_genres = $current_genres_query->fetchAll(PDO::FETCH_COLUMN);

// Проверка, является ли пользователь фотографом
$is_photographer = ($user_data['role_name'] === 'photographer');

$error_message = "";

// Если нажата кнопка "Сохранить изменения"
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $new_name = $_POST['name'];
    $new_email = $_POST['email'];

    $old_password = $_POST['old_password'] ?? null;
    $new_password = $_POST['new_password'] ?? null;

    // Проверяем, хочет ли пользователь изменить пароль
    if (!empty($new_password)) {
        if (empty($old_password) || !password_verify($old_password, $user_data['password'])) {
            $error_message = "Неверный старый пароль!";
        } else {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        }
    } else {
        $hashed_password = $user_data['password']; // Оставляем старый пароль
    }

    if (!$error_message) {
        // Обновляем описание для всех пользователей
        $new_description = $_POST['description'] ?? $user_data['description'];

        // Обновляем фото профиля, если загружено новое
        if (!empty($_FILES['profile_picture']['name'])) {
            $target_dir = "uploads/";
            $target_file = $target_dir . basename($_FILES["profile_picture"]["name"]);

            if (move_uploaded_file($_FILES["profile_picture"]["tmp_name"], $target_file)) {
                $profile_picture = $target_file;
            } else {
                $profile_picture = $user_data['profile_picture'];
            }
        } else {
            $profile_picture = $user_data['profile_picture'];
        }

        // Обновляем данные в базе
        if ($is_photographer) {
            
            
            // Обновляем город и страну только для фотографов
            $new_country = $_POST['country'] ?? $user_data['country'];
            $new_city = $_POST['city'] ?? $user_data['city'];
            $new_service_cost = isset($_POST['service_cost']) && $_POST['service_cost'] !== ''
                ? (float)$_POST['service_cost']
                : null;



            // Обновляем жанры для фотографа
            $selected_genres = $_POST['genres'] ?? [];

            // Удаляем старые жанры
            $delete_query = $pdo->prepare("DELETE FROM photographer_genres WHERE photographer_id = :photographer_id");
            $delete_query->execute(['photographer_id' => $user_data['id']]);

            // Добавляем новые жанры
            $insert_query = $pdo->prepare("INSERT INTO photographer_genres (photographer_id, genre_id) VALUES (:photographer_id, :genre_id)");
            foreach ($selected_genres as $genre_id) {
                $insert_query->execute(['photographer_id' => $user_data['id'], 'genre_id' => $genre_id]);
            }

            // Обновляем профиль фотографа
            $update_query = $pdo->prepare("
                UPDATE users 
                SET name = :name, email = :email, password = :password, description = :description, 
                    profile_picture = :profile_picture, country = :country, city = :city, service_cost = :service_cost
                WHERE id = :id
            ");

            $update_query->execute([
                'name' => $new_name,
                'email' => $new_email,
                'password' => $hashed_password,
                'description' => $new_description,
                'profile_picture' => $profile_picture,
                'country' => $new_country,
                'city' => $new_city,
                'service_cost' => $new_service_cost,
                'id' => $user_data['id']
            ]);

        } else {
            // Для клиентов без города и страны
            $update_query = $pdo->prepare("
                UPDATE users 
                SET name = :name, email = :email, password = :password, description = :description, 
                    profile_picture = :profile_picture
                WHERE id = :id
            ");
            $update_query->execute([
                'name' => $new_name,
                'email' => $new_email,
                'password' => $hashed_password,
                'description' => $new_description,
                'profile_picture' => $profile_picture,
                'id' => $user_data['id']
            ]);
        }

        // Обновляем сессию
        $_SESSION['user_name'] = $new_name;

        header("Location: profile.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Редактирование профиля</title>
    <link rel="icon" type="image/x-icon" href="images/1.ico" />
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="styles/css/update.css">
</head>
<body>
    <div class="container">
        <a href="profile.php" class="invite-link invite-link-black">Назад</a>
        <h2>Редактирование профиля</h2>
        <?php if (!empty($error_message)): ?>
            <div class="error-message">
                <?= htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>


        <form action="update.php" method="POST" enctype="multipart/form-data">
            <label for="name">Имя:</label>
            <input type="text" name="name" value="<?= htmlspecialchars($user_data['name']); ?>" required>

            <label for="email">Email:</label>
            <input type="email" name="email" value="<?= htmlspecialchars($user_data['email']); ?>" required>

            <label for="old_password">Старый пароль (только если хотите изменить):</label>
            <input type="password" name="old_password" placeholder="Введите текущий пароль">

            <label for="new_password">Новый пароль:</label>
            <input type="password" name="new_password" placeholder="Введите новый пароль">

            <label for="description">Описание:</label>
            <textarea name="description"><?= htmlspecialchars($user_data['description']); ?></textarea>

            <?php if ($is_photographer): ?>
                <label for="service_cost">Стоимость услуг (в рублях):</label>
                <input type="number" name="service_cost" step="0.01" value="<?= htmlspecialchars($user_data['service_cost']); ?>">
                <label for="country">Страна:</label>
                <input type="text" name="country" value="<?= htmlspecialchars($user_data['country']); ?>">

                <label for="city">Город:</label>
                <input type="text" name="city" value="<?= htmlspecialchars($user_data['city']); ?>">

                <label for="genres">Выберите жанры:</label>
                <select name="genres[]" multiple>
                    <?php foreach ($all_genres as $genre): ?>
                        <option value="<?= $genre['id']; ?>" <?= in_array($genre['id'], $current_genres) ? 'selected' : ''; ?>>
                            <?= htmlspecialchars($genre['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            <?php endif; ?>

            <label for="profile_picture">Фото профиля:</label>
            <input type="file" name="profile_picture" accept="image/*">

            <button type="submit">Сохранить изменения</button>
        </form>

        <a href="logout.php">Выйти из профиля</a>
    </div>
    <script>
        // Если сообщение об ошибке существует, скрываем его через 5 секунд
        const errorBlock = document.getElementById("errorMessage");
        if (errorBlock) {
            setTimeout(() => {
                errorBlock.style.display = "none";
            }, 5000); // 5000 миллисекунд = 5 секунд
        }
    </script>

</body>
</html>
