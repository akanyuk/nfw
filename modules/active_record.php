<?php
/***********************************************************************
  Copyright (C) 2010 Andrew nyuk Marinov (aka.nyuk@gmail.com)
  $Id$  

   Базовый класс active_record 
  
 ************************************************************************/

abstract class active_record extends base_module {
	var $attributes = array();
	var $record = false;		//	Current record
	protected $db_record = false;			// Last loaded DB-record
	protected $db_table = false;			// DB Tablename with stored records			
		
	function __construct($record_id = false) {
		if ($this->db_table === false) {
			$this->db_table = get_class($this);
		}
		
		// Fill undefuned attributes
		foreach ($this->attributes as &$attr) {
			if (!isset($attr['required'])) $attr['required'] = false;
			if (!isset($attr['unique'])) $attr['unique'] = false;
		}

		// Load record
		if ($record_id !== false) return $this->load($record_id);
		
		// Fill new record default values
		$this->record['id'] = false;
		foreach ($this->attributes as $varname=>$attributes) {
			$this->record[$varname] = isset($attributes['default']) ? $attributes['default'] : null;
		}
		
		return parent::__construct($record_id);
   	}

   	/**
   	 * Default record loader
   	 * 
   	 * @param $id		Record ID
   	 * @return Array 	Return loaded $this->record 
   	 */
	protected function load($id) {
		if (!$result = NFW::i()->db->query_build(array('SELECT' => '*', 'FROM' => $this->db_table, 'WHERE' => 'id='.intval($id)))) {
	    	$this->error('Unable to fetch record', __FILE__, __LINE__, NFW::i()->db->error());
	    	return false;
		}
	    if (!NFW::i()->db->num_rows($result)) {
	    	$this->error('Record not found.', __FILE__, __LINE__);
	    	return false;
	    }
	    $this->db_record = $this->record = NFW::i()->db->fetch_assoc($result);

		return $this->record;
	}

	protected function unload() {
		$this->record = array('id' => false);
		foreach ($this->attributes as $varname=>$attributes) {
			$this->record[$varname] = isset($attributes['default']) ? $attributes['default'] : null;
		}
		
		return true;
	}
	
	protected function searchArrayAssoc($array = array(), $value = false, $keyname = 'id') {
		foreach ($array as $a) {
			if ($a[$keyname] == $value) return $a;
		}

		return false;
	}
	
	protected function save() {
		if ($this->record['id']) {
			// Check if record updated
			$update = array();
			foreach ($this->attributes as $varname=>$foo) {
				$is_modified = false;
				$type = (isset($this->attributes[$varname]['type'])) ? $this->attributes[$varname]['type'] : 'str'; 
				switch ($type) {
					case 'str':
					case 'textarea':
						if (strcmp($this->record[$varname], $this->db_record[$varname]) != 0) $is_modified = true;							
						break;
					default:
						if ($this->record[$varname] != $this->db_record[$varname]) $is_modified = true;
						break;
				}
				
				if (!$is_modified) continue;
				
				$update[] = '`'.$varname.'` = \''.NFW::i()->db->escape($this->record[$varname]).'\'';
			}
			if (empty($update)) {
				return false;
			}

			if (!NFW::i()->db->query_build(array('UPDATE' => $this->db_table, 'SET' => implode(', ', $update), 'WHERE' => 'id='.$this->record['id']))) { 
				$this->error('Unable to update record', __FILE__, __LINE__, NFW::i()->db->error());
				return false;
			}
			
			$this->reload();
			return true;
		}
		else {
			foreach ($this->attributes as $varname=>$foo) {
				$insert[] = '`'.$varname.'`';
				$values[] = '\''.NFW::i()->db->escape($this->record[$varname]).'\'';
			}
			
			if (!NFW::i()->db->query_build(array('INSERT' => implode(', ', $insert), 'INTO' => $this->db_table, 'VALUES' => implode(', ', $values)))) {
				$this->error('Unable to insert record', __FILE__, __LINE__, NFW::i()->db->error());
				return false;
				
			}
			$this->record['id'] = NFW::i()->db->insert_id();
			$this->reload();
			return true;
		}
	}
	
	public function reload($id = false) {
		return $this->load($id ? $id : $this->record['id']); 
	}
	
	/* Remove current record
	 * 
	 */
	function delete() {
		if (!$this->record['id']) return false;
		
		if (!NFW::i()->db->query_build(array('DELETE' => $this->db_table, 'WHERE' => 'id='.$this->record['id']))) { 
			$this->error('Unable to delete record', __FILE__, __LINE__, NFW::i()->db->error());
			return false;
		}
		
		$this->unload();
		return true;
	}
	
	function validate($record = false, $attributes = false) {
    	return parent::validate(($record) ? $record : $this->record, ($attributes) ? $attributes : $this->attributes);
    }

    public function formatVisibleValue($value, $attribute) {
    	$prefix = isset($attribute['prefix']) ? $attribute['prefix'] : '';
    	 
    	
        switch (isset($attribute['type']) ? $attribute['type'] : 'default') { 
        	case 'bool':
    			return $value ? 'Да' : 'Нет';
        	case 'date':
    			return $prefix.((isset($attribute['withTime']) && $attribute['withTime']) ? date('d.m.Y H:I:S', intval($value)) : date('d.m.Y', intval($value)));
        	case 'select':
        		if ($o = $this->searchArrayAssoc($attribute['options'], $value)) {
        			return $prefix.(isset($o['desc']) ? $o['desc'] : $value);
        		}
        		elseif (isset($attribute['unknown_value'])) {
        			return $prefix.$attribute['unknown_value'];
        		}
        	default:
	    		return $prefix.$value;
    	}    	
    }
    
    /**
     * Format $data by $attributes rules and store in $this->record
     * 
     * @param array $data to format
     * @param array $attributes array with rules
     * @return array with affected fields
     */
    public function formatAttributes($data, $attributes = false) {
    	$result = array();
    	
    	foreach($attributes == false ? $this->attributes : $attributes as $varname => $a) {
    		if (!isset($data[$varname])) continue;
    		$result[$varname] = $this->formatAttribute($data[$varname], $a);
    	}
    	
    	// Store in $this->record
    	foreach ($result as $varname=>$value) {
    		$this->record[$varname] = $value;
    	}
    	 
    	return $result;
    }
    
    function formatAttribute($value, $rules) {
    	switch ($rules['type']) {
    		case 'custom':
    			break;
    		case 'date':
    			$value = intval($value);
    			if (isset($rules['is_end']) && $rules['is_end'] && $value) { 
    				$value = mktime(23,59,59,date("n", $value), date('j', $value), date("Y", $value));
    			}
    			break;
    			 
    		case 'int':
    			$value = intval(trim($value));
    			break;
    		case 'float':
    			$value = floatval(str_replace(',', '.', trim($value)));
    			break;
    		case 'bool':
    		case 'checkbox':
    			$value = $value ? 1 : 0;
    			break;
    		default:
    			$value = trim($value);
    	}
    	
    	return $value;
    }
}