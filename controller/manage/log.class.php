<?php
//日志
class controller_manage_logview extends tools_controller_manageview{
	static protected $viewList = array(
		'index'=>array('name'=>'日志管理','shortname'=>'管理'),
	); 
	public function index(){
		$log = FN::i('module.log');
		$page_info = $this->getPageInfo();
		if(!$page_info){
			$field = array(
				array('name'=>'id','display'=>'ID','type'=>'int'),
				array('name'=>'module_name','display'=>'模块名','width'=>'300'),
				array('name'=>'log_info_format','display'=>'日志信息','width'=>'300'),
				array('name'=>'user_id','display'=>'用户ID','width'=>100),
				array('name'=>'username','display'=>'用户名','width'=>100),
				array('name'=>'datetime_format','display'=>'操作时间','width'=>'200'),
			);
			$grid = $other = array();

			$search_data=array();
			$search_field = array(
				array('name'=>'username','display'=>'用户名'),
				array('name'=>'starttime','display'=>'开始时间','type'=>'date'),
				array('name'=>'endtime','display'=>'结束时间','type'=>'date'),
			);
			$this->_search($search_data,$search_field,$other);

			$grid=array('pageSize'=>20);

			$toolbar = array(
				array('handle'=>'manage.log.delete','icon'=>'delete','param'=>array('id'),'batch'=>true),
				//array('view'=>'manage.devolve.index','icon'=>'modify','data'=>array('type'=>3),'window'=>true),
			);
			$this->_toolbar($toolbar,$grid);
			return $this->_list($field,$grid,$other);
		}

		$pagesize=$page_info['pagesize'];
		$p=($page_info['page']-1)*$pagesize;

		$search = $this->getSearchInfo();
		if(!empty($search)){
			$condition['username']=$search['username'];
			$condition['starttime']=$search['starttime'];
			$condition['endtime']=$search['endtime'];
			$result=$log->getLogList(array('limit'=>array($p,$pagesize)),$condition);
		}else{
			$result=$log->getLogList(array('limit'=>array($p,$pagesize)));
		}
		return $this->_listdata($result['list'],$result['total']);
	}

}
class controller_manage_loghandle extends tools_controller_managehandle{
	static protected $handleList = array(
		'delete'=>array('name'=>'删除日志','shortname'=>'删除','param'=>array('id'),'batch'=>true),
	);
	public function delete(){
		$log = FN::i('module.log');
		if(!$this->isBatch()){
			$id=$_POST['id'];
		}else{
			$str='';
			$arr = $this->getBatch();
			foreach($arr as $k){
				foreach($k as $k2){
					$str.=$k2.',';
				}
			}
			$id=substr($str,0,-1);
		}
		if(!empty($id)){
			$log->delete("id in ($id)");
		}
		return array('status','success');
	}
}
?>
