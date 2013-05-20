<?php
//框架路径
define('FN_FRAME_PATH',dirname(__FILE__).'/');
//框架类前缀
define('FN_FRAME_PREFIX','FN');
//框架所支持的类文件后缀
define('FN_FRAME_SUFFIX','.class.php');
if(empty($_SERVER['argc'])){
	$default_port = array('http'=>80,'https'=>443);
	if(empty($_SERVER['REQUEST_SCHEME'])){
		$_SERVER['REQUEST_SCHEME'] = array_search($_SERVER['HTTP_HOST'],$default_port);
		if(empty($_SERVER['REQUEST_SCHEME'])) $_SERVER['REQUEST_SCHEME'] = 'http';
	}
	//当前访问的web路径
	define('FN_WEB_PATH',$_SERVER['REQUEST_SCHEME'].'://'.$_SERVER['HTTP_HOST'].($default_port[$_SERVER['REQUEST_SCHEME']] == $_SERVER['SERVER_PORT'] ? '':':'.$_SERVER['SERVER_PORT']).FNbase::getBaseUri());
}else{
	//当前为控制器操作
	define('FN_CONSOLE',true);
}
//入口文件所在的系统路径
define('FN_SYSTEM_PATH',dirname($_SERVER['SCRIPT_FILENAME']).'/');
//用于满足框架自身工具类的正常使用
FN::setConfig(
	array(
		'autoCode'=>'freedomnature'
		,'charset'=>'UTF-8'
	)
	,'global'
);
//自动加载类
if(false === spl_autoload_functions() && function_exists('__autoload')) spl_autoload_registe('__autoload',false);
spl_autoload_register(array('FN', 'loadClass'));
class FN{
	private $config = array();//全局配置设定，按每个主项目进行划分
	private static $_FileSpace = array();
	private static $_InitProject = false;
	private static $_Instance = null;
	private static $_Frame = null;
	//存储自身映射关系，用作全局对象管理
	protected static $_Map = array();
	protected static $_Server = array();
	private function __clone() {}
	private function __construct() {}
	/**
	 * 初始化项目
	 * 传递项目路径或默认为入口文件所在文件路径
	 * 项目全局只能初始化一次
	 * @param string $project  项目名称
	 * @param string $path 项目所在路径，如果不设置，默认为项目入口文件所在路径
	 */
	static public function initProject($path = ''){
		if(self::$_InitProject) return true;
		define('FN_PROJECT_PATH',$path ? $path : FN_SYSTEM_PATH);//项目路径
		//初始化云服务
		$cloud = self::getConfig('cloud');
		if(isset($cloud['platform'])) self::$_Instance = self::F('cloud.'.$cloud['platform']);
		if(!self::$_Instance) self::$_Instance = self::getFrame();
		self::$_InitProject = true;
	}
	/**
	 * 获取基础服务:任何可由其他计算机单独完成的任务
	 * @param string $servername  调用的服务名称
	 * @param string $servername  映射名称，默认为default
	 */
	static public function server($servername,$link='default'){
		if(!isset(self::$_Server[$servername][$link])){
			$config = self::serverConfig($servername,$link);
			//将服务与实际驱动分割，实现控制管理
			$config['drive'] = empty($config['drive'])?$servername : $config['drive'];
			if(empty(self::$_Server[$servername])) self::$_Server[$servername] = array();
			self::$_Server[$servername][$link] = self::getInstance()->_server($servername,$config);
		}
		return self::$_Server[$servername][$link];
	}
	protected function _server($servername,$config){
		switch($config['drive']){
			case 'memcache'://cache
				$class = new Memcache();
				$class->connect($config['host'], $config['port']) or die ("Could not connect");
				return $class;
			case 'mongodb':
				if (class_exists("MongoClient")) {
					$class = 'MongoClient';
				} else {
					$class = 'Mongo';
				}
				$options = array();
				if(!empty($config['user']) && !empty($config['pass'])){
					$options = array('username'=>$config['user'],'password'=>$config['pass']);
				}
				$class = new $class("mongodb://".$config['host'].":".$config['port'],$options);
				return $class;
		}
		$class_name = self::F('server.'.$servername.($servername == $config['drive'] ? '' : '.'.$config['drive']));
		return call_user_func_array(array($class_name,'initServer'),array($config));
	}
	/**
	 * 获取基础服务的配置信息，方便内部高阶服务调用基础配置
	 * @param string $servername  调用的服务名称
	 * @param string $servername  映射名称，默认为default
	 */
	static public function serverConfig($servername,$link='default'){
		return self::getConfig($servername.'/'.$link);
	}
	/**
	 * 用静态方式获取框架
	 */
	static private function getFrame() {
		if(empty(self::$_Frame)) self::$_Frame = new self();
		return self::$_Frame;
	}
	/**
	 * 该方法实现扩展类的返回
	 */
	static public function getInstance(){
		if(!self::$_InitProject) return self::getFrame();
		return self::$_Instance;
	}
	/**
	 * 设置全局配置信息
	 * @param array $config
	 * @param string $string
	 * @return array or string
	 */
	static public function setConfig($config,$string=''){
		return self::getFrame()->_setConfig($config,$string);
	}
	/**
	 * 获取全局配置信息
	 * @param string $string
	 * @return array or string
	 */
	static public function getConfig($string=''){
		return self::getFrame()->_getConfig($string);
	}
	/**
	 * 全局存储映射关系，一层关系，可以存储对象，单一调用接口
	 */
	static public function map($key,$value=''){
		if(empty($value)) return empty(self::$_Map[$key]) ? null : self::$_Map[$key];
		return self::$_Map[$key] = $value;
	}
	/**
	 * 项目扩展类调用
	 * 以项目目录为调用目录结构tools.controller.view  =>  FN_PROJECT_PATH/tools/controller/view  =>  tools_controller_view
	 */
	static public function i($class,$array=array()){
		if(!self::$_InitProject) return true;
		return self::C(FN_PROJECT_PATH,$class,$array);
	}
	/**
	 * 框架类调用
	 * 以工具目录为调用目录结构controller.view  =>  FN_FRAME_PATH/controller/view  =>  FN_tools_view
	 */
	static public function F($class,$array=array()){
		return self::C(FN_FRAME_PATH,FN_FRAME_PREFIX.'.'.$class,$array,FN_FRAME_PREFIX);
	}
	/**
	 * 统一类文件调用
	 */
	static private function C($class_path,$name,$array,$domain_path=false){
		list($class_name,$fname,$path) = self::parseName($name);
		//开启自动加载类，减少调用
		if(!class_exists($class_name)){
			return false;
		}
		//if(!self::loadFile($fname.FN_FRAME_SUFFIX,$class_path.($domain_path ? substr($path,strlen($domain_path)+1) : $path))) return false;
		$Reflection = new ReflectionClass($class_name);
		$interface = $Reflection->getInterfaceNames();
		if(empty($interface)){
			//直接跳过下列判断
		}elseif(in_array('FN__single',$interface)){//定义single接口,object instanceof class,parents class,interface
			//return $class_name::getInstance($array);
			return call_user_func_array(array($class_name,'getInstance'),array($array));
		}elseif(in_array('FN__factor',$interface)){//定义factor接口
			//return $class_name::factor($array);
			return call_user_func_array(array($class_name,'factor'),array($array));
		}elseif(in_array('FN__auto',$interface)){//定义auto接口
			return new $class_name($array);
		}
		return $class_name;
	}
	/**
	 * 加载类文件，方便延迟加载的调用
	 * 框架内不写成子类互相调用，子类只给内部调用
	 */
	static public function loadClass($class_name){
		if(substr($class_name,0,strlen(FN_FRAME_PREFIX)+1) == FN_FRAME_PREFIX.'_'){
			$path = FN_FRAME_PATH;
			$file_name = substr($class_name,strlen(FN_FRAME_PREFIX)+1);
		}else{
			$path = FN_PROJECT_PATH;
			$file_name = $class_name;
		}
		$file_name = $path.str_replace('_','/',$file_name).FN_FRAME_SUFFIX;
		if(!self::loadFile($file_name) && $path == FN_PROJECT_PATH){
			//子类判断,框架内不判断
			$path = dirname($file_name);
			if(!is_dir($path)) return false;
			$dir = dir($path);
			$success = false;
			$last_name = self::lastName($class_name);
			$len = strlen(FN_FRAME_SUFFIX);
			//加载所有前部名称相同的类文件
			//注：单目录中的文件数量，含义相同的文件数量都是性能问题
			while (($file = $dir->read()) !== false){
				if($file == '..' || $file== '.') continue;
				$name = substr($file,0,-$len);
				if(substr($last_name,0,strlen($name)) != $name) continue;
				self::loadFile($file,$path);
				if(class_exists($class_name,false)){
					$success = true;
					break;
				}
			}
			$dir->close();
			return $success;
		}else{
			return class_exists($class_name,false);
		}
	}
	/**
	 * 用于类完整的调用名返回
	 * child设置同类文件的子类名，用于区分该类是否是子类
	 * showchild开关，默认打开，用于处理返回值内是否带有子类的调用
	 * 完整的调用名，传递类名即可实现自动调用类
	 */
	static public function callName($className,$child='',$showchild=true){
		$lastName = self::lastName($className,$child,$showchild);
		$fatherName = str_replace('_','.',substr($className,0,strrpos($className,'_')));
		return $fatherName.'.'.$lastName;
	}
	/**
	 * 用于类最后的调用名返回
	 * child设置同类文件的子类名，用于区分该类是否是子类
	 * showchild开关，默认关闭，用于处理返回值内是否带有子类的调用
	 *
	 * 如果类名按类型划分，通过该函数可以获取该类的文件名，通过传递类名即可实现类型划分
	 */
	static public function lastName($className,$child='',$showchild=false){
		$pos = strrpos($className,'_');
		if($pos) $className = substr($className,$pos+1);
		if($child){
			$className = substr($className,0,-1 * strlen($child));
			if($showchild) $className .=':'.$child;
		}
		return $className;
	}
	/**
	 * 类文件的子类文件夹的返回
	 */
	static public function ChildDir($file){
		return substr($file,0,-1 * strlen(FN_FRAME_SUFFIX)).'/';
	}
	static public function loadFile($file,$path = '') {
		if(!empty($path)) substr($path, -1) != '/' && $path .= "/";
		$file = $path.$file;
		if(isset(self::$_FileSpace[$file])) return true;
		if(file_exists($file)) {
			@include $file;
			return self::$_FileSpace[$file] = true;
		}else {
			return false;
		}
	}
	static public function __callstatic($fname,$argus){
		$frame = self::getInstance();
		return call_user_func_array(array(&$frame,'_'.$fname),$argus);
	}
	static public function setKey($source,&$target,$path = false) {
		if(is_array($source)) {
			foreach($source as $key=>$s) {
				self::setKey($s,$target[$key],$path || $key == 'path');//substr($key,-4)=='path'
			}
		}else{
			if($path) $target = self::parsePath($source);
			else $target = $source;
		}
		return true;
	}
	static public function parsePath($dir){
		$Symbol = substr($dir,0,1);
		switch($Symbol){
			case '~':return FN_FRAME_PATH.substr($dir,1);//框架路径
			case '!':return '';//暂留，无用
			case '@':return FN_WEB_PATH.substr($dir,1);//当前访问的web路径
			case '#':return FN_SYSTEM_PATH.substr($dir,1);//当前执行脚本所在的路径（可以当项目的访问路径）
			case '$':return FN_PROJECT_PATH.substr($dir,1);//项目的路径
			default:return $dir;
		}
	}
	/**
	 * 根据字符串，返回类名，类文件名，类所在相对路径
	 * @param string $name
	 * @param string $Symbol
	 * 格式：prefix:class|child，prefix和child不参与路径和文件名操作
	 * class及prefix均用 . 进行命名分割
	 * class的分割还涉及到文件名及文件路径的判断
	 * child用于实现一个文件多个类的命名规则
	 * prefix用于实现设置类前缀，避免命名冲突
	 */
	static public function parseName($name,$Symbol='_'){
		$fname = $path = $child = $name_extend = '';
		//一个类文件中多个类
		$pos = strrpos($name,'|');
		if($pos !== FALSE){
			$child = substr($name,$pos+1);
			$name = substr($name,0,$pos);
		}
		$name = str_replace('.',$Symbol,$name);
		//不按名称设置调用路径
		$pos = strpos($name,':');
		if($pos !== FALSE){
			$name_extend = substr($name,0,$pos).$Symbol;
			$name = substr($name,$pos+1);
		}
		$pos = strrpos($name,$Symbol);
		$fname = substr($name,$pos+1);
		$path = str_replace($Symbol,'/',substr($name,0,$pos+1));
		return array($name_extend.$name.$child,$fname,$path);
	}
	private function _getConfig($string=''){
		$config = $this->config;
		if(empty($string)) return $config;
		$stringArray = explode('/',$string);
		foreach($stringArray as $str) {
			if(empty($config[$str])) return false;
			$config = $config[$str];
		}
		return $config;
	}
	private function _setConfig($config,$string=''){
		if(!is_array($config)) return false;
		if($string){
			$config_tmp = $this->_getConfig($string);
			$this->setKey($config,$config_tmp);
			$string = '$this->config["'.str_replace('/','"]["',$string).'"] = $config_tmp;';
			return eval($string);
		}
		return $this->setKey($config,$this->config);
	}
}
//基本接口，适用于单例模式
interface FN__single{
	/**
	 * 初始化
	*
	* @access  public
	*
	* @return Object
	*/
	static public function getInstance($array=array());
}
//工厂接口，适用于工厂模式
interface FN__factor{
	/**
	 * 执行工厂
	*
	* @access  public
	*
	* @return Object
	*/
	static public function factor($array);
}
//基本接口，类自动返回new对象
interface FN__auto{
	public function __construct($array=array());
}
define('SET_MAGIC_QUOTES_GPC',get_magic_quotes_gpc());
define('TIME_BASE',time());
class FNbase{
	static private $_Ip;
	static private $_GUID;
	static private $_RequestUri;
	static private $_Baseuri;
	static public function isAJAX(){
		return strtolower(self::getHead('X_REQUESTED_WITH')) == 'xmlhttprequest';
	}
	static public function isPJAX(){
		return strtolower(self::getHead('X_REQUESTED_WITH')) == 'pjax';
	}
	static public function setPath($path){
		if(empty($path)) return false;
		substr($path, -1) != '/' && $path .= "/";
		if(!is_dir($path)) mkdir($path,0777,ture);
		return $path;
	}
	static public function getTime(){
		return TIME_BASE;
	}
	static public function setHtmlChars($string) {
		if(is_array($string)) {
			foreach($string as $key => $val) {
				$string[$key] = self::setHtmlChars($val);
			}
		} else {
			$string = htmlspecialchars($string);
		}
		return $string;
	}
	//force是一个开关，如果不设置则根据配置参数确定是否添加转义，如果设置为true，则强制添加转义
	static public function setEscape($string,$force = 0) {
		if(SET_MAGIC_QUOTES_GPC && !$force) return $string;
		if(is_array($string)) {
			foreach($string as $key => $val) {
				$string[$key] = self::setEscape($val, $force);
			}
		} else {
			$string = addslashes($string);
		}
		return $string;
	}
	//force是一个开关，如果不设置则根据配置参数确定是否清除转义，如果设置为true，则强制清除转义
	static public function clearEscape($string,$force = 0){
		if(!SET_MAGIC_QUOTES_GPC && !$force) return $string;
		if(is_array($string)) {
			foreach($string as $key => $val) {
				$string[$key] = self::clearEscape($val, $force);
			}
		} else {
			$string = stripslashes($string);
		}
		return $string;
	}
	static public function isAbsolutePath($path){
		if(substr($path, 0,1) == '/') return true;
		if(strpos($path,":\\") > 0) return true;
		return false;
	}
	static public function getHead($header){
		$temp = 'HTTP_'.strtoupper(str_replace('-', '_', $header));
		if (!empty($_SERVER[$temp])) return $_SERVER[$temp];
		if (function_exists('apache_request_headers')){
			$headers = apache_request_headers();
			if (!empty($headers[$header])) return $headers[$header];
		}
		return false;
	}
	static public function getIp(){
		if(!self::$_Ip){
			if(!empty($_SERVER['HTTP_CLIENT_IP']) && strcasecmp($_SERVER['HTTP_CLIENT_IP'], 'unknown')) {
				$ip = $_SERVER['HTTP_CLIENT_IP'];
			} elseif(!empty($_SERVER['HTTP_X_FORWARDED_FOR']) && strcasecmp($_SERVER['HTTP_X_FORWARDED_FOR'], 'unknown')) {
				$ip = substr($_SERVER['HTTP_X_FORWARDED_FOR'],0,strpos($_SERVER['HTTP_X_FORWARDED_FOR'],','));
				if (preg_match("/^(10|172.16|192.168)./", $ip)) $ip = false;
			}
			if(!$ip && !empty($_SERVER['REMOTE_ADDR']) && strcasecmp($_SERVER['REMOTE_ADDR'], 'unknown')) {
				$ip = $_SERVER['REMOTE_ADDR'];
			}
			preg_match("/[\d\.]{7,15}/",$ip, $onlineipmatches);
			self::$_Ip = $onlineipmatches[0] ? $onlineipmatches[0] : 'unknown';
			unset($onlineipmatches);unset($ip);
		}
		return self::$_Ip;
	}
	function getIpLocation($ip){
		$result = self::getUrlContent('http://ip.qq.com/cgi-bin/searchip?searchip1='.$ip);
		$result = mb_convert_encoding($result, "utf-8", "gb2312");//编码转换，否则乱码
		preg_match("@<span>(.*)</span></p>@iU",$result,$ipArray);
		return $ipArray[1];
	}
	static public function getRequestUri(){
		if (!self::$_RequestUri){
			if (isset($_SERVER['HTTP_X_REWRITE_URL'])){
				self::$_RequestUri = $_SERVER['HTTP_X_REWRITE_URL'];
			}elseif (isset($_SERVER['REQUEST_URI'])){
				self::$_RequestUri = $_SERVER['REQUEST_URI'];
			}elseif (isset($_SERVER['ORIG_PATH_INFO'])){
				self::$_RequestUri = $_SERVER['ORIG_PATH_INFO'];
				if (! empty($_SERVER['QUERY_STRING'])) self::$_RequestUri .= '?' . $_SERVER['QUERY_STRING'];
			}else{
				self::$_RequestUri = '';
			}
		}
		return self::$_RequestUri;
	}
	static public function setRequestUri($requestUri){
		self::$_RequestUri = $requestUri;
		self::$_Baseuri = null;
	}
	static public function getBaseUri(){
		if (self::$_Baseuri) return self::$_Baseuri;
		$filename = basename($_SERVER['SCRIPT_FILENAME']);
		if (basename($_SERVER['SCRIPT_NAME']) === $filename){
			$url = $_SERVER['SCRIPT_NAME'];
		}elseif (basename($_SERVER['PHP_SELF']) === $filename){
			$url = $_SERVER['PHP_SELF'];
		}elseif (isset($_SERVER['ORIG_SCRIPT_NAME']) && basename($_SERVER['ORIG_SCRIPT_NAME']) === $filename){
			$url = $_SERVER['ORIG_SCRIPT_NAME'];
		}else{
			$path = $_SERVER['PHP_SELF'];
			$segs = explode('/', trim($_SERVER['SCRIPT_FILENAME'], '/'));
			$segs = array_reverse($segs);
			$index = 0;
			$last = count($segs);
			$url = '';
			do{
				$seg = $segs[$index];
				$url = '/' . $seg . $url;
				++ $index;
			} while (($last > $index) && (false !== ($pos = strpos($path, $url))) && (0 != $pos));
		}
		$request = self::getRequestUri();
		if (0 === strpos($request, dirname($url))){
			self::$_Baseuri = rtrim(dirname($url), '/').'/';
		}elseif (!strpos($request, basename($url))){
			return '';
		}else{
			if ((strlen($request) >= strlen($url)) && ((false !== ($pos = strpos($request, $url))) && ($pos !== 0))){
				$url = substr($request, 0, $pos + strlen($url));
			}
			self::$_Baseuri = self::setHtmlChars(rtrim($url, '/') . '/');
		}
		return self::$_Baseuri;
	}
	//PHP代理访问函数
	static public function getUrlContent($url,$fields=array()){
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL,$url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		if(!empty($fields)){
			curl_setopt($ch, CURLOPT_POST, 1 );
			curl_setopt($ch, CURLOPT_POSTFIELDS,$fields);
		}
		$data = curl_exec($ch);
		curl_close($ch);
		return $data;
	}
	static function random($length, $numeric = 0) {
		PHP_VERSION < '4.2.0' && mt_srand((double)microtime() * 1000000);
		if($numeric) {
			$hash = sprintf('%0'.$length.'d', mt_rand(0, pow(10, $length) - 1));
		} else {
			$hash = '';
			$chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz';
			$max = strlen($chars) - 1;
			for($i = 0; $i < $length; $i++) {
				$hash .= $chars[mt_rand(0, $max)];
			}
		}
		return $hash;
	}
	static public function guid($op=false,$namespace=''){
		$uid = uniqid("", true);
		$data = $namespace;
		$data .= $_SERVER['REQUEST_TIME'];
		$data .= $_SERVER['HTTP_USER_AGENT'];
		$data .= $_SERVER['SERVER_ADDR'];
		$data .= $_SERVER['SERVER_PORT'];
		$data .= $_SERVER['REMOTE_ADDR'];
		$data .= $_SERVER['REMOTE_PORT'];
		$hash = strtoupper(hash('ripemd128', $uid . self::$_GUID . md5($data)));
		if($op){
			self::$_GUID = substr($hash,0,32);
		}else{
			self::$_GUID = substr($hash,  0,  8).'-'.substr($hash,  8,  4) .'-'.substr($hash, 12,  4) .'-'.substr($hash, 16,  4).'-'.substr($hash, 20, 12);
		}
		return self::$_GUID;
	}
	static public function authcode($string, $operation = 'DECODE', $key = '', $expiry = 0) {
		$ckey_length = 4;
		$key = md5(empty($key) ? FN::getConfig('global/autoCode') : $key);
		$keya = md5(substr($key, 0, 16));
		$keyb = md5(substr($key, 16, 16));
		$keyc = $ckey_length ? ($operation == 'DECODE' ? substr($string, 0, $ckey_length): substr(md5(microtime()), -$ckey_length)) : '';
	
		$cryptkey = $keya.md5($keya.$keyc);
		$key_length = strlen($cryptkey);
	
		$string = $operation == 'DECODE' ? base64_decode(substr($string, $ckey_length)) : sprintf('%010d', $expiry ? $expiry + time() : 0).substr(md5($string.$keyb), 0, 16).$string;
		$string_length = strlen($string);
	
		$result = '';
		$box = range(0, 255);
	
		$rndkey = array();
		for($i = 0; $i <= 255; $i++) {
			$rndkey[$i] = ord($cryptkey[$i % $key_length]);
		}
	
		for($j = $i = 0; $i < 256; $i++) {
			$j = ($j + $box[$i] + $rndkey[$i]) % 256;
			$tmp = $box[$i];
			$box[$i] = $box[$j];
			$box[$j] = $tmp;
		}
	
		for($a = $j = $i = 0; $i < $string_length; $i++) {
			$a = ($a + 1) % 256;
			$j = ($j + $box[$a]) % 256;
			$tmp = $box[$a];
			$box[$a] = $box[$j];
			$box[$j] = $tmp;
			$result .= chr(ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
		}
	
		if($operation == 'DECODE') {
			if((substr($result, 0, 10) == 0 || substr($result, 0, 10) - time() > 0) && substr($result, 10, 16) == substr(md5(substr($result, 26).$keyb), 0, 16)) {
				return substr($result, 26);
			} else {
				return '';
			}
		} else {
			return $keyc.str_replace('=', '', base64_encode($result));
		}
	}
	static public function cutstr($string, $length, $havedot=0) {
		if(strlen($string) <= $length) return $string;
		$wordscut = '';
		if(strtolower(FN::getConfig('global/charset')) == 'utf-8') {
			$n = 0;
			$tn = 0;
			$noc = 0;
			while ($n < strlen($string)) {
				$t = ord($string[$n]);
				if($t == 9 || $t == 10 || (32 <= $t && $t <= 126)) {
					$tn = 1;
					$n++;
					$noc++;
				} elseif(194 <= $t && $t <= 223) {
					$tn = 2;
					$n += 2;
					$noc += 2;
				} elseif(224 <= $t && $t <= 239) {
					$tn = 3;
					$n += 3;
					$noc += 2;
				} elseif(240 <= $t && $t <= 247) {
					$tn = 4;
					$n += 4;
					$noc += 2;
				} elseif(248 <= $t && $t <= 251) {
					$tn = 5;
					$n += 5;
					$noc += 2;
				} elseif($t == 252 || $t == 253) {
					$tn = 6;
					$n += 6;
					$noc += 2;
				} else {
					$n++;
				}
				if ($noc >= $length) {
					break;
				}
			}
			if ($noc > $length) {
				$n -= $tn;
			}
			$wordscut = substr($string, 0, $n);
		} else {
			for($i = 0; $i < $length - 3; $i++) {
				if(ord($string[$i]) > 127) {
					$wordscut .= $string[$i].$string[$i + 1];
					$i++;
				} else {
					$wordscut .= $string[$i];
				}
			}
		}
		if($string != $wordscut && $havedot){
			return $wordscut.'...';
		}else{
			return $wordscut;
		}
	}
}
if(!function_exists('get_called_class')) {
class class_tools{
	private static $i = 0;
	private static $fl = null;
	public static function get_called_class(){
		$bt = debug_backtrace();
		//使用call_user_func或call_user_func_array函数调用类方法，处理如下
		if (array_key_exists(3, $bt)
			&& array_key_exists('function', $bt[3])
			&& in_array($bt[3]['function'], array('call_user_func', 'call_user_func_array'))
		){
			//如果参数是数组
			if (is_array($bt[3]['args'][0])) {
				$toret = $bt[3]['args'][0][0];
				return $toret;
			}else if(is_string($bt[3]['args'][0])) {//如果参数是字符串
			//如果是字符串且字符串中包含::符号，则认为是正确的参数类型，计算并返回类名
				if(false !== strpos($bt[3]['args'][0], '::')) {
					$toret = explode('::', $bt[3]['args'][0]);
					return $toret[0];
				}
			}
		}
		//使用正常途径调用类方法，如:A::make()
		if(self::$fl == $bt[2]['file'].$bt[2]['line']) {
			self::$i++;
		} else {
			self::$i = 0;
			self::$fl = $bt[2]['file'].$bt[2]['line'];
		}
		$lines = file($bt[2]['file']);
		preg_match_all('/([a-zA-Z0-9\_]+)::'.$bt[2]['function'].'/', $lines[$bt[2]['line']-1],$matches);
		return $matches[1][self::$i];
	}
}
function get_called_class(){
	return class_tools::get_called_class();
}
}
?>
