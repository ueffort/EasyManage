<?php
//用户验证随机代码表
class module_code implements FN__single{
	static private $_Instance = null;
	static private $_TypeArray = array(1=>'lostpass',2=>'emailcheck');
	private $type = null;
	static public function getInstance($array=array()){
		if(empty(self::$_Instance)){
			self::$_Instance = new self($array);
		}
		return self::$_Instance;
	}
	private function __construct(){
		$this->codelist = FN::i('module.code|listDB');
	}

 //获取状态代码
	public function getCode($uid){
		if(empty($this->type)) return false;
		$this->cleanCode($uid);
		$code = FNbase::random(6);
	
		$data['uid']=$uid;
		$data['status']="0";
		$data['type']=$this->type;
		$data['code']=$code;
		$data['inserttime']=FNbase::getTime();
		$data['updatetime']=FNbase::getTime();
		$this->codelist->add($data);		
		return $code.'-'.substr(md5($uid),0,-7);
	}
	//验证代码
	public function verifyCode($code,$verifyTime=false){
		if(empty($this->type)) return false;
		list($code,$uidMD5) = explode('-',$code);
		
		$array['field']='cid,uid';
		$array['where']='type="'.$this->type.'" and status = 0 and code="'.$code.'"';
		if($verfiyTime){
			$array['where'].= ' and inserttime > '.(FNbase::getTime() + $verifyTime);
		}
		$list=$this->codelist->select($array);
		foreach ($list as $row){
			if(substr(md5($row['uid']),0,-7) == $uidMD5){
				$this->cid = $row['cid'];
				$uid = $row['uid'];
				break;
			}
		}
		return $uid;
	}
	//清除代码
	public function cleanCode($uid){
		if(empty($this->type)) return false;
		
		$data =array('status'=>2,'updatetime'=>FNbase::getTime());
		$array=array('uid'=>$uid,'type'=>$this->type,'status'=>0);
		$this->codelist->where($array)->edit($data);
		
		return true;
	}
	//更新代码状态
	public function updateCode(){
		if(empty($this->cid)) return false;
		
		$data =array('status'=>1,'updatetime'=>FNbase::getTime());
		$this->codelist->cid=$this->cid;
		$this->codelist->edit($data);
		return true;
	}
	//获取代码类型
	public function setType($type,$all=true){
		if(empty($type)) return false;
		if($all) $type = FN::lastName($type);
		$this->type = array_search($type,self::$_TypeArray);
		return $this->type;
	}
}
//用户验证随机代码表
class module_codelistDB extends FN_layer_sql{
	//对应的数据库字段
	protected $_table='code_list';
	protected $_field=array('cid','uid','status','type','code','inserttime','updatetime');
	protected $_pkey='cid';
	protected $_aint=true;
}
?>