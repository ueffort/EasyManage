<?php
//百度云平台，云平台环境Paas，用以扩展服务接口
class FN_platform_bae extends FN_platform{
	private $accesskey = null;
	private $securekey = null;
	private $appid = null;
	protected $PlatformSelf = 'bae';
	
	public function __construct($config){
		$this->accesskey = $config['accesskey'];
		$this->securekey = $config['securekey'];
		$this->appid = $config['appid'];
	}
	//统一调用云平台服务
	/**
	 * 获取基础服务
	 * @param string $servername  调用的服务名称
	 * @param string $config  映射名称，默认为default
     * @return class
	 */
	public function server($servername,&$config){
		switch($servername){
			case 'cache':
				//http://developer.baidu.com/wiki/index.php?title=docs/cplat/rt/php/cache
				if(!$this->isCloudSelf()) return false;
				require_once ('BaeMemcache.class.php');
				return new BaeMemcache();
			case 'count':
				//http://developer.baidu.com/wiki/index.php?title=docs/cplat/rt/php/counter
				if(!$this->isCloudSelf()) return false;
				require_once ('BaeCounter.class.php');
				return new BaeCounter();
			case 'rank':
				//http://developer.baidu.com/wiki/index.php?title=docs/cplat/rt/php/rank
				if(!$this->isCloudSelf()) return false;
				require_once ('BaeRankManager.class.php');
				return BaeRankManager::getInstance();
			case 'database':
				if(!$this->isCloudSelf()) return false;
				switch($config['drive']){
					case 'mysql':
						//http://developer.baidu.com/wiki/index.php?title=docs/cplat/rt/php/mysql
						$config['host'] = getenv('HTTP_BAE_ENV_ADDR_SQL_IP');
						$config['port'] = getenv('HTTP_BAE_ENV_ADDR_SQL_PORT');
						$config['user'] = $this->accesskey;
						$config['pass'] = $this->securekey;
						break;
					case 'mongodb':
						//http://developer.baidu.com/wiki/index.php?title=docs/cplat/rt/php/mongodb
						$config['host'] = getenv('HTTP_BAE_ENV_ADDR_MONGO_IP');
						$config['port'] = getenv('HTTP_BAE_ENV_ADDR_MONGO_PORT');
						$config['user'] = $this->accesskey;
						$config['pass'] = $this->securekey;
						break;
					case 'redis':
						//http://developer.baidu.com/wiki/index.php?title=docs/cplat/rt/php/redis
						$config['host'] = getenv('HTTP_BAE_ENV_ADDR_REDIS_IP');
						$config['port'] = getenv('HTTP_BAE_ENV_ADDR_REDIS_PORT');
						$config['pass'] = $this->accesskey . '-' . $this->securekey . '' . $config['dbname'];
						break;
				}
				break;
			case 'template':
				if(!$this->isCloudSelf()) return false;
				switch($config['drive']){
					case 'smarty':
						$config['compile_dir'] = '%template/';
						$config['cache_dir'] = '%cache/';
						unset($config['platform']);
						break;
				}
				break;
			case 'log':
				//http://developer.baidu.com/wiki/index.php?title=docs/cplat/rt/php/log
				if(!$this->isCloudSelf()) return false;
				require_once ('BaeLog.class.php');
				/*
				//打印一条警告日志，包括日志级别和日志内容
				$logger ->logWrite(2, "this is for warning log print ");
				//打印一条轨迹日志
				$logger ->logTrace("this is for trace log print ");
				//打印一条通知日志
				$logger ->logNotice("this is for notice log print ");
				//打印一条调试日志
				$logger ->logDebug("this is for debug log print ");
				//打印一条警告日志
				$logger ->logWarning("this is for warning log print ");
				//打印一条致命日志
				$logger ->logFatal("this is for fatal log print ");
				*/
				return BaeLog::getInstance();
			case 'image':
				//http://developer.baidu.com/wiki/index.php?title=docs/cplat/rt/php/image
				if(!$this->isCloudSelf()) return false;
				require_once ('BaeImageService.class.php');
				return new FN_platform_baeimage();
			case 'vcode'://是image服务中的一款，但操作方式所以独立出来
				//http://developer.baidu.com/wiki/index.php?title=docs/cplat/rt/php/image#.E9.AA.8C.E8.AF.81.E7.A0.81.E5.8A.9F.E8.83.BD.EF.BC.88.E5.8F.AA.E6.94.AF.E6.8C.81.E5.8F.82.E6.95.B0.E6.95.B0.E7.BB.84.E6.96.B9.E5.BC.8F.EF.BC.89
				if(!$this->isCloudSelf()) return false;
				require_once ('BaeImageService.class.php');
				return new FN_platform_baeVCode();
			case 'taskqueue'://异步操作，一对一
				//http://developer.baidu.com/wiki/index.php?title=docs/cplat/rt/php/taskqueue
				if(!$this->isCloudSelf()) return false;
				require_once ('BaeTaskQueueManager.class.php');
				return BaeTaskQueueManager::getInstance();
			case 'storage'://BCS云存储
				//http://developer.baidu.com/wiki/index.php?title=docs/cplat/stor/api
				$config['accesskey'] = $this->accesskey;
				$config['securekey'] = $this->securekey;
				return new FN_platform_baeStorage($config);
			case 'queue'://云消息,异步操作，多对多
				//http://developer.baidu.com/wiki/index.php?title=docs/cplat/mq/api
				$config['accesskey'] = $this->accesskey;
				$config['securekey'] = $this->securekey;
				return new FN_platform_baeQueue($config);
			case 'mail':
				//http://developer.baidu.com/wiki/index.php?title=docs/cplat/mq/api#mail
				$config['accesskey'] = $this->accesskey;
				$config['securekey'] = $this->securekey;
				return new FN_platform_baeMail($config);
			case 'channel'://云推送
				//http://developer.baidu.com/wiki/index.php?title=docs/cplat/push/api
				$config['accesskey'] = $this->accesskey;
				$config['securekey'] = $this->securekey;
				return new FN_platform_baeChannel($config);
			case 'map'://LBS地图
				//http://lbsyun.baidu.com/
				return false;
		}
	}
	public function parsePath($dir,$Symbol){
		switch($Symbol){
			case '%':
				return sys_get_temp_dir().'/'.substr($dir,1);//缓存目录
			default:
				default:return $dir;
		}
	}
}
class FN_platform_baeimage{
	private $image = null;
	private $type = null;
	private $Service = null;
	public function __construct(){
		$this->Service = new BaeImageService();
	}
	public function __call($fname,$argus){
		call_user_func_array(array(&$this->image,$fname),$argus);
		return $this;
	}
	//获取文字水印功能类: http://phpsdkdoc.duapp.com/?BaeAPI/BaeImageAnnotate.html
	public function getAnnotate($text=null){
		$this->type = 'Annotate';
		$this->image = new BaeImageAnnotate($text);
		return $this;
	}
	//获取图片合成功能类: http://phpsdkdoc.duapp.com/?BaeAPI/BaeImageComposite.html
	//多个图片合成反复调用此函数设定参数
	public function getComposite($url = NULL){
		$this->type = 'Composite';
		$this->image[] = new BaeImageComposite($url);
		return $this;
	}
	//获取二维码功能类: http://phpsdkdoc.duapp.com/?BaeAPI/BaeImageQRCode.html
	public function getQRCode($text = NULL){
		$this->type = 'QRCode';
		$this->image = new BaeImageQRCode();
		return $this;
	}
	//获取图形变换处理功能类: http://phpsdkdoc.duapp.com/?BaeAPI/BaeImageTransform.html
	public function getTransform(){
		$this->type = 'Transform';
		$this->image = new BaeImageTransform();
		return $this;
	}
	//获取静态参数: http://phpsdkdoc.duapp.com/?BaeAPI/BaeImageConstant.html
	public function get($const=null){
		return constant('BaeImageConstant::'. $const);
	}
	//面向对象调用后的统一执行函数
	public function apply(){
		if(!isset($this->image) || !isset($this->type)) return -3;//未选择类型
		$num = func_num_args();
		$params = func_get_args();
		switch($this->type){
			case 'Annotate':
				if($num == 0) return -2;
				//params = [url]
				$return = $this->Service->applyAnnotateByObject($params[0], $this->image);
				break;
			case 'Composite':
				if($num == 0) return -2;
				//params = [width,height,outptcode,quality]
				if($num < 4){
					$default = array(1000,1000,$this->get('JPG'),80);
					foreach($default as $key=>$param){
						if(empty($params[$key])) $params[$key] = $param;
					}
				}
				$return = $this->Service->applyCompositeByObject($this->image,$params[0],$params[1],$params[2],$params[3]);
				break;
			case 'QRCode':
				$return = $this->Service->applyQRCodeByObject($this->image);
				break;
			case 'Transform':
				if($num == 0) return -2;//参数错误
				//params = [url]
				$return = $this->Service->applyTransformByObject($params[0],$this->image);
				break;
			default:
				return -1;//类型错误
		}
		unset($this->image);
		unset($this->type);
		//header("Content-type:image/jpg");
		//$imageSrc = base64_decode($return['image_data']);
		if($return !== false && isset($return['response_params']) && isset($return['response_params']['image_data'])) return $return['response_params'];
		//调用error；
		return false;
	}
	public function error(){
		return $this->Service->errmsg();
	}
}

class FN_platform_baeVCode{
	private $image = null;
	public function __construct(){
		$this->image = new BaeImageService();
	}
	//生成验证码
	public function generateVCode($length,$pattern){
		$return = $this->image->generateVCode(array(BaeImageConstant::VCODE_LEN=>$length,BaeImageConstant::VCODE_PATTERN=>$pattern));
		//echo "imgurl:" . $return['imgurl'] . "\n";用于显示的url
		//echo "secret:" . $return['secret'] . "\n";用于输入后的校验
		if($return !== false && isset($return['response_params']) && isset($return['response_params']['imgurl'])) return $return['response_params'];
		//调用error；
		return false;
	}
	//校验验证码
	public function verifyVCode($input,$secret){
		$return = $this->image->verifyVCode(array(BaeImageConstant::VCODE_INPUT=>$input,BaeImageConstant::VCODE_SECRET=>$secret));
		if($return !== false && isset($return['response_params']) && isset($return['response_params']['imgurl'])) return $return['response_params'];
		//调用error；
		return false;
	}
	public function error(){
		return $this->Service->errmsg();
	}
}

//暂无权限接口设计
class FN_platform_baeStorage extends FN_tools_rest{
	private $accesskey = null;
	private $securekey = null;
	private $bucket = null;
	const DEFAULT_URL = 'http://bcs.duapp.com';
	protected function init(){
		$this->accesskey = $this->config['accesskey'];
		$this->securekey = $this->config['securekey'];
	}
	/**
	 * 校验bucket是否合法，bucket规范
	 * 1. 由小写字母，数字和横线'-'组成，长度为6~63位 
	 * 2. 不能以数字作为Bucket开头 
	 * 3. 不能以'-'作为Bucket的开头或者结尾
	 * @param string $bucket
	 * @return boolean
	 */
	public static function validate_bucket($bucket) {
		//bucket 正则
		$pattern1 = '/^[a-z][-a-z0-9]{4,61}[a-z0-9]$/';
		if (! preg_match ( $pattern1, $bucket )) {
			return false;
		}
		return true;
	}
	//创建bucket
	public function creatDir($dirname,$acl='public-read'){
		if(!$this->validate_bucket($dirname)) return false;
		$sign = $this->sign('PUT',$dirname,'');
		$url = $this->formatURL($dirname,'');
		$params = array('sign'=>$sign);
		$header = array('x-bs-acl'=>$acl);
		$options = array('header'=>$header);
		$response = $this->put($url,$params,$options);
		return $response->isOK();
	}
	//列出所有bucket
	public function listDir(){
		$sign = $this->sign('GET','','');
		$url = $this->formatURL('','');
		$params = array('sign'=>$sign);
		$response = $this->get($url,$params);
		return $response->isOK() ? $response->getBodyJson():false;
	}
	//删除bucket
	public function deleteDir($dirname){
		$sign = $this->sign('DELETE',$dirname,'');
		$url = $this->formatURL($dirname,'');
		$params = array('sign'=>$sign);
		$response = $this->delete($url,$params);
		return $response->isOK();
	}
	//设置文件操作的bucket
	public function setDir($dirname){
		$this->bucket = $dirname;
	}
	//添加object
	public function addFile($filename,$file,$acl=false,$header=array()){
		$sign = $this->sign('PUT',$this->bucket,$filename);
		$url = $this->formatURL($this->bucket,$filename);
		$params = array('sign'=>$sign);
		if ($acl) {
			$header['x-bs-acl'] = $acl;
		}
		$options = array('header'=>$header);
		$options ['fileUpload'] = $file;
		$head['Content-Disposition'] = 'attachment; filename='.substr($filename,0,strrpos($filename,'/'));
		$response = $this->put ($url,$params,$options);
		return $response->isOK ();
	}
	//添加object通过byte
	public function addFileBytes($filename,$bytes,$acl=false,$header=array()){
		$sign = $this->sign('PUT',$this->bucket,$filename);
		$url = $this->formatURL($this->bucket,$filename);
		$params = array('sign'=>$sign);
		if ($acl) {
			$header['x-bs-acl'] = $acl;
		}
		$options = array('header'=>$header);
		$options ['content'] = $bytes;
		$head['Content-Disposition'] = 'attachment; filename='.substr($filename,0,strrpos($filename,'/'));
		$response = $this->put ($url,$params,$options);
		return $response->isOK ();
	}
	//复制object
	public function copyFile($source,$filename,$etag=false){
		$source = $this->parseURL($source);
		$sign = $this->sign('PUT',$this->bucket,$filename);
		$url = $this->formatURL($this->bucket,$filename);
		
		$params = array('sign'=>$sign);
		$header = array('x-bs-copy-source'=>$this->formatURL($source['bucket'],$source['object']));
		if($etag) $header['x-bs-copy-source-tag'] = $etag;
		$options = array('header'=>$header);
		$response = $this->put($url,$params,$options);
        return $response->isOK();
	}
	//删除object
	public function deleteFile($filename){
		$sign = $this->sign('DELETE',$this->bucket,$filename);
		$url = $this->formatURL($this->bucket,$filename);
		$params = array('sign'=>$sign);
		$response = $this->delete($url,$params);
		return $response->isOK();
	}
	//列出所有object
	//prefix可以作为路劲参数设置
	public function listFile($prefix = '',$start=0,$limit=0,$list_model = 2) {
		$sign = $this->sign('GET',$this->bucket,'');
		$url = $this->formatURL($this->bucket,'');
		$params = array('sign'=>$sign);
		if ($start) {
			$params['start'] = $start;
		}
		if ($limit) {
			$params['limit'] = $limit;
		}

		$params['prefix'] = rawurlencode ('/'. $prefix );
		$params['dir'] = $list_model;

		$response = $this->get($url,$params);
		return $response->isOK() ? $response->getBodyJson():false;
	}
	//获取object元数据
	public function getHead($filename,$header=array()){
		$sign = $this->sign('HEAD',$this->bucket,$filename);
		$url = $this->formatURL($this->bucket,$filename);
		$params = array('sign'=>$sign);
		$options = array('header'=>$header);
		$response = $this->head($url,$params,$options);
		return $response->isOK() ? $response->getHeader() : false;
	}
	//获取object
	public function getFile($filename,$range=false,$header=array()){
		$sign = $this->sign('HEAD',$this->bucket,$filename);
		$url = $this->formatURL($this->bucket,$filename);
		$params = array('sign'=>$sign);
		if(is_array($range)) {
			$header['Range'] = $range[0].'-'.($range[1] ? $range[1] : 'end');
		}elseif($range){
			$header['Range'] = '0-'.$range;
		}
		$options = array('header'=>$header);
		$response = $this->head($url,$params,$options);
		return $response->isOK() ? $response->getBody() : false;
	}
	//返回当前基本url
	public function getURL(){
		return self::DEFAULT_URL.'/' . $this->bucket.'/';
	}
	/**
	 * 构造url
	 * @param string $bucket
     * @param string $object
	 * @return boolean string
	 */
	private function formatURL($bucket,$object) {
		return self::DEFAULT_URL.'/' . $bucket.'/' . rawurlencode ( $object );
	}
	/**
	 * 解析url为百度的bucket+object
	 * @param string $url
	 * @return boolean string
	 */
	private function parseURL($url) {
		$url = str_replace(self::DEFAULT_URL.'/','',$url);
		$pos = strpos($url,'/');
		$bucket = substr($url,0,$pos-1);
		$object = substr($url,$pos);
		return array('bucket'=>$bucket,'object'=>$object);
	}
	//生成签名
	//time为有效时间，Unix时间戳
	//ip访问白名单
	//size限制上传object大小，单位B
	private function sign($method,$bucket,$object,$time=false,$ip=false,$size=false){
		$flag = 'MBO';
		$content = 'Method='.$method.'\n'
					.'Bucket='.$bucket.'\n'
					.'Object=/'.$object.'\n';
		if($time){
			$flag.='T';
			$content .= 'Time='.$time.'\n';
		}
		if($ip){
			$flag.='I';
			$content .= 'Ip='.$ip.'\n';
		}
		if($size){
			$flag.='S';
			$content .= 'Size='.$size.'\n';
		}
		$content = $flag.'\n'.$content;
		$signature=urlencode(base64_encode(hash_hmac('sha1',$content, $this->secretKey,true)));
		return $flag.':'.$this->accesskey.':'.$signature;
	}
}

class FN_platform_baeQueue extends FN_tools_rest{
	protected $accesskey = null;
	protected $securekey = null;
	private $queue = null;
	const DEFAULT_URL = 'http://bcms.api.duapp.com/rest/2.0/bcms';
	protected function init(){
		$this->accesskey = $this->config['accesskey'];
		$this->securekey = $this->config['securekey'];
	}
	public function setQueue($queuename){
		$this->queue = $queuename;
	}
	/**
 	* 构造url
 	* @return boolean string
 	*/
	protected function formatURL() {
		return self::DEFAULT_URL.'/' . $this->queue;
	}
	//生成签名
	protected function sign($method,$url,$params){
		$basic_string = $method.$url;
		ksort($params);
		foreach($params as $key=>$value){
			$basic_string .= $key.'='.$value;
		}
		$basic_string .= $this->securekey;
		return md5((urlencode($basic_string)));
	}
	//统一请求函数
	protected function requestProxy($params){
		//access_token	 string　	 是	 开发者准入token,https调用时必须存在
		$url = $this->formatURL();
		$params['client_id'] = $this->accesskey;
		$params['timestamp'] = FNbase::getTime();
		$sign = $this->sign('POST',$url,$params);
		$params['sign'] = $sign;
		$header = array();
		$header['Host'] = 'bcms.api.duapp.com';
		$header['Content-Type'] = 'application/x-www-form-urlencoded';
		$options = array('header'=>$header);
		$options['method'] = 'POST';
		$options['url'] = $url;
		if($options['method'] == 'GET'){
			$options['url'] = $this->combineURL($options['url'],$params);
		}else{
			$options['content'] = $this->combineParams($params);
		}
		$response = $this->request($options);
		return $response->isOK() ? $response->getBodyJson():false;
	}
}

class FN_platform_baeMail extends FN_platform_baeQueue{
	private $from = null;
	protected function init(){
		parent::init();
		if(isset($this->config['queue'])) $this->setQueue($this->config['queue']);
		$this->setFrom($this->config['from']);
	}
	public function setFrom($from){
		$this->from = $from;
		return $this;
	}
	public function mail($address,$subject,$message,$ishtml=false){
		if(is_array($address)){
			$address = json_encode($address);
		}
		$params['address'] = $address;
		$params['method'] = 'mail';
		$params['message'] = ($ishtml ? '<!--HTML-->':'').$message;//如果是二进制内容，需要将内容先做BASE64编码成文本再发布消息
		$params['mail_subject'] = $subject;
		if($this->from) $params['from'] = $this->from;
		return $this->requestProxy($params);
	}
}

class FN_platform_beaChannel extends FN_tools_rest{
	protected $accesskey = null;
	protected $securekey = null;

	const DEFAULT_URL = 'http://channel.api.duapp.com/rest/2.0/channel';
	protected function init(){
		$this->accesskey = $this->config['accesskey'];
		$this->securekey = $this->config['securekey'];
	}
	/**
	 * 构造url
	 * @param string $channel
	 * @return boolean string
	 */
	protected function formatURL($channel) {
		return self::DEFAULT_URL.'/' . $channel;
	}
	//生成签名
	protected function sign($method,$url,$params){
		$basic_string = $method.$url;
		sort($params);
		foreach($params as $key=>$value){
			$basic_string .= $key.'='.$value;
		}
		$basic_string .= $this->securekey;
		return md5((urlencode($basic_string)));
	}
	//统一请求函数
	protected function requestProxy($params,$channel){
		$url = $this->formatURL($channel);
		$params['apikey'] = $this->accesskey;
		$params['timestamp'] = FNbase::getTime();
		$sign = $this->sign('POST',$url,$params);
		$params['sign'] = $sign;
		$options = array();
		$options['url'] = $url;
		$options['method'] = $params['method'];
		if($params['method'] == 'GET'){
			$options['url'] = $this->combineURL($options['url'],$params);
		}else{
			$options['content'] = $this->combineParams($params);
		}
		$response = $this->request($options);
		return $response->isOK() ? $response->getBodyJson():false;
	}
}