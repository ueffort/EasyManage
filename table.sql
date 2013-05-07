CREATE TABLE `right_role` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL COMMENT '角色名称',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '权限开关，1是开启0是关闭',
  PRIMARY KEY (`id`),
  KEY `status` (`status`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='权限角色';

CREATE TABLE `right_list` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `module_name` varchar(50) NOT NULL COMMENT '模块名',
  `type` varchar(50) NOT NULL,
  `role_id` varchar(50) NOT NULL DEFAULT '1' COMMENT '权限表id',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1开启0关闭',
  PRIMARY KEY (`id`),
  KEY `role_id` (`role_id`,`module_name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='权限控制表';

CREATE TABLE `log` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `log_info` text NOT NULL COMMENT '日志详细信息',
  `result` text NOT NULL COMMENT '操作结果',
  `user_id` int(11) NOT NULL COMMENT '用户id',
  `datetime` int(11) NOT NULL COMMENT '操作时间',
  `module_name` varchar(50) NOT NULL COMMENT '操作的模块',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='日志';

CREATE TABLE `nav` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL COMMENT '菜单名称',
  `pid` int(11) NOT NULL DEFAULT '0' COMMENT '父id',
  `url` varchar(100) NOT NULL COMMENT '访问url',
  `ordernum` int(11) NOT NULL DEFAULT '0' COMMENT '排序',
  PRIMARY KEY (`id`),
  KEY `ordernum` (`ordernum`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='导航';

CREATE TABLE `tag` (
  `id` mediumint(10) unsigned NOT NULL AUTO_INCREMENT,
  `module_name` varchar(50) NOT NULL COMMENT '对应的模块名称',
  `name` varchar(20) NOT NULL COMMENT '标签名称',
  `color` varchar(10) NOT NULL COMMENT '标签颜色',
  `description` text NOT NULL COMMENT '标签描述',
  `order` tinyint(2) NOT NULL COMMENT '同模块标签排序',
  PRIMARY KEY (`id`),
  KEY `module_name` (`module_name`,`order`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='标签设置表';

CREATE TABLE `tag_list` (
  `id` mediumint(10) unsigned NOT NULL AUTO_INCREMENT,
  `module_name` varchar(50) NOT NULL COMMENT '对应模块名称',
  `target_id` mediumint(10) unsigned NOT NULL COMMENT '对应记录ID',
  `tag_id` mediumint(10) unsigned NOT NULL COMMENT '对应标签ID',
  PRIMARY KEY (`id`),
  KEY `module_name` (`module_name`,`target_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='标签列表';

CREATE TABLE `user` (
  `uid` int(11) NOT NULL AUTO_INCREMENT COMMENT '网站uid',
  `username` varchar(15) NOT NULL COMMENT '用户名',
  `password` varchar(32) NOT NULL COMMENT '用户密码',
  `email` varchar(50) NOT NULL COMMENT '邮箱',
  `role_id` varchar(50) NOT NULL COMMENT '权限id',
  `is_manage` int(11) NOT NULL DEFAULT '0' COMMENT '管理员,0不是，1是',
  `status` int(11) NOT NULL DEFAULT '1' COMMENT '登陆权限（1可以登陆，0禁止）',
  PRIMARY KEY (`uid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='用户表';

CREATE TABLE `session` (
  `session` char(10) NOT NULL COMMENT 'sessionID',
  `value` varchar(255) NOT NULL COMMENT 'session值',
  `inserttime` int(10) NOT NULL COMMENT '添加时间',
  `cachetime` int(10) NOT NULL COMMENT '缓存时间',
  PRIMARY KEY (`session`)
) ENGINE=MEMORY DEFAULT CHARSET=utf8 COMMENT='用户session';
