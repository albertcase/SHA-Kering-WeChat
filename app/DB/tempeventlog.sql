DROP TABLE IF EXISTS `temp_event_log`;
CREATE TABLE `temp_event_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `openid` varchar(30) NOT NULL,
  `texts` varchar(100) NOT NULL,
  `event` varchar(800) NOT NULL,
  `templog` varchar(800) NOT NULL,
  `createTime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;
