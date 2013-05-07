<?php
//用户管理
class controller_manage_userview extends tools_controller_manageview{
	static protected $viewList = array(
		'index'=>array('name'=>'管理','shortname'=>'管理'),
		'add'=>array('name'=>'添加用户','shortname'=>'添加'),
		'edit'=>array('name'=>'修改用户','shortname'=>'修改','param'=>array('uid')),
	); 

	public function index(){
		$user = FN::i('module.user');
		$page_info = $this->getPageInfo();
		if(!$page_info){
			$right = FN::i('module.right');
			$role_list = $right->getRoleList();
			$role_list=$this->_listdata($role_list['list']);
			$role_list=$role_list['Rows'];
			$field = array(
				array('name'=>'uid','display'=>'UID','type'=>'int'),
				array('name'=>'username','display'=>'用户名'),
				array('name'=>'role_id','display'=>'所属角色','type'=>'select','select'=>$role_list,'textField'=>'name','valueField'=>'id'),
				array('name'=>'is_manage','display'=>'管理员','type'=>'select','select'=>$this->_parseSelect(array(1=>'是',0=>'否'))),
				array('name'=>'status','display'=>'状态','type'=>'select','select'=>$this->_parseSelect(array(1=>'通过',0=>'禁止'))),
			);
			$search = $other = array();
			$search=array('pageSize'=>20);

			$toolbar = array(
				array('view'=>'manage.user.add','icon'=>'add','window'=>true),
				array('view'=>'manage.user.edit','icon'=>'modify','param'=>array('uid'),'window'=>true),
				array('handle'=>'manage.user.delete','icon'=>'delete','param'=>array('uid')),
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
		$result=$user->getList(array('limit'=>array($p,$pagesize)));
		$data=$result['list'];
		$total=$result['total'];
		return $this->_listdata($data,$total);
	}


	public function add(){
		$data=array();
		
		$right = FN::i('module.right');
		$role_list = $right->getRoleList();
		$role_list=$this->_listdata($role_list['list']);
		$role_list=$role_list['Rows'];

		$field = array(
			array('name'=>'uid','display'=>'UID','type'=>'int'),
			array('name'=>'role','display'=>'角色列表','type'=>'select','options'=>array(
				'data'=>$role_list,
				'textField'=>'name',
				'valueField'=>'id',
				'isMultiSelect'=>true,
				'isShowCheckBox'=>true
			)),
			array('name'=>'is_manage','display'=>'管理员','type'=>'select','options'=>array(
				'data'=>$this->_parseSelect(array(1=>'是',0=>'否'))
			)),
			array('name'=>'status','display'=>'状态','type'=>'select','options'=>array(
				'data'=>$this->_parseSelect(array(1=>'通过',0=>'禁止'))
			)),
		);
		return $this->_form($data,$field,'manage.user.add');	
	}

	public function edit(){
		$user=FN::i("module.user");
		
		$right = FN::i('module.right');
		$role_list = $right->getRoleList();
		$role_list=$this->_listdata($role_list['list']);
		$role_list=$role_list['Rows'];

		$user_info=$user->getUserOne($this->param['uid']);

		$data=array(
			'uid'=>$this->param['uid'],
			'role_id'=>$user_info['role_id'],
			'is_manage'=>$user_info['is_manage'],
			'status'=>$user_info['status'],
		);
		$field = array(
			array('name'=>'uid','display'=>'UID','type'=>'hidden'),
			array('name'=>'role_id','display'=>'角色列表','type'=>'select','options'=>array(
				'data'=>$role_list,
				'textField'=>'name',
				'valueField'=>'id',
				'isMultiSelect'=>true,
				'isShowCheckBox'=>true
			)),
			array('name'=>'is_manage','display'=>'管理员','type'=>'select','options'=>array(
				'data'=>$this->_parseSelect(array(1=>'是',0=>'否'))
			)),
			array('name'=>'status','display'=>'状态','type'=>'select','options'=>array(
				'data'=>$this->_parseSelect(array(1=>'通过',0=>'禁止'))
			)),
		);
		return $this->_form($data,$field,'manage.user.edit');	
	}
}
class controller_manage_userhandle extends tools_controller_managehandle{
	static protected $handleList = array(
		'add'=>array('name'=>'添加用户','shortname'=>'添加'),
		'edit'=>array('name'=>'修改用户','shortname'=>'修改','param'=>array('uid')),
		'delete'=>array('name'=>'删除用户','shortname'=>'删除','param'=>array('uid')),
	);

	public function add(){
		$user = FN::i('module.user');
		$user->add($_POST);
		return array('status'=>'success');
	}

	public function edit(){
		$user = FN::i('module.user');
		$user->edit($_POST['uid'],$_POST);
		return array('status'=>'success');
	}

	public function delete(){
		$user = FN::i('module.user');
		$user->delete($_POST['uid']);
		return array('status'=>'success');
	}
}
?>
