CREATE TABLE IF NOT EXISTS `user` (
  `userid` varchar(20) character set ascii NOT NULL,
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `tokenexpiration` int(11) NOT NULL default '0',
  `added` datetime NOT NULL default '0000-00-00 00:00:00',
  `removed` datetime NOT NULL default '0000-00-00 00:00:00',
  `active` datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (`userid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;