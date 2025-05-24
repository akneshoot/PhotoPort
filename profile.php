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

// Проверяем, есть ли пользователь в сессии
if (!isset($_SESSION['user_name'])) {
    header("Location: auth.php");
    exit();
}



// Получаем данные пользователя и роль в одном запросе
$user_name = $_SESSION['user_name'];
$query = $pdo->prepare("
    SELECT users.*, roles.name AS role_name 
    FROM users 
    JOIN roles ON users.role_id = roles.id 
    WHERE users.name = :name
");
$query->execute(['name' => $user_name]);
$user_data = $query->fetch(PDO::FETCH_ASSOC);

$user_id = $user_data['id'] ?? null;
$role = $user_data['role_name'] ?? 'guest';




// Получаем средний рейтинг фотографа (если роль - фотограф)
if ($role === 'photographer') {
  $rating_query = $pdo->prepare("
      SELECT AVG(rating) AS average_rating 
      FROM ratings 
      WHERE photographer_id = :photographer_id
  ");
  $rating_query->execute(['photographer_id' => $user_id]);
  $rating = $rating_query->fetch(PDO::FETCH_ASSOC);
  $average_rating = $rating['average_rating'] ?? 0;
} else {
  $average_rating = 0;
}

$genres = [];
if ($role === 'photographer') {
    $genres_query = $pdo->prepare("
        SELECT g.name 
        FROM genres g
        JOIN photographer_genres pg ON g.id = pg.genre_id
        WHERE pg.photographer_id = :photographer_id
    ");
    $genres_query->execute(['photographer_id' => $user_id]);
    $genres = $genres_query->fetchAll(PDO::FETCH_COLUMN);
}

// Получаем фото пользователя
$query = $pdo->prepare("
    SELECT p.id, p.url, p.alt, 
           pu.name AS photographer_name, 
           pc.name AS client_name
    FROM photos p
    LEFT JOIN users pu ON (p.user_id = pu.id AND pu.role_id = 2) OR (p.photo_user_id = pu.id AND pu.role_id = 2)
    LEFT JOIN users pc ON (p.user_id = pc.id AND pc.role_id = 1) OR (p.photo_user_id = pc.id AND pc.role_id = 1)
    WHERE p.user_id = :user_id OR p.photo_user_id = :user_id
");
$query->execute(['user_id' => $user_id]);
$photos = $query->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en" class="scroll-smooth">

<head>
  <meta charset="UTF-8" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>PhotoPort - Профиль</title>
  <link rel="icon" type="image/x-icon" href="images/1.ico" />
  <link rel="stylesheet" href="styles/css/main.css">
  <link rel="stylesheet" href="styles/css/profile.css">
  <link rel="stylesheet" href="styles.css" />
  <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/@fancyapps/ui@5.0/dist/fancybox/fancybox.umd.js"></script>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fancyapps/ui@5.0/dist/fancybox/fancybox.css" />
  <script src="https://code.jquery.com/jquery-3.6.3.min.js"></script>
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      content: ["./dist/*.{html,js}"],
      theme: {
        extend:
                {
                  fontFamily: {
                    'nothingyoucoulddo': ['Nothing You Could Do', 'cursive'],
                    'signika': ['Signika', 'sans-serif'],
                  },
                },
      },
      plugins: [],
    }
  </script>
</head>

<body>
  <!-- Навигация -->
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
            <li class="nav_list-item">
                <a href="gallery.php" class="nav_list-link">Галерея</a>
            </li>
            <li class="nav_list-item">
              <a href="photographers.php" class="nav_list-link">Фотографы</a></li>
            <li class="nav_list-item">
                <a href="studios.php" class="nav_list-link">Студии</a>
            </li>
        </ul>

        <div class="user-info">
                    <?php if (isset($_SESSION['user_name'])): ?>
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

  <div class="container mx-auto">
    
    <div class="pt-10 pb-8">
      <h1 class="text-4xl font-bold mr-4">Мой профиль</h1>
        <div class="profile-info mt-6 p-6">
          <div class="profile-left">
            <div class="profile-image">
              <?php 
                $profile_picture = !empty($user_data['profile_picture']) ? $user_data['profile_picture'] : 'images/default_profile.jpg';
              ?>
              <img src="<?php echo htmlspecialchars($profile_picture); ?>">
            </div>
             <div class="profile-buttons">
              <a href="update.php" class="editbtn">Изменить профиль</a>
              <?php if ($role === 'photographer'): ?>
                  <a href="edit_portfolio.php" class="editbtn">Мое портфолио</a>
              <?php endif; ?>
              <a href="messages.php" class="editbtn">Мои сообщения</a>
              <a href="liked_photos.php" class="editbtn">Мои лайки</a>
              <a href="favorites.php" class="editbtn">Избранное</a>
            </div>
          </div>


          <div class="profile-details">
            <h2 class="text-xl font-semibold mb-2"><?php echo htmlspecialchars($user_data['name']); ?></h2>

            <?php if ($role === 'photographer'): ?>
              <p><strong>Страна:</strong> <?php echo htmlspecialchars($user_data['country'] ?? 'Не указано'); ?></p>
              <p><strong>Город:</strong> <?php echo htmlspecialchars($user_data['city'] ?? 'Не указано'); ?></p>
              <p><strong>Рейтинг: </strong><?php echo number_format($average_rating, 1); ?> ★</p>
              <p><strong>Стоимость услуг:</strong> 
                <?= $user_data['service_cost'] ? htmlspecialchars($user_data['service_cost']) . ' ₽' : 'Не указана'; ?>
              </p>
              <p><strong>Жанры:</strong> <?= !empty($genres) ? implode(', ', $genres) : 'Не указаны'; ?></p>
            <?php endif; ?>

            <p class="profile-description"><?php echo nl2br(htmlspecialchars($user_data['description'] ?? 'Описание отсутствует')); ?></p>
          </div>

         
        </div>
    </div>
  </div>




    <section class="text-neutral-700 mt-8">
      <div class="container w-full">
          <div class="photo-grid">
              <?php foreach ($photos as $photo): 
                  $photo_id = $photo['id'];

                  // Получаем количество лайков
                  $likes_query = $pdo->prepare("SELECT COUNT(*) FROM likes WHERE photo_id = :photo_id");
                  $likes_query->execute(['photo_id' => $photo_id]);
                  $like_count = $likes_query->fetchColumn();

                  // Проверяем, ставил ли текущий пользователь лайк
                  $liked = false;
                  if ($user_id) {
                      $check_like_query = $pdo->prepare("SELECT COUNT(*) FROM likes WHERE photo_id = :photo_id AND user_id = :user_id");
                      $check_like_query->execute(['photo_id' => $photo_id, 'user_id' => $user_id]);
                      $liked = $check_like_query->fetchColumn() > 0;
                  }
                  // Получаем комментарии
                  $comments_query = $pdo->prepare("
                      SELECT comments.comment, users.name 
                      FROM comments 
                      JOIN users ON comments.user_id = users.id 
                      WHERE photo_id = :photo_id 
                      ORDER BY comments.created_at ASC
                  ");
                  $comments_query->execute(['photo_id' => $photo_id]);
                  $comments = $comments_query->fetchAll(PDO::FETCH_ASSOC);
              ?>
                  <div class="photo-item">
                      <a href="#photo-modal-<?= $photo_id; ?>" data-fancybox class="photo-link">
                          <img src="<?= htmlspecialchars($photo['url']); ?>" alt="<?= htmlspecialchars($photo['alt']); ?>" class="photo-img">
                      </a>
                      
                  </div>

                  <!-- Модальное окно для фото -->
                  <div id="photo-modal-<?= $photo_id; ?>" style="display: none; max-width: 600px; width: 400px; margin: auto;">
                    <a href="<?= htmlspecialchars($photo['url']); ?>" data-fancybox="gallery-<?= $photo_id; ?>" data-caption="<?= htmlspecialchars($photo['alt']); ?>">
                        <img src="<?= htmlspecialchars($photo['url']); ?>" alt="<?= htmlspecialchars($photo['alt']); ?>" class="w-full cursor-zoom-in">
                    </a>
                    <p>Фотограф: <?= htmlspecialchars($photo['photographer_name'] ?? 'Не указан'); ?></p>
                    <p>Модель: <?= htmlspecialchars($photo['client_name'] ?? 'Не указана'); ?></p>
                    <p class="text-lg font-bold mt-4"><?= htmlspecialchars($photo['alt']); ?></p>

                      <!-- Блок лайков и комментариев -->
                      <div class="likes-comments flex flex-col items-start mt-3 p-2 border-b">
                          <!-- Лайки -->
                          <div class="likes flex items-center space-x-2 mb-2">
                              <button class="like-button flex items-center space-x-1 px-3 py-1 border rounded <?= $liked ? 'liked' : '' ?>" data-photo-id="<?= $photo_id; ?>">
                                  <span class="heart text-2xl font-light"><?= $liked ? '❤️' : '🤍' ?></span> 
                                  <span id="likes-count-<?= $photo_id; ?>" class="text-md font-semibold"><?= $like_count; ?></span>
                              </button>
                          </div>

                          <!-- Кнопка "Комментарий" (сдвинута левее и стиль количества комментариев как у лайков) -->
                          <div class="comments flex items-center space-x-1">
                              <button id="comment-toggle-<?= $photo_id; ?>" class="comment-button flex items-center px-3 py-1" onclick="toggleComments(<?= $photo_id; ?>)">
                                  <span class="text-2xl">💬</span> 
                                  <span id="comments-count-<?= $photo_id; ?>" class="text-lg font-bold ml-1"><?= count($comments); ?></span>
                              </button>
                          </div>
                      </div>

                      <!-- Блок с комментариями (изначально скрыт) -->
                      <div id="comments-section-<?= $photo_id; ?>" class="comments mt-4 p-4 rounded flex flex-col" style="display: none; max-height: 300px; overflow-y: auto;">
                          <h3 class="text-lg font-bold mb-2">Комментарии</h3>
                          <div class="comment-list flex-grow">
                              <?php foreach ($comments as $comment): ?>
                                  <p class="text-sm"><strong><?= htmlspecialchars($comment['name']); ?>:</strong> <?= htmlspecialchars($comment['comment']); ?></p>
                              <?php endforeach; ?>
                          </div>

                          <!-- Форма для отправки комментария (закреплена внизу) -->
                          <div class="comment-input sticky bottom-0 bg-white p-2 border-t">
                              <form id="comment-form-<?= $photo_id; ?>" action="comment.php" method="POST" class="flex">
                                  <input type="hidden" name="photo_id" value="<?= $photo_id; ?>">
                                  <input type="text" name="comment" placeholder="Оставьте комментарий" required class="flex-grow p-2 border rounded">
                                  <button type="submit" <?= !$user_id ? 'disabled' : ''; ?> class="ml-2 btn btn-primary">Отправить</button>
                              </form>
                          </div>
                      </div>

                  </div>

                  <script>
                    function toggleComments(photoId) {
                        var commentSection = document.getElementById("comments-section-" + photoId);
                        var toggleButton = document.getElementById("comment-toggle-" + photoId);

                        if (commentSection.style.display === "none" || commentSection.style.display === "") {
                            commentSection.style.display = "block"; // Показываем комментарии
                            toggleButton.innerHTML = "<span class='text-2xl'>💬</span> <span class='text-md font-bold ml-1'>Скрыть комментарии</span>"; // Меняем текст кнопки
                            commentSection.scrollTop = commentSection.scrollHeight; // Прокрутка вниз
                        } else {
                            commentSection.style.display = "none"; // Скрываем комментарии
                            toggleButton.innerHTML = `<span class='text-2xl'>💬</span> <span id="comments-count-${photoId}" class='text-lg font-bold ml-1'>${commentSection.querySelectorAll('.comment-list p').length}</span>`;
                        }
                    }
                  </script>





              <?php endforeach; ?>
          </div>
      </div>
    </section>


    <script>
        Fancybox.bind("[data-fancybox]", {
            Toolbar: true,
            zoom: true,
            Image: {
                zoom: true,  // Включает зум при клике
            }
        });



    </script>
    <script>
        document.querySelectorAll(".like-button").forEach(button => {
        button.addEventListener("click", function (event) {
            event.preventDefault(); // Останавливаем стандартное поведение кнопки

            let photoId = this.dataset.photoId;
            let likeCountElement = document.querySelector(`#likes-count-${photoId}`);
            let heartElement = this.querySelector(".heart"); // Находим <span class="heart">
            

            fetch("like.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/x-www-form-urlencoded"
                },
                body: "photo_id=" + encodeURIComponent(photoId)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    likeCountElement.textContent = data.likes; // Обновляем количество лайков
                    heartElement.textContent = data.liked ? '❤️' : '🤍'; // Меняем символ сердца
                    
                    // Обновляем слово "лайков" в зависимости от количества
                    likeTextElement.textContent = getLikeWord(data.likes);
                } else {
                    alert(data.error);
                }
            })
            .catch(error => console.error("Ошибка:", error));
        });
      });

    </script>
    <script>
        document.addEventListener("DOMContentLoaded", function () {
          document.querySelectorAll("form[id^='comment-form-']").forEach(form => {
              form.addEventListener("submit", function (event) {
                  event.preventDefault();

                  let formData = new FormData(this);
                  let photoId = formData.get("photo_id");
                  let commentInput = this.querySelector("input[name='comment']");
                  let commentList = document.querySelector(`#comments-section-${photoId} .comment-list`);
                  let commentCount = document.getElementById(`comments-count-${photoId}`);

                  // Очищаем поле ввода сразу после нажатия кнопки "Отправить"
                  let commentText = commentInput.value.trim();
                  if (!commentText) return; // Если пустой, ничего не делаем

                  commentInput.value = ""; // Очистка поля ввода

                  fetch("comment.php", {
                      method: "POST",
                      body: formData
                  })
                  .then(response => response.json())
                  .then(data => {
                      if (data.success) {
                          // Создаём новый комментарий
                          let newComment = document.createElement("p");
                          newComment.classList.add("text-sm");
                          newComment.innerHTML = `<strong>${data.comment.name}:</strong> ${data.comment.text}`;

                          // Добавляем в список комментариев
                          commentList.appendChild(newComment);

                          // Обновляем количество комментариев
                          commentCount.textContent = parseInt(commentCount.textContent) + 1;

                          // Прокручиваем вниз
                          commentList.scrollTop = commentList.scrollHeight;
                      } else {
                          alert(data.error);
                      }
                  })
                  .catch(error => console.error("Ошибка:", error));
              });
          });
      });



    </script>
    


  </div>

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
            <li class="footer_menu-item">
              <a class="footer_menu-link" href="gallery.php">Галерея</a>
            </li>
            <li class="footer_menu-item">
              <a class="footer_menu-link" href="photographers.php">Фотографы</a></li>
            <li class="footer_menu-item">
              <a class="footer_menu-link" href="studios.php">Студии</a>
            </li>
          </ul>
        </div>
        <div class="footer_copyright">
          <p class="opaque-grey">Номер для связи: +7-977-123-45-67</p>
        </div>
      </div>
    </div>
  </footer>

  <script>
    Fancybox.bind("[data-fancybox]", {});
  </script>
  <script src="dist/fade_in.js"></script>
  <script src="dist/menu.js"></script>
  <script src="js/app.js"></script>
</body>

</html>