<?php
class FN_platform_qq extends FN_platform{
	private $appid = null;
	private $appkey = null;
	public function __construct($config){
		$this->appid = $config['appid'];
		$this->appkey = $config['appkey'];
	}
	/**
	 * 获取基础服务
	 * @param string $servername  调用的服务名称
	 * @param string $config  映射名称，默认为default
	 * @return object 服务实例
	 */
	public function server($servername,&$config){
		switch($servername){
			case 'oauth':
				$config['appid'] = $this->appid;
				$config['appkey'] = $this->appkey;
				return new FN_platform_qqoauth($config);
		}
	}
}
class FN_platform_qqoauth extends FN_tools_oauth{
	const VERSION = "2.0";
    const GET_AUTH_CODE_URL = "https://graph.qq.com/oauth2.0/authorize";
    const GET_ACCESS_TOKEN_URL = "https://graph.qq.com/oauth2.0/token";
    const GET_OPENID_URL = "https://graph.qq.com/oauth2.0/me";
	const GET_INFO_URL = "https://graph.qq.com/user/get_user_info";
	protected $oauth = 'qq';
	public function login($state){
		$keysArr = array(
			"response_type" => "code",
			"client_id" => $this->config['appid'],
			"redirect_uri" => $this->config['redirect_uri'],
			"state" => $state,
			"scope" => $this->config['scope']
        );
		header('Location:'.$this->combineURL(self::GET_AUTH_CODE_URL, $keysArr));
	}
	public function callback($state){
		if(empty($_GET['state']) || $_GET['state'] != $state) return false;
		$keysArr = array(
			"grant_type" => "authorization_code",
			"client_id" => $this->config['appid'],
			"redirect_uri" => $this->config['redirect_uri'],
			"client_secret" => $this->config['appkey'],
			"code" => $_GET['code']
        );

        $response = $this->get(self::GET_ACCESS_TOKEN_URL, $keysArr);
		$body = $response->getBody();
        if(strpos($body, "callback") !== false){
			$lpos = strpos($body, "(");
			$rpos = strrpos($body, ")");
			$body  = substr($body, $lpos + 1, $rpos - $lpos -1);
			$msg = json_decode($body,true);
			if(isset($msg['error'])) return false;
        }

		$info = array();
		parse_str($body, $info);
		$this->access_token = $info["access_token"];
		$this->open_id = $this->getopenid();
		$this->expires_time = $info['expires_in'];
		$this->refresh_token = $info['refresh_token'];
		return true;
	}
	public function getopenid(){
		$keysArr = array(
			"access_token" => $this->access_token
		);

		$response = $this->get(self::GET_OPENID_URL, $keysArr);

		$body = $response->getBody();
		if(strpos($body, "callback") !== false){
			$lpos = strpos($body, "(");
			$rpos = strrpos($body, ")");
			$body = substr($body, $lpos + 1, $rpos - $lpos -1);
        }

        $user = json_decode($body,true);
        if(isset($user['error'])) return false;

        return $user['openid'];
	}
	public function getUserInfo(){
		$keysArr = array(
            "access_token" => $this->access_token,
			"oauth_consumer_key" => $this->config['appid'],
			"openid" => $this->open_id,
			"format" => 'json'
        );

        $response = $this->get(self::GET_INFO_URL, $keysArr);
		$body = $response->getBody();
		$info = json_decode($body,true);
		if($info['ret']>0) return false;//输出错误信息
		$this->nickname = $info['nickname'];
		$this->avatar = empty($info['figureurl_qq_2']) ? $info['figureurl_qq_1'] : $info['figureurl_qq_2'];
		return parent::getUserInfo();
	}
	public function verifycode(){
		return isset($_GET['code']);
	}
}
