-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 18, 2025 at 04:29 PM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `hrm`
--

-- --------------------------------------------------------

--
-- Table structure for table `actions_log`
--

CREATE TABLE `actions_log` (
  `id` int(11) NOT NULL,
  `actor` varchar(150) DEFAULT NULL,
  `action_type` varchar(100) DEFAULT NULL,
  `target_table` varchar(100) DEFAULT NULL,
  `target_id` int(11) DEFAULT NULL,
  `details` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inventory`
--

CREATE TABLE `inventory` (
  `id` int(11) NOT NULL,
  `sku` varchar(100) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `quantity` int(10) UNSIGNED DEFAULT 1,
  `available` int(10) UNSIGNED DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inventory`
--

INSERT INTO `inventory` (`id`, `sku`, `name`, `description`, `quantity`, `available`, `created_at`, `updated_at`) VALUES
(1, '123', 'Prathmesh Shinkar', 'yudgvgewvbds', 1, 1, '2025-11-17 11:11:10', '2025-11-17 12:00:35'),
(3, 'potggh', 'qwerty', 'asdfghjk', 3, 2, '2025-11-17 11:55:15', '2025-11-17 11:55:46'),
(4, 'ITEM_1763381906', 'add', 'hello', 3, 2, '2025-11-17 12:18:26', '2025-11-18 15:26:34'),
(5, 'ITEM_1763381942', '1234@', '', 1, 0, '2025-11-17 12:19:02', '2025-11-18 15:27:23');

-- --------------------------------------------------------

--
-- Table structure for table `inventory_borrows`
--

CREATE TABLE `inventory_borrows` (
  `id` int(11) NOT NULL,
  `inventory_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `borrow_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `due_date` date DEFAULT NULL,
  `return_date` timestamp NULL DEFAULT NULL,
  `status` enum('borrowed','returned','overdue') DEFAULT 'borrowed',
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inventory_borrows`
--

INSERT INTO `inventory_borrows` (`id`, `inventory_id`, `student_id`, `borrow_date`, `due_date`, `return_date`, `status`, `notes`) VALUES
(2, 1, 3, '2025-11-17 11:54:12', '2025-11-21', '2025-11-17 12:00:35', 'returned', 'bucseh'),
(4, 4, 3, '2025-11-18 15:26:34', '2025-11-04', NULL, 'borrowed', ''),
(5, 5, 6, '2025-11-18 15:27:23', '2025-11-13', NULL, 'borrowed', '');

-- --------------------------------------------------------

--
-- Table structure for table `projects`
--

CREATE TABLE `projects` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `tech_stack` varchar(255) DEFAULT NULL,
  `progress_percent` tinyint(3) UNSIGNED DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `projects`
--

INSERT INTO `projects` (`id`, `name`, `description`, `start_date`, `end_date`, `tech_stack`, `progress_percent`, `created_at`, `updated_at`) VALUES
(2, 'nfc', 'hrm', '2025-11-16', '2025-11-17', 'php,', 15, '2025-11-17 12:37:15', '2025-11-17 12:51:06');

-- --------------------------------------------------------

--
-- Table structure for table `project_students`
--

CREATE TABLE `project_students` (
  `project_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `role` varchar(100) DEFAULT NULL,
  `assigned_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `project_students`
--

INSERT INTO `project_students` (`project_id`, `student_id`, `role`, `assigned_at`) VALUES
(2, 3, NULL, '2025-11-17 12:51:06'),
(2, 5, NULL, '2025-11-17 12:51:06'),
(2, 6, NULL, '2025-11-17 12:51:06');

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `id` int(11) NOT NULL,
  `roll_no` varchar(50) DEFAULT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `year` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`id`, `roll_no`, `first_name`, `last_name`, `email`, `phone`, `department`, `year`, `created_at`, `updated_at`) VALUES
(2, '101', 'Prathmesh', 'Shinkar', 'prathmesh@example.com', '9876543210', 'BCA', 'TY', '2025-11-17 11:09:50', '2025-11-17 11:09:50'),
(3, '102', 'Amit', 'Patil', 'amitp@example.com', '9898989898', 'BCA', 'TY', '2025-11-17 11:09:50', '2025-11-17 11:09:50'),
(5, '104', 'Rahul', 'Gadre', 'rahulg@example.com', '9988776655', 'CS', 'FY', '2025-11-17 11:09:50', '2025-11-17 11:09:50'),
(6, '105', 'Neha', 'Kadam', 'nehak@example.com', '9012345678', 'BBA', 'FY', '2025-11-17 11:09:50', '2025-11-17 11:09:50'),
(9, '52', 'Rahul', 'Pardeshi', 'rahulpar3445@gmail.com', '8457569124', 'AIML', 'LY', '2025-11-17 12:17:19', '2025-11-18 05:18:05'),
(10, '51063', 'Pratham', 'Chavan', 'chavan.pratham1212@gmail.com', '9503459537', 'Mechanical', 'LY', '2025-11-18 05:12:15', '2025-11-18 05:12:15');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `actions_log`
--
ALTER TABLE `actions_log`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `inventory`
--
ALTER TABLE `inventory`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sku` (`sku`);

--
-- Indexes for table `inventory_borrows`
--
ALTER TABLE `inventory_borrows`
  ADD PRIMARY KEY (`id`),
  ADD KEY `inventory_id` (`inventory_id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `projects`
--
ALTER TABLE `projects`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `project_students`
--
ALTER TABLE `project_students`
  ADD PRIMARY KEY (`project_id`,`student_id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `roll_no` (`roll_no`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `actions_log`
--
ALTER TABLE `actions_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `inventory`
--
ALTER TABLE `inventory`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `inventory_borrows`
--
ALTER TABLE `inventory_borrows`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `projects`
--
ALTER TABLE `projects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `inventory_borrows`
--
ALTER TABLE `inventory_borrows`
  ADD CONSTRAINT `inventory_borrows_ibfk_1` FOREIGN KEY (`inventory_id`) REFERENCES `inventory` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `inventory_borrows_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `project_students`
--
ALTER TABLE `project_students`
  ADD CONSTRAINT `project_students_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `project_students_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
