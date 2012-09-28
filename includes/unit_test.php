<?php
//if smth. went wrong
function went_wrong($test) {
	echo "\n" . $test . " - failed!!!\n\n";
}
//if smth. went wright
function went_wright($test) {
	echo $test . " - passed\n";
}
//these values must equal:
function m_equal($test, $a, $b) {
	if($a != $b) {
		went_wrong($test);
	}else{
		went_wright($test);
	}
}
//these values mustn't equal
function m_n_equal($test, $a, $b) {
	if($a == $b) {
		went_wrong($test);
	}else{
		went_wright($test);
	}
}
//these values mustn't equal
function m_n_equal_strict($test, $a, $b) {
	if($a === $b) {
		went_wrong($test);
	}else{
		went_wright($test);
	}
}

define('IN_HOO_TS', true);
try {
	require_once('/home/hoo/public_html/includes/config.php');
	require_once($_CONFIG['include_path'] . 'hoo.php');

	//hoo part
	$hoo = new hoo_html();

	// $hoo->is_ip();
	
	m_equal('$hoo->is_ip("1.1.1.1")', $hoo->is_ip("1.1.1.1"), true);
	m_equal('$hoo->is_ip("256.22.22.1")', $hoo->is_ip("256.22.22.1"), false);
	m_equal('$hoo->is_ip("2A01:E35:8A7C:E14F:F6CA:E5FF:FE54:7F1E")', $hoo->is_ip("2A01:E35:8A7C:E14F:F6CA:E5FF:FE54:7F1E"), true);
	m_equal('$hoo->is_ip("SA01:E35:8A7C:E14F:FGGA:E5FF:FE54:WF1E")', $hoo->is_ip("SA01:E35:8A7C:E14F:FGGA:E5FF:FE54:WF1E"), false);

	// $hoo->view_count();
	
	m_equal('$hoo->view_count("unit_test", "unit_test")', $hoo->view_count("unit_test", "unit_test"), true);
	//(this needs post clean up)
	
	// $hoo->wiki_db();
	
	//query enwiki_p for my user name
	$db = &$hoo->wiki_db("enwiki_p");
	m_n_equal('$db = &$hoo->wiki_db("enwiki_p");', $db, false);
	$SQL_query = "SELECT user_name FROM enwiki_p.user WHERE user_id = 9863950";	
	$statement = $db->prepare($SQL_query);
	$statement->execute();
	$tmp = $statement->fetch(PDO::FETCH_ASSOC);
	m_equal("Test query on enwiki_p", $tmp['user_name'], 'Hoo man');
	//test whether $hoo->view_count() really worked
	$db = &$hoo->wiki_db("u_hoo_p");
	m_n_equal('$db = &$hoo->wiki_db("u_hoo_p")', $db, false);
	$SQL_query = "SELECT views FROM u_hoo_p.views WHERE page = 'unit_test' AND lang = 'unit_test' LIMIT 1";	
	$statement = $db->prepare($SQL_query);
	$statement->execute();
	$tmp = $statement->fetch(PDO::FETCH_ASSOC);
	m_equal('SQL check for $hoo->view_count("unit_test", "unit_test")', $tmp['views'], '1');
	
	// $hoo->replag();
	
	m_equal('$hoo->replag("not_existing_wiki");', $hoo->replag("not_existing_wiki"), false);
	m_n_equal_strict('$hoo->replag("enwiki_p");', $hoo->replag("enwiki_p"), false);
	m_n_equal_strict('$hoo->replag("dewiki_p");', $hoo->replag("dewiki_p"), false);
	m_n_equal_strict('$hoo->replag("eswiki_p");', $hoo->replag("eswiki_p"), false);
	m_n_equal_strict('$hoo->replag("itwiki_p");', $hoo->replag("itwiki_p"), false);
	
	// clean up
	$db = &$hoo->wiki_db("u_hoo_p");
	m_n_equal('$db = &$hoo->wiki_db("u_hoo_p")', $db, false);
	$SQL_query = "DELETE FROM u_hoo_p.views WHERE page = 'unit_test' AND lang = 'unit_test'";	
	$statement = $db->prepare($SQL_query);
	$statement->execute();
	
}catch(Exception $e){
	went_wrong('Exception ' . $e->getMessage() . ' occured');
}
