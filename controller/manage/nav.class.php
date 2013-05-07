<?php
//导航
class controller_manage_navview extends tools_controller_manageview{
	static protected $viewList = array(
		'index'=>array('name'=>'导航管理','shortname'=>'管理'),
		'add'=>array('name'=>'添加','shortname'=>'添加'),
		'edit'=>array('name'=>'修改','shortname'=>'修改','param'=>array('id')),
	); 
	public function index(){
		$nav = FN::i('module.nav');
		$page_info = $this->getPageInfo();
		if(!$page_info){
			$field = array(
				array('name'=>'id','display'=>'ID','type'=>'int'),
				array('name'=>'name','display'=>'名称','align'=>'left','width'=>'300'),
				array('name'=>'url','display'=>'URL','align'=>'left','width'=>'300'),
				array('name'=>'pid','display'=>'PID'),
				array('name'=>'ordernum','display'=>'排序'),
			);
			$grid = array('tree'=>array('columnName'=>'name'));

			$toolbar = array(
				array('view'=>'manage.nav.add','icon'=>'add','param'=>array('id'),'option'=>true),
				array('view'=>'manage.nav.edit','icon'=>'modify','param'=>array('id')),
				array('handle'=>'manage.nav.delete','icon'=>'delete','param'=>array('id'),'batch'=>true),
			);
			$this->_toolbar($toolbar,$grid);

			$other = array(
				'dblclick'=>array('param'=>array('id'),'view'=>'manage.nav.edit')
			);
			$this->_parseroute($other['dblclick']);

			return $this->_list($field,$grid,$other);
		}
		
		$nav_all=$nav->getNavList();
		$result=$nav->getNav($nav_all['list']);
		$this->_parsetree($result);

		$total=1;
		return $this->_listdata($result,$total);
	}

	public function add(){
		$data=empty($this->param['id']) ? array() : array('pid'=>$this->param['id']);
		$nav = FN::i('module.nav');
		$nav_all=$nav->getNavList();
		$nav_list=$nav->getNav($nav_all['list']);
		$this->_parsetree($nav_list);
		$field = array(
			array('name'=>'name','display'=>'名称','type'=>'string'),
			array('name'=>'url','display'=>'url','type'=>'string','width'=>300),
			array('name'=>'pid','display'=>'父菜单','type'=>'combobox','options'=>array(
				//'tree'=>array('data'=>$nav_list,'textFieldName'=>'name','checkbox'=>false),
				'data'=>$nav_list,
				'newline'=>false,
				'link'=>true,
				'treeLeafOnly'=>false,
				'textField'=>'name',
				'valueField'=>'id',
			)),
			array('name'=>'ordernum','display'=>'排序','type'=>'int'),
		);

		return $this->_form($data,$field,'manage.nav.add');
	}

	public function edit(){
		$nav = FN::i('module.nav');
		$nav_all=$nav->getNavList();
		$nav_list=$nav->getNav($nav_all['list']);
		$this->_parsetree($nav_list);

		$result=$nav->getNavFirst(array('id'=>$this->param['id']));
		$data=array(
			'id'=>$this->param['id'],
			'name'=>$result['name'],
			'url'=>$result['url'],
			'pid'=>$result['pid'],
			'ordernum'=>$result['ordernum'],
		);
		$field = array(
			array('name'=>'id','display'=>'ID','type'=>'hidden'),
			array('name'=>'name','display'=>'名称','type'=>'string'),
			array('name'=>'url','display'=>'url','type'=>'string','width'=>300),
			array('name'=>'pid','display'=>'父菜单','type'=>'combobox','options'=>array(
				'data'=>$nav_list,
				'link'=>true,
				'treeLeafOnly'=>false,
				'textField'=>'name',
				'valueField'=>'id',
				'newline'=>false,
			)),
			array('name'=>'ordernum','display'=>'排序','type'=>'int'),
		);

		return $this->_form($data,$field,'manage.nav.edit');
	
	}
}
class controller_manage_navhandle extends tools_controller_managehandle{
	static protected $handleList = array(
		'add'=>array('name'=>'添加','shortname'=>'添加'),
		'edit'=>array('name'=>'修改','shortname'=>'修改','param'=>array('id')),
		'delete'=>array('name'=>'删除','shortname'=>'删除','param'=>array('id'),'batch'=>true),
	);
	public function add(){
		$nav = FN::i('module.nav');
		if(empty($_POST['pid'])){
			$_POST['pid']=0;
		}
		$nav->add($_POST);
		return array('status'=>'success');
	}
	public function edit(){
		$nav = FN::i('module.nav');
		$arr=array();
		$id=$_POST['id'];
		$arr['pid']=$_POST['pid'];
		$arr['name']=$_POST['name'];
		$arr['ordernum']=$_POST['ordernum'];
		$arr['url']=$_POST['url'];
		$nav->edit($id,$arr);
		return array('status'=>'success');
	}
	public function delete(){
		$nav = FN::i('module.nav');
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
			$nav->delete("id in ($id)");
		}
		return array('status'=>'success');
	}
}
?>
