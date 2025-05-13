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

// Проверка, если пользователь вошел в систему
$is_logged_in = isset($_SESSION['user_name']);


$searchTerm = $_GET['query'] ?? '';

if ($searchTerm) {
    $query = $pdo->prepare("SELECT name FROM users WHERE name LIKE :name LIMIT 5");
    $query->execute(['name' => "%$searchTerm%"]);
    $results = $query->fetchAll(PDO::FETCH_COLUMN);

    echo json_encode($results);
}

// Получаем список жанров для фильтрации
$query = $pdo->prepare("SELECT id, name FROM genres");
$query->execute();
$genres = $query->fetchAll(PDO::FETCH_ASSOC);

// Параметры фильтрации
$search_name = isset($_GET['name']) ? $_GET['name'] : '';
$search_country = isset($_GET['country']) ? $_GET['country'] : '';
$search_city = isset($_GET['city']) ? $_GET['city'] : '';
$search_genre = isset($_GET['genre']) ? $_GET['genre'] : '';
$min_price = isset($_GET['min_price']) ? (float)$_GET['min_price'] : null;
$max_price = isset($_GET['max_price']) ? (float)$_GET['max_price'] : null;
$sort = isset($_GET['sort']) ? $_GET['sort'] : '';


// Подготовка запроса с фильтрацией по жанру
$sql = "
    SELECT users.id, users.name, users.country, users.city, users.profile_picture,
           users.service_cost,
           COALESCE(AVG(ratings.rating), 0) AS avg_rating,
           GROUP_CONCAT(DISTINCT genres.name SEPARATOR ', ') AS genres
    FROM users
    LEFT JOIN roles ON users.role_id = roles.id
    LEFT JOIN ratings ON users.id = ratings.photographer_id
    LEFT JOIN photographer_genres ON users.id = photographer_genres.photographer_id
    LEFT JOIN genres ON photographer_genres.genre_id = genres.id
    WHERE roles.name = 'photographer'
    AND users.name LIKE :name
    AND users.country LIKE :country
    AND users.city LIKE :city
";

$params = [
    'name' => "%$search_name%",
    'country' => "%$search_country%",
    'city' => "%$search_city%",
];

if ($search_genre) {
    $sql .= " AND genres.id = :genre";
    $params['genre'] = $search_genre;
}

if ($min_price !== null) {
    $sql .= " AND users.service_cost >= :min_price";
    $params['min_price'] = $min_price;
}

if ($max_price !== null) {
    $sql .= " AND users.service_cost <= :max_price";
    $params['max_price'] = $max_price;
}

$sql .= " GROUP BY users.id";

switch ($sort) {
    case 'rating_desc':
        $sql .= " ORDER BY avg_rating DESC";
        break;
    case 'rating_asc':
        $sql .= " ORDER BY avg_rating ASC";
        break;
    case 'price_asc':
        $sql .= " ORDER BY users.service_cost ASC";
        break;
    case 'price_desc':
        $sql .= " ORDER BY users.service_cost DESC";
        break;
    default:
        $sql .= " ORDER BY users.name ASC";
        break;
}

$query = $pdo->prepare($sql);
$query->execute($params);
$photographers = $query->fetchAll(PDO::FETCH_ASSOC);

?>


<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PhotoPort - Фотографы</title>
    <link rel="icon" type="image/x-icon" href="images/1.ico" />
    <link rel="stylesheet" href="styles/css/main.css">
    <link rel="stylesheet" href="styles/css/photo.css">
    <script src="https://code.jquery.com/jquery-3.6.3.min.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
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
                    <li class="nav_list-item"><a href="studios.php" class="nav_list-link">Студии</a></li>
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

    <div class="container mx-auto pt-10 pb-8">
    <h1 class="text-4xl font-bold mb-6">Поиск фотографов</h1>
    <p class="search-description mb-6">Ищите фотографов по имени, стране или городу. Введите ключевые данные, чтобы сузить круг поиска и найти профессионала, 
        который идеально подходит для вашей съемки. Этот удобный инструмент позволит вам легко найти фотографара. 
        Начните поиск и найдите своего идеального фотографа всего за несколько кликов!</p>

        <form method="get" class="mb-6 relative">
            <div class="form-group">
                <input id="name" type="text" name="name" placeholder="Поиск по имени" value="<?= htmlspecialchars($search_name) ?>" class="form-input">
                <input id="country" type="text" name="country" placeholder="Поиск по стране" value="<?= htmlspecialchars($search_country) ?>" class="form-input">
                <input id="city" type="text" name="city" placeholder="Поиск по городу" value="<?= htmlspecialchars($search_city) ?>" class="form-input">
                <select name="sort" class="form-select">
                    <option value="">Сортировать по</option>
                    <option value="rating_desc" <?= $sort == 'rating_desc' ? 'selected' : '' ?>> Рейтинг : по убыванию</option>
                    <option value="rating_asc" <?= $sort == 'rating_asc' ? 'selected' : '' ?>> Рейтинг : по возрастанию</option>
                    <option value="price_asc" <?= $sort == 'price_asc' ? 'selected' : '' ?>> Цена : по возрастанию</option>
                    <option value="price_desc" <?= $sort == 'price_desc' ? 'selected' : '' ?>> Цена : по убыванию</option>
                </select>
                <select name="genre" class="form-select">
                    <option value="">Выберите жанр</option>
                    <?php foreach ($genres as $genre): ?>
                        <option value="<?= $genre['id']; ?>" <?= $search_genre == $genre['id'] ? 'selected' : ''; ?>>
                            <?= htmlspecialchars($genre['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-black">Поиск</button>
                <button type="button" id="clear" class="btn btn-black">Очистить</button>
            </div>
        </form>

        <!-- Блок для отображения предложений -->
        <div id="suggestions" class="suggestions-list"></div>
    </form>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($photographers as $photographer): ?>
            <div class="photographer-card">
                <div class="image-wrapper">
                    <!-- Ссылка теперь только вокруг изображения -->
                    <a href="photographer.php?id=<?= $photographer['id']; ?>">
                        <img src="<?= !empty($photographer['profile_picture']) ? htmlspecialchars($photographer['profile_picture']) : 'images/default_profile.jpg'; ?>" alt="<?= htmlspecialchars($photographer['name']); ?>">
                    </a>
                    <?php if ($is_logged_in): ?>
                        <?php
                        // Проверяем, добавлен ли фотограф в избранное
                        $query = $pdo->prepare("SELECT id FROM favorites WHERE user_id = :user_id AND photographer_id = :photographer_id");
                        $query->execute([
                            'user_id' => $user_id,
                            'photographer_id' => $photographer['id']
                        ]);
                        $is_favorite = $query->fetch();
                        ?>
                        <form action="add_to_favorites.php" method="POST" class="favorite-form" id="favorite-form-<?= $photographer['id']; ?>">
                            <input type="hidden" name="photographer_id" value="<?= $photographer['id']; ?>">
                            <button type="button" class="favorite-btn" onclick="toggleFavorite(<?= $photographer['id']; ?>)">
                                <img src="<?= $is_favorite ? 'images/favorite-icon-filled.png' : 'images/favorite-icon.png'; ?>" alt="Добавить в избранное" class="favorite-icon" id="favorite-icon-<?= $photographer['id']; ?>">
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
                <div class="photographer-info">
                    <!-- Ссылка теперь только вокруг имени -->
                    <h2><a href="photographer.php?id=<?= $photographer['id']; ?>"><?= htmlspecialchars($photographer['name']); ?></a></h2>
                    <?php
                    if (!empty($photographer['genres'])) {
                        $genres = explode(', ', $photographer['genres']);
                        foreach ($genres as $genre) {
                            echo '<span class="inline-block bg-gray-200 text-gray-700 px-2 py-1 rounded mr-1 text-sm">' . htmlspecialchars($genre) . '</span>';
                        }
                    } 
                    ?>

                    <p><strong>Стоимость услуг: </strong><?= htmlspecialchars($photographer['service_cost'] ?? 'Не указана'); ?></p>
                    <p><strong>Страна:</strong> <?= htmlspecialchars($photographer['country'] ?? 'Не указано'); ?></p>
                    <p><strong>Город:</strong> <?= htmlspecialchars($photographer['city'] ?? 'Не указано'); ?></p>
                    <p><strong>Рейтинг: </strong><?= $photographer['avg_rating'] > 0 ? number_format($photographer['avg_rating'], 1) . ' ★' : 'Нет оценок'; ?></p>

                </div>
            </div>
        <?php endforeach; ?>
    </div>





    <script>
        function toggleFavorite(photographerId) {
            const favoriteIcon = document.getElementById(`favorite-icon-${photographerId}`);
            const form = document.getElementById(`favorite-form-${photographerId}`);

            // Отправка запроса через Fetch API
            fetch('add_to_favorites.php', {
                method: 'POST',
                body: new FormData(form)
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'added') {
                    favoriteIcon.src = 'images/favorite-icon-filled.png'; // Меняем на иконку "в избранном"
                } else if (data.status === 'removed') {
                    favoriteIcon.src = 'images/favorite-icon.png'; // Меняем на иконку "не в избранном"
                }
            })
            .catch(error => console.error('Ошибка:', error));
        }


    </script>




</div>

    <script>
        function searchPhotographers() {
            var name = document.getElementById('name').value;
            var country = document.getElementById('country').value;
            var city = document.getElementById('city').value;

            // Если все поля пустые, скрываем список предложений
            if (!name && !country && !city) {
                document.getElementById('suggestions').style.display = 'none';
                return;
            }

            var xhr = new XMLHttpRequest();
            xhr.open('GET', 'search.php?name=' + name + '&country=' + country + '&city=' + city, true);
            xhr.onreadystatechange = function () {
                if (xhr.readyState == 4 && xhr.status == 200) {
                    var results = JSON.parse(xhr.responseText); // Получаем ответ в виде JSON
                    displaySuggestions(results); // Отображаем возможные варианты
                }
            };
            xhr.send();
        }

        // Функция для отображения списка предложений
        function displaySuggestions(results) {
            var suggestionBox = document.getElementById('suggestions');
            suggestionBox.innerHTML = ''; // Очищаем предыдущие результаты

            if (results.length > 0) {
                suggestionBox.style.display = 'block'; // Показываем список
                results.forEach(function(result) {
                    var div = document.createElement('div');
                    div.classList.add('suggestion-item');
                    div.textContent = result.name + ', ' + result.country + ', ' + result.city;
                    div.onclick = function() {
                        document.getElementById('name').value = result.name;
                        document.getElementById('country').value = result.country;
                        document.getElementById('city').value = result.city;
                        suggestionBox.innerHTML = ''; // Очищаем список после выбора
                        suggestionBox.style.display = 'none'; // Скрываем список
                    };
                    suggestionBox.appendChild(div);
                });
            } else {
                // Если нет результатов, показываем сообщение "Ничего не найдено"
                var noResults = document.createElement('div');
                noResults.classList.add('suggestion-item');
                noResults.textContent = 'Ничего не найдено';
                suggestionBox.appendChild(noResults);
                suggestionBox.style.display = 'block'; // Показываем сообщение
            }
        }

        // Функция для очистки полей поиска
        function clearSearch() {
            document.getElementById('name').value = '';           
            document.getElementById('country').value = '';        
            document.getElementById('city').value = '';          
            document.querySelector('select[name="genre"]').value = '';  
            document.querySelector('select[name="sort"]').value = '';   
            document.querySelector('input[name="min_price"]').value = '';  
            document.querySelector('input[name="max_price"]').value = '';  
            document.getElementById('suggestions').style.display = 'none'; 
            document.getElementById('suggestions').innerHTML = '';         
            searchPhotographers();  
        }


        // Обработчики событий для поиска
        document.getElementById('name').addEventListener('input', searchPhotographers);
        document.getElementById('country').addEventListener('input', searchPhotographers);
        document.getElementById('city').addEventListener('input', searchPhotographers);

        // Обработчик для кнопки очистки
        document.getElementById('clear').addEventListener('click', clearSearch);
    </script>







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
                        <li class="footer_menu-item"><a class="footer_menu-link" href="index.php">Главная</a></li>
                        <li class="footer_menu-item"><a class="footer_menu-link" href="gallery.php">Галерея</a></li>
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