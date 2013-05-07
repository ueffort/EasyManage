<?php
/*
 * 在按rest方式获取信息，能通过统一的缓存服务，进行信息的缓存的操作处理
 * 缓存的操作由客户端实行，用于实现客户端定制缓存
*/
class FN_layer_api_restcache_client implements FN__single{
	static private $_Instance = array();
	private $cache = null;
	protected $domain = null;
	protected $url = null;
	protected $action = null;
	protected $query = null;
	protected $debug = false;
	protected $type = null;
	protected $freq = 5;//默认缓存5分钟
	static function getInstance($array=array()){
		if(empty(self::$_Instance)){
			if(!empty($array['proxy'])){
				self::$_Instance = FN::F('layer.api.restcache.proxy');
				self::$_Instance->setProxy($array['proxy']);
			}elseif(!empty($array['cache'])){
				self::$_Instance = new self();
				self::$_Instance->setCache($array['cache']);
			}else{
				return false;
			}
		}
		return self::$_Instance;
	}
	protected function __construct(){}
	public function setCache($cache){
		$this->cache = $cache;
	}
	public function setDomain($domain){
		$this->domain = $domain;
		return $this;
	}
	public function setFreq($freq){
		$this->freq = $freq;
		return $this;
	}
	public function debug($op=true){
		$this->debug = $op;
		return $this;
	}
	//域名或域名+子目录，具体区分由服务端处理
	public function domain($domain,$path,$module=''){
		$this->_clear();
		if(empty($module)){
			//模拟多态
			$module = $path;
			$path = '';
		}
		$this->url = $domain.'.'.$this->domain.(empty($path)?'':'/'.$path).'/api/?module='.$module;
		return $this;
	}
	//可以是键值对或者完整的字符串
	public function param($action,$query='',$type='php'){
		if(is_array($action)){
			$action_string = '';
			foreach($action as $key=>$value){
				$action_string.='/'.$key.'/'.rawurlencode($value);
			}
			$action = substr($action_string,1);
		}
		if(is_array($query)){
			$query_string = '';
			foreach($query as $key=>$value){
				$query_string.='&'.$key.'='.rawurlencode($value);
			}
			$query = $query_string;
		}
		$this->action = $action;
		$this->query = $query;
		$this->type = $type;
		return $this;
	}
	protected function _parseType($content){
		if($this->debug) var_dump($content);
		switch($this->type){
			case 'json':
				return json_encode($content);
			case 'jquery'://自动获取jquery参数变量
				return $_GET['callback'].'('.json_encode($content).')';
			case 'xml':
				return '';
			case 'php':
			default:
				return $content;
		}
	}
	protected function _call($url,$array=array()){
		if($this->debug) var_dump($url);
		$content = FNbase::getUrlContent($url,FNbase::clearEscape($array));
		if($this->debug) var_dump($content);
		$content = json_decode($content,true);
		if(!is_array($content)) return $this->_cache_msg($url,'url error','error');
		if(!empty($content['error'])) return $this->_cache_msg($url,$content['error'],'error');
		$data = !isset($content['data']) ? array():array('data'=>$content['data']);
		if(!empty($content['other']) && is_array($content['other'])){
			unset($content['other']['data']);
			$data = array_merge($data,$content['other']);
		}
		$content['data'] = $data;
		return $content;
	}
	protected function _cache_msg($url,$msg,$level){
		return $msg;
	}
	protected function _getUrl(){
		return $this->url.'&action='.$this->action.(empty($this->query) ? '' : '&'.$this->query)."&access_token=51xiaoguo.com";
	}
	protected function _clear(){
		$this->url = $this->type = '';
		$this->debug = false;
	}
	//获取数据,客户端缓存设置
	public function get($freq=false){
		$frequrl = $url = $this->_getUrl();
		if(!$this->debug && $freq != -1){
			if($freq>=0) $frequrl .= '&freq='.$freq;
			$content = $this->cache->get($frequrl);
			if($content) return $content;
		}
		$content = $this->_call($url);
		if(!is_array($content)) return array();
		if($freq) $content['freq'] = $freq;
		if(!isset($content['freq'])) $content['freq'] = $this->freq;
		if(empty($content['freq']) || $content['freq'] >= 0) $this->cache->set($url,$content['data'],false,$content['freq'] * 60);
		return !isset($content['data']) ? array() : $this->_parseType($content['data']);
	}
	//提交数据
	public function post($array){
		$url = $this->_getUrl();
		$content = $this->_call($url,$array);
		return !isset($content['data']) ? array() : $this->_parseType($content['data']);
	}
	//删除缓存信息
	public function delete($freq=false){
		$url = $this->_getUrl();
		if($freq) $url .= '&freq='.$freq;
		$this->cache->delete($url);
		return !isset($content['data']) ? array() : $this->_cache_msg($url,'delete cache','log');
	}
}
?>