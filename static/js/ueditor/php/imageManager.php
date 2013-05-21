<?php
/**
 * Created by JetBrains PhpStorm.
 * User: taoqili
 * Date: 12-1-16
 * Time: 上午11:44
 * To change this template use File | Settings | File Templates.
 */
header("Content-Type: text/html; charset=utf-8");
error_reporting( E_ERROR | E_WARNING );
$path = 'upload/';                            //最好使用缩略图地址，否则当网速慢时可能会造成严重的延时
$action = htmlspecialchars( $_POST[ "action" ] );
$id = $_POST[ "id" ];
$domain = $_POST[ "domain" ];
if($action=='get' && !empty($id) && !empty($domain)){
	require_once('fn/fn.php');
	$fileserver = FN::i('file');
	$array = $fileserver->select($domain,$id);
	foreach($array as $file){
		echo $fileserver->getUrl().$file->file['filename']. "ue_separate_ue";
	}
}
	/*
	if ( $action == "get" ) {
		$files = getfiles( $path );
		if ( !$files ) return;
		rsort($files,SORT_STRING);
		$str = "";
		foreach ( $files as $file ) {
			$str .= $file . "ue_separate_ue";
		}
		echo $str;
	}

	 */

/**
 * 遍历获取目录下的指定类型的文件
 * @param $path
 * @param array $files
 * @return array
 */
	/*
	function getfiles( $path , &$files = array() )
	{
		if ( !is_dir( $path ) ) return null;
		$handle = opendir( $path );
		while ( false !== ( $file = readdir( $handle ) ) ) {
			if ( $file != '.' && $file != '..' ) {
				$path2 = $path . '/' . $file;
				if ( is_dir( $path2 ) ) {
					getfiles( $path2 , $files );
				} else {
					if ( preg_match( "/\.(gif|jpeg|jpg|png|bmp)$/i" , $file ) ) {
						$files[] = $path2;
					}
				}
			}
		}
		return $files;
	}
	 */
