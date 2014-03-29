<?php
include_once('fn/fn.php');
include_once('config.php');
FN::setConfig($config);
FN::initProject();
$route = FN::F('tools.route');
FN::map('route',$route);

//基本控制器
$route->route('main',array(
	'rule'=>'',
	'class'=>'controller.manage.main|view',
	'default'=>array('action'=>'main')
	)
);
//视图/执行控制器
$route->route('manage',array(
	'rule'=>':type:controller.:action.:op:param',
	'class'=>'controller.$type.$controller|$op',
	'extend'=>array('controller'=>array('(\.\w+)*','parseSlice',array(1)),'op'=>'(view|handle)','param'=>array('(\/\w+)*','parseParam',array('/')))
	)
);
$route->run();