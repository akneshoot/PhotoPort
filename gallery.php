<?php
session_start();
$servername = "localhost";
$username = "root"; 
$password = ""; 
$dbname = "photoport";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Ошибка соединения: " . $conn->connect_error);
}

// Проверяем, вошел ли пользователь
$is_logged_in = isset($_SESSION['user_name']);
$user_name = $_SESSION['user_name'] ?? null;
$role = 'guest'; // По умолчанию - гость

// Если пользователь авторизован, получаем его роль
if ($is_logged_in) {
    $stmt = $conn->prepare("SELECT roles.name FROM users JOIN roles ON users.role_id = roles.id WHERE users.name = ?");
    $stmt->bind_param("s", $user_name);
    $stmt->execute();
    $stmt->bind_result($role);
    $stmt->fetch();
    $stmt->close();
}




// Добавляем параметры для фильтрации
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'newest'; // 'newest', 'likes', 'comments'
$sql_photos = "SELECT 
        photos.id,
        photos.url, 
        photos.alt, 
        photographers.id AS photographer_id, 
        photographers.name AS photographer_name, 
        clients.id AS client_id, 
        clients.name AS client_name,
        COUNT(DISTINCT likes.id) AS like_count,
        COUNT(DISTINCT comments.id) AS comment_count
    FROM photos 
    LEFT JOIN users AS photographers ON photos.user_id = photographers.id AND photographers.role_id = 2
    LEFT JOIN users AS clients ON photos.photo_user_id = clients.id AND clients.role_id = 1
    LEFT JOIN likes ON photos.id = likes.photo_id
    LEFT JOIN comments ON photos.id = comments.photo_id
    GROUP BY photos.id, photos.url, photos.alt, photographer_id, photographer_name, client_id, client_name";


// Формируем SQL-запрос с учётом фильтрации
if ($filter === 'likes') {
    $sql_photos .= " ORDER BY like_count DESC";
} elseif ($filter === 'comments') {
    $sql_photos .= " ORDER BY comment_count DESC";
} else {
    $sql_photos .= " ORDER BY photos.id DESC";
}

$result_photos = $conn->query($sql_photos);


?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PhotoPort - Галерея</title>
    <link rel="icon" type="image/x-icon" href="images/1.ico" />
    <link rel="stylesheet" href="styles/css/main.css">
    <link rel="stylesheet" href="styles/css/gallery.css">
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

<main>
    <!-- HERO SECTION -->
    <section class="hero">
            <div class="container">
                <div class="section_text">
                    <div class="section_text-box remove-padding">
                        <h5>Галерея</h5>
                        <h2 class="heading white">Возможности для вдохновления</h2>
                        <p class="opaque-grey">В разделе галереи каждый фотограф может легко добавлять фотографии, а клиенты смогут ознакомиться с подходящими работами.</p>
                            <?php if ($is_logged_in): ?>
                                <a href="profile.php" class="invite-link invite-link-white">Профиль</a>
                            <?php else: ?>
                                <a href="auth.php" class="invite-link invite-link-white">Войти</a>
                            <?php endif; ?>
                    </div>
                </div>
            </div>
        </section>

        <!-- HERO SECTION (MOBILE ONLY) -->
    <section class="hero-mobile">
            <div class="container">
                <div class="section_text section_text_black">
                    <div class="section_text-box remove-padding">
                        <h5>Галерея</h5>
                        <h2 class="heading white">Возможности для вдохновления</h2>
                        <p class="opaque-grey">В разделе галереи каждый фотограф может легко добавлять фотографии, а клиенты смогут ознакомиться с подходящими работами.</p>
                        <?php if ($is_logged_in): ?>
                                <a href="profile.php" class="invite-link invite-link-white">Профиль</a>
                            <?php else: ?>
                                <a href="auth.php" class="invite-link invite-link-white">Войти</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
    </section>

    <!-- ФИЛЬТРАЦИЯ -->
    <div class="filter-bar">
        <h3 class="filter-title">Фильтровать по:</h3>
        <form class="filter-form">
            <select class="form-select" onchange="window.location.href=this.value;">
                <option value="gallery.php?filter=newest" <?php echo ($filter === 'newest' ? 'selected' : ''); ?>>Новизне</option>
                <option value="gallery.php?filter=likes" <?php echo ($filter === 'likes' ? 'selected' : ''); ?>>Лайкам</option>
                <option value="gallery.php?filter=comments" <?php echo ($filter === 'comments' ? 'selected' : ''); ?>>Комментариям</option>
            </select>
        </form>
    </div>




    <!-- ГАЛЕРЕЯ -->
    <section class="gallery">
        <div class="container-lg">
            <div class="grid">
                <?php while ($photo = $result_photos->fetch_assoc()): ?>
                    <div class="image-container">
                        <div class="image" style="background-image: url('<?php echo htmlspecialchars($photo['url']); ?>');">
                            <div class="image-box">
                                <div class="image-textbox"> 
                                    <a href="photographer.php?id=<?php echo $photo['photographer_id']; ?>&photo=<?php echo $photo['id']; ?>">
                                        <h6>Фотограф: <?php echo htmlspecialchars($photo['photographer_name']) ?: 'Не указан'; ?></h6>
                                    </a>
                                </div>
                                <div class="image-link">
                                    <?php if (!empty($photo['client_name'])): ?>
                                        <a href="photographer.php?id=<?php echo $photo['client_id']; ?>&photo=<?php echo $photo['id']; ?>">
                                            <h6>Модель: <?php echo htmlspecialchars($photo['client_name']); ?></h6>
                                        </a>
                                    <?php endif; ?>
                                </div>
                                <div class="image-stats">
                                    <br><span>Лайков: <?php echo $photo['like_count']; ?></span></br>
                                    <br><span>Комментариев: <?php echo $photo['comment_count']; ?></span></br>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    </section>


</main>

<footer>
    <div class="container">
        <div class="footer-row">
            <div class="footer_logo-box">
                <a href="index.php" class="logo"><img src="images/4.png" alt="логотип"></a>
            </div>
            <div class="footer_menu-box">
                <ul class="footer_menu">
                    <li class="footer_menu-item"><a class="footer_menu-link" href="index.php">Главная</a></li>
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