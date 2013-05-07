<?php
//标签列表
class controller_manage_tag_listhandle extends tools_controller_modulehandle{
	protected function _init_before($action){
		//登录
		$user = FN::i('module.user');
		if(!$user->isLogin()){
			$this->_message( array('error'=>'nologin'));//前端页面判断，弹出登录框
		}
		if(empty($_POST['module_name'])) $this->_message( array('error'=>'noparam'));
		//权限
		//需要该模块的顶部操作权限
		$right = FN::i('module.right');
		if(!$right->isRight($_POST['module_name'],'handle')){
			$this->_message( array('error'=>'noright'));//前端页面判断，弹出提示框
		}
	}
	//目前一条数据只能设置一个标签，主要是出于展示原因无法处理多个标签性质的显示
	//所以只有添加即可，自动删除并添加新标签
	//删除只需module_name和target_id即可
	protected static $handleList = array(
		'add'=>array('name'=>'添加标签','shortname'=>'添加','param'=>array('module_name','target_field','tag_id')),
		'delete'=>array('name'=>'取消标签','shortname'=>'取消','param'=>array('module_name','target_field'))
	);
	public function add(){
		$tag = FN::i('module.tag');
		if(empty($_POST[$_POST['target_field']])) return array('error'=>'noparam');
		$info = $tag->addTag($_POST['module_name'],$_POST[$_POST['target_field']],$_POST['tag_id']);
		return array('success'=>'success');
	}
	public function delete(){
		$tag = FN::i('module.tag');
		if(empty($_POST[$_POST['target_field']])) return array('error'=>'noparam');
		$tag->deleteTag($_POST['module_name'],$_POST[$_POST['target_field']]);
		return array('success'=>'success');
	}
}
?>

