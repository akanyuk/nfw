<?php 
	NFW::i()->registerFunction('active_field');
?>
<style>
	div.checker { top: -3px; }
	div.record-info { font-size: 85%; font-style: italic; padding: 1em 0; }
	
	form#ca-update input[type="text"] { width: 100%; }
	form#ca-update input[class="datepicker"] { width: 100px; }
</style>

<div id="ca-update-dialog">
	<div id="additional-update-tabs" style="display: none;">
		<ul>
			<li><a href="#tabs-1">Значения</a></li>
			<?php if ($attribute['with_attachments']): ?><li><a href="#tabs-2">Вложения</a></li><?php endif; ?>
			<li><a href="#tabs-3">История изменений</a></li>
		</ul>
	
		<div id="tabs-1">
			<div id="field-id">
				<?php $is_first = true; foreach ($all_values as $r) { ?>
					<input name="field-id" type="radio" id="field-id-<?php echo $r['id']?>" value="<?php echo $r['id']?>" <?php echo $is_first ? 'checked="checked"' : ''?> /><label for="field-id-<?php echo $r['id']?>"><?php echo htmlspecialchars($r['visible_value_short'] ? $r['visible_value_short'] : $r['visible_value'])?></label>
				<?php $is_first = false; } ?>
				<input name="field-id" type="radio" id="field-id-0" value="0" <?php echo $is_first ? 'checked="checked"' : ''?> /><label for="field-id-0">ДОБАВИТЬ</label>
			</div>	
			<hr />	
			<form id="ca-update" method="POST" action="<?php echo $Module->formatURL('update').'&varname='.$varname.'&owner_id='.$Module->owner_id?>">
				<div id="remove-container"></div><div id="is-active-container"></div>
				
				<div id="records">
					<?php $is_first = true; foreach ($all_values as $r) { $rand_id = md5($r['id']); ?>
						<div rel="record" id="<?php echo $r['id']?>" <?php echo $is_first ? '' : 'style="display: none;"'?>>
							<?php foreach ($attribute['childs'] as $child_name=>$c) { $r['childs']['type'] = ''; ?>
								<?php echo active_field(array('name' => 'values['.$rand_id.']['.$child_name.']', 'value' => $r['childs'][$child_name], 'attributes' => $c)); ?>
							<?php } //foreach ?>
							
							<div class="input-row">
								<input rel="is-active" id="<?php echo $rand_id?>" type="checkbox" <?php if ($r['activated']): ?>checked="checked"<?php endif;?>/> 
								Активно. <small><i>(Текущее значение: <strong><?php if ($r['activated']): echo 'Да'; else: echo 'Нет'; endif;?></strong>)</i></small>
								
								<div class="record-info">
									<?php if ($r['posted']): ?>
										<p <?php echo ($r['is_confirmed']) ? '' : 'style="color: #CD0A0A; cursor: help;" title="Не подтверждено"'?>>Добавлено: <?php echo date('d.m.Y H:i:s', $r['posted'])?> (<?php echo $r['posted_username']?>)</p>
									<?php endif; ?>
									<?php if ($r['activated']): ?>
										<p>Активировано: <?php echo date('d.m.Y H:i:s', $r['activated'])?> (<?php echo $r['activated_username']?>)</p>
									<?php endif; ?>
									<?php if (isset($r['remove_request']) && $r['remove_request']): ?>
										<p style="color: #CD0A0A;">Запрошено удаление: <?php echo date('d.m.Y H:i:s', $r['remove_request'])?> (<?php echo $r['remove_request_username']?>)</p>
									<?php endif; ?>
								</div>
								
								<button id="remove-variant" class="nfw-button ui-state-error" icon="ui-icon-close">Удалить запись</button>
							</div>	
							<div class="delimiter"></div>	
						</div>
					<?php $is_first = false; } ?>
					<div rel="record" id="0" <?php echo $is_first ? '' : 'style="display: none;"'?>>
						<?php foreach ($attribute['childs'] as $child_name=>$c) { ?>
							<?php echo active_field(array('name' => 'values[0]['.$child_name.']', 'attributes' => $c, 'width' => '400px;')); ?>
						<?php } //foreach ?>
						<div class="input-row">
							<input rel="is-active" id="0" type="checkbox" checked="checked" /> Активно		
						</div>		
						<div class="delimiter"></div>
					</div>
				</div>
			</form>		
		</div>
		
		<?php if ($attribute['with_attachments']): ?>
			<div id="tabs-2"><?php echo $attachments_form?></div>
		<?php endif; ?>
		
		<div id="tabs-3">
<?php if (isset($history)): ?>
			<table class="main-table">
				<thead>
					<tr>
						<th>Значение</th>
						<th style="text-align: right;">Действие</th>
				    	<th>Время</th>
				    	<th>Логин</th>
				    	<th>IP</th>
				    </tr>
				</thead>
				<tbody>
<?php
	foreach ($history as $r) {
?>
	<tr class="zebra">
		<td style="width: 100%;"><?php echo ($r['visible_value']) ? nl2br(htmlspecialchars($r['visible_value'])) : '-'?></td>
		<td style="text-align: right;">
<?php 
		switch ($r['operation']) {
			case $Module->operations['CREATE']:
				echo 'Добавлено';
				break;
			case $Module->operations['ACTIVATE']:
				echo 'Активировано';
				break;
			case $Module->operations['REMOVE']:
				echo 'Удалено';
				break;
		}
?>
		</td>
		<td class="nw"><?php echo date('d.m.Y H:i:s', $r['posted'])?></td>
		<td class="nw"><?php echo htmlspecialchars($r['posted_username'])?></td>
		<td class="nw"><?php echo $r['poster_ip']?></td>
	</tr>
<?php  
	} 
?>		
	</tbody></table>
<?php else: ?>
	<p>История пуста.</p>
<?php endif;?>		
		</div>
	</div>
</div>

<script type="text/javascript">
$(document).ready(function(){
	var d = $('div[id="ca-update-dialog"]');
	d.dialog({ 
		autoOpen: true, draggable: false, modal: true, resizable: false,
		title: 'Редактировать "<?php echo (isset($attribute['longdesc']) && $attribute['longdesc']) ? $attribute['longdesc'] : $attribute['desc']?>"',
		width: 700, height: $(window).height() - 50,
		buttons: { 'Сохранить': function() {
			f.find('div[id="is-active-container"]').empty();
			f.find('input[rel="is-active"]').each(function(){
				if ($(this).prop('checked')) { 
					f.find('div[id="is-active-container"]').append('<input type="hidden" name="is_active[]" value="' + this.id + '" />');
				}
			});
			f.submit();
		}},
		close: function(event, ui) {
			d.dialog('destroy').remove();
		}
	});

	var f = d.find('form[id="ca-update"]');
	f.activeForm({
		error: function(response) {
			var errorMessage = '';
			$.each(response.errors, function(varname, message){
				errorMessage = errorMessage + message + "\n";
			});
			
			if (errorMessage != '') {
				alert(errorMessage);
			}	
			else {
				alert('Неизвестная ошибка при сохранении данных.');
			}
		},
		success: function(response) {
			if (response.is_updated) {
				window.location.reload();
			}
			d.dialog('close');
		}
	});

	$('input[name="field-id"]').change(function(){
		f.find('div[id="records"]').find('div[rel="record"]').hide();
		f.find('div[id="records"]').find('div[rel="record"][id="' + $(this).val() + '"]').show();
	});		
	
	f.find('button[id="remove-variant"]').click(function(){
		var id = $(this).closest('div[rel="record"]').attr('id');

		f.find('div[id="remove-container"]').append('<input type="hidden" name="remove[]" value="' + id + '" />');
		
		f.find('div[id="records"]').find('div[rel="record"][id="' + id + '"]').remove();
		$('input[id="field-id-' + id + '"]').remove();
		$('label[for="field-id-' + id + '"]').remove();

		$('input[name="field-id"]').removeAttr('checked');
		$('input[name="field-id"]:first').attr('checked', 'checked').trigger('click');
		return false;
	});

	// Toggle 'is_active'
	/* <?php if (!$attribute['multiple']): ?> */
	$(document).off('click', f.find('input[rel="is-active"]')).on('click', f.find('input[rel="is-active"]'), function(){
		if ($(this).prop('checked')) {
			// Remove all others
			f.find('input[rel="is-active"][id!="' + $(this).attr('id') + '"]').removeAttr('checked');
		}
	});
	/* <?php endif; ?> */
		
	// Tabs create	
	$('div[id="additional-update-tabs"]').tabs({		
		ajaxOptions: { async: false }
	}).show();

	// Combine tabs with dialog
	$('div[id="additional-update-tabs"]').css('border', 'none'); 
	$('div[id="additional-update-tabs"]').find('.ui-tabs-nav').css({
		'background': 'none',
		'border-top': 'none', 'border-left': 'none', 'border-right': 'none',
		'border-radius': 0
	});

	// After tabs!
	$('div[id="field-id"]').buttonset();
	
	$(document).trigger('refresh');
});
</script>