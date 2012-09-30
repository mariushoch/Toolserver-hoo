<?php
/*
 * Unit tests for apiWikiSets
 * [[m:User:Hoo man]]; Last update: 2012-09-30
 */
if(!defined('IN_HOO_TS')) {
	exit();
}


$wikiSets = apiWikiSets::main();

// apiWikiSets:: array('wikiset' => 7, 'prop' => 'ws_wikis')

$res = $wikiSets->exec(array('wikiset' => 7, 'prop' => 'ws_wikis'));

$wikis = $res['data'][0];
if(!is_array($wikis['ws_wikis']) || count($wikis['ws_wikis']) < 100 || count($wikis['ws_wikis']) > 350) {
	went_wrong('apiWikiSets - wikiset 7, wikis');
}else{
	went_right('apiWikiSets - wikiset 7, wikis');
}
if(isset($wikis['ws_name'])) {
	went_wrong('apiWikiSets - unwanted properties');
}else{
	went_right('apiWikiSets - unwanted properties');
}

// apiWikiSets:: array('wikiset' => array(2, 7), 'prop' => array('ws_wikis', 'ws_type', 'ws_name'))

$res = $wikiSets->exec(array('wikiset' => array(2, 7), 'prop' => array('ws_wikis', 'ws_type', 'ws_name')));
$wikis = $res['data'];
// WS 2
if(!is_array($wikis[0]['ws_wikis']) || count($wikis[0]['ws_wikis']) < 100 || count($wikis[0]['ws_wikis']) > 550) {
	went_wrong('apiWikiSets - wikiset 2|7, wikis 1');
}else{
	went_right('apiWikiSets - wikiset 2|7, wikis 1');
}
// WS 7
if(!is_array($wikis[1]['ws_wikis']) || count($wikis[1]['ws_wikis']) < 100 || count($wikis[1]['ws_wikis']) > 350) {
	went_wrong('apiWikiSets - wikiset 2|7, wikis 2');
}else{
	went_right('apiWikiSets - wikiset 2|7, wikis 2');
}
m_equal('apiWikiSets - wikiset 2|7, type 1', $wikis[0]['ws_type'], 'optin');
m_equal('apiWikiSets - wikiset 2|7, type 2', $wikis[1]['ws_type'], 'optout');

// Force exceptions!

try{
	$activeSysops->exec(array('wikiset' => 7, 'prop' => 'unknown'));
	went_wrong('apiWikiSets - unexisting prop');
}catch(Exception $e){
	went_right('apiWikiSets - unexisting prop');
}
