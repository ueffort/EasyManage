<?php
//视图控制器,属于项目内
//会自动加载项目目录下的templates目录
//自动通过view参数执行方法并输出页面
class tools_controller_moduleview extends tools_controllerview{
	protected function _init_before($action){
		//登录
		$user = FN::i('module.user');
		if(!$user->isLogin()){
			$this->_message( array('error'=>'nologin'));//前端页面判断，弹出登录框
		}
		//特殊类型页面，无需权限，如：search（搜索提示），valid（验证提示）
		$type = call_user_func_array(array($this->classname,'getViewAttr'),array($view,'type'));
		if($type) return true;
		//权限
		$right = FN::i('module.right');
		if(!$right->isRight($this->controllname.'.'.$action,'view')){
			$this->_message( array('error'=>'noright'));//前端页面判断，弹出提示框
		}
	}
}

//执行控制器
class tools_controller_modulehandle extends tools_controllerhandle{
	protected function _init_before($action){
		//登录
		$user = FN::i('module.user');
		if(!$user->isLogin()){
			echo json_encode( array('error'=>'nologin'));//前端页面判断，弹出登录框
			exit;
		}
		//权限
		$right = FN::i('module.right');
		if(!$right->isRight($this->controllname.'.'.$action,'handle')){
			echo json_encode( array('error'=>'noright'));//前端页面判断，弹出提示框
			exit;
		}
	}
	protected function _handle_after($result){
		if(empty($result['error'])){
			//日志
			$log = FN::i('module.log');
			$arr=array(
				'log_info'=>$_POST,
				'result'=>$result,
				'module_name'=>$this->controllname.'.'.$action
			);
			$log->add($arr);
		}
	}
}
?>
