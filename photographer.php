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

// Проверка авторизации
$is_logged_in = isset($_SESSION['user_name']);
$user_name = $_SESSION['user_name'] ?? null;
$current_user_id = $is_logged_in ? $_SESSION['user_id'] : null;





// Получаем ID пользователя из параметра URL
$author_id = $_GET['id'] ?? null;

if ($author_id) {
    // Загружаем данные пользователя
    $stmt = $conn->prepare("SELECT users.name, users.id, roles.name AS author_role, users.profile_picture, users.country, users.city, users.description, users.service_cost
                        FROM users 
                        JOIN roles ON users.role_id = roles.id
                        WHERE users.id = ?");

    $stmt->bind_param("i", $author_id);
    $stmt->execute();
    $stmt->bind_result($author_name, $author_id, $author_role, $profile_picture, $country, $city, $description, $service_cost);
    $stmt->fetch();
    $stmt->close();
}

if ($is_logged_in && $_SESSION['user_name'] === $author_name){
  header("Location: profile.php");
  exit();
}

// Получаем жанры фотографа
$genres_stmt = $conn->prepare("SELECT genres.name FROM genres 
                               JOIN photographer_genres ON genres.id = photographer_genres.genre_id 
                               WHERE photographer_genres.photographer_id = ?");
$genres_stmt->bind_param("i", $author_id);
$genres_stmt->execute();
$genres_result = $genres_stmt->get_result();
$genres = [];
while ($row = $genres_result->fetch_assoc()) {
    $genres[] = $row['name'];
}
$genres_stmt->close();


// Получаем средний рейтинг для фотографа
$rating_query = $conn->prepare("SELECT AVG(rating) FROM ratings WHERE photographer_id = ?");
$rating_query->bind_param("i", $author_id);
$rating_query->execute();
$rating_result = $rating_query->get_result();
$average_rating = $rating_result->fetch_row()[0];
$rating_query->close();

// Если рейтинг еще не выставлен, то установить значение по умолчанию
$average_rating = $average_rating ? round($average_rating, 1) : "Нет оценок";



// Получаем фотографии пользователя
// Получаем фотографии пользователя с информацией о фотографе и клиенте
$photos_stmt = $conn->prepare(
  "SELECT p.id, p.url, p.alt, 
          pu.name AS photographer_name, 
          pc.name AS client_name
   FROM photos p
   LEFT JOIN users pu ON (p.user_id = pu.id AND pu.role_id = 2) OR (p.photo_user_id = pu.id AND pu.role_id = 2)
   LEFT JOIN users pc ON (p.user_id = pc.id AND pc.role_id = 1) OR (p.photo_user_id = pc.id AND pc.role_id = 1)
   WHERE p.user_id = ? OR p.photo_user_id = ?"
);
$photos_stmt->bind_param("ii", $author_id, $author_id);
$photos_stmt->execute();
$photos_result = $photos_stmt->get_result();
$photos = $photos_result->fetch_all(MYSQLI_ASSOC);
$photos_stmt->close();

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
      <h1 class="text-4xl font-bold mr-4">Профиль пользователя</h1>
      <div class="profile-info mt-6 p-6">
        <div class="profile-image">
          <img src="<?php echo !empty($profile_picture) ? htmlspecialchars($profile_picture) : 'images/default_profile.jpg'; ?>" alt="Фото профиля" class="rounded-full w-32 h-32 object-cover shadow-md">
        </div>

        <div class="profile-details">
          <h2 class="text-xl font-semibold mb-2">
              <?php echo htmlspecialchars($author_name); ?>
          </h2>

          <?php if ($author_role === 'photographer'): ?>
              <p><strong>Страна:</strong> <?php echo htmlspecialchars($country ?? 'Не указано'); ?></p>
              <p><strong>Город:</strong> <?php echo htmlspecialchars($city ?? 'Не указано'); ?></p>
              <p><strong>Стоимость услуг:</strong> <?php echo $service_cost ? htmlspecialchars($service_cost) . ' ₽' : 'Не указана'; ?></p>
              <p><strong>Рейтинг:</strong> <?php 
                      if ($average_rating !== "Нет оценок") {
                          echo "" . $average_rating . ★; 
                      } else {
                          echo $average_rating;
                      }
                  ?>
              </p>
              <?php if (!empty($genres)): ?>
                  <p><strong>Жанры:</strong> <?php echo implode(", ", $genres); ?></p>
              <?php else: ?>
                  <p><strong>Жанры:</strong> Не указаны</p>
              <?php endif; ?>

              <p><?php echo nl2br(htmlspecialchars($description ?? 'Нет описания')); ?></p>
          <?php endif; ?>
        </div>


        <div class="profile-buttons">
          <a href="send_message.php?receiver_id=<?= $author_id ?>" class="editbtn">Написать сообщение</a>
        </div>
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
                $likes_query = $conn->prepare("SELECT COUNT(*) FROM likes WHERE photo_id = ?");
                $likes_query->bind_param("i", $photo_id);
                $likes_query->execute();
                $like_count = $likes_query->get_result()->fetch_row()[0];

                // Проверяем, ставил ли текущий пользователь лайк
                $liked = false;
                if ($current_user_id) {
                    $check_like_query = $conn->prepare("SELECT COUNT(*) FROM likes WHERE photo_id = ? AND user_id = ?");
                    $check_like_query->bind_param("ii", $photo_id, $current_user_id);
                    $check_like_query->execute();
                    $liked = $check_like_query->get_result()->fetch_row()[0] > 0;
                }

                // Получаем комментарии
                $comments_query = $conn->prepare("SELECT comments.comment, users.name 
                                                  FROM comments 
                                                  JOIN users ON comments.user_id = users.id 
                                                  WHERE photo_id = ? 
                                                  ORDER BY comments.created_at ASC");
                $comments_query->bind_param("i", $photo_id);
                $comments_query->execute();
                $comments_result = $comments_query->get_result();
                $comments = $comments_result->fetch_all(MYSQLI_ASSOC);
            ?>
                <div class="photo-item">
                    <a href="#photo-modal-<?= $photo_id; ?>" data-fancybox class="photo-link">
                        <img src="<?= htmlspecialchars($photo['url']); ?>" alt="<?= htmlspecialchars($photo['alt']); ?>" class="photo-img">
                    </a>
                </div>
                <?php if (isset($_GET['photo'])): ?>
                    <script>
                        document.addEventListener("DOMContentLoaded", function() {
                            const targetPhotoId = "<?php echo (int)$_GET['photo']; ?>";
                            const targetLink = document.querySelector(`a[href="#photo-modal-${targetPhotoId}"]`);
                            if (targetLink) {
                                targetLink.click(); // Fancybox откроет нужное окно
                            }
                        });
                    </script>
                  <?php endif; ?>


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
                                <button type="submit" class="ml-2 btn btn-primary">Отправить</button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
  </section>

    <script>
        Fancybox.bind("[data-fancybox]", {
            Toolbar: true,
            zoom: true,
            Image: {
                zoom: true,
            }
        });
    </script>

    <script>
        document.querySelectorAll(".like-button").forEach(button => {
          button.addEventListener("click", function (event) {
              event.preventDefault(); 

              let photoId = this.dataset.photoId;
              let likeCountElement = document.querySelector(`#likes-count-${photoId}`);
              let heartElement = this.querySelector(".heart");

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
                      likeCountElement.textContent = data.likes;
                      heartElement.textContent = data.liked ? '❤️' : '🤍'; 
                  } else {
                      alert(data.error);
                  }
              })
              .catch(error => {
                  console.error("Ошибка при отправке запроса:", error);
              });
          });
    });

    function toggleComments(photoId) {
        const commentSection = document.getElementById("comments-section-" + photoId);
        const commentsToggleButton = document.getElementById("comment-toggle-" + photoId);
        const currentState = commentSection.style.display;
        
        if (currentState === "none" || currentState === "") {
            commentSection.style.display = "block";
        } else {
            commentSection.style.display = "none";
        }
    }
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
              <a class="footer_menu-link" href="photographers.php">Фотографы</a>
            </li>
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