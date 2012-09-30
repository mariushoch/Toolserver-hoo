<?php
/*
* [[m:User:Hoo man]]; Last update: 2012-09-30
* Gives information about all avaiable API modules.
* Not for embed usage, directly prints it's output
*/

/*
	This program is free software: you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation, either version 3 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

class apiInfo extends hoo_api {
	public  static function main() {
		$info = new apiInfo();
		return $info;
	}
	public function get_description() {
		return 'Gives information about all avaiable API modules.';
	}
	public function get_name() {
		return 'info';
	}
	public function get_params() {
		return array();
	}
	public function has_own_output() {
		return true;
	}
	//
	//	Build the actual module-wise output
	//
	private function output($apiObject) {
		$output = '<b>* action=' . $apiObject->get_name() . " *</b>\n"
		 . $apiObject->get_description() . "\n\n<b>Parameters:</b>\n<b>Required:</b>\n";
		$optional = '';
		foreach($apiObject->get_params() as $name => $info) {
			if($info['optional'] === false) {
				$output .= $info['name'] . str_repeat(' ', 25 - strlen( $info['name'] )) . $info['info'] . "\n";
			}else{
				$optional .= $info['name'] . str_repeat(' ', 25 - strlen( $info['name'] )) . $info['info'] . "\n";
			}
		}
		$output .= "\n<b>Optional:</b>\n" . $optional . "\n\n";
		return $output;
	}
	protected function run($input) {
		global $_CONFIG;
		//directly print, as we can hardly use the default output methods
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
		foreach($_CONFIG['api_modules'] as $action => $class) {
			$api_module = $class::main();
			if($api_module->is_public()) {
				echo $this->output($api_module);
			}
		}
		echo "</pre>
	</body>
	</html>";
	}
}
