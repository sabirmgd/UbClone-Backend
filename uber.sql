-- phpMyAdmin SQL Dump
-- version 4.5.1
-- http://www.phpmyadmin.net
--
-- Host: 127.0.0.1
-- Generation Time: Nov 26, 2016 at 07:41 AM
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
-- Table structure for table `cars`
--

CREATE TABLE `cars` (
  `ID` int(11) NOT NULL,
  `driverID` int(11) DEFAULT NULL,
  `color` varchar(15) COLLATE utf8_bin NOT NULL,
  `model` varchar(15) COLLATE utf8_bin NOT NULL,
  `year` tinyint(4) NOT NULL,
  `plateNumber` varchar(15) COLLATE utf8_bin NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- --------------------------------------------------------

--
-- Table structure for table `configuration`
--

CREATE TABLE `configuration` (
  `ID` int(11) NOT NULL,
  `_key` varchar(50) COLLATE utf8_bin NOT NULL,
  `_value` varchar(200) COLLATE utf8_bin DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

--
-- Dumping data for table `configuration`
--

INSERT INTO `configuration` (`ID`, `_key`, `_value`) VALUES
(1, 'perkm', '.56'),
(2, 'permin', '.25'),
(3, 'min', '10');

-- --------------------------------------------------------

--
-- Table structure for table `drivers`
--

CREATE TABLE `drivers` (
  `ID` int(11) NOT NULL,
  `email` varchar(100) COLLATE utf8_bin NOT NULL,
  `fullname` varchar(100) COLLATE utf8_bin NOT NULL,
  `gender` varchar(10) COLLATE utf8_bin NOT NULL,
  `phone` varchar(20) COLLATE utf8_bin NOT NULL,
  `active` tinyint(1) NOT NULL,
  `lastUpdated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `password` text COLLATE utf8_bin NOT NULL,
  `longitude` float(10,6) NOT NULL,
  `latitude` float(10,6) NOT NULL,
  `GCMID` text COLLATE utf8_bin
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

--
-- Dumping data for table `drivers`
--

INSERT INTO `drivers` (`ID`, `email`, `fullname`, `gender`, `phone`, `active`, `lastUpdated`, `password`, `longitude`, `latitude`, `GCMID`) VALUES
(8, 'insomniaa@gmail.com', 'insomnia is good', 'male', '09890890800', 1, '2016-11-25 02:36:16', '$2y$10$vvuhqrmzqIYO329AAEqVM.YNbWzuikplC36igm7RBT2e6Xd.I4whK', 10.100000, 10.200000, 'eRurufTwDO8:APA91bHVAVK-iVO9IRLoDYnb-nEoKheSJRISmg56-Vbrk_vmkMe1-CTJOxwDxEoTwMi42j4G86VJXzRxEDONJ8F43XGWnFzg9J-i5Xa6qfaI2Fo2zTjEN9z0k3Nf0PZQCHjfm7JOT88L'),
(9, 'insomeniaa@gmail.com', 'insomnia is good', 'male', '098903890800', 0, '2016-11-26 04:46:57', '$2y$10$BKDzAbtw.HzHw7TG5Kxq2.lHtEzoAXzor5akbO6cH74t3K.gFYfqS', 10.200000, 10.200000, 'eRurufTwDO8:APA91bHVAVK-iVO9IRLoDYnb-nEoKheSJRISmg56-Vbrk_vmkMe1-CTJOxwDxEoTwMi42j4G86VJXzRxEDONJ8F43XGWnFzg9J-i5Xa6qfaI2Fo2zTjEN9z0k3Nf0PZQCHjfm7JOT88L'),
(10, 'hoota@gmail.com', 'hootaa ', 'male', '0989038390800', 1, '2016-11-25 04:13:51', '$2y$10$AnEOAvPqKHVJkaSQLFuEZ.KgQ4v1xLPgHEMHR0K7cWrcubNP.8QR2', 10.200000, 10.200000, 'eRurufTwDO8:APA91bHVAVK-iVO9IRLoDYnb-nEoKheSJRISmg56-Vbrk_vmkMe1-CTJOxwDxEoTwMi42j4G86VJXzRxEDONJ8F43XGWnFzg9J-i5Xa6qfaI2Fo2zTjEN9z0k3Nf0PZQCHjfm7JOT88L'),
(11, 'ahmed@gmail.com', 'ahmed', 'male', '32432432', 1, '2016-11-25 04:14:09', '$2y$10$JPcHLJ2/U9VqpqLRrf35Gu1I6dA8idf1ftk3ctc2gCe7sd1PsPgE6', 10.100000, 10.100000, 'eRurufTwDO8:APA91bHVAVK-iVO9IRLoDYnb-nEoKheSJRISmg56-Vbrk_vmkMe1-CTJOxwDxEoTwMi42j4G86VJXzRxEDONJ8F43XGWnFzg9J-i5Xa6qfaI2Fo2zTjEN9z0k3Nf0PZQCHjfm7JOT88L'),
(12, 'rasha@gmail.com', 'rashaa mohammed', 'female', '12321321321', 1, '2016-11-25 04:14:32', '$2y$10$M.SY8oDmAtD5AK3VT9SHFefOk657C7bgJYXdNBd1re4uJZi7FHnLW', 10.000000, 10.000000, 'eRurufTwDO8:APA91bHVAVK-iVO9IRLoDYnb-nEoKheSJRISmg56-Vbrk_vmkMe1-CTJOxwDxEoTwMi42j4G86VJXzRxEDONJ8F43XGWnFzg9J-i5Xa6qfaI2Fo2zTjEN9z0k3Nf0PZQCHjfm7JOT88L'),
(13, 'allaa@gmail.com', 'alaa mohammed', 'female', '12321321321324', 1, '2016-11-25 04:17:46', '$2y$10$UUHphN8jD.7qO8DK5PLx7ezthX1Vs4HzDSJVUoubdjZT9LyP6sZXi', 10.300000, 10.300000, 'eRurufTwDO8:APA91bHVAVK-iVO9IRLoDYnb-nEoKheSJRISmg56-Vbrk_vmkMe1-CTJOxwDxEoTwMi42j4G86VJXzRxEDONJ8F43XGWnFzg9J-i5Xa6qfaI2Fo2zTjEN9z0k3Nf0PZQCHjfm7JOT88L'),
(14, 'hind@gmail.com', 'hind android', 'female', '123213213321324', 1, '2016-11-25 04:18:07', '$2y$10$2GfvL4c7rOPjdzrcufayFu0XbKFwzikrX5DXIagW0bLZ23AjGEe1K', 10.400000, 10.400000, 'eRurufTwDO8:APA91bHVAVK-iVO9IRLoDYnb-nEoKheSJRISmg56-Vbrk_vmkMe1-CTJOxwDxEoTwMi42j4G86VJXzRxEDONJ8F43XGWnFzg9J-i5Xa6qfaI2Fo2zTjEN9z0k3Nf0PZQCHjfm7JOT88L'),
(15, 'hala@gmail.com', 'hala adasd', 'female', '12321321332132434', 0, '2016-11-26 04:47:38', '$2y$10$j.HZypcrioebqmaOkd67y.LJT5Mqact8mghSKwhAQ/zRO8SIkCkSO', 10.200000, 10.200000, 'eRurufTwDO8:APA91bHVAVK-iVO9IRLoDYnb-nEoKheSJRISmg56-Vbrk_vmkMe1-CTJOxwDxEoTwMi42j4G86VJXzRxEDONJ8F43XGWnFzg9J-i5Xa6qfaI2Fo2zTjEN9z0k3Nf0PZQCHjfm7JOT88L');

-- --------------------------------------------------------

--
-- Table structure for table `passengers`
--

CREATE TABLE `passengers` (
  `ID` int(11) NOT NULL,
  `email` varchar(100) COLLATE utf8_bin NOT NULL,
  `gender` varchar(10) COLLATE utf8_bin NOT NULL,
  `fullname` varchar(100) COLLATE utf8_bin NOT NULL,
  `verified` tinyint(1) NOT NULL,
  `password` text COLLATE utf8_bin NOT NULL,
  `phone` varchar(20) COLLATE utf8_bin NOT NULL,
  `verificationCode` char(6) COLLATE utf8_bin NOT NULL,
  `GCMID` text COLLATE utf8_bin
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

--
-- Dumping data for table `passengers`
--

INSERT INTO `passengers` (`ID`, `email`, `gender`, `fullname`, `verified`, `password`, `phone`, `verificationCode`, `GCMID`) VALUES
(1, 'sabirmgd@hotmail.com', 'male', 'Islam abdala', 0, '$2y$10$sHt5cRkSlJdIsb/llSXV/.DZCAd/i37GsEr4ZzogPaI6Y0GIua39W', '091275322', 'hJ5wUD', 'eRurufTwDO8:APA91bHVAVK-iVO9IRLoDYnb-nEoKheSJRISmg56-Vbrk_vmkMe1-CTJOxwDxEoTwMi42j4G86VJXzRxEDONJ8F43XGWnFzg9J-i5Xa6qfaI2Fo2zTjEN9z0k3Nf0PZQCHjfm7JOT88L'),
(2, 'sabirmgd@gmail.com', 'male', 'Islam abdala', 1, '$2y$10$Rf9GXOtB3ROPZMF7IP8oheyo0MTTXafWIOdm0xjkxJyGaH/Q0RvxC', '09127521', 'drubTA', 'eRurufTwDO8:APA91bHVAVK-iVO9IRLoDYnb-nEoKheSJRISmg56-Vbrk_vmkMe1-CTJOxwDxEoTwMi42j4G86VJXzRxEDONJ8F43XGWnFzg9J-i5Xa6qfaI2Fo2zTjEN9z0k3Nf0PZQCHjfm7JOT88L'),
(3, 'insomnia@gmail.com', 'male', 'insomnia is good', 0, '$2y$10$Emd7Wg2O02mngZ5AAl2bk.YmbNrIrcNk0w0IiPFidnVR3BXCFs1JO', '0989089080', 'VxDQz9', 'eRurufTwDO8:APA91bHVAVK-iVO9IRLoDYnb-nEoKheSJRISmg56-Vbrk_vmkMe1-CTJOxwDxEoTwMi42j4G86VJXzRxEDONJ8F43XGWnFzg9J-i5Xa6qfaI2Fo2zTjEN9z0k3Nf0PZQCHjfm7JOT88L'),
(4, 'insomniaa@gmail.com', 'male', 'insomnia is good', 0, '$2y$10$LsJEwSEjpefnZh9AeQ9i8eFgiaQPGYKbSubqRx8NQY6GHG4lnTODu', '09890890800', 'oZiogt', 'eRurufTwDO8:APA91bHVAVK-iVO9IRLoDYnb-nEoKheSJRISmg56-Vbrk_vmkMe1-CTJOxwDxEoTwMi42j4G86VJXzRxEDONJ8F43XGWnFzg9J-i5Xa6qfaI2Fo2zTjEN9z0k3Nf0PZQCHjfm7JOT88L'),
(5, 'tesnewFunction@gmail.com', 'male', 'insomnia is good', 0, '$2y$10$.45J2DdIXH5UL0PIVrBLEOx7GnejdSdNHNzKcoyqKGU1OrQziP5Hq', '3423432', 'StgXjc', 'eRurufTwDO8:APA91bHVAVK-iVO9IRLoDYnb-nEoKheSJRISmg56-Vbrk_vmkMe1-CTJOxwDxEoTwMi42j4G86VJXzRxEDONJ8F43XGWnFzg9J-i5Xa6qfaI2Fo2zTjEN9z0k3Nf0PZQCHjfm7JOT88L'),
(6, 'tesnewFuncdtion@gmail.com', 'male', 'insomnia is good', 0, '$2y$10$ZU3pXpL/crqmYRPszsepwudlPaUnA.ow6w43nAiZ26xtffdfjGqCy', '21312312312', 'a2tW74', 'eRurufTwDO8:APA91bHVAVK-iVO9IRLoDYnb-nEoKheSJRISmg56-Vbrk_vmkMe1-CTJOxwDxEoTwMi42j4G86VJXzRxEDONJ8F43XGWnFzg9J-i5Xa6qfaI2Fo2zTjEN9z0k3Nf0PZQCHjfm7JOT88L'),
(7, 'tesnewFunecdtion@gmail.com', 'female', 'insomnia is good', 0, '$2y$10$qRBkt.a6slWLixoDGXFZX.hdlsT58BUavCo7IBvvLnfwAqAbNyl/u', '213123312312', '5xgcd1', 'eRurufTwDO8:APA91bHVAVK-iVO9IRLoDYnb-nEoKheSJRISmg56-Vbrk_vmkMe1-CTJOxwDxEoTwMi42j4G86VJXzRxEDONJ8F43XGWnFzg9J-i5Xa6qfaI2Fo2zTjEN9z0k3Nf0PZQCHjfm7JOT88L'),
(8, 'tesnewFunecd3tion@gmail.com', 'male', 'insomnia is good', 0, '$2y$10$X9qdxpexx.kCbBBuBGPsbOu2vg2M9BNUdcxfBsfKJV9g/nAgXFHle', '', 'dQYZTi', 'eRurufTwDO8:APA91bHVAVK-iVO9IRLoDYnb-nEoKheSJRISmg56-Vbrk_vmkMe1-CTJOxwDxEoTwMi42j4G86VJXzRxEDONJ8F43XGWnFzg9J-i5Xa6qfaI2Fo2zTjEN9z0k3Nf0PZQCHjfm7JOT88L'),
(9, 'tesnewFunsecd3tion@gmail.com', 'don&#39;t ', 'my name', 0, '$2y$10$ZkoCELgdk0pBpRNIki8WlO3ug0T30rbWPnP8y7SW8pfsQfBXGacTS', '324324324324', 'iuiO7A', 'eRurufTwDO8:APA91bHVAVK-iVO9IRLoDYnb-nEoKheSJRISmg56-Vbrk_vmkMe1-CTJOxwDxEoTwMi42j4G86VJXzRxEDONJ8F43XGWnFzg9J-i5Xa6qfaI2Fo2zTjEN9z0k3Nf0PZQCHjfm7JOT88L');

-- --------------------------------------------------------

--
-- Table structure for table `requests`
--

CREATE TABLE `requests` (
  `ID` bigint(20) UNSIGNED NOT NULL,
  `pickupLongitude` float(10,6) NOT NULL,
  `pickupLatitude` float(10,6) NOT NULL,
  `destinationLongitude` float(10,6) NOT NULL,
  `destinationLatitude` float(10,6) NOT NULL,
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

INSERT INTO `requests` (`ID`, `pickupLongitude`, `pickupLatitude`, `destinationLongitude`, `destinationLatitude`, `requestTime`, `femaleDriver`, `notes`, `price`, `passengerID`, `driverID`, `status`) VALUES
(202, 10.500000, 10.500000, 10.600000, 10.600000, '2016-11-25 21:21:19', 0, '', '50.80', 2, 15, 'completed'),
(203, 10.500000, 10.500000, 10.600000, 10.600000, '2016-11-25 21:35:27', 0, '', '50.80', 2, NULL, 'noDriver'),
(204, 10.500000, 10.500000, 10.600000, 10.600000, '2016-11-25 21:44:57', 0, '', '50.80', 2, 15, 'canceled');

-- --------------------------------------------------------

--
-- Table structure for table `request_driver`
--

CREATE TABLE `request_driver` (
  `ID` bigint(20) NOT NULL,
  `requestID` bigint(20) NOT NULL,
  `driverID` int(11) NOT NULL,
  `status` varchar(12) COLLATE utf8_bin NOT NULL DEFAULT 'missed'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

--
-- Dumping data for table `request_driver`
--

INSERT INTO `request_driver` (`ID`, `requestID`, `driverID`, `status`) VALUES
(158, 202, 15, 'completed'),
(159, 204, 15, 'canceled');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `cars`
--
ALTER TABLE `cars`
  ADD PRIMARY KEY (`ID`);

--
-- Indexes for table `configuration`
--
ALTER TABLE `configuration`
  ADD PRIMARY KEY (`ID`);

--
-- Indexes for table `drivers`
--
ALTER TABLE `drivers`
  ADD PRIMARY KEY (`ID`);

--
-- Indexes for table `passengers`
--
ALTER TABLE `passengers`
  ADD PRIMARY KEY (`ID`);

--
-- Indexes for table `requests`
--
ALTER TABLE `requests`
  ADD PRIMARY KEY (`ID`);

--
-- Indexes for table `request_driver`
--
ALTER TABLE `request_driver`
  ADD PRIMARY KEY (`ID`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `cars`
--
ALTER TABLE `cars`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `configuration`
--
ALTER TABLE `configuration`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;
--
-- AUTO_INCREMENT for table `drivers`
--
ALTER TABLE `drivers`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;
--
-- AUTO_INCREMENT for table `passengers`
--
ALTER TABLE `passengers`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;
--
-- AUTO_INCREMENT for table `requests`
--
ALTER TABLE `requests`
  MODIFY `ID` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=205;
--
-- AUTO_INCREMENT for table `request_driver`
--
ALTER TABLE `request_driver`
  MODIFY `ID` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=160;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
