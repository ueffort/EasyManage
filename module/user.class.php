<?php
//管理后台用户模块
class module_user implements FN__single{
	protected static $_Instance = null;
	protected $user = null;
	private $list = null;
	private $relateClass = null;
	private $className = null;
	static private $_LOGINCLASS = 'login';//对登录用户做特殊处理，绑定用户登录类
	
	static public function getInstance($array=array()){
		if(empty($array)||!is_array($array)){
			$object = FN::i('module.user.'.self::$_LOGINCLASS,$array);
		}else{
			$object = new self($array);
		}
		$object->className = FN::callName(__CLASS__);
		//设定关联子类
		$object->setRelate(FN::callName(get_called_class()));
		return $object;
	}
	private function __construct($array=array()){
		$this->init();
		if(!empty($array['uid'])){
			$this->_setUser($array['uid'],'uid');
		}elseif(!empty($array['username'])){
			$this->_setUser($array['username'],'username');
		}
	}
	protected function init(){
		$this->list = FN::i('module.user|listDB');
	}
	public function setRelate($class){
		if($class == $this->className) return false;
		if(empty($this->classArray[$class])){
			$className = FN::i($class);
			$this->classArray[$class] = new $className();
			$this->classArray[$class]->user = &$this->user;
		}
		unset($this->relatedClass);
		$this->relateClass = &$this->classArray[$class];
	}
	public function __call($fname,$argus){
		return call_user_func_array(array(&$this->relateClass,$fname),$argus);
	}
	//获取当前用户信息
	public function getUser(){
		return $this->user;
	}
	public function getUid(){
		return empty($this->user['uid']) ? 0 : $this->user['uid'];
	}
	public function getName(){
		return empty($this->user['username']) ? 0 : $this->user['username'];
	}
	public function getPass(){
		return empty($this->user['password']) ? false : $this->user['password'];
	}
	public function editPass($old_password,$password,$forget=false){
		return $this->editUser($old_password,$password,'',$forget);
	}
	public function editEmail($email){
		return $this->editUser('','',$email,true);
	}
	public function editUser($oldpassword,$newpassword,$email='',$ignorepassword=false){
		if($email == $this->user['email']) $email = '';
		$array = array();
		if(!$ignorepassword){
			$info = $this->list->where(array('id'=>$this->getUid()))->selected('first');
			if(!$info || $info['password'] != $oldpassword) return false;
		}
		if($email) $array['email'] = $email;
		if($newpassword) $array['password'] = $newpassword;
		$info = $this->list->where(array('uid'=>$this->getUid()))->edit($array);
		if(!$info) return false;
		if($newpassword)$this->user['password'] = $newpassword;
		if($email) $this->user['email'] = $email;
		$this->_updateHook();
		return true;
	}
	//判断用户是否有管理权限
	public function isManager(){
		return $this->user['is_manage'];
	}
	//获取所有用户
	public function getUserList($uid_array,$field='uid'){
		return $this->list->where(array($field=>$uid_array))->select();

	}
	//获取指定用户
	public function getUserOne($id,$field='uid'){
		if(empty($id)) return array();
		$list = $this->getUserList(array($id),$field);
		list($list) = array_slice($list,0,1);
		return $list;
	}
	//全局用户数据存储
	public function setValue($key,$value){
		$sql = 'replace into `user_value` (`uid`,`key`,`value`)values("'.$this->getUid().'","'.$key.'","'.FNbase::setEscape(serialize($value),true).'")';
		$db = XG::getMysql();
		$db->query($sql);
	}
	public function getValue($key){
		$sql = 'select value from `user_value` where `uid`="'.$this->getUid().'" and key="'.$key.'"';
		$db = XG::getMysql();
		$row = $db->getOne($sql);
		return unserialize($row);
	}
	//切换用户
	protected function _setUser($uid,$field='uid'){
		$this->_clearUser();
		$this->user = $this->getUserOne($uid,$field);
		return true;
	}
	//更新用户信息：数据库内的数据是完整的，用数据库内的数据替换缓存数据
	protected function _getUser(){
		if(!$this->getUid()) return false;
		$userInfo = $this->getUserOne($this->getUid());
		$this->user = array_merge($this->user,$userInfo);
		$this->_updateHook();
		return true;
	}
	//清楚用户信息
	protected function _clearUser(){
		$this->user = array();
		return true;
	}
	//更新用户信息时候调用的函数，用于子类扩展
	protected function _updateHook(){
		return true;
	}
	//添加用户
	public function add($array=array()){
		return $this->list->add($array);
	}
	//修改用户
	public function edit($uid,$array=array()){
		return $this->list->where(array('uid'=>$uid))->edit($array);
	}
	//删除用户
	public function delete($uid){
		return $this->list->where(array('uid'=>$uid))->delete();
	}
	//用户列表
	public function getList($array=array(),$search=array()){
		$result=array();
		$result['list']=$this->list->where($search)->select($array);
		$result['total']=$this->list->where($search)->count();
		return $result;
	}

}
//用户表
class module_userlistDB extends FN_layer_sql{
    //对应的数据库字段
    protected $_table='user';
	protected $_field=array('uid','username','password','role_id','is_manage','status');
    protected $_pkey='uid';
    protected $_aint=true;
}
 
?>
