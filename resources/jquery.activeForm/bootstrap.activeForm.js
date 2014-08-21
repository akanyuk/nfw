/** 
 * jQuery Active Form plugin
 * @date: 2014.06.02
 * 
 * options:
 * 		dataType:		custom dataType 'json' instead
  * 	action:			form action URL
 * 		beforeSubmit:	function
 * 		error:			Custom error's processing 
 * 		success:		After succes submut execution
 */
$.fn.activeForm = function(options) {
	var form = $(this);
	
	// Anti 'F5' in datepicker
	form.resetForm();
	
	if (!options) options = {};
	
	// Datepicker
	form.find('input[rel="datepicker"]').each(function(){
		var dp = $(this);
		var container = $(this).closest('div');
		var name = $(this).attr('name');
		var sFormat = 'dd.mm.yyyy';
		var sPlaceholder = 'dd.mm.yyyy';
		var iMinView = 2;
		
		if ($(this).attr('withTime') == '1') {
			sFormat = 'dd.mm.yyyy hh:ii';
			sPlaceholder = 'dd.mm.yyyy hh:ss';
			iMinView = 0;
		}
		
		dp.attr({ 'readonly': '1', 'placeholder': sPlaceholder }).removeAttr('name');
		container.append('<input name="' + name + '" value="0" type="hidden" />');
		
		dp.datetimepicker({ 
			'autoclose': true,
			'todayBtn': true,
			'todayHighlight': true,
			'format': sFormat,
			'minView': iMinView,
			'weekStart': container.attr('language') == 'English' ? 0 : 1,
			'language': container.attr('language') == 'English' ? 'en' : 'ru' 
		}).on('changeDate', function(e) {
			if (typeof(e.date) == 'undefined') {
				container.find('input[name="' + name + '"]').val(0);
				return;
			}

		    var TimeZoned = new Date(e.date.setTime(e.date.getTime() + (e.date.getTimezoneOffset() * 60000)));
		    dp.datetimepicker('setDate', TimeZoned);				
		    container.find('input[name="' + name + '"]').val(TimeZoned.valueOf() / 1000);				
		});
		
		container.find('*[id="set-date"]').click(function(){
			dp.datetimepicker('show');
		});
		
		container.find('*[id="remove-date"]').click(function(){
			dp.val('').trigger('changeDate');
		});
	});	
	
	form.bind('cleanErrors', function(){
		form.find('div[class~="form-group"]').find('*[class="help-block"]').empty();
		form.find('div[class~="form-group"]').removeClass('has-error');
		
		if (typeof(options.cleanErrors) == 'function') {
			options.cleanErrors.apply(options);
		}		
	});
	
	// Modify form attributes
	form.attr('method', options.method ? options.method : 'POST');
	
	if (options.action) {
		form.attr('action', options.action);
	}

	// Add default class
	form.addClass(options.formClass ? options.formClass : 'active-form');
	
	form.ajaxForm({
		'beforeSubmit': function(a,f,o) {
			form.trigger('cleanErrors');
			o.dataType = (options.dataType) ? options.dataType : "json";
			
			if (typeof(options.beforeSubmit) == 'function') {
				return options.beforeSubmit.apply(options, [a,f,o]);
			}
		},
		'success': function(response) {
			form.trigger('cleanErrors');
			
			if (response && response.result && response.result == 'error') {
				if (typeof(options.error) == 'function') {
					var result = options.error.apply(options, [response, status]);
					if (result === false) return;
				}
				
				if (typeof(response.errors) == 'object') {
					$.each(response.errors, function(i, e) {
						if (form.find('div[class~="form-group"][id="'+i+'"]').length) {
							form.find('div[class~="form-group"][id="'+i+'"]').addClass('has-error');
							form.find('div[class~="form-group"][id="'+i+'"]').find('*[class="help-block"]').html(e);
						}
						else {
							alert(e);
						}
					});	
				}
			}
			else {
				if (typeof(options.success) == 'function') {
					options.success.apply(options, [response, status]);
				}
			}
		}
	});
	
	return this;
};