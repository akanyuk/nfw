<?php 
NFW::i()->registerResource('dataTables');
NFW::i()->registerResource('jquery.activeForm');
NFW::i()->registerResource('colorbox');
?>
<script type="text/javascript">
$(document).ready(function(){
	// Remove file
	$(document).on('click', 'a[rel="remove-file"]', function(){
		if (!confirm('Удалить файл вложения?')) return false;
		
		$.post('<?php echo $Module->formatURL('manage')?>', { 'remove_file': this.id }, function(response) {
			oTable.fnReloadAjax();
		});

		return false;
	});

	// Main table 
	var config = dataTablesDefaultConfig;

	// Infinity scrolling
	config.bScrollInfinite = true;
	config.bScrollCollapse = true;
	config.iDisplayLength = 100;
	config.sScrollY = $(window).height() - $('table[id="attachments-manage"]').offset().top - 102;

	config.fnRowCallback = function( nRow, aData, iDisplayIndex ) {
		// TD0: 0 - url, 1 - icon, 2 - image, 3 - filesize_str
		
		// Icon 1
		$('td:eq(0)', nRow).html('<img src="' + aData[0][1] + '" />');

		// URL
		if (aData[0][2] == '1') {
			$('td:eq(1)', nRow).html('<a rel="cb-img" href="' + aData[0][0] + '">' + aData[1] + '</a>');
		}
		else {
			$('td:eq(1)', nRow).html('<a target="_blank" href="' + aData[0][0] + '">' + aData[1] + '</a>');
		}
		
		// Filesize
		$('td:eq(3)', nRow).html(aData[0][3]);
		
		// Dates
		$('td:eq(4)', nRow).html(formatDateTime(aData[4], true));

		$('td:eq(6)', nRow).html('<a href="#" id="' + aData[6] + '" rel="remove-file" class="ui-icon ui-icon-close" title="Удалить файл"></a>');
		
		return nRow;
	}
	
	config.aoColumns = [
		{ 'bSortable': false, 'sClass': 'icon-column' },			// Иконка 1
	  	{ },						  								// Имя файла
	  	{ },						  								// Комментарий
	  	{ 'sClass': 'nowrap-column right', 'sType': 'numeric' },	// Размер
		{ 'sClass': 'nowrap-column', 'sType': 'numeric' },			// Добавлено
		{ 'sClass': 'nowrap-column' },								// Кем
		{ 'bSortable': false, 'sClass': 'icon-column' } 			// Действия
  	];
  	
	config.aaSorting = [[3,'asc']];

	config.fnDrawCallback = function(obj) {
		$('a[rel="cb-img"]').colorbox({
			maxWidth:'96%', maxHeight:'96%'
		});
	};
	
	var oTable = $('table[id="attachments-manage"]').dataTable(config);

	// Custom filtering function 
	$('.dataTables_filter').before($('div[id="custom-filters"]').html()).css('width', '60%');	
	$('div[id="custom-filters"]').remove();

	$('select[id="filter-owner"]').change(function(){
		oTable.fnReloadAjax('<?php echo $Module->formatURL('manage')?>&owner_class=' + $(this).val());
	}).uniform().trigger('change');

	$(document).trigger('refresh');
});
</script>

<div id="custom-filters" style="display: none;">
	<div style="float: left;">
		<select id="filter-owner">
			<?php foreach ($owners as $owner) echo '<option value="'.$owner.'">'.$owner.'</option>';?>
		</select>
	</div>
</div>

<table id="attachments-manage" class="dataTables">
	<thead>
		<tr>
			<th>&nbsp;</th>
			<th>Имя файла</th>
			<th>Комментарий</th>
			<th>Размер</th>
			<th>Добавлено</th>
			<th>Кем</th>
			<th>&nbsp;</th>
		</tr>
	</thead>
</table>