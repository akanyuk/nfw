(function($) {
	// Tooltip defaults
	if ($.ui.tooltip) {
		$.widget("ui.tooltip", $.ui.tooltip, {
		    options: {
		        content: function () {
		            return $(this).prop('title');
		        },
		        track:true, show:false, hide:false
		    }
		});
	}
	
	// Datepicker defaults
	if ($.datepicker) {
		$.datepicker.setDefaults({
			minDate: '-60y', maxDate: '+10y',
			showButtonPanel: true,
			changeMonth: true, changeYear: true,
			duration: 0,
			dateFormat: 'dd.mm.yy',
			altFormat: 'dd.mm.yy',
			showOn: 'button', buttonImage: '/assets/jquery.activeForm/jqueryui.calendar.png', buttonImageOnly: true
		});
		
		if ($('html').attr('lang') == 'ru') {
			$.datepicker.setDefaults({
				closeText: 'Ok',currentText: 'Сегодня',
				monthNamesShort: ['Январь','Февраль','Март','Апрель','Май','Июнь','Июль','Август','Сентябрь','Октябрь','Ноябрь','Декабрь'],
				dayNamesMin: ['Вс', 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб'],firstDay: 1
			});
		}
	}
	
	// Message box
	$(document).on('uiDialog', function(e, message, params){
		if (typeof(params) == 'undefined') params = {};

		var defaultTitleText = params.state == 'error' ? 'Error' : 'Success';
		var closeText = typeof(params.closeText) == 'undefined' ? 'Ok' : params.closeText;
		var titleText = typeof(params.titleText) == 'undefined' ? defaultTitleText : params.titleText;
		
		$('<div style="padding: 2em 1em 1em 1em;">' + message + '</div>').dialog({ 
			title: titleText, width: 'auto', minHeight: 10, autoOpen:true, draggable:false, modal:true, resizable: false,
			open: function(){
				if (params.state == 'error') {
					console.log($(this).siblings());
					$(this).siblings('div.ui-dialog-titlebar').addClass('ui-state-error');
				} 
			}, 
			close: function(){ $(this).dialog('destroy').remove(); },
			buttons: [ 
				{ text: closeText, click: function() { $(this).dialog('close'); } } 
			] 
		});	
	});
	
	/**
	 * Zebra rows (re)binding
	 * @date: 2012.10.21
	 */ 
	$(document).bind('zebraRows', function(e){
		t = (e.targetTable) ? e.targetTable : document;
		
		$(t).find('tbody').each(function(){
			$(this).find('tr.zebra td').removeClass("odd last");
			$(this).find('tr.zebra:odd td').addClass("odd");
			$(this).find('tr.zebra:last td').addClass("last");
		});
	});
	
	// ------------------------
	//  After document's ready
	// ------------------------
	$(document).bind('refresh', function(){
		$(document).trigger('zebraRows');
		
		// Add UI hover
		$(".ui-state-default").hover(
			function () {
				$(this).removeClass("ui-state-default");
				$(this).addClass("ui-state-hover");
			},
			function () {
				$(this).removeClass("ui-state-hover");
				$(this).addClass("ui-state-default");
			}
		);
		
		/**
		 * Convert all elements (div, a, button...) has class "ui-button" to jQuery UI button object
		 * @date: 2013.09.08
		 * 
		 * You can use "icon" attribute;
		 */ 
		$('.nfw-button').each(function(i){
			$(this).button().removeClass('nfw-button');
			
			if (typeof($(this).attr('icon')) !== 'undefined') {
				$(this).button('option', 'icons', { 'primary':$(this).attr('icon') });
			}
			
			if (!$(this).text()) {
				$(this).find('.ui-button-text').html('&nbsp;');
				$(this).find('.ui-button-text').css({ 'padding-left': '1.4em'});
			}
		});
		
		$('.nfw-button-small').each(function(i){
			$(this).removeClass('nfw-button-small');
			
			if (typeof($(this).attr('icon')) !== 'undefined') {
				$(this).find('.ui-button-text').html('&nbsp;');
				$(this).find('.ui-icon').css({'left': '0'});
				$(this).find('.ui-button-text').css({'padding': '2px 7px 2px 6px'});
			}
			else {
				$(this).find('.ui-button-text').css({ 'padding': '0.1em 0.5em'});
			}
		});
		
		$('.nfw-tooltip').each(function(i){
			$(this).tooltip().removeClass('nfw-tooltip');
		});
	});
})(jQuery);
