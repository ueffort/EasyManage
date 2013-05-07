<?php
//忘记密码操作接口
class module_user_lostpass extends module_user{
	//发送重置验证码
	public function getCode(){
		$codeClass = FN::i('module.code');
		$codeClass->setType(get_called_class());
		return $codeClass->getCode($this->getUid());
	}
	//检验验证码
	public function verifyCode($code,$update = false){
		$codeClass = FN::i('module.code');
		$codeClass->setType(get_called_class());
		$uid = $codeClass->verifyCode($code,86400);
		if(empty($uid)) return false;
		$this->_setUser($uid,'uid');
		if($update){
			$codeClass->updateCode();
		}
		return true;
	}
}
?>