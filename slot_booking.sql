-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Sep 23, 2025 at 05:08 PM
-- Server version: 8.4.3
-- PHP Version: 8.3.16

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `slot_booking`
--

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `id` int NOT NULL,
  `time_slot_id` int NOT NULL,
  `customer_name` varchar(200) NOT NULL,
  `customer_email` varchar(200) NOT NULL,
  `players_count` int NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `payment_status` enum('pending','paid','failed') DEFAULT 'pending',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`id`, `time_slot_id`, `customer_name`, `customer_email`, `players_count`, `total_price`, `payment_status`, `created_at`) VALUES
(1, 6, 'customer@gmail.com', 'customer@gmail.com', 1, 10.00, 'pending', '2025-09-23 09:50:59');

-- --------------------------------------------------------

--
-- Table structure for table `default_time_slots`
--

CREATE TABLE `default_time_slots` (
  `id` int NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `default_time_slots`
--

INSERT INTO `default_time_slots` (`id`, `start_time`, `end_time`) VALUES
(6, '09:00:00', '10:00:00'),
(7, '10:00:00', '11:00:00'),
(8, '11:00:00', '12:00:00'),
(9, '14:00:00', '15:00:00'),
(10, '15:00:00', '16:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `player_pricings`
--

CREATE TABLE `player_pricings` (
  `id` int NOT NULL,
  `template_id` int NOT NULL,
  `players_count` int NOT NULL,
  `price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `player_pricings`
--

INSERT INTO `player_pricings` (`id`, `template_id`, `players_count`, `price`) VALUES
(1, 1, 1, 10.00),
(2, 1, 2, 20.00),
(3, 1, 3, 30.00),
(4, 1, 4, 40.00),
(5, 2, 1, 10.00),
(6, 2, 2, 20.00),
(7, 2, 3, 30.00),
(8, 2, 4, 40.00),
(9, 3, 1, 10.00),
(10, 3, 2, 20.00),
(11, 3, 3, 30.00),
(12, 3, 4, 40.00);

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int NOT NULL,
  `name` varchar(150) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`) VALUES
(1, 'Tennis Court'),
(2, 'Badminton Court'),
(3, 'Yoga Session');

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `k` varchar(100) NOT NULL,
  `v` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`k`, `v`) VALUES
('slot_refresh_interval_seconds', '15');

-- --------------------------------------------------------

--
-- Table structure for table `slot_status_log`
--

CREATE TABLE `slot_status_log` (
  `id` int NOT NULL,
  `time_slot_id` int NOT NULL,
  `old_status` varchar(32) DEFAULT NULL,
  `new_status` varchar(32) DEFAULT NULL,
  `changed_by` varchar(150) DEFAULT NULL,
  `changed_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `slot_status_log`
--

INSERT INTO `slot_status_log` (`id`, `time_slot_id`, `old_status`, `new_status`, `changed_by`, `changed_at`) VALUES
(1, 6, 'available', 'booked', 'customer@gmail.com', '2025-09-23 09:50:59'),
(2, 21, 'available', 'booked', 'customer@gmail.com', '2025-09-23 09:50:59'),
(3, 36, 'available', 'booked', 'customer@gmail.com', '2025-09-23 09:50:59');

-- --------------------------------------------------------

--
-- Table structure for table `slot_templates`
--

CREATE TABLE `slot_templates` (
  `id` int NOT NULL,
  `product_id` int NOT NULL,
  `min_players` int NOT NULL DEFAULT '1',
  `max_players` int NOT NULL DEFAULT '4',
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `slot_templates`
--

INSERT INTO `slot_templates` (`id`, `product_id`, `min_players`, `max_players`, `start_date`, `end_date`, `created_at`) VALUES
(1, 1, 1, 4, '2025-09-23', '2025-09-25', '2025-09-23 09:50:03'),
(2, 2, 1, 4, '2025-09-23', '2025-09-25', '2025-09-23 09:50:03'),
(3, 3, 1, 4, '2025-09-23', '2025-09-25', '2025-09-23 09:50:03');

-- --------------------------------------------------------

--
-- Table structure for table `staff`
--

CREATE TABLE `staff` (
  `id` int NOT NULL,
  `name` varchar(150) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `staff`
--

INSERT INTO `staff` (`id`, `name`) VALUES
(1, 'Ali Khan'),
(2, 'Maria Baloch'),
(3, 'Omar Sheikh');

-- --------------------------------------------------------

--
-- Table structure for table `time_slots`
--

CREATE TABLE `time_slots` (
  `id` int NOT NULL,
  `template_id` int DEFAULT NULL,
  `product_id` int NOT NULL,
  `slot_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `staff_id` int DEFAULT NULL,
  `status` enum('available','unavailable','booked') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_ci NOT NULL DEFAULT 'available',
  `number_of_staff` int DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `time_slots`
--

INSERT INTO `time_slots` (`id`, `template_id`, `product_id`, `slot_date`, `start_time`, `end_time`, `staff_id`, `status`, `number_of_staff`, `created_at`) VALUES
(1, 1, 1, '2025-09-23', '09:00:00', '10:00:00', NULL, 'unavailable', 4, '2025-09-23 09:50:03'),
(2, 1, 1, '2025-09-23', '10:00:00', '11:00:00', NULL, 'unavailable', 4, '2025-09-23 09:50:03'),
(3, 1, 1, '2025-09-23', '11:00:00', '12:00:00', NULL, 'unavailable', 4, '2025-09-23 09:50:03'),
(4, 1, 1, '2025-09-23', '14:00:00', '15:00:00', NULL, 'unavailable', 4, '2025-09-23 09:50:03'),
(5, 1, 1, '2025-09-23', '15:00:00', '16:00:00', NULL, 'unavailable', 4, '2025-09-23 09:50:03'),
(6, 1, 1, '2025-09-24', '09:00:00', '10:00:00', NULL, 'booked', 2, '2025-09-23 09:50:03'),
(7, 1, 1, '2025-09-24', '10:00:00', '11:00:00', NULL, 'available', 2, '2025-09-23 09:50:03'),
(8, 1, 1, '2025-09-24', '11:00:00', '12:00:00', NULL, 'available', 2, '2025-09-23 09:50:03'),
(9, 1, 1, '2025-09-24', '14:00:00', '15:00:00', NULL, 'available', 2, '2025-09-23 09:50:03'),
(10, 1, 1, '2025-09-24', '15:00:00', '16:00:00', NULL, 'available', 2, '2025-09-23 09:50:03'),
(11, 1, 1, '2025-09-25', '09:00:00', '10:00:00', NULL, 'available', 4, '2025-09-23 09:50:03'),
(12, 1, 1, '2025-09-25', '10:00:00', '11:00:00', NULL, 'available', 4, '2025-09-23 09:50:03'),
(13, 1, 1, '2025-09-25', '11:00:00', '12:00:00', NULL, 'available', 4, '2025-09-23 09:50:03'),
(14, 1, 1, '2025-09-25', '14:00:00', '15:00:00', NULL, 'available', 4, '2025-09-23 09:50:03'),
(15, 1, 1, '2025-09-25', '15:00:00', '16:00:00', NULL, 'available', 4, '2025-09-23 09:50:03'),
(16, 2, 2, '2025-09-23', '09:00:00', '10:00:00', NULL, 'unavailable', 4, '2025-09-23 09:50:03'),
(17, 2, 2, '2025-09-23', '10:00:00', '11:00:00', NULL, 'unavailable', 4, '2025-09-23 09:50:03'),
(18, 2, 2, '2025-09-23', '11:00:00', '12:00:00', NULL, 'unavailable', 4, '2025-09-23 09:50:03'),
(19, 2, 2, '2025-09-23', '14:00:00', '15:00:00', NULL, 'unavailable', 4, '2025-09-23 09:50:03'),
(20, 2, 2, '2025-09-23', '15:00:00', '16:00:00', NULL, 'unavailable', 4, '2025-09-23 09:50:03'),
(21, 2, 2, '2025-09-24', '09:00:00', '10:00:00', NULL, 'booked', 2, '2025-09-23 09:50:03'),
(22, 2, 2, '2025-09-24', '10:00:00', '11:00:00', NULL, 'available', 2, '2025-09-23 09:50:03'),
(23, 2, 2, '2025-09-24', '11:00:00', '12:00:00', NULL, 'available', 2, '2025-09-23 09:50:03'),
(24, 2, 2, '2025-09-24', '14:00:00', '15:00:00', NULL, 'available', 2, '2025-09-23 09:50:03'),
(25, 2, 2, '2025-09-24', '15:00:00', '16:00:00', NULL, 'available', 2, '2025-09-23 09:50:03'),
(26, 2, 2, '2025-09-25', '09:00:00', '10:00:00', NULL, 'available', 4, '2025-09-23 09:50:03'),
(27, 2, 2, '2025-09-25', '10:00:00', '11:00:00', NULL, 'available', 4, '2025-09-23 09:50:03'),
(28, 2, 2, '2025-09-25', '11:00:00', '12:00:00', NULL, 'available', 4, '2025-09-23 09:50:03'),
(29, 2, 2, '2025-09-25', '14:00:00', '15:00:00', NULL, 'available', 4, '2025-09-23 09:50:03'),
(30, 2, 2, '2025-09-25', '15:00:00', '16:00:00', NULL, 'available', 4, '2025-09-23 09:50:03'),
(31, 3, 3, '2025-09-23', '09:00:00', '10:00:00', NULL, 'unavailable', 4, '2025-09-23 09:50:03'),
(32, 3, 3, '2025-09-23', '10:00:00', '11:00:00', NULL, 'unavailable', 4, '2025-09-23 09:50:03'),
(33, 3, 3, '2025-09-23', '11:00:00', '12:00:00', NULL, 'unavailable', 4, '2025-09-23 09:50:03'),
(34, 3, 3, '2025-09-23', '14:00:00', '15:00:00', NULL, 'unavailable', 4, '2025-09-23 09:50:03'),
(35, 3, 3, '2025-09-23', '15:00:00', '16:00:00', NULL, 'unavailable', 4, '2025-09-23 09:50:03'),
(36, 3, 3, '2025-09-24', '09:00:00', '10:00:00', NULL, 'booked', 2, '2025-09-23 09:50:03'),
(37, 3, 3, '2025-09-24', '10:00:00', '11:00:00', NULL, 'available', 2, '2025-09-23 09:50:03'),
(38, 3, 3, '2025-09-24', '11:00:00', '12:00:00', NULL, 'available', 2, '2025-09-23 09:50:03'),
(39, 3, 3, '2025-09-24', '14:00:00', '15:00:00', NULL, 'available', 2, '2025-09-23 09:50:03'),
(40, 3, 3, '2025-09-24', '15:00:00', '16:00:00', NULL, 'available', 2, '2025-09-23 09:50:03'),
(41, 3, 3, '2025-09-25', '09:00:00', '10:00:00', NULL, 'available', 4, '2025-09-23 09:50:03'),
(42, 3, 3, '2025-09-25', '10:00:00', '11:00:00', NULL, 'available', 4, '2025-09-23 09:50:03'),
(43, 3, 3, '2025-09-25', '11:00:00', '12:00:00', NULL, 'available', 4, '2025-09-23 09:50:03'),
(44, 3, 3, '2025-09-25', '14:00:00', '15:00:00', NULL, 'available', 4, '2025-09-23 09:50:03'),
(45, 3, 3, '2025-09-25', '15:00:00', '16:00:00', NULL, 'available', 4, '2025-09-23 09:50:03');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `time_slot_id` (`time_slot_id`);

--
-- Indexes for table `default_time_slots`
--
ALTER TABLE `default_time_slots`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `player_pricings`
--
ALTER TABLE `player_pricings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `template_id` (`template_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`k`);

--
-- Indexes for table `slot_status_log`
--
ALTER TABLE `slot_status_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `time_slot_id` (`time_slot_id`);

--
-- Indexes for table `slot_templates`
--
ALTER TABLE `slot_templates`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `staff`
--
ALTER TABLE `staff`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `time_slots`
--
ALTER TABLE `time_slots`
  ADD PRIMARY KEY (`id`),
  ADD KEY `template_id` (`template_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `staff_id` (`staff_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `default_time_slots`
--
ALTER TABLE `default_time_slots`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `player_pricings`
--
ALTER TABLE `player_pricings`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `slot_status_log`
--
ALTER TABLE `slot_status_log`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `slot_templates`
--
ALTER TABLE `slot_templates`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `staff`
--
ALTER TABLE `staff`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `time_slots`
--
ALTER TABLE `time_slots`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`time_slot_id`) REFERENCES `time_slots` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `player_pricings`
--
ALTER TABLE `player_pricings`
  ADD CONSTRAINT `player_pricings_ibfk_1` FOREIGN KEY (`template_id`) REFERENCES `slot_templates` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `slot_status_log`
--
ALTER TABLE `slot_status_log`
  ADD CONSTRAINT `slot_status_log_ibfk_1` FOREIGN KEY (`time_slot_id`) REFERENCES `time_slots` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `slot_templates`
--
ALTER TABLE `slot_templates`
  ADD CONSTRAINT `slot_templates_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `time_slots`
--
ALTER TABLE `time_slots`
  ADD CONSTRAINT `time_slots_ibfk_1` FOREIGN KEY (`template_id`) REFERENCES `slot_templates` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `time_slots_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `time_slots_ibfk_3` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
