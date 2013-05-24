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
    $.fn.ligerCheckBox = function (options)
    {
        return $.ligerui.run.call(this, "ligerCheckBox", arguments);
    };
    $.fn.ligerGetCheckBoxManager = function ()
    {
        return $.ligerui.run.call(this, "ligerGetCheckBoxManager", arguments);
    };
	$.ligerDefaults.CheckBox = {
        onBeforeSelect: false, 	//选择前事件
        onSelected: null, 		//选择值事件 
        initValue: null,
        initText: null,
        valueField: 'id',
        textField: 'text',
		split: ',',
		newline:true,
        data: null,
        render: null,            //文本框显示html函数
    };
    $.ligerMethos.CheckBox = {};

    $.ligerui.controls.CheckBox = function (element, options)
    {
        $.ligerui.controls.CheckBox.base.constructor.call(this, element, options);
    };
    $.ligerui.controls.CheckBox.ligerExtend($.ligerui.controls.Input, {
        __getType: function ()
        {
            return 'CheckBox';
        },
        __idPrev: function ()
        {
            return 'CheckBox';
        },
        _extendMethods: function ()
        {
            return $.ligerMethos.CheckBox;
        },
		_init: function ()
        {
            $.ligerui.controls.CheckBox.base._init.call(this);
            var p = this.options;
            if ($(this.element).attr("disabled"))
            {
                p.disabled = true;
            }
        },
        _render: function ()
        {
			var g = this, p = this.options;
			g.input = $(this.element);
			g.wrapper = g.input.addClass('l-hidden').wrap('<div class="l-checkbox-wrapper"></div>').parent();
			if(!p.data && g.input.attr('type') == 'checkbox'){
				p.data = [{id:g.input.attr('value'),text:''}];
			}
			if(p.data){
				g.setData(p.data);
				g.set(p);
			}
        },
		destroy: function ()
        {
            if (this.wrapper) this.wrapper.remove();
            this.options = null;
            $.ligerui.remove(this);
        },
		setData: function(data){
			var g = this,p = this.options;
			if (!data || !data.length) return;
            if (g.data != data) g.data = data;
			$('label',g.wrapper).remove();
            for (var i = 0; i < data.length; i++)
            {
                var val = data[i][p.valueField];
                var txt = data[i][p.textField];
				var checkbox = $("<label value='"+val+"'><a value='"+val+"' text='"+txt+"' class='l-checkbox'/>"+(p.render ? p.render(val,txt) : txt)+"</label>"+(p.newline ? "<br />":""));
                g.wrapper.append(checkbox);
            }
			$("label",g.wrapper).click(function(){
				if (p.disabled) return ;
				var checkbox = $(this).children()[0];
				if (g.hasBind('beforeSelect') && g.trigger('beforeSelect', [$(checkbox).attr("value"), $(checkbox).attr("text")]) == false) return false;
				$(checkbox).toggleClass("l-checkbox-checked");
                g._checkboxUpdateValue();
			}).hover(function(){
                if (p.disabled) return ;
				$("label", g.wrapper).not(this).each(function (){
					$(this).removeClass("l-over");
				});
				$(this).addClass("l-over");
			},function(){
				$(this).removeClass("l-over");
			}).mousedown(function(){
				if (p.disabled) return ;
				$(this).addClass("l-down");
			}).mouseup(function(){
				$(this).removeClass("l-down");
			});
            //选择项初始化
            g._dataInit();
		},
        _dataInit: function ()
        {
            var g = this, p = this.options;
            var value = null;
            //根据值来初始化
            if (p.initValue != null)
            {
                value = p.initValue;
                g.setValue(value);
            }
            //根据文本来初始化 
            else if (p.initText != null)
            {
                value = g.findValueByText(p.initText);
                g.setValue(value);
            }
            else if (g.input.val() != "")
            {
				value = g.input.val();
                g.setValue(value);
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
        setValue: function (value)
        {
            var g = this, p = this.options;
            var targetdata = value ? value.toString().split(p.split) : [];
			$(".l-checkbox", g.wrapper).each(function (){
				if($.inArray($(this).attr('value'),targetdata) >= 0){
					$(this).addClass("l-checkbox-checked");
				}else{
					$(this).removeClass("l-checkbox-checked");
				}
			});
			g._checkboxUpdateValue();
        },
        getValue: function ()
        {
            return this.input.val();
        },
        _setDisabled: function ()
        {
            this.wrapper.addClass("l-disabled");
            this.options.disabled = true;
        },
		_setDisabled: function (value)
        {
            //禁用样式
            if (value)
            {
                this.wrapper.addClass("l-disabled");
				this.options.disabled = true;
            } else
            {
                this.wrapper.removeClass("l-disabled");
				this.options.disabled = false;
            }
        },
		_checkboxUpdateValue: function(){
			var value = [];
			$(".l-checkbox-checked", this.wrapper).each(function(){
				value.push($(this).attr('value'));
			});
			value = value.join(this.options.split);
			if(this.input.attr('type')=='checkbox'){
				this.input.attr("checked", value ? true : false);
			}else{
				var text = this.findTextByValue(value);
				this.input.val(value);
			}
			this.input.trigger('change');
			this.trigger('selected', [value, text]);
			this.trigger('validate', [value]);
		},
        updateStyle: function ()
        {
			var g = this,p = this.options;
            if (this.input.attr("disabled"))
            {
				$(".l-checkbox", g.wrapper).attr('disabled', true);
                this.wrapper.addClass("l-disabled");
                this.options.disabled = true;
            }
            if (this.input.val())
            {
				var targetdata = this.input.val().split(p.split);
                $(".l-checkbox", g.wrapper).each(function (){
					if($.inArray($(this).attr('value'),targetdata) >= 0){
						$(this).addClass("l-checkbox-checked");
					}else{
						$(this).removeClass("l-checkbox-checked");
					}
				});
            }
            else
            {
                $(".l-checkbox", g.wrapper).each(function (){
					$(this).removeClass("l-checkbox-checked");
				});
            }
        }
    });
})(jQuery);