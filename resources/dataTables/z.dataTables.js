/**
/**
 * dataTables defaults
 */
var dataTablesDefaultConfig = {
	'oLanguage': {'sLengthMenu': 'Показывать _MENU_ записей на странице','sZeroRecords': 'Ничего не найдено','sInfo': 'Записи с _START_ по _END_ (_TOTAL_ всего)','sInfoEmtpy': 'Записей не найдено','sInfoFiltered': '(отфильтровано из _MAX_ записей)','sSearch': 'Фильтр','oPaginate': {'sFirst': 'Первая страница','sLast': 'Последняя страница','sNext': 'Следующая страница','sPrevious': 'Предыдущая страница'}},
	'bJQueryUI': true,
	'bFilter': true,
	'bStateSave': true,
	'bAutoWidth': true,
	'bProcessing': false,
	'iCookieDuration': 864000,
	'sCookiePrefix': '',
	'iDisplayLength' : 20,
	'aLengthMenu': [10, 15, 20, 25, 30, 50],
	'sPaginationType': 'two_button' //'full_numbers'  
};

(function($) {
	// Resize event for all created dataTables 
	$(window).bind('resize', function () {
		var table = $.fn.dataTable.fnTables(true);
		if (table.length > 0) {
		  $(table).dataTable().fnAdjustColumnSizing();
		}
	});
		
	// Filtering delay
	jQuery.fn.dataTableExt.oApi.fnSetFilteringDelay = function ( oSettings, iDelay ) {
	    var _that = this;
	 
	    if ( iDelay === undefined ) {
	        iDelay = 250;
	    }
	      
	    this.each( function ( i ) {
	        $.fn.dataTableExt.iApiIndex = i;
	        var
	            $this = this, 
	            oTimerId = null, 
	            sPreviousSearch = null,
	            anControl = $( 'input', _that.fnSettings().aanFeatures.f );
	          
	            anControl.unbind( 'keyup' ).bind( 'keyup', function() {
	            var $$this = $this;
	  
	            if (sPreviousSearch === null || sPreviousSearch != anControl.val()) {
	                window.clearTimeout(oTimerId);
	                sPreviousSearch = anControl.val();  
	                oTimerId = window.setTimeout(function() {
	                    $.fn.dataTableExt.iApiIndex = i;
	                    _that.fnFilter( anControl.val() );
	                }, iDelay);
	            }
	        });
	          
	        return this;
	    } );
	    return this;
	};
	
	// Reload AJAX data
	jQuery.fn.dataTableExt.oApi.fnReloadAjax = function ( oSettings, sNewSource, fnCallback, bStandingRedraw ) {
		if ( typeof sNewSource != 'undefined' && sNewSource != null )
		{
			oSettings.sAjaxSource = sNewSource;
		}
		this.oApi._fnProcessingDisplay( oSettings, true );
		var that = this;
		var iStart = oSettings._iDisplayStart;
		
		oSettings.fnServerData( oSettings.sAjaxSource, [], function(json) {
			/* Clear the old information from the table */
			that.oApi._fnClearTable( oSettings );
			
			/* Got the data - add it to the table */
			var aData =  (oSettings.sAjaxDataProp !== "") ?
				that.oApi._fnGetObjectDataFn( oSettings.sAjaxDataProp )( json ) : json;
			
			for ( var i=0 ; i<aData.length ; i++ )
			{
				that.oApi._fnAddData( oSettings, aData[i] );
			}
			
			oSettings.aiDisplay = oSettings.aiDisplayMaster.slice();
			that.fnDraw();
			
			if ( typeof bStandingRedraw != 'undefined' && bStandingRedraw === true )
			{
				oSettings._iDisplayStart = iStart;
				that.fnDraw( false );
			}
			
			that.oApi._fnProcessingDisplay( oSettings, false );
			
			/* Callback user function - for event handlers etc */
			if ( typeof fnCallback == 'function' && fnCallback != null )
			{
				fnCallback( oSettings );
			}
		}, oSettings );
	};
})(jQuery);
