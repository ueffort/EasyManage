<?php
//标签
class controller_manage_tagview extends tools_controller_manageview{
	protected static $viewList = array(
		'index'=>array('name'=>'标签管理','shortname'=>'管理'),
		'add'=>array('name'=>'添加标签','shortname'=>'添加'),
		'edit'=>array('name'=>'编辑标签','shortname'=>'编辑','param'=>array('id')),
	);
	public function index(){
		$tag = FN::i('module.tag');
		$page_info = $this->getPageInfo();
		if(!$page_info){
			$other = array();
			$grid = array();
			$toolbar = array(
				array('view'=>'manage.tag.add','icon'=>'add','window'=>true),
				array('view'=>'manage.tag.edit','icon'=>'modify','param'=>array('id'),'window'=>true),
				array('handle'=>'manage.tag.move','icon'=>'up','child'=>'up','param'=>array('id')),
				array('handle'=>'manage.tag.move','icon'=>'down','child'=>'down','param'=>array('id')),
				//array('view'=>'manage.devolve.index','icon'=>'modify','data'=>array('type'=>4),'window'=>true),
				array('handle'=>'manage.tag.delete','icon'=>'delete','confirm'=>'确定删除该设置？','param'=>array('id'),'batch'=>'true'),
			);
			$this->_toolbar($toolbar,$grid);
			$array = array(0=>123123,1=>12312312,2=>123123232321);
			$field = array(
				array('name'=>'module_name','display'=>'模块名称'),
			);
			$data = array();
			$this->_search($data,$field,$list);
			$dblclick = array('param'=>array('id'),'view'=>'manage.tag.edit','window'=>true);
			$this->_dblclick($dblclick,$list);
			$field = array(
				array('name'=>'id','display'=>'序号','type'=>'hidden'),
				array('name'=>'module_name','display'=>'模块名称'),
				array('name'=>'name','display'=>'标签名称'),
				array('name'=>'color','display'=>'颜色','type'=>'color','width'=>'30'),
			);
			return $this->_list($field,$grid,$list);
		}
		$pagesize=$page_info['pagesize'];
		$p=($page_info['page']-1)*$pagesize;
		$array['order']='`module_name` asc,`order` asc';
		$array['limit']=array($p,$pagesize);
		$search = $this->getSearchInfo();
		$result=$tag->getList($array,$search);
		return $this->_listdata($result['list'],$result['total']);
	}
	public function add(){
		$field = array(
			array('group'=>'true','display'=>'模块设置'),
			array('name'=>'module_name','display'=>'模块名称'),
			array('group'=>'true','display'=>'标签设置'),
			array('name'=>'name','display'=>'标签名称'),
			array('name'=>'color','display'=>'标签颜色'),
			array('name'=>'description','display'=>'标签描述','type'=>'textarea'),
		);
		$other = array();
		return $this->_form(array(),$field,array('handle'=>'manage.tag.add'),$other);
	}
	public function edit(){
		$tag = FN::i('module.tag');
		$result = $tag->getList(array(),array('id'=>$this->param['id']));
		list($data) = array_slice($result['list'],0,1);
		$field = array(
			array('name'=>'id','type'=>'hidden'),
			array('group'=>'true','display'=>'模块设置'),
			array('name'=>'module_name','display'=>'模块名称','type'=>'text'),
			array('group'=>'true','display'=>'标签设置'),
			array('name'=>'name','display'=>'标签名称'),
			array('name'=>'color','display'=>'标签颜色'),
			array('name'=>'description','display'=>'标签描述','type'=>'textarea'),
		);
		$other = array();
		return $this->_form($data,$field,array('handle'=>'manage.tag.update'),$other);
	}
}
class controller_manage_taghandle extends tools_controller_managehandle{
	protected static $handleList = array(
		'add'=>array('name'=>'添加标签设置','shortname'=>'添加'),
		'update'=>array('name'=>'更新标签','shortname'=>'更新','param'=>array('id')),
		'move'=>array('name'=>'移动','shortname'=>'移动','param'=>array('id','order'),'children'=>array(
			'up'=>array('name'=>'上移','shortname'=>'上移','data'=>array('order'=>'up')),
			'down'=>array('name'=>'下移','shortname'=>'下移','data'=>array('order'=>'down')),
		)),
		'delete'=>array('name'=>'删除标签','shortname'=>'删除','param'=>array('id'),'batch'=>true)
	);
	public function add(){
		$tag = FN::i('module.tag');
		$info = $tag->add($_POST);
		return array('success'=>'success');
	}
	public function update(){
		$tag = FN::i('module.tag');
		$info = $tag->update($_POST['id'],$_POST);
		return array('success'=>'success');
	}
	public function move(){
		$tag = FN::i('module.tag');
		$list = $tag->getList(array(),array('id'=>$_POST['id']));
		if($list['total']==0) return array('error'=>'标签不存在');
		list($info) = array_slice($list['list'],0,1);
		if($_POST['order'] == 'up'){
			$return = $tag->move($_POST['id'],$info['order']-1);
		}elseif($_POST['order'] == 'down'){
			$return = $tag->move($_POST['id'],$info['order']+1);
		}else{
			$return = $tag->move($_POST['id'],$_POST['order']);
		}
		return array('success'=>'success');
	}
	public function delete(){
		$tag = FN::i('module.tag');
		if(!$this->isBatch()){
			if(empty($_POST['id'])){
				return array('error'=>'noparam');
			}
			$tag->delete($_POST['id']);
			return array('success'=>'success');
		}else{
			$id_arr=array();
			$arr = $this->getBatch();
			foreach($arr as $k){
				if(empty($k['id'])){
					return array('error'=>'noparam');
				}
			}
			foreach($arr as $k){
				$tag->delete($k['id']);
			}
			return array('success'=>'success');
		}
	}
}
?>

