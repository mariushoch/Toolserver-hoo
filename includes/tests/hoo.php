<?php
/*
 * Unit tests for hoo_base
 * [[m:User:Hoo man]]; Last update: 2012-09-30
 */
if(!defined('IN_HOO_TS')) {
	exit();
}

$hoo = new hoo_base();

// $hoo->get_user_input();
// Set some $_GET and $_POST values

$_GET = array('foo' => 'bar', 'get' => 'true');
$_POST = array('foo' => 'bar2', 'nr' => '23b', 'bold' => '<b>Bold!</b>');
m_equal_strict('hoo_base:: get_user_input("get");', $hoo->get_user_input("get"), 'true');
// GET overwrites POST
m_equal_strict('hoo_base:: get_user_input("foo");', $hoo->get_user_input("foo"), 'bar');
m_equal_strict('hoo_base:: get_user_input("nr", "int");', $hoo->get_user_input("nr", "int"), 23);
m_equal_strict('hoo_base:: get_user_input("nr", "int", "post");', $hoo->get_user_input("nr", "int", "post"), 23);
m_equal_strict('hoo_base:: get_user_input("nr", "int", "get");', $hoo->get_user_input("nr", "int", "get"), null);
m_equal_strict('hoo_base:: get_user_input("bold", "output");', $hoo->get_user_input("bold", "output"), '&lt;b&gt;Bold!&lt;/b&gt;');

// $hoo->load_db_map();

$dbs = $hoo->load_db_map();
m_equal('hoo_base:: load_db_map()', $dbs, true);
m_equal('hoo_base:: db_map["enwiki_p"]', $hoo->db_map["enwiki_p"], 1);

// $hoo->is_ip();

m_equal('hoo_base:: is_ip("1.1.1.1")', $hoo->is_ip("1.1.1.1"), true);
m_equal('hoo_base:: is_ip("256.22.22.1")', $hoo->is_ip("256.22.22.1"), false);
m_equal('hoo_base:: is_ip("2A01:E35:8A7C:E14F:F6CA:E5FF:FE54:7F1E")', $hoo->is_ip("2A01:E35:8A7C:E14F:F6CA:E5FF:FE54:7F1E"), true);
m_equal('hoo_base:: is_ip("SA01:E35:8A7C:E14F:FGGA:E5FF:FE54:WF1E")', $hoo->is_ip("SA01:E35:8A7C:E14F:FGGA:E5FF:FE54:WF1E"), false);

// $hoo->view_count();

//(this needs post clean up), see section clean up
m_equal('hoo_base:: view_count("unit_test", "unit_test")', $hoo->view_count("unit_test", "unit_test"), true);
//test whether $hoo->view_count() really worked
$db = &$hoo->wiki_db("u_hoo_p");
m_n_equal('hoo_base:: wiki_db("u_hoo_p")', $db, false);
$SQL_query = "SELECT views FROM u_hoo_p.views WHERE page = 'unit_test' AND lang = 'unit_test' LIMIT 1";	
$statement = $db->prepare($SQL_query);
$statement->execute();
$tmp = $statement->fetch(PDO::FETCH_ASSOC);
m_equal('hoo_base:: SQL check for view_count("unit_test", "unit_test")', $tmp['views'], '1');

// $hoo->wiki_db();

//query enwiki_p for my user name
$db = &$hoo->wiki_db("enwiki_p");
m_n_equal('hoo_base:: wiki_db("enwiki_p")', $db, false);
$SQL_query = "SELECT user_name FROM enwiki_p.user WHERE user_id = 9863950";	
$statement = $db->prepare($SQL_query);
$statement->execute();
$tmp = $statement->fetch(PDO::FETCH_ASSOC);
m_equal("hoo_base:: Test query on enwiki_p", $tmp['user_name'], 'Hoo man');
//invalid wiki
try{
	$hoo->wiki_db("not_existing_wiki");
	went_wrong('hoo_base:: wiki_db("not_existing_wiki");');
}catch(Exception $e){
	went_right('hoo_base:: wiki_db("not_existing_wiki");');
}

//	$hoo->replag();

try{
	$hoo->replag("not_existing_wiki");
	went_wrong('hoo_base:: replag("not_existing_wiki");');
}catch(Exception $e){
	went_right('hoo_base:: replag("not_existing_wiki");');
}
try{
	$hoo->replag("enwiki_p");
	went_right('hoo_base:: replag("enwiki_p")');
}catch(Exception $e){
	went_wrong('hoo_base:: replag("enwiki_p")');
}
try{
	$hoo->replag("eswiki_p");
	went_right('hoo_base:: replag("eswiki_p")');
}catch(Exception $e){
	went_wrong('hoo_base:: replag("eswiki_p")');
}
try{
	$hoo->replag("dewiki_p");
	went_right('hoo_base:: replag("dewiki_p")');
}catch(Exception $e){
	went_wrong('hoo_base:: replag("dewiki_p")');
}
try{
	$hoo->replag("commonswiki_p");
	went_right('hoo_base:: replag("commonswiki_p")');
}catch(Exception $e){
	went_wrong('hoo_base:: replag("commonswiki_p")');
}
try{
	$hoo->replag("metawiki_p");
	went_right('hoo_base:: replag("metawiki_p")');
}catch(Exception $e){
	went_wrong('hoo_base:: replag("metawiki_p")');
}
try{
	$hoo->replag("xmfwiki_p");
	went_right('hoo_base:: replag("xmfwiki_p")');
}catch(Exception $e){
	went_wrong('hoo_base:: replag("xmfwiki_p")');
}

//	$hoo->wiki_input();

// enwiki_p
m_equal('hoo_base:: wiki_input("enwiki_p")', $hoo->wiki_input("enwiki_p"), 'enwiki_p');

// enwiki
m_equal('hoo_base:: wiki_input("enwiki")', $hoo->wiki_input("enwiki"), 'enwiki_p');

// not_existing_wiki
try{
	$hoo->wiki_input("not_existing_wiki");
	went_wrong('hoo_base:: wiki_input("not_existing_wiki")');
}catch(Exception $e){
	went_right('hoo_base:: wiki_input("not_existing_wiki")');
}

//
//	clean up
//
$db = &$hoo->wiki_db("u_hoo_p");
m_n_equal('hoo_base:: wiki_db("u_hoo_p")', $db, false);
$SQL_query = "DELETE FROM u_hoo_p.views WHERE page = 'unit_test' AND lang = 'unit_test'";	
$statement = $db->prepare($SQL_query);
$statement->execute();
