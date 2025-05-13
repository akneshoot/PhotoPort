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

// Загружаем студии из БД
$search_query = $_GET['query'] ?? '';
$search_query = $conn->real_escape_string($search_query);

if (!empty($search_query)) {
    $sql_studios = "SELECT id, name, description, image_url, link 
                    FROM studios 
                    WHERE name LIKE '%$search_query%' 
                       OR description LIKE '%$search_query%' 
                    ORDER BY id DESC";
} else {
    $sql_studios = "SELECT id, name, description, image_url, link FROM studios ORDER BY id DESC";
}


$result_studios = $conn->query($sql_studios);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PhotoPort - Студии</title>
    <link rel="icon" type="image/x-icon" href="images/1.ico" />
    <link rel="stylesheet" href="styles/css/main.css">
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
            <!-- Меню-бургер -->
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
                    <h5>Студии</h5>
                    <h2 class="heading white">Лучшие студии для фотосессий</h2>
                    <p class="opaque-grey">Посмотрите нашу подборку студий, где можно организовать фотосессии.</p>
                    <?php if ($is_logged_in): ?>
                        <a href="profile.php" class="invite-link invite-link-white">Профиль</a>
                    <?php else: ?>
                        <a href="auth.php" class="invite-link invite-link-white">Войти</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>
    <section class="hero-mobile">
            <div class="container">
                <div class="section_text section_text_black">
                    <div class="section_text-box remove-padding">
                        <h5>Студии</h5>
                        <h2 class="heading white">Лучшие студии для фотосессий</h2>
                        <p class="opaque-grey">Посмотрите нашу подборку студий, где можно организовать фотосессии.</p>
                        <?php if ($is_logged_in): ?>
                            <a href="profile.php" class="invite-link invite-link-white">Профиль</a>
                        <?php else: ?>
                            <a href="auth.php" class="invite-link invite-link-white">Войти</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
    </section>

    <section class="search-section">
        <div class="container-lg">
        <form method="GET" action="studios.php" class="search-form" id="searchForm">
            <div class="search-input-wrapper" style="position: relative; display: inline-block; width: 100%;">
                <input 
                    type="text" 
                    name="query" 
                    id="searchInput" 
                    class="form-input" 
                    placeholder="Найти студию..." 
                    value="<?php echo htmlspecialchars($_GET['query'] ?? ''); ?>"
                    style="padding-right: 30px;"
                >
                <span 
                    id="clearSearch" 
                    style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); cursor: pointer; font-weight: bold; font-size: 18px; display: none;"
                >
                    ×
                </span>
            </div>
            <button type="submit" class="btn btn-black">Поиск</button>
        </form>

        </div>
    </section>

    <!-- ГАЛЕРЕЯ СТУДИЙ -->
    <section class="gallery">
        <div class="container-lg">
            <div class="grid">
                <?php while ($studio = $result_studios->fetch_assoc()): ?>
                    <div class="image-container">
                        <div class="image" style="background-image: url('<?php echo htmlspecialchars($studio['image_url']); ?>');">
                            <div class="image-box">
                                <div class="image-textbox">
                                    <h3><?php echo htmlspecialchars($studio['name']); ?></h3>
                                    <p><?php echo htmlspecialchars($studio['description']); ?></p>
                                </div>
                                <div class="image-link">
                                    <a href="<?php echo htmlspecialchars($studio['link']); ?>">
                                        <h6>Подробнее</h6>
                                    </a>
                                    <img src="images/arrow-white.svg" alt="стрелка">
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
                    <li class="footer_menu-item"><a class="footer_menu-link" href="gallery.php">Галерея</a></li>
                    <li class="footer_menu-item"><a class="footer_menu-link" href="photographers.php">Фотографы</a></li>
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
    const input = document.getElementById('searchInput');
    const clearBtn = document.getElementById('clearSearch');

    function toggleClearButton() {
        clearBtn.style.display = input.value.length > 0 ? 'block' : 'none';
    }

    clearBtn.addEventListener('click', () => {
        input.value = '';
        toggleClearButton();
        input.focus();
    });

    input.addEventListener('input', toggleClearButton);

    // Показать крестик при загрузке страницы, если есть текст
    document.addEventListener('DOMContentLoaded', toggleClearButton);
</script>

</body>
</html>
