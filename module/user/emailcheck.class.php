<?php
//验证邮箱操作接口
class module_user_emailcheck extends module_user{
	//验证邮箱
	public function verifyCode($code){
		$codeClass = FN::i('module.code');
		$codeClass->setType(get_called_class());
		$uid = $codeClass->verifyCode($code);
		if(empty($uid)||$uid != $this->getUid()) return false;
		$this->editInfo(array('emailstatus'=>1));
		$this->user['emailStatus'] = 1;
		$codeClass->updateCode();
		return true;
	}
	//发送邮箱验证码
	public function getCode(){
		$codeClass = FN::i('module.code');
		$codeClass->setType(get_called_class());
		return $codeClass->getCode($this->user['uid']);
	}
	//判断邮箱是否验证
	public function getEmailStatus(){
		if(empty($this->user['email'])) return 0;
		if(!isset($this->user['emailstatus'])){
			$this->_getUser();
		}
		//未验证
		if(empty($this->user['emailStatus'])) $this->user['emailStatus'] = 0;
		return $this->user['emailStatus'];
	}
}
?>