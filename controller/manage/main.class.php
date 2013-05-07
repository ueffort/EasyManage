<?php
//基本控制器，用于显示登录页面及主控制台，加载视图控制器
class controller_manage_mainview extends tools_controller_manageview{
	protected static $viewList = array(
		'main'=>array('name'=>'主窗体','shortname'=>'主窗体'),
		'login'=>array('name'=>'登录窗体','shortname'=>'登录窗体'),
	);
	//取消初始化前判断
	protected function _init_before($action){
		return true;
	}
	public function main(){
		$user = FN::i('module.user');
		if(!$user->isLogin()){
			$this->_view('login');//更换为登录视图
		}
		$this->template->assign('username',$user->getName());
		//导航信息
		$nav = FN::i('module.nav');
		$nav->setUser($user);
		$navAll=$nav->getNavList();
		$navList=$nav->getNav($navAll['list']);
		if($user->isManager()){
			//添加管理导航信息
			$manageList = array('name'=>'系统管理','children'=>array(
				array('name'=>'用户管理','url'=>'manage.user.index'),
				array('name'=>'权限管理','url'=>'','children'=>array(
					array('name'=>'权限列表','url'=>'manage.right.index'),
					array('name'=>'角色列表','url'=>'manage.right.role'),
					array('name'=>'导航列表','url'=>'manage.nav.index'),
				)),
				array('name'=>'标签管理','url'=>'manage.tag.index'),
				array('name'=>'日志管理','url'=>'manage.log.index'),
				array('name'=>'模块转移','url'=>'manage.devolve.index')
			));
			$navList[] = $manageList;
		}
		$navList = $this->__filterNav($navList);
		$this->template->assign('navlist',$navList);
		$this->template->display('main.html');
		exit;
	}
	private function __filterNav($navList){
		$nav_list = array();
		$right = FN::i('module.right');
		foreach($navList as $key=>$nav){
			$status = true;
			$nav_tmp = array('name'=>empty($nav['name']) ?'' :$nav['name']);
			if(!empty($nav['children'])){
				$nav_tmp['children'] =  $this->__filterNav($nav['children']);
			}
			if(!empty($nav['url']) && strpos('http://',$nav['url']) === false){
				//判断是否有权限
				if($right->isRight($nav['url'],'view')){
					$nav_tmp['url'] = $nav['url'].'.view';
				}else{
					$status = false;
				}
			}elseif(!empty($nav['url'])){
				$nav_tmp['url'] = $nav['url'];
			}else{
				$status = false;
			}
			if(empty($nav_tmp['children']) && !$status) continue;
			$nav_list[] = $nav_tmp;
		}
		return $nav_list;
	}
	public function login(){
		$this->template->display('login.html');
		exit;
	}
}
//处理登录操作
class controller_manage_mainhandle extends tools_controller_managehandle{
	protected static $handleList = array(
		'login'=>array('name'=>'登录','shortname'=>'登录'),
		'logout'=>array('name'=>'注销','shortname'=>'注销')
	);
	public function login(){
		if(empty($_POST['username']) || empty($_POST['password'])) return array('error'=>'empty');
		$user = FN::i('module.user');
		if(!$user->checkLogin($_POST['username'],$_POST['password'])) return array('error'=>'noverify');
		$user->login();
		return array('success'=>true,'username'=>$user->getName());
	}
	public function logout(){
		$user = FN::i('module.user');
		$user->logout();
		return array('success'=>true);
	}
	protected function _init_before($action){
		return true;
	}
}
?>

