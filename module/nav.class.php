<?php
//导航
class module_nav implements FN__single{
	private static $_Instance = null;
	private $user = null;
	static public function getInstance($array=array()){
		if(!self::$_Instance){
			self::$_Instance = new self($array);
		}
		return self::$_Instance;
	}

	private function __construct($array){
		$this->nav = FN::i('module.nav|DB');
		$this->log = FN::i('module.log|DB');
		
	}
	//设置当前用户
	public function setUser($user){
		$this->user = $user;
	}
	//获取用户导航菜单
	public function getNav($items) {
		$tree = array();
		foreach ($items as $item)
			if (isset($items[$item['pid']]))
				$items[$item['pid']]['children'][] = &$items[$item['id']];
			else
				$tree[] = &$items[$item['id']];
		return $tree;
	}

	//所有菜单
	public function getNavList(){
		$result=array();
		$result['list']=$this->nav->order("ordernum desc")->select();
		return $result;
	
	}
	public function getNavFirst($search){
		$result=array();
		$result=$this->nav->where($search)->selected('first');
		return $result;	
	}

	//添加数据
	public function add($array=array()){
		$result=$this->nav->add($array);

		$pid_arr=$this->nav->where("id={$result['pid']}")->selected('first');
	
		$this->nav->where("id={$result['id']}")->edit($array);
	}

	//删除数据
	public function delete($search){
		if(!empty($search)){
			$this->nav->where($search)->delete();
		}
	}
	public function deleteSortNav($items) {
		$tree = array(); 
		foreach ($items as $item)
			if (isset($items[$item['pid']]))
				$items[$item['pid']]['children'][] = &$items[$item['id']];
			else
				$tree[] = &$items[$item['id']];
		return $tree;
	}

	//修改数据
	public function edit($id,$array=array()){
		return $this->nav->where("id=$id")->edit($array);
	}
}

//导航用户表
class module_navDB extends FN_layer_sql{
	protected $_table='nav';
	protected $_field=array('id','name','pid','url','ordernum');
	protected $_pkey='id';
	protected $_aint=true;

}
?>
