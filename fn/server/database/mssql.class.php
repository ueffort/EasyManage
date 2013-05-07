<?php

class FN_server_database_mssql {

	var $version = '';
	var $querynum = 0;
	var $link;
	var $charset = '';
	
	//服务接口
	public static function initServer($config){
		$class = new self();
		$class->connect($config['host'].(empty($config['port']) ? '':','.$config['port']),$config['user'],$config['pass']);
		if(!empty($config['dbname'])) $class->select_db($config['dbname']);
		return $class;
	}
	function connect($dbhost, $dbuser, $dbpw, $dbname = '', $pconnect = 0, $halt = TRUE) {
		$func = empty($pconnect) ? 'mssql_connect' : 'mssql_pconnect';
		if(!$this->link = $func($dbhost, $dbuser, $dbpw)) {
			$halt && $this->halt($this->error().'Can not connect to MsSQL server');
			exit;
		} else {
			$this->charset = FN::getConfig('global/charset');
			$dbname && $this->select_db($dbname);
		}
	}

	function select_db($dbname) {
		return mssql_select_db($dbname, $this->link);
	}

	function fetch_array($query, $result_type = MSSQL_ASSOC) {
		return $this->iconv(mssql_fetch_array($query, $result_type));
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
	
	function query($sql) {
		$sql = $this->iconv($sql,false);
		$query = mssql_query($sql) or die($this->halt('MsSQL Query Error',$sql));
		$this->querynum++;
		return $query;
	}

	function affected_rows() {
		return mssql_rows_affected($this->link);
	}

	function error() {
		return mssql_get_last_message($this->link);
	}


	function result($query, $row) {
		$query = @mssql_result($query, $row);
		return $query;
	}

	function num_rows($query) {
		$query = mssql_num_rows($query);
		return $query;
	}

	function num_fields($query) {
		return $this->iconv(mssql_num_fields($query));
	}

	function free_result($query) {
		return mssql_free_result($query);
	}

	function insert_id() {
		$query = $this->query("SELECT SCOPE_IDENTITY() AS last_insert_id");
		$row = $this->fetch_array($query);
		$this->free_result($query);
		return $row['last_insert_id'];
	}

	function fetch_row($query) {
		return $this->iconv(mssql_fetch_row($query));
	}

	function fetch_fields($query) {
		return mssql_fetch_field($query);
	}

	function version() {
		return false;
	}

	function close() {
		return @mssql_close($this->link);
	}
	function halt($message = '', $sql = '') {
		//@file_put_contents(FRAME_DATA.'sql.log','[MSSQL]'.date('Y-m-d H:m:i').':'.$sql."\r\n",FILE_APPEND);
		if(!FN::getConfig('debug/sql')) $sql = '';
		echo $message.'<br />'.$sql;
	}
	function getFirst($sql) {
		return $this->fetch_array($this->query($sql));
	}
	function getOne($sql){
		$result = $this->query($sql);
		$row = $this->fetch_row($result);
		return empty($row[0]) ? '' : $row[0];
	}
	function __destruct(){
		$this->close();
	}
	function iconv($row,$flag=true){
		if(empty($row)) return $row;
		if(is_array($row)){
			$new_row = array();
			foreach($row as $key=>$value){
				$key = $this->iconv($key);
				$new_row[$key] = $this->iconv($value);
			}
			return $new_row;
		}else{
			if($flag){
				return iconv('GBK',$this->charset,$row);
			}else{
				return iconv($this->charset,'GBK',$row);
			}
		}
	}
}

?>