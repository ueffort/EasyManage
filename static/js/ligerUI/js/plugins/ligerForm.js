/**
* jQuery ligerUI 1.1.9
* 
* http://ligerui.com
*  
* Author daomi 2012 [ gd_star@163.com ] 
* 
*/
(function ($)
{
    $.fn.ligerForm = function ()
    {
        return $.ligerui.run.call(this, "ligerForm", arguments);
    };
	$.fn.ligerFormManager = function ()
    {
        return $.ligerui.run.call(this, "ligerFormManager", arguments);
    };
    $.ligerDefaults = $.ligerDefaults || {};
    $.ligerDefaults.Form = {
        //控件宽度
        inputWidth: false,
        //标签宽度
        labelWidth: 90,
        //间隔宽度
        space: 40,
        rightToken: '：',
        //标签对齐方式
        labelAlign: 'left',
		mode: 'table',   //3种模式，table，row，column，row和column取消newline设置，分别做优化设置，table为自定义设置
		validate : true,  //是否验证
        //控件对齐方式
        align: 'left',
        //字段
        fields: [],
        //创建的表单元素是否附加ID
        appendID: true,
        //生成表单元素ID的前缀
        prefixID: "",
        //json解析函数
        toJSON: $.ligerui.toJSON,
		editors:{}
    };

    //@description 默认表单编辑器构造器扩展(如果创建的表单效果不满意 建议重载)
    //@param {jinput} 表单元素jQuery对象 比如input、select、textarea 
    $.ligerDefaults.Form.editorbuilder = function (jinput)
    {
        //这里this就是form的ligerui对象
        var g = this, p = this.options;
		
        var name = jinput.attr('name');
		p.editors[name] = jinput;
		var options = {};
		for(var i in p.fields){
			if(p.fields[i].name == name){
				options = p.fields[i].options;
				break;
			}
		}
		if(!options) options = {};
        if (jinput.is("select"))
        {
            jinput.ligerComboBox(options);
        }
		else if (jinput.is("textarea"))
        {
            jinput.ligerEditor(options);
        }
        else if (jinput.is(":text") || jinput.is(":password") || jinput.is(":hidden") )
        {
            var ltype = jinput.attr("ltype");
            switch (ltype)
            {
				case "hidden":
					break;
				case "file":
					jinput.ligerUploadFile(options);
					break;
				case "grid":
					jinput.ligerGrid(options);
					break;
				case "radio":
					jinput.ligerRadio(options);
					break;
				case "checkbox":
					jinput.ligerCheckBox(options);
					break;
                case "select":
                case "combobox":
                    jinput.ligerComboBox(options);
                    break;
				case "search":
					options.search = true;
					jinput.ligerComboBox(options);
					break;
                case "spinner":
                    jinput.ligerSpinner(options);
                    break;
                case "date":
                    jinput.ligerDateEditor(options);
                    break;
                case "float":
                case "number":
					options.number = true;
                    jinput.ligerTextBox(options);
                    break;
                case "int":
                case "digits":
					options.digits = true;
					jinput.ligerTextBox(options);
					break;
				case "text":
					options.text = true;
					jinput.ligerTextBox(options);
					break;
                default:
                    jinput.ligerTextBox(options);
                    break;
            }
        }
    }
	$.ligerDefaults.Form.validates = $.ligerDefaults.Form.validates || {};
	$.ligerDefaults.Form.validates['required'] = function(value){
		return value ? true : false;
	}
	$.ligerDefaults.Form.validates['regular'] = function(value,regular){
		if(typeof(value) != 'string') return false;
		if(typeof(regular) == 'string'){
			var a = regular.split('/');
			a.shift();
			var tag = a.pop();
			var regular = new RegExp(a.join('\/'),tag);
		}
		return value.match(regular) ? true : false;
	}
	$.ligerDefaults.Form.validates['email'] = function(value){
		return this.options.validates['regular'].call(this,value,/^[0-9a-zA-Z]+@(([0-9a-zA-Z]+)[.])+[a-z]{2,4}$/);
	}
	$.ligerDefaults.Form.validates['url'] = function(value){
		return this.options.validates['regular'].call(this,value,/(http[s]{0,1}|ftp):\/\/[a-zA-Z0-9\.\-]+\.([a-zA-Z]{2,4})(:\d+)?(\/[a-zA-Z0-9\.\-~!@#$%^&*+?:_\/=<>]*)?/);
	}
	$.ligerDefaults.Form.validates['chinese'] = function(value){
		return this.options.validates['regular'].call(this,value,/^[u4E00-u9FA5]+$/);
	}
	$.ligerDefaults.Form.validates['num'] = function(value,type,regular){
		if(!regular) regular = '\d+(\.\d+)?';
		switch(parseInt(type)){
			case 1:
				regular = '('+regular+'|0+)';break;
			case 2:
				regular = '(-'+regular+'|0+)';break;
			default:
				regular = '-?'+regular;
		}
		return this.options.validates['regular'].call(this,value,'/^'+regular+'$/');
	}
	$.ligerDefaults.Form.validates['int'] = function(value,type){
		return this.options.validates['num'].call(this,value,type,'\d+');
	}
	$.ligerDefaults.Form.validates['float'] = function(value,type){
		return this.options.validates['num'].call(this.value,type,'\d+\.\d+');
	}
    //表单组件
    $.ligerui.controls.Form = function (element, options)
    {
        $.ligerui.controls.Form.base.constructor.call(this, element, options);
    };
	//接口方法扩展
    $.ligerMethos.Form = $.ligerMethos.Form || {};
    $.ligerui.controls.Form.ligerExtend($.ligerui.core.UIComponent, {
        __getType: function ()
        {
            return 'Form'
        },
        __idPrev: function ()
        {
            return 'Form';
        },
		_extendMethods: function ()
        {
            return $.ligerMethos.Form;
        },
        _init: function ()
        {
            $.ligerui.controls.Form.base._init.call(this);
        },
        _render: function ()
        {
            var g = this, p = this.options;
            var jform = $(this.element);
			if(p.name) jform.attr('name',p.name);
			g.hasFileInputs = false;
			p.editors = {};
			p.groups = {};
			g.validateInfo = {time:0,field:{},errorfield:{},first:false};
			var hidefields = [];
            //自动创建表单
            if (p.fields && p.fields.length)
            {
                if (!jform.hasClass("l-form"))
                    jform.addClass("l-form");
				//添加模式样式，可以在样式中自动调整显示类型
				jform.addClass("l-form-"+p.mode);
                var out = [];
                var appendULStartTag = false;
                $(p.fields).each(function (index, field)
                {
                    var name = field.name;
                    if (field.type == "hidden")
                    {
                        out.push('<input type="hidden" name="' + name + '" ltype="' + field.type + '" />');
                        return;
                    }
                    var newLine = field.renderToNewLine || field.newline;
                    if (newLine == null) newLine = true;
                    if (field.merge) newLine = false;
                    if (field.group || field.type == 'group') newLine = true;
                    if (index == 0) newLine = true;
					if (p.mode == 'row' && appendULStartTag){
						newLine = false;
					}else if(p.mode == 'column'){
						newLine = true;
					}
                    if (newLine)
                    {
                        if (appendULStartTag)
                        {
                            out.push('</ul>');
                            appendULStartTag = false;
                        }
						//如果要实现默认隐藏效果，应对field进行多维数组分割用以实现目的
                        if (field.group || field.type == 'group')
                        {
							field.display = field.display ? field.display : field.group;
                            out.push('<div text="'+field.display+'" class="l-group');
                            if (field.icon || field.img)
                                out.push(' l-group-hasicon');
                            out.push('">');
							if (field.img)
							{
								out.push("<img src='" + field.img + "' />");
							}
							else if (field.icon)
							{
								out.push("<div class='l-icon l-icon-" + field.icon + "'></div>");
							}
							
                            out.push('<span>' + field.display + '</span>');
							p.groups[field.display] = field;
							out.push('</div>');
                        }
                        out.push('<ul>');
                        appendULStartTag = true;
                    }
					if(field.group || field.type == 'group') return;
                    if(!name && !field.text) return;
					if(field.hide){
						hidefields.push(name);
					}
					out.push('<li fieldname="'+name+'">');
                    //append label
                    out.push(g._builderLabelContainer(field, newLine));
					if(field.text){
						out.push(field.text);
						return ;
                    }
					//append input 
                    out.push(g._builderControlContainer(field));
                    //append space
                    out.push(g._builderSpaceContainer(field));
					out.push('</li>');
                });
                if (appendULStartTag)
                {
                    out.push('</ul>');
                    appendULStartTag = false;
                }
                jform.append(out.join(''));
            }
			//生成ligerui表单样式
            $(".l-group", jform).each(function ()
            {
				$(this).append(g.builderButton(p.groups[$(this).attr('text')]['buttons']));
            });
            $("input,select,textarea", jform).each(function ()
            {
                p.editorbuilder.call(g, $(this));
            });
			if(p.data)
				setTimeout(function (){
					 g.setData(p.data);
				}, 0);
			if(hidefields){
				for(var i in hidefields){
					g.hide(hidefields[i]);
				}
			}
        },
        //标签部分
        _builderLabelContainer: function (field, newline)
        {
            var g = this, p = this.options;
            var label = field.label || field.display;
            var labelWidth = field.labelWidth || field.labelwidth || p.labelWidth;
            var labelAlign = field.labelAlign || p.labelAlign;
            if (label) label += p.rightToken;
            var out = [];
            out.push('<div class="l-form-container" style="');
            if (labelWidth && p.mode != 'row')
            {
                out.push('width:' + labelWidth + 'px;');
            }
            if (labelAlign)
            {
                out.push('text-align:' + labelAlign + ';');
            }
            out.push('">');
            if (label)
            {
                out.push(label);
            }
            out.push('</div>');
            return out.join('');
        },
        //控件部分
        _builderControlContainer: function (field)
        {
            var g = this, p = this.options;
            var width = field.width || p.inputWidth;
            var align = field.align || field.textAlign || field.textalign || p.align;
            var out = [];
            out.push('<div class="l-form-container" style="');
            if ((field.textarea || field.type == "textarea")) width = field.width ? field.width : false;
			if(width && p.mode =='table')
            {
                out.push('width:' + width + 'px;');
            }
            if (align)
            {
                out.push('text-align:' + align + ';');
            }
            out.push('">');
            out.push(g._builderControl(field));
            out.push('</div>');
            return out.join('');
        },
        //间隔部分
        _builderSpaceContainer: function (field)
        {
            var g = this, p = this.options;
            var spaceWidth = field.space || field.spaceWidth || p.space;
            var out = [];
            out.push('<div class="l-form-container" style="');
            if (spaceWidth && p.mode != 'column')
            {
                out.push('width:' + spaceWidth + 'px;');
            }
            out.push('">');
            out.push('</div>');
			//验证信息框
			out.push('<div class="l-form-container l-form-validate"></div>');
            return out.join('');
        },
		builderButton: function(buttons){
			if(!buttons) return ;
			if(!buttons.length) var buttons = [buttons];
			var out = [];
			for(var i in buttons){
				if(!buttons[i].text) continue;
				var button = $('<div class="btn"></div>');
				button.ligerButton(buttons[i]);
				out.push(button);
			}
			return out;
		},
        _builderControl: function (field)
        {
            var g = this, p = this.options;
            var width = field.width || p.inputWidth;
            var name = field.name || field.id;
            var out = [];
            if (field.comboboxName && field.type == "select")
            {
                out.push('<input type="text" id="' + p.prefixID + name + '" name="' + name + '" />');
            }
            if (field.textarea || field.type == "textarea")
            {
                out.push('<textarea ');
				if(field.textarea) field.options = $.extend(field.options,{textarea:field.textarea});
            }
            else if (field.type == "checkbox" || field.type == "radio" || field.type == "grid")
            {
                out.push('<input type="hidden" ');
            }
            else if (field.type == "password")
            {
                out.push('<input type="password" ');
            }
			else if (field.type == "file")
			{
				out.push('<input type="file" ');
				g.hasFileInputs = true;
			}
			else
            {
                out.push('<input type="text" ');
            }
            if (field.cssClass)
            {
                out.push('class="' + field.cssClass + '" ');
            }
            if (field.type)
            {
                out.push('ltype="' + field.type + '" ');
            }
            if (field.attr)
            {
                for (var attrp in field.attr)
                {
                    out.push(attrp + '="' + field.attr[attrp] + '" ');
                }
            }
            if (field.comboboxName && field.type == "select")
            {
                out.push('name="' + field.comboboxName + '"');
                if (p.appendID)
                {
                    out.push(' id="' + p.prefixID + field.comboboxName + '" ');
                }
            }
            else
            {
                out.push('name="' + name + '"');
                if (p.appendID)
                {
                    out.push(' id="' + name + '" ');
                }
            }
			field.options = field.options || {};
			//行模式，取消表单的行操作
			if(p.mode == 'row' && (field.type == 'radio' || field.type == 'checkbox')) field.options.newline = false;
			field.options.width = width;
			if(field.height) field.options.height = field.height;
			if(field.validate){
				//设置验证事件
				g.validateInfo.field[field.name] = true;
				field.options.onValidate = function(value){
					g.validateInfo.time++;
					var result = {};
					var ajax = false;
					var name = field.name;
					var required = false;
					for(var i in field.validate){
						var valid = field.validate[i];
						if(i == 'required') required = true;
						if(typeof(valid) != 'object') continue;
						if(i == 'ajax'){
							ajax = valid['param'] ? value['param'] : {};
						}else if(p.validates[i]){
							if(!$.isArray(valid['param'])){
								if(valid['param']){
									valid['param'] = [valid['param']];
								}else{
									valid['param'] = new Array();
								}
							}
							var param = $.extend([],valid['param']);
							param.unshift(value);
							console.log(i);
							console.log(param);
							if(!p.validates[i].apply(g,param)){
								result = valid['message'] || {error:i};   
								//error的优先级最高
								if(result.error){
									break;
								}else{
									continue;
								}
							}
						}
					}
					if(result.error){
						g.validateInfo.time--;
						g.validateData(result,name);
						return ;
					}
					//对于必填项，每次都会ajax验证，非必填项如果为空则不进行ajax验证，所以必填项的验证优先级请务必最高
					if(ajax && (value || required)){
						var url = ajax['url'] || p.validate;
						var data = ajax['data'] || {};
						data[name] = value;
						$.ajax({url:url,data:data,type: 'post', cache: false, dataType: 'json',
							success: function (result){
								g.validateInfo.time--;
								g.validateData(result,name);
							},error: function (result){
								g.validateInfo.time--;
								alert('发现系统错误 <BR>错误码：' + result.status);
							},
						});
						return ;
					}
					g.validateInfo.time--;
					result = result || {success:'success'};
					g.validateData(result,name);
				}
			}
            out.push(' />');
            return out.join('');
        },
		validateData : function(result,name){
			var g = this, p =this.options;
			//错误列表，不允许提交
			if(result.error){
				g.validateInfo.errorfield[name] = true;
			}else if(g.validateInfo.errorfield[name]){
				delete g.validateInfo.errorfield[name];
			}
			//提示显示
			var tip = $('[fieldname='+name+']',$(this.element)).find('.l-form-validate').html('');
			if(result.success){
				tip.html('');
			}else{
				for(var i in result){
					tip.html('<span class="l-validate-'+i+'">'+result[i]+'</span>');
				}
			}
			//如果点击过提交，自动提交，提交中会判断是否还有字段验证失败
			if(g.submitClicked){
				g.submit.call(g,g.submitArray.param1,g.submitArray.param2,g.submitArray.param3,g.submitArray.param4);
			}
		},
		//获取该表单数据
		getData : function(){
			var g = this, p = this.options;
			var data = {};
            //获取表单值
            if (g.options.editors)
            {
				for(var i in g.options.editors){
					var $input = g.options.editors[i];
					$input = $.ligerui.get($input);
					if(!$input){
						$input = g.options.editors[i];
						data[i] = $input.val();
					}else{
						data[i] = $input.getValue();
					}
				}
			}
			return data;
		},
		setData:function(data){
			var g = this, p = this.options;
            //设置表单值
            if (data && g.options.editors)
            {
				for(var i in g.options.editors){
					if(typeof(data[i]) == 'undefined') continue;
					var $input = g.options.editors[i];
					$input = $.ligerui.get($input);
					if(!$input){
						$input = g.options.editors[i];
						$input.val(data[i]);
					}else{
						$input.setValue(data[i]);
					}
				}
			}
			return true;
		},
		getField:function(field){
			for(var i in this.options.editors){
				if(i == field) return this.options.editors[i];
			}
			return ;
		},
		show:function(field){
			if(field)
				$('[fieldname='+field+']',this.element).show();
			else
				$(this.element).show();
		},
		hide:function(field){
			if(field)
				$('[fieldname='+field+']',this.element).hide();
			else
				$(this.element).hide();
		},
		addFormButtons: function (buttons){
			if (!buttons) return;
			var g = this, p = this.options;
			var form = $(this.element);
			var formbar = $("div.form-buttons",form);
			if (formbar.length == 0)
				formbar = $('<div class="l-form-buttons"><div class="l-form-buttons-inner"></div></div>').appendTo(form);
			$("> div:first", formbar).append(g.builderButton(buttons));
		},
		submit:function(param1,param2,param3,param4){
			var g = this, p = this.options;
			//至少执行一次所有字段的验证
			if(!$.isEmptyObject(g.validateInfo.field) && !g.validateInfo.first){
				for(var i in g.validateInfo.field){
					var $input = g.options.editors[i];
					$input = $.ligerui.get($input);
					if($input){
						$input.trigger('validate', [$input.getValue()]);
					}
				}
				g.validateInfo.first = true;
			}
			if(g.validateInfo.time > 0){
				//等待ajax验证；
				g.submitClicked = true;
				g.submitArray = {param1:param1,param2:param2,param3:param3,param4:param4};
				return ;
			}else{
				g.submitClicked = false;
				g.submitArray = {};
			}
			var url = param1;
			var type = false;
			if(typeof(param2) == 'string'){
				var type = param2;
				var callback = false;
				var data = {};
			}else if(typeof(param2) == 'function'){
				var callback = param2;
				var data = {};
				var type = param3;
			}else{
				var data = param2;
				if(typeof(param3) == 'string'){
					var type = param3;
					var callback = false;
				}else{
					var callback = param3;
					var type = param4;
				}
			}
			if(!$.isEmptyObject(g.validateInfo.errorfield)){
				if(typeof(callback) == 'function') callback({error:'验证失败'});
				return ;
			}
			data = $.extend(this.getData(),data);
			var options = {url:url,type:'post',data:data,success:callback,cache:false,dataType:type ? type:'json'};
			if(g.hasFileInputs){
				fileUploadIframe(this,options);
			}else{
				$.ajax(options);
			}
		}
    });
	// private function for handling file uploads (hat tip to YAHOO!)
    function fileUploadIframe(formmanage,options) {
		var $form = $('<form action="'+options.url+'"></form>').hide();
        var form = $form[0], i, id, $io, io, timedOut, timeoutHandle;
		var fileArray = [];
        id = 'jqFormIO' + (new Date().getTime());
        var $io = $('<iframe name="' + id + '" />');
        $io.css({ position: 'absolute', top: '-1000px', left: '-1000px' });
		
        io = $io[0];

        
        var CLIENT_TIMEOUT_ABORT = 1;
        var SERVER_ABORT = 2;

        function getDoc(frame) {
            var doc = frame.contentWindow ? frame.contentWindow.document : frame.contentDocument ? frame.contentDocument : frame.document;
            return doc;
        }

        // take a breath so that pending repaints get some cpu time before the upload starts
        function doSubmit() {

            // update form attrs in IE friendly way
            form.setAttribute('target',id);
            form.setAttribute('method', options.type);
			
            $form.attr({
                encoding: 'multipart/form-data',
                enctype:  'multipart/form-data'
            });
            // support timout
            if (options.timeout) {
                timeoutHandle = setTimeout(function() { timedOut = true; cb(CLIENT_TIMEOUT_ABORT); }, options.timeout);
            }
            // look for server aborts
            function checkState() {
                try {
                    var state = getDoc(io).readyState;
                    //console.log('state = ' + state);
                    if (state && state.toLowerCase() == 'uninitialized')
                        setTimeout(checkState,50);
                }
                catch(e) {
                    //console.log('Server abort: ' , e, ' (', e.name, ')');
                    cb(SERVER_ABORT);
                    if (timeoutHandle)
                        clearTimeout(timeoutHandle);
                    timeoutHandle = undefined;
                }
            }
				for(var k in formmanage.options.editors){
					if(formmanage.options.editors[k].attr('ltype') == 'file'){
						fileArray.push(k);
						//需要复制整个File表单，并进行替换，否则只复制表单无法获取到当前选中的文件信息
						var file = formmanage.options.editors[k].clone();
						var manage = $.ligerui.get(formmanage.options.editors[k]);
						manage.replaceFile(file).prependTo($form);
						formmanage.options.editors[k] = file;
					}
				}
				for(var j in options.data){
					if($.inArray(j, fileArray)<0) $form.append("<input type='hidden' name='"+j+"' value='"+options.data[j]+"' />");
				}
                // add iframe to doc and submit the form
                $io.appendTo('body');
				$io.bind("load",cb);
				$form.appendTo('body');
                setTimeout(checkState,15);
				$form.submit();
            
        }
		
        doSubmit();

        var data, doc, domCheckCount = 50, callbackProcessed;

        function cb(e) {
            if (callbackProcessed) {
                return;
            }
            try {
                doc = getDoc(io);
            }
            catch(ex) {
                //console.log('cannot access response document: ', ex);
                e = SERVER_ABORT;
            }
            if (e === CLIENT_TIMEOUT_ABORT) {
                return;
            }
            else if (e == SERVER_ABORT) {
                return;
            }

            if (!doc && !timedOut){
				return;
            }
            if (io.detachEvent)
                io.detachEvent('onload', cb);
            else    
                io.removeEventListener('load', cb, false);

            var status = 'success', errMsg;
            try {
                if (timedOut) {
                    throw 'timeout';
                }

                var isXml = options.dataType == 'xml' || doc.XMLDocument || $.isXMLDoc(doc);
                //console.log('isXml='+isXml);
                if (!isXml && window.opera && (doc.body === null || !doc.body.innerHTML)) {
                    if (--domCheckCount) {
                        // in some browsers (Opera) the iframe DOM is not always traversable when
                        // the onload callback fires, so we loop a bit to accommodate
                        //console.log('requeing onLoad callback, DOM not available');
                        setTimeout(cb, 250);
                        return;
                    }
                    // let this fall through because server response could be an empty document
                    //log('Could not access iframe DOM after mutiple tries.');
                    //throw 'DOMException: not available';
                }

                //log('response detected');
                var docRoot = doc.body ? doc.body : doc.documentElement;
                if (isXml)
					options.dataType = 'xml';
				if (docRoot) {
                    options.status = Number( docRoot.getAttribute('status') );
                    options.statusText = docRoot.getAttribute('statusText');
                }
                var dt = (options.dataType || '').toLowerCase();
                var scr = /(json|script|text)/.test(dt);
                if (scr ) {
					// account for browsers injecting pre around json response
					var pre = doc.getElementsByTagName('pre')[0];
					var b = doc.getElementsByTagName('body')[0];
					if (pre) {
						data = pre.textContent ? pre.textContent : pre.innerText;
					}else if (b) {
						data = b.textContent ? b.textContent : b.innerText;
					}
                }
                else if (dt == 'xml') {
                    data = toXml(doc);
                }

                try {
                    data = httpData(data,dt);
                }
                catch (e) {
                    status = 'parsererror';
                    errMsg = (e || status);
                }
            }
            catch (e) {
                //console.log('error caught: ',e);
                status = 'error';
            }

            if (options.aborted) {
                //console.log('upload aborted');
                status = null;
            }

            if (options.status) { // we've set xhr.status
                status = (options.status >= 200 && options.status < 300 || xhr.status === 304) ? 'success' : 'error';
            }

            // ordering of these callbacks/triggers is odd, but that's how $.ajax does it
            if (status === 'success') {
				for(var k in formmanage.options.editors){
					if(formmanage.options.editors[k].attr('ltype') == 'file'){
						$.ligerui.get(formmanage.options.editors[k]).uploadSuccess();
					}
				}
                if (options.success)
                    options.success.call({}, data);
            }
            else if (status) {
                if (options.error)
                    options.error.call({}, status);
            }
			
            if (options.complete)
                options.complete.call({});

            callbackProcessed = true;
            if (options.timeout)
                clearTimeout(timeoutHandle);

            // clean up
            setTimeout(function() {
                $io.remove();
				$form.remove();
            }, 100);
        }

        var toXml = $.parseXML || function(s, doc) { // use parseXML if available (jQuery 1.5+)
            if (window.ActiveXObject) {
                doc = new ActiveXObject('Microsoft.XMLDOM');
                doc.async = 'false';
                doc.loadXML(s);
            }
            else {
                doc = (new DOMParser()).parseFromString(s, 'text/xml');
            }
            return (doc && doc.documentElement && doc.documentElement.nodeName != 'parsererror') ? doc : null;
        };
        var parseJSON = $.parseJSON || function(s) {
            /*jslint evil:true */
            return window['eval']('(' + s + ')');
        };

        var httpData = function( data, type) {

            var xml = type === 'xml';

            if (xml && data.documentElement.nodeName === 'parsererror') {
                if ($.error)
                    $.error('parsererror');
            }
            if (typeof data === 'string') {
                if (type === 'json' ) {
                    data = parseJSON(data);
                } else if (type === "script") {
                    $.globalEval(data);
                }
            }
            return data;
        };
        return ;
    }
})(jQuery);
