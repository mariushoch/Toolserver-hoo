<?php
/*
* [[m:User:Hoo man]]; Last update: 2012-04-08
* This script provides a class with severall functions
*/
if(!defined('IN_HOO_TS')) {
	exit();
}

class hoofr {
	private $db_pass;
	private $db_user;
	//this function will start a new MySQL PDO connection and store it into $DB[$server]
	public function database_connect($server, $die_on_error = false, $db = false) {
		global $DB;
		if(!isset($DB[$server])) {
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
				$DB[$server] = new PDO($tmp, $this->db_user, $this->db_pass);
			}catch(PDOException $e){
				if($die_on_error) {
					if(!headers_sent()) {
						header('HTTP/1.0 500 Internal Server Error');			
					}
					echo '<h2>Internal Error:</h2><br />';
					die('Connection failed: ' . $e->getMessage());
				}else{
					return 'Connection failed: ' . $e->getMessage();
				}
			}
		}
		return true;
	}
	public function is_ip($str) {
		if(preg_match('/(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}/', $str)) {
			return true;
		}else{
			return false;
		}
	}
	//
	//This function handels the $available_languages and save the choosen one into $uselang
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
		global $_SQL, $DB;
		$year = date('o');
		$week = date('W');
		$this->database_connect($_SQL['misc_db']['server'], true);
		$SQL_query = 'INSERT INTO ' . $_SQL['misc_db']['db_name'] . '.views (page, lang, year, week, views) VALUES (:page, :lang, :year, :week, 1) ON DUPLICATE KEY UPDATE views = views + 1';
		$statement = $DB[$_SQL['misc_db']['server']]->prepare($SQL_query);
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
}
?>
