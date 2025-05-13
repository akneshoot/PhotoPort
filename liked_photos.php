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

// Проверяем, авторизован ли пользователь
if (!isset($_SESSION['user_name'])) {
    header("Location: auth.php");
    exit();
}

// Получаем ID пользователя
$user_name = $_SESSION['user_name'];
$query = $pdo->prepare("SELECT id FROM users WHERE name = :name");
$query->execute(['name' => $user_name]);
$user = $query->fetch(PDO::FETCH_ASSOC);
$user_id = $user['id'] ?? null;

// Если пользователя нет, редиректим
if (!$user_id) {
    header("Location: auth.php");
    exit();
}

// Получаем все лайкнутые фотографии
$query = $pdo->prepare("
    SELECT p.id, p.url, p.alt, u.name AS photographer_name, u.id AS photographer_id
    FROM likes l
    JOIN photos p ON l.photo_id = p.id
    JOIN users u ON p.user_id = u.id
    WHERE l.user_id = :user_id
");
$query->execute(['user_id' => $user_id]);
$liked_photos = $query->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Мои лайки</title>
    <link rel="icon" type="image/x-icon" href="images/1.ico" />
    <link rel="stylesheet" href="styles/css/main.css">
    <link rel="stylesheet" href="styles/css/likes.css">
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

    <!-- Модальное окно -->
    <div id="modal" class="modal">
        <span id="closeModal" class="modal-close">&times;</span>
        <img id="modalImage" class="modal-content" src="" alt="">
    </div>



    <section class="gallery">
        <div class="container-lg">
            <h1 class="text-4xl font-bold mb-6">Мои лайки</h1>
            <div class="grid">
                <?php if (count($liked_photos) > 0): ?>
                    <?php foreach ($liked_photos as $photo): ?>
                        <div class="image-container">
                            <div class="image open-modal" style="background-image: url('<?= htmlspecialchars($photo['url']); ?>');"
                                data-url="<?= htmlspecialchars($photo['url']); ?>">
                            </div>
                        </div>

                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="empty-message">Вы еще не лайкнули ни одной фотографии.</p>
                <?php endif; ?>
            </div>
        </div>
    </section>


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
    <script>
        // Открытие модального окна
        document.querySelectorAll('.open-modal').forEach(item => {
            item.addEventListener('click', function () {
                const imageUrl = this.getAttribute('data-url');
                document.getElementById('modalImage').setAttribute('src', imageUrl);
                document.getElementById('modal').style.display = 'flex';
            });
        });

        // Закрытие модального окна
        document.getElementById('closeModal').addEventListener('click', function () {
            document.getElementById('modal').style.display = 'none';
        });

        // Закрытие при клике вне изображения
        document.getElementById('modal').addEventListener('click', function (e) {
            if (e.target === this) {
                this.style.display = 'none';
            }
        });
    </script>

</body>
</html>
