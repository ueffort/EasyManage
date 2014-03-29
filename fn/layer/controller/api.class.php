<?php
//远程方法调用控制器
//会自动加载将继承类的所有方法
//自动通过webservice参数获取服务类型
class FN_layer_controller_api implements FN__single{
	protected $template = null;
	protected $tplfile = null;
	protected $error = null;
	static public function getInstance($array){
		if(!empty($array['api'])){
			$server = FN::F('layer.api.'.$array['api'].'.server');
			if(empty($server)) return false;
			$class = get_called_class();
			$server->setClass(new $class);
			$server->init($array);
			$server->start();
			return true;
		}
		return false;
	}
	public function getError(){
		return $this->error;
	}
}
?>
