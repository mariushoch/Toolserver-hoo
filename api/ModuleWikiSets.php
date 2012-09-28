<?php
/*
* [[m:User:Hoo man]]; Last update: 2012-07-27
* Gives the number of active sysops
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

class ModuleWikiSets extends hoo_api {
	public function __construct($action) {
		$params = array();
		// required
		$params['wikiset'] = array(
			'name' => 'wikiset',
			'info' => 'The ids of the wikisets to look up (seperate by |)',
			'optional' => false,
			'type' => 'list'
		);
		//optional
		$params['prop'] = array(
			'name' => 'prop',
			'info' => 'The information to return (ws_wikis, ws_type, ws_name; default: ws_wikis; separate with |)',
			'optional' => true,
			'default' => array('ws_wikis'),
			'type' => 'list'
		);
		$this->init_api('wikiSets', 'Shows information about one or more wiki sets', $params, $action, 129600);
	}
	public function run($input) {
		//wikiset id
		$notFirst = false;
		$wikiset_list = '';
		foreach($input['wikiset'] as $i) {
			$i = (int) $i;
			if($notFirst) {
				$wikiset_list .= ',' . $i;
			}else{
				$wikiset_list .= $i;
				$notFirst = true;
			}
		}
		//return list
		$notFirst = false;
		$return_list = '';
		foreach($input['prop'] as $i) {
			$i = trim($i);
			if($i !== 'ws_wikis' && $i !== 'ws_type' && $i !== 'ws_name') {
				$this->show_machine_redable_error('Unknown &prop');
			}
			if($notFirst) {
				$return_list .= ',' . $i;
			}else{
				$return_list .= $i;
				$notFirst = true;
			}
		}
		$db = &$this->wiki_db('metawiki_p');
		if(!$db) {
			$this->show_machine_redable_error('Database error');
		}
		//get data
		$SQL_query = 'SELECT ws_id, ' . $return_list . ' FROM centralauth_p.wikiset WHERE ws_id IN(' . $wikiset_list . ')';
		$statement = $db->prepare($SQL_query);
		$statement->execute();
		$data = $statement->fetchAll(PDO::FETCH_ASSOC);
		if($statement->errorCode() != 00000 || !is_array($data)) {
			$this->show_machine_redable_error('Database error');
		}
		if(is_array($data) && isset($data[0])) {
			foreach($data as $row) {
				#$tmp = array('pageid' => $row['page_id'], 'ns' => $row['page_namespace'], 'title' => str_replace('_', ' ', $row['full_title']), 'creation_time' => $row['creation_time']);
				$tmp['ws_id'] = $row['ws_id'];
				if(in_array('ws_wikis', $input['prop'])) {
					$tmp['ws_wikis'] = explode(',', $row['ws_wikis']);
				}
				if(in_array('ws_type', $input['prop'])) {
					$tmp['ws_type'] = $row['ws_type'];
				}
				if(in_array('ws_name', $input['prop'])) {
					$tmp['ws_name'] = $row['ws_name'];
				}
				$output[] = $tmp;
			}
		}else{
			$output[0] = array();
		}
		return $this->format_output( $output, $this->replag( 'metawiki_p' ) );
	}
}

$wikiSets = new ModuleWikiSets( $action );
?>
