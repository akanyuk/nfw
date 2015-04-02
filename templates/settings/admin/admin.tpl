<?php 
NFW::i()->registerResource('jquery.activeForm');
NFW::i()->registerResource('ckeditor');
NFW::i()->registerResource('jquery.cookie');
?>
<script type="text/javascript">
$(document).ready(function(){
	$('div[id="requests-settings-tabs"]').tabs({
		'cache': true,
		'cookie': { name: 'ui-tabs-settings-admin', expires: 30 }}).show();

	$(document).trigger('refresh');
});
</script>

<div id="requests-settings-tabs" style="display: none;">
	<ul>
		<?php foreach ($records as $r) { ?>
			<li><a href="<?php echo $Module->formatURL('update').'&varname='.$r['varname']?>"><?php echo htmlspecialchars($r['name'])?></a></li>
		<?php } ?>
	</ul>
</div>