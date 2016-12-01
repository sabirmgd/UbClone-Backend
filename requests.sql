-- phpMyAdmin SQL Dump
-- version 4.5.1
-- http://www.phpmyadmin.net
--
-- Host: 127.0.0.1
-- Generation Time: Nov 30, 2016 at 04:54 AM
-- Server version: 10.1.16-MariaDB
-- PHP Version: 7.0.9

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `uber`
--

-- --------------------------------------------------------

--
-- Table structure for table `requests`
--

CREATE TABLE `requests` (
  `ID` bigint(20) UNSIGNED NOT NULL,
  `pickupLongitude` float(10,6) NOT NULL,
  `pickupLatitude` float(10,6) NOT NULL,
  `pickup_text` varchar(250) COLLATE utf8_bin NOT NULL DEFAULT 'Khartoum',
  `destinationLongitude` float(10,6) NOT NULL,
  `destinationLatitude` float(10,6) NOT NULL,
  `dest_text` varchar(250) COLLATE utf8_bin NOT NULL DEFAULT 'Khartoum',
  `requestTime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `femaleDriver` tinyint(1) NOT NULL DEFAULT '0',
  `notes` varchar(500) COLLATE utf8_bin NOT NULL,
  `price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `passengerID` int(11) NOT NULL,
  `driverID` int(11) DEFAULT NULL,
  `status` varchar(15) COLLATE utf8_bin DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

--
-- Dumping data for table `requests`
--

INSERT INTO `requests` (`ID`, `pickupLongitude`, `pickupLatitude`, `pickup_text`, `destinationLongitude`, `destinationLatitude`, `dest_text`, `requestTime`, `femaleDriver`, `notes`, `price`, `passengerID`, `driverID`, `status`) VALUES
(212, 10.500000, 10.500000, 'Khartoum', 10.600000, 10.600000, 'Khartoum', '2016-11-29 04:06:26', 0, '', '50.80', 1, NULL, 'noDriver'),
(213, 10.500000, 10.500000, 'Khartoum', 10.600000, 10.600000, 'Khartoum', '2016-11-29 04:07:42', 0, '', '50.80', 1, NULL, 'canceled'),
(214, 10.500000, 10.500000, 'Khartoum', 10.600000, 10.600000, 'Khartoum', '2016-11-29 04:09:39', 0, '', '50.80', 1, 14, 'completed'),
(215, 10.500000, 10.500000, 'Khartoum', 10.600000, 10.600000, 'Khartoum', '2016-11-29 04:12:00', 0, '', '50.80', 1, NULL, 'canceled'),
(216, 10.500000, 10.500000, 'Khartoum', 10.600000, 10.600000, 'Khartoum', '2016-11-29 04:14:03', 0, '', '50.80', 1, NULL, 'noDriver'),
(217, 10.500000, 10.500000, 'Khartoum', 10.600000, 10.600000, 'Khartoum', '2016-11-29 04:14:25', 0, '', '50.80', 1, NULL, 'noDriver'),
(218, 10.500000, 10.500000, 'Khartoum', 10.600000, 10.600000, 'Khartoum', '2016-11-29 04:17:43', 0, '', '50.80', 1, NULL, 'noDriver'),
(219, 10.500000, 10.500000, 'omdorman', 10.600000, 10.600000, 'Mamoora', '2016-11-29 08:07:08', 0, '', '50.80', 1, NULL, 'noDriver');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `requests`
--
ALTER TABLE `requests`
  ADD PRIMARY KEY (`ID`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `requests`
--
ALTER TABLE `requests`
  MODIFY `ID` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=220;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
