<?php
/***********************************************************************
  Copyright (C) 2004-2012 Andrew nyuk Marinov (aka.nyuk@gmail.com)
  $Id$
  
 ************************************************************************/

if (NFW::i()->user['is_blocked']) {
	NFW::i()->stop(NFW::i()->lang['Errors']['Account_disabled'], 'error-page');
}

NFW::i()->setUI('jqueryui');

// Determine module and action
$chapters = explode('/', preg_replace('/(^\/)|(\/$)|(\?.*)|(\/\?.*)/', '', $_SERVER['REQUEST_URI']));
$module = $module_orig = (isset($chapters[1])) ? $chapters[1] : false;

// Module mapping
if (isset(NFW::i()->cfg['module_map'][$module])) {
	$module_orig = $module;
	$module = NFW::i()->cfg['module_map'][$module];
}

$action = (isset($_GET['action'])) ?  $_GET['action'] : 'admin';


if (!$module) {
	if (!NFW::i()->checkPermissions('admin')) {
		NFW::i()->login('form');
	}
}
elseif (class_exists($module)) {
    $CModule = new $module ();
    // Check module_name->action permissions 
    if (!NFW::i()->checkPermissions($module_orig, $action, $CModule)) {
    	NFW::i()->login('form');
    }
	    
    $CModule->path_prefix = 'admin';
    
    NFW::i()->assign('Module', $CModule);
    
    $content = $CModule->action($action);
	if($CModule->error) {
		NFW::i()->stop($CModule->last_msg, $CModule->error_report_type);
	}

    NFW::i()->assign('content', $content);
}
else
	NFW::i()->stop(NFW::i()->lang['Errors']['Bad_request'], 'error-page');

	
// Генерация меню панели администрорования
if (file_exists(PROJECT_ROOT.'include/configs/admin_menu.php')) {
	$ci = array('cat_key' => false, 'key' => false, 'weight' => 0);	// Stored current active item
	
	include(PROJECT_ROOT.'include/configs/admin_menu.php');
	foreach ($admin_menu as $cat_key=>&$category) {
		foreach ($category as $key=>&$i) {
			// External link - skip formating
			if (isset($i['external']) && $i['external']) continue;
	
			// Check permissions
			if (isset($i['perm'])) {
				list($module, $action) = explode(',',$i['perm']);
				if (!NFW::i()->checkPermissions($module, $action)) {
					unset($category[$key]);
					continue;
				}
			}
			
			// Определяем текущий элемент меню учитывая "вес" - размер поля "url" элемента (для более точного соответствия)
			if (strstr($_SERVER['REQUEST_URI'], $i['url']) && $ci['weight'] < strlen($i['url'])) {
				if (isset($admin_menu[$ci['cat_key']][$ci['key']])) {
					$admin_menu[$ci['cat_key']][$ci['key']]['is_active'] = false;
				}
				
				$ci = array('cat_key' => $cat_key, 'key' => $key, 'weight' => strlen($i['url']));
				$i['is_active'] = true;				
			}
			else {
				$i['is_active'] = false;
			}
			
			// Format URL
			$i['url'] = NFW::i()->base_path.'admin/'.$i['url'];
	   	}
	   	if (empty($category)) unset($admin_menu[$cat_key]);
	}
}
else {
	$admin_menu = array();
}

NFW::i()->registerResource('admin');
NFW::i()->registerResource('base');
NFW::i()->assign('admin_menu', $admin_menu);
NFW::i()->display('admin.tpl');