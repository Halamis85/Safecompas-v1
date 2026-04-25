-- --------------------------------------------------------
-- Hostitel:                     127.0.0.1
-- Verze serveru:                8.4.3 - MySQL Community Server - GPL
-- OS serveru:                   Win64
-- HeidiSQL Verze:               12.8.0.6908
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

-- Exportování struktury pro tabulka safecompas.users
CREATE TABLE IF NOT EXISTS `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `username` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `firstname` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `lastname` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `alias` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `role` enum('user','admin') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'user',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `last_login` timestamp NULL DEFAULT NULL,
  `preferences` json DEFAULT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  UNIQUE KEY `users_username_unique` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Exportování dat pro tabulku safecompas.users: ~3 rows (přibližně)
INSERT INTO `users` (`id`, `name`, `email`, `email_verified_at`, `password`, `username`, `firstname`, `lastname`, `alias`, `role`, `is_active`, `last_login`, `preferences`, `remember_token`, `created_at`, `updated_at`) VALUES
	(1, 'lhala', 'halamis74@gmail.com', NULL, '$2y$12$zjGPV3ZsqYJH4skQczx8SeQm2Q9B8H1s2glyhXLzjvpmBRm3QfX6a', 'lhala', 'Lukáš', 'Halamka', 'Lukáši', 'admin', 1, '2026-04-25 02:13:47', NULL, NULL, '2026-04-24 13:26:26', '2026-04-25 02:13:47'),
	(5, 'Jindřich Halamka', 'halamis@seznam.cz', NULL, '$2y$12$F7M6ekU6DbiEDk5h2UaYzuyC85.PDtKc0MSme8rTkyg0KLAr0hUCK', 'jhala', 'Jindřich', 'Halamka', 'Jindro', 'admin', 1, '2026-04-25 02:12:46', NULL, NULL, '2026-04-25 01:01:51', '2026-04-25 02:12:46'),
	(6, 'Hovno Nalopatě', 'Lukas.Halamka@tark-solutions.com', NULL, '$2y$12$nxm.jQsSFKGpSXthJNYguuGPob9GYhS86fnTpIPo.6xCCIN.J3dlK', 'hovno', 'Hovno', 'Nalopatě', 'Hovne', 'user', 1, '2026-04-25 02:25:57', NULL, NULL, '2026-04-25 02:15:18', '2026-04-25 02:25:57');

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
