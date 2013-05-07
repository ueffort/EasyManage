<?php
/*
 * 系统是建立在module_name上，当开发过程中移动了的模块，需要重构在数据库中的关系
 * 通过统一的脚本执行，转移会转移所有该模块及该模块下的子模块，请注意操作
 */
class module_devolve implements FN__single{
	private static $_Instance = null;
	private $user=null;
	static public function getInstance($array=array()){
		if(!self::$_Instance){
			self::$_Instance = new self($array);
		}
		return self::$_Instance;
	}
	private function __construct($array){
		$this->nav = FN::i('module.nav|DB');
		$this->log = FN::i('module.log|DB');
		$this->right_list = FN::i('module.right|listDB');
		$this->tag = FN::i('module.tag|DB');
		$this->taglist = FN::i('module.tag|listDB');
	}
	
	//批量替换url
	public function multiUpdate($array=array()){
		//批量替换
		$old_module_name=$array['old_module_name'];
		$new_module_name=$array['module_name'];
		
		if($array['type']==1){//权限
			$data=array('module_name'=>"=replace (module_name,'$old_module_name','$new_module_name')");
			$this->right_list->where("module_name like '$old_module_name%' ")->edit($data);
		}else if($array['type']==2){//导航
			$data=array('url'=>"=replace (url,'$old_module_name','$new_module_name')");
			$this->nav->where("url like '$old_module_name%'")->edit($data);
			//更新日志
			$data=array('module_name'=>"=replace (module_name,'$old_module_name','$new_module_name')");
			$this->log->where("module_name like '$old_module_name%'")->edit($data);
		}else if($array['type']==3){//日志
			$data=array('module_name'=>"=replace (module_name,'$old_module_name','$new_module_name')");
			$this->log->where("module_name like '$old_module_name%'")->edit($data);
		}else if($array['type']==4){//标签
			$data=array('module_name'=>"=replace (module_name,'$old_module_name','$new_module_name')");
			$this->tag->where("module_name like '$old_module_name%'")->edit($data);
			$data=array('module_name'=>"=replace (module_name,'$old_module_name','$new_module_name')");
			$this->taglist->where("module_name like '$old_module_name%'")->edit($data);
		}
	}
}
?>
