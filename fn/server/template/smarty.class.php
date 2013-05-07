<?php
include_once(dirname(__FILE__).'/smarty/SmartyBC.class.php');
class FN_server_template_smarty{
	private static $_Default = array(
			'compile_check'=>true,
			'caching'=>false,
			'compile_dir'=>'$template/',//默认为项目路径
			'cache_dir'=>'$cache/',
			'left_delimiter'=>'<!--{',
			'right_delimiter'=>'}-->'
	);
	public static function initServer($config){
		$smarty = new SmartyBC();
		$config = array_merge(self::$_Default,$config);
		if($config['compile_dir'] == self::$_Default['compile_dir']) $config['compile_dir'] .= md5($config['template_dir']).'/';
		if($config['cache_dir'] == self::$_Default['cache_dir']) $config['cache_dir'] .= md5($config['template_dir']).'/';
		FN::setKey($config,$config,true);
		foreach($config as $key=>$value){
			$smarty->$key = $value;
		}
		return $smarty;
	}
}
?>