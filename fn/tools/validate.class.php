<?php
/**
* 通过验证数组进行验证的设置
* $array = array(
*		'formElementName'=> 'require',//单一验证，并且该验证有默认参数可以使用字符串设置
*		'formElementName'=>array(//组合验证，需要根据验证优先级设置数组顺序，当遇到验证失败后，将不会进行后续验证
*			'required'=>true,//如果验证有默认参数则可以设置true来进行验证，false取消验证
*			'minlength'=>4,//单一验证参数可以用key=>value来进行设置
*			'ranglength'=>array(1,2),//对于有多个验证参数的验证条件，需要通过数组进行参数传递
*		),
* );
*/
class FN_tools_validate implements FN__single{
	static private $_Instance = null;
	static public function getInstance(){
		if(!self::$_Instance){
			self::$_Instance = new self();
		}
		return self::$_Instance;
	}
	public function valid($array,$valid,$message=array()){
		if(empty($valid)){
			return false;
		}
		$list = array();
		foreach($valid as $key=>$rule){
			$element = isset($array[$key]) ? $array[$key] : false;
			$return = $this->validRule($element,$rule,isset($message[$key]) ? $message[$key] : array());
			if($return !== true) $list[$key] = $return;
		}
		return $list;
	}
	public function validRule($element,$rule,$message=array()){
		if(!is_array($rule)) $rule = array($rule=>true);
		foreach($rule as $key=>$value){
			$fun = '__'.$key;
			if(!method_exists($this,$fun)) continue;
			if($value === true){
				$value = array();
			}elseif(!is_array($value)){
				$value = array($value);
			}
			//传递所有规则为参数，用于联合参数判断
			$value[] = $rule;
			array_unshift($value,$element);
			if(!call_user_func_array(array(&$this,$fun),$value)){
				return is_array($message) ? (isset($message[$key]) ? $message[$key] : $key) : $message;
			}
		}
		return true;
	}
	private function __required($element){
		return empty($element) ? false : true;
	}
	private function __url($element){
		return $this->__regular($element,'/(http[s]{0,1}|ftp):\/\/[a-zA-Z0-9\.\-]+\.([a-zA-Z]{2,4})(:\d+)?(\/[a-zA-Z0-9\.\-~!@#$%^&*+?:_\/=<>]*)?/');
	}
	private function __email($element){
		return $this->__regular($element,'/^[0-9a-zA-Z]+@(([0-9a-zA-Z]+)[.])+[a-z]{2,4}$/');
	}
	//只能验证UTF-8格式，判断前需转换编码
	private function __chinese($element){
		return $this->__regular($element,'/^[\x{4e00}-\x{9fa5}]+$/u');
	}
	//判断是否属于该数组
	private function __single($element,$array,$allrule){
		if(empty($element)) return isset($allrule['required']) ? $allrule['required'] : false;
		return in_array($array,$element) ? true : false;
	}
	//判断多选是否都属于该数组,element必须为数组
	private function __multiple($element,$array,$allrule){
		if(empty($element)) return isset($allrule['required']) ? $allrule['required'] : false;
		if(!is_array($element)) return false;
		foreach($element as $key=>$value){
			if(!$this->__single($value,$array,$allrule)) return false;
		}
		return true;
	}
	//判断2个值是否相同,无需像js中实现动态字段判断，因为php是服务器端操作
	private function __equals($element,$target){
		return $element == $target ? true : false;
	}
	//判断是否为数字，type只判断是否正负，结果都包括0，排除0可用required
	private function __num($element,$type='',$regular=''){
		if(empty($regular)) $regular = '\d+(\.\d+)?';
		switch($type){
			case 1://非负数
				$regular = '('.$regular.'|0+)';
				break;
			case 2://非正数
				$regular = '(-'.$regular.'|0+)';
				break;
			default:
				$regular = '-?'.$regular;
		}
		return $this->__regular($element,'/^'.$regular.'$/');
	}
	private function __int($element,$type=''){
		return $this->__num($element,$type,'\d+');
	}
	private function __float($element,$type=''){
		return $this->__num($element,$type,'\d+\.\d+');
	}
	private function __regular($element,$regular){
		if(is_array($element)) return false;
		return preg_match($regular,$element) ? true : false;
	}
	private function __ranglength($element,$min,$max){
		$length = strlen($element);
		return ($length > $max || $length < $min) ? false : true;
	}
	private function __minlength($element,$min){
		return (strlen($element) < $min)  ? false : true;
	}
	private function __maxlength($element,$max){
		return (strlen($element) > $max)  ? false : true;
	}
}
?>
