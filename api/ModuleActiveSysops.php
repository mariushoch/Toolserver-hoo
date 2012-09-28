<?php
/*
* [[m:User:Hoo man]]; Last update: 2012-07-26
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

class ModuleActiveSysops extends hoo_api {
	public function __construct($action) {
		$params = array();
		// required
		$params['wiki_db'] = array(
			'name' => 'wiki',
			'info' => 'The database name of the wiki to search in',
			'optional' => false,
			'type' => 'str'
		);
		//optional
		$params['last_action_time'] = array(
			'name' => 'last_action',
			'info' => 'Time in seconds since the last action to count the sysop as active (default: one week)',
			'optional' => true,
			'default' => 604800,
			'type' => 'int'
		);
		$this->init_api('activeSysops', 'Shows the number of administrators who where recently active', $params, $action, 86400);
	}
	public function run($input) {
		$input['last_action_time'] = time() - $input['last_action_time'];
		$date = date_create('@' . $input['last_action_time'] );
		$last_action_time = date_format($date, 'YmdHis');
		$db = &$this->wiki_db($input['wiki_db']);
		if(!$db) {
			$this->show_machine_redable_error('Database error');
		}
		//get number of active sysops
		//$input['wiki_db'] is trusty, as the above would have exited, if it wasn't a known DB
		$SQL_query = 'SELECT COUNT(*) /* LIMIT: 15 */ AS active_sysops FROM (SELECT log_user AS user FROM ' . $input['wiki_db'] . '.logging WHERE log_type IN ("block","delete","protect") AND log_timestamp > :last_action_time GROUP BY log_user) as active_users INNER JOIN ' . $input['wiki_db'] . '.user_groups ON ug_user = active_users.user WHERE ug_group = "sysop"';
		$statement = $db->prepare($SQL_query);
		$statement->bindValue(':last_action_time', $last_action_time, PDO::PARAM_STR);
		$statement->execute();
		$active_sysops = $statement->fetchColumn(0);
		if(!is_numeric($active_sysops)) {
			$this->show_machine_redable_error('Database error');
		}
		return $this->format_output( array('count' => $active_sysops), $this->replag( $input['wiki_db'] ) );
	}
}

$activeSysops = new ModuleActiveSysops( $action );
?>
