<?php
	$default_value = isset($attribute['default']) ? $attribute['default'] : '';
?>
<style>
	.ca-ud DIV.record .icon1 { float: left; width: 24px; }
	.ca-ud DIV.record .icon1 .checker { top: 1px; }
	.ca-ud DIV.record .v { float: left; }
	.ca-ud DIV.record .d { clear: both; padding-bottom: 0.5em; }
	.ca-ud DIV.record .d P { padding-left: 24px; } 
</style>

<div id="ca-<?php echo $varname?>-template" style="display: none;">
	<div id="record" class="record" rel="new">
		<div class="icon1">
			<input rel="is-active" type="checkbox" <?php if ($attribute['multiple']) echo 'checked="checked"'?> id="newvalue-%INDEX%" title="Активно" />				
		</div>
		<div class="v">
			<?php if ($attribute['type'] == 'date'): ?>
				<input rel="new-datepicker" type="text" name="values[newvalue-%INDEX%]" class="datepicker" maxlength="10" <?php echo $default_value ? 'value="'.$default_value.'"' : ''?> />
			<?php elseif ($attribute['type'] == 'select'): ?>
	   			<select name="values[newvalue-%INDEX%]" class="uniform"><?php foreach ($attribute['options'] as $option) {
	   				echo '<option value="'.$option['id'].'"'.($option['id'] == $default_value ? ' selected="selected"' : '').'>'.$option['desc'].'</option>'; 
	   			 } ?></select>		
			<?php elseif ($attribute['type'] == 'textarea'): ?>
				<textarea name="values[newvalue-%INDEX%]" class="uniform" style="width: 440px; height: 50px;"><?php echo $default_value?></textarea>
			<?php elseif ($attribute['type'] == 'combobox'): ?>
				<input rel="combobox" type="text" name="values[newvalue-%INDEX%]"  value="<?php echo $default_value?>" style="width: 440px;"/>
				<?php foreach ($attribute['options'] as $o) echo '<div rel="options" style="display: none;">'.htmlspecialchars($o).'</div>'?>
			<?php elseif ($attribute['type'] == 'float'): ?>
				<input type="text" name="values[newvalue-%INDEX%]" value="<?php echo $default_value?>" class="uniform" style="width: 220px;"/>
			<?php else: ?>
				<input type="text" name="values[newvalue-%INDEX%]" value="<?php echo $default_value?>" class="uniform" style="width: 440px;"/>
			<?php endif; ?>
		</div>
		<div class="v"><button rel="remove" id="newvalue-%INDEX%" class="nfw-button nfw-button-small" icon="ui-icon-trash"></button></div>
		<div class="d"><p>Не сохранено</p></div>
	</div>		
</div>
	
<div id="ca-update-dialog" class="ca-ud">
	<div id="additional-update-tabs" style="display: none;">
		<ul>
			<li><a href="#tabs-1">Значение</a></li>
			<?php if ($attribute['with_attachments']): ?><li><a href="#tabs-2">Вложения</a></li><?php endif; ?>
			<li><a href="#tabs-3">История изменений</a></li>
		</ul>
	
		<div id="tabs-1">
			<form id="ca-update" method="POST" action="<?php echo $Module->formatURL('update').'&varname='.$varname.'&owner_id='.$Module->owner_id?>">
				<div id="remove-container"></div><div id="is-active-container"></div>
				<div id="records"><?php foreach ($all_values as $r) echo additional_active_field($r, $attribute); ?></div>
				<div style="padding-left: 24px;">
					<button id="add-variant" class="nfw-button" icon="ui-icon-plus">Добавить вариант</button>
				</div>
			</form>		
		</div>
		
		<?php if ($attribute['with_attachments']): ?>
			<div id="tabs-2"><?php echo $attachments_form?></div>
		<?php endif; ?>
		
		<div id="tabs-3">
<?php if (!empty($history)): ?>
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
		autoOpen: true, draggable:true, modal:true, resizable:false,
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

	var f = d.find('form[id="ca-update"]').activeForm({
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

	f.find('button[id="add-variant"]').click(function(){
		var seed = randomString(16);
		var html = $('div[id="ca-<?php echo $varname?>-template"]').html().replace(/%INDEX%/g, seed);
		var isActive = (f.find('div[id="records"]').find('input[rel="is-active"]').length) ? false : true; // Первую запись всегда делаем активной 

		f.find('div[id="records"]').append(html);
		if (isActive) {
			f.find('div[id="records"]').find('input[rel="is-active"]').attr('checked', 'checked');
		}
		 
		<?php if ($attribute['type'] == 'date'): ?>
		var i = f.find('input[rel="new-datepicker"]');
		f.trigger('setDatepicker', [i]);
		i.removeAttr('rel');
		<?php elseif ($attribute['type'] == 'combobox'): ?>
			f.trigger('updateComboboxes');
		<?php endif; ?>

		return false;
	});

	$(document).off('click', 'button[rel="remove"]').on('click', 'button[rel="remove"]', function(){
		var id = $(this).attr('id');

		if (!$(this).closest('div[id="record"][rel="new"]').length) {
			f.find('div[id="remove-container"]').append('<input type="hidden" name="remove[]" value="' + id + '" />');
		}
		
		$(this).closest('div[id="record"]').remove();
		return false;
	});

	<?php if ($attribute['type'] == 'combobox'): ?>
	f.bind('updateComboboxes', function(){
		$(this).find('input[rel="combobox"]').each(function(){
			var o = $(this); 
			var tags = [];
			o.siblings('div[rel="options"]').each(function(){
				tags.push($(this).text());		
			});

			o.autocomplete({
				source: tags,
				minLength: 0
			}).click(function(){
				o.autocomplete('search', '');
			}).removeAttr('rel');

			$('<div id="toggle" style="width: 15px; height: 15px;"></div>').button({ icons: { primary: "ui-icon-triangle-1-s" }, text: false }).removeClass('ui-corner-all').addClass('ui-corner-right ui-button-icon').click(function(){
				o.autocomplete('search', '');
			}).insertAfter(o);
		});
	}).trigger('updateComboboxes');	
	<?php endif; ?>

	<?php if (!$attribute['multiple']): // Toggle 'is_active' ?>
	$(document).off('click', 'input[rel="is-active"]').on('click', 'input[rel="is-active"]', function(){
		if ($(this).prop('checked')) {
			// Remove all others
			f.find('input[rel="is-active"][id!="' + $(this).attr('id') + '"]').removeAttr('checked');
		}
	});
	<?php endif; ?>
	
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

	$(document).trigger('refresh');
});
</script>
<?php 

function additional_active_field($r, $attribute) {
	$rand_id = md5($r['id']);
	
	ob_start();
?>
<div id="record" class="record">
	<div class="icon1">
		<input rel="is-active" type="checkbox" class="uniformed" id="<?php echo $rand_id?>" <?php if ($r['activated']): ?>checked="checked"<?php endif;?> title="Активно" />				
	</div>
	<div class="v" <?php echo $attribute['type'] == 'combobox' ? 'style="position: relative; top: -4px;"' : ''?>>
		<?php if ($attribute['type'] == 'date'): ?>
			<input type="text" name="values[<?php echo $rand_id?>]" value="<?php echo $r['value']?>" class="datepicker" maxlength="10" />
		<?php elseif ($attribute['type'] == 'select'): ?>
   			<select name="values[<?php echo $rand_id?>]">
				<?php foreach ($attribute['options'] as $option) { ?>
					<option value="<?php echo $option['id']?>" <?php if ($option['id'] == $r['value']) echo 'selected="selected"'; ?>><?php echo $option['desc']?></option>
				<?php } ?>
			</select>
		<?php elseif ($attribute['type'] == 'textarea'): ?>
			<textarea name="values[<?php echo $rand_id?>]" style="width: 440px; height: 50px;"><?php echo htmlspecialchars($r['value'])?></textarea>
		<?php elseif ($attribute['type'] == 'combobox'): ?>
			<input rel="combobox" type="text" name="values[<?php echo $rand_id?>]" value="<?php echo htmlspecialchars($r['value'])?>" style="width: 440px;"/>
			<?php foreach ($attribute['options'] as $o) echo '<div rel="options" style="display: none;">'.htmlspecialchars($o).'</div>'?>
		<?php elseif ($attribute['type'] == 'float'): ?>
			<input type="text" name="values[<?php echo $rand_id?>]" value="<?php echo htmlspecialchars($r['value'])?>" style="width: 220px;"/>
		<?php else: ?>
			<input type="text" name="values[<?php echo $rand_id?>]" value="<?php echo htmlspecialchars($r['value'])?>" style="width: 440px;"/>
		<?php endif; ?>
	</div>
	<div class="v"><button rel="remove" id="<?php echo $r['id']?>" class="nfw-button nfw-button-small" icon="ui-icon-trash"></button></div>
	<div class="d">
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
</div>	
<?php 
	return ob_get_clean();
}