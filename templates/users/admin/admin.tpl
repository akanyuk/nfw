<?php
NFW::i()->registerResource('dataTables');
NFW::i()->registerResource('jquery.activeForm');
?>
<script type="text/javascript">
$(document).ready(function(){
	// Action 'admin'

	var config =  dataTablesDefaultConfig;

	// Infinity scrolling
	config.bScrollInfinite = true;
	config.bScrollCollapse = true;
	config.iDisplayLength = 100;
	config.sScrollY = $(window).height() - $('table[id="users"]').offset().top - 102;

	// Create columns
	config.aoColumns = [
		{ 'bVisible': false },								// Group ID
		{ 'bSortable': false, 'sClass': 'icon-column' },	// is_blocked
	    { 'sClass': 'right' },								// ID
	    { 'sClass': 'nowrap-column' },						// Username
	    { 'sClass': 'nowrap-column' },						// Realname
	    { 'sClass': 'nowrap-column' },						// E-Mail
	    { 'bSortable': false, 'sClass': 'nowrap-column' },	// Registered IP
	    { 'bSortable': false, 'sClass': 'nowrap-column' }	// Registered
    ];

	config.aaSorting = [[1,'desc']];
	config.oSearch = { 'sSearch': '<?php echo (isset($_GET['filter'])) ? htmlspecialchars($_GET['filter']) : ''?>' };

	config.fnRowCallback = function(nRow, aData, iDisplayIndex) {
		// Status icons
		if (aData[1] == 1) {
			$('td:eq(0)', nRow).html('<span class="tooltip ui-icon ui-icon-cancel nfw-tooltip" title="Заблокирован"></span>');
		}
		else {
			$('td:eq(0)', nRow).html('<span class="tooltip ui-icon ui-icon-check nfw-tooltip" title="Активен"></span>');
		}

		return nRow;
	};

	var oTable = $('table[id="users"]').dataTable(config);

	// Custom filtering function
	$('.dataTables_filter').before($('div[id="custom-filters"]').html()).css('width', '60%');
	$('div[id="custom-filters"]').remove();

	// Custom filtering
	$.fn.dataTableExt.afnFiltering.push(
		function(oSettings, aData, iDataIndex) {
			var isFiltered = false;

			var group_id = $('select[id="group_id"] option:selected').val();
			if (group_id == '-1' || group_id == aData[0]) {
				isFiltered = true;
			}

			return isFiltered;
		}
	);

	$('select[id="group_id"]').change(function(){
		oTable.fnDraw();
	}).uniform();


	// Action 'insert
	$('div[id="users-insert-dialog"]').dialog({
		autoOpen: false, draggable:true, modal:true, resizable:false,
		title: 'Новый пользователь',
		width: 'auto',height: 'auto',
		buttons: { 'Сохранить': function() {
			$('FORM[id="users-insert"]').submit();
		}}
	});

	$('form[id="users-insert"]').activeForm({
		'success': function(response) {
			window.location.href = '<?php echo $Module->formatURL('update')?>&record_id=' + response.record_id;
			return false;
		}
	});

	$('button[id="users-insert"]').click(function(){
		$('FORM[id="users-insert"]').resetForm();
		$('FORM[id="users-insert"]').find('input[name="password"]').val(randomString(8));
		$('div[id="users-insert-dialog"]').dialog('open');
		return false;
	});

	$(document).trigger('refresh');
});
</script>
<?php if (NFW::i()->checkPermissions('users', 'insert')) : ?>
	<div id="users-insert-dialog" style="display: none;">
		<form id="users-insert" action="<?php echo $Module->formatURL('insert')?>">
			<?php echo active_field(array('name' => 'username', 'attributes'=>$Module->attributes['username'], 'width'=>"400px;"))?>
			<?php echo active_field(array('name' => 'password', 'name'=>"password", 'desc'=>"Пароль", 'required'=>true, 'maxlength'=>32, 'width'=>"400px;"))?>
			<?php echo active_field(array('name' => 'email', 'attributes'=>$Module->attributes['email'], 'width'=>"400px;"))?>
			<?php echo active_field(array('name' => 'realname', 'attributes'=>$Module->attributes['realname'], 'width'=>"400px;"))?>
			<?php echo active_field(array('name' => 'language', 'attributes'=>$Module->attributes['language']))?>
			<?php echo active_field(array('name' => 'country', 'attributes'=>$Module->attributes['country']))?>
 		    	<?php echo active_field(array('name' => 'city', 'attributes'=>$Module->attributes['city']))?>
			<?php echo active_field(array('name' => 'group_id', 'attributes'=>$Module->attributes['group_id']))?>
		</form>
	</div>
<?php endif; ?>

<div id="custom-filters" style="display: none;">
	<div style="float: left;">
		<button id="users-insert" class="nfw-button nfw-button-small nfw-tooltip" data-icon="ui-icon-document" title="<?php echo $Module->lang['New']?>"></button>

		<select id="group_id">
			<option value="-1">Все</option>
			<option value="0">Без группы</option>
			<?php foreach ($groups as $group) { ?>
				<option value="<?php echo $group['id']?>"><?php echo htmlspecialchars($group['username'])?></option>
			<?php } ?>
		</select>
	</div>
</div>

<table id="users" class="dataTables">
	<thead>
		<tr>
			<th>group_id</th>
			<th></th>
			<th>ID</th>
			<th>Логин</th>
			<th>Имя</th>
			<th>E-mail</th>
			<th>Зарегистрирован</th>
			<th>IP</th>
		</tr>
	</thead>
	<tbody>
		<?php foreach ($users as $user) { ?>
			<tr>
				<td><?=$user['group_id']?></td>
				<td><?=$user['is_blocked']?></td>
				<td><?=$user['id']?></td>
				<td><?php if (NFW::i()->checkPermissions('users', 'update')) echo '<a href="'.$Module->formatURL('update').'&record_id='.$user['id'].'">'.htmlspecialchars($user['username']).'</a>'; else echo htmlspecialchars($user['username']); ?></td>
				<td><?php echo htmlspecialchars($user['realname']); ?></td>
				<td><?php echo htmlspecialchars($user['email']); ?></td>
				<td><?php echo date('d.m.Y H:i:s', $user['registered']); ?></td>
				<td><?php echo $user['registration_ip']?></td>
			</tr>
		<?php } ?>
</tbody></table>