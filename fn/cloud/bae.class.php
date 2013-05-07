<?php
//百度云平台
class FN_cloud_bae extends FN implements FN__single{
	private $accesskey = null;
	private $securekey = null;
	private $appid = null;
	private $sql_host = null;
	private $sql_port = null;
	
	static public function getInstance($array=array()){
		return new self($array);
	}
	private function __construct($config){
		$this->accesskey = getenv('HTTP_BAE_ENV_AK');
		$this->securekey = getenv('HTTP_BAE_ENV_SK');
		$this->appid = getenv('HTTP_BAE_ENV_APPID');
		$this->sql_host = getenv('HTTP_BAE_ENV_ADDR_SQL_IP');
		$this->sql_port = getenv('HTTP_BAE_ENV_ADDR_SQL_PORT');
	}
	//统一调用云平台服务
	/**
	 * 获取基础服务
	 * @param string $servername  调用的服务名称
	 * @param string $servername  映射名称，默认为default
	 */
	protected function _server($servername,$config){
		if(empty($config['platform']) || !in_array($config['platform'],array('cloud','bae'))) return parent::_server($servername,$config);
		switch($config['drive']){
			case 'memcache':
				require_once ('BaeMemcache.class.php');
				return new BaeMemcache();
			case 'count':
				require_once ('BaeCounter.class.php');
				return new BaeCounter();
			case 'rank':
				require_once ('BaeRankManager.class.php');
				return BaeRankManager::getInstance();
			case 'mysql':
				$config['host'] = $this->sql_host;
				$config['port'] = $this->sql_port;
				$config['user'] = $this->accesskey;
				$config['pass'] = $this->securekey;
				break;
			case 'smarty':
				$config['compile_dir'] = '%template/';
				$config['cache_dir'] = '%cache/';
				break;
		}
		return parent::_server($servername,$config);
	}
	static public function parsePath($dir){
		$Symbol = substr($dir,0,1);
		switch($Symbol){
			case '%':return sys_get_temp_dir().'/'.substr($dir,1);//缓存目录
			default:parent::parsePath($dir);
		}
	}
}
?>
