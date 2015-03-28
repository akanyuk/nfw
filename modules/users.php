<?php
/***********************************************************************
  Copyright (C) 2009-2012 Andrew nyuk Marinov (aka.nyuk@gmail.com)
  $Id$

  Админский скрипт для управления пользователями.

 ************************************************************************/

class users extends active_record {
	static $action_aliases = array(
		'read' => array(
			array('module' => 'users', 'action' => 'admin'),
		),
		'update' => array(
			array('module' => 'users', 'action' => 'update_password'),
		)
	);

	var $attributes = array(
		'username' => array('desc' => 'Имя', 'type' => 'str', 'required' => true, 'unique' => true, 'minlength' => 2, 'maxlength' => 32),
		'email' => array('desc' => 'E-mail', 'type' => 'email', 'required' => true, 'unique' => true),
		'realname' => array('desc' => 'Полное имя', 'type' => 'str', 'minlength' => 2, 'maxlength' => 200),
		'language'	=> array('desc' => 'Язык', 'type' => 'select', 'options' => array(
			'Russian', 'English'
		)),
		'city' => array('desc' => 'Город', 'type' => 'str', 'minlength' => 2, 'maxlength' => 85),
		'is_blocked' => array('desc' => 'Пользователь заблокирован', 'type' => 'bool'),
		'group_id' => array('desc' => 'Группа', 'type' => 'select', 'options' => array(
			0 => array('id' => 0, 'desc' => 'Без группы')
		)),
	);

	function __construct($record_id = false) {
    	$result = parent::__construct($record_id);

    	// Restore 'module_map' changes
    	if ($this->db_table != 'users' && isset(NFW::i()->cfg['module_map']['users']) && NFW::i()->cfg['module_map']['users'] == $this->db_table) {
    		$this->db_table = 'users';
    	}

    	$this->lang = NFW::i()->getLang('users',1);

    	$this->attributes["country"] = array('desc' => 'Страна', 'type' => 'select', 'options' =>
			$this->lang["CountryList"]
		);


    	return $result;
    }


    // Generates a salted, SHA-1 hash of $str
    public static function hash($str, $salt) {
    	return sha1($salt.sha1($str));
    }

    public static function random_key($len, $readable = false, $hash = false) {
    	$key = '';

    	if ($hash) {
    		$key = substr(sha1(uniqid(rand(), true)), 0, $len);
    	}
    	else if ($readable)	{
    		$chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';

    		for ($i = 0; $i < $len; ++$i)
    			$key .= substr($chars, (mt_rand() % strlen($chars)), 1);
    	}
    	else {
    		for ($i = 0; $i < $len; ++$i) {
    			$key .= chr(mt_rand(33, 126));
    		}
    	}

    	return $key;
    }

    /**
     * Try authentificate user with cookies
     * @return void|boolean
     */
    public function cookie_login() {
    	if (!isset(NFW::i()->cfg['cookie'])) return false;

    	// We assume it's a guest
    	$cookie = array('user_id' => 1, 'password_hash' => 'Guest', 'expiration_time' => 0, 'expire_hash' => 'Guest');

   		$cookie_data = @explode('|', base64_decode($_COOKIE[NFW::i()->cfg['cookie']['name']]));
   		if (!empty($cookie_data) && count($cookie_data) == 4) {
   			list($cookie['user_id'], $cookie['password_hash'], $cookie['expiration_time'], $cookie['expire_hash']) = $cookie_data;
    	}

    	// If this a cookie for a logged in user and it shouldn't have already expired
    	if (intval($cookie['user_id']) <= 1 || intval($cookie['expiration_time']) <= time()) return false;

   		if (!$user = $this->authentificate(intval($cookie['user_id']), $cookie['password_hash'], true)) return false;


   		// We now validate the cookie hash
   		if ($cookie['expire_hash'] !== sha1($user['salt'].$user['password'].self::hash(intval($cookie['expiration_time']), $user['salt']))) return false;

   		// Send a new, updated cookie with a new expiration timestamp
		$this->cookie_update($user);

   		return $user;
    }

    public function cookie_update($cookie) {
    	if (!isset(NFW::i()->cfg['cookie'])) return false;

    	// Send a new, updated cookie with a new expiration timestamp
    	$expire = time() + NFW::i()->cfg['cookie']['expire'];

    	// Enable sending of a P3P header
    	header('P3P: CP="CUR ADM"');

    	if (version_compare(PHP_VERSION, '5.2.0', '>='))
    		setcookie(NFW::i()->cfg['cookie']['name'], base64_encode($cookie['id'].'|'.$cookie['password'].'|'.$expire.'|'.sha1($cookie['salt'].$cookie['password'].self::hash($expire, $cookie['salt']))), $expire, NFW::i()->cfg['cookie']['path'], NFW::i()->cfg['cookie']['domain'], NFW::i()->cfg['cookie']['secure'], true);
    	else
    		setcookie(NFW::i()->cfg['cookie']['name'], base64_encode($cookie['id'].'|'.$cookie['password'].'|'.$expire.'|'.sha1($cookie['salt'].$cookie['password'].self::hash($expire, $cookie['salt']))), $expire, NFW::i()->cfg['cookie']['path'].'; HttpOnly', NFW::i()->cfg['cookie']['domain'], NFW::i()->cfg['cookie']['secure']);

    	return true;
    }

    public function cookie_logout() {
    	$cookie = array('id' => 1, 'password' => 'Guest', 'salt' => 'Guest');
    	// Send a new, updated cookie with a new expiration timestamp
   		return $this->cookie_update($cookie);
    }

    /**
     * Check if given username and password is correct
     * @param $username
     * @param $password
     * @return unknown_type
     */
    public function authentificate($user, $password, $password_is_hash = false) {
		// Get user info matching login attempt
		$query = array(
			'SELECT'	=> '*',
			'FROM'		=> 'users',
			'WHERE' 	=> 'is_group=0 AND username=\''.NFW::i()->db->escape($user).'\''
		);
		$query['WHERE'] = is_int($user) ? 'id='.intval($user) : 'username=\''.NFW::i()->db->escape($user).'\'';
		if (!$result = NFW::i()->db->query_build($query)) {
			$this->error('Search user error', __FILE__, __LINE__, NFW::i()->db->error());
			return false;
		}
		$db_user = NFW::i()->db->fetch_assoc($result);

		if (!$db_user['id'] || ($password_is_hash && $password != $db_user['password']) || (!$password_is_hash && self::hash($password, $db_user['salt']) != $db_user['password'])) return false;

		return $db_user;
    }

	/**
	 * Get two array with users and groups
	 */
	private function getRecords() {
		$query = array(
			'SELECT'	=> '*',
			'FROM'		=> $this->db_table,
			'ORDER BY'	=> 'id'
		);

		if (!$result = NFW::i()->db->query_build($query)) {
			$this->error('Unable to fetch records', __FILE__, __LINE__, NFW::i()->db->error());
			return false;
		}
		if (!NFW::i()->db->num_rows($result)) {
			return array(array(), array());
		}

		$users = $groups = array();
		while($cur_record = NFW::i()->db->fetch_assoc($result)) {
			if ($cur_record['is_group']) {
				$groups[] = $cur_record;
			}
			else {
				$users[] = $cur_record;
			}
		}

		return array($users, $groups);
	}


    protected function save() {
    	if ($this->record['id']) {
    		return parent::save();
    	}

   		$salt = self::random_key(12, true);
		$password_hash = self::hash($this->record['password'], $salt);

		$query = array(
			'INSERT'	=> 'username, realname, language, country, city, email, group_id, password, salt, registered, registration_ip',
			'INTO'		=> $this->db_table,
			'VALUES'	=> '\''.NFW::i()->db->escape($this->record['username']).'\', \''.NFW::i()->db->escape($this->record['realname']).'\', \''.NFW::i()->db->escape($this->record['language']).'\', \''.NFW::i()->db->escape($this->record['country']).'\', \''.NFW::i()->db->escape($this->record['city']).'\', \''.NFW::i()->db->escape($this->record['email']).'\', '.intval($this->record['group_id']).', \''.$password_hash.'\', \''.$salt.'\', '.time().', \''.logs::get_remote_address().'\''
		);
		if (!NFW::i()->db->query_build($query)) {
			$this->error('Unable to insert record', __FILE__, __LINE__, NFW::i()->db->error());
			return false;
		}

		$this->record['id'] = NFW::i()->db->insert_id();
		return true;
    }

   function delete() {
   		$CPermissions = new permissions();
   		if (!$CPermissions->emptyUserRoles($this->record['id'])) {
    		$this->error('Unable to delete user roles', __FILE__, __LINE__);
    		return false;
   		}

   		return parent::delete();
    }

    /**
     * Get array with users
     */
    public function getUsers($options = array()) {
    	$query = array(
    		'SELECT'	=> '*',
    		'FROM'		=> $this->db_table,
    		'WHERE'		=> 'is_group=0',
    		'ORDER BY'	=> 'id'
    	);
    	if (isset($options['group_id'])) {
    		$query['WHERE'] = 'group_id='.intval($options['group_id']);
    	}
    	if (!$result = NFW::i()->db->query_build($query)) {
    		$this->error('Unable to fetch records', __FILE__, __LINE__, NFW::i()->db->error());
    		return false;
    	}
    	if (!NFW::i()->db->num_rows($result)) {
    		return array();
    	}

    	$records = array();
    	while($cur_record = NFW::i()->db->fetch_assoc($result)) {
    		$records[] = $cur_record;
    	}

    	return $records;
    }

    /**
     * Validate user attributes
     *
     * @return array with errors
     */
	function validate($role = 'update') {
    	// Validate password (only for 'update_password')
    	if ($role == 'update_password') {
    		$errors = array();

    		if (strlen($this->record['password']) < 4) {
				$lang_profile = NFW::i()->getLang('profile');
            	$errors['password'] = $this->lang['Errors_password_too_short'];
    		}

	    	if ($this->record['password'] != $this->record['password2']) {
	    		$errors['password'] = $errors['password2'] = $this->lang['Error_passwords_missmatch'];
	    	}

    		return $errors;
    	}

    	$errors = parent::validate($this->record, $this->attributes);

    	// Validate 'unique' values
    	foreach($this->attributes as $varname=>$attribute) {
    		$error_varname = (isset($attribute['desc'])) ? $attribute['desc'] : $varname;

    		if (isset($attribute['unique']) && $attribute['unique'] && isset($this->record[$varname]) && $this->record[$varname]) {
				$query = array(
					'SELECT' 	=> '*',
					'FROM'		=> $this->db_table,
					'WHERE'		=> $varname.'=\''.NFW::i()->db->escape($this->record[$varname]).'\''
				);
    			if ($this->record['id']) {
    				$query['WHERE'] .= ' AND id<>'.$this->record['id'];
    			}
    			if (!$result = NFW::i()->db->query_build($query)) {
    				$this->error('Unable to validate '.$varname, __FILE__, __LINE__, NFW::i()->db->error());
    				return false;
    			}

    			if (NFW::i()->db->num_rows($result)) {
    				$errors[$varname] = $this->lang['Error_dupe1'].$error_varname.$this->lang['Error_dupe2'];
    			}
    		}
    	}

    	// Validate password (only on 'insert')
    	if (($role == 'insert') && strlen($this->record['password']) < 4) {
            $errors['password'] = $this->lang['Errors_password_too_short'];
    	}

        return $errors;
    }

	function actionAdmin() {
		list ($users, $groups) = $this->getRecords();
		foreach ($groups as $g) {
			$this->attributes['group_id']['options'][] = array(
				'id' => $g['id'],
				'desc' => $g['username'],
			);
		}

		return $this->renderAction(array(
			'users' => $users,
			'groups' => $groups
		));
	}

    function actionInsert() {
    	if (empty($_POST)) return false;

		$this->error_report_type = 'active_form';

    	$this->formatAttributes($_POST);
    	$this->record['password'] = $_POST['password'];

    	$errors = $this->validate('insert');
		if (!empty($errors)) {
   			NFW::i()->renderJSON(array('result' => 'error', 'errors' => $errors));
		}

    	$this->save();
    	if ($this->error) {
    		NFW::i()->renderJSON(array('result' => 'error', 'errors' => array('general' => $this->last_msg)));
    	}
   		NFW::i()->renderJSON(array('result' => 'success', 'record_id' => $this->record['id']));
    }

    function actionUpdate() {
        if (!$this->load($_GET['record_id'])) return false;

    	if (empty($_POST)) {
    		list ($foo, $groups) = $this->getRecords();
    		foreach ($groups as $g) {
    			$this->attributes['group_id']['options'][] = array(
    				'id' => $g['id'],
    				'desc' => $g['username'],
    			);
    		}

    		return $this->renderAction();
    	}

    	// Start POST'ing
    	$this->error_report_type = 'active_form';

    	$this->formatAttributes($_POST);
    	$errors = $this->validate();
		if (!empty($errors)) {
   			NFW::i()->renderJSON(array('result' => 'error', 'errors' => $errors));
		}

    	$is_ipdated = $this->save();
    	if ($this->error) {
			NFW::i()->renderJSON(array('result' => 'error', 'errors' => array('general' => $this->last_msg)));
		}
   		NFW::i()->renderJSON(array('result' => 'success', 'is_ipdated' => $is_ipdated));
    }

    function actionUpdatePassword() {
    	$this->error_report_type = 'active_form';
        if (!$this->load($_POST['record_id'])) return false;

    	$this->record['password'] = $this->record['password2'] = $_POST['password'];
    	$errors = $this->validate('update_password');
		if (!empty($errors)) {
   			NFW::i()->renderJSON(array('result' => 'error', 'errors' => $errors));
		}

    	$query = array(
			'UPDATE'	=> $this->db_table,
			'SET'		=> 'password=\''.self::hash($this->record['password'], $this->record['salt']).'\'',
			'WHERE'		=> 'id='.$this->record['id']
		);
		if (!NFW::i()->db->query_build($query)) {
			$this->error('Unable to update users password',__FILE__, __LINE__,  NFW::i()->db->error());
			return false;
		}

		NFW::i()->renderJSON(array('result' => 'success'));
	}

    function actionDelete() {
    	$this->error_report_type = 'plain';
        if (!$this->load($_POST['record_id'])) return false;

        /* Группу пока нельзя удалить
        if ($this->record['is_group']) {
	        $query = array(
	        	'UPDATE'	=> $this->db_table,
	        	'SET'		=> 'group_id=NULL',
	        	'WHERE'		=> 'id='.$this->record['id']
	        );
	        if (!NFW::i()->db->query_build($query)) {
	        	$this->error('Unable to update users groups',__FILE__, __LINE__,  NFW::i()->db->error());
	        	return false;
	        }
        }
        */

		$this->delete();
        NFW::i()->stop();
    }
}