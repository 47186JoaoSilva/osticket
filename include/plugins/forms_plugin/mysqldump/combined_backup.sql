-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: osticket
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
-- Table structure for table `ost_ticket__cdata`
--

DROP TABLE IF EXISTS `ost_ticket__cdata`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ost_ticket__cdata` (
  `ticket_id` int(11) unsigned NOT NULL,
  `subject` mediumtext DEFAULT NULL,
  `priority` mediumtext DEFAULT NULL,
  PRIMARY KEY (`ticket_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ost_ticket__cdata`
--

LOCK TABLES `ost_ticket__cdata` WRITE;
/*!40000 ALTER TABLE `ost_ticket__cdata` DISABLE KEYS */;
INSERT INTO `ost_ticket__cdata` VALUES (1,'osTicket Installed!',''),(2,'Test1','1'),(3,'Test2','1'),(4,'Test3','1'),(5,'Test4','1'),(6,'Test5','1'),(7,'Test6','1'),(8,'Test7','1'),(10,'Test9','1'),(11,'Test10','1'),(12,'Test11','1'),(13,'Test12','1'),(28,'Ups e Outro','2');
/*!40000 ALTER TABLE `ost_ticket__cdata` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ost_ticket`
--

DROP TABLE IF EXISTS `ost_ticket`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ost_ticket` (
  `ticket_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `ticket_pid` int(11) unsigned DEFAULT NULL,
  `number` varchar(20) DEFAULT NULL,
  `user_id` int(11) unsigned NOT NULL DEFAULT 0,
  `user_email_id` int(11) unsigned NOT NULL DEFAULT 0,
  `status_id` int(10) unsigned NOT NULL DEFAULT 0,
  `dept_id` int(10) unsigned NOT NULL DEFAULT 0,
  `sla_id` int(10) unsigned NOT NULL DEFAULT 0,
  `topic_id` int(10) unsigned NOT NULL DEFAULT 0,
  `staff_id` int(10) unsigned NOT NULL DEFAULT 0,
  `team_id` int(10) unsigned NOT NULL DEFAULT 0,
  `email_id` int(11) unsigned NOT NULL DEFAULT 0,
  `lock_id` int(11) unsigned NOT NULL DEFAULT 0,
  `flags` int(10) unsigned NOT NULL DEFAULT 0,
  `sort` int(11) unsigned NOT NULL DEFAULT 0,
  `ip_address` varchar(64) NOT NULL DEFAULT '',
  `source` enum('Web','Email','Phone','API','Other') NOT NULL DEFAULT 'Other',
  `source_extra` varchar(40) DEFAULT NULL,
  `isoverdue` tinyint(1) unsigned NOT NULL DEFAULT 0,
  `isanswered` tinyint(1) unsigned NOT NULL DEFAULT 0,
  `duedate` datetime DEFAULT NULL,
  `est_duedate` datetime DEFAULT NULL,
  `reopened` datetime DEFAULT NULL,
  `closed` datetime DEFAULT NULL,
  `lastupdate` datetime DEFAULT NULL,
  `created` datetime NOT NULL,
  `updated` datetime NOT NULL,
  `cabinet_id` int(11) DEFAULT NULL,
  `cinemometer_id` int(11) DEFAULT NULL,
  `ups_id` int(11) DEFAULT NULL,
  `router_id` int(11) DEFAULT NULL,
  `cabinet_is_broken` text DEFAULT 'Não',
  `cinemometer_is_broken` text DEFAULT 'Não',
  `ups_is_broken` text DEFAULT 'Não',
  `router_is_broken` text DEFAULT 'Não',
  `other_is_broken` text DEFAULT 'Não',
  PRIMARY KEY (`ticket_id`),
  KEY `user_id` (`user_id`),
  KEY `dept_id` (`dept_id`),
  KEY `staff_id` (`staff_id`),
  KEY `team_id` (`team_id`),
  KEY `status_id` (`status_id`),
  KEY `created` (`created`),
  KEY `closed` (`closed`),
  KEY `duedate` (`duedate`),
  KEY `topic_id` (`topic_id`),
  KEY `sla_id` (`sla_id`),
  KEY `ticket_pid` (`ticket_pid`)
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ost_ticket`
--

LOCK TABLES `ost_ticket` WRITE;
/*!40000 ALTER TABLE `ost_ticket` DISABLE KEYS */;
INSERT INTO `ost_ticket` VALUES (1,NULL,'537414',1,0,1,1,1,1,0,0,0,0,0,0,'::1','Web',NULL,1,0,NULL,'2024-05-30 10:29:00',NULL,NULL,'2024-05-28 10:29:00','2024-05-28 10:29:00','2024-06-05 16:31:05',NULL,NULL,NULL,NULL,'Não','Não','Não','Não','Não'),(2,NULL,'581681',2,0,1,1,1,2,0,0,0,0,0,0,'::1','Phone',NULL,1,0,NULL,'2024-05-30 10:49:00',NULL,NULL,'2024-05-28 10:49:00','2024-05-28 10:49:00','2024-06-05 16:31:05',NULL,NULL,NULL,NULL,'Não','Não','Não','Não','Não'),(3,NULL,'582454',2,0,1,1,1,2,0,0,0,0,0,0,'::1','Phone',NULL,1,0,NULL,'2024-05-30 10:49:21',NULL,NULL,'2024-05-28 10:49:21','2024-05-28 10:49:21','2024-06-05 16:31:05',NULL,NULL,NULL,NULL,'Não','Não','Não','Não','Não'),(4,NULL,'843723',2,0,1,1,1,2,0,0,0,0,0,0,'::1','Phone',NULL,1,0,NULL,'2024-05-30 10:49:45',NULL,NULL,'2024-05-28 10:49:45','2024-05-28 10:49:45','2024-06-05 16:31:05',NULL,NULL,NULL,NULL,'Não','Não','Não','Não','Não'),(5,NULL,'755211',2,0,1,1,1,2,0,0,0,0,0,0,'::1','Phone',NULL,1,0,NULL,'2024-05-30 10:50:06',NULL,NULL,'2024-05-28 10:50:06','2024-05-28 10:50:06','2024-06-05 16:31:06',NULL,NULL,NULL,NULL,'Não','Não','Não','Não','Não'),(6,NULL,'562773',2,0,1,1,1,2,0,0,0,0,0,0,'::1','Phone',NULL,1,0,NULL,'2024-05-30 10:50:23',NULL,NULL,'2024-05-28 10:50:23','2024-05-28 10:50:23','2024-06-05 16:31:06',NULL,NULL,NULL,NULL,'Não','Não','Não','Não','Não'),(7,NULL,'888921',2,0,1,1,1,2,0,0,0,0,0,0,'::1','Phone',NULL,1,0,NULL,'2024-05-30 10:50:41',NULL,NULL,'2024-05-28 10:50:41','2024-05-28 10:50:41','2024-06-05 16:31:06',NULL,NULL,NULL,NULL,'Não','Não','Não','Não','Não'),(8,NULL,'349145',2,0,1,1,1,2,0,0,0,0,0,0,'::1','Phone',NULL,1,0,NULL,'2024-05-30 10:51:12',NULL,NULL,'2024-05-28 10:51:12','2024-05-28 10:51:12','2024-06-05 16:31:06',NULL,NULL,NULL,NULL,'Não','Não','Não','Não','Não'),(10,NULL,'930338',2,0,1,1,1,2,0,0,0,0,0,0,'::1','Phone',NULL,1,0,NULL,'2024-05-30 10:51:48',NULL,NULL,'2024-05-28 10:51:48','2024-05-28 10:51:48','2024-06-05 16:31:06',NULL,NULL,NULL,NULL,'Não','Não','Não','Não','Não'),(11,NULL,'190580',2,0,1,1,1,2,0,0,0,0,0,0,'::1','Phone',NULL,1,0,NULL,'2024-05-30 10:52:06',NULL,NULL,'2024-05-28 10:52:06','2024-05-28 10:52:06','2024-06-05 16:31:06',NULL,NULL,NULL,NULL,'Não','Não','Não','Não','Não'),(12,NULL,'134663',2,0,1,1,1,2,0,0,0,0,0,0,'::1','Phone',NULL,1,0,NULL,'2024-05-30 10:52:22',NULL,NULL,'2024-05-28 10:52:22','2024-05-28 10:52:22','2024-06-05 16:31:06',NULL,NULL,NULL,NULL,'Não','Não','Não','Não','Não'),(13,NULL,'152245',2,0,3,1,1,2,1,0,0,0,0,0,'::1','Phone',NULL,0,0,NULL,'2024-05-30 10:52:38',NULL,'2024-05-29 12:50:19','2024-05-29 12:50:19','2024-05-28 10:52:38','2024-05-29 12:50:19',NULL,NULL,NULL,NULL,'Não','Não','Não','Não','Não'),(28,NULL,'193389',2,0,1,1,1,1,0,0,0,0,0,0,'::1','Phone',NULL,0,0,NULL,'2024-06-07 16:43:18',NULL,NULL,'2024-06-05 16:43:18','2024-06-05 16:43:18','2024-06-05 16:43:18',5,5,5,5,'Não','Não','Sim','Não','Sim');
/*!40000 ALTER TABLE `ost_ticket` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ost_form_entry`
--

DROP TABLE IF EXISTS `ost_form_entry`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ost_form_entry` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `form_id` int(11) unsigned NOT NULL,
  `object_id` int(11) unsigned DEFAULT NULL,
  `object_type` char(1) NOT NULL DEFAULT 'T',
  `sort` int(11) unsigned NOT NULL DEFAULT 1,
  `extra` text DEFAULT NULL,
  `created` datetime NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `entry_lookup` (`object_type`,`object_id`)
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ost_form_entry`
--

LOCK TABLES `ost_form_entry` WRITE;
/*!40000 ALTER TABLE `ost_form_entry` DISABLE KEYS */;
INSERT INTO `ost_form_entry` VALUES (1,4,1,'O',1,NULL,'2024-05-28 10:28:59','2024-05-28 10:28:59'),(2,3,NULL,'C',1,NULL,'2024-05-28 10:29:00','2024-05-28 10:29:00'),(3,1,1,'U',1,NULL,'2024-05-28 10:29:00','2024-05-28 10:29:00'),(4,2,1,'T',0,'{\"disable\":[]}','2024-05-28 10:29:00','2024-05-28 10:29:00'),(5,1,2,'U',1,NULL,'2024-05-28 10:48:48','2024-05-28 10:48:48'),(6,2,2,'T',0,'{\"disable\":[]}','2024-05-28 10:49:00','2024-05-28 10:49:00'),(7,2,3,'T',0,'{\"disable\":[]}','2024-05-28 10:49:21','2024-05-28 10:49:21'),(8,2,4,'T',0,'{\"disable\":[]}','2024-05-28 10:49:45','2024-05-28 10:49:45'),(9,2,5,'T',0,'{\"disable\":[]}','2024-05-28 10:50:06','2024-05-28 10:50:06'),(10,2,6,'T',0,'{\"disable\":[]}','2024-05-28 10:50:23','2024-05-28 10:50:23'),(11,2,7,'T',0,'{\"disable\":[]}','2024-05-28 10:50:41','2024-05-28 10:50:41'),(12,2,8,'T',0,'{\"disable\":[]}','2024-05-28 10:51:12','2024-05-28 10:51:12'),(14,2,10,'T',0,'{\"disable\":[]}','2024-05-28 10:51:48','2024-05-28 10:51:48'),(15,2,11,'T',0,'{\"disable\":[]}','2024-05-28 10:52:06','2024-05-28 10:52:06'),(16,2,12,'T',0,'{\"disable\":[]}','2024-05-28 10:52:22','2024-05-28 10:52:22'),(17,2,13,'T',0,'{\"disable\":[]}','2024-05-28 10:52:38','2024-05-28 10:52:38'),(32,2,28,'T',0,'{\"disable\":[]}','2024-06-05 16:43:18','2024-06-05 16:43:18');
/*!40000 ALTER TABLE `ost_form_entry` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ost_form_entry_values`
--

DROP TABLE IF EXISTS `ost_form_entry_values`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ost_form_entry_values` (
  `entry_id` int(11) unsigned NOT NULL,
  `field_id` int(11) unsigned NOT NULL,
  `value` text DEFAULT NULL,
  `value_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`entry_id`,`field_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ost_form_entry_values`
--

LOCK TABLES `ost_form_entry_values` WRITE;
/*!40000 ALTER TABLE `ost_form_entry_values` DISABLE KEYS */;
INSERT INTO `ost_form_entry_values` VALUES (2,23,'ISEL',NULL),(2,24,NULL,NULL),(2,25,NULL,NULL),(2,26,NULL,NULL),(3,3,NULL,NULL),(3,4,NULL,NULL),(4,20,'osTicket Installed!',NULL),(4,22,NULL,NULL),(5,3,NULL,NULL),(5,4,NULL,NULL),(6,20,'Test1',NULL),(6,22,NULL,1),(7,20,'Test2',NULL),(7,22,NULL,1),(8,20,'Test3',NULL),(8,22,NULL,1),(9,20,'Test4',NULL),(9,22,NULL,1),(10,20,'Test5',NULL),(10,22,NULL,1),(11,20,'Test6',NULL),(11,22,NULL,1),(12,20,'Test7',NULL),(12,22,NULL,1),(14,20,'Test9',NULL),(14,22,NULL,1),(15,20,'Test10',NULL),(15,22,NULL,1),(16,20,'Test11',NULL),(16,22,NULL,1),(17,20,'Test12',NULL),(17,22,NULL,1),(32,20,'Ups e Outro',NULL),(32,22,NULL,2);
/*!40000 ALTER TABLE `ost_form_entry_values` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ost_thread`
--

DROP TABLE IF EXISTS `ost_thread`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ost_thread` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `object_id` int(11) unsigned NOT NULL,
  `object_type` char(1) NOT NULL,
  `extra` text DEFAULT NULL,
  `lastresponse` datetime DEFAULT NULL,
  `lastmessage` datetime DEFAULT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `object_id` (`object_id`),
  KEY `object_type` (`object_type`)
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ost_thread`
--

LOCK TABLES `ost_thread` WRITE;
/*!40000 ALTER TABLE `ost_thread` DISABLE KEYS */;
INSERT INTO `ost_thread` VALUES (1,1,'T',NULL,NULL,'2024-05-28 10:29:00','2024-05-28 10:29:00'),(2,2,'T',NULL,NULL,'2024-05-28 10:49:00','2024-05-28 10:49:00'),(3,3,'T',NULL,NULL,'2024-05-28 10:49:21','2024-05-28 10:49:21'),(4,4,'T',NULL,NULL,'2024-05-28 10:49:45','2024-05-28 10:49:45'),(5,5,'T',NULL,NULL,'2024-05-28 10:50:06','2024-05-28 10:50:06'),(6,6,'T',NULL,NULL,'2024-05-28 10:50:23','2024-05-28 10:50:23'),(7,7,'T',NULL,NULL,'2024-05-28 10:50:41','2024-05-28 10:50:41'),(8,8,'T',NULL,NULL,'2024-05-28 10:51:12','2024-05-28 10:51:12'),(10,10,'T',NULL,NULL,'2024-05-28 10:51:48','2024-05-28 10:51:48'),(11,11,'T',NULL,NULL,'2024-05-28 10:52:06','2024-05-28 10:52:06'),(12,12,'T',NULL,NULL,'2024-05-28 10:52:22','2024-05-28 10:52:22'),(13,13,'T',NULL,NULL,'2024-05-28 10:52:38','2024-05-28 10:52:38'),(28,28,'T',NULL,NULL,'2024-06-05 16:43:18','2024-06-05 16:43:18');
/*!40000 ALTER TABLE `ost_thread` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ost_thread_entry`
--

DROP TABLE IF EXISTS `ost_thread_entry`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ost_thread_entry` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `pid` int(11) unsigned NOT NULL DEFAULT 0,
  `thread_id` int(11) unsigned NOT NULL DEFAULT 0,
  `staff_id` int(11) unsigned NOT NULL DEFAULT 0,
  `user_id` int(11) unsigned NOT NULL DEFAULT 0,
  `type` char(1) NOT NULL DEFAULT '',
  `flags` int(11) unsigned NOT NULL DEFAULT 0,
  `poster` varchar(128) NOT NULL DEFAULT '',
  `editor` int(10) unsigned DEFAULT NULL,
  `editor_type` char(1) DEFAULT NULL,
  `source` varchar(32) NOT NULL DEFAULT '',
  `title` varchar(255) DEFAULT NULL,
  `body` text NOT NULL,
  `format` varchar(16) NOT NULL DEFAULT 'html',
  `ip_address` varchar(64) NOT NULL DEFAULT '',
  `extra` text DEFAULT NULL,
  `recipients` text DEFAULT NULL,
  `created` datetime NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `pid` (`pid`),
  KEY `thread_id` (`thread_id`),
  KEY `staff_id` (`staff_id`),
  KEY `type` (`type`)
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ost_thread_entry`
--

LOCK TABLES `ost_thread_entry` WRITE;
/*!40000 ALTER TABLE `ost_thread_entry` DISABLE KEYS */;
INSERT INTO `ost_thread_entry` VALUES (1,0,1,0,1,'M',65,'osTicket Team',NULL,NULL,'Web','osTicket Installed!',' <p>Thank you for choosing osTicket. </p> <p>Please make sure you join the <a href=\"https://forum.osticket.com\">osTicket forums</a> and our <a href=\"https://osticket.com\">mailing list</a> to stay up to date on the latest news, security alerts and updates. The osTicket forums are also a great place to get assistance, guidance, tips, and help from other osTicket users. In addition to the forums, the <a href=\"https://docs.osticket.com\">osTicket Docs</a> provides a useful collection of educational materials, documentation, and notes from the community. We welcome your contributions to the osTicket community. </p> <p>If you are looking for a greater level of support, we provide professional services and commercial support with guaranteed response times, and access to the core development team. We can also help customize osTicket or even add new features to the system to meet your unique needs. </p> <p>If the idea of managing and upgrading this osTicket installation is daunting, you can try osTicket as a hosted service at <a href=\"https://supportsystem.com\">https://supportsystem.com/</a> -- no installation required and we can import your data! With SupportSystem\'s turnkey infrastructure, you get osTicket at its best, leaving you free to focus on your customers without the burden of making sure the application is stable, maintained, and secure. </p> <p>Cheers, </p> <p>-<br /> osTicket Team - https://osticket.com/ </p> <p><strong>PS.</strong> Don\'t just make customers happy, make happy customers! </p>','html','::1',NULL,NULL,'2024-05-28 10:29:00','0000-00-00 00:00:00'),(2,0,2,0,2,'M',577,'João Silva',NULL,NULL,'Phone',NULL,'<p>Test1</p>','html','::1',NULL,'{\"to\":{\"2\":\"Jo\\u00e3o Silva <joaofps2001@hotmail.com>\"}}','2024-05-28 10:49:00','0000-00-00 00:00:00'),(3,0,3,0,2,'M',577,'João Silva',NULL,NULL,'Phone',NULL,'<p>Test2</p>','html','::1',NULL,'{\"to\":{\"2\":\"Jo\\u00e3o Silva <joaofps2001@hotmail.com>\"}}','2024-05-28 10:49:21','0000-00-00 00:00:00'),(4,0,4,0,2,'M',577,'João Silva',NULL,NULL,'Phone',NULL,'<p>Test3</p>','html','::1',NULL,'{\"to\":{\"2\":\"Jo\\u00e3o Silva <joaofps2001@hotmail.com>\"}}','2024-05-28 10:49:45','0000-00-00 00:00:00'),(5,0,5,0,2,'M',577,'João Silva',NULL,NULL,'Phone',NULL,'<p>Test4</p>','html','::1',NULL,'{\"to\":{\"2\":\"Jo\\u00e3o Silva <joaofps2001@hotmail.com>\"}}','2024-05-28 10:50:06','0000-00-00 00:00:00'),(6,0,6,0,2,'M',577,'João Silva',NULL,NULL,'Phone',NULL,'<p>Test5</p>','html','::1',NULL,'{\"to\":{\"2\":\"Jo\\u00e3o Silva <joaofps2001@hotmail.com>\"}}','2024-05-28 10:50:23','0000-00-00 00:00:00'),(7,0,7,0,2,'M',577,'João Silva',NULL,NULL,'Phone',NULL,'<p>Test6</p>','html','::1',NULL,'{\"to\":{\"2\":\"Jo\\u00e3o Silva <joaofps2001@hotmail.com>\"}}','2024-05-28 10:50:41','0000-00-00 00:00:00'),(8,0,8,0,2,'M',577,'João Silva',NULL,NULL,'Phone',NULL,'<p>Test7</p>','html','::1',NULL,'{\"to\":{\"2\":\"Jo\\u00e3o Silva <joaofps2001@hotmail.com>\"}}','2024-05-28 10:51:12','0000-00-00 00:00:00'),(10,0,10,0,2,'M',577,'João Silva',NULL,NULL,'Phone',NULL,'<p>Test9</p>','html','::1',NULL,'{\"to\":{\"2\":\"Jo\\u00e3o Silva <joaofps2001@hotmail.com>\"}}','2024-05-28 10:51:48','0000-00-00 00:00:00'),(11,0,11,0,2,'M',577,'João Silva',NULL,NULL,'Phone',NULL,'<p>Test10</p>','html','::1',NULL,'{\"to\":{\"2\":\"Jo\\u00e3o Silva <joaofps2001@hotmail.com>\"}}','2024-05-28 10:52:06','0000-00-00 00:00:00'),(12,0,12,0,2,'M',577,'João Silva',NULL,NULL,'Phone',NULL,'<p>Test11</p>','html','::1',NULL,'{\"to\":{\"2\":\"Jo\\u00e3o Silva <joaofps2001@hotmail.com>\"}}','2024-05-28 10:52:22','0000-00-00 00:00:00'),(13,0,13,0,2,'M',577,'João Silva',NULL,NULL,'Phone',NULL,'<p>Test12</p>','html','::1',NULL,'{\"to\":{\"2\":\"Jo\\u00e3o Silva <joaofps2001@hotmail.com>\"}}','2024-05-28 10:52:38','0000-00-00 00:00:00'),(28,0,28,0,2,'M',577,'João Silva',NULL,NULL,'Phone',NULL,'<p>asd</p>','html','::1',NULL,'{\"to\":{\"2\":\"Jo\\u00e3o Silva <joaofps2001@hotmail.com>\"}}','2024-06-05 16:43:18','0000-00-00 00:00:00');
/*!40000 ALTER TABLE `ost_thread_entry` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ost_thread_event`
--

DROP TABLE IF EXISTS `ost_thread_event`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ost_thread_event` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `thread_id` int(11) unsigned NOT NULL DEFAULT 0,
  `thread_type` char(1) NOT NULL DEFAULT '',
  `event_id` int(11) unsigned DEFAULT NULL,
  `staff_id` int(11) unsigned NOT NULL,
  `team_id` int(11) unsigned NOT NULL,
  `dept_id` int(11) unsigned NOT NULL,
  `topic_id` int(11) unsigned NOT NULL,
  `data` varchar(1024) DEFAULT NULL COMMENT 'Encoded differences',
  `username` varchar(128) NOT NULL DEFAULT 'SYSTEM',
  `uid` int(11) unsigned DEFAULT NULL,
  `uid_type` char(1) NOT NULL DEFAULT 'S',
  `annulled` tinyint(1) unsigned NOT NULL DEFAULT 0,
  `timestamp` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `ticket_state` (`thread_id`,`event_id`,`timestamp`),
  KEY `ticket_stats` (`timestamp`,`event_id`)
) ENGINE=InnoDB AUTO_INCREMENT=45 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ost_thread_event`
--

LOCK TABLES `ost_thread_event` WRITE;
/*!40000 ALTER TABLE `ost_thread_event` DISABLE KEYS */;
INSERT INTO `ost_thread_event` VALUES (1,1,'T',1,0,0,1,1,NULL,'SYSTEM',1,'U',0,'2024-05-28 10:29:00'),(2,2,'T',1,1,0,1,2,NULL,'joaofps2001',1,'S',0,'2024-05-28 10:49:00'),(3,3,'T',1,1,0,1,2,NULL,'joaofps2001',1,'S',0,'2024-05-28 10:49:21'),(4,4,'T',1,1,0,1,2,NULL,'joaofps2001',1,'S',0,'2024-05-28 10:49:45'),(5,5,'T',1,1,0,1,2,NULL,'joaofps2001',1,'S',0,'2024-05-28 10:50:06'),(6,6,'T',1,1,0,1,2,NULL,'joaofps2001',1,'S',0,'2024-05-28 10:50:23'),(7,7,'T',1,1,0,1,2,NULL,'joaofps2001',1,'S',0,'2024-05-28 10:50:41'),(8,8,'T',1,1,0,1,2,NULL,'joaofps2001',1,'S',0,'2024-05-28 10:51:12'),(9,0,'T',1,1,0,1,2,NULL,'joaofps2001',1,'S',0,'2024-05-28 10:51:30'),(10,10,'T',1,1,0,1,2,NULL,'joaofps2001',1,'S',0,'2024-05-28 10:51:48'),(11,11,'T',1,1,0,1,2,NULL,'joaofps2001',1,'S',0,'2024-05-28 10:52:06'),(12,12,'T',1,1,0,1,2,NULL,'joaofps2001',1,'S',0,'2024-05-28 10:52:22'),(13,13,'T',1,1,0,1,2,NULL,'joaofps2001',1,'S',0,'2024-05-28 10:52:38'),(28,1,'T',8,0,0,1,1,NULL,'SYSTEM',NULL,'S',0,'2024-06-05 16:31:05'),(29,2,'T',8,0,0,1,2,NULL,'SYSTEM',NULL,'S',0,'2024-06-05 16:31:05'),(30,3,'T',8,0,0,1,2,NULL,'SYSTEM',NULL,'S',0,'2024-06-05 16:31:05'),(31,4,'T',8,0,0,1,2,NULL,'SYSTEM',NULL,'S',0,'2024-06-05 16:31:06'),(32,5,'T',8,0,0,1,2,NULL,'SYSTEM',NULL,'S',0,'2024-06-05 16:31:06'),(33,6,'T',8,0,0,1,2,NULL,'SYSTEM',NULL,'S',0,'2024-06-05 16:31:06'),(34,7,'T',8,0,0,1,2,NULL,'SYSTEM',NULL,'S',0,'2024-06-05 16:31:06'),(35,8,'T',8,0,0,1,2,NULL,'SYSTEM',NULL,'S',0,'2024-06-05 16:31:06'),(36,10,'T',8,0,0,1,2,NULL,'SYSTEM',NULL,'S',0,'2024-06-05 16:31:06'),(37,11,'T',8,0,0,1,2,NULL,'SYSTEM',NULL,'S',0,'2024-06-05 16:31:06'),(38,12,'T',8,0,0,1,2,NULL,'SYSTEM',NULL,'S',0,'2024-06-05 16:31:06'),(44,28,'T',1,1,0,1,1,NULL,'joaofps2001',1,'S',0,'2024-06-05 16:43:18');
/*!40000 ALTER TABLE `ost_thread_event` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ost__search`
--

DROP TABLE IF EXISTS `ost__search`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ost__search` (
  `object_type` varchar(8) NOT NULL,
  `object_id` int(11) unsigned NOT NULL,
  `title` text DEFAULT NULL,
  `content` text DEFAULT NULL,
  PRIMARY KEY (`object_type`,`object_id`),
  FULLTEXT KEY `search` (`title`,`content`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ost__search`
--

LOCK TABLES `ost__search` WRITE;
/*!40000 ALTER TABLE `ost__search` DISABLE KEYS */;
INSERT INTO `ost__search` VALUES ('H',1,'osTicket Installed!','Thank you for choosing osTicket. Please make sure you join the osTicket forums and our mailing list to stay up to date on the latest news, security alerts and updates. The osTicket forums are also a great place to get assistance, guidance, tips, and help from other osTicket users. In addition to the forums, the osTicket Docs provides a useful collection of educational materials, documentation, and notes from the community. We welcome your contributions to the osTicket community. If you are looking for a greater level of support, we provide professional services and commercial support with guaranteed response times, and access to the core development team. We can also help customize osTicket or even add new features to the system to meet your unique needs. If the idea of managing and upgrading this osTicket installation is daunting, you can try osTicket as a hosted service at https://supportsystem.com/ -- no installation required and we can import your data! With SupportSystem\'s turnkey infrastructure, you get osTicket at its best, leaving you free to focus on your customers without the burden of making sure the application is stable, maintained, and secure. Cheers, - osTicket Team - https://osticket.com/ PS. Don\'t just make customers happy, make happy customers!'),('H',2,'','Test1'),('H',3,'','Test2'),('H',4,'','Test3'),('H',5,'','Test4'),('H',6,'','Test5'),('H',7,'','Test6'),('H',8,'','Test7'),('H',10,'','Test9'),('H',11,'','Test10'),('H',12,'','Test11'),('H',13,'','Test12'),('H',22,'','asdad'),('H',28,'','asd'),('O',1,'osTicket',''),('T',1,'537414 osTicket Installed!','osTicket Installed!'),('T',2,'581681 Test1','Test1'),('T',3,'582454 Test2','Test2'),('T',4,'843723 Test3','Test3'),('T',5,'755211 Test4','Test4'),('T',6,'562773 Test5','Test5'),('T',7,'888921 Test6','Test6'),('T',8,'349145 Test7','Test7'),('T',10,'930338 Test9','Test9'),('T',11,'190580 Test10','Test10'),('T',12,'134663 Test11','Test11'),('T',13,'152245 Test12','Test12'),('T',22,'815470 Router e UPS','Router e UPS'),('T',28,'193389 Ups e Outro','Ups e Outro'),('U',1,'osTicket Team','feedback@osticket.com'),('U',2,'João Silva',' joaofps2001@hotmail.com\njoaofps2001@hotmail.com');
/*!40000 ALTER TABLE `ost__search` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2024-06-05 16:45:03
