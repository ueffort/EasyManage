<?php
//样例
class controller_module_indexview extends tools_controller_moduleview{
	static protected $viewlist = array(
		'index'=>'首页',
		'add'=>array('添加',''),
		'edit'=>array('修改','标题$title')
	); 
	public function index(){
		return $result;

	}
}
class controller_module_indexhandle extends tools_controller_modulehandle{
	public function index(){
	
	}
}
?>

