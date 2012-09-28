<?php
/*
* [[m:User:Hoo man]]; Last update: 2012-07-23
* This script provides a class with severall functions
*/
if(!defined('IN_HOO_TS')) {
	exit();
}

class hoo_html {
	private $db_pass;
	private $db_user;
	private $db_connections;
	private $replag_map;
	public $db_map;
	public function &wiki_db($db_name, $user_db = null) {
		$failure = false;
		if($db_name === 'toolserver' || $db_name === 'u_hoo_p') {
			if(!is_array($this->db_connections) || !$this->db_connections['toolserver']) {
				$this->database_connect($_SQL['misc_db']['server'], 'toolserver', true);
			}
			return $this->db_connections['toolserver'];
		}
		if(!is_array($this->db_map)) {
			$this->db_map = $this->get_db_map();
		}
		if(!isset($this->db_map[ $db_name ])) {
			//unknown DB
			return $failure;
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
				return $failure;
			}
		}else{
			if($this->database_connect($rr_db_server . '.toolserver.org', $rr_db_server) === true) {
				return $this->db_connections[ $rr_db_server ];
			}else{
				echo 1;
				return $failure;
			}
		}
	}
	//this function will start a new MySQL PDO connection and stores it into $this->db_connections
	protected function database_connect($server, $canonical_name, $die_on_error = false, $db = false) {
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
				if($die_on_error) {
					$this->show_error($e->getMessage(), 'Connection failed: ');
				}else{
					return 'Connection failed: ' . $e->getMessage();
				}
			}
		}
		return true;
	}
	public function get_db_map() {	
		global $_SQL;
		$this->database_connect($_SQL['misc_db']['server'], 'toolserver', true);
		$statement = $this->db_connections['toolserver']->prepare('SELECT dbname, server FROM toolserver.wiki');
		$statement->execute();
		$tmp = $statement->fetchAll(PDO::FETCH_ASSOC);
		foreach($tmp as $row) {
			$result[ $row['dbname'] ] = $row['server'];
		}
		return $result;
	}
	public function replag($db_name) {
		if(!is_array($this->db_map)) {
			$this->db_map = $this->get_db_map();
		}
		if(!isset($this->db_map[ $db_name ])) {
			//unknown DB
			return false;
		}
		$server = $this->db_map[ $db_name ];
		if(is_array($this->replag_map) && isset($this->replag_map[ $server ])) {
			return $this->replag_map[ $server ];
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
		$db = &$this->wiki_db($search_db);
		//log after the latest timestamp in both recentchanges and logging
		$SQL_query = 'SELECT /* LIMIT:3 */ UNIX_TIMESTAMP() - UNIX_TIMESTAMP(IF((MAX(rc_timestamp) > MAX(log_timestamp)), MAX(rc_timestamp), MAX(log_timestamp))) as replag FROM ' . $search_db . '.recentchanges, ' . $search_db . '.logging';	
		//simple, more perfomant query
		//$SQL_query = 'SELECT UNIX_TIMESTAMP() - UNIX_TIMESTAMP(MAX(rc_timestamp)) AS replag FROM ' . $search_db . '.recentchanges';	
		$statement = $db->prepare($SQL_query);
		$statement->execute();
		$replag = $statement->fetchColumn(0);
		if(!is_numeric($replag) && $replag !== '0') {
			var_dump($replag);
			return false;
		}
		$this->replag_map[ $server ] = $replag;
		return $replag;
	}
	public function show_error($error, $msg_pre = '', $msg_suff = '') {
		if(!headers_sent()) {
			header('HTTP/1.0 500 Internal Server Error');			
		}
		echo '<h2>Internal Error:</h2><br />';
		die($msg_pre . $error . $msg_suff);
	}
	public function is_ip($str) {
		@$success = inet_pton($str);
		if($success) {
			return true;
		}else{
			return false;
		}
	}
	//
	//This function handels the $available_languages and saves the choosen one into $uselang
	// deprecated over using Intuition
	//
	public function interface_lang() {
		global $uselang, $available_languages, $_CONFIG;
		$uselang = 'en';
		if(isset($_GET['uselang'])) {
			setcookie('hoo_lang', $_GET['uselang'], time()+60*60*24*30, $_CONFIG['cookie_path']);
			if($available_languages[$_GET['uselang']]) {
				$uselang = $_GET['uselang'];
			}
		}elseif($_COOKIE['hoo_lang']){
			if($available_languages[$_COOKIE['hoo_lang']]) {
				$uselang = $_COOKIE['hoo_lang'];
			}
		}
	}
	public function view_count($page, $lang) {
		global $_SQL;
		$year = date('o');
		$week = date('W');
		$this->database_connect($_SQL['misc_db']['server'], 'toolserver', true);
		$SQL_query = 'INSERT INTO ' . $_SQL['misc_db']['db_name'] . '.views (page, lang, year, week, views) VALUES (:page, :lang, :year, :week, 1) ON DUPLICATE KEY UPDATE views = views + 1';
		$statement = $this->db_connections['toolserver']->prepare($SQL_query);
		$statement->bindValue(':page', $page, PDO::PARAM_STR);
		$statement->bindValue(':lang', $lang, PDO::PARAM_STR);
		$statement->bindValue(':year', $year, PDO::PARAM_INT);
		$statement->bindValue(':week', $week, PDO::PARAM_INT);
		$statement->execute();
		if($statement->rowCount()) {
			return true;
		}else{
			return false;
		}
	}
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
				return htmlspecialchars($output, ENT_COMPAT | ENT_HTML401, 'UTF-8');
			break;
		}
	}
}
?>
