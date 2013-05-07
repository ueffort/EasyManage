<?php
//权限控制
class controller_manage_rightview extends tools_controller_manageview{
	static protected $viewList = array(
		'index'=>array('name'=>'权限管理','shortname'=>'管理'),
		'add'=>array('name'=>'添加权限控制','shortname'=>'添加'),
		'edit'=>array('name'=>'修改权限控制','shortname'=>'修改','param'=>array('id')),
		'delete'=>array('name'=>'删除权限控制','shortname'=>'删除','param'=>array('id')),
		'role'=>array('name'=>'角色管理','shortname'=>'管理'),
		'role_add'=>array('name'=>'添加角色','shortname'=>'添加'),
		'role_edit'=>array('name'=>'修改角色','shortname'=>'修改','param'=>array('id')),
		'role_delete'=>array('name'=>'删除角色','shortname'=>'删除','param'=>array('id')),
	); 

	public function index(){
		$right = FN::i('module.right');

		$page_info = $this->getPageInfo();
		if(!$page_info){
			
			$role_list = $right->getRoleList();
			$role_list=$this->_listdata($role_list['list']);
			$role_list=$role_list['Rows'];
			$field = array(
				array('name'=>'id','display'=>'ID','type'=>'int'),
				array('name'=>'module_name','display'=>'名称','width'=>'300'),
				array('name'=>'type','display'=>'视图'),
				array('name'=>'role_id','display'=>'所属角色','type'=>'select','select'=>$role_list,'textField'=>'name','valueField'=>'id'),
				array('name'=>'status','display'=>'状态','type'=>'select','select'=>$this->_parseSelect(array(1=>'通过',0=>'禁止'))),
			);

			$toolbar = array(
				array('view'=>'manage.right.add','icon'=>'add','window'=>true),
				array('view'=>'manage.right.edit','icon'=>'modify','param'=>array('id'),'window'=>true),
				array('handle'=>'manage.right.delete','icon'=>'delete','param'=>array('id'),'batch'=>true),
			);
			$grid=array();
			$this->_toolbar($toolbar,$grid);
			$other = array(
				'dblclick'=>array('param'=>array('id'),'view'=>'manage.right.edit','window'=>true)
			);
			$this->_parseroute($other['dblclick']);

			$role_list = $right->getRoleList();
			$role_list=$this->_listdata($role_list['list']);
			$role_list=$role_list['Rows'];
			
			$search_data=array(
				'status'=>1,
			);
			$search_field = array(
				array('name'=>'module_name','display'=>'名称'),
				array('name'=>'type','display'=>'视图','type'=>'select','options'=>array(
					'data'=>$this->_parseSelect(array('handle'=>'handle','view'=>'view')),
				)),
				array('name'=>'role_id','display'=>'角色','type'=>'select','options'=>array(
					'data'=>$role_list,
					'textField'=>'name',
					'valueField'=>'id',
				)),
				array('name'=>'status','display'=>'状态','type'=>'select','options'=>array(
					'data'=>$this->_parseSelect(array(1=>'通过',0=>'禁止')),
				)),
			);
			$this->_search($search_data,$search_field,$other);
			return $this->_list($field,$grid,$other);
		}

		$pagesize=$page_info['pagesize'];
		$p=($page_info['page']-1)*$pagesize;
		$search = $this->getSearchInfo();
		if(!empty($search)){
			$where=" 1=1 and status={$search['status']} ";
			$where.=empty($search['module_name'])?'':" and  module_name like '%{$search['module_name']}%' ";
			$where.=empty($search['type'])?'':" and  type ='{$search['type']}' ";
			$where.=empty($search['role_id'])?'':" and  FIND_IN_SET({$search['role_id']},role_id) ";
			$result=$right->getRightList(array('limit'=>array($p,$pagesize),'where'=>$where));

		}else{
			$result=$right->getRightList(array('limit'=>array($p,$pagesize)));
		}
		return $this->_listdata($result['list'],$result['total']);
	}

	public function role(){
		$right = FN::i('module.right');
		$page_info = $this->getPageInfo();
		if(!$page_info){
			$field = array(
				array('name'=>'id','display'=>'ID','type'=>'int'),
				array('name'=>'name','display'=>'名称'),
				array('name'=>'status','display'=>'状态','type'=>'select','select'=>$this->_parseSelect(array(1=>'通过',0=>'禁止'))),
			);
			$search = $other = array();
			$search=array('pageSize'=>20);
			$toolbar = array(
				array('view'=>'manage.right.role_add','icon'=>'add','window'=>true),
				array('view'=>'manage.right.role_edit','icon'=>'modify','param'=>array('id'),'window'=>true),
				array('handle'=>'manage.right.role_delete','icon'=>'delete','param'=>array('id'),'batch'=>true),
			);
			$this->_toolbar($toolbar,$search);

			$other = array(
				'dblclick'=>array('param'=>array('id'),'view'=>'manage.right.role_edit','window'=>true)
			);
			$this->_parseroute($other['dblclick']);
			return $this->_list($field,$search,$other);
		}
		$pagesize=$page_info['pagesize'];
		$p=($page_info['page']-1)*$pagesize;

		$result=$right->getRoleList(array('limit'=>array($p,$pagesize)));
		return $this->_listdata($result['list'],$result['total']);

	}

	public function add(){
		$right = FN::i('module.right');
		$data=array(
			'status'=>1,
			'type'=>'handle',
		);
		$role_list = $right->getRoleList();
		$role_list=$this->_listdata($role_list['list']);
		$role_list=$role_list['Rows'];

		$field = array(
			array('name'=>'module_name','display'=>'模块名称','type'=>'string'),
			array('name'=>'type','display'=>'类型','type'=>'select','options'=>array(
				'data'=>$this->_parseSelect(array('handle'=>'handle','view'=>'view')),
			)),
			array('name'=>'role_id','display'=>'角色','type'=>'select','options'=>array(
				'data'=>$role_list,
				'textField'=>'name',
				'valueField'=>'id',
				'isMultiSelect'=>true,
				'isShowCheckBox'=>true,
			)),
			array('name'=>'status','display'=>'状态','type'=>'select','options'=>array(
				'data'=>$this->_parseSelect(array(1=>'通过',0=>'禁止'))
			)),
		);
		return $this->_form($data,$field,'manage.right.add');	
	}

	public function edit(){
		$right = FN::i('module.right');
		$right_one=$right->getRight(array('id'=>$this->param['id']));
		$data=array(
			'id'=>$this->param['id'],
			'module_name'=>$right_one['module_name'],
			'type'=>$right_one['type'],
			'role_id'=>$right_one['role_id'],
			'status'=>$right_one['status'],
		);
		$role_list = $right->getRoleList();
		$role_list=$this->_listdata($role_list['list']);
		$role_list=$role_list['Rows'];

		$field = array(
			array('name'=>'id','display'=>'ID','type'=>'hidden'),
			array('name'=>'module_name','display'=>'模块名称','type'=>'string'),
			array('name'=>'type','display'=>'类型','type'=>'select','options'=>array(
				'data'=>$this->_parseSelect(array('handle'=>'handle','view'=>'view')),
			)),
			array('name'=>'role_id','display'=>'角色','type'=>'select','options'=>array(
				'data'=>$role_list,
				'textField'=>'name',
				'valueField'=>'id',
				'isMultiSelect'=>true,
				'isShowCheckBox'=>true,
			)),
			array('name'=>'status','display'=>'状态','type'=>'select','options'=>array(
				'data'=>$this->_parseSelect(array(1=>'通过',0=>'禁止'))
			)),
		);
		return $this->_form($data,$field,'manage.right.edit');	
	}
	
	public function role_add(){
		$data=array('status'=>1);
		//所有权限
		$field = array(
			array('name'=>'name','display'=>'角色名称','type'=>'string'),
			array('name'=>'status','display'=>'状态','type'=>'select','options'=>array(
				'data'=>$this->_parseSelect(array(1=>'通过',0=>'禁止'))
			)),
		);
		return $this->_form($data,$field,'manage.right.role_add');	
	}

	public function role_edit(){
		$right = FN::i('module.right');
		$role_one=$right->getRole(array('id'=>$this->param['id']));
		$data=array(
			'id'=>$this->param['id'],
			'name'=>$role_one['name'],
			'status'=>$role_one['status'],
		);
		//所有权限
		$field = array(
			array('name'=>'id','display'=>'ID','type'=>'hidden'),
			array('name'=>'name','display'=>'角色名称','type'=>'string'),
			array('name'=>'status','display'=>'状态','type'=>'select','options'=>array(
				'data'=>$this->_parseSelect(array(1=>'通过',0=>'禁止'))
			)),
		);
		return $this->_form($data,$field,'manage.right.role_edit');	
	}


}
class controller_manage_righthandle extends tools_controller_managehandle{
	static protected $handleList = array(
		'add'=>array('name'=>'添加权限控制','shortname'=>'添加'),
		'edit'=>array('name'=>'修改权限控制','shortname'=>'修改','param'=>array('id')),
		'delete'=>array('name'=>'删除权限控制','shortname'=>'删除','param'=>array('id'),'batch'=>true),
		'role_add'=>array('name'=>'添加角色','shortname'=>'添加'),
		'role_edit'=>array('name'=>'修改角色','shortname'=>'修改','param'=>array('id')),
		'role_delete'=>array('name'=>'删除角色','shortname'=>'删除','param'=>array('id'),'batch'=>true),
	);

	public function add(){
		$right = FN::i('module.right');
		$right->addRight($_POST);
		return array('status'=>'success');
	}

	public function edit(){
		$right = FN::i('module.right');
		$id=$_POST['id'];
		unset($_POST['id']);
		$right->editRight(array('id'=>$id),$_POST);
		return array('status'=>'success');
	}
	public function delete(){
		$right = FN::i('module.right');
		if(!$this->isBatch()){
			$id=$_POST['id'];
		}else{
			$str='';
			$arr = $this->getBatch();
			foreach($arr as $k){
				foreach($k as $k2){
					$str.=$k2.',';
				}
			}
			$id=substr($str,0,-1);
		}
		if(!empty($id)){
			$right->deleteRight("id in ($id)");
		}
		return array('status'=>'success');
	}

	public function role_add(){
		$right = FN::i('module.right');
		$right->addRole($_POST);
		return array('status'=>'success');
	}

	public function role_edit(){
		$right = FN::i('module.right');
		$id=$_POST['id'];
		unset($_POST['id']);
		$right->editRole(array('id'=>$id),$_POST);
		return array('status'=>'success');
	}

	public function role_delete(){
		$right = FN::i('module.right');

		if(!$this->isBatch()){
			$id=$_POST['id'];
		}else{
			$str='';
			$arr = $this->getBatch();
			foreach($arr as $k){
				foreach($k as $k2){
					$str.=$k2.',';
				}
			}
			$id=substr($str,0,-1);
		}
		if(!empty($id)){
			$right->deleteRole("id in ($id) ");
		}
		return array('status'=>'success');
	}
}
?>
