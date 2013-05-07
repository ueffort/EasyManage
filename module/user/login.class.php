<?php
//登录用户
class module_user_login extends module_user{
	static private $_SelfInstance = null;
	static private $_Cookie = array();
	private $login = false;
	private $session = null;
	private $time = 0;
	private $userCache = array();
	private $cacheTime = 86400;
	private $st = null;
	private $isCache = true;
	static public function getInstance($cookie=array()){
		if(empty(self::$_Cookie)){
			self::$_Cookie = FN::getConfig('cookie');
		}
		if(!self::$_SelfInstance){
			self::$_SelfInstance = new self($cookie);
		}
		return self::$_SelfInstance;
	}
	private function __construct($cookie=''){
		$this->init();
		$this->st = FN::i('module.user.login|sessionDB');
		$this->checkCookie($cookie);
		$this->setSession();
	}
	protected function _updateHook(){
		parent::_updateHook();
		$this->setSession();
	}
	public function visit(){
		$this->user['visittime'] = FNbase::getTime();//设置最新访问时间
		$this->setSession();
	}
	public function isLogin(){
		return empty($this->login) ? false : $this->login;
	}
	public function checkLogin($username,$password){
		$user = $this->getUserOne($username,'username');
		if(!$user || $user['password'] != $password) return false;
		$this->_clearUser();
		$this->user = $user;
		return array('success'=>'success');
	}
	//通过传递一个user类来自动更新用户信息
	public function setUser($user){
		$this->user = $user->user;
	}
	//获取当前用户信息
	public function getUser(){
		if($this->isCache){
			$this->_getUser();
			$this->isCache = false;
		}
		return $this->user;
	}
	public function logout(){
		$this->setSession(true);
		return true;
	}
	//只能通过register和checkLogin和setUser来设置当前新的登录用户信息
	public function login($time=0){
		if(!$this->getUid()) return false;
		//设置实际登录时间
		$this->time = $time ? FNbase::getTime() + $time : 0;
		//不做存储，但在读取cookie时会根据实际登录设置重新计算，所有cache操作都是使用该时间
		$this->cacheTime = $this->time ? $this->time - FNbase::getTime() : 86400;
		//每次登录，更新session
		$this->st->where('inserttime + cachetime > '.FNbase::getTime())->delete();
		$this->setSession();
		return true;
	}
	//获取到的值用来判断登陆状态
	public function getKey(){
		return empty($_COOKIE[self::$_Cookie['key']]) ? '':$_COOKIE[self::$_Cookie['key']];
	}
	//用来做短时间内的用户标识符，不可在http协议内传递，只允许程序内部调用
	public function getSessionId(){
		return $this->session;
	}
	public function getCookie(){
		if($this->login){
			$cookie = FNbase::authcode($this->user['uid']."\t".$this->user['username']."\t".$this->session."\t".$this->time, 'ENCODE',self::$_Cookie['pass']);
		}else{
			$cookie = FNbase::authcode("\t\t".$this->session."\t0\t\t");
		}
		$cookie = str_replace('%2','@2',urlencode($cookie));
		return $cookie;
	}
	protected function checkCookie($cookie=''){
		if(!empty($this->login) && $this->login == 'session') return true;
		if(empty($cookie) && empty($_COOKIE[self::$_Cookie['key']])) return false;
		$cookie = $cookie ? $cookie : $_COOKIE[self::$_Cookie['key']];
		$cookie = urldecode(str_replace('@2','%2',$cookie));
		list($uid,$username,$session,$time) = explode("\t", FNbase::authcode($cookie,'DECODE',self::$_Cookie['pass']));
		$this->session = $session;
		if(empty($uid)||empty($username)) return false;
		$this->user['uid'] = $uid;
		$this->user['username'] = $username;
		$this->time = $time;
		$this->cacheTime = $this->time ? $this->time - FNbase::getTime() : 86400;
		$this->login = 'cookie';
		$this->isCache = true;
		$this->checkSession();
		return true;
	}
	protected function setCookie($delete=false){
		if($delete){
			$time = FNbase::getTime()-86400;
			setcookie(self::$_Cookie['key'],'', $time,self::$_Cookie['path'],self::$_Cookie['domain']);
		}else{
			$cookie = $this->getCookie();
			setcookie(self::$_Cookie['key'],$cookie, $this->time,self::$_Cookie['path'],self::$_Cookie['domain']);
			return $cookie;
		}
		return true;
	}
	protected function checkSession(){
		if(empty($this->session)) return false;
		//$cache = FN::server('cache','session');
		//$info = $cache->get($this->session);
		$info = $this->st->field('value')->where(array('session'=>$this->session,'inserttime'=>'>'.FNbase::getTime()-$this->cacheTime),true)->selected('one');
		if(empty($info)) return false;
		$this->user=json_decode($info,true);
		if(isset($this->user['password']) && $this->user['password']) $this->login = 'session';
		return true;
	}
	protected function setSession($delete=false){
		//$cache = FN::server('cache','session');
		if($delete){
			//$cache->delete($this->session);
			$this->st->delete($this->session);
		}else{
			if(empty($this->user)) return false;
			if(empty($this->session)) $this->session = FNbase::random(10);//10位的随机性已经足够
			$this->st->add(array('session'=>$this->session,'value'=>FNbase::setEscape(json_encode($this->user),true),'inserttime'=>FNbase::getTime(),'cachetime'=>$this->cacheTime),true);
			//$cache->set($this->session,json_encode($this->user),false,$this->cacheTime);
			if(isset($this->user['password']) && $this->user['password']) $this->login = 'session';
		}
		$this->setCookie($delete);
		return true;
	}
	//永久用户数据
	//登录用户存储永久用户数据，非登录用户存储零时数据
	public function setValue($key,$value){
		if($this->isLogin()){
			return parent::setValue($key,$value);
		}else{
			return $this->setCache($key,$value);
		}
	}
	//如果登录中的永久数据不存在，则获取零时数据
	public function getValue($key){
		if($this->isLogin()){
			$result = parent::getValue($key);
			//如果永久数据中不存在，则读取未登录状态下的会话数据（零时数据），并存入永久数据中
			if($result) return $result;
			$value = $this->getCache($key);
			parent::setValue($value);
			return $value;
		}
		return $this->getCache($key);
	}
	//缓存信息
	//读取时间需和存储时间一致
	//如果不设定时间或时间设置为0，则跟随session时间存储，是会话变量
	//如果设定跟随uid存储，未登录则跟随session存储
	public function setCache($key,$value,$time=0,$force=false){
		if($time){
			$uid = $this->getUid();
			if(!$uid){
				if($force) return false;
				$uid = $this->session;
			}
			$key = $uid.'/'.$time.'/'.$key;
		}else{
			$time = $this->cacheTime;
			$key = $this->session.'/'.$key;
		}
		return $this->_setCache($key,$value,$time);
	}
	//读取时间需和存储时间一致
	public function getCache($key,$time=0,$force=false){
		if($time){
			$uid = $this->getUid();
			if(!$uid){
				if($force) return false;
				$uid = $this->session;
			}
			$key_tmp = $uid.'/'.$time.'/'.$key;
		}else{
			$key_tmp = $this->session.'/'.$key;
		}
		$value = $this->_getCache($key_tmp);
		
		//登录用户对零时性数据如果不存在，则再次获取未登录状态数据，并存入登录状态的零时性数据中
		if(!$value && $time && !$force){
			$value = $this->_getCache($this->session.'/'.$time.'/'.$key);
			if($value) $this->setCache($key,$value,$time);
		}
		return $value;
	}
	private function _setCache($key,$value,$time){
		$cache = FN::server('cache');
		$this->userCache[$key] = $value;
		return $cache->set('user/'.$key,serialize($this->userCache[$key]),false,$time);
	}
	private function _getCache($key){
		if(empty($this->userCache) || empty($this->userCache[$key])){
			$cache = FN::server('cache');
			$this->userCache[$key] = unserialize($cache->get('user/'.$key));
		}
		return $this->userCache[$key];
	}
}
//用户表
class module_user_loginsessionDB extends FN_layer_sql{
    //对应的数据库字段
    protected $_table='session';
	protected $_field=array('session','value','inserttime','cachetime');
    protected $_pkey='session';
}
?>