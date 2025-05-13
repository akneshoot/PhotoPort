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

$message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['register'])) {
        $name = trim($_POST['register-name']);
        $email = trim($_POST['register-email']);
        $password_input = $_POST['register-password'];  // Исходный пароль
        $role = $_POST['register-role'];

        // Проверка email через регулярное выражение
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = 'Неверный формат email.';
        } else {
            // Проверка домена email
            $email_parts = explode('@', $email);
            $domain = array_pop($email_parts);
            if (!checkdnsrr($domain, 'MX')) {
                $message = 'Этот email домен не существует или недействителен.';
            } else {
                // Проверка пароля
                if (preg_match('/[А-Яа-я]/', $password_input)) {
                    $message = 'Пароль не должен содержать кириллические символы.';
                } elseif (strlen($password_input) < 6) {
                    $message = 'Пароль должен содержать минимум 6 символов.';
                } elseif (!preg_match('/[\W_]/', $password_input)) {
                    $message = 'Пароль должен содержать хотя бы один специальный символ (например, !, @, # и т. д.).';
                } else {
                    // Хешируем пароль, если он прошел проверку
                    $password = password_hash($password_input, PASSWORD_DEFAULT);

                    $country = isset($_POST['register-country']) ? trim($_POST['register-country']) : null;
                    $city = isset($_POST['register-city']) ? trim($_POST['register-city']) : null;
                    $description = isset($_POST['register-description']) ? trim($_POST['register-description']) : null;

                    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                    $stmt->execute([$email]);
                    if ($stmt->fetchColumn()) {
                        $message = 'Этот email уже зарегистрирован. Попробуйте другой.';
                    } else {
                        $profile_picture = null;
                        if (isset($_FILES['register-profile-picture']) && $_FILES['register-profile-picture']['error'] == 0) {
                            $file_tmp = $_FILES['register-profile-picture']['tmp_name'];
                            $file_name = $_FILES['register-profile-picture']['name'];
                            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

                            $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
                            if (in_array($file_ext, $allowed_extensions)) {
                                $new_file_name = uniqid() . '.' . $file_ext;
                                $upload_dir = 'uploads/profile_pictures/';
                                if (!is_dir($upload_dir)) {
                                    mkdir($upload_dir, 0777, true);
                                }
                                $file_path = $upload_dir . $new_file_name;

                                if (move_uploaded_file($file_tmp, $file_path)) {
                                    $profile_picture = $file_path;
                                }
                            }
                        }

                        $stmt = $pdo->prepare("SELECT id FROM roles WHERE name = ?");
                        $stmt->execute([$role]);
                        $role_id = $stmt->fetchColumn();

                        if ($name && $email && $password && $role_id) {
                            $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role_id, country, city, description, profile_picture) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                            if ($stmt->execute([$name, $email, $password, $role_id, $country, $city, $description, $profile_picture])) {
                                $user_id = $pdo->lastInsertId();

                                if ($role === "photographer" && isset($_POST['genres'])) {
                                    foreach ($_POST['genres'] as $genre_id) {
                                        $stmt = $pdo->prepare("INSERT INTO photographer_genres (photographer_id, genre_id) VALUES (?, ?)");
                                        $stmt->execute([$user_id, $genre_id]);
                                    }
                                }

                                $_SESSION['user_name'] = $name;
                                $_SESSION['user_role'] = $role;

                                header("Location: index.php");
                                exit;
                            } else {
                                $message = 'Ошибка при регистрации.';
                            }
                        } else {
                            $message = 'Пожалуйста, заполните все поля.';
                        }
                    }
                }
            }
        }
    }

    if (isset($_POST['login'])) {
        $email = trim($_POST['login-email']);
        $password = trim($_POST['login-password']);

        if ($email && $password) {
            $stmt = $pdo->prepare("SELECT users.id, users.name, users.password, roles.name AS role FROM users INNER JOIN roles ON users.role_id = roles.id WHERE users.email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_role'] = $user['role'];

                if ($user['role'] === 'admin') {
                    header("Location: admin.php");
                } else {
                    header("Location: index.php");
                }
                exit;
            } else {
                $message = 'Неверный email или пароль.';
            }
        } else {
            $message = 'Введите email и пароль.';
        }
    }
}
?>



<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PhotoPort - Авторизация</title>
    <link rel="icon" type="image/x-icon" href="images/1.ico" />
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="styles/css/auth.css">
    <script>
        function toggleForm() {
            document.getElementById("login-form").classList.toggle("hidden");
            document.getElementById("register-form").classList.toggle("hidden");
        }

        function togglePhotographerFields() {
            var role = document.getElementById("register-role").value;
            var extraFields = document.getElementById("photographer-fields");
            if (role === "photographer") {
                extraFields.style.display = "block";
            } else {
                extraFields.style.display = "none";
            }
        }

        function togglePassword(inputId) {
            var passwordField = document.getElementById(inputId);
            passwordField.type = passwordField.type === "password" ? "text" : "password";
        }
    </script>
    <script>
        window.addEventListener("DOMContentLoaded", function () {
            const message = document.querySelector(".message");
            if (message) {
                setTimeout(() => {
                    message.style.opacity = "0";
                    setTimeout(() => message.remove(), 500); // Удаляем после плавного исчезновения
                }, 5000); // Сколько ждать до исчезновения (в мс)
            }
        });
    </script>

</head>
<body>
    <div class="container">
        <?php if (!empty($message)): ?>
            <div class="message"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <form class="form-container" id="login-form" method="POST">
            <h2>Вход</h2>
            <input type="email" name="login-email" placeholder="Email" required>
            <input type="password" name="login-password" id="login-password" placeholder="Пароль" required>
            <label class="password-toggle">
                <input type="checkbox" id="show-password-login" onclick="togglePassword('login-password')"> Показать пароль
            </label>
            <button type="submit" name="login">Войти</button>
            <p>Нет аккаунта? <a href="#" onclick="toggleForm()">Зарегистрируйтесь</a></p>
        </form>

        <form class="form-container hidden" id="register-form" method="POST" enctype="multipart/form-data">
            <h2>Регистрация</h2>
            <div class="form-row">
                <input type="text" name="register-name" placeholder="Имя" required>
                <input type="email" name="register-email" placeholder="Email" required>
                <textarea name="register-description" placeholder="О вас"></textarea>
            </div>

            <div class="form-row">
                <input type="password" name="register-password" id="register-password" placeholder="Пароль" required>
                <select id="register-role" name="register-role" required onchange="togglePhotographerFields()">
                    <option value="client">Ищу фотографа</option>
                    <option value="photographer">Я фотограф</option>
                </select>
            </div>

                
                <div class="form-row">
                    <p>Фото профиля: </p><input type="file" name="register-profile-picture" accept="image/*">
                
                </div>
                <div id="photographer-fields" style="display: none;">
                <div class="form-row">
                    <input type="text" name="register-country" placeholder="Страна">
                    <input type="text" name="register-city" placeholder="Город">
                </div>
                <label>Выберите жанры фотографии:</label>
                <div id="genres-container" class="genre-list">
                    <?php
                        $stmt = $pdo->query("SELECT id, name FROM genres ORDER BY name");
                        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                            echo "<label class='genre-item'><input type='checkbox' name='genres[]' value='{$row['id']}'> {$row['name']}</label>";
                        }
                    ?>
                </div>
            </div>

                
        

            

            <button type="submit" name="register">Зарегистрироваться</button>
            <p>Уже есть аккаунт? <a href="#" onclick="toggleForm()">Войти</a></p>
        </form>
    </div>

    <script>
        function togglePassword(inputId) {
            const passwordField = document.getElementById(inputId);
            passwordField.type = (passwordField.type === "password") ? "text" : "password";
        }

        function hideMessage() {
            const message = document.querySelector(".message");
            if (message) {
                message.remove();
            }
        }

        // Скрытие сообщения при вводе в любое поле
        document.querySelectorAll("input, textarea, select").forEach(input => {
            input.addEventListener("input", hideMessage);
        });

        // Скрытие сообщения при переключении форм
        function toggleForm() {
            document.getElementById("login-form").classList.toggle("hidden");
            document.getElementById("register-form").classList.toggle("hidden");
            hideMessage();
        }

        function togglePhotographerFields() {
            const role = document.getElementById("register-role").value;
            const extraFields = document.getElementById("photographer-fields");
            extraFields.style.display = (role === "photographer") ? "block" : "none";
            hideMessage();
        }

        // Автоматическое исчезновение через 5 секунд
        window.addEventListener("DOMContentLoaded", function () {
            const message = document.querySelector(".message");
            if (message) {
                setTimeout(() => {
                    message.style.opacity = "0";
                    setTimeout(() => message.remove(), 500);
                }, 5000);
            }
        });
    </script>

</body>
</html>
