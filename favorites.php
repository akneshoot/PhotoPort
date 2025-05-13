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

// Проверяем, вошел ли пользователь
$is_logged_in = isset($_SESSION['user_name']);
$user_name = $_SESSION['user_name'] ?? null;
$role = 'guest'; // По умолчанию - гость

// Проверяем, вошел ли пользователь в систему
if (!isset($_SESSION['user_name'])) {
    die("Ошибка: Вы должны войти в систему, чтобы просматривать избранное.");
}

$user_name = $_SESSION['user_name'];

// Получаем user_id текущего пользователя
$query = $pdo->prepare("SELECT id FROM users WHERE name = :user_name");
$query->execute(['user_name' => $user_name]);
$user = $query->fetch();

if (!$user) {
    die("Ошибка: Пользователь не найден.");
}

$user_id = $user['id'];

// Получаем список избранных фотографов
$query = $pdo->prepare("SELECT u.id, u.name, u.profile_picture 
                        FROM users u
                        JOIN favorites f ON u.id = f.photographer_id
                        WHERE f.user_id = :user_id");
$query->execute(['user_id' => $user_id]);

if (isset($_POST['remove_favorite'])) {
    $photographer_id = $_POST['photographer_id'];
    
    // Удаляем фотографа из избранного
    $query = $pdo->prepare("DELETE FROM favorites WHERE user_id = :user_id AND photographer_id = :photographer_id");
    $query->execute([
        'user_id' => $user_id,
        'photographer_id' => $photographer_id
    ]);
    
    // Перенаправляем на ту же страницу, чтобы обновить список
    header("Location: " . $_SERVER['REQUEST_URI']);
    exit();
}

$favorite_photographers = $query->fetchAll();

?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Мои избранные фотографы</title>
    <link rel="icon" type="image/x-icon" href="images/1.ico" />
    <link rel="stylesheet" href="styles/css/main.css">
    <link rel="stylesheet" href="styles/css/favorites.css">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <header>
        <div class="container">
            <nav class="navbar">
                <a href="index.php" class="logo">
                    <img src="images/3.png" alt="логотип">
                </a>
                <ul class="nav_list">
                    <li class="nav_list-item"><a href="index.php" class="nav_list-link">Главная</a></li>
                    <li class="nav_list-item"><a href="gallery.php" class="nav_list-link">Галерея</a></li>
                    <li class="nav_list-item"><a href="photographers.php" class="nav_list-link">Фотографы</a></li>
                    <li class="nav_list-item"><a href="studios.php" class="nav_list-link">Студии</a></li>
                    <?php if ($is_logged_in): ?>
                        <li class="nav_list-item"><a href="profile.php" class="nav_list-link">Профиль</a></li>
                    <?php endif; ?>
                </ul>
                <div class="user-info">
                    <?php if ($is_logged_in): ?>
                        <a href="logout.php" class="btn btn-black">Выйти</a>
                    <?php else: ?>
                        <a href="auth.php" class="btn btn-black">Войти</a>
                    <?php endif; ?>
                </div>
                <button class="menu_toggle">
                        <svg height="6" width="20" xmlns="http://www.w3.org/2000/svg" class="open">
                            <g fill-rule="evenodd">
                                <path d="M0 0h20v1H0zM0 5h20v1H0z"></path>
                            </g>
                        </svg>
                        <svg height="15" width="16" xmlns="http://www.w3.org/2000/svg" class="close">
                            <path
                                d="M14.718.075l.707.707L8.707 7.5l6.718 6.718-.707.707L8 8.207l-6.718 6.718-.707-.707L7.293 7.5.575.782l.707-.707L8 6.793 14.718.075z"
                                fill-rule="evenodd"></path>
                        </svg>
                </button>
                
            </nav>
        </div>
    </header>

    <div class="container w-full">
        <h1 class="text-4xl font-bold mb-6">Избранные фотографы</h1>
        <div class="photographer-grid">
            <?php if (count($favorite_photographers) > 0): ?>
                <?php foreach ($favorite_photographers as $photographer): ?>
                    <div class="photographer-card">
                        <div class="photographer-img-container">
                            <img src="<?= htmlspecialchars($photographer['profile_picture'] ?: 'images/default_profile.jpg'); ?>" alt="<?= htmlspecialchars($photographer['name']); ?>" class="photographer-img">
                        </div>
                        <p class="photographer-name">
                            <a href="photographer.php?id=<?= htmlspecialchars($photographer['id']); ?>" class="photographer-link">
                                <?= htmlspecialchars($photographer['name']); ?>
                            </a>
                        </p>

                        <form method="POST" action="">
                            <input type="hidden" name="photographer_id" value="<?= $photographer['id']; ?>">
                            <button type="submit" name="remove_favorite" class="remove-btn">Удалить</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="empty-message">У вас нет избранных фотографов.</p>
            <?php endif; ?>
        </div>
    </div>

    <footer>
        <div class="container">
            <div class="footer-row">
                <div class="footer_logo-box">
                    <a href="index.php" class="logo"><img src="images/4.png" alt="логотип"></a>
                </div>
                <div class="footer_menu-box">
                    <ul class="footer_menu">
                        <li class="footer_menu-item"><a class="footer_menu-link" href="index.php">Главная</a></li>
                        <li class="footer_menu-item">
                            <a class="footer_menu-link" href="gallery.php">Галерея</a>
                        </li>
                        <li class="footer_menu-item"><a class="footer_menu-link" href="photographers.php">Фотографы</a></li>
                        <li class="footer_menu-item"><a class="footer_menu-link" href="studios.php">Студии</a></li>
                        <?php if ($is_logged_in): ?>
                            <li class="footer_menu-item"><a class="footer_menu-link" href="profile.php">Профиль</a></li>
                        <?php endif; ?>
                    </ul>
                </div>
                <div class="footer_copyright">
                    <p class="opaque-grey">Номер для связи: +7-977-123-45-67</p>
                </div>
            </div>
        </div>
    </footer>

    <script src="js/app.js"></script>
</body>
</html>
