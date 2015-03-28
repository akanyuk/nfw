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
	
	public static function get_browser($user_agent = false) {
		$user_agent = $user_agent ? $user_agent : $_SERVER['HTTP_USER_AGENT']; 
		// Try to determine browser
		if (defined('BROWSCAP_CACHE')) {
			static $CBrowscap = false;
			
			if (!$CBrowscap) {
				require_once NFW_ROOT.'helpers/Browscap.php';
				$CBrowscap = new Browscap(BROWSCAP_CACHE);
				if (isset(NFW::i()->cfg['Browscap']['updateMethod'])) {
					$CBrowscap->updateMethod = NFW::i()->cfg['Browscap']['updateMethod'];
				}
			}
				
				
			$b = $CBrowscap->getBrowser($user_agent);
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
		 
		if ($b = get_browser($user_agent)) {
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
}