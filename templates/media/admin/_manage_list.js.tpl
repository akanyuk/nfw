<?php
	ob_start(); 
?>
{ "aaData": [
	<?php $records_counter = 0; 
		foreach ($records as $a) { ?>[
		["<?php echo $a['url']?>", "<?php echo $a['icons']['16x16']?>", <?php echo $a['type'] == 'image' ? '1' : '0' ?>, "<?php echo $a['filesize_str']?>"],
		"<?php echo $a['basename']?>",
		<?php echo json_encode($a['comment'])?>,
		<?php echo $a['filesize']?>,
		<?php echo $a['posted']?>,
		<?php echo json_encode($a['posted_username'])?>,
		<?php echo $a['id']?>
	]<?php if ($records_counter++ < count($records) - 1) echo ','; }?>
]}
<?php
	echo preg_replace('!\s+!u', ' ', ob_get_clean()); 