<?php
//日志
class controller_manage_devolveview extends tools_controller_manageview{
	static protected $viewList = array(
		'index'=>array('name'=>'模块转移','shortname'=>'模块转移'),
	); 
	public function index(){
		if(!empty($this->param['type'])){
			$data=array('type'=>$this->param['type']);
		}else{
			$data=array();
		}
		$field = array(
			array('group'=>'请选择功能模块'),
			array('name'=>'type','type'=>'combobox','options'=>
				array('data'=>$this->_parseSelect(array(1=>'权限',2=>'导航',3=>'日志',4=>'标签')))
			),
			array('group'=>'请输入旧模块名'),
			array('name'=>'old_module_name','type'=>'string','width'=>300),
			array('group'=>'请输入新模块名'),
			array('name'=>'module_name','type'=>'string','width'=>300),
		);
		return $this->_form($data,$field,'manage.devolve.update');
	}

}
class controller_manage_devolvehandle extends tools_controller_managehandle{
	protected static $handleList = array(
		'update'=>array('name'=>'更新','shortname'=>'更新'),
	);
	public function update(){
		$devolve = FN::i('module.devolve');
		$devolve->multiUpdate($_POST);
		return array('status'=>'success');
	}
}
?>
