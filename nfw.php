<?php
define('NFW_VERSION', '1.6.2');

// Custom error handler
set_error_handler('_errorHandler');
set_exception_handler('_exceptionHandler');

// For PHP above 5.3.0
date_default_timezone_set('Etc/GMT-4');

// Turn off magic_quotes_runtime
if (get_magic_quotes_runtime())
	@ini_set('magic_quotes_runtime', false);

// Strip slashes from GET/POST/COOKIE (if magic_quotes_gpc is enabled)
if (get_magic_quotes_gpc()) {
	function _stripslashes_array($array) {
		return is_array($array) ? array_map('_stripslashes_array', $array) : stripslashes($array);
	}
	$_GET = _stripslashes_array($_GET);
	$_POST = _stripslashes_array($_POST);
	$_COOKIE = _stripslashes_array($_COOKIE);
}

// Strip out "bad" UTF-8 characters
function _remove_bad_utf8_characters($array) {
	$bad_utf8_chars = array("\0", "\xc2\xad", "\xcc\xb7", "\xcc\xb8", "\xe1\x85\x9F", "\xe1\x85\xA0", "\xe2\x80\x80", "\xe2\x80\x81", "\xe2\x80\x82", "\xe2\x80\x83", "\xe2\x80\x84", "\xe2\x80\x85", "\xe2\x80\x86", "\xe2\x80\x87", "\xe2\x80\x88", "\xe2\x80\x89", "\xe2\x80\x8a", "\xe2\x80\x8b", "\xe2\x80\x8e", "\xe2\x80\x8f", "\xe2\x80\xaa", "\xe2\x80\xab", "\xe2\x80\xac", "\xe2\x80\xad", "\xe2\x80\xae", "\xe2\x80\xaf", "\xe2\x81\x9f", "\xe3\x80\x80", "\xe3\x85\xa4", "\xef\xbb\xbf", "\xef\xbe\xa0", "\xef\xbf\xb9", "\xef\xbf\xba", "\xef\xbf\xbb", "\xE2\x80\x8D");
	return is_array($array) ? array_map('_remove_bad_utf8_characters', $array) : str_replace($bad_utf8_chars, '', $array);
}
$_GET = _remove_bad_utf8_characters($_GET);
$_POST = _remove_bad_utf8_characters($_POST);
$_COOKIE = _remove_bad_utf8_characters($_COOKIE);
$_REQUEST = _remove_bad_utf8_characters($_REQUEST);


class NFW {
	// Site config
	var $cfg;
	
	// 
	var $base_path = '';
	var $absolute_path = '';

	// DB class instance
	var $db = false;
	
	// Current user's profile
	var $user = array();
	
	var $lang = array();
	
	var $include_paths = array();
	
	protected $permissions = null;		//Permissions list for current user

	protected $default_user = array(
		'id' => 1,
		'username' => 'Guest',
		'group_id' => 0,
		'is_guest' => true,
		'is_blocked' => false,
		'language' => 'Russian'
	);
	
	protected $resources_depends = array(
		'jquery.activeForm' => array(
			'copy' => array('jquery.activeForm'),
			'resources' => array(
				'base',
				'jquery.blockUI',
				'jquery.activeForm/jquery.form.min.js',
			),
			'resources:bootstrap' => array(
				'jquery.activeForm/bootstrap.activeForm.js',
				'jquery.activeForm/bootstrap.activeForm.css'
			),
			'resources:jqueryui' => array(
				'jquery.activeForm/jqueryui.activeForm.js',
				'jquery.activeForm/jqueryui.activeForm.css',
				'jquery.uniform'
			),
			'functions' => array('active_field')
		),
		'dataTables' => array(
			'resources' => array('jquery.blockUI', 'jquery.cookie'),
		),
		'ckeditor' => array(
			'copy' => array('ckeditor'),
			'resources' => array('ckeditor/ckedit.js', 'ckeditor/ckeditor.js', 'ckeditor/adapters/jquery.js'),  
		),
	);
	
	// Rendering vars
	protected $_template_var = array();
	
	private $ui = false;				// User interface: jQueryUI, Bootstrap
	
	private $_head_assets = array(); 	// Needfull assets

	private $_start_execution;
	
	private static $_instance;
	
	function __construct($cfg = null) {
		// Record the start time (will be used to calculate the generation time for the page)
		$this->_start_execution = $this->microtime();
				
		self::$_instance = $this;
		
		$this->cfg = $cfg;

		// include paths with order important (modules, templates, controlers, resources)
		$this->include_paths = isset($this->cfg['include_paths']) && !empty($this->cfg['include_paths']) ? $this->cfg['include_paths'] :
		array(
			PROJECT_ROOT.'include/',
			NFW_ROOT.'/'
		);
							
		if (isset($this->cfg['db']['type'])) {
			// Load DB abstraction layer and connect
			require NFW_ROOT.'dblayer/common_db.php';
			$this->db = new DBLayer($this->cfg['db']['host'], $this->cfg['db']['username'], $this->cfg['db']['password'], $this->cfg['db']['name'], $this->cfg['db']['prefix'], $this->cfg['db']['p_connect']);
		}
		
		// base_path, absolute _path
		$page = preg_replace('/(^\/)|(\/$)|(\?.*)|(\/\?.*)/', '', $_SERVER['REQUEST_URI']);
		if ($page) {
			$chapters = explode('/', $page);
			$this->base_path = str_repeat('../', count($chapters));		
		}
		
		$this->absolute_path = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']) ? 'https://' : 'http://').$_SERVER['HTTP_HOST'];
		
		// We need a assets main folder
		if (!file_exists(PROJECT_ROOT.'assets')) {
			mkdir(PROJECT_ROOT.'assets', 0777);
		}
		
		// Authentificate user if possible
		$this->user = $this->default_user;
		$this->lang = $this->getLang('nfw_main');
		$this->login();
		
		// Reload correct langpack
		if ($this->user['language'] != $this->default_user['language']) {
			$this->lang = $this->getLang('nfw_main', true);
		}
	}
	
	/**
	 * @return self instance
	 */
	public static function i() {
		return self::$_instance;
	}

	/**
	 * Start execution
	 * 
	 * @return unknown_type
	 */
	public static function run($cfg = null) {
		$classname = (defined('NFW_CLASSNAME')) ? NFW_CLASSNAME : 'NFW';
		spl_autoload_register(array($classname, 'autoload'));
		
		$FOOBAR = new $classname($cfg);
		
		// Determine controler name
		$controler = 'main';
	
		$page = preg_replace('/(^\/)|(\/$)|(\?.*)|(\/\?.*)/', '', $_SERVER['REQUEST_URI']);
		if ($page) {
			$chapters = explode('/', $page);
			if (isset($chapters[0])) {
				$controler = $chapters[0];
			}
		}
	
		foreach (NFW::i()->include_paths as $path) {
			if (file_exists($path.'controlers/'.$controler.'.php')) {
				require $path.'controlers/'.$controler.'.php';
				NFW::i()->stop();
			}
		}

		foreach (NFW::i()->include_paths as $path) {
			if (file_exists($path.'controlers/main.php')) {
				require $path.'controlers/main.php';
				NFW::i()->stop();
			}
		}
		
		NFW::i()->stop();		
	}
	
	public static function autoload($class_name) {
		foreach (NFW::i()->include_paths as $path) {
		 	if (file_exists($path.'modules/'.$class_name.'.php')) {
				require_once($path.'modules/'.$class_name.'.php');
	    	}
		}
	}

	// By default check permissions for admin's page ($module_id = 1) 
	function checkPermissions($module = 'admin', $action = '', $additional = false) {
		if ($this->permissions === null) {
			$C = new permissions();
			$this->permissions = $C->getPermissions($this->user['id']);
		}		
		
		// Search permission
		foreach ($this->permissions as $p) {
			if ($p['module'] == $module && $p['action'] == $action) return true;
		}
		
		return false;
	}
	
	// Return array with language
	function getLang($lang_name, $force_reload = false) {
	    $lang = 'lang_'.$lang_name;
	
	    global $$lang;
	    if (!empty($$lang) && !$force_reload)
	        return $$lang;

	    foreach ($this->include_paths as $i) {
	    	if (file_exists($i.'lang/'.$this->user['language'].'/'.$lang_name.'.php')) {
	        	include $i.'lang/'.$this->user['language'].'/'.$lang_name.'.php';
	        	return $$lang;
	    	}
	    }
	    
		foreach ($this->include_paths as $i) {
	    	if (file_exists($i.'lang/'.$this->default_user['language'].'/'.$lang_name.'.php')) {
	        	include $i.'lang/'.$this->default_user['language'].'/'.$lang_name.'.php';
	        	return $$lang;
	    	}
	    }
	    
        return false;
	}
	
	// Authenificate user if possible
	function login($action = '') {
		$classname = (isset(NFW::i()->cfg['module_map']['users'])) ? NFW::i()->cfg['module_map']['users'] : 'users';
		$CUsers = new $classname ();
		
		// Logout action
		if ($action == 'logout' || isset($_GET['action']) && $_GET['action'] == 'logout') {
			$CUsers->cookie_logout();
			
			// Делаем редирект, чтобы куки прижились
			// Send no-cache headers
			header('Expires: Thu, 21 Jul 1977 07:30:00 GMT');	// When yours truly first set eyes on this world! :)
			header('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');
			header('Cache-Control: post-check=0, pre-check=0', false);
			header('Pragma: no-cache');		// For HTTP/1.0 compability
			header('Content-type: text/html; charset=utf-8');
			NFW::i()->stop('<html><head><meta http-equiv="refresh" content="0;URL='.$this->absolute_path.'" /></head><body></body></html>');
		}
			
		// Login form action
		if ($action == 'form' || isset($_GET['action']) && $_GET['action'] == 'login') {
			$this->display('login.tpl');
		}
			
		// Auth data send
		if (isset($_POST['login']) && isset($_POST['username']) && isset($_POST['password'])) {
			$form_username = trim($_POST['username']);
			$form_password = trim($_POST['password']);
			unset($_POST['login'], $_POST['username'], $_POST['password']);
			
			if (!$form_username) {
				$this->display('login.tpl');
			}
			
			if (!$account = $CUsers->authentificate($form_username, $form_password)) {
				$this->assign('error', $this->lang['Errors']['Wrong_auth']);
				$this->display('login.tpl');
			}

			$this->user = $account;
			$this->user['is_guest'] = false;
			
			$CUsers->cookie_update($this->user);
			logs::write(logs::KIND_LOGIN);
			
			if (isset($this->cfg['login_redirect']) && $this->cfg['login_redirect']) {
				NFW::i()->stop('<html><head><meta http-equiv="refresh" content="0;URL='.$this->absolute_path.'/'.$this->cfg['login_redirect'].'" /></head><body></body></html>');
			}
			
			return;			
		}
		
		// Cookie login
		if ($account = $CUsers->cookie_login()) {
			$this->user = $account;
			$this->user['is_guest'] = false;
		}

		return;
	}
	
	// Return microtime for execution counting
	function microtime() {
		list($usec, $sec) = explode(' ', microtime());
		return ((float)$usec + (float)$sec);
	}
	
	//PHP функция для обратимого шифрования
	//-------------------------------------
	function encodeStr($str, $seq = '') {
		$salt = isset($this->cfg['encode_str_salt']) ? $this->cfg['encode_str_salt'] : 'yFR84oF5EWqPEDfD';
		$gamma = '';
		while (strlen($gamma) < strlen($str)) {
			$seq = sha1($gamma.$seq.$salt, true);
			$gamma.=substr($seq,0,8);
		}
	
		return $str^$gamma;
	}
	
	function serializeArray($array) {
		return base64_encode(serialize($array));
	}
	
	function unserializeArray($string) {
		$result = unserialize(base64_decode($string));
		if (!$result) $result = array();
		 
		return $result;
	}
	
	// Set COOKIE
	function setCookie($name, $value, $expire = 0) {
		// Enable sending of a P3P header
		header('P3P: CP="CUR ADM"');
	
		if (version_compare(PHP_VERSION, '5.2.0', '>='))
			setcookie($name, $value, $expire, $this->cfg['cookie']['path'], $this->cfg['cookie']['domain'], $this->cfg['cookie']['secure'], true);
		else
			setcookie($name, $value, $expire, $this->cfg['cookie']['path'].'; HttpOnly', $this->cfg['cookie']['domain'], $this->cfg['cookie']['secure']);
	}
	
	function setUI($ui = 'unknown') {
	    // Register UI related resources
	    switch($ui) {
	    	case 'jqueryui':
	    		$this->registerResource(isset($this->cfg['jqueryui_css']) ? $this->cfg['jqueryui_css'] : 'jquery.ui.smoothness', array('atStart' => true));
	    		$this->registerResource('jquery.ui', array('atStart' => true));
	    		$this->registerResource('jquery', array('atStart' => true));
	    		break;
	    	case 'bootstrap':
	    		$this->registerResource(isset($this->cfg['bootstrap_css']) ? $this->cfg['bootstrap_css'] : 'bootstrap.theme', array('atStart' => true));
	    		$this->registerResource('bootstrap', array('atStart' => true));
	    		$this->registerResource('jquery', array('atStart' => true));
	    		break;
	    	default:
	    		$this->stop('Unknown UI: '.$ui);
	    		break;
	    }
	    
	    $this->ui = $ui;
	}
	
	function getUI() {
		return $this->ui;
	}
	
	function registerFunction($function_name = '') {
		if (function_exists($function_name)) return true;
	
		foreach ($this->include_paths as $i) {
			if (file_exists($i.'functions/'.$function_name.'.php')) {
				include($i.'functions/'.$function_name.'.php');
				return true;
			}
		}
		
		return false;
	}
	
	/**
	 * Copy resource from protected storage to `assets`:
	 */
	private function copyResource($path, $_srcRes = null) {
		if (file_exists(PROJECT_ROOT.'assets/'.$path)) return;
		
		if ($_srcRes !== null) {
			if (is_dir($_srcRes)) {
				if (!file_exists(PROJECT_ROOT.'assets/'.$path)) {
					mkdir(PROJECT_ROOT.'assets/'.$path, 0777);
				}
					
				$files  = scandir($_srcRes);
				foreach ($files as $f) {
					if ($f != '.' && $f != '..') {
						$this->copyResource($path.'/'.$f, $_srcRes.'/'.$f);
					}
				}
				return;
			}
				
			if (!file_exists(PROJECT_ROOT.'assets/'.$path)) {
				@unlink(PROJECT_ROOT.'assets/'.$path);
				@copy($_srcRes, PROJECT_ROOT.'assets/'.$path);
				@touch(PROJECT_ROOT.'assets/'.$path, filemtime($_srcRes));
				clearstatcache();
			}
				
			return;
		}
	
		foreach ($this->include_paths as $i) {
			if (file_exists($i.'resources/'.$path)) {
				$this->copyResource($path, $i.'resources/'.$path);
			}
		}
	}
		
	/**
	 * Available options:
	 *  'atStart' 		- register resource maximum top of head
	 *  'skipDepends' 	- do not register depended resources 
	 */
	function registerResource($path, $options = array(), $_srcRes = null) {
		$atStart = isset($options['atStart']) && $options['atStart'] ? true : false;
		$skipDepends = isset($options['skipDepends']) && $options['skipDepends'] ? true : false;
		
		if (!$skipDepends) {
			$ui = $this->getUI();
			
			if (isset($this->resources_depends[$path]['resources'])) {
				foreach ($this->resources_depends[$path]['resources'] as $r) $this->registerResource($r, array('atStart' => $atStart, 'skipDepends' => $skipDepends));
			}
				
			if (isset($this->resources_depends[$path]['resources:'.$ui])) {
				foreach ($this->resources_depends[$path]['resources:'.$ui] as $r) $this->registerResource($r, array('atStart' => $atStart, 'skipDepends' => $skipDepends));
			}
			
			if (isset($this->resources_depends[$path]['functions'])) {
				foreach ($this->resources_depends[$path]['functions'] as $f) $this->registerFunction($f, array('atStart' => $atStart, 'skipDepends' => $skipDepends));
			}
			
			if (isset($this->resources_depends[$path]['functions:'.$ui])) {
				foreach ($this->resources_depends[$path]['functions:'.$ui] as $f) $this->registerFunction($f, array('atStart' => $atStart, 'skipDepends' => $skipDepends));
			}
			
			if (isset($this->resources_depends[$path]['copy'])) {
				foreach ($this->resources_depends[$path]['copy'] as $f) $this->copyResource($f);
				if (in_array($path, $this->resources_depends[$path]['copy'])) return;
			}
		}
		
		if ($_srcRes !== null) {
			if (in_array($path, $this->_head_assets)) return;
			
			if (is_dir($_srcRes)) {
				if (!file_exists(PROJECT_ROOT.'assets/'.$path)) {
					mkdir(PROJECT_ROOT.'assets/'.$path, 0777);
				}
			
				$files  = scandir($_srcRes);
				foreach ($files as $f) {
					if ($f != '.' && $f != '..') {
						$this->registerResource($path.'/'.$f, array('atStart' => $atStart, 'skipDepends' => $skipDepends), $_srcRes.'/'.$f);
					}
				}
				return;
			}
			
			if (!file_exists(PROJECT_ROOT.'assets/'.$path) || abs(filemtime($_srcRes) - filemtime(PROJECT_ROOT.'assets/'.$path)) > 3600) {
				@unlink(PROJECT_ROOT.'assets/'.$path);
				@copy($_srcRes, PROJECT_ROOT.'assets/'.$path);
				@touch(PROJECT_ROOT.'assets/'.$path, filemtime($_srcRes));
				clearstatcache();
			}
			
			if ($atStart) {
				array_unshift($this->_head_assets, $path);
			}
			else {
				$this->_head_assets[] = $path;
			}
			
			return;			
		}
		
		foreach ($this->include_paths as $i) {
			if (file_exists($i.'resources/'.$path)) {
				$this->registerResource($path, array('atStart' => $atStart, 'skipDepends' => $skipDepends), $i.'resources/'.$path);
			}
		}
	}
	
	/**
	 * Create assets resource (css, js, img) if not exists
	 * and return path to it
	 * 
	 * @param string 	Requested filename path
	 * @param boolean 	Echo result if false. Otherwise result returned as string to paste in <head>
	 * @return string	Real path
	 */
	function assets($path = '', $post_process = false) {
		if(!file_exists(PROJECT_ROOT.'assets/'.$path)) {
			if (strstr($path, '/')) {
				$first_dir = reset(explode('/', $path));
				$this->registerResource($first_dir);
			}
			else {			
				$this->registerResource($path);
			}
		}
		
		$result = $this->absolute_path.'/assets/'.$path;
		
		if (!$post_process) return $result;
		
		$ext = strtolower(end(explode('.', $path)));
		if ($ext == 'js') {
			return '<script src="'.$result.'" type="text/javascript"></script>';
		}
		elseif ($ext == 'css') {
			return '<link href="'.$result.'" type="text/css" rel="stylesheet" media="screen" />';
		}
		elseif (strstr($path, 'favicon.ico') || strstr($path, 'favicon.png')) {
			return '<link href="'.$result.'" rel="shortcut icon" />';
		}
		else
			return false;
	}
		
	function renderJSON($data, $_reqursive = null) {
		if ($_reqursive !== null) {
			$result = '{'."\n";
			foreach ($data as $key=>$value) {
				if (is_array($value)) {
					$result .= '"'.$key.'": '.self::renderJSON($value, true).',';
				}
				else {
					$result .= '"'.$key.'": "'.addslashes($value).'",';
				}
			}
			return substr($result, 0, -1)."\n".'}';
				
		}
		
		$result = $this->renderJSON($data, true);
		// wrap json in a textarea if the request did not come from xhr 
		if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest') {
			$this->stop($result); 
		} 
		else {
			$this->stop('<textarea>'.$result.'</textarea>');
		}		
	}
		
	public function assign($name, $value) {
		$this->_template_var[$name] = $value;
	}

	public function fetch($template_file) {
		if (!file_exists($template_file)) return false;

		extract($this->_template_var);
		ob_start();
		include($template_file);
		return ob_get_clean();
	}
	
	function display($tpl, $is_prerendered_content = false) {
	    $content = ($is_prerendered_content) ? strval($tpl) : $this->fetch($this->findTemplatePath($tpl));

	    // Check if jQuery required by template (only for normal output
	    if (!$is_prerendered_content && (strstr($content,'$(document).ready') || strstr($content,'$(function()'))) {
	    	$this->registerResource('jquery', array('atStart' => true));
	    }
	     
	    if (defined('NFW_SEPARATED_RESOURCES')) {
		    foreach(array_unique(array_reverse($this->_head_assets)) as $filename) {
		    	if ($cur_assets = $this->assets($filename, true)) {
	    			$content = str_ireplace('<head>', '<head>'."\n".$cur_assets, $content);
		    	}
		    }
	    }
		else {
		    $full_js = $full_css = '';
		    foreach(array_unique($this->_head_assets) as $filename) {
		    	switch (strtolower(end(explode('.', $filename)))) {
		    		case 'js':
		    			$full_js .= "\n".file_get_contents(PROJECT_ROOT.'assets/'.$filename);
		    			break;
		    		case 'css':
		    			// FIX paths in css
		    			$foo = pathinfo($filename);
		    			$dirname = $foo['dirname'];
		    			
		    			$css_content = file_get_contents(PROJECT_ROOT.'assets/'.$filename);
		    			$css_content = str_replace('url("', 'url("'.$dirname.'/', $css_content);
		    			$css_content = str_replace('url(\'', 'url(\''.$dirname.'/', $css_content);
		    			
		    			// Return back inline images
		    			$css_content = str_replace($dirname.'/data:', 'data:', $css_content);
		    			
		    			$full_css .= "\n".$css_content;
		    			break;
		    		default:
		    			if ($cur_assets = $this->assets($filename, true)) {
		    				$content = str_ireplace('<head>', '<head>'."\n".$cur_assets, $content);
		    			}
		    	}
		    }
		    
		    $full_js_filename = md5($full_js).'.js';
		    if(!file_exists(PROJECT_ROOT.'assets/'.$full_js_filename)) {
		    	file_put_contents(PROJECT_ROOT.'assets/'.$full_js_filename, $full_js);
		    }
		    $content = str_ireplace('<head>', '<head>'."\n".'<script src="'.$this->absolute_path.'/assets/'.$full_js_filename.'" type="text/javascript"></script>', $content);
	
		    $full_css_filename = md5($full_css).'.css';
		    if(!file_exists(PROJECT_ROOT.'assets/'.$full_css_filename)) {
		    	file_put_contents(PROJECT_ROOT.'assets/'.$full_css_filename, $full_css);
		    }
		    $content = str_ireplace('<head>', '<head>'."\n".'<link href="'.$this->absolute_path.'/assets/'.$full_css_filename.'" type="text/css" rel="stylesheet" media="screen" />', $content);
		}
	     
		if (defined('NFW_LOG_GENERATED_TIME') && class_exists('FB')) {
		    // Calculate script generation time
		    FB::info('Generated in '.sprintf('%.3f', $this->microtime() - $this->_start_execution).' seconds, '.$this->db->get_num_queries().' queries executed');
		}
	    
		if (defined('NFW_LOG_QUERIES') && class_exists('FB')) {
			FB::info('Executed queries:');
			foreach ($this->db->saved_queries as $q) {
				FB::info($q[0], $q[1].' sec');
			}
		}
		
	    // If a database connection was established (before this error) we close it
	    if ($this->db) $this->db->close();		
	    exit (trim($content));
	}

    function findTemplatePath($filename, $class = '', $controler = '') {
    	$path = str_replace('//', '/', $class.'/'.$controler.'/'.$filename);
    	foreach ($this->include_paths as $i) {
    		if (file_exists($i.'templates/'.$path)) {
    			return $i.'templates/'.$path;
    		}
    	}

    	// Try to find template without $controler subfolder
        $path = str_replace('//', '/', $class.'/'.$filename);
    	foreach ($this->include_paths as $i) {
    		if (file_exists($i.'templates/'.$path)) {
    			return $i.'templates/'.$path;
    		}
    	}
    	    	
    	// Try to find template of parent class
    	if ($parent_class = get_parent_class($class)) {
    		return $this->findTemplatePath($filename, $parent_class, $controler);
    	}
    
    	return false;
    }
	
	
	function stop($message = '', $output = null) {
		switch ($output) {
			case 'silent':
				$message = '';
				break;
			case 'xml':
				header ("Content-Type:text/xml");
				break;
			case 'login':
				$this->assign('error', $message);
				NFW::i()->login();
				return;
			case 'alert':
				$message = '<html><script type="text/javascript">alert("'.$message.'");</script></html>';
				break;
			case 'active_form':
				NFW::i()->renderJSON(array('result' => 'error', 'errors' => array('general' => $message)));
				break;
			case 'error-page':
				NFW::i()->assign('page', array(
					'subject' => 'Ошибка',
					'message' => '<strong>'.$message.'</strong>'
				));
				NFW::i()->display('main.tpl');
				break;
			case 'standalone':
				NFW::i()->display($message, true);
				break;
			default:
				break;
		}

		if (defined('NFW_LOG_GENERATED_TIME') && class_exists('FB')) {
		    // Calculate script generation time
		    FB::info('Generated in '.sprintf('%.3f', $this->microtime() - $this->_start_execution).' seconds'.($this->db ? ', '.$this->db->get_num_queries().' queries executed' : '.'));
		}
		
		if (defined('NFW_LOG_QUERIES')) {
			FB::info('Executed queries:');
			foreach ($this->db->saved_queries as $q) {
				FB::info($q[0], $q[1].' sec');
			}
		}
		
	    if ($this->db) $this->db->close();
	    
	    exit ($message);
	}
	
	function errorHandler($error_number, $message, $file, $line, $db_error = false) {
		if (class_exists('FB')) {
			FB::group('Error: '.$message,array('Collapsed' => true,'Color' => '#FF0000'));
			FB::info($file, 'File');
			FB::info($line, 'Line');
			if (isset($db_error['error_msg'])) {
				FB::info($db_error['error_msg'], 'Database reported');
				if (isset($db_error['error_sql']) && $db_error['error_sql'] != '') {
					FB::info($db_error['error_sql'], 'Failed query');
				}
			}
			FB::groupEnd();
		}
	
		return true;
	}	
}

function _exceptionHandler($exception) {
	return NFW::i()->errorHandler(0, $exception->getMessage(), $exception->getFile(), $exception->getLine());
}

function _errorHandler($error_number, $message, $file, $line, $db_error = false) {
	if ($error_number && (!(error_reporting() & $error_number))) {
		// This error code is not included in error_reporting
		return true;
	}
	
	return NFW::i()->errorHandler($error_number, $message, $file, $line, $db_error);
}