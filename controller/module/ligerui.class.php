<?php
class controller_module_ligeruiview extends tools_controller_moduleview{
	static protected $viewList = array(
		'baselist'=>array('name'=>'基本列表','shortname'=>'列表')
	); 
	public function baselist(){
		$user = FN::i('module.user');
		$page_info = $this->getPageInfo();
		if(!$page_info){
			$right = FN::i('module.right');
			$role_list = $right->getRoleList();
			$role_list=$this->_listdata($role_list['list']);
			$role_list=$role_list['Rows'];
			$field = array(
				array('name'=>'username','display'=>'用户名'),
				array('name'=>'role_id','display'=>'所属角色','type'=>'select','select'=>$role_list,'textField'=>'name','valueField'=>'id'),
				array('name'=>'status','display'=>'状态','type'=>'select','select'=>$this->_parseSelect(array(1=>'通过',0=>'禁止'))),
			);
			$search = $other = array();

			$toolbar = array(
				array('view'=>'manage.user.add','icon'=>'add','window'=>true),
				array('view'=>'manage.user.edit','icon'=>'modify','param'=>array('uid'),'window'=>true),
				array('handle'=>'manage.user.delete','icon'=>'delete','param'=>array('uid'),'batch'=>true),
			);
			$this->_toolbar($toolbar,$search);

			$other = array(
				'dblclick'=>array('param'=>array('uid'),'view'=>'manage.user.edit','window'=>true)
			);
			$this->_parseroute($other['dblclick']);
			return $this->_list($field,$search,$other);
		}
		$pagesize=$page_info['pagesize'];
		$p=($page_info['page']-1)*$pagesize;
		$result=$user->getList(array('limit'=>array($p,$pagesize)),array('is_manage'=>0));
		$data=$result['list'];
		$total=$result['total'];
		return $this->_listdata($data,$total);
	}
}
class controller_module_ligeruihandle extends tools_controller_modulehandle{
	public function index(){
	
	}
}
?>

