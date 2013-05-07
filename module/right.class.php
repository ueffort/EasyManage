<?php
class module_right implements FN__single{
	private static $_Instance = null;
	private $user = null;
	private $rightList = null;
	static public function getInstance($array=array()){
		if(!self::$_Instance){
			self::$_Instance = new self($array);
		}
		return self::$_Instance;
	}
	private function __construct($array){
		$this->role = FN::i('module.right|roleDB');
		$this->list = FN::i('module.right|listDB');
		$this->setUser(FN::i('module.user'));
	}
	//设置当前用户
	private function setUser($user){
		if($this->user){
			$uid = $user->getUid();
			$nowuid = $this->user->getUid();
			if($uid == $nowuid) return true;
		}
		$this->user = $user;
		$this->initRight();
	}
	private function initRight(){
		if(!$this->user) return false;
		$user=$this->user->getUser();
		if(!empty($user['role_id'])){
			//查找用户组状态
			$auth_arr=$this->role->where(array('status'=>1,'id'=>explode(',',$user['role_id'])))->select();
			$auth_id_arr=array();
			//所有权限的并集
			foreach($auth_arr as $k=>$v){
				$this->list->where("FIND_IN_SET ('$v[id]',role_id )");
			}
			$right_list = $this->list->order('module_name asc')->select();
			//所拥有的权限列表
			if(!empty($right_list)){
				foreach($right_list as $k=>$v){
					if(empty($this->rightList[$v['type']])) $this->rightList[$v['type']] = array(0=>array(),1=>array());
					$this->rightList[$v['type']][$v['status']][] = $v['module_name'];
				}
			}else{
				return false;
			}
		}else{
			return false;
		}
	}
	public function getRightAll($type){
		return empty($this->rightList[$type]) ? false : $this->rightList[$type];
	}
	//判断是否拥有权限
	public function isRight($info,$type){
		//权限
		if($this->user->isManager()) return true;
		$right = $this->getRightAll($type);
		if(empty($right)) return false;
		//没有任何通过权限
		if(empty($right[1])) return false;
		if(!empty($right[0])){
			//符合任意不通过权限则不通过
			foreach($right as $v){
				$module_name = str_replace(array('.','*'),array('\.','.*'),$v).'(\..*)*';
				if(preg_match("/^$module_name$/",$info)) return false;
			}
		}
		//不符合任意不通过权限
		foreach($right[1] as $v){
			//符合任意通过权限则通过
			$module_name = str_replace(array('.','*'),array('\.','.*'),$v).'(\..*)*';
			if(preg_match("/^$module_name$/",$info)) return true;
		}
		//不符合任意权限则不通过
		return false;
	}

	//获取权限组
	public function getRoleList($array=array()){
		$result=array();
		$result['list']=$this->role->select($array);
		$result['total']=$this->role->count();
		return $result;
	}
	public function getRole($search){
		return $this->role->where($search)->selected('first');
	}

	//获取权限控制列表
	public function getRightList($array=array()){
		$result=array();
		$result['list']=$this->list->select($array);
		$result['total']=$this->list->count();
		return $result;
	}
	public function getRight($search){
		return $this->list->where($search)->selected('first');
	}
	
	//添加权限控制
	public function addRight($array=array()){
		$this->list->add($array);
	}

	//添加角色
	public function addRole($array=array()){
		$this->role->add($array);
	}

	//修改权限控制
	public function editRight($search,$array=array()){
		$this->list->where($search)->edit($array);
	}

	//修改角色
	public function editRole($search,$array=array()){
		$this->role->where($search)->edit($array);
	}
	//删除角色
	public function deleteRole($search){
		if(!empty($search)){
			$this->role->where($search)->delete();
		}
	}

	//删除权限控制
	public function deleteRight($search){
		if(!empty($search)){
			$this->list->where($search)->delete();
		}
	}

}

//权限组
class module_rightroleDB extends FN_layer_sql{
	protected $_table='right_role';
	protected $_field=array('id','name','status');
	protected $_pkey='id';
	protected $_aint=true;
}
//权限表
class module_rightlistDB extends FN_layer_sql{
	protected $_table='right_list';
	protected $_field=array('id','module_name','type','role_id','status');
	protected $_pkey='id';
	protected $_aint=true;
}
?>
