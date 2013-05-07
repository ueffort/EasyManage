<?php
//视图控制器,属于项目内
//会自动加载项目目录下的templates目录
//自动通过view参数执行方法并输出页面
class FN_layer_controller_view implements FN__single{
	static private $_Instance = null;
	protected $template = null;
	protected $tplfile = null;
	protected $_link = 'default';//设定模版工具
	protected $param = array();//参数变量
	protected $default = array();//全局变量
	protected $dir = '';//分割目录
	static public function getInstance($array=array()){
		$class = get_called_class();
		return new $class($array);
	}
	private function __construct($array){
		$this->template = FN::server('template',$this->_link);
		if(defined('WEB_PATH')){
			//如果是web访问，传递路径变量
			$this->template->assign('url',WEB_PATH);
			if($this->dir){
				$this->template->assign('dir',$this->dir);
				$this->template->assign('static',WEB_PATH.'static/'.$this->dir);
			}else{
				$this->template->assign('static',WEB_PATH.'static/');
			}
		}
		
		$this->ajax = FNbase::isAJAX();
		$this->param = empty($array['param']) ? array() : $array['param'];
		$this->default = $array;
		$this->_init();
		$this->_view(empty($array['view']) ? '' : $array['view']);
	}
	//供继承类扩展，用于初始化该控制器
	protected function _init(){
		return true;
	}
	//供继承类扩展，默认视图
	protected function _default(){
		return true;
	}
	//供继承类扩展，用于进行页面输出
	protected function _view($view = '_default'){
		$this->tplfile = $this->dir.$view;
		if(method_exists($this,$view)){
			$result = $this->$view();
		}
		if($this->tplfile && file_exists(PROJECT_PATH."templates/".$this->tplfile.'.html')){
			if($this->ajax){
			
			}else{
				$this->template->display(PROJECT_PATH."templates/".$this->tplfile.'.html');
			}
		}elseif($this->ajax){
			echo json_encode($result);
			exit;
		}
	}
}
?>
