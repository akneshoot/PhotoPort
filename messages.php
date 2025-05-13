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

$is_logged_in = isset($_SESSION['user_name']);

// Получаем user_id
$query = $pdo->prepare("SELECT id, role_id FROM users WHERE name = :name");
$query->execute(['name' => $_SESSION['user_name']]);
$user = $query->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die("Ошибка: пользователь не найден.");
}

$user_id = $user['id'];
$user_role = $user['role_id']; // 1 - клиент, 2 - фотограф

// Получаем список всех собеседников
$contactsQuery = $pdo->prepare("
    SELECT DISTINCT 
        CASE 
            WHEN m.sender_id = :user_id THEN m.receiver_id 
            ELSE m.sender_id 
        END AS contact_id,
        users.name AS contact_name,
        users.profile_picture AS contact_picture,
        users.role_id AS contact_role,
        MAX(m.created_at) AS last_message_time
    FROM messages m
    JOIN users ON users.id = (
        CASE 
            WHEN m.sender_id = :user_id THEN m.receiver_id 
            ELSE m.sender_id 
        END
    )
    WHERE m.sender_id = :user_id OR m.receiver_id = :user_id
    GROUP BY contact_id, users.name, users.profile_picture, users.role_id
    ORDER BY last_message_time DESC
");
$contactsQuery->execute(['user_id' => $user_id]);
$contacts = $contactsQuery->fetchAll(PDO::FETCH_ASSOC);

// Определяем собеседника
$contact_id = isset($_GET['contact_id']) ? (int)$_GET['contact_id'] : null;
$contact_name = null;
$contact_role = null;
$can_rate = false;

if ($contact_id) {
    foreach ($contacts as $contact) {
        if ($contact['contact_id'] == $contact_id) {
            $contact_name = $contact['contact_name'];
            $contact_role = $contact['contact_role'];
            break;
        }
    }
}

// Проверка подтверждений
$stmt = $pdo->prepare("SELECT * FROM job_completion WHERE client_id = :client_id AND photographer_id = :photographer_id");
$stmt->execute([
    'client_id' => ($user_role == 1 ? $user_id : $contact_id),
    'photographer_id' => ($user_role == 2 ? $user_id : $contact_id)
]);
$job = $stmt->fetch(PDO::FETCH_ASSOC);

$job_confirmed_by_client = $job && $job['client_confirmed'];
$job_confirmed_by_photographer = $job && $job['photographer_confirmed'];
$job_fully_confirmed = $job_confirmed_by_client && $job_confirmed_by_photographer;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_work_done'])) {
    $client_id = ($user_role == 1) ? $user_id : $contact_id;
    $photographer_id = ($user_role == 2) ? $user_id : $contact_id;
    $column = ($user_role == 1) ? 'client_confirmed' : 'photographer_confirmed';

    // Вставляем или обновляем запись
    $stmt = $pdo->prepare("
        INSERT INTO job_completion (client_id, photographer_id, $column)
        VALUES (:client_id, :photographer_id, TRUE)
        ON DUPLICATE KEY UPDATE $column = TRUE
    ");
    $stmt->execute(['client_id' => $client_id, 'photographer_id' => $photographer_id]);

    header("Location: ?contact_id=$contact_id");
    exit();
}


// Разрешаем ставить оценку, если контакт - фотограф и у них был чат
if ($contact_id && $user_role == 1 && $contact_role == 2) {
    $can_rate = true;
}


// Получаем сообщения с выбранным собеседником
$messages = [];
if ($contact_id) {
    $stmt = $pdo->prepare("
        SELECT m.*, sender.name AS sender_name, receiver.name AS receiver_name 
        FROM messages m
        JOIN users sender ON m.sender_id = sender.id
        JOIN users receiver ON m.receiver_id = receiver.id
        WHERE (m.sender_id = :user_id AND m.receiver_id = :contact_id) 
           OR (m.sender_id = :contact_id AND m.receiver_id = :user_id)
        ORDER BY m.created_at ASC
    ");
    $stmt->execute(['user_id' => $user_id, 'contact_id' => $contact_id]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Обработка отправки нового сообщения
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['message'])) {
    $message = trim($_POST['message']);
    if (!empty($message)) {
        $stmt = $pdo->prepare("
            INSERT INTO messages (sender_id, receiver_id, message, created_at) 
            VALUES (:sender_id, :receiver_id, :message, NOW())
        ");
        $stmt->execute(['sender_id' => $user_id, 'receiver_id' => $contact_id, 'message' => $message]);
        header("Location: ?contact_id=$contact_id"); // Перезагружаем страницу после отправки сообщения
        exit();
    }
}

// Обработка отправки рейтинга
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['rate_photographer']) && $can_rate) {
    $rating = (int)$_POST['rating'];

    // Проверяем, не поставил ли пользователь уже рейтинг
    $stmt = $pdo->prepare("SELECT * FROM ratings WHERE user_id = :user_id AND photographer_id = :photographer_id");
    $stmt->execute(['user_id' => $user_id, 'photographer_id' => $contact_id]);
    $existing_rating = $stmt->fetch(PDO::FETCH_ASSOC);

    $rating_message = '';
    if (!$existing_rating) {
        $stmt = $pdo->prepare("INSERT INTO ratings (user_id, photographer_id, rating) VALUES (:user_id, :photographer_id, :rating)");
        $stmt->execute(['user_id' => $user_id, 'photographer_id' => $contact_id, 'rating' => $rating]);
        $rating_message = "Спасибо за оценку!";
    } else {
        $rating_message = "Вы уже оценили этого фотографа.";
    }

}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Мои сообщения</title>
    <link rel="icon" type="image/x-icon" href="images/1.ico" />
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="styles/css/messages.css">
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
                    <li class="nav_list-item">
                        <a href="index.php" class="nav_list-link">Главная</a>
                    </li>
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
                        <path d="M14.718.075l.707.707L8.707 7.5l6.718 6.718-.707.707L8 8.207l-6.718 6.718-.707-.707L7.293 7.5.575.782l.707-.707L8 6.793 14.718.075z" fill-rule="evenodd"></path>
                    </svg>
                </button>
            </nav>
        </div>
    </header>

    <div class="container">
        <div class="messages-page">
            <!-- Список собеседников -->
            <div class="contacts-list">
                <h2>Чаты</h2>
                <?php if (empty($contacts)): ?>
                    <p>Нет чатов</p>
                <?php else: ?>
                    <?php foreach ($contacts as $contact): ?>
                        <a href="?contact_id=<?= $contact['contact_id'] ?>" class="<?= ($contact_id == $contact['contact_id']) ? 'active' : '' ?>">
                            <img src="<?= !empty($contact['contact_picture']) ? htmlspecialchars($contact['contact_picture']) : 'images/default_profile.jpg'; ?>" class="contact-photo">
                            <strong><?= htmlspecialchars($contact['contact_name']) ?></strong>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Чат с выбранным собеседником -->
            <div class="chat-container">
                <?php if ($contact_id): ?>
                    <h2>Чат с <?= htmlspecialchars($contact_name) ?></h2>
                    <div id="chat" class="chat-box">
                        <?php
                            if (empty($messages)) {
                                echo "<p>Нет сообщений</p>";
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
                            <textarea name="message" placeholder="Введите сообщение..."></textarea>
                            <button type="submit">➤</button>
                        </form>
                    </div>
                    <?php
                        $valid_pair = ($user_role == 1 && $contact_role == 2) || ($user_role == 2 && $contact_role == 1);
                    ?>

                    <?php if ($valid_pair && !$job_fully_confirmed): ?>
                        <form method="POST" style="display: flex; align-items: center; gap: 10px; margin: 20px 0;">
                            <p style="margin: 0;">Работа была выполнена?</p>
                            <button type="submit" name="confirm_work_done" class="btn-black">Да</button>
                        </form>
                    <?php elseif ($valid_pair && $job_fully_confirmed): ?>
                        <p style="margin: 20px 0;">Оба участника подтвердили выполнение работы.</p>
                    <?php endif; ?>






                    <?php if ($can_rate && $job_fully_confirmed): ?>
                        <div class="rating-form">
                            <h3>Оцените работу фотографа:</h3>
                            <form method="POST">
                                <select name="rating" required>
                                    <option value="">Выберите оценку</option>
                                    <option value="1">1 звезда</option>
                                    <option value="2">2 звезды</option>
                                    <option value="3">3 звезды</option>
                                    <option value="4">4 звезды</option>
                                    <option value="5">5 звезд</option>
                                </select>
                                <button type="submit" name="rate_photographer">Оценить</button>
                            </form>
                            <?php if (!empty($rating_message)): ?>
                                <p class="rating-message"><?= htmlspecialchars($rating_message); ?></p>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <p>Выберите чат</p>
                <?php endif; ?>
            </div>
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
                        <li class="footer_menu-item">
                            <a class="footer_menu-link" href="index.php">Главная</a>
                        </li>
                        <li class="footer_menu-item"><a class="footer_menu-link" href="gallery.php">Галерея</a></li>
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
