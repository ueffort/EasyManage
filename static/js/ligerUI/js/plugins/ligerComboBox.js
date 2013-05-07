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

    $.fn.ligerComboBox = function (options)
    {
        return $.ligerui.run.call(this, "ligerComboBox", arguments);
    };

    $.fn.ligerGetComboBoxManager = function ()
    {
        return $.ligerui.run.call(this, "ligerGetComboBoxManager", arguments);
    };

    $.ligerDefaults.ComboBox = {
        resize: true,           //是否调整大小
        isMultiSelect: false,   //是否多选
        isShowCheckBox: false,  //是否选择复选框
        columns: false,       //表格状态
        selectBoxWidth: false, //宽度
        selectBoxHeight: false, //高度
        onBeforeSelect: false, //选择前事件
        onSelected: null, //选择值事件 
        initValue: null,
        initText: null,
        valueField: 'id',
        textField: 'text',
        valueFieldID: null,
        slide: true,           //是否以动画的形式显示
        split: ",",
        data: null,
        tree: null,            //下拉框以树的形式显示，tree的参数跟LigerTree的参数一致 
        treeLeafOnly: true,   //是否只选择叶子
        grid: null,              //表格
        onStartResize: null,
        onEndResize: null,
        hideOnLoseFocus: true,
        url: null,              //数据源URL(需返回JSON)
        onSuccess: null,
        onError: null,
        onBeforeOpen: null,      //打开下拉框前事件，可以通过return false来阻止继续操作，利用这个参数可以用来调用其他函数，比如打开一个新窗口来选择值
        render: null,            //文本框显示html函数
        absolute: true,         //选择框是否在附加到body,并绝对定位
		//暂不支持树形和表格型的搜索模式
		returntext:false,		//返回text还是value，纯搜索表单可以设置为text获取输入内容
		limit:10,               //搜索模式下，显示多少
		search:false,           //是否已搜索选择的方式，只允许单选，复选会自动切换为单选状态
		serachExtra:{},         //搜索url模式下提交的字段
		delay:400,               //搜索操作的延迟时间 
		//联级处理，不支持树形和表格型的处理
		link: false,			//是否启动联级下拉框处理，支持ajax或data的children字段，值允许单选，复选会自动切换为单选状态
		linktype:'last',	    //值处理是完整，还是返回最后一个值，默认是返回最后一个，完整是"full"，full的通过split进行分割
								//last模式下，联级下拉框不允许ajax传递，只能一次或直接传递联级数据
								
		newline:true,           //是否换行显示
    };

    //扩展方法
    $.ligerMethos.ComboBox = $.ligerMethos.ComboBox || {};


    $.ligerui.controls.ComboBox = function (element, options)
    {
        $.ligerui.controls.ComboBox.base.constructor.call(this, element, options);
    };
    $.ligerui.controls.ComboBox.ligerExtend($.ligerui.controls.Input, {
        __getType: function ()
        {
            return 'ComboBox';
        },
        _extendMethods: function ()
        {
            return $.ligerMethos.ComboBox;
        },
        _init: function ()
        {
            $.ligerui.controls.ComboBox.base._init.call(this);
            var p = this.options;
            if (p.columns)
            {
                p.isShowCheckBox = true;
            }
			//只能做单选
			if (p.search || p.link)
			{
				p.isMultiSelect = false;
			}
            if (p.isMultiSelect)
            {
                p.isShowCheckBox = true;
            }
        },
        _render: function ()
        {
            var g = this, p = this.options;
			g.isReblid = false;
            g.data = p.data;
            g.inputText = null;
            g.select = null;
            g.textFieldID = "";
            g.valueFieldID = "";
            g.valueField = null; //隐藏域(保存值)
            //文本框初始化
            if (this.element.tagName.toLowerCase() == "input")
            {
                this.element.readOnly = true;
                g.inputText = $(this.element);
                g.textFieldID = this.element.id;
            }
            else if (this.element.tagName.toLowerCase() == "select")
            {
                $(this.element).hide();
                g.select = $(this.element);
                p.isMultiSelect = false;
                p.isShowCheckBox = false;
                g.textFieldID = this.element.id + "_txt";
                g.inputText = $('<input type="text" readOnly="true"/>');
                g.inputText.attr("id", g.textFieldID).insertAfter($(this.element));
            } else
            {
                //不支持其他类型
                return;
            }
			g.inputText[0].autocomplete = 'off';
			if(p.search){
				g.inputText[0].readOnly = false;
			}
			g.currentValue = '';
            if (g.inputText[0].name == undefined) g.inputText[0].name = g.textFieldID;
            //隐藏域初始化
            g.valueField = null;
            if (p.valueFieldID)
            {
                g.valueField = $("#" + p.valueFieldID + ":input");
                if (g.valueField.length == 0) g.valueField = $('<input type="hidden"/>');
                g.valueField[0].id = g.valueField[0].name = p.valueFieldID;
            }
            else
            {
                g.valueField = $('<input type="hidden"/>');
                g.valueField[0].id = g.valueField[0].name = g.textFieldID + "_val";
            }
            if (g.valueField[0].name == undefined) g.valueField[0].name = g.valueField[0].id;
            //开关
            g.link = $('<div class="l-trigger"><div class="l-trigger-icon"></div></div>');
            //下拉框
            g.selectBox = $('<div class="l-box-select"><div class="l-box-select-inner"><table cellpadding="0" cellspacing="0" border="0" class="l-box-select-table"></table></div></div>');
            g.selectBox.table = $("table:first", g.selectBox);
            //外层
            g.wrapper = g.inputText.wrap('<div class="l-text l-text-combobox"></div>').parent();
            g.wrapper.append('<div class="l-text-l"></div><div class="l-text-r"></div>');
            g.wrapper.append(g.link);
            //添加个包裹，
            g.textwrapper = g.wrapper.wrap('<div class="l-text-wrapper"></div>').parent();
			if(!p.newline) g.textwrapper.css('float','left');
            if (p.absolute)
                g.selectBox.appendTo('body').addClass("l-box-select-absolute");
            else
                g.textwrapper.append(g.selectBox);

            g.textwrapper.append(g.valueField);
            g.inputText.addClass("l-text-field");
            if (p.isShowCheckBox && !g.select)
            {
                $("table", g.selectBox).addClass("l-table-checkbox");
            } else
            {
                p.isShowCheckBox = false;
                $("table", g.selectBox).addClass("l-table-nocheckbox");
            }
            //开关 事件
            g.link.hover(function ()
            {
                if (p.disabled) return;
                this.className = "l-trigger-hover";
            }, function ()
            {
                if (p.disabled) return;
                this.className = "l-trigger";
            }).mousedown(function ()
            {
                if (p.disabled) return;
                this.className = "l-trigger-pressed";
            }).mouseup(function ()
            {
                if (p.disabled) return;
                this.className = "l-trigger-hover";
            }).click(function ()
            {
                if (p.disabled) return;
                if (g.trigger('beforeOpen') == false) return false;
                if (p.search && !g.selectBox.is(":visible")){
					g.changeSearch();
				}else{
					g._toggleSelectBox(g.selectBox.is(":visible"));
				}
				return false;
            });
            g.inputText.click(function ()
            {
                if (p.disabled) return;
                if (g.trigger('beforeOpen') == false) return false;
				if (p.search && !g.selectBox.is(":visible")){
					g.changeSearch();
				}else{
					g._toggleSelectBox(g.selectBox.is(":visible"));
				}
				return false;
            }).blur(function ()
            {
                if (p.disabled) return;
                g.wrapper.removeClass("l-text-focus");
            }).focus(function ()
            {
                if (p.disabled) return;
                g.wrapper.addClass("l-text-focus");
            });
			if(p.search){
				g.inputText.bind(($.browser.opera ? "keypress" : "keydown"), function(event) {
					clearTimeout(g.timeout);
					g.timeout = setTimeout(callback, p.delay);
					function callback(){
						g.changeSearch.call(g);
					}
				});
			}
            g.wrapper.hover(function ()
            {
                if (p.disabled) return;
                g.wrapper.addClass("l-text-over");
            }, function ()
            {
                if (p.disabled) return;
                g.wrapper.removeClass("l-text-over");
            });
            g.resizing = false;
            g.selectBox.hover(null, function (e)
            {
                if (p.hideOnLoseFocus && g.selectBox.is(":visible") && !g.boxToggling && !g.resizing)
                {
                    g._toggleSelectBox(true);
                }
            });
            var itemsleng = $("tr", g.selectBox.table).length;
            if (!p.selectBoxHeight && itemsleng < 8) p.selectBoxHeight = itemsleng * 30;
            if (p.selectBoxHeight)
            {
                g.selectBox.height(p.selectBoxHeight);
            }
            //下拉框内容初始化
            g.bulidContent();

            g.set(p);
        },
		changeSearch: function ()
		{
			var g = this, p = this.options;
			var previousValue = g.currentValue;
			g.currentValue = g.inputText.val();
			if(g.currentValue == previousValue){
				if(!g.data || (g.data && g.data.length == 0)){
					g._toggleSelectBox(true);
				}else{
					g._toggleSelectBox(false);
				}
				return ;
			}else{
				g.valueField.val("");
				g.rebulidContent();
			}
		},
        destroy: function ()
        {
            if (this.wrapper) this.wrapper.remove();
            if (this.selectBox) this.selectBox.remove();
            this.options = null;
			if(this.linkSelect) this.linkSelect.destroy(value);
            $.ligerui.remove(this);
        },
        _setDisabled: function (value)
        {
            //禁用样式
            if (value)
            {
                this.wrapper.addClass('l-text-disabled');
            } else
            {
                this.wrapper.removeClass('l-text-disabled');
            }
			if(this.linkSelect) this.linkSelect.set('disabled',value);
        },
		_setHidden: function(value)
		{
			//隐藏
            if (value)
            {
                this.textwrapper.hide();
            } else
            {
                this.textwrapper.show();
            }
			if(this.linkSelect) this.linkSelect.set('hidden',value);
		},
        _setLable: function (label)
        {
            var g = this, p = this.options;
            if (label)
            {
                if (g.labelwrapper)
                {
                    g.labelwrapper.find(".l-text-label:first").html(label + ':&nbsp');
                }
                else
                {
                    g.labelwrapper = g.textwrapper.wrap('<div class="l-labeltext"></div>').parent();
                    g.labelwrapper.prepend('<div class="l-text-label" style="float:left;display:inline;">' + label + ':&nbsp</div>');
                    g.textwrapper.css('float', 'left');
                }
                if (!p.labelWidth)
                {
                    p.labelWidth = $('.l-text-label', g.labelwrapper).outerWidth();
                }
                else
                {
                    $('.l-text-label', g.labelwrapper).outerWidth(p.labelWidth);
                }
                $('.l-text-label', g.labelwrapper).width(p.labelWidth);
                $('.l-text-label', g.labelwrapper).height(g.wrapper.height());
                g.labelwrapper.append('<br style="clear:both;" />');
                if (p.labelAlign)
                {
                    $('.l-text-label', g.labelwrapper).css('text-align', p.labelAlign);
                }
                g.textwrapper.css({ display: 'inline' });
                g.labelwrapper.width(g.wrapper.outerWidth() + p.labelWidth + 2);
            }
        },
        _setWidth: function (value)
        {
            var g = this;
            if (value > 20)
            {
                g.wrapper.css({ width: value });
                g.inputText.css({ width: value - 20 });
                g.textwrapper.css({ width: value });
				if(this.linkSelect) this.linkSelect.set('width',value);
            }
        },
        _setHeight: function (value)
        {
            var g = this;
            if (value > 10)
            {
                g.wrapper.height(value);
                g.inputText.height(value - 2);
                g.link.height(value - 4);
                g.textwrapper.css({ width: value });
				if(this.linkSelect) this.linkSelect.set('height',value);
            }
        },
        _setResize: function (resize)
        {
            //调整大小支持
            if (resize && $.fn.ligerResizable)
            {
                var g = this;
                g.selectBox.ligerResizable({ handles: 'se,s,e', onStartResize: function ()
                {
                    g.resizing = true;
                    g.trigger('startResize');
                }
                , onEndResize: function ()
                {
                    g.resizing = false;
                    if (g.trigger('endResize') == false)
                        return false;
                }
                });
                g.selectBox.append("<div class='l-btn-nw-drop'></div>");
            }
        },
        //查找Text,适用多选和单选
        findTextByValue: function (value)
        {
            var g = this, p = this.options;
            if (value == undefined) return "";
            var texts = "";
            var contain = function (checkvalue)
            {
                var targetdata = value.toString().split(p.split);
                for (var i = 0; i < targetdata.length; i++)
                {
                    if (targetdata[i] == checkvalue) return true;
                }
                return false;
            };
            $(g.data).each(function (i, item)
            {
                var val = item[p.valueField];
                var txt = item[p.textField];
                if (contain(val))
                {
                    texts += txt + p.split;
                }
            });
            if (texts.length > 0) texts = texts.substr(0, texts.length - 1);
            return texts;
        },
        //查找Value,适用多选和单选
        findValueByText: function (text)
        {
            var g = this, p = this.options;
            if (!text && text == "") return "";
            var contain = function (checkvalue)
            {
                var targetdata = text.toString().split(p.split);
                for (var i = 0; i < targetdata.length; i++)
                {
                    if (targetdata[i] == checkvalue) return true;
                }
                return false;
            };
            var values = "";
            $(g.data).each(function (i, item)
            {
                var val = item[p.valueField];
                var txt = item[p.textField];
                if (contain(txt))
                {
                    values += val + p.split;
                }
            });
            if (values.length > 0) values = values.substr(0, values.length - 1);
            return values;
        },
        removeItem: function ()
        {
        },
        insertItem: function ()
        {
        },
        addItem: function ()
        {

        },
        _setValue: function (value)
        {
            var g = this, p = this.options;
			//搜索功能不能设置默认值
			if(p.search || typeof(value) == 'undefined') return ;
            var text = g.findTextByValue(value);
            if (p.tree)
            {
                g.selectValueByTree(value);
            }
			else if (p.grid)
			{
				g.selectValueByGrid(value);
			}
            else if (!p.isMultiSelect)
            {
                g._changeValue(value, text);
				if(!p.link){
					$("tr[value='" + value + "'] td", g.selectBox).addClass("l-selected");
					$("tr[value!='" + value + "'] td", g.selectBox).removeClass("l-selected");
				}
            }
            else
            {
                g._changeValue(value, text);
                var targetdata = value.toString().split(p.split);
                $("table.l-table-checkbox :checkbox", g.selectBox).each(function () {
					this.checked = false;
					$(".l-checkbox-checked", $(this).parent()).removeClass("l-checkbox-checked");
				});
                for (var i = 0; i < targetdata.length; i++)
                {
                    $("table.l-table-checkbox tr[value='" + targetdata[i] + "'] :checkbox", g.selectBox).each(function () { 
						this.checked = true;
						$(".l-checkbox", $(this).parent()).addClass("l-checkbox-checked"); 
					});
                }
            }
        },
        selectValue: function (value)
        {
            this._setValue(value);
        },
		rebulidContent: function ()
		{
			var g = this, p = this.options;
			g.isRebulid = true;
			g.bulidContent();
		},
        bulidContent: function ()
        {
            var g = this, p = this.options;
			if(p.search && g.inputText.val() == '') return ;
            if (g.select)
            {
                g.setSelect();
            }
			else if (p.tree)
            {
                g.setTree(p.tree);
            }
            else if (p.grid)
            {
                g.setGrid(p.grid);
            }
            else if (p.url)
            {
                $.ajax({
                    type: 'post',
                    url: p.url,
                    cache: false,
                    dataType: 'json',
					data: $.extend({
						q: g.lastWord(g.currentValue),
						limit: p.limit
					}, p.serachExtra),
                    success: function (data)
                    {
						g.data = data;
                        g.setData(g.data,true);
                        g.trigger('success', [g.data]);
                    },
                    error: function (XMLHttpRequest, textStatus)
                    {
                        g.trigger('error', [XMLHttpRequest, textStatus]);
                    }
                });
            }
            else if (g.data)
            {
                g.setData(g.data);
            }
            
        },
		trimWords: function (value) {
			var g = this, p = this.options;
			if (!value)
				return [""];
			if (!p.isMultiSelect)
				return [$.trim(value)];
			return $.map(value.split(p.split), function(word) {
				return $.trim(value).length ? $.trim(word) : null;
			});
		},
		lastWord: function (value) {
			var g = this, p = this.options;
			if ( !p.isMultiSelect )
				return value;
			var words = trimWords(value);
			if (words.length == 1) 
				return words[0];
			var cursorAt = $(input).selection().start;
			if (cursorAt == value.length) {
				words = trimWords(value)
			} else {
				words = trimWords(value.replace(value.substring(cursorAt), ""));
			}
			return words[words.length - 1];
		},
        clearContent: function ()
        {
            var g = this, p = this.options;
            $("table", g.selectBox).html("");
        },
		formatMatch: function (txt,val,currentValue)
		{
			var g = this, p = this.options;
			if(p.search && g.inputText.val() == '') return true;
			var txt = txt.toString();
			var currentValue = currentValue.toString();
			return p.formatMatch ? p.formatMatch(txt,val,currentValue) : (txt.indexOf(currentValue) >=0 ? true : false);
		},
		highlight: function(value, currentValue) 
		{
			var g = this, p = this.options;
			if(p.search && g.inputText.val() == '') return value;
			var value = value.toString();
			var currentValue = currentValue.toString();
			return value.replace(new RegExp("(?![^&;]+;)(?!<[^<>]*)(" + currentValue.replace(/([\^\$\(\)\[\]\{\}\*\.\+\?\|\\])/gi, "\\$1") + ")(?![^<>]*>)(?![^&;]+;)", "gi"), "<strong>$1</strong>");
		},
        setSelect: function ()
        {
            var g = this, p = this.options;
            this.clearContent();
            $('option', g.select).each(function (i)
            {
                var val = $(this).val();
                var txt = $(this).html();
				if(p.search){
					var formatted = g.formatMatch(txt,val,g.currentValue);
					if ( formatted === false ) return ;
				}
                var tr = $("<tr><td index='" + i + "' value='" + val + "' text='" + txt +"'>" + g.highlight(txt,g.currentValue) + "</td>");
                $("table.l-table-nocheckbox", g.selectBox).append(tr);
                $("td", tr).hover(function ()
                {
                    $(this).addClass("l-over");
                }, function ()
                {
                    $(this).removeClass("l-over");
                });
            });
            g._addClickEven();
			if(g.isRebulid) return ;
            $('td:eq(' + g.select[0].selectedIndex + ')', g.selectBox).each(function ()
            {
                if ($(this).hasClass("l-selected"))
                {
                    g.selectBox.hide();
                    return;
                }
                $(".l-selected", g.selectBox).removeClass("l-selected");
                $(this).addClass("l-selected");
                if (g.select[0].selectedIndex != $(this).attr('index') && g.select[0].onchange)
                {
                    g.select[0].selectedIndex = $(this).attr('index'); g.select[0].onchange();
                }
                var newIndex = parseInt($(this).attr('index'));
                g.select[0].selectedIndex = newIndex;
                g.select.trigger("change");
                g.selectBox.hide();
                var value = $(this).attr("value");
                var text = $(this).html();
                if (p.render)
                {
                    g.inputText.val(p.render(value, text));
                }
                else
                {
                    g.inputText.val(text);
                }
            });
        },
        setData: function (data,format)
        {
            var g = this, p = this.options;
            this.clearContent();
            if (!data || !data.length){
				g._toggleSelectBox(true);
				return;
			}
			if(p.search) g._toggleSelectBox(false);
            if (g.data != data) g.data = data;
            if (p.columns)
            {
                g.selectBox.table.headrow = $("<tr class='l-table-headerow'><td width='18px'></td></tr>");
                g.selectBox.table.append(g.selectBox.table.headrow);
                g.selectBox.table.addClass("l-box-select-grid");
                for (var j = 0; j < p.columns.length; j++)
                {
                    var headrow = $("<td columnindex='" + j + "' columnname='" + p.columns[j].name + "'>" + p.columns[j].header + "</td>");
                    if (p.columns[j].width)
                    {
                        headrow.width(p.columns[j].width);
                    }
                    g.selectBox.table.headrow.append(headrow);

                }
            }
            for (var i = 0; i < data.length; i++)
            {
                var val = data[i][p.valueField];
                var txt = data[i][p.textField];
				if(p.search && format!=true){
					var formatted = g.formatMatch(txt,val,g.currentValue);
					if ( formatted === false ) continue ;
				}
                if (!p.columns)
                {
                    $("table.l-table-checkbox", g.selectBox).append("<tr value='" + val + "'><td style='width:18px;'  index='" + i + "' value='" + val + "' text='" + txt + "' ><input type='checkbox' /></td><td index='" + i + "' value='" + val + "' align='left'>" + txt + "</td>");
                    $("table.l-table-nocheckbox", g.selectBox).append("<tr value='" + val + "'><td index='" + i + "' value='" + val + "' text='" + txt + "' align='left'>" + g.highlight(txt,g.currentValue) + "</td>");
                } else
                {
                    var tr = $("<tr value='" + val + "'><td style='width:18px;'  index='" + i + "' value='" + val + "' text='" + txt + "' ><input type='checkbox' /></td></tr>");
                    $("td", g.selectBox.table.headrow).each(function ()
                    {
                        var columnname = $(this).attr("columnname");
                        if (columnname)
                        {
                            var td = $("<td>" + data[i][columnname] + "</td>");
                            tr.append(td);
                        }
                    });
                    g.selectBox.table.append(tr);
                }
            }
            //自定义复选框支持
            if (p.isShowCheckBox && $.fn.ligerCheckBox)
            {
                $("table input:checkbox", g.selectBox).ligerCheckBox();
            }
            $(".l-table-checkbox input:checkbox", g.selectBox).change(function ()
            {
                if (this.checked && g.hasBind('beforeSelect'))
                {
                    var parentTD = null;
                    if ($(this).parent().get(0).tagName.toLowerCase() == "div")
                    {
                        parentTD = $(this).parent().parent();
                    } else
                    {
                        parentTD = $(this).parent();
                    }
                    if (parentTD != null && g.trigger('beforeSelect', [parentTD.attr("value"), parentTD.attr("text")]) == false)
                    {
                        g.selectBox.slideToggle("fast");
                        return false;
                    }
                }
                if (!p.isMultiSelect)
                {
                    if (this.checked)
                    {
                        $("input:checked", g.selectBox).not(this).each(function ()
                        {
                            this.checked = false;
                            $(".l-checkbox-checked", $(this).parent()).removeClass("l-checkbox-checked");
                        });
                        g.selectBox.slideToggle("fast");
                    }
                }
                g._checkboxUpdateValue();
            });
            $("table.l-table-nocheckbox td", g.selectBox).hover(function ()
            {
                $(this).addClass("l-over");
            }, function ()
            {
                $(this).removeClass("l-over");
            });
            g._addClickEven();
			if(g.isRebulid) return ;
            //选择项初始化
            g._dataInit();
        },
        //树
        setTree: function (tree)
        {
            var g = this, p = this.options;
            this.clearContent();
            g.selectBox.table.remove();
            if (tree.checkbox != false)
            {
                tree.onCheck = function ()
                {
                    var nodes = g.treeManager.getChecked();
                    var value = [];
                    var text = [];
                    $(nodes).each(function (i, node)
                    {
                        if (p.treeLeafOnly && node.data.children) return;
                        value.push(node.data[p.valueField]);
                        text.push(node.data[p.textField]);
                    });
                    g._changeValue(value.join(p.split), text.join(p.split));
                };
            }
            else
            {
                tree.onSelect = function (node)
                {
                    if (p.treeLeafOnly && node.data.children) return;
                    var value = node.data[p.valueField];
                    var text = node.data[p.textField];
                    g._changeValue(value, text);
                };
                tree.onCancelSelect = function (node)
                {
                    g._changeValue("", "");
                };
            }
            tree.onAfterAppend = function (domnode, nodedata)
            {
                if (!g.treeManager) return;
                var value = null;
                if (p.initValue) value = p.initValue;
                else if (g.valueField.val() != "") value = g.valueField.val();
                g.selectValueByTree(value);
            };
            g.tree = $("<ul></ul>");
            $("div:first", g.selectBox).append(g.tree);
            g.tree.ligerTree(tree);
            g.treeManager = g.tree.ligerGetTreeManager();
        },
        selectValueByTree: function (value)
        {
            var g = this, p = this.options;
            if (value != null)
            {
                var text = "";
                var valuelist = value.toString().split(p.split);
                $(valuelist).each(function (i, item)
                {
                    g.treeManager.selectNode(item.toString());
                    text += g.treeManager.getTextByID(item);
                    if (i < valuelist.length - 1) text += p.split;
                });
                g._changeValue(value, text);
            }
        },
        //表格
        setGrid: function (grid)
        {
            var g = this, p = this.options;
            this.clearContent();
            g.selectBox.table.remove();
            g.grid = $("div:first", g.selectBox);
            grid.columnWidth = grid.columnWidth || 120;
            grid.heightDiff = -2;
            grid.InWindow = false;
            g.gridManager = g.grid.ligerGrid(grid);
            p.hideOnLoseFocus = false;
            if (grid.checkbox != false)
            {
                var onCheckRow = function ()
                {
                    var rowsdata = g.gridManager.getCheckedRows();
                    var value = [];
                    var text = [];
                    $(rowsdata).each(function (i, rowdata)
                    {
                        value.push(rowdata[p.valueField]);
                        text.push(rowdata[p.textField]);
                    });
                    g._changeValue(value.join(p.split), text.join(p.split));
                };
                g.gridManager.bind('CheckAllRow', onCheckRow);
                g.gridManager.bind('CheckRow', onCheckRow);
            }
            else
            {
                g.gridManager.bind('SelectRow', function (rowdata, rowobj, index)
                {
                    var value = rowdata[p.valueField];
                    var text = rowdata[p.textField];
                    g._changeValue(value, text);
                });
                g.gridManager.bind('UnSelectRow', function (rowdata, rowobj, index)
                {
                    g._changeValue("", "");
                });
            }
            g.bind('show', function ()
            {
                if (g.gridManager)
                {
                    g.gridManager._updateFrozenWidth();
                }
            });
            g.bind('endResize', function ()
            {
                if (g.gridManager)
                {
                    g.gridManager._updateFrozenWidth();
                    g.gridManager.setHeight(g.selectBox.height() - 2);
                }
            });
			g.gridManager.bind('afterShowData',function()
			{ 
				if (p.initValue) value = p.initValue;
                else if (g.valueField.val() != "") value = g.valueField.val();
                g.selectValueByGrid(value);
			});
        },
		selectValueByGrid: function (value)
        {
            var g = this, p = this.options;
            if (value != null)
            {
                var text = "";
                var valuelist = value.toString().split(p.split);
				var data = g.gridManager.getData();
				$.each(data,function(i,item){
					if($.inArray(item[p.valueField],valuelist) <0) return;
					g.gridManager.select(i);
                    text += item[p.textField];
                    if (i < data.length - 1) text += p.split;
				});
                g._changeValue(value, text);
            }
        },
        _getValue: function ()
        {
			if(this.options.search && this.options.returntext){
				return this.getText();
			}else if(this.linkSelect){
				var value = this.linkSelect.getValue();
				if(!value) return $(this.valueField).val();
				if(this.options.linktype=='full'){
					return $(this.valueField).val()+this.options.split+value;
				}else{
					return value;
				}
			}else{
				return $(this.valueField).val();
			}
        },
        getValue: function ()
        {
            //获取值
            return this._getValue();
        },
		getText: function ()
		{
			if(this.linkSelect){
				var text = this.linkSelect.getText();
				if(!text) return $(this.inputText).val();
				if(this.options.linktype=='full'){
					return $(this.inputText).val()+this.options.split+text;
				}else{
					return text;
				}
			}else{
				return $(this.inputText).val();
			}
		},
        updateStyle: function ()
        {
            var g = this, p = this.options;
            g._dataInit();
        },
        _dataInit: function ()
        {
            var g = this, p = this.options;
            var value = null; 
            if (p.initValue != null && p.initText != null)
            {
                g._changeValue(p.initValue, p.initText);
            }
            //根据值来初始化
            if (p.initValue != null)
            {
                value = p.initValue;
                if (p.tree)
                {
                    if(value)
                        g.selectValueByTree(value);
                }
				else if (g.grid)
				{
					if(value)
						g.selectValueByGrid(value);
				}
                else
                {
                    var text = g.findTextByValue(value);
                    g._changeValue(value, text);
                }
            }
            //根据文本来初始化 
            else if (p.initText != null)
            {
                value = g.findValueByText(p.initText);
                g._changeValue(value, p.initText);
            }
            else if (g.valueField.val() != "")
            {
                value = g.valueField.val();
                if (p.tree)
                {
                    if(value)
                        g.selectValueByTree(value);
                }
				else if (g.grid)
				{
					if(value)
						g.selectValueByGrid(value);
				}
                else
                {
                    var text = g.findTextByValue(value);
                    g._changeValue(value, text);
                }
            }
            if (!p.isShowCheckBox && value != null)
            {
                $("table tr", g.selectBox).find("td:first").each(function ()
                {
                    if (value == $(this).attr("value"))
                    {
                        $(this).addClass("l-selected");
                    }
                });
            }
            if (p.isShowCheckBox && value != null)
            {
                $(":checkbox", g.selectBox).each(function ()
                {
                    var parentTD = null;
                    var checkbox = $(this);
                    if (checkbox.parent().get(0).tagName.toLowerCase() == "div")
                    {
                        parentTD = checkbox.parent().parent();
                    } else
                    {
                        parentTD = checkbox.parent();
                    }
                    if (parentTD == null) return;
                    var valuearr = value.toString().split(p.split);
                    $(valuearr).each(function (i, item)
                    {
                        if (item == parentTD.attr("value"))
                        {
                            $(".l-checkbox", parentTD).addClass("l-checkbox-checked");
                            checkbox[0].checked = true;
                        }
                    });
                });
            }
        },
        //设置值到 文本框和隐藏域
        _changeValue: function (newValue, newText)
        {
            var g = this, p = this.options;
			if (p.link){
				var r = g._linkBox(newValue,newText);
				newValue = r[0];
				newText = r[1];
			}
            g.valueField.val(newValue);
            if (p.render)
            {
                g.inputText.val(p.render(newValue, newText));
            }
            else
            {
                g.inputText.val(newText);
            }
            g.selectedValue = newValue;
            g.selectedText = newText;
            g.inputText.trigger("change").focus();
            g.trigger('selected', [newValue, newText]);
			g.trigger('validate', [newValue]);
        },
        //更新选中的值(复选框)
        _checkboxUpdateValue: function ()
        {
            var g = this, p = this.options;
            var valueStr = "";
            var textStr = "";
            $("input:checked", g.selectBox).each(function ()
            {
                var parentTD = null;
                if ($(this).parent().get(0).tagName.toLowerCase() == "div")
                {
                    parentTD = $(this).parent().parent();
                } else
                {
                    parentTD = $(this).parent();
                }
                if (!parentTD) return;
                valueStr += parentTD.attr("value") + p.split;
                textStr += parentTD.attr("text") + p.split;
            });
            if (valueStr.length > 0) valueStr = valueStr.substr(0, valueStr.length - 1);
            if (textStr.length > 0) textStr = textStr.substr(0, textStr.length - 1);
            g._changeValue(valueStr, textStr);
        },
        _addClickEven: function ()
        {
            var g = this, p = this.options;
            //选项点击
            $(".l-table-nocheckbox td", g.selectBox).click(function ()
            {
                var value = $(this).attr("value");
                var index = parseInt($(this).attr('index'));
                var text = $(this).attr("text");
                if (g.hasBind('beforeSelect') && g.trigger('beforeSelect', [value, text]) == false)
                {
                    if (p.slide) g.selectBox.slideToggle("fast");
                    else g.selectBox.hide();
                    return false;
                }
                if ($(this).hasClass("l-selected"))
                {
                    if (p.slide) g.selectBox.slideToggle("fast");
                    else g.selectBox.hide();
                    return;
                }
                $(".l-selected", g.selectBox).removeClass("l-selected");
                $(this).addClass("l-selected");
                if (g.select)
                {
                    if (g.select[0].selectedIndex != index)
                    {
                        g.select[0].selectedIndex = index;
                        g.select.trigger("change");
                    }
                }
                if (p.slide)
                {
                    g.boxToggling = true;
                    g.selectBox.hide("fast", function ()
                    {
                        g.boxToggling = false;
                    })
                } else g.selectBox.hide();
                g._changeValue(value, text);
            });
        },
        updateSelectBoxPosition: function ()
        {
            var g = this, p = this.options;
            if (p.absolute)
            {
                g.selectBox.css({ left: g.wrapper.offset().left, top: g.wrapper.offset().top + 1 + g.wrapper.outerHeight() });
            }
            else
            {
                var topheight = g.wrapper.offset().top - $(window).scrollTop();
                var selfheight = g.selectBox.height() + textHeight + 4;
                if (topheight + selfheight > $(window).height() && topheight > selfheight)
                {
                    g.selectBox.css("marginTop", -1 * (g.selectBox.height() + textHeight + 5));
                }
            }
        },
        _toggleSelectBox: function (isHide)
        {
            var g = this, p = this.options;
            var textHeight = g.wrapper.height();
            g.boxToggling = true;
            if (isHide)
            {
                if (p.slide)
                {
                    if(g.selectBox.is(":visible")) 
						g.selectBox.slideToggle('fast', function ()
						{
							g.boxToggling = false;
						});
                }
                else
                {
                    g.selectBox.hide();
                    g.boxToggling = false;
                }
            }
            else
            {
				//下拉框宽度、高度初始化
				if (p.selectBoxWidth)
				{
					g.selectBox.width(p.selectBoxWidth);
				}
				else
				{
					g.selectBox.css('width', g.wrapper.width());
				}
                g.updateSelectBoxPosition();
                if (p.slide)
                {
                    if(!g.selectBox.is(":visible")) 
						g.selectBox.slideToggle('fast', function ()
						{
							g.boxToggling = false;
							if (!p.isShowCheckBox && $('td.l-selected', g.selectBox).length > 0)
							{
								var offSet = ($('td.l-selected', g.selectBox).offset().top - g.selectBox.offset().top);
								$(".l-box-select-inner", g.selectBox).animate({ scrollTop: offSet });
							}
						});
                }
                else
                {
                    g.selectBox.show();
                    g.boxToggling = false;
                    if (!g.tree && !g.grid && !p.isShowCheckBox && $('td.l-selected', g.selectBox).length > 0)
                    {
                        var offSet = ($('td.l-selected', g.selectBox).offset().top - g.selectBox.offset().top);
                        $(".l-box-select-inner", g.selectBox).animate({ scrollTop: offSet });
                    }
                }
            }
						
            g.isShowed = g.selectBox.is(":visible");
            g.trigger('toggle', [isHide]);
            g.trigger(isHide ? 'hide' : 'show');
        },
		findValueByLast: function(last,data){
			if(!data ||data.length == 0) return last;
            var g = this, p = this.options;
			var tmp = '';
			for(var i in data){
				if(data[i][p.valueField] == last){
					return data[i][p.valueField];
				}else if(data[i]['children']){
					tmp = this.findValueByLast(last,data[i]['children']);
					if(tmp){
						return data[i][p.valueField] + p.split + tmp;
					}
				}
			}
			return false;
		},
		_linkBox:function(value){
            var g = this, p = this.options;
			if(!value){
				if(g.linkSelect){
					g.linkSelect.set('hidden',true);
					g.linkSelect.setValue('');
				}
				return value;
			}
			var value1 = null;
			var text1 = null;
			if(p.linktype == 'last'){
				value = g.findValueByLast(value,g.data);
			}
			var a = value.split(p.split);
			if(a.length>0){
				value1 = a.shift();
				value = a.join(p.split);
			}
			if(!g.linkSelect){
				var id = this.element.id + "_link";
				var input  = $('<input type="text" readOnly="true"/>').attr("id",id).insertAfter(g.textwrapper);
				var options = $.extend({},p);
				if(p.linktype == 'last') options.url = '';
				options.linktype = 'full';
				options.data = false;
				g.linkSelect = input.ligerComboBox(p);
			}
			$("tr[value='" + value1 + "'] td", g.selectBox).addClass("l-selected");
			$("tr[value!='" + value1 + "'] td", g.selectBox).removeClass("l-selected");
			if(p.url){
			}else if(g.data && value1){
				var data = null;
				for (var i = 0; i < g.data.length; i++){
					var val = g.data[i][p.valueField];
					if(val == value1) {
						text1 = g.data[i][p.textField];
						if(g.data[i]['children']) data = g.data[i]['children'];
						break;
					}
				}
				if(data){
					g.linkSelect.set('hidden');
					g.linkSelect.setData(data);
					g.linkSelect.setValue(value);
				}else{
					g.linkSelect.set('hidden',true);
					g.linkSelect.setData([]);
					g.linkSelect.setValue('');
				}
			}
			return [value1,text1];
		}
    });

    $.ligerui.controls.ComboBox.prototype.setValue = $.ligerui.controls.ComboBox.prototype.selectValue;
    //设置文本框和隐藏控件的值
    $.ligerui.controls.ComboBox.prototype.setInputValue = $.ligerui.controls.ComboBox.prototype._changeValue;
    

})(jQuery);