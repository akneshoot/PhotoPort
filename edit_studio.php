<?php
// Подключение к базе данных
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "photoport";

// Создаем соединение
$conn = new mysqli($servername, $username, $password, $dbname);

// Проверка соединения
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Получаем ID студии из URL
if (isset($_GET['id'])) {
    $id = $_GET['id'];
    
    // Получаем данные студии по ID
    $sql = "SELECT * FROM studios WHERE id=$id";
    $result = $conn->query($sql);

    // Если студия найдена, загружаем данные
    if ($result->num_rows > 0) {
        $studio = $result->fetch_assoc();
    } else {
        echo "Studio not found!";
        exit;
    }
} else {
    echo "No studio ID provided!";
    exit;
}

// Обработка формы редактирования
if (isset($_POST['edit_studio'])) {
    $name = $_POST['name'];
    $description = $_POST['description'];
    $link = $_POST['link'];
    
    // Проверка, был ли загружен файл изображения
    if (isset($_FILES['image']['name']) && $_FILES['image']['name'] != '') {
        $image = $_FILES['image'];
        
        // Указываем папку для загрузки (используем абсолютный путь)
        $uploadDir = __DIR__ . '/uploads/studios/';  // Использование абсолютного пути
        // Проверяем, существует ли папка и если нет - создаём её
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);  // Рекурсивное создание папки с правами на запись
        }
        
        // Генерируем уникальное имя для файла
        $imageName = time() . '_' . basename($image['name']);
        
        // Путь для сохранения изображения
        $uploadFilePath = $uploadDir . $imageName;
        
        // Перемещаем файл в нужную директорию
        if (move_uploaded_file($image['tmp_name'], $uploadFilePath)) {
            $image_url = 'uploads/studios/' . $imageName;  // Сохраняем путь к изображению в базе данных
        } else {
            echo "Ошибка загрузки изображения.";
            exit;
        }
    } else {
        // Если изображение не было загружено, оставляем старое изображение
        $image_url = $studio['image_url'];
    }
    
    // Обновляем данные студии в базе
    $sql = "UPDATE studios SET name='$name', description='$description', image_url='$image_url', link='$link' WHERE id=$id";
    if ($conn->query($sql) === TRUE) {
        header('Location: admin.php');  // Перенаправление на страницу админки
        exit;
    } else {
        echo "Error: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Редактировать студию</title>
    <link rel="icon" type="image/x-icon" href="images/1.ico" />
    <!-- Подключение Bootstrap для стилизации -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    
</head>
<body>
<div class="container mt-5">
    <h1 class="mb-4">Редактировать студию</h1>
    
    <!-- Форма редактирования студии -->
    <div class="card">
        <div class="card-header">Редактировать студию: <?php echo $studio['name']; ?></div>
        <div class="card-body">
            <form method="POST" action="" enctype="multipart/form-data">
                <input type="hidden" name="id" value="<?php echo $studio['id']; ?>">

                <div class="mb-3">
                    <label for="name" class="form-label">Название</label>
                    <input type="text" class="form-control" id="name" name="name" value="<?php echo $studio['name']; ?>" required>
                </div>
                <div class="mb-3">
                    <label for="description" class="form-label">От какой кампании</label>
                    <input type="text" class="form-control" id="description" name="description" value="<?php echo htmlspecialchars($studio['description']); ?>" required>
                </div>


                <div class="mb-3">
                    <label for="image" class="form-label">Изображение</label>
                    <input type="file" class="form-control" id="image" name="image">
                    <?php if ($studio['image_url']) { ?>
                        <p class="mt-2">Текущее изображение:</p>
                        <img src="<?php echo $studio['image_url']; ?>" alt="Current Image" class="img-thumbnail" style="max-width: 200px;">
                    <?php } ?>
                </div>

                <div class="mb-3">
                    <label for="link" class="form-label">Ссылка на бронирование</label>
                    <input type="text" class="form-control" id="link" name="link" value="<?php echo $studio['link']; ?>" required>
                </div>

                <button type="submit" class="btn btn-primary" name="edit_studio">Сохранить изменения</button>
            </form>
        </div>
    </div>

    <!-- Кнопка для возврата к списку студий -->
    <a href="admin.php" class="btn btn-secondary mt-4">Назад к списку студий</a>
</div>

<style>
    .btn {
        display: inline-block;
        border: none;
        outline: none;
        padding: 11px 25px;
        background-color: #000000;
        color: #ffffff;
    }

    .btn:hover {
        background-color: #dfdfdf;
        color: #000000;
    }

    .btn-black {
        background-color: #000000;
        color: #ffffff;
        -webkit-transition: all .4s;
        transition: all .4s;
    }

    .btn-black:hover {
        background-color: #dfdfdf;
        color: #000000;
    }
</style>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
// Закрытие соединения
$conn->close();
?>
