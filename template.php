<?php
/*
* [[m:User:Hoo man]]; Last update: 2012-01-04
* This script should provide a tiny nice template for all my tools
*/
if(!defined('IN_HOO_TS')) {
	exit();
}

class html_output {
	//	string in which the output will be stored
	private $output;
	//	In this var data will be stored (eg. template vars)
	private $data;
	//	This var will be used to cache some stuff (to improve performance) (atm only used by $this->parse_row();)
	private $cache;
	//
	//	This function loads the template file and resolves imports, sets vars etc.
	//
	//	$templateFile = the file to load as main template file
	//
	function __construct($templateFile = 'template.html') {
		global $_CONFIG, $uselang, $page_name, $_LANG;
		$this->output = file_get_contents($_CONFIG['include_path'] . '/templates/' . $templateFile);
		//resolve includes
		$this->resolve_imports();
		//init all possible vars
		preg_match_all('/{.*}/', $this->output, $tmp);
		foreach($tmp[0] as $i) {
			$i = str_replace('{', '', $i);
			$i = str_replace('}', '', $i);
			$this->data[$i] = '';
		}
		//set some defaults
		$this->set('robots', 'index,follow');
		$this->set('lang', $uselang);
		$this->set('document_root', $_CONFIG['document_root']);
		$this->set('page', $page_name);
		if($_LANG['title']) {
			$this->set('title', $_LANG['title']);
		}else{
			$this->set('title', $page_name);
		}
		
		$this->sub_ifs('disable');
	}
	//
	// This function will import files out of include_path/templates. To use it insert: (import:filename); into the template
	//
	function resolve_imports() {
		global $_CONFIG;
		$y = 1;
		while($y) {
			$y++;
			preg_match_all('/\(import:.*\);/', $this->output, $tmp);
			if(isset($tmp[0][0])) {
				foreach($tmp[0] as $i) {
					$i = str_replace('(import:', '', $i);
					$i = str_replace(');', '', $i);
					$this->output = str_replace('(import:' . $i . ');', $this->sub_ifs('disable', file_get_contents($_CONFIG['include_path'] . '/templates/' . basename($i))), $this->output);
				}
			}else{
				return true;
			}
			if($y > 10) {
				//likely a include loop
				return false;
			}
		}
	}
	//
	//	This function enables and disables IFs in rows (which is needed to prevent parsing of IFs in unfilled rows)
	//	It returns the new string and (if $str not set it save them into the default output)
	//
	//	$mode = enable/disable enable or disable Ifs
	//	$str = The string to use (default is the standard output which will be changed if $str isn't set)
	//
	function sub_ifs($mode, $str = NULL) {
		if(!isset($str)) {
			$str = &$this->output;
		}
		if($mode == 'disable') {
			$pattern = '/(<!-- ?ROW name="(.*)" ?-->)(.*)(<!-- ?END ?ROW name="\2" ?-->)/sUui';
			preg_match_all($pattern, $str, $matches);
			if(isset($matches[3][0])) {
				foreach($matches[3] as $key => $i) {
					$pattern = '/<!-- ?IF (.*)-->(.*)<!-- ?END ?IF (.*)-->/sUui';
					$replacement = '<!-- I_F \1-->\2<!-- END I_F \3-->';
					$tmp = preg_replace($pattern, $replacement, $i);
					$str = str_replace($matches[0][$key], $matches[1][$key] . $tmp . $matches[4][$key], $str);
				}
			}
		}else{
			$pattern = '/<!-- I_F (.*)-->(.*)<!-- END I_F (.*)-->/sUui';
			$replacement = '<!-- IF \1-->\2<!-- END IF \3-->';
			$str = preg_replace($pattern, $replacement, $str);
		}
		return $str;
	}
	//
	//	This function will take data and store it till the page gets parsed or (if $apply_directly is true) it applies them directly
	//	Syntax: {Name_of_the_var}
	//
	//	$var = name of the var (in the template)
	//	$data = value of the var
	//	$apply_directly = (bool) set to true if the var should be changed in the template right now (and not only at the end in $this->parse_page();)
	//	$method = set to 'append' to have it stored after the already existing value of the var, set to prepend to have it in front of the already existing value (defualt is override the existing value)
	//
	function set($var, $data, $apply_directly = false, $method = NULL) {
		if($method == NULL) {
			$this->data[$var] = $data;
		}elseif($method == 'append') {
			$this->data[$var] .= $data;
		}elseif($method == 'prepend'){
			$this->data[$var] = $data . $this->data[$var];
		}
		if($apply_directly) {
			$this->output = str_ireplace('{' . $var . '}', $this->data[$var] . "\n{" . $var . '}', $this->output);
			$this->data[$var] = '';
		}
		return true;
	}
	//
	//	This functions makes IFs in templates work (syntax: <!-- IF foo == "bar" -->foo is bar<!-- END IF foo == "bar" -->)
	//
	//	$var = Name of the var to use
	//	$value = the value of the var
	//	$str = The string to parse IFs in (default $this->output)
	//	$escape = (bool) Set to false if the input strings ($var and $value) shouldn't be escaped (eg. to parse multiple vars like (foo|bar|test))
	//
	function template_if($var, $value, $str = NULL, $escape = true) {
		if(!$str) {
			$str = &$this->output;
		}
		if($escape) {
			$var = preg_quote($var, '/');
			$value = preg_quote($value, '/');
		}
		//true
		#$patterns[0] = '/<!-- ?IF (' . $var . ') (== "(' . $value . ')"|!= (?!"(' . $value . ')")).*-->(.*)<!-- ?END ?IF (\1) (== "(\3)"|!= (?!"(\5)")).*-->/sUui';
		$patterns[0] = '/<!-- ?IF ' . $var . ' (== "' . $value . '"|!= (?!"' . $value . '")).*-->(.*)<!-- ?END ?IF ' . $var . ' (== "' . $value . '"|!= (?!"' . $value . '")).*-->/sUui';
		$replacements[0] = '\2';
		#$replacements[0] = '\5';
		//everything else (false)
		#$patterns[1] = '/<!-- ?IF (' . $var . ') .*-->.*<!-- ?END ?IF \1 .*-->/sUui';
		$patterns[1] = '/<!-- ?IF ' . $var . ' .*-->.*<!-- ?END ?IF ' . $var . ' .*-->/sUui';
		$replacements[1] = '';
		ksort($patterns); ksort($replacements);
		$str = preg_replace($patterns, $replacements, $str);
		return $str;
	}
	//
	//This function does the same as template_if(), but only for the current insert cache
	//(the last row inserted by $this->parse_row(); if $fulltext_cache isn't set to false)
	//
	//	$var = Name of the var to use
	//	$value = the value of the var
	//
	function last_row_if($var, $value) {
		$this->cache['rows']['last_insert']['text'] = $this->template_if($var, $value, $this->cache['rows']['last_insert']['text']);
	}
	//
	//	This function replaces a string with another one (in $this->output)
	//	this is needed, cause $this->output can't be changed from outside this class
	//
	//	$search = value to replace
	//	$replace = replacement
	//
	function output_replace($search, $replace) {
		$this->output = str_ireplace($search, $replace, $this->output);
	}
	//
	//	Can parse rows in the format <!-- ROW name="name_of_the_row" -->Field1: {@first_field@}, Field2: {@second_one@}<!-- END ROW name="name_of_the_row" -->
	//	use multi_replace to create severall rows at once using a multi dimensional array
	//
	//	$row_name = Name of the row to parse
	//	$data = Data to insert (array, Format: ['first_field'] => 'something here', ['second_one'] => 'foo'
	//	$multi_replace = (bool) set to true to use a multi level arrays
	//	$cache_key = can be set if you have multiple rows with different names (eg. because they are on different locations within the template), but the same code (to enable caching for those)
	//	$fulltext_cache = (bool) set to false when you have sub rows or if you need this->template_if to work for all rows (much slower on big rows)
	//
	function parse_row($row_name, $data, $multi_replace = false, $cache_key = false, $fulltext_cache = true) {
		if($this->cache['rows']['last_insert']) {
			$tmp = $this->cache['rows']['last_insert']['key'];
			$this->cache['rows'][$tmp]['insert'] .= $this->cache['rows']['last_insert']['text'];
			unset($this->cache['rows']['last_insert']);
		}
		//get the row (caches it into $this->cache['rows'] to prevent the regexp from running again)
		if(!$cache_key) {
			$cache_key = $row_name;
		}
		if(isset($this->cache['rows'][$cache_key])) {
			$row = $this->cache['rows'][$cache_key]['row'];
			if($row_name != $cache_key) {
				$this->cache['rows'][$row_name]['cache_key'] = $cache_key;
			}
			if(!$fulltext_cache) {
				$full_row = $this->cache['rows'][$cache_key]['full_row'];
				$full_row = str_replace('%rowname%', $row_name, $full_row);
				//dummy will be stored for every row, cause there could be multiple rows with the same $cache_key but different locations and names in the template
				$dummy = $this->cache['rows'][$row_name]['dummy'];
				if(!$dummy) {
					//create new dummy
					$dummy = '<!-- %_' . mt_rand(74524,459865892) . '_% -->';
					$replace = $full_row;
					$this->cache['rows'][$row_name]['dummy'] = $dummy;
				}else{
					$replace = $dummy;
				}
			}
		}else{
			$pattern = '/(<!-- ?ROW name=")' . $row_name . '(" ?-->)(.*)(<!-- ?END ?ROW name=")' . $row_name . '(" ?-->)/sUui';
			preg_match($pattern, $this->output, $matches);
			$row = $this->sub_ifs('enable', $matches[3]);
			$full_row = $matches[0];
			if($cache_key == $row_name) {
				$tmp = $full_row;
			}else{
				$tmp = $matches[1] . '%rowname%' . $matches[2] . $matches[3] . $matches[4] . '%rowname%' . $matches[5];
			}
			$dummy = '<!-- %_' . mt_rand(74524,459865892) . '_% -->';
			//set cache entries
			$this->cache['rows'][$cache_key]['row'] = $row;
			$this->cache['rows'][$cache_key]['full_row'] = $tmp;
			if($cache_key != $row_name) {
				$this->cache['rows'][$cache_key]['is_cache_key'] = true;
				$this->cache['rows'][$row_name]['cache_key'] = $cache_key;
			}
			$this->cache['rows'][$row_name]['dummy'] = $dummy;
			$replace = $full_row;
		}
		$insert = '';
		if($multi_replace) {
			foreach($data as $i) {
				$tmp = $row;
				foreach($i as $x => $y) {
					$tmp = str_replace('{@' . $x . '@}', $y, $tmp);
				}
				$insert .= $tmp . "\n";
			}
		}else{
				$tmp = $row;
				foreach($data as $x => $y) {
					$tmp = str_replace('{@' . $x . '@}', $y, $tmp);
				}
				$insert .= $tmp . "\n";
		}
		if(!$fulltext_cache) {
			//insert the new row(s)
			//insert the dummy, just in case that we are going to add rows again
			$this->output = str_replace($replace, $insert . "\n" . $dummy, $this->output);
		}else{
			//cache the new row(s)
			$this->cache['rows']['last_insert']['text'] = $insert;
			$this->cache['rows']['last_insert']['key'] = $row_name;
		}
		//return the new row(s), that is needed for some functions
		return $insert;
	}
	//
	//	This function will handle the multi lang links at the top of the page
	//
	//	$langs = avaiable langs as array (eg. ['en'] => english)
	//
	function lang($langs = NULL) {
		global $available_languages;
		if(!$langs) {
			$langs = $available_languages;
		}
		$i = 0;
		foreach($langs as $code => $name) {
			if($i != 0) {
				$this->set('lang', ' - ', 'append');
			}
			if(stristr($_SERVER['REQUEST_URI'], 'uselang=')) {
				$this->set('lang', '<a href="' . preg_replace('/uselang\=[a-z]*/i', 'uselang=' . $code, $_SERVER['REQUEST_URI']). '">' . $name . '</a>', 'append');
			}else{
				if(stristr($_SERVER['REQUEST_URI'], '?')) {
					$this->set('lang', '<a href="' . $_SERVER['REQUEST_URI'] . '&uselang=' . $code . '">' . $name . '</a>', 'append');
				}else{
					$this->set('lang', '<a href="' . $_SERVER['REQUEST_URI'] . '?uselang=' . $code . '">' . $name . '</a>', 'append');
				}
			}
			$i = 1;
		}
	}
	//
	//	This function will finaly parse the page and print the results
	//
	function parse_page() {
		global $uselang, $page_name, $_LANG, $_CONFIG;
		$this->output = $this->sub_ifs('enable', $this->output);
		//apply rows
		if($this->cache['rows']['last_insert']) {
			$tmp = $this->cache['rows']['last_insert']['key'];
			$this->cache['rows'][$tmp]['insert'] .= $this->cache['rows']['last_insert']['text'];
			unset($this->cache['rows']['last_insert']);
		}
		if(is_array($this->cache['rows'])) {
			foreach($this->cache['rows'] as $row_name => $i) {
				if(!$i['is_cache_key']) {
					if($i['cache_key']) {
						$cache_key = $i['cache_key'];
						$full_row = str_replace('%rowname%', $row_name, $this->cache['rows'][$cache_key]['full_row']);
					}else{
						$full_row = $i['full_row'];
					}
					$full_row = $this->sub_ifs('enable', $full_row);
					$this->output = str_replace($full_row, $i['insert'], $this->output);
				}
			}
		}
		//resolve includes
		$this->resolve_imports();
		//some often needed ifs
		$this->template_if('lang', $this->data['lang']);
		$this->template_if('page', $this->data['page']);
		//process $this->data
		foreach($this->data as $var => $replace) {
			$this->output = str_ireplace('{' . $var . '}', $replace, $this->output);
		}
		//remove all the ifs that haven't been called
		$this->template_if('[0-9a-z_-]*', 'false', NULL, false);
		//var $_LANG['foo'] as {lang->foo}
		if(is_array($_LANG)) {
			foreach($_LANG as $var => $replace) {
				$this->output = str_ireplace('{lang->' . $var . '}', $replace, $this->output);
			}
		}
		//same as above with $_CONFIG['foo'] as {config->foo}
		foreach($_CONFIG as $var => $replace) {
			$this->output = str_ireplace('{config->' . $var . '}', $replace, $this->output);
		}
		echo $this->output;
		return $this->output;
	}
}
?>