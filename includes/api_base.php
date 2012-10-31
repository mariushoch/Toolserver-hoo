<?php
/*
 * API base class
 * [[m:User:Hoo man]]; Last update: 2012-09-30
 */

abstract class hoo_api extends hoo_base {
	//
	//	Manage the input and run ::run()
	//
	public function exec($params) {
		$params = $this->manage_input($params);
		return $this->run($params);
	}
	//
	//	Takes the data from ::run() and handles it
	//	to assure we always return the data in the same format,
	//	no matter what
	//
	protected function return_data($data, $replag = -1, $continue = false) {
		return array('data' => $data, 'replag' => $replag, 'continue' => $continue);
	}
	//
	//	Function which manages the user input
	//	returns an array on success and false or a string on error
	//
	protected function manage_input($input) {
		$params = $this->get_params();
		$output = array();
		foreach($params as $name => $info) {
			if(!is_array($info)) {
				throw new exception('Erroneous API parameter definition');
			}
			if(!isset($input[ $info['name'] ])) {
				//no need to go one, not in the input
				if($info['optional'] === false) {
					throw new exception('Missing parameter: ' . $info['name']);
				}else{
					$output[ $name ] = $info['default'];
					continue;
				}
			}
			if($info['type'] === 'int') {
				$tmp = (int) $input[ $info['name'] ];
			}else if( $info['type'] === 'list' ) {
				$tmp = $input[ $info['name'] ];
				if(!is_array($tmp)) {
					//$tmp is no real array, so we try to create one
					if($tmp !== null && stripos($tmp, '|') !== false) {
						// Input like foo=blah|bar|whatever (from browser input)
						$tmp = explode('|', $tmp);
					}elseif($tmp !== null) {
						// $tmp doesn't contain a pipe and is no array
						// so just use it as is
						$tmp = array($tmp);
					}
				}
			}else if( $info['type'] === 'wiki' ) {
				// TODO: Handle different inputs like foo.wikipedia.org,
				// foowiki_p... probably in hoo_base
				$tmp = $this->wiki_input( $input[ $info['name'] ] );
				if(!$tmp) {
					throw new exception('Missing unknown/ invalid wiki: ' . $input[ $info['name'] ]);
				}
			}else{
				$tmp = $input[ $info['name'] ];
			}
			$output[ $name ] = $tmp;
		}
		return $output;
	}
	//
	//	Module information, with defaults
	//
	
	//
	//	Returns a bool whether hits should be counted
	//
	public function count_hits() {
		return true;
	}
	//
	//	Cache expiry, if served directly
	//
	public function get_expiry() {
		return 3600;
	}
	//
	//	Is this module publically usable and visible in action=info
	//
	public function is_public() {
		return true;
	}
	//
	//	Whether this module has it's own output module
	//
	public function has_own_output() {
		return false;
	}

	//
	//	Abstract functions serving module informations/ functionality
	//

	//
	//	Returns an array with allowed parameters
	//
	abstract public function get_params();
	//
	//	Description of the API module
	//
	abstract public function get_description();
	//
	//	Name (=action) of the api module
	//
	abstract public function get_name();
	//
	//	Run the actual module
	//	protected as this shouldn't be called directly
	//
	abstract protected function run($input);
}
