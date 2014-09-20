<?php
/*
 * 'additional_field' function plugin
 *
 * Purpose: Универсальная функция отображения поля модуля 'additional'   
 */
function additional_field($Module, $varname, $editable = true) {
	$value = $Module->active_values[$varname];
	$label = (isset($Module->attributes[$varname]['longdesc']) && $Module->attributes[$varname]['longdesc']) ? $Module->attributes[$varname]['longdesc'] : $Module->attributes[$varname]['desc'];

	if ($Module->attributes[$varname]['multiple']): ?>
		<?php if (isset($Module->active_values['attachments'][$varname])): ?>
			<div class="attachments">
				<?php foreach ($Module->active_values['attachments'][$varname] as $a) { ?>
					<div><a target="_blank" href="<?php echo $a['url']?>" title="<?php echo ($a['comment'] ? htmlspecialchars($a['comment']) : htmlspecialchars($a['filename'])).' ('.$a['filesize_str'].')'?>"><img src="<?php echo $a['icons']['16x16']?>" width="16" height="16" alt=""/></a></div>
				<?php } ?>
			</div>
		<?php endif; ?>
		<dt>
			<?php if (NFW::i()->checkPermissions(get_class($Module), 'update') && $editable) { ?>
				<a href="#" rel="update" varname="<?php echo $varname?>" title="Дополнительно..."><?php echo $label?></a>
			<?php } else echo $label; ?>
		</dt>
		<?php $is_first_icon = true; foreach ($value as $v) { ?>
			<?php if (isset($Module->unconfirmed[$varname])): ?>
				<span class="nfw-tooltip ui-icon ui-icon-alert ui-icon-state-error" style="<?php echo $is_first_icon ? '' : ' margin-left:160px;'?>" title="Изменено клиентом"></span>
			<?php else: ?>
				<span class="nfw-tooltip ui-icon ui-icon-check ui-icon-state-success" style="<?php echo $is_first_icon ? '' : ' margin-left:160px;'?>" title="Активно"></span>
			<?php endif; ?>
			<dd><?php echo ($v['visible_value'] ? nl2br(htmlspecialchars($v['visible_value'])) : '&nbsp;')?></dd>
		<?php $is_first_icon = false; } ?>
	<?php else: ?>
		<?php if (isset($Module->active_values['attachments'][$varname])): ?>
			<div class="attachments">
				<?php foreach ($Module->active_values['attachments'][$varname] as $a) { ?>
					<div><a target="_blank" href="<?php echo $a['url']?>" title="<?php echo ($a['comment'] ? htmlspecialchars($a['comment']) : htmlspecialchars($a['filename'])).' ('.$a['filesize_str'].')'?>"><img src="<?php echo $a['icons']['16x16']?>" width="16" height="16" alt=""/></a></div>
				<?php } ?>
			</div>
		<?php endif; ?>
		<dt>
			<?php if (NFW::i()->checkPermissions(get_class($Module), 'update') && $editable) { ?>
				<a href="#" rel="update" varname="<?php echo $varname?>" title="Дополнительно..."><?php echo $label?></a>
			<?php } else echo $label; ?>
		</dt>
		<?php if (isset($Module->unconfirmed[$varname])): ?>
			<span class="nfw-tooltip ui-icon ui-icon-alert ui-icon-state-error" title="Изменено клиентом"></span>
		<?php elseif ($value['id']): ?>
			<span class="nfw-tooltip ui-icon ui-icon-check ui-icon-state-success" title="Активно"></span>
		<?php endif; ?>
		<dd><?php echo isset($value['visible_value']) && $value['visible_value'] ? nl2br(htmlspecialchars($value['visible_value'])) : '&nbsp;';?></dd>
	<?php endif;
}