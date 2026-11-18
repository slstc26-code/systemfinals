-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 18, 2026 at 03:43 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `finals`
--

-- --------------------------------------------------------

--
-- Table structure for table `homepage`
--

CREATE TABLE `homepage` (
  `id` int(11) NOT NULL,
  `fullname` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `homepage`
--

INSERT INTO `homepage` (`id`, `fullname`, `email`, `password`) VALUES
(1, 'Dean Pierre Enriquez', 'slstc26@gmail.com', '$2y$10$l7xruLXXOjVq0YV7Fp.vKOKpAurul/3wSU1W/r628pcfRuDyyRgzm');

-- --------------------------------------------------------

--
-- Table structure for table `rooms`
--

CREATE TABLE `rooms` (
  `id` int(11) NOT NULL,
  `room_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rooms`
--

INSERT INTO `rooms` (`id`, `room_name`, `description`, `created_by`, `created_at`) VALUES
(1, 'admin', 'admin', 1, '2026-11-10 18:15:45'),
(2, 'Jomar Sangil', 'Finals - System', 1, '2026-11-10 18:31:22'),
(4, 'Room 1', 'Test', 1, '2026-11-12 02:08:49');

-- --------------------------------------------------------

--
-- Table structure for table `room_files`
--

CREATE TABLE `room_files` (
  `id` int(11) NOT NULL,
  `room_id` int(11) NOT NULL,
  `uploaded_by` int(11) NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `stored_name` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_size` bigint(20) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `admin_viewed_at` datetime DEFAULT NULL,
  `notification_dismissed` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `room_files`
--

INSERT INTO `room_files` (`id`, `room_id`, `uploaded_by`, `original_name`, `stored_name`, `file_path`, `file_size`, `uploaded_at`, `admin_viewed_at`, `notification_dismissed`) VALUES
(1, 2, 2, 'uly47.jpg', 'fef8b2382239d101846d0884089a063d.jpg', 'uploads/rooms/fef8b2382239d101846d0884089a063d.jpg', 351568, '2026-11-10 18:36:33', NULL, 0),
(2, 1, 2, 'CASE-STUDY_for-School-stress-and-pressure (1).pdf', '9935198ab5ae0fd7fc5e504973cf37d8.pdf', 'uploads/rooms/9935198ab5ae0fd7fc5e504973cf37d8.pdf', 176018, '2026-11-10 18:42:35', NULL, 0),
(3, 1, 11, 'uly47.jpg', 'e3e2e8a4b59d6729a4f9d202331ff9d1.jpg', 'uploads/rooms/e3e2e8a4b59d6729a4f9d202331ff9d1.jpg', 351568, '2026-11-11 02:04:58', NULL, 0),
(5, 4, 2, 'uly47.jpg', '85028ef081b6457ff008898d4452a8e6.jpg', 'uploads/rooms/85028ef081b6457ff008898d4452a8e6.jpg', 351568, '2026-11-12 02:12:35', NULL, 0),
(6, 2, 2, 'uly47.jpg', 'bb5494889e6a466ba03b4d391a8a2c9e.jpg', 'uploads/rooms/bb5494889e6a466ba03b4d391a8a2c9e.jpg', 351568, '2026-11-13 15:49:45', NULL, 0);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(255) NOT NULL,
  `fullname` varchar(100) NOT NULL,
  `username` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(100) NOT NULL,
  `role` enum('admin','user') NOT NULL,
  `email_verified` tinyint(1) NOT NULL DEFAULT 0,
  `verification_token` varchar(64) DEFAULT NULL,
  `token_expires` datetime DEFAULT NULL,
  `profile_pic` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `fullname`, `username`, `email`, `password`, `role`, `email_verified`, `verification_token`, `token_expires`, `profile_pic`) VALUES
(1, 'admin', 'admin', 'admin@school.com', '$2y$10$aeQfgVzIOLk0cvg3DO3pLeN1KQvPD/iBqlqyz3FRmkqkiO0Vnl7Kq', 'admin', 1, NULL, NULL, NULL),
(2, 'user', 'user', 'user@school.com', '$2y$10$2j.HI652zWhQ8q1/kskKYeHRxCKgqa4Y.o29b9PrdZVFXv8S6CJPG', 'user', 1, NULL, NULL, 'avatar_2_f6423eaf3704daeb.jpg'),
(11, 'Audrey Nicole P. Sumandal', 'Korebaby_', 'nicole14audrey@gmail.com', '$2y$10$/V.YUZSDjKIMyA7cu13fyOKAqd8JRS.MZ6VADkLpXQNbDB9ivnABi', 'user', 0, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `user_files`
--

CREATE TABLE `user_files` (
  `id` int(11) NOT NULL,
  `owner_user_id` int(11) NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `stored_name` varchar(255) NOT NULL,
  `mime_type` varchar(100) NOT NULL,
  `file_size` bigint(20) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `homepage`
--
ALTER TABLE `homepage`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `rooms`
--
ALTER TABLE `rooms`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `room_files`
--
ALTER TABLE `room_files`
  ADD PRIMARY KEY (`id`),
  ADD KEY `room_id` (`room_id`),
  ADD KEY `uploaded_by` (`uploaded_by`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `user_files`
--
ALTER TABLE `user_files`
  ADD PRIMARY KEY (`id`),
  ADD KEY `owner_user_id` (`owner_user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `homepage`
--
ALTER TABLE `homepage`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `rooms`
--
ALTER TABLE `rooms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `room_files`
--
ALTER TABLE `room_files`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(255) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `user_files`
--
ALTER TABLE `user_files`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `rooms`
--
ALTER TABLE `rooms`
  ADD CONSTRAINT `rooms_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `room_files`
--
ALTER TABLE `room_files`
  ADD CONSTRAINT `room_files_ibfk_1` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `room_files_ibfk_2` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
