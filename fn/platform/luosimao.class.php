<?php
//Luosimao短信平台
class FN_platform_luosimao extends FN_platform{
	private $accesskey = null;

	public function __construct($config){
		$this->accesskey = $config['accesskey'];
	}
	//统一调用云平台服务
	/**
	 * 获取基础服务
	 * @param string $servername  调用的服务名称
	 * @param string $config  映射名称，默认为default
     * @return class server 服务实例
	 */
	protected function _server($servername,$config){
		switch($servername){
			case 'sms':
				$config['accesskey'] = $this->accesskey;
				return new FN_platform_luosimao_sms($config);
        }
	}
}
class FN_platform_luosimao_sms extends FN_tools_rest{
	//http://luosimao.com/docs/api/
	const SEND_URL = "https://sms-api.luosimao.com/v1/send.json";
	const STATUS_URL = "https://sms-api.luosimao.com/v1/status.json";

	//发送单条短信
	public function send($mobile,$message){
		$fields = array(
			'mobile'=>$mobile,
			'message'=>$message.'【'.$this->config['sign'].'】'
		);
		$response = $this->post(self::SEND_URL,$fields,array('username'=>'api','password'=>'key-'.$this->config['accesskey']));
		$code = $response->getBodyJson();
		if(!isset($code['error'])) return 0;//发送失败，稍后发送
		switch($code['error']){
			case 0:return 1;//发送成功
			case -10://检查api key是否和各种中心内的一致，调用传入是否正确
			case -20://短信余额不足
			case -31://短信内容存在敏感词
			case -32://短信内容末尾增加签名信息eg.【公司名称】
				return -1;//系统错误
			case -40:
				return -2;//手机号错误
		}
	}
	//账户信息(余额)
	public function status(){
		$response = $this->get(self::STATUS_URL,array(),array('username'=>'api','password'=>'key-'.$this->config['accesskey']));
		$code = $response->getBodyJson();
		if(!isset($code['error'])) return 0;//发送失败，稍后发送
		switch($code['error']){
			case 0:return $code['deposit'];//返回余额
			case -10://检查api key是否和各种中心内的一致，调用传入是否正确
				return -1;//系统错误
		}
	}
}
