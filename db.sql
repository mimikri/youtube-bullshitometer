-- --------------------------------------------------------
-- Host:                         127.0.0.1
-- Server-Version:               10.11.13-MariaDB-0ubuntu0.24.04.1 - Ubuntu 24.04
-- Server-Betriebssystem:        debian-linux-gnu
-- HeidiSQL Version:             12.8.0.6908
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

-- Exportiere Struktur von Tabelle yt_analyse.video
CREATE TABLE IF NOT EXISTS `video` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `set_date` datetime DEFAULT current_timestamp(),
  `channel_title` varchar(255) NOT NULL,
  `video_title` varchar(255) NOT NULL,
  `bullshit_percent` decimal(5,2) DEFAULT NULL,
  `valid_percent` decimal(5,2) DEFAULT NULL,
  `niveau_percent` decimal(5,2) DEFAULT NULL,
  `video_description` mediumtext NOT NULL,
  `llm_model` varchar(255) NOT NULL DEFAULT '0',
  `llm_provider` varchar(255) NOT NULL DEFAULT '0',
  `video_id` varchar(255) NOT NULL,
  `channel_id` varchar(255) NOT NULL,
  `transcript` longtext DEFAULT NULL,
  `analysis_html` mediumtext DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `llm_provider` (`llm_provider`,`llm_model`),
  KEY `channel_id` (`channel_id`)
) ENGINE=InnoDB AUTO_INCREMENT=66 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Daten-Export vom Benutzer nicht ausgewählt

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
