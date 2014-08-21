<?php
/*
 * 'additional_field' function plugin
 *
 * Purpose: Универсальная функция отображения поля модуля 'additional'   
 */
function additional_field($Module, $varname, $editable = true) {
	$value = $Module->active_values[$varname];
	$label = (isset($Module->attributes[$varname]['longdesc']) && $Module->attributes[$varname]['longdesc']) ? $Module->attributes[$varname]['longdesc'] : $Module->attributes[$varname]['desc'];

	if ($varname == "Komment" || $varname == "activity_caregories"): ?>
		<?php if (!empty($value)) foreach ($value as $key=>$v) { ?>
			<div style="padding: 0.3em 1em; <?php if ($key%2) echo 'background-color: #f4f4f4;'?><?php if ($key < count($value) - 1) echo 'border-bottom: 1px dotted #aaa;'?>">
				<?php echo (($v['visible_value']) ? nl2br(htmlspecialchars($v['visible_value'])) : '&nbsp;')?>
			</div>
		<?php } ?>
			
		<div class="attachments" style="padding-top: 0.5em;">
			<?php if (isset($Module->active_values['attachments'][$varname])) foreach ($Module->active_values['attachments'][$varname] as $a) { ?>
				<div><a target="_blank" href="<?php echo $a['url']?>" title="<?php echo ($a['comment'] ? htmlspecialchars($a['comment']) : htmlspecialchars($a['filename'])).' ('.$a['filesize_str'].')'?>"><img src="<?php echo $a['icons']['16x16']?>" width="16" height="16" alt=""/></a></div>
			<?php } ?>
		</div>
		<?php if (NFW::i()->checkPermissions(get_class($Module), 'update') && $editable): ?>
			<div style="padding-top: 0.5em;">
				<a href="#" rel="update" varname="<?php echo $varname?>" class="nfw-button nfw-button-small">Редактировать</a>
				</div>
		<?php endif; ?>
		<div style="clear: both;"></div>
	<?php elseif ($varname == "bank"): ?>
		<div class="record">
			<?php foreach ($value as $key=>$v) { ?>
				<div style="padding: 5px 0; <?php if ($key%2) echo 'background-color: #f4f4f4;'?><?php if ($key < count($value) - 1) echo 'border-bottom: 1px dotted #aaa;'?>">
					<label>Название банка</label>
					<div class="v2"><?php echo htmlspecialchars($v['childs']['name'])?></div>
					<div class="d"></div>
					
					<label>БИК</label>
					<div class="v2"><?php echo htmlspecialchars($v['childs']['bik'])?></div>
					<div class="d"></div>
					
					<label>Расчетный счет</label>
					<div class="v2"><?php echo htmlspecialchars($v['childs']['rs'])?></div>
					<div class="d"></div>
						
					<label>Корр. счет</label>
					<div class="v2"><?php echo htmlspecialchars($v['childs']['ks'])?></div>
					<div class="d"></div>
				</div>
				<div class="d"></div>
			<?php } ?>
				
			<?php if (isset($Module->active_values['attachments'][$varname])): ?>
				<div style="padding: 0.5em 0">
					<label>Вложения</label>
					<div class="v2">
						<?php foreach ($Module->active_values['attachments'][$varname] as $a) { ?>
							<div style="display: inline; padding-right: 0.3em;"><a target="_blank" href="<?php echo $a['url']?>" title="<?php echo ($a['comment'] ? htmlspecialchars($a['comment']) : htmlspecialchars($a['filename'])).' ('.$a['filesize_str'].')'?>"><img src="<?php echo $a['icons']['16x16']?>" width="16" height="16" alt=""/></a></div>
						<?php } ?>
					</div>
					<div class="d"></div>
				</div>
			<?php endif; ?>
			
			<?php if (NFW::i()->checkPermissions(get_class($Module), 'update') && $editable): ?>
				<a href="#" rel="update" varname="<?php echo $varname?>" class="nfw-button nfw-button-small">Редактировать банковские реквизиты</a>
			<?php endif; ?>
			<div style="clear: both;"></div>
		</div>	
	<?php elseif ($Module->attributes[$varname]['multiple']): ?>
		<div class="record">
			<?php if (isset($Module->active_values['attachments'][$varname])): ?>
				<div class="attachments">
					<?php foreach ($Module->active_values['attachments'][$varname] as $a) { ?>
						<div><a target="_blank" href="<?php echo $a['url']?>" title="<?php echo ($a['comment'] ? htmlspecialchars($a['comment']) : htmlspecialchars($a['filename'])).' ('.$a['filesize_str'].')'?>"><img src="<?php echo $a['icons']['16x16']?>" width="16" height="16" alt=""/></a></div>
					<?php } ?>
				</div>
			<?php endif; ?>
			<label>
				<?php if (NFW::i()->checkPermissions(get_class($Module), 'update') && $editable) { ?>
					<a href="#" rel="update" varname="<?php echo $varname?>" title="Дополнительно..."><?php echo $label?></a>
				<?php } else echo $label; ?>
			</label>
			<?php if (empty($value)): ?>
				<div class="icon1">&nbsp;</div>
				<div class="v">-</div>
				<div class="d"></div>
			<?php else: ?>
			<?php foreach ($value as $v) { ?>
				<div class="icon1">
					<?php if (isset($Module->unconfirmed[$varname])): ?>
						<span class="nfw-tooltip ui-icon ui-icon-alert ui-icon-red" title="Изменено клиентом"></span>
					<?php else: ?>
						<span class="nfw-tooltip ui-icon ui-icon-check ui-icon-green" title="Активно"></span>
					<?php endif; ?>
				</div>
				<div class="v">
					<?php echo (($v['visible_value']) ? nl2br(htmlspecialchars($v['visible_value'])) : '&nbsp;')?>
				</div>
				<div class="d"></div>
			<?php } ?>
			<?php endif; ?>
		</div>
	<?php else: ?>
		<div class="record">
			<?php if (isset($Module->active_values['attachments'][$varname])): ?>
				<div class="attachments">
					<?php foreach ($Module->active_values['attachments'][$varname] as $a) { ?>
						<div><a target="_blank" href="<?php echo $a['url']?>" title="<?php echo ($a['comment'] ? htmlspecialchars($a['comment']) : htmlspecialchars($a['filename'])).' ('.$a['filesize_str'].')'?>"><img src="<?php echo $a['icons']['16x16']?>" width="16" height="16" alt=""/></a></div>
					<?php } ?>
				</div>
			<?php endif; ?>
			<label>
				<?php if (NFW::i()->checkPermissions(get_class($Module), 'update') && $editable) { ?>
					<a href="#" rel="update" varname="<?php echo $varname?>" title="Дополнительно..."><?php echo $label?></a>
				<?php } else echo $label; ?>
			</label>
			<div class="icon1">
				<?php if (isset($Module->unconfirmed[$varname])): ?>
					<span class="nfw-tooltip ui-icon ui-icon-alert ui-icon-red" title="Изменено клиентом"></span>
				<?php elseif ($value['id']): ?>
					<span class="nfw-tooltip ui-icon ui-icon-check ui-icon-green" title="Активно"></span>
				<?php else: ?>
					&nbsp;
				<?php endif; ?>
			</div>
			<div class="v"><?php echo isset($value['visible_value']) && $value['visible_value'] ? nl2br(htmlspecialchars($value['visible_value'])) : '&nbsp;';?></div>
			<div class="d"></div>
		</div>
	<?php endif;
}