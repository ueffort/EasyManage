<?php

class FN_server_database_mysql{

	var $version = '';
	var $querynum = 0;
	var $link;

	//服务接口
	public static function initServer($config){
		$class = new self();
		$class->connect($config['host'].(empty($config['port']) ? '':':'.$config['port']),$config['user'],$config['pass']);
		if(!empty($config['dbname'])) $class->select_db($config['dbname']);
		return $class;
	}
	function connect($dbhost, $dbuser, $dbpw, $dbname = '', $pconnect = 0, $halt = TRUE) {
		$func = empty($pconnect) ? 'mysql_connect' : 'mysql_pconnect';
		if(!$this->link = @$func($dbhost, $dbuser, $dbpw)) {
			$halt && $this->halt('Can not connect to MySQL server');
			exit;
		} else {
			if($this->version() > '4.1') {
				$charset = FN::getConfig('global/charset');
				$dbcharset = in_array(strtolower($charset), array('gbk', 'big5', 'utf-8')) ? str_replace('-', '', $charset) : '';
				$serverset = $dbcharset ? 'character_set_connection='.$dbcharset.', character_set_results='.$dbcharset.', character_set_client=binary' : '';
				$serverset .= $this->version() > '5.0.1' ? ((empty($serverset) ? '' : ',').'sql_mode=\'\'') : '';
				$serverset && mysql_query("SET $serverset", $this->link);
			}
			$dbname && $this->select_db($dbname);
		}

	}

	function select_db($dbname) {
		return mysql_select_db($dbname, $this->link);
	}

	function fetch_array($query, $result_type = MYSQL_ASSOC) {
		return mysql_fetch_array($query, $result_type);
	}

	function fetch_first($sql) {
		return $this->fetch_array($this->query($sql));
	}

	function result_first($sql) {
		return $this->result($this->query($sql), 0);
	}
	
	function fetch_all($sql) {
		$arr = array();
		$query = $this->query($sql);
		while($data = $this->fetch_array($query)) {
			$arr[] = $data;
		}
		return $arr;
	}
	
	function query($sql, $type = '') {
		$func = $type == 'UNBUFFERED' && @function_exists('mysql_unbuffered_query') ?
			'mysql_unbuffered_query' : 'mysql_query';
		if(!($query = $func($sql, $this->link))) {
			if(in_array($this->errno(), array(2006, 2013)) && substr($type, 0, 5) != 'RETRY') {
				$this->close();
			} elseif($type != 'SILENT' && substr($type, 5) != 'SILENT') {
				$this->halt('MySQL Query Error',$sql);
			}
		}
		$this->querynum++;
		return $query;
	}

	function affected_rows() {
		return mysql_affected_rows($this->link);
	}

	function error() {
		return (($this->link) ? mysql_error($this->link) : mysql_error());
	}

	function errno() {
		return intval(($this->link) ? mysql_errno($this->link) : mysql_errno());
	}

	function result($query, $row) {
		$query = @mysql_result($query, $row);
		return $query;
	}

	function num_rows($query) {
		$query = mysql_num_rows($query);
		return $query;
	}

	function num_fields($query) {
		return mysql_num_fields($query);
	}

	function free_result($query) {
		return mysql_free_result($query);
	}

	function insert_id() {
		return ($id = mysql_insert_id($this->link)) >= 0 ? $id : $this->result($this->query("SELECT last_insert_id()"), 0);
	}

	function fetch_row($query) {
		return mysql_fetch_row($query);
	}

	function fetch_fields($query) {
		return mysql_fetch_field($query);
	}

	function version() {
		if(empty($this->version)) {
			$this->version = mysql_get_server_info($this->link);
		}
		return $this->version;
	}

	function close() {
		return @mysql_close($this->link);
	}
	function halt($message = '', $sql = '') {
		//@file_put_contents(FRAME_DATA.'sql.log','[MYSQL]['.$this->errno().']'.date('Y-m-d H:m:i').':'.$this->error().' '.$sql."\r\n",FILE_APPEND);
		if(!FN::getConfig('debug/sql')) $sql = '';
		echo $message.'<br />'.$sql;
	}
	function getFirst($sql) {
		return $this->fetch_array($this->query($sql));
	}
	function getOne($sql){
		$result = $this->query($sql);
		$row = $this->fetch_row($result);
		return isset($row[0]) ?  $row[0] : '';
	}
	function getAll($sql){
		return $this->fetch_all($sql);
	}
	function __destruct(){
		$this->close();
	}
}

?>