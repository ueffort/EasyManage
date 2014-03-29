<?php
class FN_platform_weibo extends FN_platform{
	private $appkey = null;
	private $appsecret = null;
	public function __construct($config){
		$this->appkey = $config['appkey'];
		$this->appsecret = $config['appsercet'];
	}
	//统一调用云平台服务
	/**
	 * 获取基础服务
	 * @param string $servername  调用的服务名称
	 * @param string $config  映射名称，默认为default
	 * @return object 服务实例
	 */
	public function server($servername,&$config){
		switch($servername){
			case 'oauth':
				$config['appkey'] = $this->appkey;
				$config['appsecret'] = $this->appsecret;
				return new FN_platform_weibooauth($config);
		}
	}
}
class FN_platform_weibooauth extends FN_tools_oauth{
	const VERSION = "2.0";
    const GET_AUTH_CODE_URL = "https://api.weibo.com/oauth2/authorize";
    const GET_ACCESS_TOKEN_URL = "https://api.weibo.com/oauth2/access_token";
	const GET_INFO_URL = "http://api.t.sina.com.cn/users/show.json";
	protected $oauth = 'weibo';
	public function login($state){
		$keysArr = array(
			"client_id" => $this->config['appkey'],
			"redirect_uri" => $this->config['redirect_uri'],
			"state" => $state,
			"scope" => $this->config['scope']
        );
		header('Location:'.$this->combineURL(self::GET_AUTH_CODE_URL, $keysArr));
	}
	public function callback(){
		$keysArr = array(
			"grant_type" => "authorization_code",
			"client_id" => $this->config['appkey'],
			"redirect_uri" => urlencode($this->config['uri']),
			"client_secret" => $this->config['appsecret'],
			"code" => $_GET['code']
        );

        $response = $this->get(self::GET_ACCESS_TOKEN_URL, $keysArr);

		$info = json_decode($response,true);
		if(isset($info['eror'])){
			return false;
		}

		$this->access_token = $info["access_token"];
		$this->open_id = $info['uid'];
		$this->expires = $info['expires_in'];
		$this->refresh_token = '';//不支持刷新授权
		return true;
	}
	public function getinfo(){
		$keysArr = array(
            "source" => $this->config['appkey'],
			"user_id" => $this->open_id,
			"format" => 'json'
        );

        $response = $this->get(self::GET_INFO_URL, $keysArr);
		$info = json_decode($response,true);
		if(isset($info['eror'])){
			return false;
		}
		$this->nickname = $info['screen_name'];
		$this->avatar = $info['profile_image_url'];
		return parent::getUserInfo();
	}
	public function verifycode(){
		return isset($_GET['code']);
	}
}