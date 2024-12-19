-- MariaDB dump 10.19  Distrib 10.4.28-MariaDB, for osx10.10 (x86_64)
--
-- Host: localhost    Database: kilburnazon_db
-- ------------------------------------------------------
-- Server version	10.4.28-MariaDB

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
-- Table structure for table `AuditTermination`
--

DROP TABLE IF EXISTS `AuditTermination`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `AuditTermination` (
  `log_id` int(11) NOT NULL AUTO_INCREMENT,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `termination_date` datetime NOT NULL DEFAULT current_timestamp(),
  `deleted_by_employee_id` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`log_id`),
  KEY `audittermination_ibfk_2` (`deleted_by_employee_id`)
) ENGINE=InnoDB AUTO_INCREMENT=135 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `AuditTermination`
--

LOCK TABLES `AuditTermination` WRITE;
/*!40000 ALTER TABLE `AuditTermination` DISABLE KEYS */;
INSERT INTO `AuditTermination` VALUES (129,'malissia','Johnsson','2024-11-28 17:42:31','9'),(132,'Maria','Jones','2024-11-28 20:52:19','9'),(133,'Maria','Jones','2024-11-28 20:52:44',NULL),(134,'Maria','Jones','2024-11-28 20:52:59','9');
/*!40000 ALTER TABLE `AuditTermination` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Customer`
--

DROP TABLE IF EXISTS `Customer`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `Customer` (
  `customer_id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_name` varchar(255) NOT NULL,
  `customer_address` varchar(255) DEFAULT NULL,
  `customer_email_address` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`customer_id`),
  UNIQUE KEY `customer_email_address` (`customer_email_address`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Customer`
--

LOCK TABLES `Customer` WRITE;
/*!40000 ALTER TABLE `Customer` DISABLE KEYS */;
INSERT INTO `Customer` VALUES (1,'John Doe','123 Main St','john.doe@example.com'),(2,'David Green','789 Pine St, Liverpool','david.green@example.com'),(3,'Ella White','321 Oak St, Leeds','ella.white@example.com');
/*!40000 ALTER TABLE `Customer` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Delivery`
--

DROP TABLE IF EXISTS `Delivery`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `Delivery` (
  `delivery_id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) DEFAULT NULL,
  `delivery_run_id` int(11) DEFAULT NULL,
  `delivery_status` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`delivery_id`),
  KEY `order_id` (`order_id`),
  KEY `delivery_run_id` (`delivery_run_id`),
  CONSTRAINT `delivery_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `Order` (`order_id`),
  CONSTRAINT `delivery_ibfk_2` FOREIGN KEY (`delivery_run_id`) REFERENCES `DeliveryRun` (`delivery_run_id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Delivery`
--

LOCK TABLES `Delivery` WRITE;
/*!40000 ALTER TABLE `Delivery` DISABLE KEYS */;
/*!40000 ALTER TABLE `Delivery` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `DeliveryRun`
--

DROP TABLE IF EXISTS `DeliveryRun`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `DeliveryRun` (
  `delivery_run_id` int(11) NOT NULL AUTO_INCREMENT,
  `region_id` int(11) DEFAULT NULL,
  `delivery_driver_id` int(11) DEFAULT NULL,
  `vehicle_id` int(11) DEFAULT NULL,
  `date` date DEFAULT NULL,
  PRIMARY KEY (`delivery_run_id`),
  KEY `region_id` (`region_id`),
  KEY `delivery_driver_id` (`delivery_driver_id`),
  KEY `vehicle_id` (`vehicle_id`),
  CONSTRAINT `deliveryrun_ibfk_1` FOREIGN KEY (`region_id`) REFERENCES `Region` (`region_id`),
  CONSTRAINT `deliveryrun_ibfk_2` FOREIGN KEY (`delivery_driver_id`) REFERENCES `Employee` (`employee_id`),
  CONSTRAINT `deliveryrun_ibfk_3` FOREIGN KEY (`vehicle_id`) REFERENCES `Vehicle` (`vehicle_id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `DeliveryRun`
--

LOCK TABLES `DeliveryRun` WRITE;
/*!40000 ALTER TABLE `DeliveryRun` DISABLE KEYS */;
INSERT INTO `DeliveryRun` VALUES (5,1,9,1,'2024-11-01'),(6,2,10,2,'2024-11-02'),(7,1,9,1,'2024-11-01'),(8,2,10,2,'2024-11-02');
/*!40000 ALTER TABLE `DeliveryRun` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Department`
--

DROP TABLE IF EXISTS `Department`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `Department` (
  `department_id` int(11) NOT NULL AUTO_INCREMENT,
  `department_head_id` int(11) DEFAULT NULL,
  `department_name` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`department_id`),
  KEY `fk_department_head` (`department_head_id`),
  CONSTRAINT `fk_department_head` FOREIGN KEY (`department_head_id`) REFERENCES `Employee` (`employee_id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Department`
--

LOCK TABLES `Department` WRITE;
/*!40000 ALTER TABLE `Department` DISABLE KEYS */;
INSERT INTO `Department` VALUES (1,9,'Human Resources'),(2,NULL,'Information Technology'),(3,NULL,'Finance'),(4,NULL,'Logistics');
/*!40000 ALTER TABLE `Department` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `EmergencyContact`
--

DROP TABLE IF EXISTS `EmergencyContact`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `EmergencyContact` (
  `contact_id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) DEFAULT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `relationship` varchar(50) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`contact_id`),
  KEY `employee_id` (`employee_id`),
  CONSTRAINT `emergencycontact_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `Employee` (`employee_id`)
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `EmergencyContact`
--

LOCK TABLES `EmergencyContact` WRITE;
/*!40000 ALTER TABLE `EmergencyContact` DISABLE KEYS */;
INSERT INTO `EmergencyContact` VALUES (17,11,'Bob','Brown','Father','07777777777'),(18,9,'Mia','Johnson','Mother','09999999999'),(19,10,'Alex','Smith','Father','01234567890'),(20,49,'Marylynne','Jonsson','Mother','07437 831717'),(21,52,'Marylynne','Jones','Mother','07437 831717');
/*!40000 ALTER TABLE `EmergencyContact` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Employee`
--

DROP TABLE IF EXISTS `Employee`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `Employee` (
  `employee_id` int(11) NOT NULL AUTO_INCREMENT,
  `position_id` int(11) DEFAULT NULL,
  `office_location_id` int(11) DEFAULT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `salary_amount` decimal(10,2) DEFAULT NULL,
  `email_address` varchar(255) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `home_street_address` varchar(255) DEFAULT NULL,
  `home_city` varchar(100) DEFAULT NULL,
  `hire_date` date DEFAULT NULL,
  `employment_contract_type` enum('Full-Time','Part-Time','') NOT NULL,
  `national_insurance_number` varchar(20) DEFAULT NULL,
  `is_deleted` tinyint(1) DEFAULT 0,
  `deleted_at` datetime DEFAULT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`employee_id`),
  KEY `position_id` (`position_id`),
  KEY `office_location_id` (`office_location_id`),
  CONSTRAINT `employee_ibfk_1` FOREIGN KEY (`position_id`) REFERENCES `Position` (`position_id`),
  CONSTRAINT `employee_ibfk_2` FOREIGN KEY (`office_location_id`) REFERENCES `Location` (`location_id`)
) ENGINE=InnoDB AUTO_INCREMENT=53 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Employee`
--

LOCK TABLES `Employee` WRITE;
/*!40000 ALTER TABLE `Employee` DISABLE KEYS */;
INSERT INTO `Employee` VALUES (9,2,2,'Alice','Johnson',52500.00,'alice.johnson@example.com','2024-10-30','das','dasdasdasd','2024-11-22','Part-Time','AB123456A',0,NULL,'../uploads/blank.png'),(10,5,2,'Bob','Smith',54337.50,'bob.smith@example.com','2024-11-01','dasds','dasd','2019-03-15','Full-Time','NI987654B',0,NULL,'../uploads/blank.png'),(11,9,2,'Charlied','Brown',31500.00,'charlie.brown@example.com','2024-11-09','dasfsaf','fasfasfas','2021-07-01','Part-Time','NI654321C',0,NULL,'../uploads/blank.png'),(48,10,1,'malissia','Johnsson',10000.00,'mj@email.com','1990-01-28','grover alley','london','2024-11-12','Full-Time','AB123456B',1,'2024-11-28 17:42:31','../uploads/blank.png'),(49,9,1,'Mary','Brown',39000.00,'asddasdas@dsa','2006-01-28','29416 Grover Alley, London','London','2024-11-28','Part-Time','AB123456B',0,NULL,NULL),(51,10,1,'Malissia','Jonsson',39000.00,'dfasf@asd.com','1998-11-12','29416 Grover Alley','London','2024-11-28','Full-Time','AB123456B',0,NULL,'../uploads/6748d234596cf8.72129728.png'),(52,NULL,NULL,'','',NULL,'',NULL,'','',NULL,'','',1,'2020-11-28 20:52:19','../uploads/6748d2bed0e4c2.68700491.png');
/*!40000 ALTER TABLE `Employee` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `after_employee_insert` 
AFTER INSERT ON `Employee` 
FOR EACH ROW 
BEGIN
    -- Insert initial leave balances for the new employee across all leave types
    INSERT INTO `LeaveBalances` (`employee_id`, `leave_type_id`, `balance`)
    SELECT NEW.`employee_id`, lt.`leave_type_id`, 20
    FROM `LeaveTypes` lt;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER log_employee_soft_deletion
BEFORE UPDATE ON Employee
FOR EACH ROW
BEGIN
    IF NEW.is_deleted = 1 THEN
        INSERT INTO AuditTermination (first_name, last_name, termination_date, deleted_by_employee_id)
        VALUES (OLD.first_name, OLD.last_name, NOW(), @admin_id);
    END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER delete_user_on_employee_is_deleted
AFTER UPDATE ON Employee
FOR EACH ROW
BEGIN
    IF NEW.is_deleted = 1 THEN
        DELETE FROM users WHERE employee_id = NEW.employee_id;
    END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `EmployeeRegionAssignment`
--

DROP TABLE IF EXISTS `EmployeeRegionAssignment`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `EmployeeRegionAssignment` (
  `employee_id` int(11) NOT NULL,
  `region_id` int(11) NOT NULL,
  PRIMARY KEY (`employee_id`,`region_id`),
  KEY `region_id` (`region_id`),
  CONSTRAINT `employeeregionassignment_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `Employee` (`employee_id`),
  CONSTRAINT `employeeregionassignment_ibfk_2` FOREIGN KEY (`region_id`) REFERENCES `Region` (`region_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `EmployeeRegionAssignment`
--

LOCK TABLES `EmployeeRegionAssignment` WRITE;
/*!40000 ALTER TABLE `EmployeeRegionAssignment` DISABLE KEYS */;
INSERT INTO `EmployeeRegionAssignment` VALUES (9,1),(10,2),(11,3);
/*!40000 ALTER TABLE `EmployeeRegionAssignment` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Inventory`
--

DROP TABLE IF EXISTS `Inventory`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `Inventory` (
  `product_id` int(11) NOT NULL,
  `location_id` int(11) NOT NULL,
  `number_available_in_stock` int(11) DEFAULT 0,
  PRIMARY KEY (`product_id`,`location_id`),
  KEY `location_id` (`location_id`),
  CONSTRAINT `inventory_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `Product` (`product_id`),
  CONSTRAINT `inventory_ibfk_2` FOREIGN KEY (`location_id`) REFERENCES `Location` (`location_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Inventory`
--

LOCK TABLES `Inventory` WRITE;
/*!40000 ALTER TABLE `Inventory` DISABLE KEYS */;
INSERT INTO `Inventory` VALUES (1,2,50),(2,3,30);
/*!40000 ALTER TABLE `Inventory` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `LeaveBalances`
--

DROP TABLE IF EXISTS `LeaveBalances`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `LeaveBalances` (
  `balance_id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) NOT NULL,
  `leave_type_id` int(11) NOT NULL,
  `balance` float NOT NULL DEFAULT 0,
  PRIMARY KEY (`balance_id`),
  KEY `employee_id` (`employee_id`),
  KEY `leave_type_id` (`leave_type_id`),
  CONSTRAINT `leavebalances_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `Employee` (`employee_id`) ON DELETE CASCADE,
  CONSTRAINT `leavebalances_ibfk_2` FOREIGN KEY (`leave_type_id`) REFERENCES `LeaveTypes` (`leave_type_id`) ON DELETE CASCADE,
  CONSTRAINT `leavebalances_ibfk_3` FOREIGN KEY (`employee_id`) REFERENCES `Employee` (`employee_id`) ON DELETE CASCADE,
  CONSTRAINT `leavebalances_ibfk_4` FOREIGN KEY (`leave_type_id`) REFERENCES `LeaveTypes` (`leave_type_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=76 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `LeaveBalances`
--

LOCK TABLES `LeaveBalances` WRITE;
/*!40000 ALTER TABLE `LeaveBalances` DISABLE KEYS */;
INSERT INTO `LeaveBalances` VALUES (1,10,1,20),(2,9,1,20),(3,11,1,15),(4,10,2,16),(5,9,2,20),(6,11,2,14),(7,10,3,20),(8,9,3,20),(9,11,3,11),(64,49,1,20),(65,49,2,20),(66,49,3,20),(70,51,1,20),(71,51,2,20),(72,51,3,20);
/*!40000 ALTER TABLE `LeaveBalances` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `LeaveRequests`
--

DROP TABLE IF EXISTS `LeaveRequests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `LeaveRequests` (
  `request_id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) NOT NULL,
  `leave_type_id` int(11) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `comments` text DEFAULT NULL,
  `status` enum('Pending','Approved','Denied') DEFAULT 'Pending',
  `manager_id` int(11) DEFAULT NULL,
  `request_date` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`request_id`),
  KEY `employee_id` (`employee_id`),
  KEY `leave_type_id` (`leave_type_id`),
  KEY `fk_manager` (`manager_id`),
  CONSTRAINT `fk_manager` FOREIGN KEY (`manager_id`) REFERENCES `Employee` (`employee_id`) ON DELETE SET NULL,
  CONSTRAINT `leaverequests_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `Employee` (`employee_id`) ON DELETE CASCADE,
  CONSTRAINT `leaverequests_ibfk_2` FOREIGN KEY (`leave_type_id`) REFERENCES `LeaveTypes` (`leave_type_id`) ON DELETE CASCADE,
  CONSTRAINT `leaverequests_ibfk_3` FOREIGN KEY (`manager_id`) REFERENCES `Employee` (`employee_id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `LeaveRequests`
--

LOCK TABLES `LeaveRequests` WRITE;
/*!40000 ALTER TABLE `LeaveRequests` DISABLE KEYS */;
INSERT INTO `LeaveRequests` VALUES (1,11,3,'2024-12-01','2024-12-04','dasdfas','Approved',9,'2024-11-24 01:26:16'),(2,11,1,'2024-11-25','2024-11-27','dasdasd','Approved',9,'2024-11-24 02:02:18'),(3,10,2,'2024-11-14','2024-11-17','dasd','Approved',9,'2024-11-24 05:27:19'),(4,10,3,'2024-11-18','2024-12-07','fasfas','Denied',9,'2024-11-24 14:37:24'),(5,11,3,'2024-11-26','2024-11-28','fsafasf','Approved',9,'2024-11-25 15:00:50'),(6,11,2,'2024-11-29','2024-12-02','','Approved',9,'2024-11-27 17:06:44'),(7,11,1,'2024-12-04','2024-12-05','dasfas','Approved',9,'2024-11-27 17:15:52'),(8,11,2,'2024-12-03','2024-12-04','fasfas','Denied',9,'2024-11-27 17:19:07'),(9,11,3,'2024-12-07','2024-12-08','fsafasf','Approved',9,'2024-11-27 17:19:16'),(10,11,2,'2024-12-06','2024-12-07','fsdaf','Denied',9,'2024-11-27 22:45:40'),(12,11,2,'2024-12-12','2024-12-13','vacation leave pls','Approved',9,'2024-11-28 14:05:02');
/*!40000 ALTER TABLE `LeaveRequests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `LeaveTypes`
--

DROP TABLE IF EXISTS `LeaveTypes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `LeaveTypes` (
  `leave_type_id` int(11) NOT NULL AUTO_INCREMENT,
  `leave_type_name` varchar(50) NOT NULL,
  PRIMARY KEY (`leave_type_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `LeaveTypes`
--

LOCK TABLES `LeaveTypes` WRITE;
/*!40000 ALTER TABLE `LeaveTypes` DISABLE KEYS */;
INSERT INTO `LeaveTypes` VALUES (1,'Sick Leave'),(2,'Vacation'),(3,'Personal Leave');
/*!40000 ALTER TABLE `LeaveTypes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Location`
--

DROP TABLE IF EXISTS `Location`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `Location` (
  `location_id` int(11) NOT NULL AUTO_INCREMENT,
  `location_name` varchar(255) NOT NULL,
  `location_city` varchar(100) DEFAULT NULL,
  `location_address` varchar(255) DEFAULT NULL,
  `location_type` varchar(50) DEFAULT NULL,
  `contact_phone` varchar(20) DEFAULT NULL,
  `location_description` text DEFAULT NULL,
  PRIMARY KEY (`location_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Location`
--

LOCK TABLES `Location` WRITE;
/*!40000 ALTER TABLE `Location` DISABLE KEYS */;
INSERT INTO `Location` VALUES (1,'Head Office','Manchester','123 Main St','Office','0161-123-4567','Main company headquarters'),(2,'Warehouse 1','London','45 King St','Warehouse','020-123-4567','Primary distribution center'),(3,'Warehouse 2','Birmingham','78 Queen St','Warehouse','0121-123-4567','Secondary distribution center');
/*!40000 ALTER TABLE `Location` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Notifications`
--

DROP TABLE IF EXISTS `Notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `Notifications` (
  `notification_id` int(11) NOT NULL AUTO_INCREMENT,
  `manager_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`notification_id`),
  KEY `manager_id` (`manager_id`),
  CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`manager_id`) REFERENCES `Employee` (`employee_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Notifications`
--

LOCK TABLES `Notifications` WRITE;
/*!40000 ALTER TABLE `Notifications` DISABLE KEYS */;
/*!40000 ALTER TABLE `Notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Order`
--

DROP TABLE IF EXISTS `Order`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `Order` (
  `order_id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) NOT NULL,
  `order_date` date NOT NULL,
  `shipping_address` varchar(255) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `order_status` varchar(50) NOT NULL,
  PRIMARY KEY (`order_id`),
  KEY `customer_id` (`customer_id`),
  CONSTRAINT `order_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `Customer` (`customer_id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Order`
--

LOCK TABLES `Order` WRITE;
/*!40000 ALTER TABLE `Order` DISABLE KEYS */;
INSERT INTO `Order` VALUES (1,1,'2024-10-15','789 Pine St, Liverpool',1099.98,'Shipped'),(2,2,'2024-10-20','321 Oak St, Leeds',699.99,'Processing'),(3,1,'2024-10-15','789 Pine St, Liverpool',1099.98,'Shipped'),(4,2,'2024-10-20','321 Oak St, Leeds',699.99,'Processing'),(5,1,'2024-10-15','789 Pine St, Liverpool',1099.98,'Shipped'),(6,2,'2024-10-20','321 Oak St, Leeds',699.99,'Processing');
/*!40000 ALTER TABLE `Order` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `OrderItem`
--

DROP TABLE IF EXISTS `OrderItem`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `OrderItem` (
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price_at_order_time` decimal(10,2) NOT NULL,
  PRIMARY KEY (`order_id`,`product_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `orderitem_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `Order` (`order_id`),
  CONSTRAINT `orderitem_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `Product` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `OrderItem`
--

LOCK TABLES `OrderItem` WRITE;
/*!40000 ALTER TABLE `OrderItem` DISABLE KEYS */;
INSERT INTO `OrderItem` VALUES (1,1,1,999.99),(1,2,1,99.99),(2,2,1,699.99);
/*!40000 ALTER TABLE `OrderItem` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Position`
--

DROP TABLE IF EXISTS `Position`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `Position` (
  `position_id` int(11) NOT NULL AUTO_INCREMENT,
  `department_id` int(11) DEFAULT NULL,
  `reports_to_position_id` int(11) DEFAULT NULL,
  `position_title` varchar(100) DEFAULT NULL,
  `is_executive` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`position_id`),
  KEY `department_id` (`department_id`),
  KEY `reports_to_position_id` (`reports_to_position_id`),
  CONSTRAINT `position_ibfk_1` FOREIGN KEY (`department_id`) REFERENCES `Department` (`department_id`),
  CONSTRAINT `position_ibfk_2` FOREIGN KEY (`reports_to_position_id`) REFERENCES `Position` (`position_id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Position`
--

LOCK TABLES `Position` WRITE;
/*!40000 ALTER TABLE `Position` DISABLE KEYS */;
INSERT INTO `Position` VALUES (2,1,NULL,'HR Manager',1),(5,1,NULL,'HR Specialist',0),(6,1,NULL,'Recruitment Officer',0),(7,1,NULL,'Compensation Analyst',0),(8,2,NULL,'IT Support Specialist',0),(9,2,NULL,'Systems Administrator',0),(10,2,NULL,'Network Engineer',0),(11,3,NULL,'Accountant',0),(12,3,NULL,'Financial Analyst',0),(13,3,NULL,'Tax Specialist',0);
/*!40000 ALTER TABLE `Position` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Product`
--

DROP TABLE IF EXISTS `Product`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `Product` (
  `product_id` int(11) NOT NULL AUTO_INCREMENT,
  `product_name` varchar(255) NOT NULL,
  `manufacturer_name` varchar(255) DEFAULT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `answered_questions_count` int(11) DEFAULT 0,
  `amazon_category` varchar(255) DEFAULT NULL,
  `amazon_sub_category` varchar(255) DEFAULT NULL,
  `product_description` text DEFAULT NULL,
  PRIMARY KEY (`product_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Product`
--

LOCK TABLES `Product` WRITE;
/*!40000 ALTER TABLE `Product` DISABLE KEYS */;
INSERT INTO `Product` VALUES (1,'Widget A','Widgets Inc.',19.99,0,NULL,NULL,NULL),(2,'Laptop','Tech Co.',999.99,12,'Electronics','Computers','A high-performance laptop.'),(3,'Smartphone','Phone Inc.',699.99,8,'Electronics','Mobile Phones','A premium smartphone with great features.');
/*!40000 ALTER TABLE `Product` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Region`
--

DROP TABLE IF EXISTS `Region`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `Region` (
  `region_id` int(11) NOT NULL AUTO_INCREMENT,
  `region_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  PRIMARY KEY (`region_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Region`
--

LOCK TABLES `Region` WRITE;
/*!40000 ALTER TABLE `Region` DISABLE KEYS */;
INSERT INTO `Region` VALUES (1,'Northwest','Covers the northwest region of the UK'),(2,'Southeast','Covers the southeast region of the UK'),(3,'Midlands','Covers the midlands region of the UK');
/*!40000 ALTER TABLE `Region` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Review`
--

DROP TABLE IF EXISTS `Review`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `Review` (
  `review_id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `rating` decimal(2,1) DEFAULT 0.0,
  `review_text` text DEFAULT NULL,
  `review_date` date DEFAULT NULL,
  PRIMARY KEY (`review_id`),
  KEY `customer_id` (`customer_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `review_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `Customer` (`customer_id`),
  CONSTRAINT `review_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `Product` (`product_id`),
  CONSTRAINT `CONSTRAINT_1` CHECK (`rating` >= 1.0 and `rating` <= 5.0)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Review`
--

LOCK TABLES `Review` WRITE;
/*!40000 ALTER TABLE `Review` DISABLE KEYS */;
INSERT INTO `Review` VALUES (1,1,1,5.0,'Excellent laptop, highly recommended!','2024-11-01'),(2,2,2,4.5,'Great phone with excellent battery life.','2024-11-02');
/*!40000 ALTER TABLE `Review` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Vehicle`
--

DROP TABLE IF EXISTS `Vehicle`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `Vehicle` (
  `vehicle_id` int(11) NOT NULL AUTO_INCREMENT,
  `location_id` int(11) DEFAULT NULL,
  `vehicle_type` varchar(50) DEFAULT NULL,
  `is_available` tinyint(1) DEFAULT 1,
  `registration_number` varchar(20) DEFAULT NULL,
  `capacity` decimal(10,2) DEFAULT NULL,
  PRIMARY KEY (`vehicle_id`),
  KEY `location_id` (`location_id`),
  CONSTRAINT `vehicle_ibfk_1` FOREIGN KEY (`location_id`) REFERENCES `Location` (`location_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Vehicle`
--

LOCK TABLES `Vehicle` WRITE;
/*!40000 ALTER TABLE `Vehicle` DISABLE KEYS */;
INSERT INTO `Vehicle` VALUES (1,2,'Truck',1,'TRK-001',1500.00),(2,3,'Van',1,'VAN-002',800.00);
/*!40000 ALTER TABLE `Vehicle` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `employeeHistory`
--

DROP TABLE IF EXISTS `employeeHistory`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `employeeHistory` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) NOT NULL,
  `base_salary` float NOT NULL,
  `allowance` float NOT NULL,
  `incentives` float NOT NULL,
  `date_added` date NOT NULL,
  `date_modified` date DEFAULT NULL,
  `bonuses` float DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=36 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `employeeHistory`
--

LOCK TABLES `employeeHistory` WRITE;
/*!40000 ALTER TABLE `employeeHistory` DISABLE KEYS */;
INSERT INTO `employeeHistory` VALUES (26,11,31500,3,2,'2024-11-28',NULL,0),(27,9,52500,500,10000,'2024-11-28',NULL,10000),(28,10,54337.5,500,10000,'2024-11-28',NULL,10000),(29,49,39000,0,0,'2024-11-28','2024-11-28',0),(30,49,39000,0,0,'2024-11-28','2024-11-28',0),(31,49,39000,0,0,'2024-11-28',NULL,0),(32,51,39000,1000,100,'2024-11-28',NULL,100);
/*!40000 ALTER TABLE `employeeHistory` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `employee_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('executive','jarvis','employee') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`employee_id`),
  UNIQUE KEY `username` (`username`),
  CONSTRAINT `fk_employee_id` FOREIGN KEY (`employee_id`) REFERENCES `Employee` (`employee_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (9,'executive_user','$2y$10$crWB90yD4orkDIJJuZ4uY.dU2Qo80pS9Lq02jMnDFtFMd/ENuFRvG','executive','2024-11-24 04:55:02'),(10,'jarvis_user','$2y$10$4MwxmiiMuby6zINIHXG00uuHK9X6e/Bl.FwIaHwgAgcQ76avNUUti','jarvis','2024-11-24 04:55:03'),(11,'employee_user','$2y$10$T.uP8jeTbnraXGlHv.7zPOJ8j9qbzmOWyse.U0I5UNVhDqLKYk1Ze','employee','2024-11-24 04:55:03'),(49,'malissi','$2y$10$I6MXW/IouDc9xJOIyQccFeInGYkVVDdH0jpC92GXUVrM5Wtoi7zji','employee','2024-11-28 21:19:53'),(51,'Malissia','$2y$10$wrKD2TB2SYLGqfxImReySePoYRSDkcELHVsKDYQPw.ynkXGjm3odC','employee','2024-11-28 21:27:32');
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

-- Dump completed on 2024-11-29 13:29:06
