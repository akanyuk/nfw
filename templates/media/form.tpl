<?php
	NFW::i()->registerResource('jquery.activeForm');
	NFW::i()->registerResource('jquery.cookie');
	NFW::i()->registerFunction('ui_message');
	
	$lang_media = NFW::i()->getLang('media');
?>
<script type="text/javascript">
$(document).ready(function(){
	/** 
	 * Media Form
	 * @date: 2014.11.10
	 */

	// Set session cookie
	$.cookie('<?php echo $session_id?>', '<?php echo $cookie_data?>', { path: '/' });

	if ($.uniform) {
		$.uniform.defaults.fileDefaultHtml = '<?php echo $lang_media['fileDefaultHtml']?>';
		$.uniform.defaults.fileButtonHtml = '<?php echo $lang_media['fileButtonHtml']?>';
	}
	 
	 <?php if (isset($allow_reload) && $allow_reload): ?>
	 var reloadForm = $('form[id="reload-<?php echo $session_id?>"]');
	 reloadForm.ajaxForm({
		beforeSubmit: function(a,f,o) {
			 o.dataType = "json";
		},
		success: function(response) {
			if (response && response.result && response.result == 'error') {
				if (typeof(response.errors) == 'object') {
					$.each(response.errors, function(i, e) {
						if (i == 'general') {
							alert(e);
						}
					});
				}
			}
			else if (response && response.result && response.result == 'success') {
				form.resetForm().trigger('cleanErrors').trigger('load');
			}
		}
	});
	reloadForm.find('input[name="local_file"]').change(function() {
		form.trigger('save-comments');
		reloadForm.submit();
    });
	<?php endif; ?>
		 
	var form = $('form[id="<?php echo $session_id?>"]');
	
	form.activeForm({
		success: function(response) {
			form.resetForm().trigger('cleanErrors').trigger('load');
		}
	});

	form.find('input[name="local_file"]').change(function() {
		form.trigger('save-comments').submit();
    });

	if ($.uniform) {
		form.find('input[name="local_file"]').uniform().addClass('uniformed');
	}
	
	form.bind('load', function(){
		$.get("<?php echo NFW::i()->base_path.'media.php?action=list&owner_class='.$owner_class.'&owner_id='.$owner_id.(NFW::i()->getUI() ? '&ui='.NFW::i()->getUI() : '').'&ts='?>" + new Date().getTime(), function(response){
			if (response.iTotalRecords == 0) {
				form.find('*[id="media-list"]').hide();
				return false;
			}

			form.find('*[id="session_size"]').text(response.iSessionSize_str);
			
			var rowTemplate = '';
			if (typeof(response.sRowTemplate) != 'undefined' && response.sRowTemplate) {
				rowTemplate = response.sRowTemplate;
			}
			else {
				<?php if ($owner_id): ?>
				rowTemplate = '<tr class="zebra"><td style="white-space: nowrap;"><a href="%url%" target="_blank" type="%type%"><img src="%icon%"/> <span style="position: relative; top: -4px;">%basename%</span></a></td><td style="white-space: nowrap;">%filesize_str%</td><td style="white-space: nowrap;">%posted_str%</td><td><input id="%id%" rel="comment" type="text" style="width: 100%" value="%comment%" /></td><td style="white-space: nowrap;"><a rel="reload-media-file" href="#" id="%id%" class="nfw-button nfw-button-small" icon="ui-icon-arrowrefresh-1-e" title="<?php echo $lang_media['Reload']?>"></a><a rel="remove-media-file" href="#" id="%id%" class="nfw-button nfw-button-small" icon="ui-icon-close" title="<?php echo $lang_media['Remove']?>"></a></td></tr>';
				<?php else: ?>
				rowTemplate = '<tr class="zebra"><td style="white-space: nowrap;"><a href="%url%" target="_blank" type="%type%"><img src="%icon%"/> <span style="position: relative; top: -4px;">%basename%</span></a></td><td style="white-space: nowrap;">%filesize_str%</td><td><input id="%id%" rel="comment" type="text" style="width: 100%" value="%comment%" /></td><td style="white-space: nowrap;"><a rel="reload-media-file" href="#" id="%id%" class="nfw-button nfw-button-small" icon="ui-icon-arrowrefresh-1-e" title="<?php echo $lang_media['Reload']?>"></a><a rel="remove-media-file" href="#" id="%id%" class="nfw-button nfw-button-small" icon="ui-icon-close" title="<?php echo $lang_media['Remove']?>"></a></td></tr>';
				<?php endif; ?>
			}

			form.find('*[id="media-list-rows"]').empty();
			$.each(response.aaData, function(i, r){
				var tpl = rowTemplate.replace(/%id%/g, r.id);
				tpl = tpl.replace('%type%', r.type); 
				tpl = tpl.replace('%icon%', r.icon);
				tpl = tpl.replace('%icon_medium%', r.icon_medium);
				tpl = tpl.replace('%url%', r.url);
				tpl = tpl.replace('%filesize_str%', r.filesize_str);
				tpl = tpl.replace('%posted_str%', r.posted_str);
				tpl = tpl.replace('%basename%', r.basename.substr(0,48));
				tpl = tpl.replace('%comment%', r.comment);
				
				form.find('*[id="media-list-rows"]').append(tpl);
			});

			<?php if (!isset($allow_reload) || !$allow_reload): ?>
			form.find('*[id="media-list-rows"]').find('a[rel="reload-media-file"]').remove();
			<?php endif; ?>

			if ($.uniform) {
				form.find('input:not(.uniformed)').uniform().addClass('uniformed');
			}

			if ($.colorbox) {
				form.find('a[type="image"]').colorbox({ maxWidth:'96%', maxHeight:'96%', 'current' : '{current} / {total}' });
			}
			
			form.find('*[id="media-list"]').show();
			$(document).trigger('refresh');
					
		}, 'json');
	});

	<?php if (isset($allow_reload) && $allow_reload): ?>
	$(document).off('click', 'a[rel="reload-media-file"]').on('click', 'a[rel="reload-media-file"]', function(){
		reloadForm.find('input[name="file_id"]').val(this.id);
		reloadForm.find('input[name="local_file"]').trigger('click');
		return false;
	});
	<?php endif; ?>
	
	$(document).off('click', 'a[rel="remove-media-file"]').on('click', 'a[rel="remove-media-file"]', function(){
		<?php if ($owner_id): ?>
		if (!confirm('Удалить файл вложения?')) return false;
		<?php endif; ?>

		var currentForm = $(this).closest('form');
		currentForm.trigger('save-comments');
		
		var file_id = $(this).attr('id');
		$.post('<?php echo NFW::i()->base_path.'media.php?action=remove&owner_class='.$owner_class.'&owner_id='.$owner_id?>', { 'file_id': file_id }, function(){
			currentForm.trigger('load');
		});
		
		return false;
	});
	
	form.bind('save-comments', function(){
		var commentsArray = [];
		form.find('input[rel="comment"]').each(function(){
			commentsArray.push({ 'file_id': $(this).attr('id'), 'comment': $(this).val() });
		});
		
		if (commentsArray.length) {
			$.ajax({
				type: 'POST', async: false,
				url: '<?php echo NFW::i()->base_path.'media.php?action=update_comment&owner_class='.$owner_class.'&owner_id='.$owner_id?>',
				data: { 'comments': commentsArray }
			});
		}
	});

	form.bind('unload', function(){
		$.removeCookie('<?php echo $session_id?>', { path: '/' });
	});
	
	<?php if ($owner_id && (!isset($lazy_load) || !$lazy_load) ): ?>
	form.trigger('load');
	<?php endif; ?>

	$(window).on('beforeunload', function() {
		form.trigger('unload');
	});
});
</script>
<style>
	<?php if (NFW::i()->getUI() == 'bootstrap'): ?>
		form#<?php echo $session_id?> .alert-cond { padding: 10px; }
		form#<?php echo $session_id?> .alert-cond P { font-size: 12px; line-height: 13px; }
		
		form#<?php echo $session_id?> table#media-list td { vertical-align: middle; }
		form#<?php echo $session_id?> table#media-list a { text-decoration: none !important; }
		form#<?php echo $session_id?> table#media-list input { margin-bottom: 0; }
	<?php else: ?>
		/* uniform modify */
		form#<?php echo $session_id?> div.uploader { width: 190px; }
		form#<?php echo $session_id?> div.uploader span.filename { width: 85px; }
		form#<?php echo $session_id?> div.info-block { margin-left: 200px; font-size: 85% }
	<?php endif; ?>
</style>

<?php if (isset($allow_reload) && $allow_reload): ?>
<form id="reload-<?php echo $session_id?>" style="display: none;" method="POST" action="<?php echo NFW::i()->base_path.'media.php?action=reload'?>" enctype="multipart/form-data">
	<input type="hidden" name="MAX_FILE_SIZE" value="<?php echo $MAX_FILE_SIZE?>" />
	<input type="hidden" name="file_id" value="" />
	<input type="file" name="local_file" />
</form>						
<?php endif; ?>

<?php if(NFW::i()->getUI() == 'jqueryui'): ?>
<form id="<?php echo $session_id?>" action="<?php echo NFW::i()->base_path.'media.php?action=upload'?>" enctype="multipart/form-data">
	<input type="hidden" name="owner_id" value="<?php echo $owner_id?>" />
	<input type="hidden" name="owner_class" value="<?php echo $owner_class?>" />
	<input type="hidden" name="MAX_FILE_SIZE" value="<?php echo $MAX_FILE_SIZE?>" />

	<div style="float: left;">
		<input type="file" name="local_file" />
		<div rel="error-info" id="local_file" class="error-info"></div>
	</div>
		
	<?php ob_start(); ?>
	<div><?php echo $lang_media['MaxFileSize']?>: <strong><?php echo number_format($MAX_FILE_SIZE / 1048576, 2, '.', ' ')?><?php echo $lang_media['mb']?></strong></div>
	<div><?php echo $lang_media['MaxSessionSize']?>: <strong><?php echo number_format($MAX_SESSION_SIZE / 1048576, 2, '.', ' ')?><?php echo $lang_media['mb']?></strong></div>
	<?php if (!$owner_id): ?>
		<div><?php echo $lang_media['CurrentSessionSize']?>: <strong><span id="session_size">0</span><?php echo $lang_media['mb']?></strong></div>
	<?php endif; ?>
	<?php $info_text = ob_get_clean(); ?>
	<div class="info-block">
		<?php echo ui_message(array('text' => $info_text))?>
	</div>
	<div style="clear: both;"></div>
    
	<table id="media-list" class="main-table" style="display: none;">
		<thead>
			<tr>
				<th><?php echo $lang_media['Filename']?></th>
				<th><?php echo $lang_media['Filesize']?></th>
				<?php if ($owner_id): ?>
				<th><?php echo $lang_media['Uploaded']?></th>
				<?php endif; ?>
				<th style="width: 100%;"><?php echo $lang_media['Comment']?></th>
				<th>&nbsp;</th>
			</tr>
		</thead>
		<tbody id="media-list-rows"></tbody>
	</table>
</form>
<?php elseif (NFW::i()->getUI() == 'bootstrap'): ?>
<form id="<?php echo $session_id?>" class="form-horizontal" action="<?php echo NFW::i()->base_path.'media.php?action=upload'?>" enctype="multipart/form-data"><fieldset>
	<input type="hidden" name="owner_id" value="<?php echo $owner_id?>" />
	<input type="hidden" name="owner_class" value="<?php echo $owner_class?>" />
	<input type="hidden" name="MAX_FILE_SIZE" value="<?php echo $MAX_FILE_SIZE?>" />

	<div class="form-group" id="local_file">
		<div class="col-md-offset-3 col-md-4">
			<input type="file" name="local_file" />
			<span class="help-block"></span>
		</div>
		<div class="col-md-5">
			<div class="alert alert-warning alert-cond">
				<p style="font-size: 80%;"><?php echo $lang_media['MaxFileSize']?>: <strong><?php echo number_format($MAX_FILE_SIZE / 1048576, 2, '.', ' ')?>Mb</strong></p>
				<p style="font-size: 80%;"><?php echo $lang_media['MaxSessionSize']?>: <strong><?php echo number_format($MAX_SESSION_SIZE / 1048576, 2, '.', ' ')?>Mb</strong></p>
				<p style="font-size: 80%;"><?php echo $lang_media['CurrentSessionSize']?>: <strong><span id="session_size">0</span>Mb</strong></p>
			</div>
		</div>
	</div>
	
	<div class="form-group"><div class="col-md-offset-3 col-md-9">
		<table id="media-list" class="table table-striped table-condensed table-hover" style="display: none;">
			<thead>
				<tr>
					<th></th>
					<th><?php echo $lang_media['Comment']?></th>
					<th></th>
				</tr>
			</thead>
			<tbody id="media-list-rows"></tbody>
		</table>
	</div></div>
</fieldset></form>	
<?php endif; ?>	