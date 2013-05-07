<?php
class module_log implements FN__single{
	private static $_Instance = null;
	private $user=null;
	static public function getInstance($array=array()){
		if(!self::$_Instance){
			self::$_Instance = new self($array);
		}
		return self::$_Instance;
	}
	private function __construct($array){
		$this->log = FN::i('module.log|DB');
		$this->user = FN::i('module.user');
	}
	//所有日志
	public function getLogList($array=array(),$search=null){
		$result=array();
		$where="1=1";
		if(!empty($search)){
			if(!empty($search['username'])){
				$usernameInfo=$this->user->getUserInfo($search['username'],'username');
				$uid_str=$usernameInfo['uid'];
				$where.=" and user_id=$uid_str ";
			}
			if(!empty($search['starttime'])){
				$starttime=strtotime($search['starttime']);
				$where.=" and datetime > $starttime ";
			}
			if(!empty($search['endtime'])){
				$endtime=strtotime($search['endtime']);
				$where.=" and datetime < $endtime";
			}
		}
		$list=$this->log->where($where)->select($array);
		$list_arr=array();

		$uid_arr=array();
		foreach($list as $k=>$v){
			$uid_arr[$v['user_id']]=$v['user_id'];
			$list_arr[$k]=$v;
			$log_info_format_arr=unserialize($v['log_info']);
			$result_format_arr=unserialize($v['result']);
			$str='';
			if(!empty($log_info_format_arr)){
				foreach($log_info_format_arr as $lk=>$lv){
					$str.="$lk:$lv &nbsp;";
				}
			}
			$list_arr[$k]['log_info_format']=$str;
			$str='';
			if(!empty($result_format_arr)){
				foreach($result_format_arr as $lk=>$lv){
					$str.="$lk:$lv &nbsp;";
				}
			}
			$list_arr[$k]['result_format']=$str;
			$list_arr[$k]['datetime_format']=date('Y-m-d H:i:s',$v['datetime']);
		}
		//用户名
		if(!empty($uid_arr)){
			$userNameInfo=$this->user->getUserList($uid_arr);
		}
		$new_list=array();
		foreach($list_arr as $k=>$v){
			$v['username']=$userNameInfo[$v['user_id']]['username'];
			$new_list[$k]=$v;
		}

		$result['list']=$new_list;
		$total=$this->log->count();
		$result['total']=$total;
		return $result;
	}
	//添加日志
	public function add($arr){
		$user = FN::i('module.user');
		$arr['datetime']=time();
		$arr['user_id']=$user->getUid();
		$arr['log_info'] = FNbase::setEscape(serialize($arr['log_info']),true);
		$arr['result'] = FNbase::setEscape(serialize($arr['result']),true);
		$this->log->add($arr);
	}
	//删除日志
	public function delete($search){
		if(!empty($search)){
			$this->log->where($search)->delete();
		}
	}
}

//日志表
class module_logDB extends FN_layer_sql{
	//对应的数据库字段
	protected $_table='log';
	protected $_field=array('id','log_info','result','user_id','datetime','module_name');
	protected $_pkey='id';
	protected $_aint=true;
}

?>
