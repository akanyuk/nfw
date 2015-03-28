<?php
NFW::i()->registerResource('jquery.activeForm');
NFW::i()->registerFunction('ui_message');
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" " http://www.w3.org/TR/html4/strict.dtd">
<html><head>
<title><?php echo NFW::i()->lang['Authorization']?></title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<style>
	HTML,BODY { margin:1em; color: #444; font: 9pt Verdana, Arial, Helvetica, sans-serif; text-align: left; }
	P { margin: 0 !important; }
	
	FORM { width: 400px; }
	.error-info { min-height: 0.5em !important; }
	
	.error-message { font-size: 90%; min-height: 2em; color: #f00; overflow: hidden; }
</style>
<script type="text/javascript">
$(document).ready(function(){
	var f = $('form[id="login"]');
	f.activeForm({
		error: function(response) {
			f.find('div[id="error-message"]').text(response.message);
		},
		success: function(response) {
			if (response.redirect) {
				window.location.href = response.redirect;
			}
			else {
				window.location.reload();
			}
		}
	}).bind('cleanErrors', function(){
		f.find('div[id="error-message"]').empty();
	});
	f.find('input').uniform();
	f.find('button').button();
});
</script>
</head>
<body>
	<form id="login">
		<fieldset>
			<legend><?php echo NFW::i()->lang['Authorization']?></legend>
			<?php echo ui_message(array('text' => NFW::i()->lang['Authorization_desc']))?>
			<?php echo active_field(array('name' => 'username', 'desc'=> NFW::i()->lang['Login'], 'width'=>"200px;"))?>
			<?php echo active_field(array('name' => 'password', 'type' => 'password', 'desc'=> NFW::i()->lang['Password'], 'width'=>"200px;"))?>
			<div class="input-row">
				<div id="error-message" class="error-message"></div>
				<button name="login" type="submit"><?php echo NFW::i()->lang['GoIn']?></button>
			</div>
		</fieldset>	
    </form>
</body></html>	