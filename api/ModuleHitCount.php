<?php
/*
* [[m:User:Hoo man]]; Last update: 2012-08-05
* Counts visits to projects per language
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

class ModuleHitCount extends hoo_api {
	public function __construct($action) {
		$params = array();
		// required
		$params['project'] = array(
			'name' => 'project',
			'info' => 'The project that has been viewed',
			'optional' => false,
			'type' => 'str'
		);
		$params['lang'] = array(
			'name' => 'lang',
			'info' => 'The language the project has been used in',
			'optional' => false,
			'type' => 'str'
		);
		$this->init_api('hitCount', 'Counts visits to projects per language (for internal use by hoo only)', $params, $action);
	}
	public function run($input) {
		$allowed_projects = array('wikilint');
		$allowed_langs = array('de', 'en');
		if(!in_array($input['project'], $allowed_projects) || !in_array($input['lang'], $allowed_langs)) {
			$this->show_machine_redable_error('Unknown project or language');
		}
		if($this->view_count('hitCount-' . $input['project'], $input['lang'])) {
			return $this->format_output( array());
		}else{
			$this->show_machine_redable_error('Database error');
		}
	}
}

$hitCount = new ModuleHitCount( $action );
?>
