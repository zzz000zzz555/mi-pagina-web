-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: control_acceso_db
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Current Database: `control_acceso_db`
--

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `control_acceso_db` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci */;

USE `control_acceso_db`;

--
-- Table structure for table `bitacora_auditoria`
--

DROP TABLE IF EXISTS `bitacora_auditoria`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bitacora_auditoria` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `actor_user_id` int(11) DEFAULT NULL,
  `actor_employee_id` int(11) DEFAULT NULL,
  `actor_type` varchar(20) NOT NULL DEFAULT 'sistema',
  `actor_name` varchar(150) NOT NULL,
  `action` varchar(80) NOT NULL,
  `entity` varchar(80) NOT NULL,
  `entity_id` int(11) DEFAULT NULL,
  `description` varchar(500) NOT NULL,
  `details_json` longtext DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_audit_logs_created_at` (`created_at`),
  KEY `idx_audit_logs_entity` (`entity`,`entity_id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bitacora_auditoria`
--

LOCK TABLES `bitacora_auditoria` WRITE;
/*!40000 ALTER TABLE `bitacora_auditoria` DISABLE KEYS */;
INSERT INTO `bitacora_auditoria` VALUES (2,1,NULL,'usuario','admin','eliminar','estudiante',3,'Se eliminó el estudiante MARIA ARIAS.','{\"before\":{\"employee_code\":\"EMP003\",\"first_name\":\"MARIA\",\"last_name\":\"ARIAS\",\"dni\":null,\"age\":null,\"sex\":null,\"email\":\"mariaarias@correo.com\",\"department_id\":3,\"cycle_id\":1,\"semester_id\":1,\"shift\":\"manana\",\"status\":\"activo\"}}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','2026-10-15 14:35:01'),(3,1,NULL,'usuario','admin','eliminar','estudiante',2,'Se eliminó el estudiante CARLOS RAMIREZ.','{\"before\":{\"employee_code\":\"EMP002\",\"first_name\":\"CARLOS\",\"last_name\":\"RAMIREZ\",\"dni\":null,\"age\":null,\"sex\":null,\"email\":\"carlosramirez@correo.com\",\"department_id\":2,\"cycle_id\":1,\"semester_id\":1,\"shift\":\"manana\",\"status\":\"activo\"}}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','2026-10-15 14:35:06'),(4,1,NULL,'usuario','admin','eliminar','estudiante',1,'Se eliminó el estudiante VICTOR RAMOS.','{\"before\":{\"employee_code\":\"EMP001\",\"first_name\":\"VICTOR\",\"last_name\":\"RAMOS\",\"dni\":null,\"age\":null,\"sex\":null,\"email\":\"victor.rs.datsoft@gmail.com\",\"department_id\":5,\"cycle_id\":1,\"semester_id\":1,\"shift\":\"manana\",\"status\":\"activo\"}}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','2026-10-15 14:35:11'),(5,1,NULL,'usuario','admin','crear','estudiante',45,'Se registró el estudiante Kinich Aurea.','{\"after\":{\"employee_code\":\"JSC-2026-0001\",\"first_name\":\"Kinich\",\"last_name\":\"Aurea\",\"dni\":\"12345678\",\"age\":25,\"sex\":\"masculino\",\"email\":\"Kinich@gmail.com\",\"department_id\":\"8\",\"cycle_id\":6,\"semester_id\":4,\"shift\":\"tarde\"}}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','2026-10-15 14:39:25'),(6,1,NULL,'usuario','admin','cambiar_estado','estudiante',44,'Se cambió el estado del estudiante SAMUEL AMOS VILLANUEVA CHINCHAY.','{\"before\":{\"status\":\"activo\"},\"after\":{\"status\":\"inactivo\"}}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','2026-10-15 14:47:48'),(7,1,NULL,'usuario','admin','cambiar_estado','estudiante',44,'Se cambió el estado del estudiante SAMUEL AMOS VILLANUEVA CHINCHAY.','{\"before\":{\"status\":\"inactivo\"},\"after\":{\"status\":\"activo\"}}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','2026-10-15 14:47:53'),(8,1,NULL,'usuario','admin','editar','estudiante',43,'Se actualizaron los datos de JOSÉ MANUEL ROSALES RAMOS.','{\"before\":{\"employee_code\":\"EST-2026-0012\",\"first_name\":\"JOSÉ MANUEL\",\"last_name\":\"ROSALES RAMOS\",\"dni\":\"75689045\",\"age\":null,\"sex\":null,\"email\":null,\"department_id\":8,\"cycle_id\":6,\"semester_id\":4,\"shift\":\"tarde\",\"status\":\"activo\"},\"after\":{\"first_name\":\"JOSÉ MANUEL\",\"last_name\":\"ROSALES RAMOS\",\"dni\":\"75689045\",\"age\":99,\"sex\":\"masculino\",\"email\":\"R@mail.com\",\"department_id\":\"8\",\"cycle_id\":6,\"semester_id\":4,\"shift\":\"tarde\"}}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','2026-10-15 15:00:52');
/*!40000 ALTER TABLE `bitacora_auditoria` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ciclos`
--

DROP TABLE IF EXISTS `ciclos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ciclos` (
  `id` tinyint(3) unsigned NOT NULL,
  `name` varchar(20) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_cycles_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ciclos`
--

LOCK TABLES `ciclos` WRITE;
/*!40000 ALTER TABLE `ciclos` DISABLE KEYS */;
INSERT INTO `ciclos` VALUES (1,'Ciclo I'),(2,'Ciclo II'),(3,'Ciclo III'),(4,'Ciclo IV'),(5,'Ciclo V'),(6,'Ciclo VI');
/*!40000 ALTER TABLE `ciclos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `configuracion`
--

DROP TABLE IF EXISTS `configuracion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `configuracion` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_name` varchar(50) NOT NULL,
  `setting_value` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_name` (`setting_name`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `configuracion`
--

LOCK TABLES `configuracion` WRITE;
/*!40000 ALTER TABLE `configuracion` DISABLE KEYS */;
INSERT INTO `configuracion` VALUES (1,'entry_time','08:00'),(2,'institution_name','IES José Santos Chocano'),(3,'morning_entry_time','08:00'),(4,'afternoon_entry_time','14:00'),(5,'timezone','America/Lima');
/*!40000 ALTER TABLE `configuracion` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `especialidades`
--

DROP TABLE IF EXISTS `especialidades`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `especialidades` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `status` enum('activo','inactivo') DEFAULT 'activo',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_departments_name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `especialidades`
--

LOCK TABLES `especialidades` WRITE;
/*!40000 ALTER TABLE `especialidades` DISABLE KEYS */;
INSERT INTO `especialidades` VALUES (1,'GERENCIA','activo'),(2,'ADMINISTRACIÓN','activo'),(3,'GESTIÓN HUMANA','activo'),(4,'LOGÍSTICA','activo'),(5,'SISTEMA','activo'),(6,'PRODUCCIÓN','activo'),(8,'DESARROLLO DE SISTEMAS DE INFORMACIÓN','activo');
/*!40000 ALTER TABLE `especialidades` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `estudiantes`
--

DROP TABLE IF EXISTS `estudiantes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `estudiantes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_code` varchar(20) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `dni` varchar(20) DEFAULT NULL,
  `age` tinyint(3) unsigned DEFAULT NULL,
  `sex` enum('masculino','femenino','otro') DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `department_id` int(11) DEFAULT NULL,
  `cycle_id` tinyint(3) unsigned DEFAULT NULL,
  `semester_id` tinyint(3) unsigned DEFAULT NULL,
  `legacy_cycle` varchar(100) DEFAULT NULL,
  `shift` enum('manana','tarde') NOT NULL DEFAULT 'manana',
  `photo_path` varchar(255) DEFAULT 'default.png',
  `status` enum('activo','inactivo') DEFAULT 'activo',
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `employee_code` (`employee_code`),
  UNIQUE KEY `uq_employees_dni` (`dni`),
  KEY `department_id` (`department_id`),
  KEY `idx_employees_cycle_id` (`cycle_id`),
  KEY `idx_employees_semester_id` (`semester_id`),
  CONSTRAINT `estudiantes_ibfk_1` FOREIGN KEY (`department_id`) REFERENCES `especialidades` (`id`),
  CONSTRAINT `fk_employees_cycle` FOREIGN KEY (`cycle_id`) REFERENCES `ciclos` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_employees_semester` FOREIGN KEY (`semester_id`) REFERENCES `semestres` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=46 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `estudiantes`
--

LOCK TABLES `estudiantes` WRITE;
/*!40000 ALTER TABLE `estudiantes` DISABLE KEYS */;
INSERT INTO `estudiantes` VALUES (32,'EST-2026-0001','MARICIELO ABIGAIL','ARAMAYO PINEDO','61035046',NULL,NULL,NULL,NULL,8,6,4,NULL,'tarde','default.png','activo','2026-10-15 14:38:15'),(33,'EST-2026-0002','PEDRO ALDAIR','ARRIBASPLATA MOGOLLON','75527112',NULL,NULL,NULL,NULL,8,6,4,NULL,'tarde','default.png','activo','2026-10-15 14:38:15'),(34,'EST-2026-0003','ZINEDINE ANGELO','AVENDAÑO MORALES','60806033',NULL,NULL,NULL,NULL,8,6,4,NULL,'tarde','default.png','activo','2026-10-15 14:38:15'),(35,'EST-2026-0004','LUIS MIGUEL','CERNA JARA','60806016',NULL,NULL,NULL,NULL,8,6,4,NULL,'tarde','default.png','activo','2026-10-15 14:38:15'),(36,'EST-2026-0005','LUIS FERNANDO','INOÑAN SANTISTEBAN','75100725',NULL,NULL,NULL,NULL,8,6,4,NULL,'tarde','default.png','activo','2026-10-15 14:38:15'),(37,'EST-2026-0006','JORDAN ALBERT','LUIS USURIAGA','60746114',NULL,NULL,NULL,NULL,8,6,4,NULL,'tarde','default.png','activo','2026-10-15 14:38:15'),(38,'EST-2026-0007','DIEGO ESTEFANO','MEDRANO YNGAR','75923808',NULL,NULL,NULL,NULL,8,6,4,NULL,'tarde','default.png','activo','2026-10-15 14:38:15'),(39,'EST-2026-0008','JOSÉ VALENTINO','MENA FERNANDEZ','60959200',NULL,NULL,NULL,NULL,8,6,4,NULL,'tarde','default.png','activo','2026-10-15 14:38:15'),(40,'EST-2026-0009','KEREN SARAI','PACHAS CORCINO','76035435',NULL,NULL,NULL,NULL,8,6,4,NULL,'tarde','default.png','activo','2026-10-15 14:38:15'),(41,'EST-2026-0010','MAYLOO MIKSUCO','QUINECHE BONILLA','60774186',NULL,NULL,NULL,NULL,8,6,4,NULL,'tarde','default.png','activo','2026-10-15 14:38:15'),(42,'EST-2026-0011','ANTHONY JOSUÉ','ROJAS GONZALES','60958140',NULL,NULL,NULL,NULL,8,6,4,NULL,'tarde','default.png','activo','2026-10-15 14:38:15'),(43,'EST-2026-0012','JOSÉ MANUEL','ROSALES RAMOS','75689045',99,'masculino','R@mail.com',NULL,8,6,4,NULL,'tarde','student_photos/student_3911db0d9a96641a8b568ba2.jpg','activo','2026-10-15 14:38:15'),(44,'EST-2026-0013','SAMUEL AMOS','VILLANUEVA CHINCHAY','60241160',NULL,NULL,NULL,NULL,8,6,4,NULL,'tarde','default.png','activo','2026-10-15 14:38:15'),(45,'JSC-2026-0001','Kinich','Aurea','12345678',25,'masculino','Kinich@gmail.com','$2y$10$VxU282tFn9jUrx1MbtqvHepXKZLci.9QyPoVkCvlseL/8s1v7s.IS',8,6,4,NULL,'tarde','student_photos/student_40eb8487bd053ec56306a9f9.jpg','activo','2026-10-15 14:39:25');
/*!40000 ALTER TABLE `estudiantes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `incidencias`
--

DROP TABLE IF EXISTS `incidencias`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `incidencias` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `severity` enum('baja','media','alta') DEFAULT 'baja',
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `attachment` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `incidencias_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `incidencias`
--

LOCK TABLES `incidencias` WRITE;
/*!40000 ALTER TABLE `incidencias` DISABLE KEYS */;
INSERT INTO `incidencias` VALUES (1,'VISITA DE PROVEEDOR MARIO ARIAS','EL PROVEEDOR INGRESÓ CON HERRAMIENTAS DE TRABAJO','baja',1,'2025-11-21 10:46:02','evidencia_1763739962.jpg');
/*!40000 ALTER TABLE `incidencias` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `registros_asistencia`
--

DROP TABLE IF EXISTS `registros_asistencia`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `registros_asistencia` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) NOT NULL,
  `date_log` date NOT NULL,
  `check_in_time` time DEFAULT NULL,
  `check_out_time` time DEFAULT NULL,
  `total_hours` decimal(5,2) DEFAULT NULL,
  `status` enum('a_tiempo','tarde') DEFAULT 'a_tiempo',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_attendance_employee_date` (`employee_id`,`date_log`),
  KEY `employee_id` (`employee_id`),
  KEY `idx_attendance_date_status` (`date_log`,`status`),
  CONSTRAINT `registros_asistencia_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `estudiantes` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `registros_asistencia`
--

LOCK TABLES `registros_asistencia` WRITE;
/*!40000 ALTER TABLE `registros_asistencia` DISABLE KEYS */;
/*!40000 ALTER TABLE `registros_asistencia` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `registros_visitantes`
--

DROP TABLE IF EXISTS `registros_visitantes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `registros_visitantes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `visitor_id` int(11) NOT NULL,
  `employee_to_visit_id` int(11) DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `check_in` datetime DEFAULT current_timestamp(),
  `check_out` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `visitor_id` (`visitor_id`),
  KEY `employee_to_visit_id` (`employee_to_visit_id`),
  CONSTRAINT `registros_visitantes_ibfk_1` FOREIGN KEY (`visitor_id`) REFERENCES `visitantes` (`id`),
  CONSTRAINT `registros_visitantes_ibfk_2` FOREIGN KEY (`employee_to_visit_id`) REFERENCES `estudiantes` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `registros_visitantes`
--

LOCK TABLES `registros_visitantes` WRITE;
/*!40000 ALTER TABLE `registros_visitantes` DISABLE KEYS */;
INSERT INTO `registros_visitantes` VALUES (1,1,NULL,'REUNIÓN CON ÁREA LOGISTICA','2025-11-21 10:43:22','2025-11-21 10:44:23'),(2,2,NULL,'REUNION DE TRABAJO','2025-11-23 16:06:17',NULL);
/*!40000 ALTER TABLE `registros_visitantes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `semestres`
--

DROP TABLE IF EXISTS `semestres`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `semestres` (
  `id` tinyint(3) unsigned NOT NULL,
  `name` varchar(20) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_semesters_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `semestres`
--

LOCK TABLES `semestres` WRITE;
/*!40000 ALTER TABLE `semestres` DISABLE KEYS */;
INSERT INTO `semestres` VALUES (1,'Semestre 1'),(2,'Semestre 2'),(3,'Semestre 3'),(4,'Semestre 4'),(5,'Semestre 5');
/*!40000 ALTER TABLE `semestres` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `usuarios`
--

DROP TABLE IF EXISTS `usuarios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','guardia') DEFAULT 'guardia',
  `created_at` datetime DEFAULT current_timestamp(),
  `status` enum('activo','inactivo') DEFAULT 'activo',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usuarios`
--

LOCK TABLES `usuarios` WRITE;
/*!40000 ALTER TABLE `usuarios` DISABLE KEYS */;
INSERT INTO `usuarios` VALUES (1,'admin','$2y$10$ZDQqXB3RymRVbB79F1ktB.bpUMb3HVWhNUKRPh/dRa4Hg0rGeA62W','admin','2025-11-18 01:01:37','activo'),(3,'seguridad','$2y$10$4O7YBx0b4WlR3vMe12eN4OGe6X7J3tXUXzBH495rsT95YMtKCVh9K','guardia','2025-11-21 10:28:36','activo');
/*!40000 ALTER TABLE `usuarios` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `visitantes`
--

DROP TABLE IF EXISTS `visitantes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `visitantes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `dni` varchar(20) NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `company` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `is_banned` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_visitors_dni` (`dni`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `visitantes`
--

LOCK TABLES `visitantes` WRITE;
/*!40000 ALTER TABLE `visitantes` DISABLE KEYS */;
INSERT INTO `visitantes` VALUES (1,'44444444','MARIO ARIAS','EMPRESA1',NULL,0),(2,'33333333','JUAN ARIAS','EMPRESA2',NULL,0);
/*!40000 ALTER TABLE `visitantes` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-10-15 16:04:52
