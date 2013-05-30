<?php
class FN_server_image{
	static private $_AllowType = array('*','jpg', 'jpeg', 'gif', 'png');
	/*
	 * 判断是否是图片
	 * 根据文件扩展名判断
	 */
	static public function isImage($filename){
		$ext = strtolower(trim(substr(strrchr($filename, '.'), 1)));
		return array_search($ext,self::$_AllowType);
	}
	/*
	 * 创建缩略图，根据服务器上所支持的创建缩略图
	 * option:4、最佳缩放模式（自动判断缩放），8、宽度最佳缩放，16、高度最佳缩放
	 * cutmode:0、默认模式（不裁剪），1、左或上裁剪模式，2、中间裁剪模式，3、右或下裁剪模式
	 */
	static public function thumb($source_file,$target_file,$width,$height,$option=4,$cutmode=0){
		$opnotkeepscale = 4;
		$opbestresizew = 8;
		$opbestresizeh = 16;
		$startx = 0;
		$starty = 0;
		$dstW = intval($width);
		$dstH = intval($height);
		if (!file_exists($source_file)) return false;

		$data = getimagesize($source_file);
		if(empty($data) || !is_array($data)) return '';

		$imgtype = array(1=>'gif', 2=>'jpeg', 3=>'png');

		$func_create = "imagecreatefrom".$imgtype[$data[2]];
		if (!function_exists ($func_create)) return '';

		$func_output = 'image'.$imgtype[$data[2]];
		if (!function_exists ($func_output)) return '';

		$im = $func_create($source_file);
		$srcW = imagesx($im);
		$srcH = imagesy($im);
		$srcX = 0;
		$srcY = 0;
		$dstX = 0;
		$dstY = 0;

		//SIZE
		if($srcW < $dstW) $dstW = $srcW;
		if($srcH < $dstH) $dstH = $srcH;

		if ($option & $opbestresizew) {
			$dstH = round($dstW * $srcH / $srcW);
		}
		if ($option & $opbestresizeh) {
			$dstW = round($dstH * $srcW / $srcH);
		}

		$fdstW = $dstW;
		$fdstH = $dstH;

		if ($cutmode != 0) {
			$srcW -= $startx;
			$srcH -= $starty;
			if ($srcW*$dstH > $srcH*$dstW) {
				$testW = round($dstW * $srcH / $dstH);
				$testH = $srcH;
			} else {
				$testH = round($dstH * $srcW / $dstW);
				$testW = $srcW;
			}
			switch ($cutmode) {
			case 1: $srcX = 0; $srcY = 0;
			break;
			case 2: $srcX = round(($srcW - $testW) / 2);
			$srcY = round(($srcH - $testH) / 2);
			break;
			case 3: $srcX = $srcW - $testW;
			$srcY = $srcH - $testH;
			break;
			}
			$srcW = $testW;
			$srcH = $testH;
			$srcX += $startx;
			$srcY += $starty;
		} else {
			if (!($option & $opnotkeepscale)) {
				if ($srcW*$dstH > $srcH*$dstW) {
					$fdstH = round($srcH*$dstW/$srcW);
					$dstY = floor(($dstH-$fdstH)/2);
					$fdstW = $dstW;
				} else {
					$fdstW = round($srcW*$dstH/$srcH);
					$dstX = floor(($dstW-$fdstW)/2);
					$fdstH = $dstH;
				}
				$dstX=($dstX<0)?0:$dstX;
				$dstY=($dstX<0)?0:$dstY;
				$dstX=($dstX>($dstW/2))?floor($dstW/2):$dstX;
				$dstY=($dstY>($dstH/2))?floor($dstH/2):$dstY;
			}
		}
		//echo $dstW.'<br/>'.$dstH.'<br />'.$srcX.'<br/>'.$srcY.'<br/>'.$fdstW.'<br/>'.$fdstH.'<br />'.$srcW.'<br/>'.$srcH;
		if(function_exists("imagecopyresampled") and function_exists("imagecreatetruecolor")) {
			$func_create = "imagecreatetruecolor";
			$func_resize = "imagecopyresampled";
		} elseif (function_exists("imagecreate") and function_exists("imagecopyresized")) {
			$func_create = "imagecreate";
			$func_resize = "imagecopyresized";
		} else {
			return '';
		}
		$newim = $func_create($dstW,$dstH);
		$black = imagecolorallocate($newim, 0,0,0);
		$back = imagecolortransparent($newim, $black);
		imagefilledrectangle($newim,0,0,$dstW,$dstH,$black);
		$func_resize($newim,$im,$dstX,$dstY,$srcX,$srcY,$fdstW,$fdstH,$srcW,$srcH);
		$func_output($newim, $target_file);
		imagedestroy($im);
		imagedestroy($newim);
		if(!file_exists($target_file)) return '';
		return true;
	}
	/*
	 * 添加水印
	 */
	static public function water($source_file,$target_file,$water,$pos=0,$pct=30,$quality=80){
		$ispng = false;
		// 加载水印图片
		$info = self::getImageInfo($water);
		if(!empty($info[0])){
			$water_w = $info[0];
			$water_h = $info[1];
			$type = $info['type'];
			$fun  = 'imagecreatefrom'.$type;
			$waterimg = $fun($water);
			if($type=='png') $ispng = true;
		} else{
			return false;
		}
		// 加载背景图片
		$info = self::getImageInfo($source_file);

		if(!empty($info[0])){
			$old_w = $info[0];
			$old_h = $info[1];
			$type  = $info['type'];
			$fun   = 'imagecreatefrom'.$type;
			$source_file = $fun($source_file);
		} else{
			return false;
		}
		// 剪切水印
		$water_w >$old_w && $water_w = $old_w;
		$water_h >$old_h && $water_h = $old_h;

		// 水印位置
		switch($pos){
			case 0://随机
				$posX = rand(0,($old_w - $water_w));
				$posY = rand(0,($old_h - $water_h));
				break;
			case 1://1为顶端居左
				$posX = 0;
				$posY = 0;
				break;
			case 2://2为顶端居中
				$posX = ($old_w - $water_w) / 2;
				$posY = 0;
				break;
			case 3://3为顶端居右
				$posX = $old_w - $water_w;
				$posY = 0;
				break;
			case 4://4为中部居左
				$posX = 0;
				$posY = ($old_h - $water_h) / 2;
				break;
			case 5://5为中部居中
				$posX = ($old_w - $water_w) / 2;
				$posY = ($old_h - $water_h) / 2;
				break;
			case 6://6为中部居右
				$posX = $old_w - $water_w;
				$posY = ($old_h - $water_h) / 2;
				break;
			case 7://7为底端居左
				$posX = 0;
				$posY = $old_h - $water_h;
				break;
			case 8://8为底端居中
				$posX = ($old_w - $water_w) / 2;
				$posY = $old_h - $water_h;
				break;
			case 9://9为底端居右
				$posX = $old_w - $water_w;
				$posY = $old_h - $water_h;
				break;
			default: //随机
				$posX = rand(0,($old_w - $water_w));
				$posY = rand(0,($old_h - $water_h));
				break;
		}
		if($ispng) {
			$watermark_photo = imagecreatetruecolor($old_w, $old_h);
			imageCopy($watermark_photo, $source_file, 0, 0, 0, 0, $old_w, $old_h);
			imageCopy($watermark_photo, $waterimg, $posX, $posY, 0, 0, $water_w, $water_h);
			$source_file = $watermark_photo;
		} else {
			// 设定图像的混色模式
			imagealphablending($source_file, true);
			// 添加水印
			imagecopymerge($source_file, $waterimg, $posX, $posY, 0, 0, $water_w,$water_h,$pct);
		}
		$fun = 'image'.$type;
		if($fun == 'imagejpeg'){
			imagejpeg($source_file,$target_file,$quality);
		}else{
			$fun($source_file, $target_file);
		}
		imagedestroy($source_file);
		imagedestroy($waterimg);
		if(!file_exists($target_file)) return '';
		return $target_file;
	}

	// imageInfo
	static public function getImageInfo($img){
		$info = @getimagesize($img);
		$extArr=array(1=>'gif','2' =>'jpg','3'=>'png');
		$extStr=$extArr[$info[2]];
		if($extStr=='jpg') $extStr='jpeg';
		$info['ext']=$info['type']=$extStr;
		$info['size'] = @filesize($img);
		return $info;
	}
}
?>
