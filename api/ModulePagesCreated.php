<?php
/*
* [[m:User:Hoo man]]; Last update: 2012-07-27
* Gives a list of created pages
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


class ModulePagesCreated extends hoo_api {
	public function __construct($action) {
		$params = array();
		// required
		$params['wiki_db'] = array(
			'name' => 'wiki',
			'info' => 'The database name of the wiki to search in',
			'optional' => false,
			'type' => 'str'
		);
		$params['user'] = array(
			'name' => 'user_name',
			'info' => 'The user to get created pages from',
			'optional' => false,
			'type' => 'str'
		);
		//optional
		$params['from'] = array(
			'name' => 'from',
			'info' => 'Unix timestamp from which page creations are taken into account (default: now)',
			'optional' => true,
			'default' => false,
			'type' => 'int'
		);
		$params['to'] = array(
			'name' => 'to',
			'info' => 'Unix timestamp to which page creations are getting counted (default: 0)',
			'optional' => true,
			'default' => false,
			'type' => 'int'
		);
		$params['namespaces'] = array(
			'name' => 'namespaces',
			'info' => 'Namespaces from which pages are taken (default: all, seperate with |)',
			'optional' => true,
			'default' => false,
			'type' => 'list'
		);
		$params['excludeRedirects'] = array(
			'name' => 'excludeRedirects',
			'info' => 'Exclude redirects',
			'optional' => true,
			'default' => false,
			'type' => 'bool'
		);
		$this->init_api('pagesCreated', 'Gives a list of pages created by a user', $params, $action, 0);
	}
	public function run($input) {
		$user_name = str_replace('_', ' ', strip_tags($input['user']));
		//from
		$from = false;
		if($input['from'] !== false) {
			$date = date_create('@' . $input['from']);
			$from = date_format($date, 'YmdHis');
		}
		//to
		$to = false;
		if($input['to'] !== false) {
			$date = date_create('@' . $input['to']);
			$to = date_format($date, 'YmdHis');
		}
		//namespaces
		$namespaces_list = false;
		if($input['namespaces'] !== false) {
			$namespaces_list = '';
			$y = false;
			foreach($input['namespaces'] as $i) {
				$i = (int) $i; //typecast to int
				if($y) {
					$namespaces_list .= ',' . $i;
				}else{
					$namespaces_list .= $i;
					$y = true;
				}
			}
		}
		$db = &$this->wiki_db($input['wiki_db']);
		if(!$db) {
			$this->show_machine_redable_error('Database error');
		}
		$SQL_query = 'SELECT /* LIMIT:60 NM*/ sub.* FROM (';
		$SQL_query .= 'SELECT IF((page_namespace = 0), page_title, CONCAT(namespacename.ns_name , ":", page_title)) AS full_title, page_id, page_namespace, page_is_redirect, rev_timestamp AS creation_time FROM ' . $input['wiki_db'] . '.revision INNER JOIN ' . $input['wiki_db'] . '.page ON page_id = rev_page INNER JOIN toolserver.namespacename ON namespacename.ns_id = page_namespace';
		$SQL_query .= ' WHERE rev_user_text = :user_name AND rev_parent_id = 0 AND namespacename.dbname = "' . $input['wiki_db'] . '" AND namespacename.ns_is_favorite = 1';
		if($to !== false) {
			$SQL_query .= ' AND rev_timestamp > :to';
		}
		if($from !== false) {
			$SQL_query .= ' AND rev_timestamp < :from';
		}
		if($input['excludeRedirects']) {
			$SQL_query .= ' AND page_is_redirect = 0';
		}
		if($namespaces_list !== false) {
			$SQL_query .= ' AND page.page_namespace IN(' . $namespaces_list . ')';
		}
		//to ensure we don't have moved/ imported pages in there
		$SQL_query .= ') AS sub INNER JOIN ' . $input['wiki_db'] . '.revision ON sub.page_id = rev_page GROUP BY rev_page HAVING sub.creation_time = MIN(rev_timestamp)';
		$statement = $db->prepare($SQL_query);
		$statement->bindValue(':user_name', $user_name, PDO::PARAM_STR);
		if($to) {
			$statement->bindValue(':to', $to, PDO::PARAM_STR);
		}
		if($from) {
			$statement->bindValue(':from', $from, PDO::PARAM_STR);
		}
		$statement->execute();
		$pages_created = $statement->fetchALL(PDO::FETCH_ASSOC);
		if($statement->errorCode() != 00000) {
			$this->show_machine_redable_error('Database error');
		}
		if(is_array($pages_created) && isset($pages_created[0])) {
			foreach($pages_created as $row) {
				$tmp = array('pageid' => $row['page_id'], 'ns' => $row['page_namespace'], 'title' => str_replace('_', ' ', $row['full_title']), 'creation_time' => $row['creation_time']);
				if($row['page_is_redirect']) {
					$tmp['redirect'] = '';
				}
				$output[] = $tmp;
			}
		}else{
			$output[0] = array();
		}
		return $this->format_output( $output, $this->replag( $input['wiki_db'] ) );
	}
}

$pagesCreated = new ModulePagesCreated( $action );
?>
