<?php
class FN_tools_ajax implements FN__single{
	static private $_Instance = null;
	private $array = array();
	private $classArray = array();
	private $domain = null;
	static public function getInstance($array = array()){
		if(!self::$_Instance){
			self::$_Instance = new self();
		}
		//加入关系
		self::$_Instance->addClass(get_called_class());
		return self::$_Instance;
	}
	//可以是类名或者是实际类
	static public function addClass($class){
		switch(gettype($class)){
			case 'object':
				$className = get_class($class);
				break;
			case 'string':
				$className = $class;
				$class = new $className();
				break;
			default:return false;
		}
		self::$_Instance->classArray[$className] = $class;
		$domain = empty($class->domain) ? '__DEFAULT__':$class->domain;
		//合并可操作接口
		self::$_Instance->array[$domain] = array_merge(empty(self::$_Instance->array[$domain]) ? array() : self::$_Instance->array[$domain],$class->array);
		self::$_Instance->domain = $domain;
		return true;
	}
	//每个类都被实例化，无静态方法
	static public function __callstatic($fname,$argus){
		return self::$_Instance->__call($fname,$argus);
	}
	public function __call($fname,$argus){
		$class = null;
		foreach($this->classArray as $key=>$classI){
			if($classI->domain != self::$_Instance->domain) continue;
			if(!method_exists($classI, $fname)) continue;
			$class = $classI;
		}
		if(!$class) return false;
		return call_user_func_array(array(&$class,$fname),$argus);
	}
	static public function returnInfo($array){
		if(empty($_GET['callback'])){
			echo json_encode($array);
		}else{
			echo $_GET['callback'].'('.json_encode($array).')';
		}
		exit;
	}
	static public function setDomain($domain){
		return self::$_Instance->domain = $domain;
	}
	static public function load($ac){
		if(empty($ac) || !isset(self::$_Instance->array[self::$_Instance->domain][$ac])) return false;
		$ac_array = self::$_Instance->array[self::$_Instance->domain][$ac];
		if(empty($ac_array) || $ac_array[0] == 'self'){
			$info = self::$_Instance->$ac();
		}elseif($ac_array[0] == 'module'){
			$function = $ac_array[2];
			$class = XG::M($ac_array[1]);
			if(empty($ac_array[3])){
				$info = $class->$function();
			}else{
				$eval_string = implode(',',$ac_array[3]);
				eval('$info = $class->$function('.$eval_string.');');
			}
			$ac_array[4] = empty($ac_array[4]) ? '' : $ac_array[4];
			switch($ac_array[4]){
				case 'boole':$info =  $info ? 1 : 0; break;
				case 'int':$info = intval($info); break;
				case 'callback':$info = self::$_Instance->$ac($info);break;
				case 'string':
				case 'array':
				default:
			}
		}
		self::returnInfo($info);
	}
}
?>
