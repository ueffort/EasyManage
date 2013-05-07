(function ($){
//全局系统对象
	window['EI'] = {};
	//全局面板视图TAB控制器
	EI.panel= {};
	//当前页面所在的itemid
	EI.panelid = false;
	//当前页面弹窗层
	EI.dialog = $.ligerDialog;
	//默认当前页面操作
	EI.autoOp = 3;
	EI.setPanel = function(panel,id){
		EI.panel = panel;
		if(id) EI.panelid = id;//视图页面获取自身id
	}
	EI.setOp = function(autoOp){
		EI.autoOp = autoOp;
	}
	EI.setDialog = function(dialog){
		EI.dialog = dialog;
		setTimeout(function (){
			//EI.autosize();
		}, 0);
	}
	EI.autosize = function(){
		if(EI.dialog){
			var manage = EI.dialog;
			//manage._setHeight($('body').height());
			//manage._setWidth($('body').width());
			//manage.initLT();
		}
	}
	EI.view = {
		open: function(options,type){
			//新开TAB
			if(type.target && type.target == '_blank'){
				type.location = false;
				type.window = false;
			//自身
			}else if(type.target && type.target == 'self'){
				type.location = true;
			}
			if(type.window){
				//弹出层
				EI.dialogmanage = EI.dialog.open(options);
			}else if(type.location){
				EI.panel.overrideSelectedTabItem(options);
			}else {
				options.fatherid = EI.panelid;
				EI.panel.addTabItem(options);
			}
		},
		close: function(){
			if(EI.panelid){
				if(!EI.panel.removeChildSelectedFather(EI.panelid)){
					EI.panel.removeTabItem(EI.panelid);
				}
			}else{
				EI.dialog.close();
			}
		},
		reload: function(){
			if(EI.panelid){
				EI.panel.reload(EI.panelid);
			}else{
				EI.dialog.reload();
			}
		},
		//关闭并刷新当前页
		closeTreload:function(){
			this.close();
			var panelid = EI.panel.getSelectedTabItemID();
			if(panelid) EI.panel.reload(panelid);
		}
	}
    EI.cookies = (function (){
        var fn = function (){};
        fn.prototype.get = function (name){
            var cookieValue = "";
            var search = name + "=";
            if (document.cookie.length > 0){
                offset = document.cookie.indexOf(search);
                if (offset != -1){
                    offset += search.length;
                    end = document.cookie.indexOf(";", offset);
                    if (end == -1) end = document.cookie.length;
                    cookieValue = decodeURIComponent(document.cookie.substring(offset, end))
                }
            }
            return cookieValue;
        };
        fn.prototype.set = function (cookieName, cookieValue, DayValue){
            var expire = "";
            var day_value = 1;
            if (DayValue != null){
                day_value = DayValue;
            }
            expire = new Date((new Date()).getTime() + day_value * 86400000);
            expire = "; expires=" + expire.toGMTString();
            document.cookie = cookieName + "=" + encodeURIComponent(cookieValue) + ";path=/" + expire;
        }
        fn.prototype.remvoe = function (cookieName){
            var expire = "";
            expire = new Date((new Date()).getTime() - 1);
            expire = "; expires=" + expire.toGMTString();
            document.cookie = cookieName + "=" + escape("") + ";path=/" + expire;
            /*path=/*/
        };
        return new fn();
    })();

    //右下角的提示框
    EI.tip = function (message){
        if (EI.wintip){
            EI.wintip.set('content', message);
            EI.wintip.show();
        }else{
            EI.wintip = EI.dialog.tip({ content: message });
        }
        setTimeout(function (){
            EI.wintip.hide()
        }, 4000);
    };

    //预加载图片
    EI.prevLoadImage = function (rootpath, paths){
        for (var i in paths){
            $('<img />').attr('src', rootpath + paths[i]);
        }
    };
    //显示loading
    EI.showLoading = function (message){
        message = message || "正在加载中...";
        $('body').append("<div class='jloading'>" + message + "</div>");
        $.ligerui.win.mask();
    };
    //隐藏loading
    EI.hideLoading = function (message){
        $('body > div.jloading').remove();
        $.ligerui.win.unmask({ id: new Date().getTime() });
    }
    //显示成功提示窗口
    EI.showSuccess = function (message, callback){
        if (typeof (message) == "function" || arguments.length == 0)
        {
            callback = message;
            message = "操作成功!";
        }
        EI.dialog.success(message, '提示信息', callback);
    };
    //显示失败提示窗口
    EI.showError = function (message, callback){
        if (typeof (message) == "function" || arguments.length == 0){
            callback = message;
            message = "操作失败!";
        }
		EI.dialog.error(message, '提示信息', callback);
    };
    //预加载dialog的图片
    EI.prevDialogImage = function (rootPath){
        rootPath = rootPath || "";
        EI.prevLoadImage(rootPath + 'lib/ligerUI/skins/Aqua/images/win/', ['dialog-icons.gif']);
        EI.prevLoadImage(rootPath + 'lib/ligerUI/skins/Gray/images/win/', ['dialogicon.gif']);
    };
	EI.ajaxCallBack = function(result,success_callback,autoOp,error_callback){
		if (!result || result.error){
			if(result.error == 'null') EI.showError('链接错误！',errored);
			else if(result.error == 'nologin') EI.login();
			else if(result.error == 'noright') EI.showError('没有权限！',errored);
			else if(result.error == 'noparam') EI.showError('参数错误！',errored);
			else if(result.error == 'noresult') EI.showError('操作数据为空！',errored);
			else if(result.error == 'nobatch') EI.showError('不支持批量操作！',errored);
			else if(result.error == 'unknow') EI.showError('未知错误，联系管理员',errored);
			else if(result.error) EI.showError(result.error,errored);
			else errored();
		} else {
			if(autoOp == true) autoOp = EI.autoOp;
			if(!result.success || result.success == 'null' || result.success == 'success') 
				EI.showSuccess('操作成功！',successed);
			else 
				EI.showSuccess(result.success,successed);
		}
		function errored(){
			if(typeof(error_callback) == 'function'){
				error_callback(result);
			}
		}
		function successed(){
			if(typeof(success_callback) == 'function'){
				//返回False则不执行关闭自己
				autoOpFun(success_callback(result));
			}else{
				autoOpFun(autoOp);
			}
		}
		function autoOpFun(op){
			if(!autoOp) return ;
			if(!EI.panelid && !op && !EI.popDialog ) return ;
			if(typeof(op) == 'undefined') op = autoOp;
			if(op == 1){//close
				EI.view.close();
			}else if(op == 2){//refresh
				EI.view.reload();
			}else if(op == 3){
				EI.view.closeTreload();//close and refresh
			}
			
		}
	}
    //提交服务器请求
    //返回json格式
    //1,提交给类 options.type  方法 options.method 处理
    //2,并返回 AjaxResult(这也是一个类)类型的的序列化好的字符串
    EI.ajax = function (options){
		options = $.extend({
			type: 'post', cache: false, dataType: 'json',
			success: function (result){
				EI.ajaxCallBack(result,this.successed,this.autoOp,this.errored);
				EI.hideLoading();
			},
			error: function (result){
				EI.tip('发现系统错误 <BR>错误码：' + result.status);
			},
			beforeSend: function (){
				EI.showLoading();
			},
			complete: function (){
				EI.hideLoading();
			}
		}, options || {});
        $.ajax(options);
    };
	
	EI.parsePanel = function(result){
		var panel = $("<div style='overflow:hidden'></div>");
		if(result.type){
			if(result.type=='list'){
				panel.append(EI.parseList(result));
			}else if(result.type=='form'){
				panel.append(EI.parseForm(result));
			}else{
				EI.showError('数据类型错误！');
			}
		}
		if(result.op){
			EI.setOp(result.op);
		}
		return panel;
	}
	EI.fun = {
		pageList : function (field){
			field.buttons = [];
			for(i=1;i<=field.page;i++){
				var item = {
					'text':i,
					'view':field.view,
					'url':field.url,
					'data':$.extend({},field.data,{page:i}),
					'title':viewtitle+':'+i,
					'location':true
				};
				if(field.nowpage == i) item.disabled = true;
				field.buttons.push(item);
			}
		},
		viewLink:function(item){
			if(typeof(item.getData) == 'function'){
				var data = item.getData();
				if(!data) return ;
			}else{
				var data = item.data;
			}
			if(item.view){
				EI.view.open({text:item.title ? item.title : item.text,url:item.url,data:data},{target:item.target,window:item.window,location:item.location});
			}else if(item.handle){
				EI.ajax({
					url:item.url,
					data:data,
					autoOp:item.autoOp,
					successed:item.successed ? item.successed : successed
				});
			}
			function successed(){
				if(item.success) EI.fun.viewLink(item.success);
			}
		},
		parseData : function(param,data,defaultdata){
			var dataparam = {};
			if(defaultdata) $.extend(dataparam,defaultdata);
			for(i=0;i<param.length;i++){
				dataparam[param[i]] = data[param[i]];
			}
			return dataparam;
		}
	};
	
	EI.event = {
		on : function (event,item){
			item[event] = function(){
				EI.event.now(item);
			}
		},
		click : function (item){
			EI.event.on('click',item);
		},
		now : function(item){
			if(item.confirm){
				EI.dialog.confirm(item.confirm,item.text,callback);
			}else{
				callback(true);
			}
			function callback(confirm){
				if(!confirm) return ;
				EI.fun.viewLink(item);
			}
		}
	};
	EI.Grid = {
		//是否支持批量处理的工具条切换
		Toolbarselect:function(grid,item){
			if(!grid.toolbarManager) return ;
			var num = grid.selected.length;
			var batch  = num > 1 ? true : false;
			var manage = grid.toolbarManager;
			EI.filterAble(manage,item);
			for(i=0;i<manage.options.items.length;i++){
				var item = manage.options.items[i];
				if(!item.param) continue;
				if((batch && !item.batch) || (num == 0 && !item.option)){
					manage.setDisabled(item.id);
				}
			}
		},
	}
	//扩展grid的字段展示功能
	$.ligerDefaults.Grid.formatters['color'] = function (value, column)
    {
		var split = column.split || ',';
		var values = [];
		if(typeof(value) == 'string') {
			values = value.split(split);
		}else if(typeof(value) == 'object'){
			values = value;
		}else if(typeof(value) == 'number'){
			values.push(value);
		}
		var html = '';
		var list = column.list;
		for(var i in values){
			var color = '';
			color = (list && list[values[i]]) ? list[values[i]] : values[i];
			html = html + '<div class="l-grid-color" style="background:'+color+';"></div>';
		}
		return html;
    }
	//扩展结束
	EI.filterAble = function(manage,items,filter){
		if(!filter){
			filter = items;
			items = manage.options.items;
		}
		for(var i in items){
			var item = items[i];
			var disabled = false;
			if(item.children){
				for(var k in item.children){
					EI.filterAble(manage,item.children[k],filter);
				}
			}
			//嵌套菜单
			item.menu && EI.filterAble(item.menu,filter);
			//参数必选，数据不存在则不能操作
			if(item.param && !item.option){
				for(var k in item.param){
					if(!filter[item.param[k]]){
						disabled = true;
						break;
					}
				}
			}
			//数据中的过滤项与过滤数据有一个不匹配，则不能操作
			if(item.filter && !disabled){
				for(var k in item.filter){
					if(!filter[k]) filter[k]=false;
						var a = item.filter[k].toString().split('/');
						a.shift();
						var tag = a.pop();
						var regular = new RegExp(a.join('\/'),tag);
						//console.log(filter[k].toString());
						//console.log(item.filter[k].toString());
						//console.log(filter[k].toString().match(regular));
					if(!filter[k].toString().match(regular)){
						disabled = true;
						break;
					}
				}
			}
			if(disabled){
				manage.setDisabled(item.id);
			}else{
				manage.setEnabled(item.id);
			}
		}
	}
	EI.parseMenu = function(items){
		if(items.length){
			for(var i in items) EI.parseMenu(items[i]);
		}
		var item = items;
		if(item.children){
			for(var i in item.children){
				EI.parseMenu(item.children[i]);
			}
			return ;
		}
		if(item.line){
			return ;
		}
		if(typeof(item.click) == 'function') return item;
		EI.event.click(item);
		return item;
	}
	EI.parseToolbar = EI.parseButton = function(items){
		if(items.length){
			for(var i in items) EI.parseButton(items[i]);
			return ;
		}
		var item = items;
		//嵌套菜单
		item.menu && EI.parseMenu(item.menu.items);
		//执行默认click操作
		if(typeof(item.click) == 'function') return item;
		EI.event.click(item);
		return item;
	};
	EI.parseList = function(result){
		var grid = $("<div style='overflow:hidden'></div>");
		if(result.dblclick){
			result.grid.onDblClickRow = function(data,index,dom){
				if(!result.dblclick.data) result.dblclick.data = {};
				var dataparam = EI.fun.parseData(result.dblclick.param,data,result.dblclick.data);
				result.dblclick.getData = function(){
					return dataparam;
				}
				EI.fun.viewLink(result.dblclick);
			}
		}
		if(result.menu){
			function parseMenu(items){
				for(var i in items){
					var item = items[i];
					item.children && parseToolbar(item.children);
					item.menu && parseToolbar(item.menu.items);
					if(!item.url) continue;
					item.getData = function(){
						var item = this;
						var dataparam = {};
						if(!confirm) return ;
						if(item.data) $.extend(dataparam, item.data);
						if(item.param){
							for(var i in item.param){
								dataparam[item.param[i]] = contentdata[item.param[i]];
							}
						}
						return dataparam;
					}
					item.successed = function(result){
						var manager = grid.ligerGetGridManager();
						manager.loadData();
						return false;
					}
				}
			}
			parseMenu(result.menu.items);
			EI.parseMenu(result.menu.items);
			var contentdata = {};
			var menu = $.ligerMenu(result.menu);
			result.grid.onContextmenu = function(data,index,dom,e){
				//关闭默认右键事件，IE需要返回false
				e.preventDefault();
				EI.filterAble(menu,data);
				contentdata = data;
				menu.show({top:e.clientY,left:e.clientX});
				return false;
			}
		}
		if(result.grid.toolbar && result.grid.toolbar.items){
			function parseToolbar(items){
				for(var i in items){
					var item = items[i];
					item.children && parseToolbar(item.children);
					item.menu && parseToolbar(item.menu.items);
					if(item.param && !item.option) item.disable = true;
					//视图，不支持批量操作
					if(item.view) item.batch = false;
					if(item.url){
						if(!item.getData) item.getData = function(){
							var item = this;
							var dataparam = {};
							var batch = false;
							if(item.data) $.extend(dataparam, item.data);
							if(item.param){
								var manager = grid.ligerGetGridManager(); 
								var data = manager.getSelectedRows();
								if(data.length ==0 && !item.option) return EI.tip('选择操作数据');
								if(data.length ==1){
									for(var i in item.param){
										dataparam[item.param[i]] = data[0][item.param[i]];
									}
								}else if(data.length >0){
									var dataArray = [];
									for(var i in data){
										dataArray[i] = {};
										if(item.data) $.extend(dataArray[i], item.data);
										for(var j in item.param){
											dataArray[i][item.param[j]] = data[i][item.param[j]];
										}
									}
									dataparam['batch'] = $.ligerui.toJSON(dataArray);
									dataparam['_batch'] = true;
								}
							}
							return dataparam;
						}
						if(!item.successed) item.successed = function(result){
							var manager = grid.ligerGetGridManager();
							manager.loadData();
							return false;
						}
					}
				}
			}
			parseToolbar(result.grid.toolbar.items);
			EI.parseToolbar(result.grid.toolbar.items);
			result.grid.onSelectRow = function(data,index,dom){
				EI.Grid.Toolbarselect(this,data);
			}
			result.grid.onUnSelectRow = function(data,index,dom){
				EI.Grid.Toolbarselect(this,data);
			}
		}
		if(result.search){
			if(!result.grid.data) result.grid.data = {};
			$.extend(result.grid.data,{search:$.ligerui.toJSON(result.search.data)});
		}
		grid.ligerGrid(result.grid);
		if(result.search){
			var form = $("<form></form>");
			var manage = form.ligerForm(result.search);
			manage.setData(result.search.data);
			manage.addFormButtons([
				{ text: '搜索', click: function (){
					var gmanage = grid.ligerGetGridManager();
					$.extend(gmanage.options.data,{search:$.ligerui.toJSON(manage.getData())});
					gmanage.loadData();
				}}
			]);
			form.prependTo(grid);
		}
		return grid;
	};
	EI.parseForm = function(result){
		var form = $("<form></form>");
		for(var i in result.form.fields){
			var field = result.form.fields[i];
			if(field.type == 'file' && field.options && field.options.url){
				(function(field){
					field.options.successed = function(result){
						var manager = form.ligerFormManager();
						var filefield = $.ligerui.get(manage.getField(field.name));
						filefield.deleteSuccess();
					}
					field.options.confirm = '是否删除当前的文件';
					EI.event.on('onDelete',field.options);
				})(field);
			}else if(field.buttons || field.page){
				if(field.page) EI.fun.pageList(field);
				EI.parseButton(field.buttons);
			}
		}
		var manage = form.ligerForm(result.form);
		if(result.submit){
			EI.parseButton(result.submit);
			manage.addFormButtons(result.submit);
		}else{
			manage.addFormButtons([
				{ text: '提交', click: function (){
					manage.submit(result.form.url,function(result){
						EI.ajaxCallBack(result,false,true);
					});
				}},
				{ text: '取消', click: function (){
					EI.view.close();
				}}
			]);
		}
		if(result.toolbar){
			function parseToolbar(items){
				for(var i in items){
					var item = items[i];
					item.children && parseToolbar(item.children);
					item.menu && parseToolbar(item.menu.items);
					if(typeof(item.click) != 'function'){
						item.getData = function(){
							var item = this;
							var dataparam = {};
							if(item.data) $.extend(dataparam, item.data);
							if(item.param){
								var manager = form.ligerFormManager();
								var data = manager.getData();
								for(var i in item.param){
									dataparam[item.param[i]] = data[item.param[i]];
								}
							}
							return dataparam;
						}
					}
				}
			}
			parseToolbar(result.toolbar.items);
			EI.parseToolbar(result.toolbar.items);
			var toolbar = $("<div></div>");
			var toolbarmanage = toolbar.ligerToolBar(result.toolbar);
			toolbar.prependTo(form);
			EI.filterAble(toolbarmanage,result.form.data);
		}
		return form;
	}
	EI.login = function (){
		$(document).bind('keydown.login', function (e){
			if (e.keyCode == 13){
				dologin();
			}
		});

		if (!window.loginWin){
			var loginPanle = $("<form></form>");
			loginPanle.ligerForm({
				fields: [
					{ display: '用户名', name: 'LoginUserName' },
					{ display: '密码', name: 'LoginPassword', type: 'password' }
				]
			});

			window.loginWin = $.ligerDialog.open({
				width: 400,
				height: 140, top: 200,
				isResize: true,
				title: '用户登录',
				target: loginPanle,
				buttons: [
					{ text: '登录', onclick: function (){
						dologin();
					}},
					{ text: '取消', onclick: function (){
						window.loginWin.hide();
						$(document).unbind('keydown.login');
					}}
				]
			});
		}else{
			window.loginWin.show();
		}

		$("#LoginUserName").focus();
		$("#LoginUserName,#LoginPassword").val("");

		function dologin(){
			var username = $("#LoginUserName").val();
			var password = $("#LoginPassword").val();

			$.ajax({
				type: 'post', cache: false, dataType: 'json',
				url: 'manage.main.login.handle',
				data: [
					{ name: 'username', value: username },
					{ name: 'password', value: password }
				],
				success: function (result){
					if (!result || !result.success){
						if(result.error == 'empty') EI.showError('请填写帐号密码！');
						if(result.error == 'noverify') EI.showError('帐号密码错误，请重新输入！');
						$("#LoginUserName").focus();
						return;
					} else {
						$("#username").html(result.username);
						window.loginWin.hidden();
					}
				},
				error: function (){
					EI.showError('发送系统错误,请与系统管理员联系!');
				},
				beforeSend: function (){
					EI.showLoading('正在登录中...');
				},
				complete: function (){
					EI.hideLoading();
				}
			});
		}
	};
	EI.logout = function(){
		$.ajax({
			type: 'post', cache: false, dataType: 'json',
			url: 'manage.main.logout.handle',
			data: [],
			success: function (result){
				if (result && result.success){
					location.href = '/';
				}
			},
			error: function (){
				$.ligerDialog.error('发送系统错误,请与系统管理员联系！');
			},
			beforeSend: function (){
				$.ligerDialog.waitting("正在退出系统中,请稍后...");
			},
			complete: function (){
				$.ligerDialog.closeWaitting();
			}
		});
	}
})(jQuery);