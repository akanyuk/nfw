<?php
	foreach ($Module->record['attributes'] as $key=>&$a) { 
		$a['style'] = isset($a['style']) ? $a['style'] : 'width: '.(isset($a['width']) ? $a['width'] : '300px;');
	} 
	unset($a);
?>
<script type="text/javascript">
$(document).ready(function(){
 	// Action 'update'
 	var f = $('form[id="settings-update-<?php echo $Module->record['varname']?>"]')
 	f.activeForm({
 	 	beforeSubmit: function() {
 	 	 	var isSuccess = true;
 	 	 	 
 	 		f.find('div[id="values-area"]').find('input').removeClass('error');
 	 	 	$.each(f.find('div[id="values-area"]').find('input[req="required"]'), function(i,o) {
 	 	 	 	if ($(this).val()) return;

 	 	 	 	$(this).addClass('error');
 	 	 	 	isSuccess = false;
 	 	 	});
 	 	 	return isSuccess;
 	 	},
		success: function(response) {
			if (response.is_updated) {
				alert('Настройки сохранены');
			}
		}
	});

 	// Sortable `values`
 	f.find('div[id="values-area"]').sortable({
		items: 'div[id="record"]',
 		axis: 'y', 
 	 	handle: '.icon'
	});

 	$(document).on('click', '*[rel="remove-values-record"]', function(event){
 	 	if ($(this).closest('div[id="record"]').attr('rel') == 'update') {
 	 		if (!confirm('Удалить параметр?')) {
 	 			event.preventDefault();
 	 	 		return false;
 	 		}
 	 	}

 	 	$(this).closest('div[id="record"]').remove();
	});

 	f.find('button[id="add-values-record"]').click(function(){
 	 	var tpl = $('div[id="values-record-template-<?php echo $Module->record['varname']?>"]').html();
 	 	f.find('div[id="values-area"]').append(tpl);
 	 	f.find('input, select').uniform();
 	 	
 	 	return false;
	});

	
	$(document).trigger('refresh');
});
</script>
<style>
	.settings {	display: table;	}
	.settings .record, .settings .header { display:table-row; }
	.settings .record:nth-child(even) { background-color: #E2E4FF; }
	.settings .cell { display:table-cell; padding: 5px 2px; }
	.settings .cell:nth-child(1) { padding-left: 5px; }
	.settings .header .cell { font-size: 90%; font-weight: bold; }
</style>

<div id="values-record-template-<?php echo $Module->record['varname']?>" style="display: none;">
	<div id="record" class="record" rel="insert">
		<?php foreach ($Module->record['attributes'] as $key=>$a) { ?>
			<div class="cell"><input type="text" name="values[<?php echo $key?>][]" style="<?php echo $a['style']?>" placeholder="<?php echo $a['desc']?>" <?php echo isset($a['required']) && $a['required'] ? 'req="required"' : ''?> /></div>
		<?php } ?>
		<div class="cell"><span class="icon ui-icon ui-icon-arrowthick-2-n-s ui-state-disabled" title="Переместить"></span></div>
		<div class="cell"><span rel="remove-values-record" class="ui-icon ui-icon-closethick ui-state-disabled" title="Удалить"></span></div>
	</div>
</div>

<form id="settings-update-<?php echo $Module->record['varname']?>" action="<?php echo $Module->formatURL('update').'&varname='.$Module->record['varname']?>">
	<div id="values-area" class="settings">
		<div class="header">
			<?php foreach ($Module->record['attributes'] as $key=>$a) { ?>
				<div class="cell"><?php echo $a['desc']?></div>
			<?php } ?>
		</div>
		<?php foreach ($Module->record['values'] as $v) { ?>
			<div id="record" class="record" rel="update">
				<?php foreach ($Module->record['attributes'] as $key=>$a) { ?>
					<div class="cell"><input type="text" name="values[<?php echo $key?>][]" value="<?php echo $v[$key]?>" style="<?php echo $a['style']?>" placeholder="<?php echo $a['desc']?>" <?php echo isset($a['required']) && $a['required'] ? 'req="required"' : ''?> /></div>
				<?php } ?>
				<div class="cell"><span class="icon ui-icon ui-icon-arrowthick-2-n-s ui-state-disabled" title="Переместить"></span></div>
				<div class="cell"><span rel="remove-values-record" class="ui-icon ui-icon-closethick ui-state-disabled" title="Удалить"></span></div>
			</div>
		<?php } ?>
	</div>
	
	<div style="padding-top: 0.5em;">
		<button type="submit" name="form-send" class="nfw-button" icon="ui-icon-disk">Сохранить изменения</button>
		<button id="add-values-record" class="nfw-button" icon="ui-icon-plus">Добавить параметр</button>
	</div>
</form>