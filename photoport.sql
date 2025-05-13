-- phpMyAdmin SQL Dump
-- version 4.8.5
-- https://www.phpmyadmin.net/
--
-- Хост: 127.0.0.1:3306
-- Время создания: Май 13 2025 г., 23:11
-- Версия сервера: 10.3.13-MariaDB
-- Версия PHP: 7.1.22

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- База данных: `photoport`
--

-- --------------------------------------------------------

--
-- Структура таблицы `comments`
--

CREATE TABLE `comments` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `photo_id` int(11) NOT NULL,
  `comment` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Дамп данных таблицы `comments`
--

INSERT INTO `comments` (`id`, `user_id`, `photo_id`, `comment`, `created_at`) VALUES
(37, 2, 50, 'мне очень нравится эта фотография!', '2025-05-13 18:30:43'),
(38, 19, 49, 'мне очень нравится эта фотография!', '2025-05-13 18:39:07'),
(39, 19, 50, 'мне очень нравится эта фотография!', '2025-05-13 18:41:06'),
(40, 43, 50, 'очень круто!!!!', '2025-05-13 18:48:11');

-- --------------------------------------------------------

--
-- Структура таблицы `favorites`
--

CREATE TABLE `favorites` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `photographer_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Структура таблицы `genres`
--

CREATE TABLE `genres` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Дамп данных таблицы `genres`
--

INSERT INTO `genres` (`id`, `name`) VALUES
(5, 'Пейзажная'),
(1, 'Портретная'),
(3, 'Репортажная'),
(2, 'Свадебная'),
(4, 'Студийная');

-- --------------------------------------------------------

--
-- Структура таблицы `job_completion`
--

CREATE TABLE `job_completion` (
  `id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `photographer_id` int(11) NOT NULL,
  `client_confirmed` tinyint(1) DEFAULT 0,
  `photographer_confirmed` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Дамп данных таблицы `job_completion`
--

INSERT INTO `job_completion` (`id`, `client_id`, `photographer_id`, `client_confirmed`, `photographer_confirmed`) VALUES
(30, 43, 18, 1, 1);

-- --------------------------------------------------------

--
-- Структура таблицы `likes`
--

CREATE TABLE `likes` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `photo_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Дамп данных таблицы `likes`
--

INSERT INTO `likes` (`id`, `user_id`, `photo_id`, `created_at`) VALUES
(261, 2, 50, '2025-05-13 18:30:34'),
(262, 2, 49, '2025-05-13 18:31:03'),
(263, 20, 50, '2025-05-13 18:37:43'),
(264, 43, 50, '2025-05-13 18:47:59');

-- --------------------------------------------------------

--
-- Структура таблицы `messages`
--

CREATE TABLE `messages` (
  `id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `receiver_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Дамп данных таблицы `messages`
--

INSERT INTO `messages` (`id`, `sender_id`, `receiver_id`, `message`, `created_at`) VALUES
(4, 2, 1, 'uhghghghghjgh', '2025-03-03 16:05:55'),
(5, 1, 2, 'привет!', '2025-03-06 19:02:48'),
(36, 1, 18, 'привет', '2025-03-11 12:13:31'),
(39, 19, 2, 'привет', '2025-03-13 16:42:41'),
(40, 1, 20, 'привет', '2025-03-13 17:27:04'),
(41, 20, 1, 'тит', '2025-03-13 19:23:58'),
(42, 2, 18, 'апа', '2025-04-09 10:50:29'),
(50, 43, 18, 'Здравствуйте, я хотела бы провести фотосессию, какие у вас свободные дни?', '2025-05-13 18:48:54');

-- --------------------------------------------------------

--
-- Структура таблицы `photographer_genres`
--

CREATE TABLE `photographer_genres` (
  `photographer_id` int(11) NOT NULL,
  `genre_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Дамп данных таблицы `photographer_genres`
--

INSERT INTO `photographer_genres` (`photographer_id`, `genre_id`) VALUES
(1, 2),
(18, 4),
(19, 4),
(20, 1),
(20, 3),
(20, 5),
(27, 1),
(27, 4);

-- --------------------------------------------------------

--
-- Структура таблицы `photos`
--

CREATE TABLE `photos` (
  `id` int(11) NOT NULL,
  `url` varchar(255) NOT NULL,
  `alt` varchar(1000) NOT NULL,
  `user_id` int(11) NOT NULL,
  `photo_user_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Дамп данных таблицы `photos`
--

INSERT INTO `photos` (`id`, `url`, `alt`, `user_id`, `photo_user_id`) VALUES
(41, 'uploads/Полетаев Антон_и_Анастасия_1741892286.jpg', 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr', 20, 0),
(43, 'uploads/Полетаев Антон_и_Анастасия_1741892798.jpg', 'cvcv', 20, 2),
(45, 'uploads/xdcjkxlcc_и_Анастасия_1741896412.jpg', 'вава', 1, 2),
(48, 'uploads/Полетаев Антон_фото_1744215695.jpg', 'лох', 20, 2),
(49, 'uploads/Зиновьев Михаил Владимирович_фото_1746648800.jpeg', 'эта фотография описывает мою любовь к фотографиям', 18, NULL),
(50, 'uploads/Зиновьев Михаил Владимирович_фото_1746648938.jpg', 'спасибо Анастасии что согласилась сняться)', 18, 38),
(53, 'uploads/Зиновьев Михаил Владимирович_фото_1747162513.jpg', 'Моя одна из лучших работ!!!!', 18, 43);

-- --------------------------------------------------------

--
-- Структура таблицы `ratings`
--

CREATE TABLE `ratings` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `photographer_id` int(11) NOT NULL,
  `rating` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Дамп данных таблицы `ratings`
--

INSERT INTO `ratings` (`id`, `user_id`, `photographer_id`, `rating`, `created_at`) VALUES
(10, 43, 18, 5, '2025-05-13 18:56:56');

-- --------------------------------------------------------

--
-- Структура таблицы `roles`
--

CREATE TABLE `roles` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Дамп данных таблицы `roles`
--

INSERT INTO `roles` (`id`, `name`) VALUES
(6, 'admin'),
(1, 'client'),
(2, 'photographer');

-- --------------------------------------------------------

--
-- Структура таблицы `studios`
--

CREATE TABLE `studios` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `image_url` varchar(255) NOT NULL,
  `link` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Дамп данных таблицы `studios`
--

INSERT INTO `studios` (`id`, `name`, `description`, `image_url`, `link`, `created_at`) VALUES
(1, 'Студия Рига', 'от CROSS STUDIO', 'images/studio1.jpg', 'https://cross-studio.ru/studious?id=9&i=1471', '2025-03-06 21:08:48'),
(2, 'Зал Антресоль', 'от PROSTRANSTVO', 'images/studio2.jpg', 'https://prostranstvo.photo/rooms/antresol/', '2025-03-06 21:08:48'),
(3, 'Студия с аквариумом', 'от OCEAN STARS', 'images/studio3.jpg', 'https://oceanstars.ru/studiya-akvarium/', '2025-03-06 21:08:48'),
(4, 'Зал LOFT', 'от PINK PHOTO STUDIO', 'images/studio4.jpg', 'https://pink-photo.ru/loft/', '2025-03-06 21:08:48'),
(15, 'Студия Monday', 'от FRIDAY studios', 'uploads/studios/1746616607_1667044481.jpg', 'https://cross-studio.ru/studious?id=118', '2025-05-07 11:14:53'),
(16, 'Зал TOMMY', 'от PINK PHOTO STUDIO', 'https://pink-photo.ru/wp-content/uploads/2025/03/0036.jpg', 'https://pink-photo.ru/tommy/', '2025-05-07 13:11:53');

-- --------------------------------------------------------

--
-- Структура таблицы `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role_id` int(11) NOT NULL,
  `country` varchar(100) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `service_cost` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `profile_picture` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Дамп данных таблицы `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role_id`, `country`, `city`, `description`, `service_cost`, `created_at`, `profile_picture`) VALUES
(1, 'Дмитрий Иванов', 'antoha@mail.ru', '$2y$10$ICWtR5mJ4VrjjrWBm34Y8.8OxbXlKRqq.ygqNq6CzWn.3o.W4MlnG', 2, 'Россия', 'Москва', 'ывывывыв', '5000.00', '2025-02-24 21:24:28', 'uploads/profile_pictures/67bcdadc6f86d.jpg'),
(2, 'Анастасия', 'nastyasid04@gmail.com', '$2y$10$2MIGbi4CD8cf.oa8cikGHei.8OoSVdL7m6VlVsnK12JLPniYVo/52', 1, '', '', 'Я косплеер!', NULL, '2025-02-24 21:50:08', 'uploads/e2ec318333608d137b47ce8f2a83eed0.jpg'),
(18, 'Зиновьев Михаил Владимирович', 'gffgfg@fcgfg.ru', '$2y$10$3esYEddm.dKMyfgwc6w8HeQp8madf3f4yxj3ylo7NzEyPx3BsnCba', 2, 'Россия', 'Москва', 'Я фотограф ахаха', '1000.00', '2025-03-09 21:51:39', NULL),
(19, 'Леон Кеннеди', 'nast@gmail.com', '$2y$10$8W8JXYQ5MzV7503b4cWvvOom9HmhBgf8rUTycQfHLEaakxMtqsARO', 2, 'Россия', 'Москва', 'прррп', NULL, '2025-03-13 15:51:42', NULL),
(20, 'Полетаев Антон', 'sjdhsjdh@mail.ru', '$2y$10$qT0spK.ECZXi3WdHUaNPculTWgudJKfcuCIBBpRWRpnsE4V54Ig4i', 2, 'Россия', 'Омск', 'я фотограф', '1000.00', '2025-03-13 17:07:20', 'uploads/profile_pictures/67d310c82a46c.jpg'),
(28, 'Студия', 'ksdjklsdk@yandex.ru', '$2y$10$/K6nD3kdGmJIdJ8TEZkf2.vxTdCX7Pzk56U9xFgVRUW0sbrjGXLNi', 1, '', '', 'sfsffsfsf', NULL, '2025-05-05 20:23:01', 'uploads/profile_pictures/68191e2555dfc.jpg'),
(33, 'Admin', 'photoport@yandex.ru', '$2y$10$qnDtXedjhbc3lJHl1gvNfOcazuE576kwTNT/AIwjDNToNrG6T5F82', 6, NULL, NULL, NULL, NULL, '2025-05-05 20:53:45', NULL),
(38, 'Сидельникова', 'gffgfg@mail.ru', '$2y$10$bEr8gVeW1W1IEhIFDEVxYOyriP7P5oym8o4gDpZ58NWmpSkQNgnEm', 1, '', '', '', NULL, '2025-05-07 11:51:29', NULL),
(43, 'Сидельникова Анастасия Антоновна', 'nastosya@gmail.com', '$2y$10$hOnv.HM/WgOwCH5aDZD/auBqKf0TOlCulORoK9S.oeo5uOgwfNlWm', 1, '', '', 'Меня зовут Настя и я косплеер!!!', NULL, '2025-05-13 18:12:47', 'uploads/somwarpet.jpg');

--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `comments`
--
ALTER TABLE `comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `photo_id` (`photo_id`);

--
-- Индексы таблицы `favorites`
--
ALTER TABLE `favorites`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `photographer_id` (`photographer_id`);

--
-- Индексы таблицы `genres`
--
ALTER TABLE `genres`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Индексы таблицы `job_completion`
--
ALTER TABLE `job_completion`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_pair` (`client_id`,`photographer_id`);

--
-- Индексы таблицы `likes`
--
ALTER TABLE `likes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`,`photo_id`),
  ADD KEY `photo_id` (`photo_id`);

--
-- Индексы таблицы `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sender_id` (`sender_id`),
  ADD KEY `receiver_id` (`receiver_id`);

--
-- Индексы таблицы `photographer_genres`
--
ALTER TABLE `photographer_genres`
  ADD PRIMARY KEY (`photographer_id`,`genre_id`),
  ADD KEY `genre_id` (`genre_id`);

--
-- Индексы таблицы `photos`
--
ALTER TABLE `photos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Индексы таблицы `ratings`
--
ALTER TABLE `ratings`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Индексы таблицы `studios`
--
ALTER TABLE `studios`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `role_id` (`role_id`);

--
-- AUTO_INCREMENT для сохранённых таблиц
--

--
-- AUTO_INCREMENT для таблицы `comments`
--
ALTER TABLE `comments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT для таблицы `favorites`
--
ALTER TABLE `favorites`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=99;

--
-- AUTO_INCREMENT для таблицы `genres`
--
ALTER TABLE `genres`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT для таблицы `job_completion`
--
ALTER TABLE `job_completion`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT для таблицы `likes`
--
ALTER TABLE `likes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=265;

--
-- AUTO_INCREMENT для таблицы `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- AUTO_INCREMENT для таблицы `photos`
--
ALTER TABLE `photos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=54;

--
-- AUTO_INCREMENT для таблицы `ratings`
--
ALTER TABLE `ratings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT для таблицы `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT для таблицы `studios`
--
ALTER TABLE `studios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT для таблицы `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- Ограничения внешнего ключа сохраненных таблиц
--

--
-- Ограничения внешнего ключа таблицы `comments`
--
ALTER TABLE `comments`
  ADD CONSTRAINT `comments_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `comments_ibfk_2` FOREIGN KEY (`photo_id`) REFERENCES `photos` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
