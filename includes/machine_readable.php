<?php
/*
 * Static class for machine readable output
 * [[m:User:Hoo man]]; Last update: 2012-09-30
 */

//
//	Static class for machine readable output
//	Assumes that MACHINE_READABLE_FORMAT is set to a valid format
//
class machine_readable {
	public static $supported_formats = array('xml', 'json', 'print_r', 'serialize');
	//
	//	Prints an error in a machine readable way and exit
	//
	public static function show_error($error = 'Unknown error') {
		if(!headers_sent()) {
			header('HTTP/1.0 500 Internal Server Error');
		}
		self::set_headers(MACHINE_READABLE_FORMAT, 0);
		echo self::format_output('error', array(), -1, false, $error);
		exit(1);
	}
	//
	//	Sets the headers for the given format and expiry
	//
	public static function set_headers( $format = MACHINE_READABLE_FORMAT, $expires = null ) {
		if(headers_sent()) {
			return false;
		}
		if($expires !== null) {
			header('Expires: ' . gmdate('D, d M Y H:i:s \G\M\T', time() + $expires) );
			header('Cache-Control: max-age=' . $expires . ', must-revalidate');
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
	//	TODO: $continue
	//
	public static function format_output($name, $data, $replag = -1, $continue = false, $error = false, $format = MACHINE_READABLE_FORMAT) {
		//format the output according to $format
		if(!$error) {
			$error = 'false';
		}
		$data = array('api' => array(strtolower($name) => $data, 'replag' => $replag, 'error' => $error));
		switch( $format ) {
			case 'json':
				return json_encode( $data );
			break;
			case 'xml':
				return '<?xml version="1.0" encoding="UTF-8"?>' . self::format_xml( $data );
			break;
			case 'print_r':
				return print_r( $data, true );
			break;
			case 'serialize':
				return serialize( $data );
			break;
		}
	}
	//
	//	Converts an array into a xml string
	//
	public static function format_xml( $data ) {
		if(!is_array($data)) {
			return false;
		}
		$output = '';
		foreach($data as $key => $value) {
			//<root
			if(!self::validate_tag_name($key)) {
				throw new Exception('Invalid input, tag names should only contain letters and underscores and begin with a letter');
			}
			$output .= '<' . self::escape_tag_name($key);
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
							$inner .= self::format_xml( array($foo => $bar) );
						}else{
							// create an empty sub node, just to keep the output consistent
							$inner .= self::format_xml( array($foo => '') );
						}
					}else{
						// $bar is no array, so make it foo="bar"
						if(!is_numeric($foo)) {
							if(substr($output, -1) !== ' ') {
								$output .= ' ';
							}
							if(!self::validate_tag_name($foo)) {
								throw new Exception('Invalid input, attribute names should only contain letters and underscores and begin with a letter');
							}
							$output .= str_replace(' ', '_', $foo) . '="' . htmlspecialchars($bar, ENT_COMPAT, 'UTF-8') . '"';
						}else{
							//we can't create attributes with numbers
							//create an sub element instead
							$inner .= self::format_xml( array($key => $bar) );
						}
					}
				}
				if($inner !== '') {
					$output .= '>' . $inner . '</' . self::escape_tag_name($key) . '>';
				}else{
					$output .= '/>';
				}
			}else{
				//content is no array, so just embed like <foo>bar</foo>
				$output .= '>' . htmlspecialchars($value, ENT_COMPAT, 'UTF-8');
				$output .= '</' . self::escape_tag_name($key) . '>';
			}
		}
		return $output;
	}
	//
	//	Helper function for self::format_xml which escapes erroneous chars in tag names into _
	//
	protected static function escape_tag_name($str) {
		return str_replace( ' ', '_', $str );
	}
	//
	//	Helper function for self::format_xml which validates tag and attribute names
	//
	protected static function validate_tag_name($str) {
		return (preg_match('@^[a-z]+[a-z_ ]*$@i', $str) !== 0);
	}
}
