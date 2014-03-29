<?php
//支持类似淘宝的js和css的压缩读取方式
//http://a.tbcdn.cn/p/fp/2012/fp/??layout.css,dpl/dpl.css,sitenav/sitenav.css,logo/logo.css,search/search.css,nav/nav.css,product-list/product-list.css,mainpromo/mainpromo.css,attraction/attraction.css,notice/notice.css,status/status.css,interlayer/interlayer.css,cat/cat.css,convenience/convenience.css,act/act.css,expressway/expressway.css,guang/guang.css,hotsale/hotsale.css,helper/helper.css,footer/footer.css,recom_new/recom_new.css,local/local.css,globalshop/globalshop.css,guide/guide.css?t=20130411.css
//从??开始获取需要加载的css或者js，一次只能加载一个类型
//最后再次添加?设定版本号或者时间日期再加类型
//web服务器通过判断最后的文件类型选择是否做缓存，整个请求加上参数作为缓存KEY
//默认读取的资源是10年缓存设置，客户端，也就是http头中设置了Expires，Cache-Control，Last-Modified
//压缩操作只是去除空格换行等多余符号，不能实现如.min.js等优化压缩，该类型只能是实现生成或者编写web服务器插件实现
//原因有2：1.生成时间长，对用户无法及时返回数据
//		   2.兼容性并不是100%，还需测试（部分情况）
class FN_tools_combine{
	static private $_Minify = true;//是否压缩
	static private $_Header = array(
		'js' => 'Content-Type: application/x-javascript',
		'css' => 'Content-Type: text/css',
		//'jpg' => 'Content-Type: image/jpg',
		//'gif' => 'Content-Type: image/gif',    
		//'png' => 'Content-Type: image/png',
		//'jpeg' => 'Content-Type: image/jpeg'
	);
	static public function combine(){
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
		$pos = strpos("??",$_SERVER['REQUEST_URI']);
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
				array('/^\//','/\?.+$/','\.\.\/'),
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
		if(self::$_Minify){
			$class = XG::T('optimize.combine.'.$type);
		}
		// 拼接文件，并应用通用规则
		foreach ($a_files as $k) {

			if(empty($type)) {
				$type = self::getExtend($k);
			}
		 
			$in_str = file_get_contents($k);
			if($in_str===false){
				$unfound[] = $k;
			}elseif(self::$_Minify){
				//$R_files[] = $class::minify($in_str);
				$R_files[] = call_user_func_array(array($class,'minify'),array($in_str));
			}else{
				$R_files[] = $in_str;
			}
		}
		 
		//添加过期头，过期时间1年
		header("Expires: " . date("D, j M Y H:i:s", strtotime("now + 10 years")) ." GMT");
		header("Cache-Control: max-age=315360000");
		header('Last-Modified: '.gmdate('D, d M Y H:i:s', $last_modified_time).' GMT');
		//输出文件类型
		header(self::$_Header[$type]);
		//拼装文件
		$result = join("\n",$R_files);
		//输出文件
		echo $result;
		echo "/* non published files:\n";
		echo join("\n",$unfound);
		echo "\n*/";
	}
	static public function getExtend($filename){
		$extend =explode("." , $filename);
		$va=count($extend)-1;
		return $extend[$va];
	}
}
?>
