<?php
//视图控制器,属于项目内
//会自动加载项目目录下的templates目录
//自动通过view参数执行方法并输出页面
class tools_controller_manageview extends tools_controllerview{
	protected function _init_before($action){
		//登录
		$user = FN::i('module.user');
		if(!$user->isLogin()){
			$this->_message(array('error'=>'nologin'));//前端页面判断，弹出登录框
		}
		//权限
		if(!$user->isManager()){
			$this->_message( array('error'=>'nomanage'));//非管理员，弹出提示框
		}
	}
}

//执行控制器
class tools_controller_managehandle extends tools_controllerhandle{
	protected function _init_before($action){
		//登录
		$user = FN::i('module.user');
		if(!$user->isLogin()){
			echo json_encode( array('error'=>'nologin'));//前端页面判断，弹出登录框
			exit;
		}
		if(!$user->isManager()){
			echo json_encode( array('error'=>'nomanage'));//非管理员，弹出提示框
			exit;
		}
		//权限
	}
}
?>
