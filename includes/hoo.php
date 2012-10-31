<?php
/*
* [[m:User:Hoo man]]; Last update: 2012-10-01
* This script provides a class with severall functions
*/
if(!defined('IN_HOO_TS')) {
	exit();
}

class hoo_base {
	private $db_pass;
	private $db_user;
	private $db_connections;
	private $replag_map;
	public $db_map;
	//
	// This function will return a PDO object from which the given $db_name can be accessed
	//
	public function &wiki_db($db_name, $user_db = null) {
		if($db_name === 'toolserver' || $db_name === 'u_hoo_p') {
			if(!is_array($this->db_connections) || !$this->db_connections['toolserver']) {
				$this->database_connect($_SQL['misc_db']['server'], 'toolserver', true);
			}
			return $this->db_connections['toolserver'];
		}
		$this->load_db_map();
		if(!isset($this->db_map[ $db_name ])) {
			//unknown DB
			throw new database_exception('Unknown database: ' . $db_name);
		}
		$user_db_server = 'sql-s' . $this->db_map[ $db_name ] . '-user';
		$rr_db_server = 'sql-s' . $this->db_map[ $db_name ] . '-rr';
		//are we already connected to a satisfying server?
		//check user db first
		if(isset($this->db_connections[ $user_db_server ]) && $this->db_connections[ $user_db_server ] && $user_db !== false) {
			return $this->db_connections[ $user_db_server ];
		}else if(isset($this->db_connections[ $rr_db_server ]) && $this->db_connections[ $rr_db_server ] && $user_db !== true) {
			return $this->db_connections[ $rr_db_server ];
		}
		//establish a connection
		if($user_db) {
			if($this->database_connect($user_db_server . '.toolserver.org', $user_db_server) === true) {
				return $this->db_connections[ $user_db_server ];
			}else{
				throw new database_exception('Couldn\'t connect to user-database: ' . $db_name);
			}
		}else{
			if($this->database_connect($rr_db_server . '.toolserver.org', $rr_db_server) === true) {
				return $this->db_connections[ $rr_db_server ];
			}else{
				throw new database_exception('Couldn\'t connect to database: ' . $db_name);
			}
		}
	}
	//
	// this function will start a new MySQL PDO connection and store it into $this->db_connections
	//
	protected function database_connect($server, $canonical_name, $throw_on_error = false, $db = false) {
		if(!isset($this->db_connections[$canonical_name])) {
			if(!$this->db_pass) {
				$ts_pw = posix_getpwuid(posix_getuid());
				$ts_mycnf = parse_ini_file($ts_pw['dir'] . "/.my.cnf");
				$this->db_pass = $ts_mycnf['password'];
				$this->db_user = $ts_mycnf['user'];
			}
			if($db) {
				$tmp = 'mysql:host=' . $server . ';dbname=' . $db . ';';
			}else{
				$tmp = 'mysql:host=' . $server . ';';
			}
			try {
				$this->db_connections[$canonical_name] = new PDO($tmp, $this->db_user, $this->db_pass);
			}catch(PDOException $e){
				if($throw_on_error) {
					throw new database_exception('Connection failed: ' . $server);
				}else{
					return false;
				}
			}
		}
		return true;
	}
	//
	// This function will load $this->db_map and return it
	//
	public function load_db_map() {	
		global $_SQL;
		if(is_array($this->db_map)) {
			return $this->db_map;
		}
		try {
			$this->database_connect($_SQL['misc_db']['server'], 'toolserver', true);
			$statement = $this->db_connections['toolserver']->prepare('SELECT dbname, server FROM toolserver.wiki');
			$statement->execute();
			$tmp = $statement->fetchAll(PDO::FETCH_ASSOC);
			if(!is_array($tmp)) {
				throw new Exception('Invalid result');
			}
			$this->db_map = array();
			foreach($tmp as $row) {
				$this->db_map[ $row['dbname'] ] = $row['server'];
			}
		}catch(Exception $e){
			throw new database_exception('Database error: toolserver');
		}
		return $this->db_map;
	}
	//
	// This function give the current replag at the given $db_name
	// (Cached)
	//
	public function replag($db_name, $user_db = false) {
		$this->load_db_map();
		if(!isset($this->db_map[ $db_name ])) {
			throw new Exception('Unknown Database');
		}
		$server_prefix = '';
		if($user_db) {
			$server_prefix = 'u';
		}
		$server = $this->db_map[ $db_name ];
		if(is_array($this->replag_map) && isset($this->replag_map[ $server_prefix . $server ])) {
			return $this->replag_map[ $server_prefix . $server ];
		}
		//select (probably) busiest DB
		switch( $server) {
			case 1:
				$search_db = 'enwiki_p';
			break;
			case 3:
			case 7:
				$search_db = 'eswiki_p';
			break;
			break;
			case 5:
				$search_db = 'dewiki_p';
			break;
			case 6:
				$search_db = 'frwiki_p';
			break;
			default:
				$search_db = 'commonswiki_p';
			break;
		}
		$db = &$this->wiki_db($search_db, $user_db);
		//log after the latest timestamp in both recentchanges and logging
		$SQL_query = 'SELECT /* LIMIT:3 */ UNIX_TIMESTAMP() - UNIX_TIMESTAMP(IF((MAX(rc_timestamp) > MAX(log_timestamp)), MAX(rc_timestamp), MAX(log_timestamp))) as replag FROM ' . $search_db . '.recentchanges, ' . $search_db . '.logging';	
		//simple, more perfomant query
		//$SQL_query = 'SELECT UNIX_TIMESTAMP() - UNIX_TIMESTAMP(MAX(rc_timestamp)) AS replag FROM ' . $search_db . '.recentchanges';	
		$statement = $db->prepare($SQL_query);
		$statement->execute();
		$replag = $statement->fetchColumn(0);
		if(!is_numeric($replag) && $replag !== '0') {
			throw new database_exception('Database error: ' . $search_db);
		}
		$this->replag_map[ $server_prefix . $server ] = $replag;
		return $replag;
	}
	//
	//	Print a (more or less) user readable error
	//
	public static function show_error($error, $msg_pre = '', $msg_suff = '') {
		if(!headers_sent()) {
			header('HTTP/1.0 500 Internal Server Error');			
		}
		echo '<h2>Internal Error:</h2><br />';
		echo $msg_pre . htmlspecialchars($error, ENT_COMPAT | ENT_HTML401, 'UTF-8') . $msg_suff;
		exit(1);
	}
	//
	// Check whether the given input is an IP
	//
	public function is_ip($str) {
		@$success = inet_pton($str);
		if($success) {
			return true;
		}else{
			return false;
		}
	}
	//
	// Count the current view in the database, for stats
	//
	public function view_count($page, $lang) {
		global $_SQL;
		$year = date('o');
		$week = date('W');
		try {
			$this->database_connect($_SQL['misc_db']['server'], 'toolserver', true);
			$SQL_query = 'INSERT INTO ' . $_SQL['misc_db']['db_name'] . '.views (page, lang, year, week, views) VALUES (:page, :lang, :year, :week, 1) ON DUPLICATE KEY UPDATE views = views + 1';
			$statement = $this->db_connections['toolserver']->prepare($SQL_query);
			$statement->bindValue(':page', $page, PDO::PARAM_STR);
			$statement->bindValue(':lang', $lang, PDO::PARAM_STR);
			$statement->bindValue(':year', $year, PDO::PARAM_INT);
			$statement->bindValue(':week', $week, PDO::PARAM_INT);
			$statement->execute();
		}catch(Exception $e){
			// This isn't good, but maybe we still can serve the request
			log::write_line($e->getMessage(), $e->getFile());
			return false;
		}
		if($statement->rowCount()) {
			return true;
		}else{
			return false;
		}
	}
	//
	//	Retrieve and sanitize user input
	//
	public function get_user_input($name, $sanitize = 'raw', $method = null) {
		if($method === 'get') {
			if(isset($_GET[ $name ])) {
				$output = $_GET[ $name ];
			}else{
				return null;
			}
		}else if($method === 'post') {
			if(isset($_POST[ $name ])) {
				$output = $_POST[ $name ];
			}else{
				return null;
			}
		}else{
			if(isset($_GET[ $name ])) {
				$output = $_GET[ $name ];
			}else if(isset($_POST[ $name ])) {
				$output = $_POST[ $name ];
			}else{
				return null;
			}
		}
		switch($sanitize) {
			case 'raw':
				return $output;
			break;
			case 'int':
				return (int) $output;
			break;
			case 'output':
				return htmlspecialchars($output, ENT_QUOTES, 'UTF-8');
			break;
		}
	}
	//
	// Accept wiki input in different forms (returns the dbname or false):
	// 1. Database names with trailing _p (like enwiki_p)
	// 2. Database names without trailing _p (like enwiki)
	// @TODO: Implement more options
	//
	public function wiki_input($input) {
		$this->load_db_map();
		// 1. case:
		if(isset($this->db_map[ $db_name ])) {
			return $db_name;
		}
		// 2. case:
		if(isset($this->db_map[ $db_name . '_p' ])) {
			return $db_name . '_p';
		}
		return false;
	}
}
