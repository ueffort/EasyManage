/**
* jQuery ligerUI 1.1.9
* 
* http://ligerui.com
*  
* Author gaojie 2012 [ gaojie886@qq.com ] 
* 
*/
(function ($)
{
    $.fn.ligerEditor = function ()
    {
        return $.ligerui.run.call(this, "ligerEditor", arguments);
    };

    $.fn.ligerGetEditorManager = function ()
    {
        return $.ligerui.run.call(this, "ligerGetEditorManager", arguments);
    };

    $.ligerDefaults.Editor = {
		
    };
	//第三方扩展扩展
    $.ligerDefaults.Editor.thirdpartys = $.ligerDefaults.Editor.thirdpartys || {};
	$.ligerDefaults.Editor.thirdpartys['ueditor'] = {
		setValue:function(value){
			if(this.editor){
				this.editor.setContent(value);
			}else{
				this.editorvalue = value;
			}
			this.trigger('changeValue', [value]);
			this.trigger('validate', [value]);
		},
		getValue:function(){
			var g = this, p = this.options;
			return g.editor.getContent();
		},
		init:function(){
			if(typeof(UE) == 'undefined'){
				this._defaultEditor();
			}
			var g = this, p = this.options;
			p.initialContent = "";
			var editor = new UE.ui.Editor(p);
			//元素还没有加载进真实DOM，编辑器出错
			setTimeout(function() {
				editor.render(g.element);
				editor.ready(function(){
					g.editor = this;
					if(g.editorvalue){
						g.editor.setContent(g.editorvalue);
						g.editorvalue = null;
					}
				});
			}, 0);
		},
		destroy:function(){
			this.editor.destroy();
		},
		setHeight:function(height){
			this.editor.setHeight(height)
		},
		setWidth:function(width){
			return false;
		},
		setDisabled:function(value){
			if (value)
            {
                this.editor.enable();
            }
            else
            {
                this.editor.disable();
            }
		}
	};
	$.ligerMethos.Editor = {};
	
    $.ligerui.controls.Editor = function (element, options)
    {
        $.ligerui.controls.Editor.base.constructor.call(this, element, options);
    };

    $.ligerui.controls.Editor.ligerExtend($.ligerui.controls.Input, {
        __getType: function ()
        {
            return 'Editor'
        },
        __idPrev: function ()
        {
            return 'Editor';
        },
		_extendMethods: function ()
        {
            return $.ligerMethos.Editor;
        },
        _init: function ()
        {
            $.ligerui.controls.Editor.base._init.call(this);
            var g = this, p = this.options;
            if ($(this.element).attr("readonly"))
            {
                p.disabled = true;
            }
        },
        _render: function ()
        {
            var g = this, p = this.options;
			g.width & $(this.element).width(g.width);
			g.height & $(this.element).height(g.height);
            //外层
			g.wrapper = $(this.element).parent();
            g.wrapper.append('<div class="l-text-l"></div><div class="l-text-r"></div>');
            this._setEvent();
            g.set(p);
			g.ready = false;
			if(p.textarea && typeof(p.thirdpartys[p.textarea]) == 'object'){
				g.thirdparty = p.thirdpartys[p.textarea];
				g.thirdparty.init.call(g);
				return ;
			}else if(!p.textarea){
				this._defaultEditor();
			}
        },
		_defaultEditor:function(){
			var g = this, p = this.options;
			$(this.element).addClass("l-textarea")
			.change(function ()
            {
                g.trigger('changeValue', [this.value]);
				g.trigger('validate',[this.value]);
            });
			this.thirdparty = this.editor = false;
			return ;
		},
        _getValue: function ()
        {
			var g = this, p = this.options;
            return this.thirdparty ? this.thirdparty.getValue.call(g) : $(this.element).val();
        },
        _setEvent: function ()
        {
            
        },
        _setDisabled: function (value)
        {
			if(this.thirdparty) return this.thirdparty.setDisabled.call(g,value);
            if (value)
            {
                $(this.element).attr('disable',true);
            }
            else
            {
                $(this.element).attr('disable',false);
            }
        },
        _setWidth: function (value)
        {
            return this.thirdparty ? this.thirdparty.setWidth.call(g,value) : $(this.element).width(value);
        },
        _setHeight: function (value)
        {
            if (value > 100)
            {
                this.thirdparty ? this.thirdparty.setHeight.call(g,value) : $(this.element).height(value);
            }
        },
        _setValue: function (value)
        {
			if (value == null) return ;
            var g = this, p = this.options;
            if(this.thirdparty) return this.thirdparty.setValue.call(g,value);
			$(this.element).val(value);
			this.trigger('changeValue', [value]);
			this.trigger('validate', [value]);
        },
        _setLabel: function (value)
        {
            var g = this, p = this.options;
            if (!g.labelwrapper)
            {
                g.labelwrapper = g.wrapper.wrap('<div class="l-labeltext"></div>').parent();
                var lable = $('<div class="l-text-label" style="float:left;">' + value + ':&nbsp</div>');
                g.labelwrapper.prepend(lable);
                g.wrapper.css('float', 'left');
                if (!p.labelWidth)
                {
                    p.labelWidth = lable.width();
                }
                else
                {
                    g._setLabelWidth(p.labelWidth);
                }
                lable.height(g.wrapper.height());
                if (p.labelAlign)
                {
                    g._setLabelAlign(p.labelAlign);
                }
                g.labelwrapper.append('<br style="clear:both;" />');
                g.labelwrapper.width(p.labelWidth + p.width + 2);
            }
            else
            {
                g.labelwrapper.find(".l-text-label").html(value + ':&nbsp');
            }
        },
        _setLabelWidth: function (value)
        {
            var g = this, p = this.options;
            if (!g.labelwrapper) return;
            g.labelwrapper.find(".l-text-label").width(value);
        },
        _setLabelAlign: function (value)
        {
            var g = this, p = this.options;
            if (!g.labelwrapper) return;
            g.labelwrapper.find(".l-text-label").css('text-align', value);
        },
		destroy:function()
		{
			if(this.thirdparty) this.thirdparty.destroy.call(g);
			if (this.element){
				$(this.element).remove();
			}
            this.options = null;
            $.ligerui.remove(this);
		}
    });
})(jQuery);