<?php /* Smarty version Smarty-3.1.12, created on 2014-03-29 08:34:09
         compiled from "H:\Item\easymanage-bae\templates\main.html" */ ?>
<?php /*%%SmartyHeaderCode:396953367771719c85-34989379%%*/if(!defined('SMARTY_DIR')) exit('no direct access allowed');
$_valid = $_smarty_tpl->decodeProperties(array (
  'file_dependency' => 
  array (
    'd2f804c33ced3dafae4907792230bfd5789a83b8' => 
    array (
      0 => 'H:\\Item\\easymanage-bae\\templates\\main.html',
      1 => 1387701565,
      2 => 'file',
    ),
  ),
  'nocache_hash' => '396953367771719c85-34989379',
  'function' => 
  array (
  ),
  'variables' => 
  array (
    'static' => 0,
    'navlist' => 0,
    'username' => 0,
    'nav' => 0,
    'key' => 0,
  ),
  'has_nocache_code' => false,
  'version' => 'Smarty-3.1.12',
  'unifunc' => 'content_53367771b38888_04814507',
),false); /*/%%SmartyHeaderCode%%*/?>
<?php if ($_valid && !is_callable('content_53367771b38888_04814507')) {function content_53367771b38888_04814507($_smarty_tpl) {?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <title>EasyManage 管理系统</title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <script src="<?php echo $_smarty_tpl->tpl_vars['static']->value;?>
js/jquery-1.8.3.min.js" type="text/javascript"></script>
    <link href="<?php echo $_smarty_tpl->tpl_vars['static']->value;?>
js/ligerUI/skins/Aqua/css/ligerui-all.css" rel="stylesheet" type="text/css" />
	<link href="<?php echo $_smarty_tpl->tpl_vars['static']->value;?>
js/ligerUI/skins/Gray/css/all.css" rel="stylesheet" type="text/css" />
	<link href="<?php echo $_smarty_tpl->tpl_vars['static']->value;?>
js/ligerUI/skins/ligerui-icons.css" rel="stylesheet" type="text/css" />
	<script src="<?php echo $_smarty_tpl->tpl_vars['static']->value;?>
js/ligerUI/js/ligerui.all.js" type="text/javascript"></script>
	<script src="<?php echo $_smarty_tpl->tpl_vars['static']->value;?>
js/EM.js" type="text/javascript"></script>
<script type="text/javascript">
var navList = <?php echo json_encode($_smarty_tpl->tpl_vars['navlist']->value);?>
;

$(function (){
	var panel = null;//面板视图标签
	var accordion = null;//导航
	$("#layout").ligerLayout({ leftWidth: 190, height: '100%',space:4, 
		onHeightChanged: function(options){
			if (panel) panel.addHeight(options.diff);
			if (accordion) accordion.setHeight(options.layoutHeight-24);
		}
	});
	var height = $("#layout").height();
	$("#panel").ligerTab({height: height });
	panel = $("#panel").ligerGetTabManager();
	EM.setPanel(panel);
	$.each($("#accordion").children(),function(k,item){
		var tree = $('<ul></ul>').appendTo($(item));
		var key = $(item).attr('key');
		tree.ligerTree({
			data:navList[key].children,
			checkbox:false,
			single:true,
			slide:false,
			needCancel:false,
			nodeWidth:115,
			textFieldName:'name',
			attribute:['name','url'],
			onSelect:function(node){
				if (!node.data.url) return;
				var tabid = $(node.target).attr("tabid");
				if (!tabid){
					tabid = new Date().getTime();
					$(node.target).attr("tabid", tabid);
				}
				//if(/http:\/\//.test(node.data.url)){
					panel.addTabItem({tabid:tabid,text:node.data.name,url:node.data.url});
				//}else{
				//	panel.addTabItem({ tabid : tabid,text: node.data.name, target:EM.loadPanel(node.data.url)});
				//}
			}
		});
	});
	$("#accordion").ligerAccordion({ height: height-24, speed: null });
	accordion = $("#accordion").ligerGetAccordionManager();
	$("#pageloading").hide();
	//退出绑定
	$("#logout").click(EM.logout);
	$("#login").click(EM.login);
});
</script> 
<style type="text/css"> 
	body,html{height:100%;}
	body{ padding:0px; margin:0;   overflow:hidden;padding:0px;background:#EAEEF5;}  
	.l-link{ display:block; height:26px; line-height:26px; padding-left:10px; text-decoration:underline; color:#333;}
	.l-link2{text-decoration:underline; color:white; margin-left:2px;margin-right:2px;}
	.l-layout-top{background:#102A49; color:White;}
	.l-layout-bottom{ background:#E5EDEF; text-align:center;}
	#pageloading,{position:absolute; left:0px; top:0px; background:white url('<?php echo $_smarty_tpl->tpl_vars['static']->value;?>
images/loading.gif') no-repeat center; width:100%; height:100%;z-index:99999;}
	.l-link{ display:block; line-height:22px; height:22px; padding-left:16px;border:1px solid white; margin:4px;}
	.l-link-over{ background:#FFEEAC; border:1px solid #DB9F00;} 
	.l-winbar{ background:#2B5A76; height:30px; position:absolute; left:0px; bottom:0px; width:100%; z-index:99999;}
	.space{ color:#E7E7E7;}
	/* 顶部 */ 
	.l-topmenu{ margin:0; padding:0; height:31px; line-height:31px; background:url('<?php echo $_smarty_tpl->tpl_vars['static']->value;?>
images/top.jpg') repeat-x bottom;  position:relative; border-top:1px solid #1D438B;}
	.l-topmenu-logo{ color:#E7E7E7; padding-left:35px; line-height:26px;background:url('<?php echo $_smarty_tpl->tpl_vars['static']->value;?>
images/topicon.gif') no-repeat 10px 5px;}
	.l-topmenu-welcome{position:absolute; height:24px; line-height:24px;  right:30px; top:2px;color:#070A0C;}
	.l-topmenu-welcome a{ color:#E7E7E7; text-decoration:underline}
	h2{padding:20px 0;}
	h3{padding:5px 0;}
	p{padding-left:20px;}
</style>
</head>
<body>  
<div id="pageloading"></div>  
<div id="topmenu" class="l-topmenu">
    <div class="l-topmenu-logo">EasyManage 管理中心</div>
    <div class="l-topmenu-welcome">
        <a href="javascript:;" class="l-link2" id="username"><?php echo $_smarty_tpl->tpl_vars['username']->value;?>
</a>
		<span class="space">|</span>
		<a href="javascript:;" class="l-link2" id="login">切换用户</a>
		<span class="space">|</span>
		<a href="javascript:;" class="l-link2" id="logout">退出</a>
    </div> 
</div>

<div id="layout" style="width:99.2%; margin:0 auto; margin-top:4px; "> 
	<div position="left"  title="后台管理" id="accordion">
		<?php  $_smarty_tpl->tpl_vars['nav'] = new Smarty_Variable; $_smarty_tpl->tpl_vars['nav']->_loop = false;
 $_smarty_tpl->tpl_vars['key'] = new Smarty_Variable;
 $_from = $_smarty_tpl->tpl_vars['navlist']->value; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
foreach ($_from as $_smarty_tpl->tpl_vars['nav']->key => $_smarty_tpl->tpl_vars['nav']->value){
$_smarty_tpl->tpl_vars['nav']->_loop = true;
 $_smarty_tpl->tpl_vars['key']->value = $_smarty_tpl->tpl_vars['nav']->key;
?>
		<div title="<?php echo $_smarty_tpl->tpl_vars['nav']->value['name'];?>
" key="<?php echo $_smarty_tpl->tpl_vars['key']->value;?>
"></div>
 
		<?php } ?>
	</div>
	<div position="center" id="panel">
		<div tabid="readme" title="说明" style="margin:8px;" >
<h2 style="padding-top:0px;">EasyManage：</h2>
<p>EasyManage是一款后台管理系统，使用FN的PHP框架和基于jQuery的ligerUI作为前端处理</p>
<p>EasyManage的github地址：https://github.com/ueffort/EasyManage.git</p>
<p>FN的github地址：https://github.com/ueffort/fn-php.git</p>
<h2>相关链接：</h2>
<p><a href="http://www.ligerui.com/" target="_blank">ligerUI官方</a>、<a href="http://developer.baidu.com/bae" target="_blank">百度云平台</a></p>
<h2>使用说明：</h2>
<p>通过左侧的导航，可以查看对于ligerUI的修改，EasyManage的基本功能，FN的使用方式</p>
<p>目前程序搭建在百度云BAE中，FN框架底层支持BAE云的构架，日后还会继续添加SAE等云环境支持，以方便在多个云中迁移</p>
<h2>联系我们：</h2>
<p><a href="http://www.ueffort.com/" target="_blank">博客</a>、<a href="mailto:ueffort@ueffort.com" target="_blank" rel="nofollow">邮箱</a></p>
		</div>
	</div>
</div>
</body>
</html><?php }} ?>