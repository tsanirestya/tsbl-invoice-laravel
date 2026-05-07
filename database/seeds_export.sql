-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: tsbl_invoice
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Dumping data for table `settings`
--

LOCK TABLES `settings` WRITE;
/*!40000 ALTER TABLE `settings` DISABLE KEYS */;
INSERT INTO `settings` VALUES (1,'company_name','Trans Studio Bali','Company Name','2026-05-05 13:04:45'),(2,'company_address',NULL,'Company Address','2026-05-05 13:04:45'),(3,'company_phone',NULL,'Company Phone','2026-05-05 13:04:45'),(4,'company_email',NULL,'Company Email','2026-05-05 13:04:45'),(5,'company_npwp',NULL,'NPWP Perusahaan','2026-05-05 13:04:45'),(6,'invoice_prefix','INV','Invoice Prefix','2026-05-05 13:04:45'),(7,'default_due_days','14','Default Due Days','2026-05-05 13:04:45'),(8,'bank_name','Bank Mandiri','Bank Name','2026-05-05 13:04:45'),(9,'bank_account_no','1011010101010','Bank Account No','2026-05-05 13:04:45'),(10,'bank_account_name','Trans Studio Bali','Bank Account Name','2026-05-05 13:04:45'),(11,'invoice_notes','1. Please transfer payment to :\r\n\r\nTAMAN HIBURAN BALI\r\nBank Mega\r\nAcc No : 010740016000334\r\nNPWP : 39.927.954.6-014.000\r\n2. Email : financetransstudiobali@gmail.com\r\n\r\n*Official Receipt can be requested upon payment have been\r\ncredited into our account\r\n*No Refund Policy','Invoice Notes','2026-05-05 13:04:45'),(12,'terms_conditions',NULL,'Terms & Conditions','2026-05-05 13:04:45'),(13,'logo_path','uploads/settings/69fa97e8bbd90_1778030568.png','Company Logo','2026-05-05 13:04:45'),(14,'favicon_path','uploads/settings/69fa97ddea4bf_1778030557.png',NULL,NULL),(15,'navbar_logo_path','uploads/settings/69fa97ddef3f0_1778030557.png',NULL,NULL),(16,'deposit_low_threshold','1000000',NULL,NULL);
/*!40000 ALTER TABLE `settings` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-05-07 10:21:34
