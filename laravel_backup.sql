-- MySQL dump 10.13  Distrib 8.4.9, for Linux (x86_64)
--
-- Host: localhost    Database: laravel
-- ------------------------------------------------------
-- Server version	8.4.9

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `academic_calendar_events`
--

DROP TABLE IF EXISTS `academic_calendar_events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `academic_calendar_events` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `event_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `allow_attendance` tinyint(1) NOT NULL DEFAULT '0',
  `override_schedule` tinyint(1) NOT NULL DEFAULT '0',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `academic_calendar_events_date_active_index` (`start_date`,`end_date`,`is_active`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `academic_calendar_events`
--

LOCK TABLES `academic_calendar_events` WRITE;
/*!40000 ALTER TABLE `academic_calendar_events` DISABLE KEYS */;
INSERT INTO `academic_calendar_events` VALUES (1,'libur','2026-05-02','2026-05-09','national_holiday',1,0,0,NULL,'2026-05-02 10:58:33','2026-05-02 10:58:33');
/*!40000 ALTER TABLE `academic_calendar_events` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `attendance_day_overrides`
--

DROP TABLE IF EXISTS `attendance_day_overrides`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `attendance_day_overrides` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `date` date NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `event_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'custom',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `attendance_site_id` bigint unsigned DEFAULT NULL,
  `override_attendance_policy` tinyint(1) NOT NULL DEFAULT '0',
  `override_schedule` tinyint(1) NOT NULL DEFAULT '0',
  `allow_check_in` tinyint(1) NOT NULL DEFAULT '1',
  `allow_check_out` tinyint(1) NOT NULL DEFAULT '1',
  `waive_check_out` tinyint(1) NOT NULL DEFAULT '0',
  `dismiss_students_early` tinyint(1) NOT NULL DEFAULT '0',
  `check_in_open_at` time DEFAULT NULL,
  `check_in_on_time_until` time DEFAULT NULL,
  `check_in_close_at` time DEFAULT NULL,
  `check_out_open_at` time DEFAULT NULL,
  `check_out_close_at` time DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_by` bigint unsigned NOT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `attendance_day_overrides_attendance_site_id_foreign` (`attendance_site_id`),
  KEY `attendance_day_overrides_created_by_foreign` (`created_by`),
  KEY `attendance_day_overrides_updated_by_foreign` (`updated_by`),
  KEY `attendance_day_overrides_date_is_active_index` (`date`,`is_active`),
  CONSTRAINT `attendance_day_overrides_attendance_site_id_foreign` FOREIGN KEY (`attendance_site_id`) REFERENCES `attendance_sites` (`id`) ON DELETE SET NULL,
  CONSTRAINT `attendance_day_overrides_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `attendance_day_overrides_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `attendance_day_overrides`
--

LOCK TABLES `attendance_day_overrides` WRITE;
/*!40000 ALTER TABLE `attendance_day_overrides` DISABLE KEYS */;
/*!40000 ALTER TABLE `attendance_day_overrides` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `attendance_manual_statuses`
--

DROP TABLE IF EXISTS `attendance_manual_statuses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `attendance_manual_statuses` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `student_profile_id` bigint unsigned NOT NULL,
  `attendance_site_id` bigint unsigned DEFAULT NULL,
  `date` date NOT NULL,
  `type` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `reason` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'approved',
  `created_by` bigint unsigned NOT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `attendance_manual_statuses_user_id_foreign` (`user_id`),
  KEY `attendance_manual_statuses_attendance_site_id_foreign` (`attendance_site_id`),
  KEY `attendance_manual_statuses_created_by_foreign` (`created_by`),
  KEY `attendance_manual_statuses_updated_by_foreign` (`updated_by`),
  KEY `attendance_manual_statuses_student_profile_id_date_status_index` (`student_profile_id`,`date`,`status`),
  CONSTRAINT `attendance_manual_statuses_attendance_site_id_foreign` FOREIGN KEY (`attendance_site_id`) REFERENCES `attendance_sites` (`id`) ON DELETE SET NULL,
  CONSTRAINT `attendance_manual_statuses_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `attendance_manual_statuses_student_profile_id_foreign` FOREIGN KEY (`student_profile_id`) REFERENCES `student_profiles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `attendance_manual_statuses_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `attendance_manual_statuses_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `attendance_manual_statuses`
--

LOCK TABLES `attendance_manual_statuses` WRITE;
/*!40000 ALTER TABLE `attendance_manual_statuses` DISABLE KEYS */;
/*!40000 ALTER TABLE `attendance_manual_statuses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `attendance_records`
--

DROP TABLE IF EXISTS `attendance_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `attendance_records` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `attendance_site_id` bigint unsigned DEFAULT NULL,
  `schedule_id` bigint unsigned DEFAULT NULL,
  `attendance_type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'rejected',
  `attendance_at` timestamp NOT NULL,
  `client_time` timestamp NULL DEFAULT NULL,
  `reason_code` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reason_detail` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `distance_m` decimal(10,2) DEFAULT NULL,
  `network_payload` json DEFAULT NULL,
  `location_payload` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `attendance_records_attendance_site_id_foreign` (`attendance_site_id`),
  KEY `attendance_records_schedule_id_foreign` (`schedule_id`),
  KEY `attendance_records_user_id_attendance_at_index` (`user_id`,`attendance_at`),
  KEY `attendance_records_status_attendance_type_index` (`status`,`attendance_type`),
  CONSTRAINT `attendance_records_attendance_site_id_foreign` FOREIGN KEY (`attendance_site_id`) REFERENCES `attendance_sites` (`id`) ON DELETE SET NULL,
  CONSTRAINT `attendance_records_schedule_id_foreign` FOREIGN KEY (`schedule_id`) REFERENCES `schedules` (`id`) ON DELETE SET NULL,
  CONSTRAINT `attendance_records_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `attendance_records`
--

LOCK TABLES `attendance_records` WRITE;
/*!40000 ALTER TABLE `attendance_records` DISABLE KEYS */;
/*!40000 ALTER TABLE `attendance_records` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `attendance_site_wifi_rules`
--

DROP TABLE IF EXISTS `attendance_site_wifi_rules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `attendance_site_wifi_rules` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `attendance_site_id` bigint unsigned NOT NULL,
  `ssid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `bssid` varchar(17) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ip_subnet` varchar(43) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `attendance_site_wifi_rules_attendance_site_id_is_active_index` (`attendance_site_id`,`is_active`),
  CONSTRAINT `attendance_site_wifi_rules_attendance_site_id_foreign` FOREIGN KEY (`attendance_site_id`) REFERENCES `attendance_sites` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `attendance_site_wifi_rules`
--

LOCK TABLES `attendance_site_wifi_rules` WRITE;
/*!40000 ALTER TABLE `attendance_site_wifi_rules` DISABLE KEYS */;
INSERT INTO `attendance_site_wifi_rules` VALUES (3,1,'ERA BARU DIGITAMA','A0:31:DB:00:2A:98',NULL,1,'2026-04-30 02:02:06','2026-04-30 02:02:06');
/*!40000 ALTER TABLE `attendance_site_wifi_rules` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `attendance_sites`
--

DROP TABLE IF EXISTS `attendance_sites`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `attendance_sites` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `latitude` decimal(10,7) NOT NULL,
  `longitude` decimal(10,7) NOT NULL,
  `radius_m` int unsigned NOT NULL DEFAULT '100',
  `check_in_open_at` time DEFAULT NULL,
  `check_in_on_time_until` time DEFAULT NULL,
  `check_in_close_at` time DEFAULT NULL,
  `check_out_open_at` time DEFAULT NULL,
  `check_out_close_at` time DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `attendance_sites_is_active_index` (`is_active`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `attendance_sites`
--

LOCK TABLES `attendance_sites` WRITE;
/*!40000 ALTER TABLE `attendance_sites` DISABLE KEYS */;
INSERT INTO `attendance_sites` VALUES (1,'KANTOR EBEDE',-7.2971960,112.7583465,100,'06:00:00','07:00:00','12:00:00','12:00:00','15:00:00',1,NULL,'2026-04-29 08:34:27','2026-04-30 02:02:06');
/*!40000 ALTER TABLE `attendance_sites` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `attendance_validation_logs`
--

DROP TABLE IF EXISTS `attendance_validation_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `attendance_validation_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `attendance_record_id` bigint unsigned DEFAULT NULL,
  `user_id` bigint unsigned NOT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `reason_code` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `details` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `attendance_validation_logs_attendance_record_id_foreign` (`attendance_record_id`),
  KEY `attendance_validation_logs_user_id_created_at_index` (`user_id`,`created_at`),
  CONSTRAINT `attendance_validation_logs_attendance_record_id_foreign` FOREIGN KEY (`attendance_record_id`) REFERENCES `attendance_records` (`id`) ON DELETE SET NULL,
  CONSTRAINT `attendance_validation_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `attendance_validation_logs`
--

LOCK TABLES `attendance_validation_logs` WRITE;
/*!40000 ALTER TABLE `attendance_validation_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `attendance_validation_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache`
--

DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache`
--

LOCK TABLES `cache` WRITE;
/*!40000 ALTER TABLE `cache` DISABLE KEYS */;
INSERT INTO `cache` VALUES ('robithotul-hikmah-admin-cache-5d3e481cd0aaa79ab21c760d04b21cc3','i:1;',1777736915),('robithotul-hikmah-admin-cache-5d3e481cd0aaa79ab21c760d04b21cc3:timer','i:1777736915;',1777736915);
/*!40000 ALTER TABLE `cache` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache_locks`
--

DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache_locks`
--

LOCK TABLES `cache_locks` WRITE;
/*!40000 ALTER TABLE `cache_locks` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache_locks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `class_student`
--

DROP TABLE IF EXISTS `class_student`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `class_student` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `class_id` bigint unsigned NOT NULL,
  `student_profile_id` bigint unsigned NOT NULL,
  `school_year_id` bigint unsigned NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `class_student_unique` (`class_id`,`student_profile_id`,`school_year_id`),
  KEY `class_student_student_profile_id_foreign` (`student_profile_id`),
  KEY `class_student_school_year_id_foreign` (`school_year_id`),
  CONSTRAINT `class_student_class_id_foreign` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `class_student_school_year_id_foreign` FOREIGN KEY (`school_year_id`) REFERENCES `school_years` (`id`),
  CONSTRAINT `class_student_student_profile_id_foreign` FOREIGN KEY (`student_profile_id`) REFERENCES `student_profiles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=65 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `class_student`
--

LOCK TABLES `class_student` WRITE;
/*!40000 ALTER TABLE `class_student` DISABLE KEYS */;
INSERT INTO `class_student` VALUES (60,6,60,1,1,'2026-04-29 08:28:33','2026-04-29 08:28:33'),(61,5,61,1,1,'2026-04-29 08:30:45','2026-04-29 08:30:45'),(62,1,62,1,1,'2026-04-29 08:31:29','2026-04-29 08:31:29'),(64,1,64,1,1,'2026-05-02 11:01:57','2026-05-02 11:01:57');
/*!40000 ALTER TABLE `class_student` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `classes`
--

DROP TABLE IF EXISTS `classes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `classes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `school_year_id` bigint unsigned NOT NULL,
  `name` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `level` tinyint NOT NULL,
  `homeroom_teacher_id` bigint unsigned DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `classes_school_year_id_name_unique` (`school_year_id`,`name`),
  KEY `classes_homeroom_teacher_id_foreign` (`homeroom_teacher_id`),
  KEY `classes_school_year_id_level_index` (`school_year_id`,`level`),
  CONSTRAINT `classes_homeroom_teacher_id_foreign` FOREIGN KEY (`homeroom_teacher_id`) REFERENCES `teacher_profiles` (`id`) ON DELETE SET NULL,
  CONSTRAINT `classes_school_year_id_foreign` FOREIGN KEY (`school_year_id`) REFERENCES `school_years` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `classes`
--

LOCK TABLES `classes` WRITE;
/*!40000 ALTER TABLE `classes` DISABLE KEYS */;
INSERT INTO `classes` VALUES (1,1,'7A',7,1,1,'2026-04-29 08:28:32','2026-04-29 08:28:32'),(2,1,'7B',7,2,1,'2026-04-29 08:28:32','2026-04-29 08:28:32'),(3,1,'8A',8,3,1,'2026-04-29 08:28:32','2026-04-29 08:28:32'),(4,1,'8B',8,1,1,'2026-04-29 08:28:32','2026-04-29 08:28:32'),(5,1,'9A',9,5,1,'2026-04-29 08:28:32','2026-04-29 08:28:32'),(6,1,'9B',9,1,1,'2026-04-29 08:28:32','2026-04-29 08:28:32');
/*!40000 ALTER TABLE `classes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `daily_attendances`
--

DROP TABLE IF EXISTS `daily_attendances`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `daily_attendances` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `student_profile_id` bigint unsigned NOT NULL,
  `attendance_site_id` bigint unsigned NOT NULL,
  `date` date NOT NULL,
  `check_in_at` timestamp NULL DEFAULT NULL,
  `check_out_at` timestamp NULL DEFAULT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `late_minutes` smallint unsigned DEFAULT NULL,
  `check_in_reason_code` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `check_in_reason_detail` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `network_payload` json DEFAULT NULL,
  `location_payload` json DEFAULT NULL,
  `device_payload` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `daily_attendances_user_id_date_unique` (`user_id`,`date`),
  KEY `daily_attendances_attendance_site_id_foreign` (`attendance_site_id`),
  KEY `daily_attendances_student_profile_id_date_index` (`student_profile_id`,`date`),
  CONSTRAINT `daily_attendances_attendance_site_id_foreign` FOREIGN KEY (`attendance_site_id`) REFERENCES `attendance_sites` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `daily_attendances_student_profile_id_foreign` FOREIGN KEY (`student_profile_id`) REFERENCES `student_profiles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `daily_attendances_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `daily_attendances`
--

LOCK TABLES `daily_attendances` WRITE;
/*!40000 ALTER TABLE `daily_attendances` DISABLE KEYS */;
INSERT INTO `daily_attendances` VALUES (1,67,61,1,'2026-04-30','2026-04-30 08:47:39',NULL,'late',107,'LATE_CHECK_IN','Check-in setelah batas hadir tepat waktu.','{\"ssid\": \"ERA BARU DIGITAMA\", \"bssid\": \"a0:31:db:00:2a:98\", \"local_ip\": \"192.168.100.251\", \"transport\": \"WIFI\", \"gateway_ip\": \"0.0.0.0\", \"subnet_prefix\": 24}','{\"is_mock\": false, \"latitude\": -7.2973268, \"provider\": \"fused\", \"longitude\": 112.758318, \"accuracy_m\": 20.100000381469727, \"captured_at\": \"2026-04-30T08:47:39+07:00\"}','{\"platform\": \"android\", \"os_version\": \"13\", \"app_version\": \"1.0\"}','2026-04-30 01:47:39','2026-04-30 01:47:39'),(2,68,62,1,'2026-04-30','2026-04-30 03:37:38','2026-04-30 06:05:14','late',217,'LATE_CHECK_IN','Check-in setelah batas hadir tepat waktu.','{\"ssid\": \"ERA BARU DIGITAMA\", \"bssid\": \"a0:31:db:00:2a:98\", \"local_ip\": \"192.168.100.251\", \"transport\": \"WIFI\", \"gateway_ip\": \"0.0.0.0\", \"subnet_prefix\": 24}','{\"is_mock\": false, \"latitude\": -7.2973308, \"provider\": \"fused\", \"longitude\": 112.7583184, \"accuracy_m\": 20, \"captured_at\": \"2026-04-30T13:05:14+07:00\"}','{\"platform\": \"android\", \"os_version\": \"13\", \"app_version\": \"1.0\"}','2026-04-30 03:37:39','2026-04-30 06:05:14');
/*!40000 ALTER TABLE `daily_attendances` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `failed_jobs`
--

LOCK TABLES `failed_jobs` WRITE;
/*!40000 ALTER TABLE `failed_jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `failed_jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `job_batches`
--

DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext COLLATE utf8mb4_unicode_ci,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `job_batches`
--

LOCK TABLES `job_batches` WRITE;
/*!40000 ALTER TABLE `job_batches` DISABLE KEYS */;
/*!40000 ALTER TABLE `job_batches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jobs`
--

DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` tinyint unsigned NOT NULL,
  `reserved_at` int unsigned DEFAULT NULL,
  `available_at` int unsigned NOT NULL,
  `created_at` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jobs`
--

LOCK TABLES `jobs` WRITE;
/*!40000 ALTER TABLE `jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'0001_01_01_000000_create_users_table',1),(2,'0001_01_01_000001_create_cache_table',1),(3,'0001_01_01_000002_create_jobs_table',1),(4,'2025_08_14_170933_add_two_factor_columns_to_users_table',1),(5,'2026_04_22_044224_add_role_fields_to_users_table',1),(6,'2026_04_22_044225_create_school_years_table',1),(7,'2026_04_22_044226_create_teacher_profiles_table',1),(8,'2026_04_22_044227_create_student_profiles_table',1),(9,'2026_04_22_044228_create_classes_table',1),(10,'2026_04_22_044229_create_subjects_table',1),(11,'2026_04_22_044230_create_class_student_table',1),(12,'2026_04_22_044231_create_teacher_subjects_table',1),(13,'2026_04_22_044232_create_schedules_table',1),(14,'2026_04_22_044233_create_password_reset_audits_table',1),(15,'2026_04_22_050151_create_personal_access_tokens_table',1),(16,'2026_04_24_073721_change_role_to_roles_json_on_users_table',1),(17,'2026_04_28_024328_add_plain_password_to_users_table',1),(18,'2026_04_28_071247_create_attendance_site_wifi_rules_table',1),(19,'2026_04_28_071247_create_attendance_sites_table',1),(20,'2026_04_28_071248_create_academic_calendar_events_table',1),(21,'2026_04_28_071248_create_attendance_records_table',1),(22,'2026_04_28_071248_create_attendance_validation_logs_table',1),(23,'2026_04_28_071249_add_attendance_site_foreign_key_to_attendance_site_wifi_rules_table',1),(24,'2026_04_29_072541_create_attendance_day_overrides_table',1),(25,'2026_04_29_080959_add_daily_policy_fields_to_attendance_sites_table',1),(26,'2026_04_29_100000_create_daily_attendances_table',1),(27,'2026_04_29_100001_create_attendance_manual_statuses_table',1);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_reset_audits`
--

DROP TABLE IF EXISTS `password_reset_audits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset_audits` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `reset_by_admin_id` bigint unsigned NOT NULL,
  `reason` text COLLATE utf8mb4_unicode_ci,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `password_reset_audits_reset_by_admin_id_foreign` (`reset_by_admin_id`),
  KEY `password_reset_audits_user_id_index` (`user_id`),
  CONSTRAINT `password_reset_audits_reset_by_admin_id_foreign` FOREIGN KEY (`reset_by_admin_id`) REFERENCES `users` (`id`),
  CONSTRAINT `password_reset_audits_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_reset_audits`
--

LOCK TABLES `password_reset_audits` WRITE;
/*!40000 ALTER TABLE `password_reset_audits` DISABLE KEYS */;
INSERT INTO `password_reset_audits` VALUES (1,66,1,NULL,'192.168.97.1','2026-05-02 10:59:02','2026-05-02 10:59:02');
/*!40000 ALTER TABLE `password_reset_audits` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_reset_tokens`
--

LOCK TABLES `password_reset_tokens` WRITE;
/*!40000 ALTER TABLE `password_reset_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_reset_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `personal_access_tokens`
--

DROP TABLE IF EXISTS `personal_access_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `personal_access_tokens` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tokenable_id` bigint unsigned NOT NULL,
  `name` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `abilities` text COLLATE utf8mb4_unicode_ci,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`),
  KEY `personal_access_tokens_expires_at_index` (`expires_at`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `personal_access_tokens`
--

LOCK TABLES `personal_access_tokens` WRITE;
/*!40000 ALTER TABLE `personal_access_tokens` DISABLE KEYS */;
INSERT INTO `personal_access_tokens` VALUES (2,'App\\Models\\User',68,'student-app','fa4c3eede0c7355e9523d9bd6182f3443943e40f10928b0ce8d086e07da3990a','[\"*\"]','2026-04-30 02:06:20',NULL,'2026-04-29 08:34:47','2026-04-30 02:06:20'),(3,'App\\Models\\User',68,'student-app','424a31dadb93e055e4c93c29f75fdf640bd2a4782ed93a6dd9d52c0474f35798','[\"*\"]','2026-05-02 11:12:50',NULL,'2026-04-30 03:37:31','2026-05-02 11:12:50'),(4,'App\\Models\\User',70,'student-app','719bcf2aa9d869d5573c5ab8950016683da417b440986a74edc0cd1a6974379a','[\"*\"]','2026-05-02 11:04:52',NULL,'2026-05-02 11:02:14','2026-05-02 11:04:52');
/*!40000 ALTER TABLE `personal_access_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `schedules`
--

DROP TABLE IF EXISTS `schedules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `schedules` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `school_year_id` bigint unsigned NOT NULL,
  `semester` tinyint NOT NULL,
  `class_id` bigint unsigned NOT NULL,
  `subject_id` bigint unsigned NOT NULL,
  `teacher_profile_id` bigint unsigned NOT NULL,
  `day_of_week` tinyint NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `room` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `schedules_school_year_id_foreign` (`school_year_id`),
  KEY `schedules_subject_id_foreign` (`subject_id`),
  KEY `schedules_teacher_conflict_index` (`teacher_profile_id`,`school_year_id`,`semester`,`day_of_week`),
  KEY `schedules_class_conflict_index` (`class_id`,`school_year_id`,`semester`,`day_of_week`),
  CONSTRAINT `schedules_class_id_foreign` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`),
  CONSTRAINT `schedules_school_year_id_foreign` FOREIGN KEY (`school_year_id`) REFERENCES `school_years` (`id`),
  CONSTRAINT `schedules_subject_id_foreign` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`),
  CONSTRAINT `schedules_teacher_profile_id_foreign` FOREIGN KEY (`teacher_profile_id`) REFERENCES `teacher_profiles` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `schedules`
--

LOCK TABLES `schedules` WRITE;
/*!40000 ALTER TABLE `schedules` DISABLE KEYS */;
INSERT INTO `schedules` VALUES (1,1,1,1,1,1,4,'08:00:00','09:00:00','Ruangan Kelas 7A',NULL,1,'2026-04-29 08:32:06','2026-04-29 08:32:06'),(2,1,1,5,4,2,4,'10:00:00','12:00:00','Ruangan Kelas 9A','TESS',1,'2026-04-29 08:32:48','2026-04-29 08:32:48');
/*!40000 ALTER TABLE `schedules` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `school_years`
--

DROP TABLE IF EXISTS `school_years`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `school_years` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `school_years_name_unique` (`name`),
  KEY `school_years_is_active_index` (`is_active`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `school_years`
--

LOCK TABLES `school_years` WRITE;
/*!40000 ALTER TABLE `school_years` DISABLE KEYS */;
INSERT INTO `school_years` VALUES (1,'2026/2027','2026-07-15','2027-06-30',1,'2026-04-29 08:28:32','2026-04-29 08:28:32');
/*!40000 ALTER TABLE `school_years` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessions` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sessions`
--

LOCK TABLES `sessions` WRITE;
/*!40000 ALTER TABLE `sessions` DISABLE KEYS */;
INSERT INTO `sessions` VALUES ('JP301mcsTeannm5OCaBHSMVZKiOplKfcTrRRXhJr',1,'192.168.97.1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','eyJfdG9rZW4iOiJLNG5CelpPeXM3ZTc3TDJrU2tVUDJhdklWZWRYcWxXS1dFWjI1RmFyIiwidXJsIjpbXSwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cL2xvY2FsaG9zdFwvYWRtaW5cL2Rhc2hib2FyZCIsInJvdXRlIjoiYWRtaW4uZGFzaGJvYXJkIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfSwibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiOjF9',1777740430);
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `student_profiles`
--

DROP TABLE IF EXISTS `student_profiles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `student_profiles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `nis` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nisn` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `full_name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `gender` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `birth_date` date DEFAULT NULL,
  `birth_place` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` text COLLATE utf8mb4_unicode_ci,
  `parent_name` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `parent_phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `photo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `student_profiles_user_id_unique` (`user_id`),
  UNIQUE KEY `student_profiles_nis_unique` (`nis`),
  UNIQUE KEY `student_profiles_nisn_unique` (`nisn`),
  CONSTRAINT `student_profiles_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=65 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `student_profiles`
--

LOCK TABLES `student_profiles` WRITE;
/*!40000 ALTER TABLE `student_profiles` DISABLE KEYS */;
INSERT INTO `student_profiles` VALUES (60,66,'55925695','5122939891','Molly Smitham','female','2010-07-12','Marianneport','+1 (571) 383-6728','7372 Zboncak Bypass\nNorth Cydneyview, WA 16094-0324','Hazle Leuschke','(646) 848-0811',NULL,'2026-04-29 08:28:33','2026-04-29 08:28:33'),(61,67,'2202041122','2202041122','Muhammad Husnun Ni\'am','male',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-04-29 08:30:45','2026-04-29 08:30:45'),(62,68,'2202041212','2202041212','Yahya Nur Aditya','male',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-04-29 08:31:29','2026-04-29 08:31:29'),(64,70,'1122',NULL,'idris','male',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-05-02 11:01:57','2026-05-02 11:01:57');
/*!40000 ALTER TABLE `student_profiles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `subjects`
--

DROP TABLE IF EXISTS `subjects`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `subjects` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `subjects_code_unique` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `subjects`
--

LOCK TABLES `subjects` WRITE;
/*!40000 ALTER TABLE `subjects` DISABLE KEYS */;
INSERT INTO `subjects` VALUES (1,'MTK','Matematika',NULL,1,'2026-04-29 08:28:32','2026-04-29 08:28:32'),(2,'BIN','Bahasa Indonesia',NULL,1,'2026-04-29 08:28:32','2026-04-29 08:28:32'),(3,'BIG','Bahasa Inggris',NULL,1,'2026-04-29 08:28:32','2026-04-29 08:28:32'),(4,'IPA','Ilmu Pengetahuan Alam',NULL,1,'2026-04-29 08:28:32','2026-04-29 08:28:32'),(5,'IPS','Ilmu Pengetahuan Sosial',NULL,1,'2026-04-29 08:28:32','2026-04-29 08:28:32'),(6,'FIQ','Fiqih',NULL,1,'2026-04-29 08:28:32','2026-04-29 08:28:32'),(7,'AQD','Aqidah Akhlak',NULL,1,'2026-04-29 08:28:32','2026-04-29 08:28:32'),(8,'QHD','Quran Hadist',NULL,1,'2026-04-29 08:28:32','2026-04-29 08:28:32'),(9,'SKI','Sejarah Kebudayaan Islam',NULL,1,'2026-04-29 08:28:32','2026-04-29 08:28:32'),(10,'BAR','Bahasa Arab',NULL,1,'2026-04-29 08:28:32','2026-04-29 08:28:32');
/*!40000 ALTER TABLE `subjects` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `teacher_profiles`
--

DROP TABLE IF EXISTS `teacher_profiles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `teacher_profiles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `nip` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `full_name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `gender` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` text COLLATE utf8mb4_unicode_ci,
  `photo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `teacher_profiles_user_id_unique` (`user_id`),
  UNIQUE KEY `teacher_profiles_nip_unique` (`nip`),
  CONSTRAINT `teacher_profiles_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `teacher_profiles`
--

LOCK TABLES `teacher_profiles` WRITE;
/*!40000 ALTER TABLE `teacher_profiles` DISABLE KEYS */;
INSERT INTO `teacher_profiles` VALUES (1,2,'554058618669920116','Valentin Little','male','480-535-5772','7253 Goyette Port Suite 514\nDixiemouth, WV 14049',NULL,'2026-04-29 08:28:32','2026-04-29 08:28:32'),(2,3,'852570649501303512','Dr. Kade O\'Conner','female','+1-985-248-6681','7076 Rice Ville Suite 948\nOlsonburgh, CT 73010-2368',NULL,'2026-04-29 08:28:32','2026-04-29 08:28:32'),(3,4,'943898125543476742','Linda Mraz DVM','male','+1-865-528-8535','55411 Christiana Point\nNew Ebba, OR 59094-9263',NULL,'2026-04-29 08:28:32','2026-04-29 08:28:32'),(4,5,'744013613739889071','Maribel Cronin','male','+1-802-508-9908','198 Catalina Station Apt. 770\nNorth Barrett, NJ 31120',NULL,'2026-04-29 08:28:32','2026-04-29 08:28:32'),(5,6,'392697325814985261','Dr. Alanis Kub','female','+1-754-812-1673','915 Nico Dam Suite 749\nSusanbury, OH 93713',NULL,'2026-04-29 08:28:32','2026-04-29 08:28:32');
/*!40000 ALTER TABLE `teacher_profiles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `teacher_subjects`
--

DROP TABLE IF EXISTS `teacher_subjects`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `teacher_subjects` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `teacher_profile_id` bigint unsigned NOT NULL,
  `subject_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `teacher_subjects_unique` (`teacher_profile_id`,`subject_id`),
  KEY `teacher_subjects_subject_id_foreign` (`subject_id`),
  CONSTRAINT `teacher_subjects_subject_id_foreign` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `teacher_subjects_teacher_profile_id_foreign` FOREIGN KEY (`teacher_profile_id`) REFERENCES `teacher_profiles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `teacher_subjects`
--

LOCK TABLES `teacher_subjects` WRITE;
/*!40000 ALTER TABLE `teacher_subjects` DISABLE KEYS */;
INSERT INTO `teacher_subjects` VALUES (1,1,1,'2026-04-29 08:28:32','2026-04-29 08:28:32'),(2,1,2,'2026-04-29 08:28:32','2026-04-29 08:28:32'),(3,2,3,'2026-04-29 08:28:32','2026-04-29 08:28:32'),(4,2,4,'2026-04-29 08:28:32','2026-04-29 08:28:32'),(5,3,5,'2026-04-29 08:28:32','2026-04-29 08:28:32'),(6,3,6,'2026-04-29 08:28:32','2026-04-29 08:28:32'),(7,4,7,'2026-04-29 08:28:32','2026-04-29 08:28:32'),(8,4,8,'2026-04-29 08:28:32','2026-04-29 08:28:32'),(9,5,9,'2026-04-29 08:28:32','2026-04-29 08:28:32'),(10,5,10,'2026-04-29 08:28:32','2026-04-29 08:28:32');
/*!40000 ALTER TABLE `teacher_subjects` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `plain_password` text COLLATE utf8mb4_unicode_ci,
  `roles` json DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `must_change_password` tinyint(1) NOT NULL DEFAULT '0',
  `last_login_at` timestamp NULL DEFAULT NULL,
  `two_factor_secret` text COLLATE utf8mb4_unicode_ci,
  `two_factor_recovery_codes` text COLLATE utf8mb4_unicode_ci,
  `two_factor_confirmed_at` timestamp NULL DEFAULT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_username_unique` (`username`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=71 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'Administrator','admin','admin@robithotulhikmah.sch.id','2026-04-29 08:28:32','$2y$12$cWd022hiutnWs8ygato4ruvddYIytPmfmZqaJPvWLMHc/30bffvLu',NULL,'[\"admin\"]',1,0,'2026-05-02 15:47:35',NULL,NULL,NULL,'jM04awRfdY','2026-04-29 08:28:32','2026-05-02 15:47:35'),(2,'Saige Mitchell','yvon','mraz.kirsten@example.org','2026-04-29 08:28:32','$2y$12$cWd022hiutnWs8ygato4ruvddYIytPmfmZqaJPvWLMHc/30bffvLu',NULL,'[\"teacher\"]',1,0,NULL,NULL,NULL,NULL,'UJ0kdl51em','2026-04-29 08:28:32','2026-04-29 08:28:32'),(3,'Prof. Pasquale Terry PhD','ladarius.eichmann','reinger.terrence@example.org','2026-04-29 08:28:32','$2y$12$cWd022hiutnWs8ygato4ruvddYIytPmfmZqaJPvWLMHc/30bffvLu',NULL,'[\"teacher\"]',1,0,NULL,NULL,NULL,NULL,'gGL1sepnus','2026-04-29 08:28:32','2026-04-29 08:28:32'),(4,'Virgil Grimes','fsmitham','oratke@example.org','2026-04-29 08:28:32','$2y$12$cWd022hiutnWs8ygato4ruvddYIytPmfmZqaJPvWLMHc/30bffvLu',NULL,'[\"teacher\"]',1,0,NULL,NULL,NULL,NULL,'YizVwFCDcc','2026-04-29 08:28:32','2026-04-29 08:28:32'),(5,'Armani Schaefer DDS','eprohaska','rebeca.yost@example.com','2026-04-29 08:28:32','$2y$12$cWd022hiutnWs8ygato4ruvddYIytPmfmZqaJPvWLMHc/30bffvLu',NULL,'[\"teacher\"]',1,0,NULL,NULL,NULL,NULL,'ybNQUMcVT6','2026-04-29 08:28:32','2026-04-29 08:28:32'),(6,'Dr. Emmanuelle Ondricka','windler.nona','okeefe.enrique@example.net','2026-04-29 08:28:32','$2y$12$cWd022hiutnWs8ygato4ruvddYIytPmfmZqaJPvWLMHc/30bffvLu',NULL,'[\"teacher\"]',1,0,NULL,NULL,NULL,NULL,'RGn65apU8j','2026-04-29 08:28:32','2026-04-29 08:28:32'),(66,'Elnora Frami','juliana35','renner.zachery@example.com','2026-04-29 08:28:33','$2y$12$5FqB.TBi92Njf5XW5jfkbeBzsmMur8xCXnUGvWNDkz4a0VJ0qRffC','eyJpdiI6ImgxSTFMdEw4c3pubDl0TXpBbngxYUE9PSIsInZhbHVlIjoicHBDSTR4elM2YWNEYUV3YzBjVkVSUT09IiwibWFjIjoiZTBlMGM5ZjQ5MGM0M2Y5OTM2MDdkMDMyOWJmNzE0ODFmNzk4ZGMxMmM1MTkyMmYwODI5Nzg5ODU1NDA3OTE4NyIsInRhZyI6IiJ9','[\"student\"]',1,1,NULL,NULL,NULL,NULL,'o8uD0csEQL','2026-04-29 08:28:33','2026-05-02 10:59:02'),(67,'Muhammad Husnun Ni\'am','husnun','husnun200702@gmail.com',NULL,'$2y$12$herRleb0eXQ2qzOD0Rqr2ubl0Sb0/OlxwlO9eT1izENaJEmpiNVkG',NULL,'[\"student\"]',1,0,'2026-04-29 08:34:36',NULL,NULL,NULL,NULL,'2026-04-29 08:30:45','2026-04-29 08:34:36'),(68,'Yahya Nur Aditya','yahya','yahya@gmail.com',NULL,'$2y$12$Bpsehav.8ALho73VRJ4LIeMlrhX9fbj93YTlYFJd6olJTWLBtLl7q',NULL,'[\"student\"]',1,0,'2026-04-30 03:37:31',NULL,NULL,NULL,NULL,'2026-04-29 08:31:29','2026-04-30 03:37:31'),(70,'idris','idris',NULL,NULL,'$2y$12$VjWNdXTk9bwR/BYZcNT/Lua2TSVQR4q.qnRdIb5.0ryx993r92lyu',NULL,'[\"student\"]',1,0,'2026-05-02 11:02:14',NULL,NULL,NULL,NULL,'2026-05-02 11:01:57','2026-05-02 11:02:14');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-05-02 16:56:01
