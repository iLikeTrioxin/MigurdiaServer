-- phpMyAdmin SQL Dump
-- version 5.1.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Sep 18, 2021 at 06:37 PM
-- Server version: 8.0.25
-- PHP Version: 7.3.29-1~deb10u1

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `migurdia`
--

-- --------------------------------------------------------

--
-- Table structure for table `FileFormats`
--

CREATE TABLE `FileFormats` (
  `ID` smallint UNSIGNED NOT NULL,
  `Name` varchar(256) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
  `FileExtension` varchar(8) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
  `MIME` varchar(64) COLLATE utf8_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `Files`
--

CREATE TABLE `Files` (
  `ID` bigint UNSIGNED NOT NULL,
  `Name` varchar(64) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `Description` varchar(4096) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `FileHosting` int UNSIGNED NOT NULL,
  `FilePath` varchar(256) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `PreviewHosting` int UNSIGNED NOT NULL,
  `PreviewPath` varchar(256) COLLATE utf8_unicode_ci NOT NULL,
  `Author` int UNSIGNED DEFAULT NULL,
  `PostedBy` int UNSIGNED NOT NULL,
  `FileFormat` smallint UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `FilesParticipants`
--

CREATE TABLE `FilesParticipants` (
  `FileID` bigint UNSIGNED NOT NULL,
  `PersonID` int UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `FilesTags`
--

CREATE TABLE `FilesTags` (
  `FileID` bigint UNSIGNED NOT NULL,
  `TagID` int UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `Hostings`
--

CREATE TABLE `Hostings` (
  `ID` int UNSIGNED NOT NULL,
  `URL` varchar(256) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `People`
--

CREATE TABLE `People` (
  `ID` int UNSIGNED NOT NULL,
  `FirstName` varchar(64) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `SecondName` varchar(64) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `Surname` varchar(64) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `Description` varchar(2048) COLLATE utf8_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `Tags`
--

CREATE TABLE `Tags` (
  `ID` int UNSIGNED NOT NULL,
  `Tag` varchar(64) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `Description` varchar(2048) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `RequiresPermission` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `Users`
--

CREATE TABLE `Users` (
  `ID` int UNSIGNED NOT NULL,
  `Username` varchar(64) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `Password` varchar(256) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `Email` varchar(128) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `VerificationCode` varchar(8) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `UsersPermissions`
--

CREATE TABLE `UsersPermissions` (
  `UserID` int UNSIGNED NOT NULL,
  `TagID` int UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `FileFormats`
--
ALTER TABLE `FileFormats`
  ADD PRIMARY KEY (`ID`),
  ADD UNIQUE KEY `MIME` (`MIME`),
  ADD UNIQUE KEY `Name` (`Name`);

--
-- Indexes for table `Files`
--
ALTER TABLE `Files`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `FileFormat` (`FileFormat`),
  ADD KEY `PostedBy` (`PostedBy`),
  ADD KEY `author` (`Author`),
  ADD KEY `StorageServer` (`FileHosting`),
  ADD KEY `PreviewHosting` (`PreviewHosting`);

--
-- Indexes for table `FilesParticipants`
--
ALTER TABLE `FilesParticipants`
  ADD KEY `fileID` (`FileID`),
  ADD KEY `personID` (`PersonID`);

--
-- Indexes for table `FilesTags`
--
ALTER TABLE `FilesTags`
  ADD KEY `fileID` (`FileID`),
  ADD KEY `tagID` (`TagID`);

--
-- Indexes for table `Hostings`
--
ALTER TABLE `Hostings`
  ADD PRIMARY KEY (`ID`);

--
-- Indexes for table `People`
--
ALTER TABLE `People`
  ADD PRIMARY KEY (`ID`);

--
-- Indexes for table `Tags`
--
ALTER TABLE `Tags`
  ADD PRIMARY KEY (`ID`);

--
-- Indexes for table `Users`
--
ALTER TABLE `Users`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `email` (`Email`);

--
-- Indexes for table `UsersPermissions`
--
ALTER TABLE `UsersPermissions`
  ADD KEY `userID` (`UserID`),
  ADD KEY `permissionID` (`TagID`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `FileFormats`
--
ALTER TABLE `FileFormats`
  MODIFY `ID` smallint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `Files`
--
ALTER TABLE `Files`
  MODIFY `ID` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `Hostings`
--
ALTER TABLE `Hostings`
  MODIFY `ID` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `People`
--
ALTER TABLE `People`
  MODIFY `ID` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `Tags`
--
ALTER TABLE `Tags`
  MODIFY `ID` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `Users`
--
ALTER TABLE `Users`
  MODIFY `ID` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `Files`
--
ALTER TABLE `Files`
  ADD CONSTRAINT `Files_ibfk_1` FOREIGN KEY (`PostedBy`) REFERENCES `Users` (`ID`) ON UPDATE CASCADE,
  ADD CONSTRAINT `Files_ibfk_2` FOREIGN KEY (`FileHosting`) REFERENCES `Hostings` (`ID`) ON UPDATE CASCADE,
  ADD CONSTRAINT `Files_ibfk_3` FOREIGN KEY (`FileFormat`) REFERENCES `FileFormats` (`ID`) ON UPDATE CASCADE,
  ADD CONSTRAINT `Files_ibfk_4` FOREIGN KEY (`Author`) REFERENCES `People` (`ID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `Files_ibfk_5` FOREIGN KEY (`Author`) REFERENCES `People` (`ID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `Files_ibfk_6` FOREIGN KEY (`PreviewHosting`) REFERENCES `Hostings` (`ID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `FilesParticipants`
--
ALTER TABLE `FilesParticipants`
  ADD CONSTRAINT `FilesParticipants_ibfk_1` FOREIGN KEY (`FileID`) REFERENCES `Files` (`ID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `FilesParticipants_ibfk_3` FOREIGN KEY (`FileID`) REFERENCES `Files` (`ID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `FilesParticipants_ibfk_4` FOREIGN KEY (`PersonID`) REFERENCES `People` (`ID`);

--
-- Constraints for table `FilesTags`
--
ALTER TABLE `FilesTags`
  ADD CONSTRAINT `FilesTags_ibfk_1` FOREIGN KEY (`FileID`) REFERENCES `Files` (`ID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `FilesTags_ibfk_2` FOREIGN KEY (`TagID`) REFERENCES `Tags` (`ID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `FilesTags_ibfk_3` FOREIGN KEY (`FileID`) REFERENCES `Files` (`ID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `FilesTags_ibfk_4` FOREIGN KEY (`TagID`) REFERENCES `Tags` (`ID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `UsersPermissions`
--
ALTER TABLE `UsersPermissions`
  ADD CONSTRAINT `UsersPermissions_ibfk_1` FOREIGN KEY (`UserID`) REFERENCES `Users` (`ID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `UsersPermissions_ibfk_3` FOREIGN KEY (`UserID`) REFERENCES `Users` (`ID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `UsersPermissions_ibfk_4` FOREIGN KEY (`TagID`) REFERENCES `Tags` (`ID`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
