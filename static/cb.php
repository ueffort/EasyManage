<?php
/* 压缩 */
$MINIFY = true;
 
//抓取文件
function get_contents($url){
    $ch =curl_init($url);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $str =curl_exec($ch);
    curl_close($ch);
    if ($str !==false) {
        return $str;
    }else {
        return '';
    }  
}
 
//得到扩展名
function get_extend($file_name) { 
	$extend =explode("." , $file_name);
	$va=count($extend)-1;
	return $extend[$va];
} 
 
/**
 * begin
 */
//cdn上存在的各种可能的文件类型
$header = array(
    'js' => 'Content-Type: application/x-javascript',
    'css' => 'Content-Type: text/css',
    'jpg' => 'Content-Type: image/jpg',
    'gif' => 'Content-Type: image/gif',    
    'png' => 'Content-Type: image/png',
    'jpeg' => 'Content-Type: image/jpeg'
);
 
// 线上未找到的文件
$unfound = array();

//文件类型
$type = '';
 
//原始请求文件完整路径数组
$files = array();

//过滤后的文件完整路径数组，即待抓取的文件列表
$a_files = array();

//文件的最后修改时间
$last_modified_time = 0;

// request headers
$request_headers = getallheaders(); 

// 输出结果使用的数组
$R_files = array();
 
//得到文件夹路径
$prefix = realpath(dirname(__FILE__)).'/';

// 处理请求中附带的文件列表，得到原始数据
$pos = strpos($_SERVER['REQUEST_URI'],"??");
if($pos!==false){
	$file_string = substr($_SERVER['REQUEST_URI'],$pos+2);
	$_tmp = explode(',',$file_string);
	foreach($_tmp as $v){
		$files[] = $prefix.$v;
	}
}
// 得到需要读取的文件列表
foreach ($files as $k){
	//将开头的/和?去掉,和上级目录
	$k = preg_replace(
		array('/^\//','/\?.+$/','/\.\.\//'),
		array('','',''),
	$k);
		
	if(!preg_match('/(\.js|\.css)$/',$k)){
		continue;
	}
	
	$a_files[] = $k;
}

// 得到拼接文件的Last-Modified时间
foreach ($a_files as $k){
	if(file_exists($k)){
		$filemtime = filemtime($k);
		if($filemtime && ($filemtime > $last_modified_time)){
			$last_modified_time = $filemtime;
		}
	}
}

// 检查请求头的if-modified-since，判断是否304
if (isset($request_headers['If-Modified-Since']) && (strtotime($request_headers['If-Modified-Since']) == $last_modified_time)) {
	// 如果客户端带有缓存
	header('Last-Modified: '.gmdate('D, d M Y H:i:s', $last_modified_time.' GMT'), true, 304);
	exit;
} 
if($MINIFY){
	require 'jsmin.php';
	require 'cssmin.php';
}
// 拼接文件，并应用通用规则
foreach ($a_files as $k) {

    if(empty($type)) {
		$type = get_extend($k);
    }
 
	$in_str = file_get_contents($k);
	if($in_str===false){
		$unfound[] = $k;
	}elseif($MINIFY){
		if($type == 'js'){
			$R_files[] = JSMin::minify($in_str);
		}else if($type == 'css'){
			$R_files[] = cssmin::minify($in_str);
		}
	}else{
		$R_files[] = $in_str;
	}
}
 
//添加过期头，过期时间1年
header("Expires: " . date("D, j M Y H:i:s", strtotime("now + 10 years")) ." GMT");
header("Cache-Control: max-age=315360000");
header('Last-Modified: '.gmdate('D, d M Y H:i:s', $last_modified_time).' GMT');
//输出文件类型
header($header[$type]);
//拼装文件
$result = join("\n",$R_files);
//输出文件
echo $result;
if(!empty($unfound)){
echo "/* non published files:\n";
echo join("\n",$unfound);
echo "\n*/";
}
?>
