<?php
class module_tag implements FN__single{
	private static $_Instance = null;
	private $tag = null;
	private $taglist = null;
	static public function getInstance($array=array()){
		if(!self::$_Instance){
			self::$_Instance = new self($array);
		}
		return self::$_Instance;
	}
	private function __construct($array){
		$this->tag = FN::i('module.tag|DB');
		$this->taglist = FN::i('module.tag|listDB');
	}
	//获取标签设置列表
	public function getList($array=array(),$search=array()){
		$count = $this->tag->where($search)->count();
		$list=$this->tag->where($search)->select($array);
		return array('list'=>$list,'total'=>$count);
	}
	//添加标签设置
	public function add($array){
		$order = $this->tag->where(array('module_name'=>$array['module_name']))->field(' max(`order`) ')->selected('one');
		$array['order'] = $order + 1;
		return $this->tag->add($array);
	}
	//修改标签设置
	public function update($tag_id,$array){
		//不允许在修改中更换顺序
		unset($array['order']);
		unset($array['module_name']);
		$this->tag->id = $tag_id;
	
		return $this->tag->edit($array);
	}
	//移动标签顺序
	public function move($tag_id,$order){
		$info = $this->tag->where(array('id'=>$tag_id))->selected('first');
		if(!$info) return false;
		if($order == $info['order']) return true;
		if($order <=1){
			//移动到第一
			$order = 1;
			if($info['order'] == $order) return false;
		}else{
			//移到最后
			$max = $this->tag->where(array('module_name'=>$info['module_name']))->field(' max(`order`) ')->selected('one');
			if($order == false || $order >= $max){
				$order = $max;
				if($info['order'] == $order) return false;
			}
		}
		//下移
		if($order > $info['order']){
			$this->tag->where(array('module_name'=>$info['module_name'],'order'=>'>='.$info['order'],'order'=>'<='.$order))->edit(array('order'=>'=`order`-1'));
		//上移
		}else{
			$this->tag->where(array('module_name'=>$info['module_name'],'order'=>'<='.$info['order'],'order'=>'>='.$order))->edit(array('order'=>'=`order`+1'));
		}
		$this->tag->id = $tag_id;
		return $this->tag->edit(array('order'=>$order));
	}
	//删除标签设置
	public function delete($tag_id){
		$info = $this->tag->where(array('id'=>$tag_id))->selected('first');
		if(!$info) return true;
		$this->tag->where(array('module_name'=>$info['module_name'],'order'=>'>='.$info['order']))->edit(array('order'=>'=`order`-1'));
		$this->tag->id = $tag_id;
		//是否自动删除该模块下的标签数据
		//$this->taglist->where(array('module_name'=>$info['module_name'],'tag_id'=>$info['id']))->delete();
		return $this->tag->delete($tag_id);
	}
	//添加标签
	public function addTag($module_name,$target_id,$tag_id){
		$array = array(
			'module_name'=>$module_name,
			'target_id'=>$target_id,
		);
		//目前一条数据只能设置一个标签，主要是出于展示原因无法处理多个标签性质的显示
		$tag = $this->taglist->where($array)->selected('first');
		if(empty($tag)){
			$array['tag_id'] = $tag_id;
			return $this->taglist->add($array);
		}else{
			$array = array(
				'tag_id'=>$tag_id
			);
			$this->taglist->id = $tag['id'];
			return $this->taglist->edit($array);
		}
	}
	//删除标签取消标签
	public function deleteTag($module_name,$target_id,$tag_id=''){
		$array = array(
			'module_name'=>$module_name,
			'target_id'=>$target_id,
		);
		if(!empty($tag_id)){
			$array['tag_id'] = $tag_id;
		}
		return $this->taglist->where($array)->delete();
	}
	//查找对应数据的标签数据
	public function searchTag($module_name,$target_array){
		return $this->taglist->where(array('module_name'=>$module_name,'target_id'=>$target_array))->select();
	}
}

//标签设置表
class module_tagDB extends FN_layer_sql{
	//对应的数据库字段
	protected $_table='tag';
	protected $_field=array('id','module_name','name','color','description','right','order');
	protected $_pkey='id';
	protected $_aint=true;
}
//标签列表
class module_taglistDB extends FN_layer_sql{
	//对应的数据库字段
	protected $_table='tag_list';
	protected $_field=array('id','module_name','target_id','tag_id');
	protected $_pkey='id';
	protected $_aint=true;
}
?>
