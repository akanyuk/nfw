/** 
 * jQuery Active Form plugin
 * @date: 2018.12.06
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
	
	// Datepicker
	form.find('input[data-datepicker]').each(function(){
		const dp = $(this);
		const container = dp.closest('div');
		const name = dp.attr('name');
		const withTime = parseInt(dp.data('withtime')) === 1;

		let sFormat = 'dd.mm.yyyy';
		let sPlaceholder = 'dd.mm.yyyy';
		let iMinView = 2;
		if (withTime) {
			sFormat = 'dd.mm.yyyy hh:ii';
			sPlaceholder = 'dd.mm.yyyy hh:mm';
			iMinView = 0;
		}

		dp.attr({ 'placeholder': sPlaceholder }).removeAttr('name');
		container.append('<input name="' + name + '" value="' + dp.data('unixtimestamp') + '" type="hidden" />');

		dp.datetimepicker({
			'autoclose': true,
			'todayBtn': !withTime,
			'todayHighlight': true,
			'format': sFormat,
			'minView': iMinView,
			'weekStart': container.data('language') === 'English' ? 0 : 1,
			'language': container.data('language') === 'English' ? 'en' : 'ru',
			'startDate': dp.data('startdate'),
			'endDate': dp.data('enddate')
		}).on('changeDate', function(e) {
			if (typeof(e.date) == 'undefined') {
				container.find('input[name="' + name + '"]').val(0);
				return;
			}

			const TimeZoned = new Date(e.date.setTime(e.date.getTime() + (e.date.getTimezoneOffset() * 60000)));
			dp.datetimepicker('setDate', TimeZoned);
		    container.find('input[name="' + name + '"]').val(TimeZoned.valueOf() / 1000);
		}).on('updateAltDate', function() {
			const date = dp.datetimepicker('getDate');
			date.setTime(date.getTime() - (date.getTimezoneOffset() * 60000));
		    container.find('input[name="' + name + '"]').val(date.valueOf() / 1000);
		});
		
		container.find('*[id="set-date"]').click(function(){
			dp.datetimepicker('show');
		});
		
		container.find('*[id="remove-date"]').click(function(){
			dp.val('').trigger('changeDate');
		});
		
		if (dp.data('editable') === '1'){
			dp.on('keyup', function(){
				dp.trigger('updateAltDate');
			});
		}
		else {
			dp.attr({ 'readonly': '1' }); 
		}
	});	
	
	form.bind('cleanErrors', function(){
		if (typeof(options.cleanErrors) == "function") {
			result = options.cleanErrors.apply(options);
			if (result === false) return;
		}
		
		form.find('[data-active-container]').find('*[class="help-block"]').empty();
		form.find('[data-active-container]').removeClass('has-error');
	});
	
	// Modify form attributes
	form.attr('method', options.method ? options.method : 'POST');
	
	if (options.action) {
		form.attr('action', options.action);
	}

	// Add default class
	if (!form.attr('class')) {
		form.addClass('form-horizontal active-form');
	}
	
	form.ajaxForm({
		'beforeSubmit': function(a,f,o) {
			form.trigger('cleanErrors');
			o.dataType = (options.dataType) ? options.dataType : "json";
			
			if (typeof(options.beforeSubmit) == 'function') {
				return options.beforeSubmit.apply(options, [a,f,o]);
			}
		},
		error: function (response) {
			if (typeof(options.error) == 'function') {
				const result = options.error.apply(options, [response]);
				if (result === false) {
					return;
				}
			}

			const optScrollToError = !(typeof (options.scrollToError) != 'undefined' && options.scrollToError === false);
			let scrollToError = false;

			$.each(response['responseJSON']['errors'], function (i, e) {
				if (form.find('[data-active-container="'+i+'"]').length) {
					form.find('[data-active-container="'+i+'"]').addClass('has-error');
					form.find('[data-active-container="'+i+'"]').find('*[class="help-block"]').html(e);

					if (scrollToError === false) {
						scrollToError = form.find('[data-active-container="'+i+'"]').offset().top - 128;
					}
				}
				else {
					alert(e);
				}
			});

			if (optScrollToError !== false && scrollToError !== false) {
				$('html, body').animate({ scrollTop: scrollToError }, 500);
			}
		},
		'success': function(response) {
			form.trigger('cleanErrors');
			
			if (response && response.result && response.result === 'error') {
				if (typeof(options.error) == 'function') {
					const result = options.error.apply(options, [response]);
					if (result === false) return;
				}
				
				if (typeof(response.errors) == 'object') {
					const optScrollToError = !(typeof (options.scrollToError) != 'undefined' && options.scrollToError === false);
					let scrollToError = false;

					$.each(response.errors, function(i, e) {
						if (form.find('[data-active-container="'+i+'"]').length) {
							form.find('[data-active-container="'+i+'"]').addClass('has-error');
							form.find('[data-active-container="'+i+'"]').find('*[class="help-block"]').html(e);
							
							if (scrollToError === false) {
								scrollToError = form.find('[data-active-container="'+i+'"]').offset().top - 128;
							}
						}
						else {
							alert(e);
						}
					});	
					
					if (optScrollToError !== false && scrollToError !== false) {
						$('html, body').animate({ scrollTop: scrollToError }, 500);
					}
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