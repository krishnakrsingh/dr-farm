-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Feb 19, 2026 at 07:52 PM
-- Server version: 10.11.15-MariaDB
-- PHP Version: 8.4.15

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `smartsip_farm`
--

-- --------------------------------------------------------

--
-- Table structure for table `call_control`
--

CREATE TABLE `call_control` (
  `id` int(11) NOT NULL,
  `trigger_call` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `call_control`
--

INSERT INTO `call_control` (`id`, `trigger_call`) VALUES
(1, 0);

-- --------------------------------------------------------

--
-- Table structure for table `sensor_data`
--

CREATE TABLE `sensor_data` (
  `id` int(11) NOT NULL,
  `node_id` varchar(50) DEFAULT NULL,
  `temperature` float DEFAULT NULL,
  `humidity` float DEFAULT NULL,
  `mq7` int(11) DEFAULT NULL,
  `mq3` int(11) DEFAULT NULL,
  `rain` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `sensor_data`
--

INSERT INTO `sensor_data` (`id`, `node_id`, `temperature`, `humidity`, `mq7`, `mq3`, `rain`, `created_at`) VALUES
(1, 'NODE_1', 27.5, 82, 300, 250, 0, '2026-02-19 09:46:23');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `call_control`
--
ALTER TABLE `call_control`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `sensor_data`
--
ALTER TABLE `sensor_data`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `sensor_data`
--
ALTER TABLE `sensor_data`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
