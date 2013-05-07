<?php
/*
 * 简易的权限机制，使用位操作，速度快，可以同时判断多个权限
 * 通过一个字段可以实现多个权限的设置
 */
class FN_tools_right{
	private static $_RightArray = array();
	//设置全局权限列表数组
	public static function setRightArray($rightArrayAll){
		self::$_RightArray = $rightArrayAll;
	}
	//将拥有的权限转换成值，用于存储
	public static function value2int($right,$rightArrayAll=array()){
		if(empty($rightArray)) return 0;
		if(empty($rightArrayAll)) $rightArrayAll = self::$_RightArray;
		return 1<<(array_search($right,$rightArrayAll));
	}
	//array2int($right,array('read','edit'));
	public static function array2int($rightArray,$rightArrayAll=array()){
		if(empty($rightArray)) return 0;
		if(empty($rightArrayAll)) $rightArrayAll = self::$_RightArray;
		$int = 0;
		foreach($rightArray as $value){
			$int +=1<<(array_search($value,$rightArrayAll));
		}
		return $int;
	}
	//将值匹对获取拥有的权限
	public static function int2array($int,$rightArrayAll=array()){
		if(empty($rightArrayAll)) $rightArrayAll = self::$_RightArray;
		$array = array();
		$len = strlen(decbin($int));
		for($i=0;$i<$len;$i++){
			if($int & (1<<$i)) $array[] = $rightArrayAll[$i];
		}
		return $array;
	}
	//用于判断权限是否存在，可以传递多个所需权限，只有全部符合才会返回true，否则为false
	public static function judge($int,$right,$rightArrayAll=array()){
		if(!is_array($right)) $right = array($right);
		$int_need = self::array2int($right,$rightArrayAll);
		return ($int & $int_need) == $int_need;
	}
	/*
	 * 将数据库的字段及需要判断的值传入
	 * $value，$field存储的值只能是由value2int和array2int转换的值
	 */
	static function int1sql($int,$field){
		return ' (`'.$field.'` & '.$int.') = '.$int.' ';
	}
}
?>