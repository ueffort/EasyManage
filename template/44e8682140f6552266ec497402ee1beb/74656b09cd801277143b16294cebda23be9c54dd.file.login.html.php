<?php /* Smarty version Smarty-3.1.12, created on 2014-03-29 08:25:20
         compiled from "H:\Item\easymanage-bae\templates\login.html" */ ?>
<?php /*%%SmartyHeaderCode:200885336756062e643-53061408%%*/if(!defined('SMARTY_DIR')) exit('no direct access allowed');
$_valid = $_smarty_tpl->decodeProperties(array (
  'file_dependency' => 
  array (
    '74656b09cd801277143b16294cebda23be9c54dd' => 
    array (
      0 => 'H:\\Item\\easymanage-bae\\templates\\login.html',
      1 => 1387701565,
      2 => 'file',
    ),
  ),
  'nocache_hash' => '200885336756062e643-53061408',
  'function' => 
  array (
  ),
  'variables' => 
  array (
    'static' => 0,
  ),
  'has_nocache_code' => false,
  'version' => 'Smarty-3.1.12',
  'unifunc' => 'content_5336756092ff73_35470479',
),false); /*/%%SmartyHeaderCode%%*/?>
<?php if ($_valid && !is_callable('content_5336756092ff73_35470479')) {function content_5336756092ff73_35470479($_smarty_tpl) {?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head> 
    <title>EasyManage 管理系统登录</title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <script src="<?php echo $_smarty_tpl->tpl_vars['static']->value;?>
js/jquery-1.8.3.min.js" type="text/javascript"></script>
    <link href="<?php echo $_smarty_tpl->tpl_vars['static']->value;?>
js/ligerUI/skins/Aqua/css/ligerui-dialog.css" rel="stylesheet" type="text/css" />
    <link href="<?php echo $_smarty_tpl->tpl_vars['static']->value;?>
js/ligerUI/skins/Gray/css/dialog.css" rel="stylesheet" type="text/css" />
    <script src="<?php echo $_smarty_tpl->tpl_vars['static']->value;?>
js/ligerUI/js/core/base.js" type="text/javascript"></script>
    <script src="<?php echo $_smarty_tpl->tpl_vars['static']->value;?>
js/ligerUI/js/plugins/ligerDialog.js" type="text/javascript"></script>
	<script src="<?php echo $_smarty_tpl->tpl_vars['static']->value;?>
js/EM.js" type="text/javascript"></script>
    <style type="text/css">
        *{ padding:0; margin:0;}
        body{ text-align:center; background:#4974A4;}
        #login{ width:740px; margin:0 auto; font-size:12px;}
        #loginlogo{ width:700px; height:100px; overflow:hidden; background:url('<?php echo $_smarty_tpl->tpl_vars['static']->value;?>
images/login/logo.jpg') no-repeat; margin-top:50px;   }
        #loginpanel{ width:729px; position:relative;height:300px;}
        .panel-h{ width:729px; height:20px; background:url('<?php echo $_smarty_tpl->tpl_vars['static']->value;?>
images/login/panel-h.gif') no-repeat; position:absolute; top:0px; left:0px; z-index:3;}
        .panel-f{ width:729px; height:13px; background:url('<?php echo $_smarty_tpl->tpl_vars['static']->value;?>
images/login/panel-f.gif') no-repeat; position:absolute; bottom:0px; left:0px; z-index:3;}
        .panel-c{ z-index:2;background:url('<?php echo $_smarty_tpl->tpl_vars['static']->value;?>
images/login/panel-c.gif') repeat-y;width:729px; height:300px;}
        .panel-c-l{ position:absolute; left:60px; top:40px;}
        .panel-c-r{ position:absolute; right:20px; top:50px; width:222px; line-height:200%; text-align:left;}
        .panel-c-l h3{ color:#556A85; margin-bottom:10px;}
        .panel-c-l td{ padding:7px;}
        
        .login-text{ height:24px; left:24px; border:1px solid #e9e9e9; background:#f9f9f9;}
        .login-text-focus{ border:1px solid #E6BF73;}
        .login-btn{width:114px; height:29px; color:#E9FFFF; line-height:29px; background:url('<?php echo $_smarty_tpl->tpl_vars['static']->value;?>
images/login/login-btn.gif') no-repeat; border:none; overflow:hidden; cursor:pointer;}
        #txtUsername,#txtPassword{ width:191px;} 
        #logincopyright{ text-align:center; color:White; margin-top:50px;}
    </style>
<script type="text/javascript">
$(function (){
	$(".login-text").focus(function (){
		$(this).addClass("login-text-focus");
	}).blur(function (){
		$(this).removeClass("login-text-focus");
	});
	$(document).keydown(function (e){
		if (e.keyCode == 13){
			var username = $("#txtUsername").val();
			var password = $("#txtPassword").val();
			dologin(username,password);
		}
	});
	$("#btnLogin").click(function (){
		var username = $("#txtUsername").val();
		var password = $("#txtPassword").val();
		dologin(username,password);
	});
	$("#btnGuest").click(function(){
		dologin('guest','guest');
	});
});
function dologin(username,password){
	if (username == ""){
		EM.showError('账号不能为空！');
		$("#txtUsername").focus();
		return;
	}
	if (password == ""){
		EM.showError('密码不能为空！');
		$("#txtPassword").focus();
		return;
	}
	$.ajax({
		type: 'post', cache: false, dataType: 'json',
		url: 'manage.main.login.handle',
		data: [{ name: 'username', value: username },{ name: 'password', value: password }],
		success: function (result){
			if (!result || result.error){
				if(result){
					if(result.error == 'empty') EM.showError('请填写帐号密码！');
					if(result.error == 'noverify') EM.showError('帐号密码错误，请重新输入！');
				}else{
					$.ligerDialog.error('登录失败，请与管理员联系！');
				}
				$("#txtUsername").focus();
				return;
			} else {
				location.href = '/';
			}
		},
		error: function (){
			EM.showError('发送系统错误,请与系统管理员联系！');
		},
		beforeSend: function (){
			$.ligerDialog.waitting("正在登陆中,请稍后...");
			$("#btnLogin").attr("disabled", true);
		},
		complete: function (){
			$.ligerDialog.closeWaitting();
			$("#btnLogin").attr("disabled", false);
		}
	});
}
</script>
</head>
<body style="padding:10px"> 
    <div id="login">
        <div id="loginlogo"></div>
        <div id="loginpanel">
            <div class="panel-h"></div>
            <div class="panel-c">
                <div class="panel-c-l">
                   
                    <table cellpadding="0" cellspacing="0">
                        <tbody>
                         <tr>
                            <td align="left" colspan="2"> 
                             <h3>请使用管理系统账号登陆</h3>
                            </td>
                            </tr> 
                            <tr>
                            <td align="right">账号：</td><td align="left"><input type="text" name="loginusername" id="txtUsername" class="login-text" /></td>
                         </tr>
                         <tr>
                            <td align="right">密码：</td><td align="left"><input type="password" name="loginpassword" id="txtPassword" class="login-text" /></td>
                            </tr> 
                            <tr>
                            <td align="center" colspan="2">
                                <input type="submit" id="btnLogin" value="登陆" class="login-btn" />
                            </td>
                            </tr> 
                        </tbody>
                    </table>
                </div>
                <div class="panel-c-r">
                <p>请从左侧输入登录账号和密码登录</p>
                <p>测试帐号：guest，测试密码：guest</p>
				<p><input type="button" id="btnGuest" value="游客登陆" class="login-btn" /></p>
                <p>如果没有账号，请联系网站管理员。 </p>
                <p>......</p>
                </div>
            </div>
            <div class="panel-f"></div>
        </div>
         <div id="logincopyright">Copyright © 2012 <a href="http://www.ueffort.com" target="_blank">ueffort.com</a> - U-effort Grop</div>
    </div>
</body>
</html><?php }} ?>