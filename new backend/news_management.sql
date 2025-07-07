-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 27, 2025 at 03:38 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `news_management`
--

-- --------------------------------------------------------

--
-- Table structure for table `articles`
--

CREATE TABLE `articles` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `image_path` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `articles`
--

INSERT INTO `articles` (`id`, `title`, `content`, `created_at`, `image_path`) VALUES
(1, 'Breaking News: New Policy Announced', 'The government has announced a new policy...', '2025-06-25 09:47:23', NULL),
(2, 'Sports Update: Local Team Wins', 'The local team secured a victory in...', '2025-06-25 09:47:23', NULL),
(3, 'Weather Alert: Heavy Rain Expected', 'Meteorologists warn of heavy rain...', '2025-06-25 09:47:23', NULL),
(4, 'hehe', 'sample content', '2025-06-25 09:57:34', '../../uploads/685b578ed914e_Final.png');

-- --------------------------------------------------------

--
-- Table structure for table `article_tags`
--

CREATE TABLE `article_tags` (
  `article_id` int(11) NOT NULL,
  `tag_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `article_tags`
--

INSERT INTO `article_tags` (`article_id`, `tag_id`) VALUES
(1, 1),
(2, 2),
(3, 3),
(4, 4);

-- --------------------------------------------------------

--
-- Table structure for table `featured_media`
--

CREATE TABLE `featured_media` (
  `id` int(11) NOT NULL,
  `url` varchar(512) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `featured_media`
--

INSERT INTO `featured_media` (`id`, `url`, `title`, `description`, `created_at`) VALUES
(1, 'https://www.facebook.com/share/v/161oLBa6oA/', 'EL CASTIGADOR WITH ROGIE PANGILINAN 03/01/24', 'K5 NEWS FM OLONGAPO IS LOCATED @ 3rd Floor Macariola Building Rizal Avenue Infront of Olongapo City Hall, Olongapo, Philippines, 2200. • CONTACT US @ SMART:0929-437-4124, GLOBE: 0915-0630115 FOR CALLS & SMS OR CONTACT US THROUGH OUR FB PAGE MESSENGER: 88.7 K5 NEWS FM OLONGAPO CITY• MAARING TUMUTOK SA K5 NEWS FM 88.7 SA FB LIVE. LAHAT NG IYAN AY HATID SA INYO NG DTX 500 ANG SUPER-ANTIOXIDANT, DTX COFFEE MIX ANG HEALTHY NA KAPE AT MIGHTY CEE ANG ALKALINE VITAMIN C •', '2025-06-25 05:20:32');

-- --------------------------------------------------------

--
-- Table structure for table `live_updates`
--

CREATE TABLE `live_updates` (
  `id` int(11) NOT NULL,
  `message` text NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `live_updates`
--

INSERT INTO `live_updates` (`id`, `message`, `created_at`) VALUES
(1, 'Urgent: City council meeting at 5 PM today.', '2025-06-25 09:47:23'),
(2, 'Traffic update: Main street closed for repairs.', '2025-06-25 09:47:23');

-- --------------------------------------------------------

--
-- Table structure for table `program_schedule`
--

CREATE TABLE `program_schedule` (
  `id` int(11) NOT NULL,
  `program_name` varchar(100) NOT NULL,
  `day_of_week` varchar(10) NOT NULL,
  `time_slot` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `program_schedule`
--

INSERT INTO `program_schedule` (`id`, `program_name`, `day_of_week`, `time_slot`) VALUES
(1, 'Morning News', 'Monday', '08:00 - 09:00'),
(2, 'Sports Hour', 'Monday', '09:00 - 10:00'),
(3, 'Weather Watch', 'Tuesday', '08:00 - 08:30'),
(4, 'El Castigador', 'Mondays - ', '4:30 - 6:00'),
(5, '11 MBPS', 'Monday', '11:00 - 12:00'),
(6, '11 MBPS', 'Tuesday', '11:00 - 12:00'),
(7, '11 MBPS', 'Wednesday', '11:00 - 01:00'),
(8, '11 MBPS', 'Thursday', '11:00 - 12:00'),
(9, '11 MBPS', 'Friday', '11:00 - 12:00'),
(10, '11 MBPS', 'Saturday', '11:00 - 12:00');

-- --------------------------------------------------------

--
-- Table structure for table `station_members`
--

CREATE TABLE `station_members` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `position` varchar(100) NOT NULL,
  `program` varchar(150) DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `station_members`
--

INSERT INTO `station_members` (`id`, `name`, `position`, `program`, `image_url`) VALUES
(1, 'Ms. Aileen Cuevas-Sanchez', 'Station Manager', '', 'https://www.google.com/imgres?q=fb%20silhouette&imgurl=https%3A%2F%2Fwww.drupal.org%2Ffiles%2Fissues%2F10354686_10150004552801856_220367501106153455_n.jpg&imgrefurl=https%3A%2F%2Fwww.drupal.org%2Fproject%2Fsimple_fb_connect%2Fissues%2F2838644&docid=kuzus0');

-- --------------------------------------------------------

--
-- Table structure for table `tags`
--

CREATE TABLE `tags` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tags`
--

INSERT INTO `tags` (`id`, `name`) VALUES
(5, 'Culture'),
(1, 'Politics'),
(2, 'Sports'),
(4, 'Technology'),
(3, 'Weather');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`) VALUES
(1, 'admin', '$2y$10$wHk6v1n6K2QwQn8QwQn8QeQn8QwQn8QwQn8QwQn8QwQn8QwQn8Qe'),
(2, 'admin2', '$2y$10$Db56EcUtlJfSbhW5jnL/uegXYeOYCI42n4wt5bZEa2pnjFgvE9Oua');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `articles`
--
ALTER TABLE `articles`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `article_tags`
--
ALTER TABLE `article_tags`
  ADD PRIMARY KEY (`article_id`,`tag_id`),
  ADD KEY `tag_id` (`tag_id`);

--
-- Indexes for table `featured_media`
--
ALTER TABLE `featured_media`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `live_updates`
--
ALTER TABLE `live_updates`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `program_schedule`
--
ALTER TABLE `program_schedule`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `station_members`
--
ALTER TABLE `station_members`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tags`
--
ALTER TABLE `tags`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `articles`
--
ALTER TABLE `articles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `featured_media`
--
ALTER TABLE `featured_media`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `live_updates`
--
ALTER TABLE `live_updates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `program_schedule`
--
ALTER TABLE `program_schedule`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `station_members`
--
ALTER TABLE `station_members`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `tags`
--
ALTER TABLE `tags`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `article_tags`
--
ALTER TABLE `article_tags`
  ADD CONSTRAINT `article_tags_ibfk_1` FOREIGN KEY (`article_id`) REFERENCES `articles` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `article_tags_ibfk_2` FOREIGN KEY (`tag_id`) REFERENCES `tags` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
