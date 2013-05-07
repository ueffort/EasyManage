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

    $.fn.ligerRadio = function ()
    {
        return $.ligerui.run.call(this, "ligerRadio", arguments);
    };

    $.fn.ligerGetRadioManager = function ()
    {
        return $.ligerui.run.call(this, "ligerGetRadioManager", arguments);
    };

    $.ligerDefaults.Radio = {
        onBeforeSelect: false, //选择前事件
        onSelected: null, //选择值事件 
		showRadio: true, //是否显示单选按钮
        initValue: null,
        initText: null,
        valueField: 'id',
        textField: 'text',
		newline:true,
        data: null,
        render: null,            //文本框显示html函数
    };

    $.ligerMethos.Radio = {};

    $.ligerui.controls.Radio = function (element, options)
    {
        $.ligerui.controls.Radio.base.constructor.call(this, element, options);
    };
    $.ligerui.controls.Radio.ligerExtend($.ligerui.controls.Input, {
        __getType: function ()
        {
            return 'Radio';
        },
        __idPrev: function ()
        {
            return 'Radio';
        },
        _extendMethods: function ()
        {
            return $.ligerMethos.Radio;
        },
		_init: function ()
        {
            $.ligerui.controls.Radio.base._init.call(this);
            var p = this.options;
            if ($(this.input).attr("disabled"))
            {
                p.disabled = true;
            }
        },
        _render: function ()
        {
            var g = this, p = this.options;
			if(p.data){
				g.input = $(this.element);
				g.wrapper = g.input.addClass('l-hidden').wrap('<div class="l-radio-wrapper"></div>').parent();
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
            for (var i = 0; i < data.length; i++)
            {
                var val = data[i][p.valueField];
                var txt = data[i][p.textField];
				var radio = $("<label value='"+val+"'><a value='"+val+"' text='"+txt+" 'class='l-radio'/>"+(p.render ? p.render(val,txt) : txt)+"</label>"+(p.newline ? "<br />":""));
				if(!p.showRadio) $(radio.children()[0]).hide();
                g.wrapper.append(radio);
            }
			$("label",g.wrapper).click(function(){
				if (p.disabled) return ;
				var radio = $(this).children()[0];
				if (g.hasBind('beforeSelect') && g.trigger('beforeSelect', [$(radio).attr("value"), $(radio).attr("text")]) == false) return false;
                $(".l-radio", g.wrapper).each(function (){
					$(this).removeClass("l-radio-checked");
				});
				$(radio).addClass("l-radio-checked");
                g._radioUpdateValue();
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
                g.setValue(value);
            }
        },
        findTextByValue: function (value)
        {
            var g = this, p = this.options;
            if (value == undefined) return "";
            var texts = "";
            var contain = function (checkvalue)
            {
                var targetdata = value.toString();
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
                    texts = txt;
					return false;
                }
            });
            if (texts.length > 0) texts = texts.substr(0, texts.length - 1);
            return texts;
        },
        findValueByText: function (text)
        {
            var g = this, p = this.options;
            if (!text && text == "") return "";
            var contain = function (checkvalue)
            {
                var targetdata = text.toString();
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
                    values = val;
					return false;
                }
            });
            return values;
        },
        setValue: function (value)
        {
            var g = this, p = this.options;
            $(".l-radio", g.wrapper).each(function (){
				if($(this).attr('value') == value.toString()){
					$(this).addClass("l-radio-checked");
				}else{
					$(this).removeClass("l-radio-checked");
				}
			});
			g._radioUpdateValue();
        },
        getValue: function ()
        {
            return $(this.input).val();
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
		_radioUpdateValue: function(){
			var value = $(".l-radio-checked", this.wrapper).attr('value');
			var text = this.findTextByValue(value);
			$(this.input).val(value);
			this.trigger('selected', [value, text]);
			this.trigger('validate', [value]);
		},
        updateStyle: function ()
        {
			var g = this,p = this.options;
            if ($(this.input).attr("disabled"))
            {
				$(".l-radio", g.wrapper).attr('disabled', true);
                this.wrapper.addClass("l-disabled");
                this.options.disabled = true;
            }
            if ($(this.input).val())
            {
                $(".l-radio", g.wrapper).each(function (){
					if($(this).attr('value') == $(this.input).val()){
						$(this).addClass("l-radio-checked");
					}else{
						$(this).removeClass("l-radio-checked");
					}
				});
            }
            else
            {
                $(".l-radio", g.wrapper).each(function (){
					$(this).removeClass("l-radio-checked");
				});
            }
        }
    });


})(jQuery);