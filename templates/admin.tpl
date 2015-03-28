<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" " http://www.w3.org/TR/html4/strict.dtd"> 
<html lang="<?php echo NFW::i()->lang['lang']?>"><head><title><?php echo NFW::i()->cfg['admin']['title']?></title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<script type="text/javascript">
$(document).ready(function(){
	$('div[id="main-menu"]').accordion({
		heightStyle: "content",
		active: $('.main-menu-item-active').length ? parseInt($('.main-menu-item-active').parent().attr('id')) : 0
	});
});
</script>
<style>
	/* Main header */
	.main-header { background-color:#000; color: #fff; padding: 6px 10px; margin-bottom: 1em; }
	.main-header A { color: #ff0; }
	.main-header A:hover { color: #ffa; }
	
	.main-menu { margin-left: 1em; }
	.main-menu .ui-accordion-content { padding: 0.5em; }
	.main-menu H3 { font-size: 80%; }
	.main-menu-item { text-align: center; margin-bottom: 1em; padding: 0.5em; font: 7pt Tahoma, Verdana, Arial, Helvetica, sans-serif; }
	.main-menu-item-active { background-color: #ffe; border: 1px dotted #aaa; }

	.main-menu A { color: #222; }
	.main-menu A:hover { color: #555; }
	
	.admin-index .record { padding: 0.5em 1em; margin-top: 1em; max-width: 900px; }
	.admin-index .record:nth-child(odd) { background-color: #f8f8f8; }
	.admin-index p { font-size: 110%; padding-top: 0.3em; }
	.admin-index .icon { float: left; }
	.admin-index .icon IMG { width: 48px; height: 48px; -webkit-filter: grayscale(80%); filter: grayscale(80%); }
	.admin-index .inner { margin-left: 60px; }
	.admin-index .d { clear: both; }	
</style>
</head>
<body>

<div class="main-header">
	<div style="float: right;"><small><?=NFW::i()->lang['LoggedAs']?> <a href="<?=NFW::i()->base_path?>admin/profile"><b><?=htmlspecialchars(NFW::i()->user['username'])?></b></a>. [<a href="?action=logout"><?=NFW::i()->lang['Logout']?></a>]</small></div>
	<small><a href="<?=NFW::i()->base_path?>"><?php echo $_SERVER['HTTP_HOST']?></a> / <a href="<?=NFW::i()->base_path?>admin">admin</a></small>
</div> 

<div style="position: absolute; width: 100px;">
	<div id="main-menu" class="main-menu">
	<?php 
		$main_menu_cnt = 0;
		foreach ($admin_menu as $cat_name=>$c) {
			echo '<h3><a href="#">'.(($cat_name) ? $cat_name : '').'</a></h3>'."\n";			
		    echo '<div id="'.($main_menu_cnt++).'">'."\n";
			foreach ($c as $i) {
			    echo "\t".'<div class="main-menu-item'.($i['is_active'] ? ' main-menu-item-active' : '').'">'."\n";
			    echo "\t\t".'<a href="'.$i['url'].'">'."\n";
			    if ($i['icon']) {
					echo "\t\t\t".'<img src="'.NFW::i()->assets($i['icon']).'" />'."\n";
				}
				echo "\t\t\t".'<div>'.$i['name'].'</div>'."\n";
			    echo "\t\t".'</a>'."\n";
			    echo "\t".'</div>'."\n";
			}
			echo '</div>'."\n";
		}
	?>
	</div>
</div>
<div style="margin-left: 110px; padding-right: 1em;">
<?php if (isset($content) && $content) : echo ($content); else: 
		$lang_admin = NFW::i()->getLang('admin');
?>
<div class="admin-index">
<h1><?php echo $lang_admin['welcome']?></h1>
<p><?php echo $lang_admin['welcome desc']?></p>
<?php 
	foreach ($admin_menu as $cat_name=>$c) {
		foreach ($c as $i) {
			echo '<div class="record">';
			if ($i['icon']) {
				echo '<div class="icon"><a href="'.$i['url'].'"><img src="'.NFW::i()->assets($i['icon']).'" /></a></div>'."\n";
			}

			echo '<div class="inner"><h3><a href="'.$i['url'].'">'.$i['name'].'</a></h3>'."\n";
			echo isset($i['desc']) ? '<p>'.$i['desc'].'</p>'."\n" : '';
			echo '</div><div class="d"></div></div>'."\n";
		}
	}
?>
</div>
<?php endif; ?>
</div>
</body></html>