var CKEDITOR_BASEPATH = '/assets/ckeditor/';

$(document).ready(function(){
	// Custom language values
	if ($('html').attr('lang') == 'ru') {
		var ckLng = {
			'language': 'ru',
			'afSave': 'Сохранить изменения'
		};
	}
	else {
		var ckLng = {
			'language': 'en',
			'afSave': 'Save setting'
		};
	}
	
	/* CKEditor function
	 * Available options:
	 * height		textarea height 
	 * save_button	Add 'Save' button in toolbar. Enabled by default 
	 * toolbar		Toolbar type: 'Full' for extended toolbar 
	 * media		`owner_class` for media uploading
	 * media_owner	`owner_id` for media uploading
	 */
	$.fn.CKEDIT = function(options) {
		var oTextarea = $(this);
		if (!options) options = {};

		options.save_button = isNaN(options.save_button) || options.save_button ? true : false;
		
		// Select toolbar
    	if (options.toolbar == 'Full') {
	    	var ckToolbar = [
				{ name: 'document', items: [ 'active_form_save' ]},
				{ name: 'basicstyles', items: [ 'Format', 'Bold', 'Italic', 'Underline', 'Strike', 'Subscript', 'Superscript' ]},
				{ name: 'paragraph', items: [ 'NumberedList', 'BulletedList', '-', 'HorizontalRule', 'Blockquote', 'CreateDiv' ]},
				{ name: 'justify', items: [ 'JustifyLeft', 'JustifyCenter', 'JustifyRight', 'JustifyBlock' ]},
				{ name: 'insert', items: [ 'Link', 'Unlink', 'Anchor', '-', 'Image', 'Table', 'Iframe' ]},
				{ name: 'document2', items: [ 'Source', '-', 'Maximize' ]}
			];
	    }
	    else {
	    	var ckToolbar = [
 		   		{ name: 'document', items: [ 'active_form_save' ]},
 				{ name: 'basicstyles', items: [ 'Format', 'Bold', 'Italic', '-', 'NumberedList', 'BulletedList' ]},
 				{ name: 'justify', items: [ 'JustifyLeft', 'JustifyCenter', 'JustifyRight', 'JustifyBlock' ]},
 				{ name: 'insert', items: [ 'Link', 'Unlink', '-', 'Image', 'Table' ]},
 				{ name: 'document2', items: [ 'Source', 'Maximize' ]}
 			];
    	}
		
		var cfg = {
			language: ckLng.language,
			toolbar: ckToolbar,
			height: options.height ? options.height : 400,
			removePlugins: 'colorbutton,find,flash,font,forms,newpage,removeformat,smiley,specialchar,stylescombo,templates',
			format_tags: 'p;h1;h2;h3;pre',
			on: {
				configLoaded: function() {
					// Allow HTML
					this.config.protectedSource.push( /<script[\s\S]*?script>/g ); /* script tags */
					this.config.allowedContent = true; /* all tags */
					
	 	 		   	// Add `active_form` save
					if (options.save_button) {
		 		    	this.addCommand('active_form_save', {
		 	 				exec: function(editor) {
		 	 					oTextarea.closest('form').submit();
		 	 				}
		 	 			});
		 		    	this.ui.addButton('active_form_save', {
		 					label : ckLng.afSave,
		 					command : 'active_form_save',
		 			 	    icon: 'cke_save.png',
		 				});
					}
		    	}
		    }
		};
		
		if (options.media) {
		   	// Add `media` support
			var owner_str = options.media_owner ? '&owner_class=' + options.media + '&owner_id=' + options.media_owner : '&owner_class=' + options.media;
			
			cfg.filebrowserBrowseUrl = '/media.php?action=list&tpl=list_CKE' + owner_str;
			cfg.filebrowserImageBrowseUrl = '/media.php?action=list&tpl=list_CKE' + owner_str + '&type=image&t[1][x]=100&t[2][y]=100&t[3][x]=175&t[4][y]=175';
			cfg.filebrowserWindowWidth = '30%';
			cfg.filebrowserWindowHeight = '200';				
			cfg.filebrowserImageWindowWidth = '100%';
			cfg.filebrowserImageWindowHeight = '500';					
		}
		
		oTextarea.ckeditor(cfg);
	}
});