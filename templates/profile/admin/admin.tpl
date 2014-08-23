<?php
NFW::i()->registerResource('jquery.activeForm');
NFW::i()->registerFunction('ui_message');
?>
<script type="text/javascript">
$(document).ready(function(){
	// Action 'update'
	var f = $('form[id="profile-update"]');
	f.activeForm({
		'success': function(response){
			if (response.is_updated) {
				alert('Профиль сохранен');
			}
		}
	});

	$(document).trigger('refresh');
});
</script>

<form id="profile-update">
	<fieldset>
	    <legend>Параметры профиля</legend>

    	<label for="login">Логин</label>
    	<div class="input-row"><?php echo htmlspecialchars(NFW::i()->user['username'])?></div>
        <div class="delimiter"></div>

    	<label for="login">E-mail</label>
    	<div class="input-row"><?php echo NFW::i()->user['email']?></div>
        <div class="delimiter"></div>

		<div style="padding-bottom: 1em;"></div>

		<?php echo active_field(array('name' => 'realname', 'value' => NFW::i()->user['realname'], 'attributes'=>$Module->attributes['realname'], 'width'=>"400px;"))?>
		<?php echo active_field(array('name' => 'language', 'value' => NFW::i()->user['language'], 'attributes'=>$Module->attributes['language']))?>
		<?php echo active_field(array('name' => 'country', 'value' => NFW::i()->user['country'], 'attributes'=>$Module->attributes['country']))?>
		<?php echo active_field(array('name' => 'city', 'value' => NFW::i()->user['city'], 'attributes'=>$Module->attributes['city'], 'width'=>"400px;"))?>

		<?php echo active_field(array('name' => 'password', 'type' => 'password', 'desc'=>'Новый пароль', 'maxlength' => '32', 'width' => '200px;'))?>
		<?php echo active_field(array('name' => 'password2', 'type' => 'password', 'desc'=>'Повторите ввод', 'maxlength' => '32', 'width' => '200px;'))?>

	    <div class="input-row" style="width: 400px;">
	    	<?php echo ui_message(array('icon' => 'info', 'text' => 'Если Вы не хотите менять пароль, оставьте оба поля пустым.'))?>
       	</div>

	    <div class="input-row">
	    	<button type="submit" class="nfw-button" icon="ui-icon-disk"><?php echo $Module->lang['Save']?></button>
        </div>
	</fieldset>
</form>