-- MySQL dump 10.13  Distrib 5.7.22, for Linux (x86_64)
--
-- Host: localhost    Database: anno
-- ------------------------------------------------------
-- Server version	5.7.22-0ubuntu18.04.1

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Current Database: `anno`
--

/*!40000 DROP DATABASE IF EXISTS `anno`*/;

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `anno` /*!40100 DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci */;

USE `anno`;

--
-- Table structure for table `Annotation`
--

DROP TABLE IF EXISTS `Annotation`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `Annotation` (
  `Id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `PublicationId` int(10) unsigned NOT NULL,
  `Index` varchar(15) NOT NULL,
  `Class` int(10) unsigned NOT NULL,
  `Sentence` int(11) NOT NULL,
  `Onset` int(10) unsigned DEFAULT NULL,
  `Offset` int(10) unsigned DEFAULT NULL,
  `Text` varchar(255) NOT NULL,
  `User` int(10) unsigned NOT NULL,
  `Reference` int(10) unsigned DEFAULT NULL,
  `annometa` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`Id`),
  UNIQUE KEY `Id_UNIQUE` (`Id`),
  UNIQUE KEY `PublicationId_2` (`PublicationId`,`Class`,`Sentence`,`Onset`,`Offset`,`User`),
  KEY `fk_Annotation_1_idx` (`Class`),
  KEY `PublicationId` (`PublicationId`),
  KEY `TokenOnset` (`Onset`),
  KEY `TokenOffset` (`Offset`),
  KEY `fk_user` (`User`),
  KEY `fk_ref` (`Reference`),
  CONSTRAINT `fk_Annotation_1` FOREIGN KEY (`Class`) REFERENCES `Class` (`Id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_publication` FOREIGN KEY (`PublicationId`) REFERENCES `Publication` (`Id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_ref` FOREIGN KEY (`Reference`) REFERENCES `Annotation` (`Id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_user` FOREIGN KEY (`User`) REFERENCES `User` (`Id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=73761 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `Class`
--

DROP TABLE IF EXISTS `Class`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `Class` (
  `Id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `Name` varchar(63) NOT NULL,
  `IndividualName` tinyint(1) NOT NULL,
  `Description` text,
  PRIMARY KEY (`Id`),
  UNIQUE KEY `Name` (`Name`),
  UNIQUE KEY `Id` (`Id`)
) ENGINE=InnoDB AUTO_INCREMENT=567 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `Data`
--

DROP TABLE IF EXISTS `Data`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `Data` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `ClassId` int(10) unsigned NOT NULL,
  `Parent` int(10) unsigned DEFAULT NULL,
  `AnnotationId` int(10) unsigned DEFAULT NULL,
  `RelationId` int(10) unsigned DEFAULT NULL,
  `DataGroup` int(10) unsigned DEFAULT NULL,
  `Name` varchar(63) DEFAULT NULL,
  `User` int(10) unsigned NOT NULL,
  `PublicationId` int(10) unsigned NOT NULL,
  `ManuallySet` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `fk_1Data_1_idx` (`ClassId`),
  KEY `fk_1Data_2_idx` (`Parent`),
  KEY `fk_1Data_3_idx` (`AnnotationId`),
  KEY `fk_1Data_4_idx` (`RelationId`),
  KEY `fk_1Data_5_idx` (`DataGroup`),
  KEY `fk_1Data_6_idx` (`User`),
  KEY `PublicationId_idx_1` (`PublicationId`),
  CONSTRAINT `fk_2Data_1` FOREIGN KEY (`ClassId`) REFERENCES `Class` (`Id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_2Data_2` FOREIGN KEY (`Parent`) REFERENCES `Data` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_2Data_3` FOREIGN KEY (`AnnotationId`) REFERENCES `Annotation` (`Id`) ON DELETE SET NULL ON UPDATE SET NULL,
  CONSTRAINT `fk_2Data_4` FOREIGN KEY (`RelationId`) REFERENCES `Relation` (`Id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_2Data_5` FOREIGN KEY (`DataGroup`) REFERENCES `Data` (`id`) ON DELETE SET NULL ON UPDATE SET NULL,
  CONSTRAINT `fk_2Data_6` FOREIGN KEY (`User`) REFERENCES `User` (`Id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_2Data_7` FOREIGN KEY (`PublicationId`) REFERENCES `Publication` (`Id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=17838 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `Data_bak`
--

DROP TABLE IF EXISTS `Data_bak`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `Data_bak` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `ClassId` int(10) unsigned NOT NULL,
  `Parent` int(10) unsigned DEFAULT NULL,
  `AnnotationId` int(10) unsigned DEFAULT NULL,
  `RelationId` int(10) unsigned DEFAULT NULL,
  `DataGroup` int(10) unsigned DEFAULT NULL,
  `Name` varchar(63) DEFAULT NULL,
  `User` int(10) unsigned NOT NULL,
  `PublicationId` int(10) unsigned NOT NULL,
  `ManuallySet` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_UNIQUE` (`id`),
  KEY `fk_Data_1_idx` (`ClassId`),
  KEY `fk_Data_2_idx` (`Parent`),
  KEY `fk_Data_3_idx` (`AnnotationId`),
  KEY `fk_Data_4_idx` (`RelationId`),
  KEY `fk_Data_5_idx` (`DataGroup`),
  KEY `fk_Data_6` (`User`),
  KEY `PublicationId` (`PublicationId`),
  CONSTRAINT `fk_Data_1` FOREIGN KEY (`ClassId`) REFERENCES `Class` (`Id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_Data_2` FOREIGN KEY (`Parent`) REFERENCES `Data_bak` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_Data_3` FOREIGN KEY (`AnnotationId`) REFERENCES `Annotation` (`Id`) ON DELETE SET NULL ON UPDATE SET NULL,
  CONSTRAINT `fk_Data_4` FOREIGN KEY (`RelationId`) REFERENCES `Relation` (`Id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_Data_5` FOREIGN KEY (`DataGroup`) REFERENCES `Data_bak` (`id`) ON DELETE SET NULL ON UPDATE SET NULL,
  CONSTRAINT `fk_Data_6` FOREIGN KEY (`User`) REFERENCES `User` (`Id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_Data_7` FOREIGN KEY (`PublicationId`) REFERENCES `Publication` (`Id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=10812 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `Group`
--

DROP TABLE IF EXISTS `Group`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `Group` (
  `Id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `Group` varchar(63) NOT NULL,
  `Heading` varchar(63) NOT NULL,
  `Order` int(10) NOT NULL,
  PRIMARY KEY (`Id`),
  UNIQUE KEY `Id_UNIQUE` (`Group`,`Heading`),
  UNIQUE KEY `Value_UNIQUE` (`Group`,`Heading`),
  UNIQUE KEY `Order_UNIQUE` (`Order`),
  KEY `Group` (`Group`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `Publication`
--

DROP TABLE IF EXISTS `Publication`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `Publication` (
  `Id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `FileName` varchar(63) NOT NULL,
  `Name` varchar(63) DEFAULT NULL,
  PRIMARY KEY (`Id`)
) ENGINE=InnoDB AUTO_INCREMENT=236 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `Relation`
--

DROP TABLE IF EXISTS `Relation`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `Relation` (
  `Id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `Domain` int(10) unsigned NOT NULL,
  `Relation` varchar(63) NOT NULL,
  `Range` int(10) unsigned NOT NULL,
  `From` varchar(3) NOT NULL,
  `To` varchar(3) NOT NULL,
  `DataProperty` tinyint(4) NOT NULL,
  `MergedName` varchar(63) NOT NULL,
  PRIMARY KEY (`Id`),
  UNIQUE KEY `Id_UNIQUE` (`Id`),
  UNIQUE KEY `values_UNIQUE` (`Domain`,`Relation`,`Range`,`From`,`To`,`DataProperty`),
  KEY `Ix1` (`Domain`),
  KEY `fk_Annotation_2_idx` (`Range`),
  CONSTRAINT `fk_Relation_1` FOREIGN KEY (`Domain`) REFERENCES `Class` (`Id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_Relation_2` FOREIGN KEY (`Range`) REFERENCES `Class` (`Id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=106 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `SubClass`
--

DROP TABLE IF EXISTS `SubClass`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `SubClass` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `SuperClass` int(10) unsigned NOT NULL,
  `SubClass` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_UNIQUE` (`id`),
  UNIQUE KEY `values_UNIQUE` (`SuperClass`,`SubClass`),
  KEY `fk_SubClass_1_idx` (`SuperClass`),
  KEY `fk_SubClass_2_idx` (`SubClass`),
  CONSTRAINT `fk_SubClass_1` FOREIGN KEY (`SuperClass`) REFERENCES `Class` (`Id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_SubClass_2` FOREIGN KEY (`SubClass`) REFERENCES `Class` (`Id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=518 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `Token`
--

DROP TABLE IF EXISTS `Token`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `Token` (
  `Id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `PublicationId` int(10) unsigned NOT NULL,
  `Text` varchar(255) CHARACTER SET utf8 NOT NULL,
  `Onset` int(10) unsigned NOT NULL,
  `Offset` int(10) unsigned NOT NULL,
  `Sentence` int(10) unsigned NOT NULL,
  `Number` int(10) unsigned NOT NULL,
  PRIMARY KEY (`Id`),
  KEY `PublicationId` (`PublicationId`),
  CONSTRAINT `pubid` FOREIGN KEY (`PublicationId`) REFERENCES `Publication` (`Id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2112309 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `User`
--

DROP TABLE IF EXISTS `User`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `User` (
  `Id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `Mail` varchar(63) CHARACTER SET utf8 NOT NULL,
  `Password` char(60) CHARACTER SET utf8 NOT NULL,
  `IsCurator` tinyint(1) NOT NULL,
  PRIMARY KEY (`Id`),
  UNIQUE KEY `Mail` (`Mail`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `User_Publication`
--

DROP TABLE IF EXISTS `User_Publication`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `User_Publication` (
  `Id` int(11) NOT NULL AUTO_INCREMENT,
  `UserId` int(10) unsigned NOT NULL,
  `PublicationId` int(10) unsigned NOT NULL,
  `Ready` tinyint(1) NOT NULL DEFAULT '0',
  `ReadyCuration` tinyint(1) DEFAULT '0',
  `ReadySlotFilling` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`Id`),
  UNIQUE KEY `UserId_2` (`UserId`,`PublicationId`),
  KEY `UserId` (`UserId`),
  KEY `PublicationId` (`PublicationId`),
  CONSTRAINT `User_Publication_fk1` FOREIGN KEY (`UserId`) REFERENCES `User` (`Id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `User_Publication_fk2` FOREIGN KEY (`PublicationId`) REFERENCES `Publication` (`Id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5778 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2018-07-12  1:05:50
