<?php
class controller_module_ligeruiview extends tools_controller_moduleview{
	static protected $viewList = array(
		'baselist'=>array('name'=>'基本列表','shortname'=>'列表'),
		'baseform'=>array('name'=>'基本表单','shortname'=>'表单'),
		'readme'=>array('name'=>'修改说明','shortname'=>'说明')
	);
	protected $tagStatus = 'uid';
	protected $tagList = array('baselist');
	public function baselist(){
		$user = FN::i('module.user');
		$page_info = $this->getPageInfo();
		if(!$page_info){
			$right = FN::i('module.right');
			$role_list = $right->getRoleList();
			$role_list=$this->_listdata($role_list['list']);
			$role_list=$role_list['Rows'];
			$field = array(
				array('name'=>'username','display'=>'用户名'),
				array('name'=>'role_id','display'=>'所属角色','type'=>'select','select'=>$role_list,'textField'=>'name','valueField'=>'id'),
				array('name'=>'status','display'=>'状态','type'=>'select','select'=>$this->_parseSelect(array(1=>'通过',0=>'禁止'))),
			);
			$grid = $other = array();
			
			$search_data=array();
			$search_field = array(
				array('name'=>'username','display'=>'用户名'),
			);
			$this->_search($search_data,$search_field,$other);
			
			$toolbar = array(
				array('view'=>'manage.user.add','icon'=>'add','window'=>true),
				array('view'=>'manage.user.edit','icon'=>'modify','param'=>array('uid'),'window'=>true),
				array('handle'=>'manage.user.delete','icon'=>'delete','param'=>array('uid'),'batch'=>true,'confirm'=>'删除确认！'),
			);
			$this->_toolbar($toolbar,$grid);

			$other['dblclick']=array('param'=>array('uid'),'view'=>'manage.user.edit','window'=>true);
			
			$this->_parseroute($other['dblclick']);
			return $this->_list($field,$grid,$other);
		}
		$pagesize=$page_info['pagesize'];
		$p=($page_info['page']-1)*$pagesize;
		$search = $this->getSearchInfo();
		$search['is_manage'] = 0;
		if(isset($search['username'])){
			$search['username'] = 'like "%'.$search['username'].'%"';
		}
		$result=$user->getList(array('limit'=>array($p,$pagesize)),$search);
		if($result['total'] > 0){
			foreach($result['list'] as $key=>$value){
				unset($value['password']);
				$result[$key] = $value;
			}
		}
		return $this->_listdata($result['list'],$result['total']);
	}
	public function baseform(){
		$field = array(
			array('group'=>'true','display'=>'分组','buttons'=>array(
				array('text'=>'按钮1')
			)),
			array('group'=>'true','display'=>'分页扩展','page'=>5,'nowpage'=>1),
			array('name'=>'radio','display'=>'单选按钮','type'=>'radio','options'=>array(
				'data'=>$this->_parseSelect(array(1=>'单选1',2=>'单选2',3=>'单选3')),
				'newline'=>false
			)),
			array('name'=>'checkbox','display'=>'多选按钮','type'=>'checkbox','options'=>array(
				'data'=>$this->_parseSelect(array(1=>'多选1',2=>'多选2',3=>'多选3')),
				'newline'=>false
			)),
			array('group'=>'true','display'=>'分组1'),
			array('name'=>'date','display'=>'日期选择','type'=>'date','options'=>array(
				'thirdparty'=>'My97DatePicker'
			)),
			array('name'=>'search','display'=>'筛选下拉框','type'=>'combobox','options'=>array(
				'search'=>true,
				'data'=>array(
					array('id'=>'1','text'=>'123456789'),
					array('id'=>'2','text'=>'1abcdefg4'),
					array('id'=>'3','text'=>'2efghijk5'),
					array('id'=>'4','text'=>'3hijklmn6'),
				)
			)),
			array('name'=>'link','display'=>'联级下拉框','type'=>'combobox','options'=>array(
				'link'=>true,
				'newline'=>false,
				'data'=>array(
					array('id'=>'1','text'=>'1','children'=>array(
						array('id'=>'11','text'=>'1_1'),
						array('id'=>'12','text'=>'1_2')
					)),
					array('id'=>'2','text'=>'2','children'=>array(
						array('id'=>'21','text'=>'2_1'),
						array('id'=>'22','text'=>'2_2')
					))
				)
			)),
			array('name'=>'text','display'=>'只读控件','type'=>'text'),
			array('name'=>'description','display'=>'文本框','textarea'=>'ueditor')
		);
		$other = array();
		return $this->_form(array('text'=>'自动赋值'),$field,array('handle'=>''),$other);
	}
	public function readme(){
		return array();
	}
}
class controller_module_ligeruihandle extends tools_controller_modulehandle{
	public function index(){
	
	}
}
?>

