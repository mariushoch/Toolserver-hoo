<?php
/*
 * Class for logging
 * [[m:User:Hoo man]]; Last update: 2012-09-30
 */

class log {
	//
	//	Write a line like into $_CONFIG['log_file']
	//	20120930143812 - fooBar.php:312 - Message
	//
	public static function write_line($msg, $file, $line = false) {
		global $_CONFIG;
		$date = new DateTime('@' . time());
		$str = $date->format('YmdHis');
		$str .= ' - ' . $file;
		if($line) {
			$str . ':' . $line;
		}
		$str .= ' - ' . $msg . PHP_EOL;
		
		$handle = fopen($_CONFIG['log_file'], 'ab');
		flock($handle, LOCK_EX);
		$success = fwrite($handle, $str);
		flock($handle, LOCK_UN);
		fclose($handle);
		if($success !== false) {
			return true;
		}
		return false;
	}
}
