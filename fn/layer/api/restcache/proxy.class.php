<?php
/*
 * 接口代理端，可以用于测试或其他使用
*/
class FN_layer_api_restcache_proxy extends FN_layer_api_restcache_client{
	static function getInstance($array){
		return new self();
	}
	public function setProxy($proxy){
		$this->domain = $proxy;
	}
	public function setDomain($domain){
		return $this;
	}
	protected function _call($url,$array=array()){
		if($this->debug) var_dump($url);
		$content = FNbase::getUrlContent($url,FNbase::clearEscape($array));
		if($this->debug) var_dump($content);
		return $content;
	}
	public function domain($domain,$path,$module=''){
		$this->_clear();
		if(empty($module)){
			//模拟多态
			$module = $path;
			$path = '';
		}
		$this->url = $domain.(empty($path) ? '' : ','.str_replace('/',',',$path)).'/'.$module;
		return $this;
	}
	protected function _getUrl(){
		return $this->domain.'/'.$this->url.'/'.$this->action.'?'.substr($this->query,1);
	}
	protected function _parseType($content){
		if($this->debug) var_dump(json_decode($content,true));
		switch($this->type){
			case 'json':
				return $content;
			case 'jquery'://自动获取jquery参数变量
				return $_GET['callback'].'('.$content.')';
			case 'xml':
				return '';
			case 'php':
			default:
				return json_decode($content,true);
		}
	}
	//获取数据,缓存设置无效
	public function get($freq=false){
		$url = $this->_getUrl();
		$content = $this->_call($url);
		return $this->_parseType($content);
	}
	//提交数据
	public function post($array){
		$url = $this->_getUrl();
		$content = $this->_call($url,$array);
		return $this->_parseType($content);
	}
	//删除缓存信息
	public function delete($freq=false){
		$url = $this->_getUrl();
		$content = $this->_call($url);
		return $this->_parseType($content);
	}
}
?>