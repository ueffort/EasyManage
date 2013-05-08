<?php
//视图控制器,属于项目内
//会自动加载项目目录下的templates目录
//自动通过view参数执行方法并输出页面
class tools_controllerview implements FN__single{
	static private $_Instance = null;
	protected static $viewList = array();//视图列表
	protected $tagStatus = false;//模块的TAG功能是否开启
	protected $tagField = '__TAG__';//如果与实际字段冲突，可以在子类中重置该设置
	protected $param = array();
	protected $action = null;
	protected $ajax = false;
	protected $template = null;
	protected $_link = 'default';
	protected $classname = '';
	protected $controllname = '';
	static public function getInstance($array=array()){
		$class = get_called_class();
		return new $class($array,$class);
	}
	private function __construct($array,$class){
		$this->classname = $class;
		$this->controllname = call_user_func_array(array($this->classname,'getControllName'),array());
		$this->template = FN::server('template',$this->_link);
		if(defined('WEB_PATH')){
			//如果是web访问，传递路径变量
			$web_path = FN::getConfig('global/web_path');
			if(!$web_path) $web_path = WEB_PATH;
			$this->template->assign('url',$web_path);
			$this->template->assign('static',$web_path.'static/');
		}
		$this->action = empty($array['action']) ? '' : $array['action'];
		$this->param = empty($array['param']) ? array() : $array['param'];
		$_POST = FNbase::setEscape($_POST);
		$this->param = array_merge($this->param,$_POST);
		$this->ajax = FNbase::isAJAX();
		$this->_init_before($this->action);
		$this->_init($this->action);
		$result = $this->_view($this->action);
		$this->_view_after($result);
	}
	//供继承类扩展，用于初始化该控制器
	protected function _init($action){
		return true;
	}
	protected function _init_before($action){
		return true;
	}
	protected function _view_after($result){
		return true;
	}
	//供继承类扩展，用于进行页面输出
	protected function _view($view = '_default'){
		//$info = static::getView($view);
		$info = call_user_func_array(array($this->classname,'getView'),array($view));
		if($info && method_exists($this,$view)){
			//$param = self::getViewAttr($view,'param');
			$param = call_user_func_array(array($this->classname,'getViewAttr'),array($view,'param'));
			if(!empty($param) && is_array($param)){
				foreach($param as $value){
					if(empty($this->param[$value])) $this->_message(array('error'=>'noparam'));
				}
			}
			$result = $this->$view();
		}else{
			$this->_message(array('error'=>'null'));
		}
		if($this->ajax){
			echo json_encode($result);
		}else{
			$this->template->assign('result',$result);
			$op = $this->controllname.'.'.$view;
			$url = $op.'.view';
			$file = str_replace('.','/',$op).'.html';
			if(file_exists(PROJECT_PATH."templates/".$file)){
				$this->template->assign('view',$file);
			}else{
				$this->template->assign('view',false);
			}
			$this->template->assign('viewurl',$url);
			$this->template->assign('viewdata',$this->param);
			$this->template->assign('viewtitle',self::getViewAttr($view,'name'));
			$this->_show('view');
		}
		return $result;
	}
	//用于进行页面消息的显示
	protected function _message($array){
		if($this->ajax){
			echo json_encode($array);
			exit;
		}
		$this->template->assign('message',$array);
		$this->_show('message');
	}
	protected function _show($template){
		$this->template->display(PROJECT_PATH."templates/".$template.".html");
		exit;
	}
	protected function _filefield(&$element,$file=array()){
		if(!empty($file['url'])) $file['handle'] = $file['url'];
		$this->_parseroute($file,'name',array('handle'));
		$element['options'] = $file;
		$element['type'] = 'file';
		return $element;
	}
	protected function _searchfield(&$element,$search=array()){
		if(!empty($search['url'])) $search['view'] = $search['url'];
		$this->_parseroute($search,'shortname',array('view'));
		$element['options'] = $search;
		$element['type'] = 'search';
		return $element;
	}
	protected function _gridfield($fields,&$element,$grid=array()){
		$grid['columns'] = $fields;
		$grid['columnWidth'] = '120';
		if(!empty($grid['url'])) $grid['view'] = $grid['url'];
		$this->_parseroute($grid,'shortname',array('view'));
		$element['options'] = $grid;
		return $element;
	}
	protected function _toolbar($items,&$element,$other=array()){
		if(empty($items) || !is_array($items)) return $element;
		foreach($items as $key=>$value){
			$items[$key] = $this->_parseroute($value,'shortname');
		}
		$other['items'] = $items;
		$element['toolbar'] = $other;
		return $element;
	}
	protected function _menu($items,&$element,$other=array()){
		if(empty($items) || !is_array($items)) return $element;
		foreach($items as $key=>$value){
			$items[$key] = $this->_parseroute($value,'shortname');
		}
		$other['items'] = $items;
		$element['menu'] = $other;
		return $element;
	}
	protected function _search($data,$fields,&$element,$other=array()){
		if(empty($fields) || !is_array($fields)) return $element;
		$other['fields'] = $fields;
		$other['data'] = $data;
		$other['mode'] = 'row';
		$other['space'] = '10';
		$element['search'] = $other;
		return $element;
	}
	protected function _dblclick($items,&$element,$other=array()){
		if(empty($items) || !is_array($items)) return $element;
		$this->_parseroute($items,'name');
		$other = array_merge($items,$other);
		$element['dblclick'] = $other;
		return $element;
	}
	protected function _list($field,$grid=array(),$list=array()){
		$grid['columns'] = $field;
		$grid['columnWidth'] = '120';
		if($this->tagStatus){
			$tag = FN::i('module.tag');
			$tag_list = $tag->getList(array(),array('module_name'=>$this->controllname));
			$tag_list = $tag_list['list'];
			if(!empty($tag_list)){
				if(empty($grid['toolbar'])){
					$this->_toolbar(array(),$grid);
				}
				$tagbutton = array();
				foreach($tag_list as $key=>$value){
					$button = array(
						'text'=>$value['name'],
						'handle'=>'manage.tag.list.add',
						'param'=>array($this->tagStatus),
						'filter'=>array($this->tagField=>"/^(?!".$value['id'].").*$/"),
						'data'=>array('module_name'=>$this->controllname,'tag_id'=>$value['id'],'target_field'=>$this->tagStatus),
						'color'=>$value['color'],
					);
					$tagbutton[] = $this->_parseroute($button,'shortname',array('handle'));
					$tag_list_tmp[$value['id']] = $value['color'];
				}
				$tagbutton[] = array(
					'line'=>true
				);
				$button = array(
					'handle'=>'manage.tag.list.delete',
					'param'=>array($this->tagStatus),
					'filter'=>array($this->tagField=>"/^\d+$/"),
					'data'=>array('module_name'=>$this->controllname,'target_field'=>$this->tagStatus),
				);
				$tagbutton[] = $this->_parseroute($button,'shortname',array('handle'));
				$item = array(
					'text'=>'标签设置',
					'icon'=>'pager',
					'param'=>array($this->tagStatus)
				);
				$this->_menu($tagbutton,$item);
				$grid['toolbar']['items'][] = $item;
				array_unshift($grid['columns'],array('name'=>$this->tagField,'display'=>'标签','type'=>'color','list'=>$tag_list_tmp,'width'=>'30'));
			}
		}
		if(empty($grid['view'])) $grid['view'] = $this->controllname.'.'.$this->action;
		$this->_parseroute($grid,'name',array('view'));
		$list['type'] = 'list';
		$list['grid'] = $grid;
		return $list;
	}
	protected function _listdata($data,$count=false){
		$new_data=$taglist=array();
		if(!empty($data)){
			if($this->tagStatus){
				foreach($data as $k=>$v){
					if(!empty($v[$this->tagStatus])) $id_array[]=$v[$this->tagStatus];
				}
			}else{
				foreach($data as $k=>$v) $new_data[] = $v;
			}
		}
		if($this->tagStatus && !empty($data)){
			$tag = FN::i('module.tag');
			$list = $tag->searchTag($this->controllname,$id_array);
			$list_tmp = array();
			if(!empty($list)){
				foreach($list as $key=>$value){
					$list_tmp[$value['target_id']] = $value['tag_id'];
				}
			}
			foreach($data as $k=>$v){
				if(!empty($list_tmp[$v[$this->tagStatus]])){
					$v[$this->tagField] = $list_tmp[$v[$this->tagStatus]];
				}
				$new_data[] = $v;
			}
		}
		return array('Rows'=>$new_data,'Total'=>(string)$count);
	}
	protected function _form($data,$field,$form=array(),$other=array()){
		if(empty($other) && !is_array($form)){
			$url = $form;
			$form = array();
			$form['handle'] = $url;
		}
		if($this->tagStatus && !empty($data[$this->tagStatus])){
			$tag = FN::i('module.tag');
			$tag_list = $tag->getList(array(),array('module_name'=>$this->controllname));
			$tag_list = $tag_list['list'];
			if(!empty($tag_list)){
				if(empty($other['toolbar'])){
					$this->_toolbar(array(),$other);
				}
				$list = $tag->searchTag($this->controllname,$data[$this->tagStatus]);
				$taginfo = array();
				if(!empty($list)){
					list($taginfo) = array_slice($list,0,1);
					$data[self::$tagField] = $taginfo['tag_id'];
					$taginfo = array_merge($taginfo,$tag_list[$taginfo['tag_id']]);
				}
				$tagbutton = array();
				foreach($tag_list as $key=>$value){
					$button = array(
						'text'=>$value['name'],
						'handle'=>'manage.tag.list.add',
						'param'=>array($this->tagStatus),
						'filter'=>array($this->tagField=>"/^(?!".$value['id'].").*$/"),
						'data'=>array('module_name'=>$this->controllname,'tag_id'=>$value['id'],'target_field'=>$this->tagStatus),
						'color'=>$value['color'],
						'autoOp'=>2
					);
					$tagbutton[] = $this->_parseroute($button,'shortname',array('handle'));
				}
				$tagbutton[] = array(
					'line'=>true
				);
				$button = array(
					'handle'=>'manage.tag.list.delete',
					'param'=>array($this->tagStatus),
					'filter'=>array($this->tagField=>"/^\d+$/"),
					'data'=>array('module_name'=>$this->controllname,'target_field'=>$this->tagStatus),
					'autoOp'=>2
				);
				$tagbutton[] = $this->_parseroute($button,'shortname',array('handle'));
				$item = array(
					'text'=>'标签设置:',
					'icon'=>'pager',
					'param'=>array($this->tagStatus)
				);
				if($taginfo){
					$item = array(
						'text'=>'标签:'.$taginfo['name'],
						'color'=>$taginfo['color'],
						'param'=>array($this->tagStatus)
					);
				}else{
					$item = array(
						'text'=>'标签设置:',
						'icon'=>'pager',
						'param'=>array($this->tagStatus)
					);
				}
				$this->_menu($tagbutton,$item);
				$other['toolbar']['items'][] = $item;
				
			}
		}
		$form['fields'] = $field;
		$form['data'] = $data;
		if(!empty($form['url'])) $form['handle'] = $form['url'];
		$this->_parseroute($form,'name',array('handle'));
		//验证操作
		if(!empty($form['validate'])) $form['validate'] = $form['validate'].'.view';
		$other['type'] = 'form';
		$other['form'] = $form;
		return $other;
	}
	protected function _parseroute(&$data,$namefield='name',$field=array('view','handle')){
		if(!empty($data) && is_array($data)){
			if(isset($data['children'])){
				foreach($data['children'] as $key=>$value){
					$data['children'][$key] = $this->_parseroute($value,$namefield,$field);
				}
			}
			foreach($field as $value){
				if(!array_key_exists($value,$data)) continue;
				$controller = self::getController($data[$value],$value,empty($data['child']) ? false : $data['child']);
				if(empty($data['text'])){
					$data['text'] = $controller[$namefield];
				}else{
					$data['text'] = sprintf($data['text'],$controller[$namefield]);
				}
				$data['url'] = $data[$value].'.'.$value;
				if(!empty($controller['data'])) $data['data'] = empty($data['data']) ? $controller['data'] : array_merge($controller['data'],$data['data']);
			}
		}
		return $data;
	}
	protected function _parsetree(&$data,$field='children'){
		if(!empty($data) && is_array($data)){
			$data_new = array();
			foreach($data as $key=>$value){
				$this->_parsetree($value[$field],$field);
				$data_new[] = $value;
			}
			$data = $data_new;
		}
		return $data;
	}
	protected function _parseSelect($array){
		if(empty($array) || !is_array($array)) return array();
		$array_tmp = array();
		foreach($array as $key=>$value){
			$array_tmp[] = array('id'=>$key,'text'=>$value);
		}
		return $array_tmp;
	}
	protected function getPageInfo(){
		if(empty($_POST['dataaction'])) return false;
		$this->ajax = true;//list的分页读取数据，是ajax操作
		return array(
			'page'=>empty($_POST['page']) ? 1 : $_POST['page'],
			'pagesize'=>empty($_POST['pagesize']) ? 10 : $_POST['pagesize'],
			'sortname'=>empty($_POST['sortname'])? '' : $_POST['sortname'],
			'sortorder'=>empty($_POST['sortorder']) ? 'asc' : $_POST['sortorder']
		);
	}
	protected function getSearchInfo(){
		//初始化的时候转义了，所以不论系统设置都需要清除转义
		return empty($_POST['search']) ? array() :json_decode(FNbase::clearEscape($_POST['search'],true),true);
	}
	public static function getViewList(){
		$class = get_called_class();
		eval('$viewList = '.$class.'::$viewList;');
		return $viewList;
	}
	public static function getViewAttr($action,$attr){
		$class = get_called_class();
		eval('$viewList = '.$class.'::$viewList;');
		return empty($viewList[$action][$attr]) ? '' : $viewList[$action][$attr];
	}
	public static function getView($action,$child=false){
		$class = get_called_class();
		eval('$viewList = '.$class.'::$viewList;');
		if(empty($viewList[$action])) return false;
		$info = $viewList[$action];
		if($child && !empty($viewList[$action]['children'][$child])) $info = array_merge($info,$viewList[$action]['children'][$child]);
		return $info;
	}
	static public function getController($module,$type,$child = false){
		$pos = strrpos($module,'.');
		$action = substr($module,$pos+1);
		//$classname = FN::i('controller.'.substr($module,0,$pos).'|'.$type);
		list($classname) = FN::parseName('controller.'.substr($module,0,$pos).'|'.$type);
		$function = 'get'.$type;
		//return $classname::$function($action,$child);
		return call_user_func_array(array($classname,$function),array($action,$child));
	}
	static public function getControllName(){
		return substr(FN::callName(get_called_class(),'view',false),strlen('controller.'));
	}
}
//操作控制器
class tools_controllerhandle implements FN__single{
	static private $_Instance = null;
	protected static $handleList = array();//操作列表
	protected $param = array();
	protected $action = null;
	protected $classname = '';
	protected $controllname = '';
	static public function getInstance($array=array()){
		$class = get_called_class();
		return new $class($array,$class);
	}
	private function __construct($array,$class){
		$this->classname = $class;
		$this->controllname = call_user_func_array(array($this->classname,'getControllName'),array());
		$_POST = FNbase::setEscape($_POST);
		$this->action = empty($array['action']) ? '' : $array['action'];
		$this->param = empty($array['param']) ? array() : $array['param'];

		$this->_init_before($this->action);
		$this->_init($this->action);
		$result = $this->_handel($this->action);
		$this->_handle_after($result);
		exit;
	}
	//供继承类扩展，用于初始化该控制器
	protected function _init($action){
		return true;
	}
	protected function _init_before($action){
		return true;
	}
	protected function _handle_after($result){
		return true;
	}
	//供继承类扩展，用于进行页面输出
	protected function _handel($handle){
		$info = call_user_func_array(array($this->classname,'getHandle'),array($handle));
		//$info = self::getHandle($handle);
		if($info && method_exists($this,$handle)){
			if($this->isBatch()){
				//if(!self::getHandleAttr($handle,'batch')) $result = array('error'=>'nobatch');
				if(call_user_func_array(array($this->classname,'getHandleAttr'),array($handle,'batch'))) $result = array('error'=>'nobatch');
			}else{
				//$param = self::getHandleAttr($handle,'param');
				$param = call_user_func_array(array($this->classname,'getHandleAttr'),array($handle,'param'));
				if(!empty($param) && is_array($param)){
					foreach($param as $value){
						if(empty($_POST[$value])) $result = array('error'=>'noparam');
					}
				}
			}
			if(empty($result)) $result = $this->$handle();
		}else{
			$result = array('error'=>'null');
		}
		if(empty($result) || !is_array($result)) $result = array('error'=>'noresult');
		echo json_encode($result);
		return $result;
	}
	protected function isBatch(){
		return !empty($_POST['_batch']) && !empty($_POST['batch']) ? true : false;
	}
	protected function getBatch(){
		return empty($_POST['batch']) ? array() :json_decode(FNbase::clearEscape($_POST['batch']),true);
	}
	public static function getHandleList(){
		$class = get_called_class();
		eval('$handleList = '.$class.'::$handleList;');
		return $handleList;
	}
	public static function getHandleAttr($action,$attr){
		$class = get_called_class();
		eval('$handleList = '.$class.'::$handleList;');
		return empty($handleList[$action][$attr]) ? '' : $handleList[$action][$attr];
	}
	public static function getHandle($action,$child=false){
		$class = get_called_class();
		eval('$handleList = '.$class.'::$handleList;');
		if(empty($handleList[$action])) return false;
		$info = $handleList[$action];
		if($child && !empty($handleList[$action]['children'][$child])) $info = array_merge($info,$handleList[$action]['children'][$child]);
		return $info;
	}
	static public function getControllName(){
		return substr(FN::callName(get_called_class(),'handle',false),strlen('controller.'));
	}
}
?>
