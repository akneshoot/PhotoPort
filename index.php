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

$sql = "SELECT * FROM studios ORDER BY created_at DESC LIMIT 4";
$result = $conn->query($sql);


// Проверка, если пользователь вошел в систему
$is_logged_in = isset($_SESSION['user_name']);



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


?>
<?php
    $title = "PhotoPort - Главная";
    $phone_number = "+7-977-123-45-67";
?>
<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PhotoPort - Главная</title>
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
                    <li class="nav_list-item">
                        <a href="gallery.php" class="nav_list-link">Галерея</a>
                    </li>
                    <li class="nav_list-item">
                        <a href="photographers.php" class="nav_list-link">Фотографы</a></li>
                    <li class="nav_list-item">
                        <a href="studios.php" class="nav_list-link">Студии</a>
                    </li>
                    <?php if ($is_logged_in): ?>
                        <li class="nav_list-item">
                            <a href="profile.php" class="nav_list-link">Профиль</a>
                        </li>
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


    <!-- Основа -->
    <main>
        <!-- Описание глав страницы -->
        <section class="create">
            <div class="container-lg">
                <div class="row">
                    <div class="section_text section_text_black">
                        <div class="section_text-box">
                            <h2 class="heading white">Создавайте, смотрите и делитесь своими фотографиями.</h2>
                            <p class="opaque-grey">PhotoPort — это платформа для фотографов и клиентов. Мы
                                упрощаем процесс обмена фотографиями, процесс поиска фотографов, студий и общения с другими людьми.</p>
                                <?php if ($is_logged_in): ?>
                                    <a href="profile.php" class="invite-link invite-link-white">Профиль</a>
                                <?php else: ?>
                                    <a href="auth.php" class="invite-link invite-link-white">Войти</a>
                                <?php endif; ?>
                            
                        </div>
                    </div>
                    <div class="section_image section-create-img"></div>
                </div>
            </div>
        </section>

        <!-- Галерея -->
        <section class="stories">
            <div class="container-lg">
                <div class="row">
                    <div class="section_image section-stories-img"></div>
                    <div class="section_text section_text_white">
                        <div class="section_text-box">
                            <h2 class="heading black">Красивые фотографии каждый раз</h2>
                            <p class="opaque-black">Мы предоставляем различные идеи для фотографии, а также даем клиентам понимание о хороших фотографах и их работах.
                                В разделе галереи каждый фотограф может легко добавлять фотографии, а клиенты смогут ознакомиться с подходящими работами.</p>
                            <a href="gallery.php" class="invite-link invite-link-black">К галерее</a>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Поиск фотографов -->
        <section class="everyone">
            <div class="container-lg">
                <div class="row">
                    <div class="section_text section_text_white">
                        <div class="section_text-box">
                            <h2 class="heading black">Поиск фотографов</h2>
                            <p class="opaque-black">PhotoPort помогает создавать свой профиль фотографа, который поможет найти отклик
                                у аудитории. Каждый клиент сможет найти для себя определенного фотографа, а также в дальнейшем изучить его работы.
                                </p>
                            <a href="photographers.php" class="invite-link invite-link-black">К фотографам</a>
                        </div>
                    </div>
                    <div class="section_image section-everyone-img"></div>
                </div>
            </div>
        </section>


        <!-- Описание про студии -->
        <section class="our-features">
            <div class="container">
                <div class="row">
                    <div class="feature">
                        <div class="feature-image"><img src="images/studios.svg" alt="студии"></div>
                        <h3 class="feature-heading">Поиск студий для фотосессий</h3>
                        <p class="feature-text">На нашем сайте очень просто подобрать подходящую студию, если потребуется.
                            Каждый день на нашем сайте обновляются предложения от различных студий, самые популярные студии предложены на главной странице, чтобы было удобнее
                            ореентироваться клиенту при выборе студии. Пользователь может выбрать из предложенных ниже вариантов или поискать сам в расширенном поиске.</p>
                            <a href="studios.php" class="invite-link invite-link-black">Ко всем студиям</a>
                    </div>
                    
                </div>
            </div>
        </section>

        <!-- Студии -->
        <section class="gallery">
            <div class="container-lg">
                <div class="grid">
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <div class="image-container">
                            <div class="image" style="background-image: url('<?php echo $row['image_url']; ?>');">
                                <div class="image-box">
                                    <div class="image-textbox">
                                        <h3><?php echo htmlspecialchars($row['name']); ?></h3>
                                        <p><?php echo htmlspecialchars($row['description']); ?></p>
                                    </div>
                                    <div class="image-link">
                                        <a href="<?php echo htmlspecialchars($row['link']); ?>">
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

    <!-- Футер -->
    <footer>
        <div class="container">
            <div class="footer-row">
                <div class="footer_logo-box">
                    <a href="index.php" class="logo">
                        <img src="images/4.png" alt="логотип">
                    </a>
                </div>
                <div class="footer_menu-box">
                    <ul class="footer_menu">
                        <li class="footer_menu-item">
                            <a class="footer_menu-link" href="gallery.php">Галерея</a>
                        </li>
                        <li class="footer_menu-item">
                            <a class="footer_menu-link" href="photographers.php">Фотографы</a></li>
                        <li class="footer_menu-item">
                            <a class="footer_menu-link" href="studios.php">Студии</a>
                        </li>
                        <?php if ($is_logged_in): ?>
                        <li class="footer_menu-item">
                            <a class="footer_menu-link" href="profile.php">Профиль</a>
                        </li>
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
