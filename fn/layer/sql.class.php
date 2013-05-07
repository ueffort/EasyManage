<?php
//数据库操作类
//通过设置表字段，主键及主键是否自增长实现sql的自动编写等功能
class FN_layer_sql implements FN__single{
	//支持对象级别操作，所以所有内置变量都添加_前缀用于避免冲突
	private $_sql=null;
	private $_join=null;
	private $_where=null;
	private $_order=null;
	private $_group=null;
	private $_having=null;
	private $_limit=null;
	private $_count=null;
	private $_field_string=null;
	private $_exec=null;
	private $_row=null;
	private $_db=null;
	private $_error=null;
	//驱动跟随映射走的
	//protected $_drive=null;//数据库驱动，mysql，mssql，默认是mysql
	protected $_link='default';//数据库映射名。默认是default
	protected $_dbname=null;//数据库名
	protected $_table=null;//数据表名
	protected $_pkey = false;//主键字段
	protected $_field = array();//字段数组
	protected $_aint = true;//主键是否自增长
	protected $_alias = null;//别名，用来自动连表等操作
	static public function getInstance($array=array()){
		$class = get_called_class();
		return new $class();
	}
	private function __construct(){
		$config = FN::serverConfig('database',$this->_link);
		if(empty($config) || !in_array($config['drive'],array('mysql','mssql'))){
			$this->_error = 5;//配置错误
			return false;
		}
		$this->_db = FN::server('database',$this->_link);
		if(!empty($config['prefix'])) $this->table = $config['prefix'].$this->table;//实现表前缀添加
		if(!in_array($this->_pkey,$this->_field)) $this->_pkey = null;
		if(empty($this->_pkey)) $this->_aint = false;
	}
	public function add($array=array(),$replace=false){
		if(empty($array) && empty($this->_exec)){
			$this->_error = 1;//操作数据为空
			return false;
		}
		if(!empty($array)){
			foreach($array as $field=>$value){
				$this->$field = $value;
			}
		}
		$string1 = $string2 = '';
		foreach($this->_field as $field){
			if($field == $this->_pkey && $this->_aint){
				if(empty($array[$field])) continue;
			}
			if(!isset($array[$field])) $array[$field] = '';//默认字段为空
			$string1 .= '`'.$field.'`,';
			$string2 .= '"'.$array[$field].'",';
		}
		$sql = ($replace ? 'replace' : 'insert').' into '.$this->getTable().'('.substr($string1,0,-1).')values('.substr($string2,0,-1).')';
		$this->execute($sql);
		if($this->_aint && $this->_pkey){
			$_pkey = $this->_pkey;
			$this->$_pkey = $this->_db->insert_id();
		}
		return $this->_map();
	}
	public function addMore($array){
		$field_array = array();
		$string1 = $string2 = '';
		foreach($this->_field as $field){
			if($field == $this->_pkey && $this->_aint){
				continue;
			}
			$field_array[] = $field;
			$string1 .= '`'.$field.'`,';
		}
		foreach($array as $key=>$value){
			foreach($field_array as $field){
				$string2 .= '"'.(empty($value[$field]) ? '' : $value[$field]).'",';
			}
			$string_array[] = substr($string2,-1);
		}
		$sql = 'insert into '.$this->getTable().'('.substr($string1,0,-1).')values('.implode('),(',$string_array).')';
		$this->execute($sql);
		return true;
	}
	public function edit($array=array()){
		if(empty($array) && empty($this->_exec)){
			$this->_error = 1;
			return false;
		}
		if(!empty($array)){
			foreach($array as $field=>$value){
				$this->$field = $value;
			}
		}
		$string = '';
		foreach($this->_exec as $key=>$value){
			$string .= '`'.$key.'`'.self::judgeSQL($value,true).',';
		}
		if($this->_pkey){
			$_pkey = $this->_pkey;
			if($this->$_pkey >0) $this->where(' `'.$this->_pkey.'` = "'.$this->$_pkey.'"');
		}
		if(empty($this->_where)){
			$this->_error = 3;//无查询条件
			return false;
		}
		$sql = 'update '.$this->getTable().' set '.substr($string,0,-1)
			.$this->_buildSQL('where',$this->_where);
		$this->execute($sql);
		return $this->_map();
	}
	public function delete($string=''){
		if($this->_pkey && $string){
			if(is_array($string)){
				$string = implode('","',$string);
			}
			$this->where(' `'.$this->_pkey.'` in ("'.$string.'")');
		}elseif($this->_pkey){
			$_pkey = $this->_pkey;
			if($this->$_pkey > 0) $this->where(' `'.$this->_pkey.'` = "'.$this->$_pkey.'"');
		}
		if(empty($this->_where)){
			$this->_error = 3;
			return false;
		}
		$sql = 'delete from '.$this->getTable()
			.$this->_buildSQL('where',$this->_where);
		$this->_map(false);
		return $this->execute($sql);
	}
	public function selected($type=''){
		switch($type){
			case 'first': $type = 2;break;
			case 'one': $type = 3;break;
			case 'array': $type = 4;break;
		}
		return $this->query($this->buildSQL(),$type);
	}
	public function select($array = array()){
		if(!empty($array['limit'])){
			$this->limit($array['limit']);
		}
		if(!empty($array['page'])){
			$this->page($array['page']);
		}
		if(!empty($array['order'])){
			$this->order($array['order']);
		}
		if(!empty($array['where'])){
			$this->where($array['where']);
		}
		if(!empty($array['group'])){
			$this->group($array['group']);
		}
		if(!empty($array['having'])){
			$this->group($array['having']);
		}
		if(!empty($array['field'])){
			$this->field($array['field']);
		}
		return $this->query($this->buildSQL());
	}
	public function find($string=''){
		if(!$this->_pkey){
			$this->_error = 2;//无主键
			return false;
		}
		if($string){
			if(is_array($string)){
				$string = implode('","',$string);
			}
			$this->where(' `'.$this->_pkey.'` in ("'.$string.'")');
		}else{
			$_pkey = $this->_pkey;
			if($this->$_pkey > 0) $this->where(' `'.$this->pkey.'` = "'.$this->$_pkey.'"');
		}
		if(empty($this->_where)){
			$this->_error = 3;
			return false;
		}
		$row = $this->query($this->buildSQL(),2);
		$this->_map($row);
		return $row;
	}
	public function join($table,$relation,$fields,$dire='left'){
		$this->_join[] = array($table,$relation,$dire);
		$this->field($fields,$table);
		return $this;
	}
	public function where($string,$table=false){
		$this->_where[] = array($string,$table);
		return $this;
	}
	public function page($page){
		if(empty($this->_limit[1])){
			$this->_error = 4;//没有设置limit
			return false;
		}
		$this->_limit[0] = ($page-1)*$this->_limit[1];
		return $this;
	}
	public function limit($l1,$l2=''){
		if(empty($l2)){
			if(is_array($l1)){
				$this->_limit = array($l1[0],$l1[1]);
			}else{
				$this->_limit = array(0,$l1);
			}
		}else{
			$this->_limit = array($l1,$l2);
		}
		return $this;
	}
	public function order($string,$table=false){
		$this->_order[] = array($string,$table);
		return $this;
	}
	public function group($string,$table=false){
		$this->_group[] = array($string,$table);
		return $this;
	}
	public function having($string,$table=false){
		$this->_having[] = array($string,$table);
		return $this;
	}
	public function field($string,$table=false){
		$this->_field_string[] = array($string,$table);
		return $this;
	}
	public function query($sql,$type=0,$check=1){
		if($check){
			$this->_sql = $sql;
			$this->_clear();
		}
		switch($type){
			case 1://返回资源符
				return $this->_db->query($sql);
			case 2://返回第一行
				return $this->_db->getFirst($sql);
			case 3://返回第一行第一列
				return $this->_db->getOne($sql);
			case 4://返回所有行第一列的数组
				$array = array();
				$result = $this->_db->query($sql);
				while($row = $this->_db->fetch_row($result)){
					$array[] = $row[0];
				}
				return $array;
			default://返回全部
				if($this->_pkey){
					$array = array();
					$result = $this->_db->query($sql);
					$op = 0;
					while($row = $this->_db->fetch_array($result)){
						if(empty($op)){
							if(empty($row[$this->_pkey])){
								$op = -1;
							}else{
								$op = 1;
							}
						}
						if($op > 0){
							$array[$row[$this->_pkey]] = $row;
						}else{
							$array[] = $row;
						}
					}
					return $array;
				}else{
					return $this->_db->getAll($sql);
				}
		}
	}
	public function execute($sql){
		$this->_sql = $sql;
		$this->_clear();
		return $this->_db->query($sql);
	}
	public function getSQL(){
		return $this->_sql;
	}
	public function getTable(){
		return (empty($this->_dbname) ? '' : '`'.$this->_dbname.'`').'`'.$this->_table.'`';
	}
	public function getAlias(){
		return $this->_alias;
	}
	public function getAliasTable(){
		return $this->_alias ? $this->_alias : $this->getTable();
	}
	public function getField(){
		return $this->_field;
	}
	public function clear(){
		$this->_clear();
		return $this;
	}
	private function _buildSQL($type,$array){
		$alias = '';
		switch($type){
			case 'where':
				if(empty($array)) return ' ';
				foreach($array as $key=>$value){
					$string = $value[0];
					if(is_array($string)){
						$str = array();
						foreach($string as $k=>$v){
							$str[] = '`'.$k.'`'.self::judgeSQL($v);
						}
						$string = implode(' and ',$str);
					}
					if(empty($string)) {
						unset($array[$key]);
						continue;
					}
					$array[$key] = $string;
				}
				if(empty($array)) return ' ';
				return ' where ('.implode(') and (',$array).') ';
			case 'order':
				if(empty($array)) return ' ';
				foreach($array as $key=>$value){
					if(empty($value[0])) {
						unset($array[$key]);
						continue;
					}
					$array[$key] = $value[0];
				}
				if(empty($array)) return ' ';
				return ' order by '.implode(' , ',$array);
			case 'limit':
				if(empty($array)) return ' ';
				return ' limit '.implode(',',$array);
			case 'group':
				if(empty($array)) return ' ';
				foreach($array as $key=>$value){
					if(empty($value[0])) {
						unset($array[$key]);
						continue;
					}
					$array[$key] = $value[0];
				}
				if(empty($array)) return ' ';
				return ' group by '.implode(',',$array);
			case 'having':
				if(empty($array)) return ' ';
				foreach($array as $key=>$value){
					$string = $value[0];
					if(is_array($string)){
						$str = array();
						foreach($string as $k=>$v){
							$str[] = '`'.$k.'`'.self::judgeSQL($v);
						}
						$string = implode(' and ',$str);
					}
					if(empty($string)) {
						unset($array[$key]);
						continue;
					}
					$array[$key] = $string;
				}
				if(empty($array)) return ' ';
				return ' having '.implode(',',$array);
			case 'field':
				if(empty($array)) return ' * ';
				foreach($array as $key=>$value){
					$string = $value[0];
					if(is_array($string)){
						$str = '';
						foreach($string as $value){
							$str .= ',`'.$value.'`';
						}
						$string = substr($str,1);
					}
					if(empty($string)) {
						unset($array[$key]);
						continue;
					}
					$array[$key] = $string;
				}
				if(empty($array)) return ' ';
				return ' '.implode(' , ',$array);
		}
	}
	private function _buildJoinSQL($type,$array){
		switch($type){
			case 'join':
				if(empty($array)) return '';
				foreach($array as $key=>$a){
					$table_name = $a[0]->getTable();
					$alias_2 = $a[0]->getAlias();
					$string = ' '.$a[2].' join '.$table_name.(!empty($alias_2) ? ' as '.$alias_2: '').' on ';
					$alias = !empty($this->_alias) ? $this->_alias:$this->getTable();
					$alias_2 = $a[0]->getAliasTable();
					foreach($a[1] as $f=>$ff){
						$string .= $alias.'.`'.$f.'` = '.$alias_2.'.`'.$ff.'` and ';
					}
					$string = substr($string,0,-4);
					$array[$key] = $string;
				}
				if(empty($array)) return ' ';
				return ' '.implode(' ',$array);
			case 'where':
				if(empty($array)) return ' ';
				foreach($array as $key=>$value){
					$string = $value[0];
					if(!$value[1]) $value[1] = $this;
					$alias = $value[1]->getAliasTable().'.';
					$fields = $value[1]->getField();
					if(is_array($string)){
						$str = array();
						foreach($string as $k=>$v){
							$str[] = $alias.'`'.$k.'`'.self::judgeSQL($v);
						}
						$string = implode(' and ',$str);
					}
					if(empty($string)) {
						unset($array[$key]);
						continue;
					}
					$array[$key] = $string;
				}
				if(empty($array)) return ' ';
				return ' where ('.implode(') and (',$array).') ';
			case 'order':
				if(empty($array)) return ' ';
				foreach($array as $key=>$value){
					$string = $value[0];
					if(!$value[1]) $value[1] = $this;
					$alias = $value[1]->getAliasTable().'.';
					$fields = $value[1]->getField();
					$string = trim($string);
					$replate_array = array();
					foreach($fields as $field){
						$replace_array[] = $alias.'`'.$field.'`';
					}
					$string = str_replace($fields,$replace_array,$string);
					if(empty($string)) {
						unset($array[$key]);
						continue;
					}
					$array[$key] = $string;
				}
				if(empty($array)) return ' ';
				return ' order by '.implode(' , ',$array);
			case 'limit':
				if(empty($array)) return ' ';
				return ' limit '.implode(',',$array);
			case 'group':
				if(empty($array)) return ' ';
				foreach($array as $key=>$value){
					$string = $value[0];
					if(!$value[1]) $value[1] = $this;
					$alias = $value[1]->getAliasTable().'.';
					$fields = $value[1]->getField();
					$string = trim($string);
					$replate_array = array();
					foreach($fields as $field){
						$replace_array[] = $alias.'`'.$field.'`';
					}
					$string = str_replace($fields,$replace_array,$string);
					if(empty($string)) {
						unset($array[$key]);
						continue;
					}
					$array[$key] = $string;
				}
				if(empty($array)) return ' ';
				return ' group by '.implode(',',$array);
			case 'having':
				if(empty($array)) return ' ';
				foreach($array as $key=>$value){
					$string = $value[0];
					if(!$value[1]) $value[1] = $this;
					$alias = $value[1]->getAliasTable().'.';
					if(is_array($string)){
						$str = array();
						foreach($string as $k=>$v){
							$str[] = $alias.'`'.$k.'`'.self::judgeSQL($v);
						}
						$string = implode(' and ',$str);
					}
					if(empty($string)) {
						unset($array[$key]);
						continue;
					}
					$array[$key] = $string;
				}
				if(empty($array)) return ' ';
				return ' having '.implode(',',$array);
			case 'field':
				if(empty($array)) return ' * ';
				foreach($array as $key=>$value){
					$string = $value[0];
					if(!$value[1]) $value[1] = $this;
					$alias = $value[1]->getAliasTable().'.';
					if(is_array($string)){
						$str = '';
						foreach($string as $value){
							$str .= ','.$alias.'`'.$value.'`';
						}
						$string = substr($str,1);
					}elseif($alias){
						$fields = $value[1]->getField();
						$string = trim($string);
						if($string == '*'){
							$string = $alias.$string;
						}else{
							$replate_array = array();
							foreach($fields as $field){
								$replace_array[] = $alias.'`'.$field.'`';
							}
							$string = str_replace($fields,$replace_array,$string);
						}
					}
					if(empty($string)) {
						unset($array[$key]);
						continue;
					}
					$array[$key] = $string;
				}
				if(empty($array)) return ' ';
				return ' '.implode(' , ',$array);
		}
	}
	public function buildSQL(){
		if(!empty($this->_join)){
			$this->_sql = 'select '.$this->_buildJoinSQL('field',$this->_field_string).' from '.$this->getTable().(!empty($this->_join) ? ' '.$this->getAlias():'').' '
				.$this->_buildJoinSQL('join',$this->_join).$this->_buildJoinSQL('where',$this->_where)
				.$this->_buildJoinSQL('group',$this->_group).$this->_buildJoinSQL('having',$this->_having)
				.$this->_buildJoinSQL('order',$this->_order).$this->_buildJoinSQL('limit',$this->_limit);
		}else{
			$this->_sql = 'select '.$this->_buildSQL('field',$this->_field_string).' from '.$this->getTable().' '.$this->_buildSQL('where',$this->_where)
				.$this->_buildSQL('group',$this->_group).$this->_buildSQL('having',$this->_having)
				.$this->_buildSQL('order',$this->_order).$this->_buildSQL('limit',$this->_limit);
		}
		$this->_clear();
		return $this->_sql;
	}
	private function _clear(){
		$this->_join = $this->_where = $this->_group = $this->_having = $this->_order = $this->_limit = $this->_count = $this->_field_string = $this->_exec = null;
	}
	private function _map($row=true){
		if($row===false) $row = array();
		if(is_array($row)) $this->_row = $row;
		return $this->_row;
	}
	public function count($new_field=''){
		$field = $this->_field_string;
		$this->_field_string = array(array('count('.($new_field?'`'.$new_field.'`':'*').')',$this));
		$num = $this->query($this->buildSql(),3,0);
		$this->_field_string = $field;
		return $num ? $num : 0;
	}
	public function __get($property){
		if(in_array($property ,$this->_field)){
			if(!$this->_row || !isset($this->_row[$property])) return '';
			return $this->_row[$property];
		}
		$this->_error = 5;//该字段不存在
		return false;
	}
	public function __set($property,$value){
		if(in_array($property,$this->_field)){
			$this->_row[$property] = $value;
			if(!($this->_pkey && $property == $this->_pkey)) $this->_exec[$property] = $value;
			return true;
		}
	}
	public function getError(){
		return $thsi->_error();
	}
	/*继承该ArrayAccess接口的函数
	//是否存在某个下标、索引或者键值都是一个东西
	public function offsetExists($offset){
		return $this->$offset ? true : false;
	}
	//通过索引获取数据对象
	public function offsetGet($offset){
		return $this->$offset;
	}
	//通过索引设置对象
	public function offsetSet($offset,$value){
		return $this->$offset = $value;
	}
	//释放掉某个索引的对象
	public function offsetUnset($offset){
		return $this->$offset = '';
	}
	*/
	static public function judgeSQL($value,$set=false){
		if(is_array($value) && !$set) return ' in ("'.implode('","',$value).'")';
		$Symbol = substr($value,0,1);
		//匹配基本符号
		switch($Symbol){
			case '!':
				if(!$set){
					return '<>"'.substr($value,1).'"';
				}else{
					break;
				}
			case '>':
			case '<':
				if(!$set){
					return $value;
				}else{
					break;
				}
			case '=':return $value;
		}
		//匹配特殊的where查询关键字
		if(!$set && preg_match('/^(like)\s[\'"].*?[\'"]$/',$value)) return ' '.$value;
		//匹配数字
		if(preg_match('/^\d+(\.\d+)?$/',$value)) return '='.$value;
		return '="'.$value.'"';
	}
}
?>
