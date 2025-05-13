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

// Проверяем авторизацию пользователя
if (!isset($_SESSION['user_name'])) {
    header("Location: auth.php");
    exit();
}

// Получаем `user_id` текущего пользователя
$query = $pdo->prepare("SELECT id FROM users WHERE name = :name");
$query->execute(['name' => $_SESSION['user_name']]);
$user = $query->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die("Ошибка: пользователь не найден.");
}

$user_id = $user['id'];

// Получаем ID получателя из URL (например, messages.php?receiver_id=2)
if (!isset($_GET['receiver_id']) || !is_numeric($_GET['receiver_id'])) {
    die("Ошибка: получатель не указан.");
}

$receiver_id = (int)$_GET['receiver_id'];

// Проверяем, существует ли получатель
$query = $pdo->prepare("SELECT name FROM users WHERE id = ?");
$query->execute([$receiver_id]);
$receiver = $query->fetch(PDO::FETCH_ASSOC);

if (!$receiver) {
    die("Ошибка: получатель не найден.");
}

$receiver_name = $receiver['name'];

// Обработка отправки сообщения
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['message'])) {
    $message = trim($_POST['message']);

    if (!empty($message)) {
        $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, message, created_at) VALUES (?, ?, ?, NOW())");
        $stmt->execute([$user_id, $receiver_id, $message]);

        // Перезагрузка страницы, чтобы обновить список сообщений
        header("Location: send_message.php?receiver_id=$receiver_id");
        exit();
    }
}

// Получаем сообщения между текущим пользователем и выбранным получателем
$stmt = $pdo->prepare("
    SELECT m.*, sender.name AS sender_name
    FROM messages m
    JOIN users sender ON m.sender_id = sender.id
    WHERE (m.sender_id = ? AND m.receiver_id = ?) 
       OR (m.sender_id = ? AND m.receiver_id = ?)
    ORDER BY m.created_at ASC
");
$stmt->execute([$user_id, $receiver_id, $receiver_id, $user_id]);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Чат</title>
    <link rel="icon" type="image/x-icon" href="images/1.ico" />
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="styles/css/sendmessages.css">
    <link rel="stylesheet" href="styles/css/main.css">
</head>
<body>

    <!-- Хэдер -->
    <header>
        <div class="container">
            <nav class="navbar">
                <a href="index.php" class="logo">
                    <img src="images/3.png" alt="логотип">
                </a>
                <ul class="nav_list">
                    <li class="nav_list-item"><a href="gallery.php" class="nav_list-link">Галерея</a></li>
                    <li class="nav_list-item"><a href="photographers.php" class="nav_list-link">Фотографы</a></li>
                    <li class="nav_list-item"><a href="studios.php" class="nav_list-link">Студии</a></li>
                    <?php if (isset($_SESSION['user_name'])): ?>
                        <li class="nav_list-item"><a href="profile.php" class="nav_list-link">Профиль</a></li>
                    <?php endif; ?>
                </ul>
                <div class="user-info">
                    <?php if (isset($_SESSION['user_name'])): ?>
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
                        <path d="M14.718.075l.707.707L8.707 7.5l6.718 6.718-.707.707L8 8.207l-6.718 6.718-.707-.707L7.293 7.5.575.782l.707-.707L8 6.793 14.718.075z" fill-rule="evenodd"></path>
                    </svg>
                </button>
            </nav>
        </div>
    </header>

    

    <!-- Чат с выбранным собеседником -->
    <div class="chat-container">
        <h2>Чат с <?= htmlspecialchars($receiver_name) ?></h2>
        <div class="chat-box">
            <?php
            if (empty($messages)) {
                echo "<p class='no-messages'>Нет сообщений</p>";
            } else {
                $current_date = null;
                setlocale(LC_TIME, 'ru_RU.UTF-8'); // Устанавливаем локаль для русского языка
                foreach ($messages as $msg) {
                    $message_date = strftime('%d %B %Y', strtotime($msg['created_at'])); // Форматируем дату с названием месяца
                    $message_date = mb_convert_case($message_date, MB_CASE_LOWER, "UTF-8"); // Делаем название месяца с маленькой буквы
                    if ($message_date !== $current_date) {
                        echo "<div class='message-date'><strong>$message_date</strong></div>";
                        $current_date = $message_date;
                    }
                    ?>
                    <div class="message <?= ($msg['sender_id'] == $user_id) ? 'me' : 'other' ?>">
                        <strong><?= htmlspecialchars($msg['sender_name']) ?></strong>
                        <p><?= nl2br(htmlspecialchars($msg['message'])) ?></p>
                        <span class="message-time"><?= date('H:i', strtotime($msg['created_at'])) ?></span>
                    </div>
                    <?php
                }
            }
            ?>
        </div>

        <!-- Форма для отправки нового сообщения -->
        <div class="message-input">
            <form method="POST">
                <textarea name="message" placeholder="Введите сообщение..." required></textarea>
                <button type="submit">➤</button>
            </form>
        </div>
    </div>

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
                        <li class="footer_menu-item"><a class="footer_menu-link" href="gallery.php">Галерея</a></li>
                        <li class="footer_menu-item"><a class="footer_menu-link" href="photographers.php">Фотографы</a></li>
                        <li class="footer_menu-item"><a class="footer_menu-link" href="studios.php">Студии</a></li>
                        <?php if (isset($_SESSION['user_name'])): ?>
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
