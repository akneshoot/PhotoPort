CREATE DATABASE photoport;

USE photoport;

-- Создаем таблицу ролей
CREATE TABLE roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE
);

-- Добавляем роли "client" (клиент) и "photographer" (фотограф)
INSERT INTO roles (name) VALUES ('client'), ('photographer');

-- Создаем таблицу пользователей
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role_id INT NOT NULL,
    country VARCHAR(100) DEFAULT NULL,
    city VARCHAR(100) DEFAULT NULL,
    description TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE
);

ALTER TABLE users
ADD COLUMN profile_picture VARCHAR(255) DEFAULT NULL;


CREATE TABLE studios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    image_url VARCHAR(255) NOT NULL,
    link VARCHAR(255) NOT NULL
);

-- Добавляем студии в таблицу
INSERT INTO studios (name, description, image_url, link) VALUES
('Студия Рига', 'от CROSS STUDIO', 'images/studio1.jpg', 'https://cross-studio.ru/studious?id=9&i=1471'),
('Зал Антресоль', 'от PROSTRANSTVO', 'images/studio2.jpg', 'https://prostranstvo.photo/rooms/antresol/'),
('Студия с аквариумом', 'от OCEAN STARS', 'images/studio3.jpg', 'https://oceanstars.ru/studiya-akvarium/'),
('Зал LOFT', 'от PINK PHOTO STUDIO', 'images/studio4.jpg', 'https://pink-photo.ru/loft/');

CREATE TABLE photos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    url VARCHAR(255) NOT NULL,
    alt VARCHAR(255) NOT NULL
);



INSERT INTO photos (url, alt) VALUES
('images/architecturals.jpg', 'a person standing in front of a rock formation'),
('images/18-days-voyage.jpg', 'a cat laying on top of a sidewalk next to the ocean'),
('images/beautiful-stories.jpg', 'a person standing in front of a rock formation'),
('images/behind-the-waves.jpg', 'a cat laying on top of a sidewalk next to the ocean'),
('images/dark-forest.jpg', 'a person standing in front of a rock formation'),
('images/rage-of-the-sea.jpg', 'a cat laying on top of a sidewalk next to the ocean'),
('images/6447922023.jpg', 'a man standing on a beach next to the ocean');

-- Таблица лайков
CREATE TABLE likes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    photo_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (user_id, photo_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (photo_id) REFERENCES photos(id) ON DELETE CASCADE
);

-- Таблица комментариев
CREATE TABLE comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    photo_id INT NOT NULL,
    comment TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (photo_id) REFERENCES photos(id) ON DELETE CASCADE
);

CREATE TABLE `messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sender_id` int(11) NOT NULL,
  `receiver_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`receiver_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


CREATE TABLE favorites (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    photographer_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (photographer_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE ratings (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    photographer_id INT NOT NULL,
    rating INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

ALTER TABLE `users` 
ADD COLUMN `service_cost` DECIMAL(10, 2) DEFAULT NULL AFTER `description`;




