/*
SQLyog Community Edition- MySQL GUI v7.12 
MySQL - 5.1.45-1-log : Database - maurice_dev
*********************************************************************
*/

/*!40101 SET NAMES utf8 */;

/*!40101 SET SQL_MODE=''*/;

/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;

CREATE DATABASE /*!32312 IF NOT EXISTS*/`maurice_dev` /*!40100 DEFAULT CHARACTER SET latin1 */;

USE `maurice_dev`;

/*Table structure for table `p98TestData` */

DROP TABLE IF EXISTS `p98TestData`;

CREATE TABLE `p98TestData` (
  `id` varchar(30) NOT NULL,
  `cid` int(10) unsigned NOT NULL,
  `schemeid` int(10) unsigned NOT NULL,
  `value` longtext,
  PRIMARY KEY (`id`,`cid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

/*Data for the table `p98TestData` */

insert  into `p98TestData`(`id`,`cid`,`schemeid`,`value`) values ('11-38-1',5,56,'TestImporter-a0a0a0-a'),('11-38-2',5,56,'TestImporter-a0a0a1-a'),('11-38-1',1,56,'<file><originalName>TestImporter-a0a0a0-a_12513.jpg</originalName><localName>11-38-1-1-TestImporter-a0a0a0-a_12513.jpg</localName><size>228863</size><type>image/jpeg</type></file>'),('11-38-2',1,56,'<file><originalName>TestImporter-a0a0a1-a_12513.rm</originalName><localName>11-38-2-1-TestImporter-a0a0a1-a_12513.rm</localName><size>45641388</size><type>application/vnd.rn-realmedia</type></file>'),('11-38-2',0,56,'<reverseAssociator><assoc><kid>11-38-2</kid><cid>2</cid></assoc></reverseAssociator>'),('11-38-2',2,56,'<associator><kid>11-38-2</kid></associator>'),('11-38-2',3,56,'5000'),('11-38-2',4,56,'45000'),('11-38-1',6,56,'Fri, 18 Dec 2009 09:30:15 -0500'),('11-38-2',6,56,'Fri, 18 Dec 2009 09:30:15 -0500');

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
