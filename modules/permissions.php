<?php
/***********************************************************************
  Copyright (C) 2009-2011 Andrew nyuk Marinov (aka.nyuk@gmail.com)
  $Id$
  
  Админский скрипт для управления правами пользователей.
  
		 	  
 ************************************************************************/

class permissions extends base_module {
	private $roles = array();		// Полный список существующих ролей
	
	public static function expandPermissionsByAliases($permissions) {
		$expanded = array();
		foreach ($permissions as $p) {
			$expanded[] = $p;
			
			if (!class_exists($p['module']) || !isset($p['module']::$action_aliases[$p['action']])) continue;
			
			foreach ($p['module']::$action_aliases[$p['action']] as $alias) {
				$expanded[] = $alias;
			}
		}
		 
		return $expanded;
	}
	
	function __construct() {
		// Load all available roles
		$query = array(
			'SELECT'	=> '*',
			'FROM'		=> 'permissions',
			'ORDER BY'	=> 'role, module, action'
		);
	    if (!$result = NFW::i()->db->query_build($query)) {
	    	$this->error('Unable to fetch permissions', __FILE__, __LINE__, NFW::i()->db->error());
	    	return false;
	    }
    	while($record = NFW::i()->db->fetch_assoc($result)) {
    		$this->roles[$record['role']][] = $record;
    	}
		    	
    	return true;
    }
    
    public function getPermissions($user_id) {
    	$permissions = array();
    	
    	$query = array(
    		'SELECT'	=> 'role',
    		'FROM'		=> 'users_role',
    		'WHERE'		=> 'user_id='.intval($user_id)
    	);
    	if (!$result = NFW::i()->db->query_build($query)) {
    		$this->error('Unable to fetch users roles', __FILE__, __LINE__, NFW::i()->db->error());
    		return false;
    	}
    	while($role = NFW::i()->db->fetch_assoc($result)) {
    		foreach ($this->roles[$role['role']] as $r) {
    			$permissions[] = $r;
    		}
    	}
    	
    	return self::expandPermissionsByAliases($permissions);
    }
    
    public function emptyUserRoles($user_id) {
		$query = array(
			'DELETE'	=> 'users_role',
    		'WHERE'		=> 'user_id='.intval($user_id)
		);
    	if (!$result = NFW::i()->db->query_build($query)) {
    		$this->error('Unable to delete user roles', __FILE__, __LINE__, NFW::i()->db->error());
    		return false;
    	}
    	
    	return true;
    }

    function actionUpdate() {
    	if (!$CUsers = new users($_GET['user_id'])) return false;
    	 
    	$query = array(
    		'SELECT'	=> 'role',
    		'FROM'		=> 'users_role',
    		'WHERE'		=> 'user_id='.intval($_GET['user_id']),
    		'ORDER BY'	=> 'role'
    	);
    	if (!$result = NFW::i()->db->query_build($query)) {
    		$this->error('Unable to fetch user roles', __FILE__, __LINE__, NFW::i()->db->error());
    		return false;
    	}
    	$user_roles = array();
    	while($record = NFW::i()->db->fetch_assoc($result)) {
    		$user_roles[] = $record['role'];
    	}
    	
    	if (empty($_POST)) {
    		NFW::i()->stop($this->renderAction(array(
    			'all_roles' => $this->roles,
    			'user_roles' => $user_roles,
    			'user' => $CUsers->record    		
    		)));
    	}

    	// Empty user's roles
    	$sql = 'DELETE FROM '.NFW::i()->db->prefix.'users_role WHERE user_id='.$CUsers->record['id'];
    	if (!$result = NFW::i()->db->query($sql)) {
    		$this->error('Unable to empty user roles', __FILE__, __LINE__, NFW::i()->db->error());
    		return false;
    	}

    	if (!empty($_POST['roles'])) foreach ($_POST['roles'] as $rolename=>$foo) {
    		if (!isset($this->roles[$rolename])) continue;
    		    		
    		$sql = 'INSERT INTO '.NFW::i()->db->prefix.'users_role (user_id, role) VALUES ('.$CUsers->record['id'].', \''.NFW::i()->db->escape($rolename).'\')';
    		if (!$result = NFW::i()->db->query($sql)) {
    			$this->error('Unable to insert user role', __FILE__, __LINE__, NFW::i()->db->error());
    			return false;
    		}
    	}
    	
    	logs::write('UID='.$CUsers->record['id'], logs::KIND_PERMISSIONS_UPDATE_ROLES);
    	NFW::i()->renderJSON(array('result' => 'success'));
    }
}