<?php
/***********************************************************************
  Copyright (C) 2004-2010 Andrew nyuk Marinov (aka.nyuk@gmail.com)
  $Id$
		
	Модуль записи логов.
	В конфиге необходимо установить
	write_logs=1
 ************************************************************************/

class logs extends base_module {
	
	const KIND_LOGIN 					= 10;
		
	const KIND_PERMISSIONS_UPDATE_ROLES	= 14;

	
	public static function get_remote_address() {
		return $_SERVER['REMOTE_ADDR'];
	}
	
	public static function get_browser() {
		// Try to determine browser
		if (defined('BROWSCAP_CACHE')) {
			require_once NFW_ROOT.'helpers/Browscap.php';
			$CBrowscap = new Browscap(BROWSCAP_CACHE);
			if (isset(NFW::i()->cfg['Browscap']['updateMethod'])) {
				$CBrowscap->updateMethod = NFW::i()->cfg['Browscap']['updateMethod'];
			}
				
			$b = $CBrowscap->getBrowser();
			if (isset($b->Browser) && $b->Browser) {
				$str =  $b->Browser;
				 
				if (isset($b->Version) && $b->Version) $str .=  ' '.$b->Version;
				if (isset($b->Platform) && $b->Platform) $str .= ' / '.$b->Platform;
				 
				if ($str) return $str;
			}
				
			if (isset($b->Browser) && $b->Browser) {
				$str =  $b->Browser;
				 
				if (isset($b->Version) && $b->Version) $str .=  ' '.$b->Version;
				if (isset($b->Platform) && $b->Platform) $str .= ' / '.$b->Platform;
				 
				if ($str) return $str;
			}
		}
		 
		if ($b = get_browser($_SERVER['HTTP_USER_AGENT'])) {
			if (isset($b->parent)) {
				$browser =  $b->parent;
	
				if (isset($b->platform) && $b->platform)  {
					$browser .= ' / '.$b->platform;
				}
	
				return $browser;
			}
		}
		 
		return '';
	}
		
	/**
	 * Write logs record
	 * @param $message	string	Logged message
	 * @param $kind		integer	Message kind
	 * 
	 * Usage:
	 * logs::write('Message');
	 * 
	 * or only `kind`:
	 * logs::write($kind);
	 * 
	 * or both `kind` and `message`:
	 * logs::write('Message', $kind);
	 *  
	 * or `kind`, `message` an `additional`:
	 * logs::write('Message', $kind, $additional);
	 *  
	 * @return true
	 */
    public static function write($message, $kind = 0, $additional = false) {
    	if (!isset(NFW::i()->cfg['write_logs']) || !NFW::i()->cfg['write_logs']) return true;
    	
    	if (!is_string($message)) {
    		$kind = intval($message);
    		$message = '';    		
    	}
    	
    	$insert = array(
    		'posted' => time(),
    		'poster' => NFW::i()->user['id'],
    		'ip' => self::get_remote_address(),
    		'url' => NFW::i()->db->escape(((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']) ? 'https://' : 'http://').$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']),
    		'message' => NFW::i()->db->escape($message),
    		'kind' => intval($kind)
    	);

    	if (isset($_SERVER['HTTP_USER_AGENT'])) {
    		$insert['user_agent'] = NFW::i()->db->escape($_SERVER['HTTP_USER_AGENT']);
    	}
    	
    	// API-пользователи, авторизованные локально (через класс SSL) не имеют поля `username`
    	if (isset(NFW::i()->user['username']) && NFW::i()->user['username']) {
    		$insert['poster_username'] = NFW::i()->user['username'];
    	}
    	
    	if ($additional) {
    		$insert['additional'] = NFW::i()->db->escape($additional);
    	}
    	
    	// Try to determine browser
    	if (defined('BROWSCAP_CACHE')) {
    		require_once NFW_ROOT.'helpers/Browscap.php';
    		$CBrowscap = new Browscap(BROWSCAP_CACHE);
    		if (isset(NFW::i()->cfg['Browscap']['updateMethod'])) {
    			$CBrowscap->updateMethod = NFW::i()->cfg['Browscap']['updateMethod'];
    		}
    		
    		$b = $CBrowscap->getBrowser();

    		if (isset($b->Browser) && $b->Browser) {
    			$str =  $b->Browser;
    			
    			if (isset($b->Version) && $b->Version) $str .=  ' '.$b->Version;
    			if (isset($b->Platform) && $b->Platform) $str .= ' / '.$b->Platform;
    			
    			if ($str) {
    				$insert['browser'] = NFW::i()->db->escape($str);    				
    			}
    		}
    	}
    	elseif ($b = get_browser($_SERVER['HTTP_USER_AGENT'])) {
    	   	if (isset($b->parent)) {
	    		$insert['browser'] =  $b->parent;
	    	
	    		if (isset($b->platform) && $b->platform)  {
	    			$insert['browser'] .= ' / '.$b->platform;
	    		}
	    	
	    		$insert['browser'] = NFW::i()->db->escape($insert['browser']);
	    	}
    	}

    	
    	// Generate query
    	$varnames = $values = array();
    	foreach($insert as $varname=>$value) {
    		$varnames[] = $varname;
    		$values[] = '\''.$value.'\'';
    	}
    	if (!NFW::i()->db->query_build(array('INSERT' => implode(', ', $varnames), 'INTO' => 'logs', 'VALUES' => implode(', ', $values)))) {
    		self::error('Unable to insert log', __FILE__, __LINE__, NFW::i()->db->error());
    		return false;
    	
    	}

        return true;       
    }

	/**
     * Get array with logs
     *
     * @param array	  $options 		Options array:
     * 								'filter'			// Filter array
     * 									'kind'			// Logs with one kind or kind's array
     * 									'posted_from'	// From timestamp
     * 									'posted_to'		// To timestamp 
     * 									'poster'		// User ID or array with ID's
     * 									'message'		// Logs message
     * 									'kind'			// Logs kind
     * 									'IP'			// Poster IP 
     * 								'free_filter'		// Неполное совпадение с фильтром прои поиске
     * 									'IP'			// Poster IP 
     * 								'limit'				// SQL LIMIT
     * 								'offset'			// SQL OFFSET
     * 								'sort_reverse'		// Reverse sorting
     * 
     * @return array(
     * 			logs,				// Array with items 
     * 		   )
     */    
    public static function get($options = array()) {
		$filter = (isset($options['filter'])) ? $options['filter'] : array();

    	// Setup WHERE from filter
    	$where = array();
    	
    	if (isset($filter['posted_from']))
    		$where[] = 'posted > '.intval($filter['posted_from']);
    	
    	if (isset($filter['posted_to']))
    		$where[] = 'posted < '.intval($filter['posted_to']);

    	if (isset($filter['poster']) && $filter['poster']) {
    		if (is_array($filter['poster']))
    			$where[] = 'poster IN ('.join(',',$filter['poster']).')';
    		else
    			$where[] = 'poster = '.intval($filter['poster']);
    	}

        if (isset($filter['poster_username']) && $filter['poster_username']) {
    		if (is_array($filter['poster_username']))
    			$where[] = 'poster_username IN ('.join(', ','\''.$filter['poster_username'].'\'').')';
    		else
    			$where[] = 'poster_username=\''.$filter['poster_username'].'\'';
    	}

    	if (isset($filter['message']))
    		$where[] = 'message = \''.$filter['message'].'\'';
    	
    	if (isset($filter['kind']) && $filter['kind']) {
    		if (is_array($filter['kind']))
    			$where[] = 'kind IN ('.join(',',$filter['kind']).')';
    		else
    			$where[] = 'kind= '.intval($filter['kind']);
    	}

    	if (isset($filter['ip']))
    		$where[] = 'ip = \''.$filter['ip'].'\'';

		$where_str = (count($where)) ? join(' AND ', $where) : '';
		
        // Generate not strong "WHERE"
        if (isset($options['free_filter']) && is_array($options['free_filter'])) {
        	$filter = $options['free_filter'];
	        $foo = array();
	        if (isset($options['free_filter']['ip'])) {
	            $foo[] = 'ip LIKE \'%'.NFW::i()->db->escape($filter['ip']).'%\'';
	        }

			if (!empty($foo)) {
				if ($where_str)
					$where_str .= ' AND ('.join(' OR ', $foo).')';
				else
					$where_str = join(' OR ', $foo);
			}
        }

        // Count filtered values
        $query = array(
        	'SELECT'	=> 'COUNT(*)',
        	'FROM'		=> 'logs',
        	'WHERE'		=> $where_str,
        );
        if (!$result = NFW::i()->db->query_build($query)) {
        	self::error('Unable to count logs', __FILE__, __LINE__, NFW::i()->db->error());
        	return false;
        }
        list($num_filtered) = NFW::i()->db->fetch_row($result);
		if (!$num_filtered) {
			return array(array(), 0);
		}
        
		// ----------------
		// Fetching records
		// ----------------
		$query = array(
			'SELECT'	=> '*',
			'FROM'		=> 'logs',
			'WHERE'		=> $where_str,
			'ORDER BY'  => 'posted'.(isset($options['sort_reverse']) && $options['sort_reverse'] ? ' DESC' : ''),
			'LIMIT' 	=> isset($options['limit']) && $options['limit'] ? intval($options['limit']) : '',
			'OFFSET' 	=> isset($options['offset']) && $options['offset'] ? intval($options['offset']) : '',
		);
        if (!$result = NFW::i()->db->query_build($query)) {
        	self::error('Unable to fetch logs', __FILE__, __LINE__, NFW::i()->db->error());
        	return false;
        }
        if (!NFW::i()->db->num_rows($result)) return false; 
        while ($l = NFW::i()->db->fetch_assoc($result)) {
			$logs[] = $l; 
        }
        
		return array($logs, $num_filtered);
    }
}