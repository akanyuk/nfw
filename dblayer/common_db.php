<?php
/**
 * Loads the proper database layer class.
 *
 * @copyright (C) 2008-2012 PunBB, partially based on code (C) 2008-2009 FluxBB.org
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package PunBB
 */


// Load the appropriate DB layer class
switch ($this->cfg['db']['type']) {
	case 'mysql':
		require dirname(__FILE__).'/mysql.php';
		break;

	case 'mysql_innodb':
		require dirname(__FILE__).'/mysql_innodb.php';
		break;

	case 'mysqli':
		require dirname(__FILE__).'/mysqli.php';
		break;

	case 'mysqli_innodb':
		require dirname(__FILE__).'/mysqli_innodb.php';
		break;

	case 'pgsql':
		require dirname(__FILE__).'/pgsql.php';
		break;

	case 'sqlite':
		require dirname(__FILE__).'/sqlite.php';
		break;

	case 'sqlite3':
		require dirname(__FILE__).'/sqlite3.php';
		break;

	default:
		require dirname(__FILE__).'/dummy.php';
		break;
}

