<?php
/***********************************************************************
  Copyright (C) 2009-2013 Andrew nyuk Marinov (aka.nyuk@gmail.com)
  $Id$
  
  Модуль работы с мультимедией (фото, файлы).
  
					
 ************************************************************************/

class media extends active_record {
	const LOGS_MEDIA_UPLOAD = 20;
	const LOGS_MEDIA_REMOVE = 21;
	const LOGS_MEDIA_RELOAD = 22;
	const LOGS_MEDIA_UPDATE_COMMENT = 23;
	
	const NUM_CACHED = 5;					// Number of caches of image with various size
	const CACHE_PATH = 'var/images_cache/';	// Path to images cache
	const JPEG_QUALITY = 100;				// JPEG creation quality (100 - best)

	const MAX_FILE_SIZE = 2097152;			// Default MAX_FILE_SIZE # 2Mb
	const MAX_SESSION_SIZE = 10485760;		// Default MAX_SESSION_SIZE # 10Mb
	
	private $storage_path 		 = 'media';				// Path for 'filesystem' storage
	private $secure_storage_path = 'var/protected_media';
	
	protected $session = array();			// Current opened session
	
	function __construct($options = false) {
		$this->storage_path = isset(NFW::i()->cfg['media']['storage_path']) ? NFW::i()->cfg['media']['storage_path'] : $this->storage_path;
		$this->secure_storage_path = isset(NFW::i()->cfg['media']['secure_storage_path']) ? NFW::i()->cfg['media']['secure_storage_path'] : $this->secure_storage_path;
		
		if (!is_array($options)) {
			parent::__construct($options);
			return;
		}
		
		parent::__construct();
	}
	
	private function cachePrefix($record) {
		return substr(md5($record['id']), 0, 15);
	}
	
	private function loadData(&$record) {
		if (!file_exists($record['fullpath'])) {
			$this->error('File not found in storage', __FILE__, __LINE__);
			return false;
		}
	
		ob_start();
		readfile($record['fullpath']);
		$record['data'] = ob_get_clean();
	
		return true;
	}

	private function removeTemporaryFiles($owner_class) {
		$query = array(
			'SELECT'	=> 'id, basename, owner_class, secure_storage',
			'FROM'		=> $this->db_table,
			'WHERE' 	=> 'session_id=\''.$this->calculateSessionId($owner_class).'\''
		);
		if (!$result = NFW::i()->db->query_build($query)) {
			$this->error('Unable to fetch temporary files', __FILE__, __LINE__, NFW::i()->db->error());
			return false;
		}
		if (!NFW::i()->db->num_rows($result)) return true;
	
		while($record = NFW::i()->db->fetch_assoc($result)) {
			NFW::i()->db->query_build(array('DELETE' => $this->db_table, 'WHERE' => 'id='.$record['id']));
			
			$this->removeFile($record);
		}
	
		return true;
	}
		
	private function removeFile($record) {
		if ($record['secure_storage']) {
			@unlink(PROJECT_ROOT.$this->secure_storage_path.'/'.$record['id']);
		}
		else {
			@unlink(PROJECT_ROOT.$this->storage_path.'/'.$record['owner_class'].'/'.$record['basename']);
		}
		
		// remove cached thumbnails
		$prefix = $this->cachePrefix($record);
		for ($i = 1; $i <= self::NUM_CACHED; $i++) {
			@unlink(PROJECT_ROOT.self::CACHE_PATH.$prefix.$i);
		}
	}
	
	protected function formatRecord($record) {
		$lang_media = NFW::i()->getLang('media');
		
		// Filesize str
		if ($record['filesize'] >= 1048576)
			$record['filesize_str'] = number_format($record['filesize']/1048576, 2, '.', ' ').$lang_media['mb'];
		elseif ($record['filesize'] >= 1024)
			$record['filesize_str'] = number_format($record['filesize']/1024, 2, '.', ' ').$lang_media['kb'];
		else
			$record['filesize_str'] = $record['filesize'].$lang_media['b'];

		$path_parts = pathinfo($record['basename']);
		$record['filename'] = $path_parts['filename'];
		$record['extension'] = $path_parts['extension'];
		
		// icons
		NFW::i()->registerResource('icons');
		$record['icons']['16x16'] = file_exists(PROJECT_ROOT.'assets/icons/16x16/mimetypes/'.$record['extension'].'.png') ? NFW::i()->absolute_path.'/assets/icons/16x16/mimetypes/'.$record['extension'].'.png' : NFW::i()->absolute_path.'/assets/icons/16x16/mimetypes/unknown.png';
		$record['icons']['32x32'] = file_exists(PROJECT_ROOT.'assets/icons/32x32/mimetypes/'.$record['extension'].'.png') ? NFW::i()->absolute_path.'/assets/icons/32x32/mimetypes/'.$record['extension'].'.png' : NFW::i()->absolute_path.'/assets/icons/32x32/mimetypes/unknown.png';
		$record['icons']['64x64'] = file_exists(PROJECT_ROOT.'assets/icons/64x64/mimetypes/'.$record['extension'].'.png') ? NFW::i()->absolute_path.'/assets/icons/64x64/mimetypes/'.$record['extension'].'.png' : NFW::i()->absolute_path.'/assets/icons/64x64/mimetypes/unknown.png';
		
		// mime_type
		$mimetypes = array(
			"pdf"=>"application/pdf",
			"exe"=>"application/octet-stream",
			"zip"=>"application/zip",
			"doc"=>"application/msword",
			"xls"=>"application/vnd.ms-excel",
			"ppt"=>"application/vnd.ms-powerpoint",
			"gif"=>"image/gif",
			"png"=>"image/png",
			"jpeg"=>"image/jpg",
			"jpg"=>"image/jpg",
			"mp3"=>"audio/mpeg",
			"wav"=>"audio/x-wav",
			"ogg"=>"audio/ogg",
			"mpeg"=>"video/mpeg",
			"mpg"=>"video/mpeg",
			"mpe"=>"video/mpeg",
			"mov"=>"video/quicktime",
			"avi"=>"video/x-msvideo",
			"css"=>"text/css",
			"php"=>"text/plain",
			"htm"=>"text/plain",
			"html"=>"text/plain",
			"tpl"=>"text/plain",
			"txt"=>"text/plain"
		);
		$lext = strtolower($record['extension']);
		$record['mime_type'] = isset($mimetypes[$lext]) ? $mimetypes[$lext] : 'application/force-download';
		list($record['type'], $foo) = explode('/',$record['mime_type']);
		
		$record['url'] = $record['secure_storage'] ? NFW::i()->absolute_path.'/'.get_class($this).'/_protected/'.$record['owner_class'].'/'.$record['id'].'/'.$record['basename'] : NFW::i()->absolute_path.'/'.$this->storage_path.'/'.$record['owner_class'].'/'.$record['basename']; 
		$record['fullpath'] = $record['secure_storage'] ? PROJECT_ROOT.'/'.$this->secure_storage_path.'/'.$record['id'] : PROJECT_ROOT.$this->storage_path.'/'.$record['owner_class'].'/'.$record['basename'];

		if ($record['type'] == 'image') {
			$record['tmb_prefix'] = $record['secure_storage'] ? NFW::i()->absolute_path.'/'.get_class($this).'/_protected/'.$record['owner_class'].'/'.$record['id'].'/_tmb' : NFW::i()->absolute_path.'/'.$this->storage_path.'/'.$record['owner_class'].'/'.$record['filename'].'_tmb';
			$record['cache_prefix'] = $this->cachePrefix($record);
		}
				
		return $record;
	}
	
	protected function calculateSessionId($owner_class, $owner_id = 0) {
		return 'm'.substr(md5($_SERVER['REMOTE_ADDR'].$owner_class.$owner_id.NFW::i()->user['id']),5,15);
	}
		
	protected function upload($file, $params = array()) {
		$lang_media = NFW::i()->getLang('media');
	
		// Make sure the upload went smooth
		if ($file['error']) switch ($file['error']) {
			case 1: // UPLOAD_ERR_INI_SIZE
			case 2: // UPLOAD_ERR_FORM_SIZE
				$this->error($lang_media['Errors']['Ambigious_file'], __FILE__, __LINE__);
				return false;
			case 3: // UPLOAD_ERR_PARTIAL
				$this->error($lang_media['Errors']['Partial_Upload'], __FILE__, __LINE__);
				return false;
			case 4: // UPLOAD_ERR_NO_FILE
				$this->error($lang_media['Errors']['No_File'], __FILE__, __LINE__);
				return false;
			default:
				// No error occured, but was something actually uploaded?
				if ($file['size'] == 0) {
					$this->error($lang_media['Errors']['No_File'], __FILE__, __LINE__);
					return false;
				}
				break;
		}
	
		if (!is_uploaded_file($file['tmp_name'])) {
			$this->error($lang_media['Errors']['Unknown'], __FILE__, __LINE__);
			return false;
		}
	
		if ($file['size'] > $this->session['MAX_FILE_SIZE']) {
			$this->error($lang_media['Errors']['File_too_big1'].$this->session['MAX_FILE_SIZE'].$lang_media['Errors']['File_too_big2'], __FILE__, __LINE__);
			return false;
		}
	
		if (isset($this->session['images_only']) && $this->session['images_only']) {
			$size = getimagesize($file['tmp_name']);
			if (!in_array($size['mime'], array('image/gif','image/png','image/jpeg'))) {
				$this->error($lang_media['Errors']['Wrong_image_type'], __FILE__, __LINE__);
				return false;
			}
				
			if (isset($this->session['image_max_x']) && $size[0] > $this->session['image_max_x']) {
				$this->error($lang_media['Errors']['Wrong_image_size'], __FILE__, __LINE__);
				return false;
			}
	
			if (isset($this->session['image_max_y']) && $size[1] > $this->session['image_max_y']) {
				$this->error($lang_media['Errors']['Wrong_image_size'], __FILE__, __LINE__);
				return false;
			}
		}
	
		if (isset($this->session['single_upload']) && $this->session['single_upload']) {
			// Only one file for each owner allowed
			if ($this->session['owner_id']) {
				foreach ($this->getFiles($this->session['owner_class'], $this->session['owner_id']) as $f) {
					if (!$this->load($f['id'])) continue;
					$this->delete();
				}
			}
			else {
				$this->removeTemporaryFiles($this->session['owner_class']);
			}
		}
	
		// Safetly filename
		if (isset($this->session['safe_filenames']) && $this->session['safe_filenames']) {
			$this->record['basename'] =  str_replace(
				array(' ', 'а','б','в','г','д','е','ё','ж','з','и','й','к','л','м','н','о','п','р','с','т','у','ф','х','ц','ч','ш','щ','ъ','ы','ь','э','ю','я'),
				array('_', 'a','b','v','g','d','e','e','zh','z','i','j','k','l','m','n','o','p','r','s','t','u','f','h','c','ch','sh','sch','','y','','e','yu','ya'),
				mb_convert_case($file['name'], MB_CASE_LOWER, 'UTF-8'));
		}
		else {
			$this->record['basename'] = $file['name'];
		}
		
		if (!$this->session['secure_storage'] && file_exists(PROJECT_ROOT.$this->storage_path.'/'.$this->session['owner_class'].'/'.$this->record['basename'])) {
			if (isset($this->session['force_overwrite']) && $this->session['force_overwrite']) {
				@unlink(PROJECT_ROOT.$this->storage_path.'/'.$this->session['owner_class'].'/'.$this->record['basename']);
			}
			elseif(isset($this->session['force_rename']) && $this->session['force_rename']) {
				$target_dir = PROJECT_ROOT.$this->storage_path.'/'.$this->session['owner_class'].'/';
		
				$path_parts = pathinfo($this->record['basename']);
				$filename = $path_parts['filename'];
				$extension = isset($path_parts['extension']) ? '.'.$path_parts['extension'] : '';
				$name_postfix = 0;
				while (file_exists($target_dir.$filename.'_'.$name_postfix.$extension)) {
					$name_postfix++;
				}
				
				$this->record['basename'] = $filename.'_'.$name_postfix.$extension;
			}
			else {
				$this->error($lang_media['Errors']['File_Exists'], __FILE__, __LINE__);
				return false;
			}
		}
		
		if (!$this->record['id']) {
			$this->record['filesize'] = $file['size'];
			
			// Determine comment
			$path_parts = pathinfo($file['name']);
			$this->record['comment'] = isset($params['comment']) ? $params['comment'] : $path_parts['filename'];
				
			if (!$this->save()) return false;
		}

		$target_file = $this->session['secure_storage'] ? PROJECT_ROOT.$this->secure_storage_path.'/'.$this->record['id'] : PROJECT_ROOT.$this->storage_path.'/'.$this->session['owner_class'].'/'.$this->record['basename'];
		if (!move_uploaded_file(urldecode($file['tmp_name']), $target_file)) {
			$this->error($lang_media['Errors']['Move_Error'], __FILE__, __LINE__);
			return false;
		}
				
		$this->reload();
		return true;
	}

	protected function loadSession($owner_class, $owner_id = 0) {
		$session_id = $this->calculateSessionId($owner_class, $owner_id);
	
		if (!isset($_COOKIE[$session_id]) || $_COOKIE[$session_id] == null) {
			$this->error('Media session not found.', __FILE__, __INE__);
			return false;
		}
	
		$cookie_data = unserialize(NFW::i()->encodeStr(base64_decode($_COOKIE[$session_id])));
		if (!isset($cookie_data['MAX_FILE_SIZE']) || !isset($cookie_data['MAX_SESSION_SIZE'])) {
			$this->error('Incorrect media session.', __FILE__, __INE__);
			return false;
		}
		else {
			$this->session = $cookie_data;
			return $session_id;
		}
	}
	
	protected function load($id) {
		if (is_array($id) && isset($id['owner_class']) && isset($id['basename'])) {
			// Load by `owner_class` && `basename`
			if (!$result = NFW::i()->db->query_build(array('SELECT' => '*', 'FROM' => $this->db_table, 'WHERE' => 'owner_class=\''.NFW::i()->db->escape($id['owner_class']).'\' AND basename=\''.NFW::i()->db->escape($id['basename']).'\''))) {
				$this->error('Unable to fetch record', __FILE__, __LINE__, NFW::i()->db->error());
				return false;
			}
			if (!NFW::i()->db->num_rows($result)) {
				$this->error('Record not found.', __FILE__, __LINE__);
				return false;
			}
			$this->db_record = $this->record = NFW::i()->db->fetch_assoc($result);
		}
		elseif (!parent::load($id)) {
			return false;
		}
		
		//  Check permissions
		if ($this->record['secure_storage']) {
			if ($this->record['owner_id']) {
				// Permanent file
				if (!NFW::i()->checkPermissions(preg_replace('/\|.*/', '', $this->record['owner_class']), 'media_get', $this->record['owner_id'])) {
					$this->error('Permissions denied', __FILE__, __LINE__);
					return false;
				}
			}
			else {
				// Temporary file
				if ($this->record['session_id'] != $this->calculateSessionId($this->record['owner_class'])) {
					$this->error('Wrong session_id', __FILE__, __LINE__);
					return false;
				}
			}
		}
	
		$this->record = $this->formatRecord($this->record);
		if (!file_exists($this->record['fullpath'])) {
			$this->error('File not found in storage', __FILE__, __LINE__);
			return false;
		}
		
		return $this->record;
	}

	protected function save() {
		if ($this->record['id']) {
			return parent::save();
		}
			
		if (isset($this->session['owner_id']) && $this->session['owner_id']) {
			$query = array(
				'INSERT'	=> 'owner_class, owner_id, secure_storage, basename, filesize, comment, posted_by, posted_username, poster_ip, posted',
				'INTO'		=> $this->db_table,
				'VALUES'	=> '\''.NFW::i()->db->escape($this->session['owner_class']).'\', '.$this->session['owner_id'].', '.intval($this->session['secure_storage']).', \''.NFW::i()->db->escape($this->record['basename']).'\', '.$this->record['filesize'].', \''.NFW::i()->db->escape($this->record['comment']).'\', '.NFW::i()->user['id'].', \''.NFW::i()->db->escape(NFW::i()->user['username']).'\', \''.logs::get_remote_address().'\','.time()
			);
		}
		else {
			$query = array(
				'INSERT'	=> 'session_id, owner_class, secure_storage, basename, filesize, comment, posted_by, posted_username, poster_ip, posted',
				'INTO'		=> $this->db_table,
				'VALUES'	=> '\''.$this->calculateSessionId($this->session['owner_class']).'\', \''.NFW::i()->db->escape($this->session['owner_class']).'\', '.intval($this->session['secure_storage']).', \''.NFW::i()->db->escape($this->record['basename']).'\', '.$this->record['filesize'].', \''.NFW::i()->db->escape($this->record['comment']).'\', '.NFW::i()->user['id'].', \''.NFW::i()->db->escape(NFW::i()->user['username']).'\', \''.logs::get_remote_address().'\','.time()
			);
		}
		if (!NFW::i()->db->query_build($query)) {
			$this->error('Unable to insert record.', __FILE__, __LINE__, NFW::i()->db->error());
			return false;
		}
		
		$this->record['id'] = NFW::i()->db->insert_id();
		return true;
	}
		
	public function insertFromString($data, $params) {
		if (!isset($params['owner_class'])) {
			$this->error('Missing `owner_class` during insertFromString', __FILE__, __LINE__);
			return false;
		}
		
		if (!isset($params['owner_id'])) {
			$this->error('Missing `owner_id` during insertFromString', __FILE__, __LINE__);
			return false;
		}

		if (!isset($params['basename'])) {
			$this->error('Missing `basename` during insertFromString', __FILE__, __LINE__);
			return false;
		}
		
		$this->session = array(
			'owner_class' => $params['owner_class'],
			'owner_id' => $params['owner_id'],
			'secure_storage' => isset($params['secure_storage']) && $params['secure_storage'] ? true : false);

		// Фикс неправильной работы strlen (возвращает количество символов вместо количества байт, расхождение при UTF-8)
		mb_internal_encoding("iso-8859-1");
		
		$this->record = array(
			'id' => false,
			'basename' => $params['basename'],
			'comment' => isset($params['comment']) ? $params['comment'] : '',
			'filesize' => mb_strlen($data));
		if (!$this->save()) return false;
		
		$target_file = $this->session['secure_storage'] ? PROJECT_ROOT.$this->secure_storage_path.'/'.$this->record['id'] : PROJECT_ROOT.$this->storage_path.'/'.$this->session['owner_class'].'/'.$this->record['basename'];
		if (file_exists($target_file)) {
			$lang_media = NFW::i()->getLang('media');
			$this->error($lang_media['Errors']['File_Exists'], __FILE__, __LINE__);
			return false;
		}
		
		$fp = fopen($target_file, 'w');
		fwrite($fp, $data);
		fclose($fp);		
		
		return true;
	}
	
	public function delete() {
		$record = $this->record;
		if (!parent::delete()) return false;
		
		$this->removeFile($record);
		return true;
	}
		
	public function reload($id = false, $options = array()) {
		if (!$this->load($id ? $id : $this->record['id'])) {
			return false;
		}
		
		if (isset($options['load_data']) && $options['load_data']) {
			if (!$this->loadData($this->record)) {
				return false;
			}
		}
		 
		return $this->record;
	}
		
	/* Available options:
	 * owner_class		string 	required!
	 * owner_id			int 	if not set, required `closeSession` triggering for save temporary files
	 * secure_storage	bool	store files secure or not
	 * allow_reload		bool	allow reload file or not
	 * lazy_load		bool	lazy load files list (external triggering) or not
	 * preload_media	bool	load media list or not
	 * single_upload	bool	one owner - one file
	 * safe_filenames	bool	rename russian filenames
	 * force_overwrite	bool	owerwrite exists files
	 * force_rename		bool	rename new file if exists 
	 * images_only		bool 	only images (png, jpg, gif)
	 * image_max_x		int
	 * image_max_y		int
	 * MAX_FILE_SIZE	int
	 * MAX_SESSION_SIZE	int 
	 */
	public function openSession($options, $form_data = array()) {
		// Try open session
		if (!isset($options['owner_class'])) {
			$this->error('`owner_class` - required parameter', __FILE__, __LINE__);
			return false;
		}
		$options['owner_id'] = isset($options['owner_id']) ? $options['owner_id'] : 0;
		$options['secure_storage'] = isset($options['secure_storage']) && $options['secure_storage'] ? true : false;
		
		if (!$options['secure_storage'] && !file_exists(PROJECT_ROOT.$this->storage_path.'/'.$options['owner_class'])) {
			$this->error('Storage path not found: '.PROJECT_ROOT.$this->storage_path.'/'.$options['owner_class'], __FILE__, __LINE__);
			return false;
		}
		
		if ($options['secure_storage'] && !NFW::i()->checkPermissions(preg_replace('/\|.*/', '', $options['owner_class']), 'media_upload', $options['owner_id'])) {
			$lang_media = NFW::i()->getLang('media');
			$this->error($lang_media['Errors']['No_Permissions'], __FILE__, __LINE__);
			return false;
		}
		
		// remove previously uploaded, but unconfirmed files
		$this->removeTemporaryFiles($options['owner_class']);
		
		if (!isset($options['MAX_FILE_SIZE'])) {
			$options['MAX_FILE_SIZE'] = isset(NFW::i()->cfg['media']['MAX_FILE_SIZE']) ? NFW::i()->cfg['media']['MAX_FILE_SIZE'] : self::MAX_FILE_SIZE;
		}

		if (!isset($options['MAX_SESSION_SIZE'])) {
			$options['MAX_SESSION_SIZE'] = isset(NFW::i()->cfg['media']['MAX_SESSION_SIZE']) ? NFW::i()->cfg['media']['MAX_SESSION_SIZE'] : self::MAX_SESSION_SIZE;
		}
		
		$options['session_id'] = $this->calculateSessionId($options['owner_class'], $options['owner_id']);
		$options['cookie_data'] = base64_encode(NFW::i()->encodeStr(serialize($options)));
		
		$template_vars = array_merge($options, $form_data);
		
		if (isset($options['preload_media']) && $options['preload_media'] && $options['owner_id']) {
			$template_vars['preloaded_media'] = $this->getFiles($options['owner_class'], $options['owner_id']);
		}
			
		// Render form
		$this->path_prefix = isset($options['path_prefix']) ? $options['path_prefix'] : false;
		return $this->renderAction($template_vars, isset($options['template']) ? $options['template'] : 'form');
	}
	
	public function closeSession($owner_class, $owner_id) {
		if (!$this->loadSession($owner_class)) return false;
		
		if (!$result = NFW::i()->db->query_build(array('SELECT'	=> 'COUNT(*)', 'FROM' => $this->db_table, 'WHERE' => 'session_id=\''.$this->calculateSessionId($owner_class).'\''))) {
			$this->error('Unable to count uploaded files', __FILE__, __LINE__, NFW::i()->db->error());
			return false;
		}
		list ($num_files) = NFW::i()->db->fetch_row($result);
	
		if ($num_files) {
			$query = array('UPDATE' => $this->db_table, 'SET' => 'session_id=NULL, owner_id='.$owner_id, 'WHERE' => 'session_id=\''.$this->calculateSessionId($owner_class).'\'');
			if (!NFW::i()->db->query_build($query)) {
				$this->error('Unable to close session', __FILE__, __LINE__, NFW::i()->db->error());
			}
		}
			
		NFW::i()->setCookie($this->calculateSessionId($owner_class), null, 0);
		return $num_files;
	}
		
	public function getFiles($owner_class = false, $owner_id = 0, $options = array()) {
		$query = array(
			'SELECT'	=> '*',
			'FROM'		=> $this->db_table,
			'ORDER BY'  => isset($options['order_by']) ? $options['order_by'] : 'posted DESC'
		);
	
		if ($owner_id) {
			if (strstr($owner_class, '%')) {
				// Неточное соответствие класса
				$query['WHERE']	= 'owner_class LIKE \''.NFW::i()->db->escape($owner_class).'\'';
			}
			else {
				$query['WHERE']	= 'owner_class=\''.NFW::i()->db->escape($owner_class).'\'';
			}
				
			if (is_array($owner_id)) {
				$query['WHERE']	.= ' AND owner_id IN('.implode(',',$owner_id).')';
			}
			else {
				$query['WHERE']	.= ' AND owner_id='.intval($owner_id);
			}
		}
		else {
			if (!$this->loadSession($owner_class)) return false;
			$query['WHERE']	= 'session_id=\''.$this->calculateSessionId($owner_class).'\'';
		}
			
		$files = array();
		$load_data = isset($options['load_data']) && $options['load_data'] ? true : false;
		
		if (!$result = NFW::i()->db->query_build($query)) {
			$this->error('Unable to fetch media list', __FILE__, __LINE__, NFW::i()->db->error());
			return false;
		}
		while($cur_file = NFW::i()->db->fetch_assoc($result)) {
			$cur_file = $this->formatRecord($cur_file);
			
			if ($load_data) {
				$this->loadData($cur_file);
			}
			
			$files[] = $cur_file;
		}
		 
		return $files;
	}
		
	function actionList() {
		$this->error_report_type = 'alert';
	
		if (!isset($_GET['owner_class'])) {
			$this->error('Неверный запрос.', __FILE__, __LINE__);
			return false;
		}
	
		$owner_class = trim($_GET['owner_class']);
		$owner_id = (isset($_GET['owner_id'])) ? intval($_GET['owner_id']) : false;

		// Load opened session
		if (!$this->loadSession($owner_class, $owner_id)) return false;
		
		if ($owner_id && $this->session['secure_storage'] && !NFW::i()->checkPermissions(preg_replace('/\|.*/', '', $owner_class), 'media_get', $owner_id)) {
			$this->error('Недостаточно прав для просмотра списка вложений.', __FILE__, __LINE__);
			return false;
		}
	
		$this->path_prefix = isset($this->session['path_prefix']) ? $this->session['path_prefix'] : false;
		NFW::i()->stop($this->renderAction(array(
			'records' => $this->getFiles($owner_class, $owner_id, array('order_by' => 'id')),
		), isset($_GET['tpl']) ? $_GET['tpl'] : 'records.js'));
	}
	
	function actionUpload() {
		$this->error_report_type = 'active_form';
		 
		if (!isset($_POST['owner_class'])) {
			$this->error('Неверный запрос.', __FILE__, __LINE__);
			return false;
		}
		 
		$owner_class = $_POST['owner_class'];
		$owner_id = (isset($_POST['owner_id'])) ? $_POST['owner_id'] : 0;
		 
		if (!NFW::i()->checkPermissions(preg_replace('/\|.*/', '', $owner_class), 'media_upload', $owner_id)) {
			$this->error('Недостаточно прав для загрузки вложения.', __FILE__, __LINE__);
			return false;
		}
		 
		// Load opened session
		if (!$this->loadSession($owner_class, $owner_id)) {
			NFW::i()->renderJSON(array('result' => 'error', 'errors' => array('local_file' => htmlspecialchars($this->last_msg))));
			return false;
		}
		 
		// Check MAX_SESSION_SIZE overflow
		$session_size = $_FILES['local_file']['size'];
		foreach ($this->getFiles($owner_class, $owner_id) as $a) {
			$session_size += $a['filesize'];
		}
		if ($session_size > $this->session['MAX_SESSION_SIZE']) {
			$this->error('Общий объем загруженных файлов одной сессии не может превышать '.(number_format($this->session['MAX_SESSION_SIZE']/(1024*1024), 2, '.', ' ')).' мб.', __FILE__, __LINE__);
			NFW::i()->renderJSON(array('result' => 'error', 'errors' => array('local_file' => htmlspecialchars($this->last_msg))));
			return false;
		}
	
		if (!$this->upload($_FILES['local_file'], $_POST)) {
			NFW::i()->renderJSON(array('result' => 'error', 'errors' => array('local_file' => htmlspecialchars($this->last_msg))));
		}
		 
		logs::write('ID='.$this->record['id'], self::LOGS_MEDIA_UPLOAD, $owner_id.':'.$owner_class);
		NFW::i()->renderJSON(array(
			'result' => 'success',
			'url' => $this->record['url']			
		));
	}
	
	function actionReload() {
		$this->error_report_type = 'active_form';
	
		if (!$this->load($_POST['file_id'])) return false;
		 
		// Для временных файлов проверка прав не нужна, т.к. если смогли его получить, значит являемся его автором и имеем право удалить.
		// Для перманентных файлов производим проверку.
		if ($this->record['owner_id'] && !NFW::i()->checkPermissions(preg_replace('/\|.*/', '', $this->record['owner_class']), 'media_modify')) return false;
		 
		// Load opened session
		if (!$this->loadSession($this->record['owner_class'], $this->record['owner_id'])) {
			NFW::i()->renderJSON(array('result' => 'error', 'errors' => array('general' => htmlspecialchars($this->last_msg))));
			return false;
		}
		 
		// Check MAX_SESSION_SIZE overflow
		$session_size = $_FILES['local_file']['size'];
		foreach ($this->getFiles($this->record['owner_id'], $this->record['owner_class']) as $a) {
			$session_size += $a['filesize'];
		}
		if ($session_size > $this->session['MAX_SESSION_SIZE']) {
			$this->error('Общий объем загруженных файлов одной сессии не может превышать '.(number_format($this->session['MAX_SESSION_SIZE']/(1024*1024), 2, '.', ' ')).' мб.', __FILE__, __LINE__);
			NFW::i()->renderJSON(array('result' => 'error', 'errors' => array('general' => htmlspecialchars($this->last_msg))));
			return false;
		}
	
		if (!$this->upload($_FILES['local_file'])) {
			NFW::i()->renderJSON(array('result' => 'error', 'errors' => array('general' => htmlspecialchars($this->last_msg))));
		}
		logs::write('ID='.$this->record['id'], self::LOGS_MEDIA_RELOAD, $this->record['owner_id'].':'.$this->record['owner_class']);
		NFW::i()->renderJSON(array('result' => 'success'));
	}
	
	function actionRemove() {
		$this->error_report_type = 'alert';
	
		if (!$this->load($_POST['file_id'])) return false;
	
		// Для временных файлов проверка прав не нужна, т.к. если смогли его получить, значит являемся его автором и имеем право удалить.
		// Для перманентных файлов производим проверку.
		if ($this->record['owner_id'] && !NFW::i()->checkPermissions(preg_replace('/\|.*/', '', $this->record['owner_class']), 'media_modify', $this->record['owner_id'])) {
			$this->error('Permissions denied', __FILE__, __LINE__);
			return false;
		}
	
		// Store variables before `delete`
		$record_id = $this->record['id'];
		$owner_id = $this->record['owner_id'];
		$owner_class = $this->record['owner_class'];
		
		$this->delete();
		logs::write('ID='.$record_id, self::LOGS_MEDIA_REMOVE, $owner_id.':'.$owner_class);
		NFW::i()->stop();
	}
	
	function actionUpdateComment(){
		$this->error_report_type = 'alert';
	
		// Generate updating list
		if (!isset($_POST['comments']) && !is_array($_POST['comments'])) {
			NFW::i()->stop('success');
		}
		
		foreach($_POST['comments'] as $r) {
			if (!isset($r['file_id']) || !$this->load($r['file_id'])) continue;
	
			// Для временных файлов проверка прав не нужна, т.к. если смогли его получить, значит являемся его автором и имеем право удалить.
			// Для перманентных файлов производим проверку.
			if ($this->record['owner_id'] && !NFW::i()->checkPermissions(preg_replace('/\|.*/', '', $this->record['owner_class']), 'media_modify')) return false;
				
			if (!NFW::i()->db->query_build(array(
				'UPDATE'	=> $this->db_table,
				'SET'		=> '`comment`='.(isset($r['comment']) ? '\''.NFW::i()->db->escape(trim($r['comment'])).'\'' : 'NULL'),
				'WHERE'		=> '`id`='.$this->record['id']
			))) {
				$this->error('Unable to update record', __FILE__, __LINE__, NFW::i()->db->error());
				return false;
			}
				
			logs::write('ID='.$this->record['id'], self::LOGS_MEDIA_UPDATE_COMMENT, $this->record['owner_id'].':'.$this->record['owner_class']);
		}
	
		NFW::i()->stop('success');
	}

	// ПРосмотр и редактирование всех публичных вложений 
	function actionManage() {
		if (isset($_POST['remove_file'])) {
			$this->error_report_type = 'plain';
			
			if (!$this->load($_POST['remove_file'])) return false;
			
			// Store variables before `delete`
			$record_id = $this->record['id'];
			$owner_id = $this->record['owner_id'];
			$owner_class = $this->record['owner_class'];
			
			$this->delete();
			logs::write('ID='.$record_id, self::LOGS_MEDIA_REMOVE, $owner_id.':'.$owner_class);
			NFW::i()->stop('success');
		}
				
		if (!isset($_GET['owner_class'])) {
			$owners = array();
			foreach (scandir(PROJECT_ROOT.$this->storage_path) as $f) {
				if ($f != '.' && $f != '..' && is_dir(PROJECT_ROOT.$this->storage_path.'/'.$f)) {
					$owners[] = $f;
				}
			}
	
			return $this->renderAction(array('owners' => $owners));
		}
	
		// Generate list
		$this->error_report_type = 'plain';
		 
		$records = array();
		
		$owner_class = $_GET['owner_class'];
		if (!file_exists(PROJECT_ROOT.$this->storage_path.'/'.$owner_class)) {
			$this->error('Unlnown `owner_class`', __FILE__, __LINE__);
			return false;
		}

		if (!$result = NFW::i()->db->query_build(array('SELECT' => '*', 'FROM' => $this->db_table,	'WHERE' => 'owner_class=\''.$owner_class.'\''))) {
			$this->error('Unable to fetch media list', __FILE__, __LINE__, NFW::i()->db->error());
			return false;
		}
		while($cur_file = NFW::i()->db->fetch_assoc($result)) {
			$records[] = $this->formatRecord($cur_file);
		}
					
		NFW::i()->stop($this->renderAction(array(
			'records' => $records
		), '_manage_list.js'));
	}	
}