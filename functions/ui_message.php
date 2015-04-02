<?php
/*
 * 'ui_message' function plugin
 *
 * Type:     function
 * Purpose:  Generate jQuiery UI widget with message
 */


/* Usage:
 *
 *     1.   ui_message("Text")
 *     2.   ui_message(array('state'=>"error", 'icon'=>"alert", 'text'=>"Text")
 *
 */

function ui_message($params) {
	if (!is_array($params)) {
		$params = array('text' => $params);
	}
	
	$icon = (isset($params['icon'])) ? '<span style="float: left; margin-right: 0.3em;" class="ui-icon ui-icon-'.$params['icon'].'"></span>' : ''; 
	$state = (isset($params['state'])) ? $params['state'] : 'highlight';
	ob_start();
?>
<div class="ui-widget">
	<div style="padding: 0.5em; margin-bottom: 0.5em;" class="ui-state-<?php echo $state;?> ui-corner-all"> 
		<p>
			<?php echo $icon;?> 
			<?php echo $params['text'];?>
		</p>
	</div>
</div>

<?php	
	$result = ob_get_contents();
	ob_end_clean();
	return $result;
}