<?php
/*
* [[m:User:Hoo man]]; Last update: 2012-09-30
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

class apiHitCount extends hoo_api {
	public static function main() {
		$hitCount = new apiHitCount();
		return $hitCount;
	}
	public function get_description() {
		return 'Counts visits to projects per language (for internal use by hoo only)';
	}
	public function get_name() {
		return 'hitCount';
	}
	public function count_hits() {
		// We count the hits at our own
		return false;
	}
	public function get_params() {
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
		return $params;
	}
	protected function run($input) {
		global $_SQL;
		$allowed_projects = array('wikilint');
		$allowed_langs = array('de', 'en');
		if(!in_array($input['project'], $allowed_projects) || !in_array($input['lang'], $allowed_langs)) {
			throw new Exception('Unknown project or language');
		}
		if($this->view_count('hitCount-' . $input['project'], $input['lang'])) {
			return $this->return_data( array());
		}else{
			throw new database_exception('Database error: ' . $_SQL['misc_db']['db_name']);
		}
	}
}
