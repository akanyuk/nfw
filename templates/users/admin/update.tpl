<?php
NFW::i()->registerResource('dataTables');	// permissions tab
NFW::i()->registerResource('jquery.activeForm');
NFW::i()->registerResource('jquery.cookie');
?>
<script type="text/javascript">
$(document).ready(function(){

	// Action 'update'
	var f = $('form[id="users-update"]');
	f.activeForm({
		'success': function(response){
			alert("<?php echo $Module->lang['Msg_Saved']?>");
		}
	});

	// Action 'update_password'
	var up_form = $('form[id="update-password"]');
	var up_dialog = $('div[id="update-password-dialog"]');
	up_dialog.dialog({
		autoOpen: false, draggable:true, modal:true, resizable:false,
		title: "<?php echo $Module->lang['Update_password']?>",
		width: 'auto',height: 'auto',
		buttons: { '<?php echo $Module->lang['Save']?>': function() {
			up_form.submit();
		}}
	});

	up_form.activeForm({
		'success': function(){
			up_dialog.dialog('close');
			alert("<?php echo $Module->lang['Msg_Pass_updated']?>");
			return false;
		}
	});

	$('button[id="update-password"]').click(function(){
		up_form.resetForm().trigger('cleanErrors');
		up_form.find('input[name="password"]').val(randomString(8));
		up_dialog.dialog('open');
		return false;
	});


	// Action 'delete'
	$('a[id="delete"]').click(function(){
		if (!confirm("<?php echo $Module->lang['Msg_Confirm_delete']?>")) return false;

		$.post('<?php echo $Module->formatURL("delete")?>', { record_id: '<?php echo $Module->record['id']?>' }, function(){
			window.location.href = '<?php echo $Module->formatURL("admin")?>';
		});
	});

	// Tabs create
	$('div[id="users-update-tabs"]').tabs({
		cookie: { name: 'ui-tabs-users-update', expires: 30 },	// После обновления прав возвращаемся на ту же вкладку
		cache: true,
		ajaxOptions: { async: false }
	}).show();

	$(document).trigger('refresh');
});
</script>

<div id="update-password-dialog" style="display: none;">
	<form id="update-password" action="<?php echo $Module->formatURL('update_password')?>">
		<input type="hidden" name="record_id" value="<?php echo $Module->record['id']?>" />
		<?php echo active_field(array('name' => 'password', 'desc' => $Module->lang['New_password'], 'required'=>true, 'maxlength' => '32', 'width'=>"200px;"))?>
	</form>
</div>

<div id="users-update-tabs" style="display: none;">
	<?php if (NFW::i()->checkPermissions('users', 'delete')): ?>
		<div class="ui-state-error ui-corner-all" style="float: right; margin-right: 0.5em; margin-top: 0.2em; padding-right: 1px;">
			<a id="delete" href="#" class="ui-icon ui-icon-close nfw-tooltip" title="<?php echo $Module->lang['Delete']?>"></a>
		</div>
	<?php endif; ?>

	<div style="float: right; padding-right: 1em; padding-top: 0.2em;">
		<p style="font-size: 85%; text-align: right;"><?php echo $Module->lang['Registered']?>: <br /><?php echo date('d.m.Y H:i:s', $Module->record['registered']).' ('.$Module->record['registration_ip'].')'?></p>
	</div>

	<ul>
		<li><a href="#tabs-1"><?php echo $Module->lang['Profile']?></a></li>
		<?php if (NFW::i()->checkPermissions('permissions', 'update')): ?>
			<li><a href="<?php echo NFW::i()->base_path.'admin/permissions?action=update&user_id='.$Module->record['id'];?>"><?php echo $Module->lang['Permissions']?></a></li>
		<?php endif; ?>
	</ul>

	<div id="tabs-1">
		<form id="users-update">
			<?php echo active_field(array('name' => 'username', 'value' => $Module->record['username'], 'attributes'=>$Module->attributes['username'], 'width'=>"400px;"))?>
			<?php echo active_field(array('name' => 'email', 'value' => $Module->record['email'], 'attributes'=>$Module->attributes['email'], 'width'=>"400px;"))?>
			<?php echo active_field(array('name' => 'realname', 'value' => $Module->record['realname'], 'attributes'=>$Module->attributes['realname'], 'width'=>"400px;"))?>
			<?php echo active_field(array('name' => 'language', 'value' => $Module->record['language'], 'attributes'=>$Module->attributes['language']))?>
			<?php echo active_field(array('name' => 'country', 'value' => $Module->record['country'], 'attributes'=>$Module->attributes['country']))?>
			<?php echo active_field(array('name' => 'city', 'value' => $Module->record['city'], 'attributes'=>$Module->attributes['city'], 'width'=>"400px;"))?>
			<?php echo active_field(array('name' => 'group_id', 'value' => $Module->record['group_id'], 'attributes'=>$Module->attributes['group_id']))?>
			<?php echo active_field(array('name' => 'is_blocked', 'value' => $Module->record['is_blocked'], 'attributes'=>$Module->attributes['is_blocked']))?>

        	<label></label>
        	<div class="input-row">
        		<button type="submit" class="nfw-button" data-icon="ui-icon-disk"><?php echo $Module->lang['Save']?></button>
        		<?php if (NFW::i()->checkPermissions('users', 'update_password')): ?>
	        		<button id="update-password" class="nfw-button"><?php echo $Module->lang['Update_password']?></button>
        		<?php endif; ?>
			</div>
			<div class="delimiter"></div>
		</form>
	</div>
</div>