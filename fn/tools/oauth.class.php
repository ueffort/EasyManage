<?php
class FN_tools_oauth extends FN_tools_rest implements FN__auto{
	protected $open_id = null;
	protected $access_token = null;
	protected $expires_time = null;
	protected $refresh_token = null;
	protected $oauth = null;
	protected $nickname = null;
	protected $avatar = null;
	public function getVar(){
		return array(
			'oauth'=>$this->oauth,
			'open_id'=>$this->open_id,
			'access_token'=>$this->access_token,
			'expires_time'=>$this->expires_time,
			'refresh_token'=>$this->refresh_token
		);
	}
	public function setVar($array){
		$this->open_id = $array['open_id'];
		$this->access_token = $array['access_token'];
		$this->expires_time = $array['expires_time'];
		$this->refresh_token = $array['refresh_token'];
	}
	public function getUserInfo(){
		return array(
			'nickname'=>$this->nickname,
			'avatar'=>$this->avatar
		);
	}
}