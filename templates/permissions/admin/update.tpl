<script type="text/javascript">
$(document).ready(function(){
	
	// Update roles
	$('form[id="update-roles"]').activeForm({
		'success': function(){
			window.location.reload();
		}
	});
	
	$(document).trigger('after-ready');
});
</script>

<form id="update-roles" action="<?php echo $Module->formatURL('update').'&user_id='.$user['id']?>">
	<div style="padding-bottom: 1em;">
		<button type="submit" class="nfw-button" data-icon="ui-icon-disk" title="Сохранить изменения прав пользователя">Сохранить изменения</button>
	</div>

	<table class="main-table">	
		<thead>
			<tr>
				<th></th>			
				<th>Роль</th>
				<th style="width: 100%;">Список прав роли</th>
			</tr>
		</thead>
		<tbody>
		<?php foreach ($all_roles as $role_name=>$role) { ?>
			<tr class="zebra">
				<td><input type="checkbox" name="roles[<?php echo $role_name?>]" <?php if (in_array($role_name, $user_roles)) echo 'checked="checked"' ?>/></td>
				<td<?php if (in_array($role_name, $user_roles)) echo ' style="font-weight: bold;"' ?>><?php echo $role_name?></td>
				<td>
					<?php foreach ($role as $r) { ?>
	            		<div><?php echo htmlspecialchars($r['description'])?></div>
	            	<?php } ?>
				</td>
			</tr>
		<?php } ?>
		</tbody>
	</table>
</form>