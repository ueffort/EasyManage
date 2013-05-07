<?php
/*
 * 在按rest方式提供信息的同时，能通过统一的缓存服务，进行信息的缓存
 */
class FN_layer_api_restcache_server implements FN__single{
	private static $_Instance = null;
	private $class = null;
	private $array = array();
	private $error = false;
	//每次都获取到同一个类
	static public function getInstance($array = array()){
        if(!self::$_Instance){
            self::$_Instance = new self();
        }
        return self::$_Instance;
    }
	public function init($array){
		$this->array = $array;
		return true;
	}
	public function setClass($class){
		$this->class = $class;
	}
	public function start(){
		$this->verify();
		$module = $this->module();
		$param = $this->param();
		if($this->getError()){
			echo json_encode(array('error'=>$this->getError()));
		}else{
			$result = call_user_func_array(array(&$this->class,$module),$param);
			if(!$result){
				echo json_encode(array('error'=>$this->class->getError()));
			}else{
				$data = empty($result['data']) ? array() : $result['data'];
				$freq = empty($this->class->freq) ? -1 : $this->class->freq;
				unset($result['data']);
				echo json_encode(array('freq'=>$freq,'data'=>$data,'error'=>$this->class->getError(),'other'=>$result));
			}
		}
		exit;
	}
	public function getError(){
		return $this->error;
	}
	private function verify(){
		if(empty($_GET['access_token']) || $_GET['access_token'] != '51xiaoguo.com') {
			$this->error = 1;//验证失败
			return false;
		}
	}
	private function param(){
		if(empty($_GET['action'])){
			$this->error = 3;//直接参数不存在
		}
		$get = $_GET;
		unset($get['action']);
		unset($get['module']);
		unset($get['access_token']);
		$array = array_merge($get,$this->parseParam($_GET['action']));
		return array($array);
	}
	private function module(){
		if(empty($_GET['module'])){
			$this->error = 2;//调用模块不存在
		}
		return $_GET['module'];
	}
	static public function parseParam($param,$split = '/'){
		if(empty($param)) return array();
		$paramarr = array();
		$sarr = explode($split,$param);
		if(empty($sarr[0])) array_shift($sarr);
		$len = count($sarr);
		if(empty($sarr)) return $paramarr;
		if($len == 1){
			return $sarr;
		}elseif($len%2 != 0){
			$sarr = array_slice($sarr, 0, -1);
		}
		for($i=0; $i<=$len; $i=$i+2) {
			if(isset($sarr[$i+1])) $paramarr[$sarr[$i]] = addslashes(str_replace(array('/', '\\'), '', rawurldecode(stripslashes($sarr[$i+1]))));
		}
		return $paramarr;
	}
}
?>