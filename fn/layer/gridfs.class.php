<?php
/*
$fileserver = XG::DB('file');
var_dump($fileserver->storeFile('D:\git.ppk','other','1'));//添加
$array = $fileserver->select('other','1');
foreach($array as $file){
	var_dump($file);
	$fileserver->setFile($file);//设置，才能修改
}
$file->file['filename']='123123.exe';
var_dump($fileserver->edit($file->file));//必须传递整个文件信息才能修改，否则会替换文件的内容
$fileserver->setFile($file);
var_dump($fileserver->find());//可以直接查询已经设置的文件
$fileserver->delete();//可以直接删除已经设置的文件
*/
class FN_layer_gridfs implements FN__single{
	private $_db=null;
	private $_gridfs=null;
	private $_field=array();
	private $_error=null;
	public $id=null;//指定文档ID
	
	protected $_url=null;//图片在浏览器上的前缀链接，和web server配合设置:设定规则，域名/dbname/(prefix)/filename ，prefix可选，web server需要注意
	protected $_onlyimage=false;//快捷方式，只支持png，jpg，jpeg，gif4种图片格式的上传，
	protected $_mime = false;//支持所有格式，如果要设定格式需设置成数组，并填入对应的MIME类型名
	protected $_link='default';//数据库映射名。默认是default
	protected $_dbname=null;//数据库名
	protected $_prefix=null;//数据集前缀，这个和sql里面的prefix不同，Gridfs没有table选项
	protected $_domain='domain';//文档命名空间字段
	protected $_autoid='autoid';//文档自增长字段
	static public function getInstance($array = array()){
		$class = get_called_class();
		return new $class();
	}
	private function __construct(){
		$config = FN::serverConfig('database',$this->_link);
		if(empty($config) || !in_array($config['drive'],array('mongodb'))){
			$this->_error = 5;//配置错误
			return false;
		}
		$this->_db = FN::server('database',$this->_link);
		$dbname = $this->_db->selectDB($this->_dbname);
		if(empty($this->_prefix)){
			$this->_gridfs = $dbname->getGridFS();
		}else{
			$this->_gridfs = $dbname->getGridFS($this->_prefix);
		}
		if($this->_onlyimage && !$this->_mime){
			$this->_mime = array('image/gif','image/png','image/jpeg');
		}
	}
	public function getUrl(){
		return $this->_url.$this->_dbname.'/'.(empty($this->_prefix) ? '':'/'.$this->_prefix);
	}
	public function getDomain(){
		return $this->_domain;
	}
	public function getAutoid(){
		return $this->_autoid;
	}
	//自动获取表单上传的文件名用来获取文件的扩展名及文件的originalname
	public function storeUpload($upload,$domain,$autoid,$other=array()){
		if(empty($_FILES[$upload])){
			$this->_error = 2;//上传字段不存在
			return false;
		}
		$other['filename'] = $_FILES[$upload]['name'];
		return $this->add($_FILES[$upload]['tmp_name'],$domain,$autoid,$other);
	}
	//如果file的没有扩展名，在other中需要填写filename,用来获取该文件的扩展名
	//并且filename会被存为该文件的originalname
	public function storeFile($file,$domain,$autoid,$other=array(),$options=array()){
		return $this->add($file,$domain,$autoid,$other,$options);
	}
	//需要填写filename，用来获取该文件的扩展名，并且filename会被存为该文件的originalname
	public function storeBytes($bytes,$domain,$autoid,$other=array(),$options=array()){
		$other[$this->_domain] = (string)$domain;
		$other[$this->_autoid] = (string)$autoid;
		if(!empty($other['filename'])) $other['originalname'] = $other['filename'];
		$other['filename'] = $this->createFileName($other);
		if(empty($other['contentType'])) $other['contentType'] = $this->_parseContentType($file,2);
		if(!$other['contentType']){
			$this->_error = 3;//文件类型未定义
			return false;
		}
		return $this->_map($this->_gridfs->storeBytes($bytes,$other,$options));
	}
	public function add($file,$domain,$autoid,$other=array(),$options=array()){
		$other[$this->_domain] = (string)$domain;
		$other[$this->_autoid] = (string)$autoid;
		if(empty($other['filename'])) $other['filename'] = basename($file);
		$other['originalname'] = $other['filename'];
		$other['filename'] = $this->createFileName($other);
		if(empty($other['contentType'])) $other['contentType'] = $this->_parseContentType($file,1);
		if(!$other['contentType']){
			$this->_error = 3;
			return false;
		}
		return $this->_map($this->_gridfs->put($file,$other,$options));
	}
	public function edit($data,$domain='',$autoid='',$other=array(),$options=array()){
		if(empty($data)){
			$this->_error = 1;//无修改数据
			return false;
		}
		if(empty($domain) && empty($autoid)){
			if($this->id){
				return $this->_map($this->_gridfs->update(array("_id" => $this->id),$data,$options));
			}else{
				$this->_error = 4;//指定文件不明确
				return false;
			}
		}elseif(empty($domain) || empty($autoid)){
			$this->_error = 4;
			return false;
		}
		$other[$this->_domain] = (string)$domain;
		$other[$this->_autoid] = (string)$autoid;
		$options['multiple'] = true;
		return $this->_map($this->_gridfs->update($other,$data,$options));
	}
	public function delete($domain='',$autoid='',$other=array(),$options=array()){
		if(empty($domain) && empty($autoid)){
			if($this->id){
				return $this->_map($this->_gridfs->delete($this->id));
			}else{
				$this->_error = 4;
				return false;
			}
		}elseif(empty($domain) || empty($autoid)){
			$this->_error = 4;
			return false;
		}
		$other[$this->_domain] = (string)$domain;
		$other[$this->_autoid] = (string)$autoid;
		return $this->_map($this->_gridfs->remove($other,$options));
	}
	public function field($array){
		if(is_array($array)){
			$this->_field = array_merge($this->_field,$array);
		}else{
			if(!in_array($array,$this->_field)) $this->_field[] = $array;
		}
		return $this;
	}
	public function select($domain,$autoid,$other=array()){
		$other[$this->_domain] = (string)$domain;
		$other[$this->_autoid] = (string)$autoid;
		return $this->_map($this->_gridfs->find($other,$this->_field));
	}
	public function find($id=''){
		if(empty($id)){
			if(empty($this->id)){
				$this->_error = 4;
				return false;
			}
			$id = $this->id;
		}else{
			$id = new MongoId($id);
		}
		return $this->_map($this->_gridfs->get($id));
	}
	public function setFile($file){
		if(is_object($file) && !empty($file->file["_id"])){
			$this->id = $file->file["_id"];
			return true;
		}
		$this->_error = 5;//参数错误
		return false;
	}
	public function getError(){
		return $this->_error;
	}
	protected function createFileName($info){
		$ext = strtolower(trim(substr(strrchr($info['filename'], '.'), 1)));
		return FNbase::guid(true).'.'.$ext;
	}
	private function _parseContentType($file,$type){
		$finfo = new finfo(FILEINFO_MIME_TYPE);
		switch($type){
			case 2:
				$mime = $finfo->buffer($file);
				break;
			case 1:
			default:
				$mime = $finfo->file($file);
		}
		if(!$this->_mime || in_array($mime,$this->_mime)){
			return $mime;
		}else{
			return false;
		}
	}
	private function _clear(){
		$this->_field = array();
		$this->id = null;
	}
	//将ID信息映射保存在属性字段中，可以快捷操作
	private function _map($return=false){
		$this->_clear();
		if(is_object($return)) {
			//返回MongoId
			if(isset($return->{'$id'})){
				$this->id = $return;
			//返回MongoGridFSFile
			}elseif(!empty($return->file["_id"])){
				$this->id = $return->file["_id"];
			}
		}
		return $return;
	}
}

?>