<?php
session_start();

// Проверяем, авторизован ли администратор
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: auth.php");
    exit;
}

// Подключение к базе данных
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "photoport";
$conn = new mysqli($servername, $username, $password, $dbname);

// Проверка соединения
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (isset($_POST['logout'])) {
    session_destroy();
    header("Location: auth.php");
    exit;
}

// Добавление студии
if (isset($_POST['add_studio'])) {
    $name = $_POST['name'];
    $description = $_POST['description'];
    $link = $_POST['link'];

    if (isset($_FILES['image']['name']) && $_FILES['image']['name'] != '') {
        $image = $_FILES['image'];
        $uploadDir = 'uploads/studios/';
        $imageName = time() . '_' . basename($image['name']);
        $uploadFilePath = $uploadDir . $imageName;

        if (move_uploaded_file($image['tmp_name'], $uploadFilePath)) {
            $image_url = $uploadFilePath;
        } else {
            $_SESSION['message'] = ['text' => 'Ошибка загрузки изображения.', 'type' => 'danger'];
            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
        }
    } else {
        $image_url = $_POST['image_url'];
    }

    $sql = "INSERT INTO studios (name, description, image_url, link) VALUES ('$name', '$description', '$image_url', '$link')";
    if ($conn->query($sql) === TRUE) {
        $_SESSION['message'] = ['text' => 'Студия успешно добавлена.', 'type' => 'success'];
    } else {
        $_SESSION['message'] = ['text' => 'Ошибка: ' . $conn->error, 'type' => 'danger'];
    }
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Удаление студии
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $sql = "DELETE FROM studios WHERE id=$id";
    if ($conn->query($sql) === TRUE) {
        $_SESSION['message'] = ['text' => 'Студия успешно удалена.', 'type' => 'success'];
    } else {
        $_SESSION['message'] = ['text' => 'Ошибка: ' . $conn->error, 'type' => 'danger'];
    }
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Редактирование студии
if (isset($_POST['edit_studio'])) {
    $id = $_POST['id'];
    $name = $_POST['name'];
    $description = $_POST['description'];
    $link = $_POST['link'];

    if (isset($_FILES['image']['name']) && $_FILES['image']['name'] != '') {
        $image = $_FILES['image'];
        $uploadDir = 'uploads/studios/';
        $imageName = time() . '_' . basename($image['name']);
        $uploadFilePath = $uploadDir . $imageName;

        if (move_uploaded_file($image['tmp_name'], $uploadFilePath)) {
            $image_url = $uploadFilePath;
        } else {
            $_SESSION['message'] = ['text' => 'Ошибка загрузки изображения.', 'type' => 'danger'];
            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
        }
    } else {
        $image_url = $_POST['image_url'];
    }

    $sql = "UPDATE studios SET name='$name', description='$description', image_url='$image_url', link='$link' WHERE id=$id";
    if ($conn->query($sql) === TRUE) {
        $_SESSION['message'] = ['text' => 'Студия успешно обновлена.', 'type' => 'info'];
    } else {
        $_SESSION['message'] = ['text' => 'Ошибка: ' . $conn->error, 'type' => 'danger'];
    }
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Получаем список студий
$sql = "SELECT * FROM studios";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Панель администратора</title>
    <link rel="icon" type="image/x-icon" href="images/1.ico" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="mb-4">Панель администратора: Студии</h1>
        <form method="POST">
            <button type="submit" name="logout" class="btn btn-black">Выйти</button>
        </form>
    </div>

    <!-- Уведомления -->
    <?php if (isset($_SESSION['message'])): ?>
        <div class="alert alert-<?php echo $_SESSION['message']['type']; ?> alert-dismissible fade show" role="alert">
            <?php echo $_SESSION['message']['text']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Закрыть"></button>
        </div>
        <?php unset($_SESSION['message']); ?>
    <?php endif; ?>

    <!-- Форма добавления студии -->
    <div class="card mb-4">
        <div class="card-header">Добавить студию</div>
        <div class="card-body">
            <form method="POST" action="" enctype="multipart/form-data">
                <div class="mb-3">
                    <label for="name" class="form-label">Название студии</label>
                    <input type="text" class="form-control" id="name" name="name" required>
                </div>
                <div class="mb-3">
                    <label for="description" class="form-label">Кампания</label>
                    <input type="text" class="form-control" id="description" name="description" required>
                </div>
                <div class="mb-3">
                    <label for="image_url" class="form-label">URL изображения (если нет файла)</label>
                    <input type="text" class="form-control" id="image_url" name="image_url">
                    <small class="form-text text-muted">Введите URL изображения, если не хотите загружать файл.</small>
                </div>
                <div class="mb-3">
                    <label for="image" class="form-label">Изображение (если нет URL)</label>
                    <input type="file" class="form-control" id="image" name="image">
                    <small class="form-text text-muted">Загрузите файл, если хотите использовать изображение с вашего компьютера.</small>
                </div>
                <div class="mb-3">
                    <label for="link" class="form-label">Ссылка на бронирование</label>
                    <input type="text" class="form-control" id="link" name="link" required>
                </div>
                <button type="submit" class="btn btn-primary" name="add_studio">Добавить</button>
            </form>
        </div>
    </div>

    <!-- Таблица студий -->
    <div class="card">
        <div class="card-header">Список студий</div>
        <div class="card-body">
            <table class="table">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Название</th>
                    <th>Кампания</th>
                    <th>Изображение</th>
                    <th>Ссылка</th>
                    <th>Действия</th>
                </tr>
                </thead>
                <tbody>
                <?php while($row = $result->fetch_assoc()) { ?>
                    <tr>
                        <td><?php echo $row['id']; ?></td>
                        <td><?php echo $row['name']; ?></td>
                        <td><?php echo $row['description']; ?></td>
                        <td><img src="<?php echo $row['image_url']; ?>" alt="Studio Image" width="100"></td>
                        <td><a href="<?php echo $row['link']; ?>" target="_blank">Ссылка</a></td>
                        <td>
                            <a href="edit_studio.php?id=<?php echo $row['id']; ?>" class="btn btn-warning btn-sm">Редактировать</a>
                            <a href="?delete=<?php echo $row['id']; ?>" class="btn btn-danger btn-sm">Удалить</a>
                        </td>
                    </tr>
                <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
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

<?php $conn->close(); ?>
