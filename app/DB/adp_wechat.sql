DROP TABLE IF EXISTS `wechat_menu`;
DROP TABLE IF EXISTS `wechat_menu_event`;
DROP TABLE IF EXISTS `wechat_getmsglog`;
-- DROP TABLE IF EXISTS `wechat_admin`;
DROP TABLE IF EXISTS `adp_article`;


DROP TABLE IF EXISTS `wechat_menu`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wechat_menu` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `mOrder` enum('0','1','2') DEFAULT '0',
  `subOrder` enum('0','1','2','3','4','5') DEFAULT '0',
  `menuName` varchar(80) NOT NULL,
  `eventtype` varchar(50) NOT NULL,
  `eventKey` varchar(50) DEFAULT NULL,
  `eventUrl` varchar(255) DEFAULT NULL,
  `eventmedia_id` varchar(255) DEFAULT NULL,
  `createTime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `wechat_events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wechat_events` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `menuId` varchar(50) NOT NULL,
  `getMsgType` varchar(50) NOT NULL,
  `getContent` varchar(250) NOT NULL,
  `getEvent` varchar(100) NOT NULL,
  `getEventKey` varchar(255) NOT NULL,
  `getTicket` varchar(255) NOT NULL,
  `MsgType` varchar(50) NOT NULL,
  `createTime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `wechat_feedbacks`;

CREATE TABLE `wechat_feedbacks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `menuId` varchar(50) NOT NULL,
  `MsgType` varchar(50) NOT NULL,
  `MsgData` longtext NOT NULL,
  `createTime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `wechat_keyword_tag`;

CREATE TABLE `wechat_keyword_tag` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `menuId` varchar(50) NOT NULL,
  `Tagname` varchar(50) NOT NULL,
  `createTime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `wechat_getmsglog`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wechat_getmsglog` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `openid` varchar(50) NOT NULL,
  `msgType` varchar(50) NOT NULL,
  `msgXml` longtext NOT NULL,
  `createTime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

-- DROP TABLE IF EXISTS `wechat_admin`;
-- CREATE TABLE `wechat_admin` (
--   `id` int(11) NOT NULL AUTO_INCREMENT,
--   `username` varchar(50) NOT NULL,
--   `password` varchar(50) NOT NULL,
--   `latestTime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
--   `createTime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
--   PRIMARY KEY (`id`),
--   UNIQUE KEY `username` (`username`)
-- ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `adp_article`;
CREATE TABLE `adp_article` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pageid` varchar(50) NOT NULL,
  `pagename` varchar(50) NOT NULL,
  `pagetitle` varchar(50) NOT NULL,
  `content` longtext NOT NULL,
  `submiter` varchar(50) NOT NULL,
  `edittime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `createTime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pageid` (`pageid`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;
