<html><head><title><?=NFW::i()->lang['Authorization']?></title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<style>
	HTML,BODY { margin:0; }
	HTML,BODY, TD, TH { color: #444; font: 9pt Verdana, Arial, Helvetica, sans-serif; text-align: left; }
	TABLE { border: none; width: 100%; }
	H1 { font-weight: bold; font-size: 10pt; padding: 0; margin: 0; }
	.error { color: red; font-weight: bold; font-size: 8pt; }
</style>
</head>
<body leftmargin="0" topmargin="0" marginwidth="0" marginheight="0">
	<form method="POST">		
	    <table cellspacing="0" cellpadding="2">
	    	<tr><td colspan="2" style="padding: 10px;"><h1><?=NFW::i()->lang['Authorization_desc']?></h1></td></tr>
	    	<?php  if (isset($error) && $error) : ?>
	    		<tr><td class="error" colspan="2" style="padding: 10px;"><?php echo htmlspecialchars($error); ?></td></tr>
	    	<?php endif; ?>
	    	<tr><td style="white-space: nowrap; padding-right: 10px; padding-left: 20px; vertical-align: middle; text-align: right"><b><?=NFW::i()->lang['Login']?>:</b></td>
	        <td style="width: 100%"><input type="text" name="username" maxlength="64" style="width: 100px" /></td></tr>
	
	        <tr><td style="white-space: nowrap; padding-right: 10px; padding-left: 20px; vertical-align: middle; text-align: right;"><b><?=NFW::i()->lang['Password']?>:</b></td>
	        <td style="width: 100%"><input type="password" name="password" maxlength="64" style="width: 100px" /></td></tr>

	        <tr><td>&nbsp;</td><td style="width: 100%"><input type="submit" name="login" value=" Войти " style="font-size: 10px; padding: 1px;" /></td></tr>
	        <tr><td colspan="2" style="padding: 10px;"><a href="javascript:history.go(-1)"><?=NFW::i()->lang['Go_Back']?></a></td></tr>
		</table>        
    </form>
</body></html>	