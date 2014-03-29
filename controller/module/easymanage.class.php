<?php
class controller_module_easymanageview extends tools_controller_moduleview{
	static protected $viewList = array(
		'readme'=>array('name'=>'修改说明','shortname'=>'说明')
	);
	public function readme(){
		return array();
	}
}
?>

