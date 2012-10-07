<?php
/*
* [[m:User:Hoo man]]; Last update: 2012-09-30
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

class apiActiveSysops extends hoo_api {
	public static function main() {
		$activeSysops = new apiActiveSysops();
		return $activeSysops;
	}
	public function get_expiry() {
		return 86400;
	}
	public function get_description() {
		return 'Shows the number of administrators who where recently active';
	}
	public function get_name() {
		return 'activeSysops';
	}
	public function get_params() {
		return array(
			// required
			'wiki_db' => array(
				'name' => 'wiki',
				'info' => 'The database name of the wiki to search in',
				'optional' => false,
				'type' => 'wiki'
			),
			//optional
			'last_action_time' => array(
				'name' => 'last_action',
				'info' => 'Time in seconds since the last action to count the sysop as active (default: one week)',
				'optional' => true,
				'default' => 604800,
				'type' => 'int'
			)
		);
	}
	protected function run($input) {
		$input['last_action_time'] = time() - $input['last_action_time'];
		$date = new DateTime('@' . $input['last_action_time']);
		$last_action_time = $date->format('YmdHis');
		$db = &$this->wiki_db($input['wiki_db']);
		if(!$db) {
			throw new database_exception('Couldn\'t connect to database: ' . $input['wiki_db']);
		}
		//get number of active sysops
		//$input['wiki_db'] is trusty, as the above would have exited, if it wasn't a known DB
		$SQL_query = 'SELECT COUNT(*) /* LIMIT:15 NM */ AS active_sysops FROM (SELECT log_user AS user FROM ' . $input['wiki_db'] . '.logging WHERE log_type IN ("block","delete","protect") AND log_timestamp > :last_action_time GROUP BY log_user) as active_users INNER JOIN ' . $input['wiki_db'] . '.user_groups ON ug_user = active_users.user WHERE ug_group = "sysop"';
		$statement = $db->prepare($SQL_query);
		$statement->bindValue(':last_action_time', $last_action_time, PDO::PARAM_STR);
		$statement->execute();
		$active_sysops = $statement->fetchColumn(0);
		if($statement->errorCode() != 00000 || !is_numeric($active_sysops)) {
			throw new database_exception('Database error: ' . $input['wiki_db']);
		}
		return $this->return_data( array('count' => $active_sysops), $this->replag( $input['wiki_db'] ) );
	}
}
