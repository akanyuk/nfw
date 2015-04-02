/** 
 * jQuery Active Form plugin
 * @date: 2014.06.02
 * 
 * options:
 * 		dataType:		custom dataType 'json' instead
 * 		action:			form action URL
 * 		beforeSubmit:	function
 * 		error:			Custom error's processing 
 * 		success:		After succes submut execution
 */
$.fn.activeForm = function(options) {
	var form = $(this);
	
	// Anti 'F5' in datepicker
	form.resetForm();
	
	if (!options) options = {};
	
	
	form.bind('setDatepicker', function(event, field){
		var withTime = $(field).attr('withTime') == '1' ? true : false;
		var altFieldID = 'adp' + Math.floor(Math.random()*10000000);
		var fieldName = $(field).attr('name');
		var fieldValue = $(field).val();
	
		$(field).val(fieldValue == '' ? '' : formatDateTime(fieldValue, withTime, true)).attr('disabled', 'disabled').removeAttr('name').after('<input type="hidden" id="' + altFieldID + '" name="' + fieldName + '" value="' + fieldValue + '" />');

		if (withTime) {
			$(field).datetimepicker({ 
				'altField': '#' + altFieldID, 
				'altFormat': '@',
				'altFieldTimeOnly': false,
				'onSelect': function(dateText, inst) {
					$('input[id="' + altFieldID + '"]').val($.datepicker.formatDate('@', $(field).datepicker('getDate')) / 1000);
					$(document).trigger('datepicker-select', [$(field)]);
				},
				'onClose': function(dateText, inst) {
					$('input[id="' + altFieldID + '"]').val($.datepicker.formatDate('@', $(field).datepicker('getDate')) / 1000);
					$(document).trigger('datepicker-select', [$(field)]);
				}				
			});
		}
		else {
			$(field).datepicker({ 
				'altField': '#' + altFieldID, 
				'altFormat': '@',
				'onSelect': function(dateText, inst) {
					$('input[id="' + altFieldID + '"]').val($.datepicker.formatDate('@', $(field).datepicker('getDate')) / 1000);
					$(document).trigger('datepicker-select', [$(field)]);
				}
			});
		}

		$('<img src="/assets/jquery.activeForm/jqueryui.eraser.png" title="Очистить" />').bind('click', function(){
			$(field).val('');
			form.find('input[id="' + altFieldID + '"]').val('');
			$(document).trigger('datepicker-select', [$(field)]);
		}).insertAfter($(field).next());
	});
	
	form.bind('cleanErrors', function(){
		form.find('[rel=error-info], [data-rel=error-info]').empty();
		form.find('INPUT').removeClass('error');
		form.find('TEXTAREA').removeClass('error');
		
		if ($.uniform) {
			form.find('*[class~="uniformed"]').each(function(){
				$.uniform.update(this);
			});		
		}
		
		if (typeof(options.cleanErrors) == 'function') {
			options.cleanErrors.apply(options);
		}		
	});
	
	// Create datepickers at start
	form.find('input[class="datepicker"]').each(function(index, field){
		form.trigger('setDatepicker', [field]);
	});
	
	// 'uniformify' fields
	if ($.uniform) {
		form.find('select:not(.uniformed), input[type="text"]:not(.uniformed), input[type="password"]:not(.uniformed), input[type="checkbox"]:not(.uniformed), input[type="radio"]:not(.uniformed), textarea:not(.uniformed)').uniform().addClass('uniformed');
	}
	
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
						if (i == 'general') {
							alert(e);
						}
						else {
							form.find('*[rel=error-info][id="'+i+'"]').html(e);
							form.find('*[data-rel=error-info][id="'+i+'"]').html(e);
							form.find('*[id='+i+']').find('INPUT').addClass('error');
							form.find('*[id='+i+']').find('TEXTAREA').addClass('error');
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