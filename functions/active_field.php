<?php
/*
 * 'active_field' function
 *
 * Type:     function
 * Name:     active_field
 * Purpose:  Generate active form field. Supported UI: jQueryUI, Bootstrap
 * Modified: 2014-06-02
 */


/* Usage:
 *
 *     	1.  active_field(array('name'=>"name", 'value'=>$value, 'desc'=>"Имя", 'type'=>"text", 'required'=>true))
 *     
 *		2.	active_field(array('attributes'=>$array, 'value'=>$value))
 */

/* available attributes
 *		name		(str)	input field name
 *		type		(str)	one of: str|text (DEFAULT), date, select, textarea, password, bool|checkbox
 *		value		(str)	input field value
 *     	required	(bool) 	field required
 *		options		(array)	An array with all selectbox options. 
 *							One piece must be string or array with fields: 'id' and 'desc'
 *		id			(str)	input field id
 *		class		(str)	input field class
 *		maxlength	(int)	value maxlength (only for type="text" and type="password")
 *		rows		(int)	Num rows (for type="textarea")
 *		withTime	(bool)	Create input time fields (for type="date")
 *		width		(str)	input field width (i.e. '100px')
 *		height		(str)	textarea height (i.e. '100px')
 *     	desc		(str) 	field description
 */

function active_field($params) {
	if (function_exists('_active_field_'.NFW::i()->getUI())) {
		return call_user_func('_active_field_'.NFW::i()->getUI(), $params);
	}
}

function _active_field_jqueryui($params) {
	foreach(array('name', 'type', 'value', 'options', 'id', 'class', 'rel', 'width', 'height', 'maxlength', 'placeholder', 'desc', 'required', 'req', 'withTime', 'rows') as $varname) {
		if (isset($params[$varname])) {
			$$varname = $params[$varname];
		}
		elseif (isset($params['attributes'][$varname])) {
			$$varname = $params['attributes'][$varname];
		}
		else {
			$$varname = false;
		}
	}
	
	if (!$type) $type = 'text';
	if (!$options) $options = array();
	if ($class) $class = ' class="'.$class.'"';
	if ($rel) $rel = ' rel="'.$rel.'"';
	if ($required) {
		$required = ' class="required"';
		$req = ' req="required"';
	}
	if ($maxlength) $maxlength = ' maxlength="'.intval($maxlength).'"';
	if ($placeholder) $placeholder = ' placeholder="'.$placeholder.'"';
	if ($rows) $rows = ' rows="'.intval($rows).'"';
	
	$style = array();
	if ($width && $type != 'date') $style[] = 'width: '.$width;
	if ($height) $style[] = 'height: '.$height;
	$style = (empty($style)) ? '' : ' style="'.implode(' ',$style).'"';
	
	$labelDesc = $type == 'checkbox' || $type == 'bool' ? '' : $desc;
	
	ob_start();
?>
    <div class="active-field" id="<?php echo $id ? $id : $name?>">
	    <label for="<?php echo $name?>"<?php echo $required?>><?php echo $labelDesc?></label>
    	<div class="input-row">
<?php 
	if ($type == 'date') {
		NFW::i()->registerResource('jquery.activeForm/jqueryui.timepicker.min.js'); 
		NFW::i()->registerResource('jquery.activeForm/jqueryui.timepicker.js');
?>    	
	<input <?php echo $id ? 'id="'.$id.'"' : ''?> <?php echo $class.$rel.$req.$style?> type="text" name="<?php echo $name?>" value="<?php echo $value ? $value : ''?>" class="datepicker" maxlength="10" withTime="<?php echo $withTime ? '1' : '0'?>" />
<?php } elseif ($type == 'select') { ?>
   	<select <?php echo $id ? 'id="'.$id.'"' : ''?> <?php echo $class.$rel.$req.$style?> name="<?php echo $name?>">
<?php   	
  foreach ($options as $option) {
		if (is_array($option)) {
?>
		<option value="<?php echo $option['id']?>" <?php echo ($option['id'] == $value) ? ' selected="selected"' : ''?>><?php echo htmlspecialchars($option['desc'])?></option>
<?php
  		}
  		else {
?>
		<option value="<?php echo $option?>" <?php echo ($option == $value) ? ' selected="selected"' : ''?>><?php echo htmlspecialchars($option)?></option>
<?php
  		}
  }
?>
  	</select>
<?php } elseif ($type == 'textarea') { ?>    	
   	<textarea <?php echo $id ? 'id="'.$id.'"' : ''?> <?php echo $class.$rel.$req.$style.$placeholder.$rows?> name="<?php echo $name?>"><?php echo htmlspecialchars($value)?></textarea>
<?php } elseif ($type == 'password') { ?>    	
   	<input <?php echo $id ? 'id="'.$id.'"' : ''?> <?php echo $class.$rel.$req.$style.$maxlength?> type="password" name="<?php echo $name?>" />
<?php } elseif ($type == 'checkbox' || $type == 'bool') {
?>    	
	<input type="hidden" name="<?php echo $name?>" value="0" />
   	<input <?php echo $id ? 'id="'.$id.'"' : ''?> type="checkbox" name="<?php echo $name?>" value="1" <? if ($value) echo ' checked="CHECKED"'?> />
   	<?php echo $desc?>
<?php } else { ?>
	<input <?php echo $id ? 'id="'.$id.'"' : ''?> <?php echo $class.$rel.$req.$style.$maxlength.$placeholder?> type="text" name="<?php echo $name?>" value="<?php echo htmlspecialchars($value)?>" />
<?php }?>
		    <div data-rel="error-info" class="error-info" id="<?php echo $id ? $id : $name?>"></div>
    	</div>
    </div>
<?php
	return ob_get_clean();
}

function _active_field_bootstrap($params) {
	foreach(array('name', 'type', 'value', 'options', 'id', 'class', 'rel', 'width', 'height', 'maxlength', 'placeholder', 'desc', 'required', 'withTime', 'rows', 'labelCols', 'inputCols') as $varname) {
		if (isset($params[$varname])) {
			$$varname = $params[$varname];
		}
		elseif (isset($params['attributes'][$varname])) {
			$$varname = $params['attributes'][$varname];
		}
		else {
			$$varname = false;
		}
	}

	if (!$type) $type = 'text';
	if (!$options) $options = array();
	if ($rel) $rel = ' rel="'.$rel.'"';
	if ($required) $required = ' class="required"';
	if ($maxlength) $maxlength = ' maxlength="'.intval($maxlength).'"';
	if ($placeholder) $placeholder = ' placeholder="'.$placeholder.'"';
	if ($rows) $rows = ' rows="'.intval($rows).'"';

	$style = array();
	if ($width && $type != 'date') $style[] = 'width: '.$width;
	if ($height) $style[] = 'height: '.$height;
	$style = (empty($style)) ? '' : ' style="'.implode(' ',$style).'"';

	$labelCols = $labelCols ? intval($labelCols) : 3;
	$inputCols = $inputCols ? intval($inputCols) : 6;
	if ($type == 'date') $inputCols = 5;
	
	$labelDesc = $type == 'checkbox' || $type == 'bool' ? '' : $desc;
	
	ob_start();
	?>
	<div id="<?php echo $id ? $id : $name?>" class="form-group">
		<label for="<?php echo $name?>" class="col-md-<?php echo $labelCols?> control-label"><?php echo $required ? '<strong>'.$labelDesc.'</strong>' : $labelDesc?></label>
		<div class="col-md-<?php echo $inputCols?>">
	<?php if ($type == 'date'): 
			NFW::i()->registerResource('jquery.activeForm/bootstrap-datetimepicker.min.js');
			NFW::i()->registerResource('jquery.activeForm/bootstrap-datetimepicker.ru.js');
			NFW::i()->registerResource('jquery.activeForm/bootstrap-datetimepicker.min.css');
	?>
	    <div language="<?php echo NFW::i()->user['language']?>">
	    	<input rel="datepicker" type="text" class="form-control" style="display: inline; width: auto;" name="<?php echo $name?>" value="<?php echo $value ? date('d.m.Y', $value) : ''?>" unixTimestamp="<?php echo intval($value)?>" withTime="<?php echo $withTime?>" /><span id="set-date" class="btn btn-default btn-sm"><span class="glyphicon glyphicon-calendar"></span></span><span id="remove-date" class="btn btn-default btn-sm"><span class="glyphicon glyphicon-remove"></span></span>
	    </div>
	<?php elseif ($type == 'select'): ?>
			<select name="<?php echo $name?>" class="form-control<?php echo $class ? ' '.$class : ''?>"><?php foreach ($options as $o) { ?>
			<?php if (is_array($o)): ?>
				<option value="<?php echo $o['id']?>"<?php echo ($o['id'] == $value) ? ' selected="selected"' : ''?>><?php echo $o['desc']?></option>
			<?php else: ?>
				<option value="<?php echo $o?>"<?php echo ($o == $value) ? ' selected="selected"' : ''?>><?php echo $o?></option>
			<?php endif; ?>
		<?php } ?></select>
	<?php elseif ($type == 'checkbox' || $type == 'bool'): ?>    	
		<input type="hidden" name="<?php echo $name?>" value="0" />
	   	<input <?php echo $id ? 'id="'.$id.'"' : ''?> type="checkbox" name="<?php echo $name?>" value="1" <? if ($value) echo ' checked="CHECKED"'?> />
	   	<?php echo $desc?>
	<?php elseif ($type == 'textarea'): ?>
			<textarea name="<?php echo $name?>" class="form-control <?php echo $class?>"<?php echo $placeholder.$rows.$style?>><?php echo htmlspecialchars($value)?></textarea>
	<?php elseif ($type == 'password'): ?>
			<input type="password" name="<?php echo $name?>" class="form-control <?php echo $class?>" <?php echo $placeholder.$maxlength?> value="<?php echo htmlspecialchars($value)?>" />
	<?php else: ?>
			<input type="text" name="<?php echo $name?>" class="form-control <?php echo $class?>"<?php echo $placeholder.$maxlength?> value="<?php echo htmlspecialchars($value)?>" />
	<?php endif; ?>
		</div>
	</div>
	<div id="<?php echo $id ? $id : $name?>" class="form-group">
		<div class="col-md-<?php echo $labelCols?>">&nbsp;</div>
		<div class="col-md-<?php echo (12-$labelCols)?>"><span class="help-block"></span></div>
	</div>
<?php 
	return ob_get_clean();
}