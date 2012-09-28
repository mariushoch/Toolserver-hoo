<?php
/*
 * API entry point script
 * [[m:User:Hoo man]]; Last update: 2012-07-26
 */
define('IN_HOO_TS', true);

///*
ini_set("display_errors", 1);
error_reporting(-1);
//*/

require_once('/home/hoo/public_html/includes/config.php');
require_once($_CONFIG['include_path'] . 'hoo.php');

$hoo = new hoo_html();

abstract class hoo_api extends hoo_html {
	public $format;
	public $params;
	public $expires;
	public $name;
	public $supported_formats = array('xml', 'json', 'print_r', 'serialize');
	//
	//	Inits several variables and calls ::run()
	//
	public function init_api($name, $info, $params, $action, $expires = 0) {
		//output format
		$format = $this->get_user_input('format');
		if($format) {
			if(!in_array($format, $this->supported_formats)) {
				//makes no sense to use an own error handler over here,
				//as we don't know the output format anyway
				$this->show_error('Unsupported format');
			}
			$this->format = $format;
		}else{
			$this->format = 'xml';
		}
		//default expiry
		$this->expires = $expires;
		//overwrite if set by user
		if($this->get_user_input('expires')) {
			$this->expires = $this->get_user_input('expires', 'int');
		}
		$this->name = $name;
		//parameters
		$this->params = $params;
		if( $action === 'info') {
			echo $this->info($name, $info, $params);
		}else{
			$user_data = $this->manage_input($params);
			if(!is_array($user_data)) {
				$this->show_machine_redable_error($user_data);
			}
			echo $this->run( $user_data );
		}
	}
	//
	//	Function which manages the user input
	//	returns an array on success and false or a string on error
	//
	protected function manage_input($params) {
		if(!is_array($params)) {
			return '$params must be an array';
		}
		$output = array();
		foreach($params as $name => $info) {
			if(!is_array($info)) {
				return false;
			}
			if($info['type'] === 'int') {
				$tmp = $this->get_user_input( $info['name'], 'int' );
			}else if( $info['type'] === 'list' ) {
				$tmp = $this->get_user_input( $info['name'] );
				if($tmp !== null && stripos($tmp, '|') !== false) {
					$tmp = explode('|', $tmp);
				}elseif($tmp !== null) {
					$tmp = array($tmp);
				}
			}else{
				$tmp = $this->get_user_input( $info['name'] );
			}
			if($tmp === null && $info['optional'] === false) {
				return 'Missing parameter: ' . $info['name'];
			}
			if($tmp === null && isset($info['default'])) {
				$tmp = $info['default'];
			}
			$output[ $name ] = $tmp;
		}
		return $output;
	}
	public function info($action, $info, $params) {
		if(!is_array($params)) {
			return false;
		}
		$output = '<b>* action=' . $action . " *</b>\n"
		 . $info . "\n\n<b>Parameters:</b>\n<b>Required:</b>\n";
		$optional = '';
		foreach($params as $name => $info) {
			if($info['optional'] === false) {
				$output .= $info['name'] . str_repeat(' ', 25 - strlen( $info['name'] )) . $info['info'] . "\n";
			}else{
				$optional .= $info['name'] . str_repeat(' ', 25 - strlen( $info['name'] )) . $info['info'] . "\n";
			}
		}
		$output .= "\n<b>Optional:</b>\n" . $optional . "\n\n";
		return $output;
	}
	//
	//	Prints an error in a machine readable way and exit
	//
	public function show_machine_redable_error($error = 'Unknown error') {
		if(!headers_sent()) {
			header('HTTP/1.0 500 Internal Server Error');			
		}
		$this->expires = 0;
		die($this->format_output(array(), -1, false, $error));
	}
	//
	//	Sets the headers for the given format
	//
	public function set_headers( $format = false ) {
		if(headers_sent()) {
			return false;
		}
		header('Expires: ' . gmdate('D, d M Y H:i:s \G\M\T', time() + $this->expires) );
		header('Cache-Control: max-age=' . $this->expires . ', must-revalidate');
		if(!$format) {
			$format = $this->format;
		}
		switch( $format ) {
			case 'json':
				header('Content-type: text/javascript; charset=utf-8');
			break;
			case 'xml':
				header('Content-type: text/xml; charset=utf-8');
			break;
			case 'print_r':
				header('Content-type: text/plain; charset=utf-8');
			break;
			case 'serialize':
				header('Content-type: x-php; charset=utf-8');
			break;
		}
	}
	//
	//	Formats the given input in a machine readable way
	//
	public function format_output($data, $replag = -1, $continue = false, $error = false, $format = false) {
		//format the output according to $this->format or $format
		if(!$format) {
			$format = $this->format;
		}
		if(!$error) {
			$error = 'false';
		}
		$data = array('api' => array(strtolower($this->name) => $data, 'replag' => $replag, 'error' => $error));
		if($format === 'json') {
			$this->set_headers( 'json' );
			$inner = json_encode( $data );
			//callback, assign to var or plain?
			$callback = $this->get_user_input( 'callback' );
			$var =  $this->get_user_input( 'js_var' );
			if($callback) {
				return $callback . '(' . $inner . ');';
			}
			if($var) {
				return 'var ' . $var . ' = ' . $inner . ';';
			}
			return $inner;
		}
		if($format === 'xml') {
			$this->set_headers( 'xml' );
			return '<?xml version="1.0" encoding="UTF-8"?>' . $this->format_xml( $data );
		}
		if($format === 'print_r') {
			$this->set_headers( 'print_r' );
			return print_r( $data, true );
		}
		if($format === 'serialize') {
			return serialize( $data );
		}
	}
	//
	//	Converts an array into a xml string
	//
	public function format_xml( $data ) {
		if(!is_array($data)) {
			return false;
		}
		$output = '';
		foreach($data as $key => $value) {
			//<root
			$output .= '<' . str_replace( '/', '_', str_replace( '>', '_', str_replace( ' ', '_', $key ) ) );
			if(is_array($value)) {
				//content is array
				$inner = '';
				foreach($value as $foo => $bar) {
					if(is_array($bar)) {
						if(is_numeric($foo)) {
							//we can't create elements with numbers
							$foo = $key;
						}
						if($bar !== array()) {
							//$bar is an array as well, so create sub nodes
							$inner .= $this->format_xml( array($foo => $bar) );
						}else{
							// create an empty sub node, just to keep the output consistent
							$inner .= $this->format_xml( array($foo => '') );
						}
					}else{
						// $bar is no array, so make it foo="bar"
						if(!is_numeric($foo)) {
							if(substr($output, -1) !== ' ') {
								$output .= ' ';
							}
							$output .= htmlspecialchars(str_replace(' ', '_', $foo), ENT_COMPAT, 'UTF-8') . '="' . str_replace('"', '\"', $bar) . '" ';
						}else{
							//we can't create attributes with numbers
							//create an sub element instead
							$inner .= $this->format_xml( array($key => $bar) );
						}
					}
				}
				if($inner !== '') {
					$output .= '>' . $inner . '</' . str_replace( '/', '_', str_replace( '>', '_', str_replace( ' ', '_', $key ) ) ) . '>';
				}else{
					$output .= '/>';
				}
			}else{
				//content is no array, so just embed like <foo>bar</foo>
				$output .= '>' . htmlspecialchars($value, ENT_COMPAT, 'UTF-8');
				$output .= '</' . str_replace( '/', '_', str_replace( '>', '_', str_replace( ' ', '_', $key ) ) ) . '>';
			}
		}
		return $output;
	}
}
$action = $hoo->get_user_input('action');
if(!$action) {
	//default action (list all modules)
	$action = 'info';
	echo <<<INFO
<!DOCTYPE HTML>
<html>
<head>
	<title>hoo's API</title>
</head>
<body>
	<center><h1>hoo's API</h1></center><br /><br />
	Global parameters:<br />
	<table border="1">
		<tr>
			<th>Parameter</th><th>Description</th>
		</tr>
		<tr>
			<td>action</td><td>Given by the used module</td>
		</tr>
		<tr>
			<td>format</td><td>Output format, must be either xml (default), json, serialize or print_r</td>
		</tr>
		<tr>
			<td>callback</td><td>Name of the callback function, for the JSON output</td>
		</tr>
		<tr>
			<td>js_var</td><td>Name of the var the output should be assigned to, for the JSON output</td>
		</tr>
		<tr>
			<td>expires</td><td>Time in seconds until the result expires</td>
		</tr>
	</table>
<pre>
	
*** *** *** *** *** *** *** *** *** *** *** *** *** ***  Modules  *** *** *** *** *** *** *** *** *** *** *** *** *** *** 

INFO;
}else if(!isset($_CONFIG['api_modules'][ $action ])) {
	//unknown module
	$hoo->show_error('Unknown action');
}
$hoo->view_count($action, 'api');
if($action === 'info') {
	foreach($_CONFIG['api_modules'] as $name => $file) {
		include_once( $_CONFIG['api_include_path'] . $file );
	}
}else{
	require_once( $_CONFIG['api_include_path'] . $_CONFIG['api_modules'][ $action ] );
}
if($action === 'info') {
	echo "</pre>
	</body>
	</html>";
}
?>
