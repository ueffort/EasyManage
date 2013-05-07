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
    $.fn.ligerUploadFile = function ()
    {
        return $.ligerui.run.call(this, "ligerUploadFile", arguments);
    };

    $.fn.ligerGetUploadFileManager = function ()
    {
        return $.ligerui.run.call(this, "ligerGetUploadFileManager", arguments);
    };

    $.ligerDefaults.UploadFile = {
		isImage:true,	//是否是图片，如果是需设置预览大小
		previewWidth:200,
		previewHeight:200,
		onDelete:null,//ajax的删除操作
        onChangeValue: null,
        width: null,
        disabled: false,
        value: null,     //初始化值 
        nullText: null,   //不能为空时的提示
    };


    $.ligerui.controls.UploadFile = function (element, options)
    {
        $.ligerui.controls.UploadFile.base.constructor.call(this, element, options);
    };

    $.ligerui.controls.UploadFile.ligerExtend($.ligerui.controls.Input, {
        __getType: function ()
        {
            return 'UploadFile'
        },
        __idPrev: function ()
        {
            return 'UploadFile';
        },
        _init: function ()
        {
            $.ligerui.controls.UploadFile.base._init.call(this);
            var g = this, p = this.options;
            if (!p.width)
            {
                p.width = $(g.element).width();
            }
            if ($(this.element).attr("readonly"))
            {
                p.disabled = true;
            }
        },
        _render: function ()
        {
            var g = this, p = this.options;
            g.inputFile = $(this.element);
            //外层
            g.wrapper = g.inputFile.wrap('<div class="l-file"></div>').parent();
			if(p.isImage){
				g.image = $('<img class="preview" width="'+p.previewWidth+'" height="'+p.previewHeight+'" />').appendTo(g.wrapper).hide();
			}
			g.replaceButton = $('<a href="javascript:;">重新选择</a>').prependTo(g.wrapper).hide();
			g.replaceButton.click(function(){
				g.isUpload = false;
				if(p.onDelete) g.deleteButton.hide();
				g.replaceButton.hide();
				g.image.hide();
				g.inputFile.show().val("");
			});
			if(p.onDelete){
				g.deleteButton = $('<a href="javascript:;">删除</a>').prependTo(g.wrapper).hide();
				g.deleteButton.click(function(){
					p.onDelete.call(g);
				});
			}
            this._setEvent();
            g.set(p);
        },
        _getValue: function ()
        {
            return this.inputFile.val();
        },
        _setEvent: function ()
        {
            var g = this, p = this.options;
            g.inputFile.change(function ()
            {
				if (this.files && this.files[0]) {
					var reader = new FileReader();
					reader.onload = function (e) { g.setValue(e.target.result)};
					reader.readAsDataURL(this.files[0]);
				} else {
					//IE浏览器
					var file_upl = p.inputFile[0];  
					file_upl.select();
					var realpath = document.selection.createRange().text; 
					g.setValue(realpath);
				}
                g.trigger('changeValue', [this.value]);
				g.trigger('validate',[this.value]);
            });
        },
        _setDisabled: function (value)
        {
            if (value)
            {
                this.inputFile.attr("readonly", "readonly");
            }
            else
            {
                this.inputFile.removeAttr("readonly");
            }
        },
        _setValue: function (value)
        {
			var g = this, p = this.options;
            if (value != null && value)
			{
				if(/http/.test(value) || !/:/.test(value)) g.isUpload = true;
                if(p.isImage) g.image.attr('src',value).show();
				if(p.onDelete && g.isUpload){
					g.deleteButton.show();
					g.replaceButton.hide();
				}else{
					g.replaceButton.show();
				}
				g.inputFile.hide();
			}
		},
        updateStyle: function ()
        {
            var g = this, p = this.options;
        },
		replaceFile: function (obj)
		{
			var g = this, p = this.options;
			var old = g.inputFile;
			old.hide();
			g.inputFile = $(obj);
			g.inputFile.prependTo(g.wrapper);
			g.inputFile.hide();
			g.replaceButton.show();
			if(p.onDelete && g.isUpload) g.deleteButton.show();
			this._setEvent();
			return old;
		},
		uploadSuccess: function()
		{
			var g = this, p = this.options;
			g.isUpload = true;
			if(p.onDelete && g.isUpload) g.deleteButton.show();
			g.replaceButton.show();
			g.image.show();
			g.inputFile.hide();
		},
		deleteSuccess: function()
		{
			var g = this, p = this.options;
			g.isUpload = false;
			if(p.onDelete) g.deleteButton.hide();
			g.replaceButton.hide();
			g.image.hide();
			g.inputFile.show().val('');
		}
    });
})(jQuery);