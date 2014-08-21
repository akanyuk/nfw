<?php
/***********************************************************************
  Copyright (C) 2009-2013 Andrew nyuk Marinov (aka.nyuk@gmail.com)
  $Id$  

 Абстрактный класс управления дополнительными полями объекта. (ФИО, адрес, паспортные данные...)
 Поддерживаются мультивариантность, групповые поля, вложения, полная история операций.
 
 Для настройки:
 1. Класс-наследник должен установить корректные значения $this->owner_classname и $this->attributes
 2. $this->owner_classname должен наследоваться от active_record и иметь совместимый конструктор 
    (см. функция $this->loadRecord)
 3. Имя класса должно совпадать с именем таблицы и с имененм каталога шаблонов
 4. Прописать все необходимые атрибуты в $this->attributes
 5. В шаблоне `admin.tpl` подстороить отбражение полей по вкусу.
  
 ************************************************************************/
abstract class additional extends active_record {
	var $operations = array(
		'CREATE' => 1,
		'ACTIVATE' => 2,
		'REMOVE' => 3,
	);
	
	var $owner_classname = '';
	var $owner_id = false;
	
	var $active_values = array();		// Активные значения
	var $unconfirmed = array();			// Неподтвержденные значения
	
	var $attributes = array();
	
	function __construct($owner_class = false) {
		foreach ($this->attributes as $varname=>&$a) {
			$a['type'] = isset($a['type']) ? $a['type'] : 'str'; 
			$a['unique'] = isset($a['unique']) ? $a['unique'] : false;
				
			if ($a['type'] == 'group') {
				foreach ($a['childs'] as &$c) {
					$c['default'] = isset($c['default']) ? $c['default'] : '';
				}
			}
			
			$a['multiple'] = (isset($a['multiple']) && $a['multiple']) ? true : false;
			$a['with_attachments'] = (isset($a['with_attachments']) && $a['with_attachments']) ? true : false;
		}
		
		$this->clean();
		
		$this->db_table = get_class($this);
		
		if (is_object($owner_class) && $owner_class->record['id']) {
			$this->owner_id = $owner_class->record['id'];
			return $this->loadRecord();
		}
		
		return true;
	}

	// Cleanup loaded values
	private function clean() {
		$this->active_values = $this->unconfirmed = array();
		
		foreach ($this->attributes as $varname=>&$a) {
			$this->active_values[$varname] = array();
		}		
	}
	
	private function doOperation($operation, $varname, $value = false, $is_confirmed = false) {
		$value = $value === false ? $this->record[$varname] : $value;
		$is_confirmed = $is_confirmed ? 1 : 0;
	
		$query = array(
			'INSERT'	=> '`owner_id`, `varname`, `value`, `operation`, `is_confirmed`, `posted_by`, `posted_username`, `poster_ip`, `posted`',
			'INTO'		=> $this->db_table,
			'VALUES'	=> $this->owner_id.', \''.$varname.'\', \''.NFW::i()->db->escape($value).'\', '.$operation.', '.$is_confirmed.', '.NFW::i()->user['id'].', \''.NFW::i()->db->escape(NFW::i()->user['username']).'\', \''.logs::get_remote_address().'\', '.time()
		);
		
		if (!NFW::i()->db->query_build($query)) {
			$this->error('Unable to insert record attribute', __FILE__, __LINE__, NFW::i()->db->error());
			return false;
		}
	
		return NFW::i()->db->insert_id();
	}
		
	protected function loadRecord($owner_id = false) {
		$this->clean();
		
		if (!$this->owner_id) {
			$obj = new $this->owner_classname($owner_id);
			if (!$obj->record['id']) {
				$this->error('Unable to load owner record', __FILE__, __LINE__);
				return false;
			}
			
			$this->owner_id = $obj->record['id'];
		}
		
		$query = array(
			'SELECT'	=> '*',
			'FROM'		=> $this->db_table,
			'WHERE'		=> 'owner_id='.$this->owner_id,
			'ORDER BY'	=> 'varname, id'
		);
		if (!$result = NFW::i()->db->query_build($query)) {
			$this->error('Unable to fetch records', __FILE__, __LINE__, NFW::i()->db->error());
			return false;
		}
		while($r = NFW::i()->db->fetch_assoc($result)) {
			$r['visible_value'] = $this->formatVisibleValue($r, $this->attributes[$r['varname']]);

			$id_hash = md5($r['value']);
			
			if ($r['operation'] == $this->operations['CREATE']) {
				if (!$r['is_confirmed']) {
					// Collect unconfirmed (added by client) values. Only last addition
					$r['activated'] = false;
					$r['remove_request'] = false;
					$this->unconfirmed[$r['varname']][$id_hash] = $r;
					
				}
				
				unset($this->active_values[$r['varname']][$id_hash]);
			}
			elseif ($r['operation'] == $this->operations['ACTIVATE'] && $r['is_confirmed']) {
				$this->active_values[$r['varname']][$id_hash] = $r;
				
				unset($this->unconfirmed[$r['varname']][$id_hash]);
				
			}
			elseif ($r['operation'] == $this->operations['REMOVE']) {
				unset($this->active_values[$r['varname']][$id_hash]);
				
				if ($r['is_confirmed']) {
					unset($this->unconfirmed[$r['varname']][$id_hash]);
				}
				else {
					$r['activated'] = false;
					$r['remove_request'] = true;
					$this->unconfirmed[$r['varname']][$id_hash] = $r;
				}
			}
		}

		foreach ($this->active_values as $varname=>&$acv) {
			usort($acv, 'sortAdditionalByDate');
			
			if (!$this->attributes[$varname]['multiple']) {
				$acv = reset($acv);
			}
		}
		
		foreach ($this->unconfirmed as $varname=>&$uv) {
			usort($uv, 'sortAdditionalByDate');
			
			if (!$this->attributes[$varname]['multiple']) {
				$uv = reset($uv);
			}
		}
		
		$CMedia = new media();
		foreach($CMedia->getFiles($this->owner_classname.'|%', $this->owner_id) as $a) {
			$varname = substr($a['owner_class'], strlen($this->owner_classname.'|'));
			$this->active_values['attachments'][$varname][] = $a;
		}
		
		return true;
	}
	
	public function reloadRecord() {
		return $this->loadRecord();
	}
	
	public function insertValue($varname, $value = false, $is_confirmed = false) {
		return $this->doOperation($this->operations['CREATE'], $varname, $value, $is_confirmed);
	}
		
	public function activateValue($varname, $value = false, $is_confirmed = false) {
		return $this->doOperation($this->operations['ACTIVATE'], $varname, $value, $is_confirmed);
	}
		
	public function removeValue($id, $is_confirmed = false) {
		// Search before updating
		$query = array(
			'SELECT' => 'varname, value',
			'FROM' => $this->db_table,
			'WHERE' => 'owner_id='.$this->owner_id.' AND id='.intval($id)
		);
		if (!$result = NFW::i()->db->query_build($query)) {
			$this->error('Unable to search record attribute', __FILE__, __LINE__, NFW::i()->db->error());
			return false;
		}
		if (!NFW::i()->db->num_rows($result)) return false;
		list($varname, $value) = NFW::i()->db->fetch_row($result);
				
		return $this->doOperation($this->operations['REMOVE'], $varname, $value, $is_confirmed);
	}
		
	function getActiveValue($id) {
		foreach ($this->active_values as $varname=>$a) {
			if ($this->attributes[$varname]['multiple']) {
				foreach ($a as $child) {
					if ($child['id'] == $id) return $child;
				}
			}
			elseif ($a['id'] == $id) {
				return $a;
			}
		}
		
		return false;
	}
	
	function validate($record = false, $attributes = false) {
		$record = $record ? $record : $this->record;
		$attributes = $attributes ? $attributes : $this->attributes;
    	
		$errors = parent::validate($record, $attributes);
		
		// Unsed unused attributes errors & Check unique values
		foreach($record as $varname=>$value) {
			if (!isset($attributes[$varname]['unique']) || !$attributes[$varname]['unique']) continue;
				
			$query = array(
				'SELECT'	=> 'operation',
				'FROM'		=> $this->db_table,
				'WHERE'		=> 'is_confirmed=1 AND varname=\''.NFW::i()->db->escape($varname).'\' AND value=\''.NFW::i()->db->escape($record[$varname]).'\'',
				'ORDER BY'	=> 'id'
			);
			if ($this->owner_id) {
				$query['WHERE'] .= ' AND owner_id<> '.$this->owner_id;
			}
			if (!$result = NFW::i()->db->query_build($query)) {
				$this->error('Unable to check value unique.', __FILE__, __LINE__, NFW::i()->db->error());
			}
			
			$is_exist = false;
			while ($r = NFW::i()->db->fetch_assoc($result)) {
				if ($r['operation'] == $this->operations['ACTIVATE']) {
					$is_exist = true;
				}
				elseif ($r['operation'] == $this->operations['REMOVE'] && $r['is_confirmed']) {
					$is_exist = false;
				}
			}
			if ($is_exist) {
				$errors[$varname] = 'Такое же значение «'.$attributes[$varname]['desc'].'» уже зарегистрирован в системе.';
			}
		}
		 
		return $errors;
	}

	public function formatVisibleValue(&$value, $attribute) {
		// Generate 'visible_value'
		if ($attribute['type'] != 'group') {
			return parent::formatVisibleValue($value['value'], $attribute);
		}
		
		$value['childs'] = NFW::i()->unserializeArray($value['value']);
		$implode_me = $implode_me_short = array();
		foreach ($value['childs'] as $child_varname=>$child_value) {
			if ($child_value) {
				$implode_me[] = parent::formatVisibleValue($child_value, $attribute['childs'][$child_varname]);
				
				if (isset($attribute['childs'][$child_varname]['visible_value_short']) && isset($attribute['childs'][$child_varname]['visible_value_short'])) {
					$implode_me_short[] = parent::formatVisibleValue($child_value, $attribute['childs'][$child_varname]);
				}
			}
		}

		// Сокращенное значение (например для банковских реквизитов - только наименование банка и Р/С)
		$value['visible_value_short'] = empty($implode_me_short) ? false : implode($attribute['implode_by'], $implode_me_short);
		 
		return implode($attribute['implode_by'], $implode_me);
	}
		
	function actionAdmin($params = array()) {
		$this->error_report_type = 'alert';

		if (isset($params['owner_id'])) {
			$owner_id = $params['owner_id'];
		}
		elseif (isset($_GET['owner_id'])) {
			$owner_id = $_GET['owner_id'];
		}
		else {
			$this->error('Не передан идентификатор владельца данных.', __FILE__, __LINE__);
			return false;
		}
		
		if (!$this->loadRecord($owner_id)) return false;
		$this->error_report_type = 'plain';
		
		if (isset($params['continue-executing']) && $params['continue-executing']) {
			return $this->renderAction(array(
				'params' => $params
			)); 
		}
		else {
			NFW::i()->stop($this->renderAction(array(
				'params' => $params
			)));
		}        
	}

	function actionUpdate($params = array()) {
		$this->error_report_type = empty($_POST) ? 'plain' : 'active_form';
		
		$varname = (isset($_GET['varname'])) ? $_GET['varname'] : '';
		if (!isset($this->attributes[$varname])) {
			$this->error('Wrong request', __FILE__, __LINE__);
			return false;
		}
		
		if (!$this->loadRecord(isset($_GET['owner_id']) ? $_GET['owner_id'] : false)) return false;
		
		// Load `all_values` & `history`
		$history = $all_values = array();
		$query = array(
			'SELECT'	=> '*',
			'FROM'		=> $this->db_table,
			'WHERE'		=> 'varname="'.$varname.'" AND owner_id='.$this->owner_id,
			'ORDER BY'	=> 'id'
		);
		if (!$result = NFW::i()->db->query_build($query)) {
			$this->error('Unable to fetch records', __FILE__, __LINE__, NFW::i()->db->error());
			return false;
		}
		while($r = NFW::i()->db->fetch_assoc($result)) {
			$r['visible_value'] = $this->formatVisibleValue($r, $this->attributes[$varname]);
		
			$history[] = $r;
				
			$id_hash = md5($r['value']);
			if ($r['operation'] == $this->operations['CREATE']) {
				$all_values[$id_hash] = $r;
				$all_values[$id_hash]['activated'] = false;
			}
			elseif ($r['operation'] == $this->operations['ACTIVATE'] && $r['is_confirmed']) {
				$all_values[$id_hash]['activated'] = $r['posted'];
				$all_values[$id_hash]['activated_username'] = $r['posted_username'];
			}
			elseif ($r['operation'] == $this->operations['REMOVE']) {
				if ($r['is_confirmed']) {
					unset($all_values[$id_hash]);
				}
				else {
					$all_values[$id_hash]['activated'] = false;
					$all_values[$id_hash]['remove_request'] = $r['posted'];
					$all_values[$id_hash]['remove_request_username'] = $r['posted_username'];
				}
			}		
		}
		usort($all_values, 'sortAdditionalByDate');
				
		if (empty($_POST)) {
			$CMedia = new media();
			
			if (isset($this->attributes[$varname]['update_template'])) {
				$tpl = $this->attributes[$varname]['update_template'];
			}
			else {
				$tpl = ($this->attributes[$varname]['type'] == 'group') ? '_update_group' : 'update';
			}
			NFW::i()->stop($this->renderAction(array(
				'varname' => $varname,
				'all_values' => $all_values,
				'history' => $history,
				'attribute' => $this->attributes[$varname],
				'attachments_form' => ($this->attributes[$varname]['with_attachments']) ? $CMedia->openSession(array(
					'secure_storage' => 1,
					'autoconfirm' => 1,
					'owner_class' => $this->owner_classname.'|'.$varname,
					'owner_id' => $this->owner_id)) : null
			), $tpl));
		}
		
		// --------------
		// Start updating
		// --------------
		$remove_records = isset($_POST['remove']) && is_array($_POST['remove']) ? $_POST['remove'] : array();
		$is_active = isset($_POST['is_active']) && is_array($_POST['is_active']) ? $_POST['is_active'] : array();
		
		$create_records = $activate_records = array();
		foreach (isset($_POST['values']) && is_array($_POST['values']) ? $_POST['values'] : array() as $id=>$new_value) {
			// Format & validate `new_value`
			if ($this->attributes[$varname]['type'] == 'group') {
				// Format 'group' values
				$childs = $this->formatAttributes($new_value, $this->attributes[$varname]['childs']);
				
				// Check if empty `new_value`
				$is_empty_childs = true;
				foreach ($childs as $c) if ($c) { $is_empty_childs = false; break; }
				if ($is_empty_childs) continue;
		
				$errors = $this->validate($childs, $this->attributes[$varname]['childs']);
				if (!empty($errors)) {
					NFW::i()->renderJSON(array('result' => 'error', 'errors' => $errors));
				}
		
				$new_value = NFW::i()->serializeArray($childs);
			}
			else {
				// Format & validate 'value'
				$new_value = reset($this->formatAttributes(array($varname => $new_value)));
				
				if (!$new_value && $this->attributes[$varname]['type'] != 'bool') continue;
				
				$errors = $this->validate(array($varname => $new_value), array($varname => $this->attributes[$varname]));
				if (!empty($errors)) {
					NFW::i()->renderJSON(array('result' => 'error', 'errors' => $errors));
				}
			}
		
			// Already in `create` list
			if (in_array($new_value, $create_records)) continue;
			
			$new_value_activated = in_array($id, $is_active) ? 1 : 0;
		
			// Search same value
			$exist_value = false;
			foreach ($all_values as $r) {
				if ($r['value'] != $new_value) continue;
				
				// Same found, change `activated` state
				if ($new_value_activated) {
					$activate_records[] = $new_value;
				}
				elseif ($r['activated'] && !$new_value_activated) {
					$create_records[] = $new_value;
				}
				
				$exist_value = true; 
				break;
			}
				
			if (!$exist_value) {
				// Add new value
				$create_records[] = $new_value;
		
				if ($new_value_activated) {
					$activate_records[] = $new_value;
				}
			}
		}
/*
		FB::log(array(
		 	'create_records' => $create_records,
		 	'activate_records' => $activate_records,
		 	'remove_records' => $remove_records,
		));
		return false;
*/		

		// Start updating
		$is_updated = false;
		
		// Remove old records
		foreach ($remove_records as $id) {
			if (!$this->removeValue($id, true)) {
				NFW::i()->renderJSON(array('result' => 'error', 'errors' => $this->errors));
			}
		
			$is_updated = true;
		}
		
		// Insert new records
		foreach ($create_records as $value) {
			if (!$this->insertValue($varname, $value, true)) {
				NFW::i()->renderJSON(array('result' => 'error', 'errors' => $this->errors));
			}
		
			$is_updated = true;
		}
		
		// Update `activated`
		foreach ($activate_records as $value) {
			if (!$this->activateValue($varname, $value, true)) {
				NFW::i()->renderJSON(array('result' => 'error', 'errors' => $this->errors));
			}
		
			$is_updated = true;
		}

		if (isset($this->unconfirmed[$varname])) {
			// Confirm unconfirmed values
			$query = array(
				'UPDATE'	=> $this->db_table,
				'SET'		=> '`is_confirmed`=1',
				'WHERE' => 'owner_id='.$this->owner_id.' AND varname=\''.$varname.'\''
			);
			if (!NFW::i()->db->query_build($query)) {
				$this->error('Unable to insert record attribute', __FILE__, __LINE__, NFW::i()->db->error());
				return false;
			}
			
			$is_updated = true;
		}
		
		NFW::i()->renderJSON(array('result' => 'success', 'is_updated' => $is_updated));
	}
}

function sortAdditionalByDate($a, $b) {
	if ($a['posted'] == $b['posted']) {
		return 0;
	}
	return ($a['posted'] > $b['posted']) ? -1 : 1;
}