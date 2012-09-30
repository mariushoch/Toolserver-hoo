<?php
/*
 * Unit tests for apiPagesCreated
 * [[m:User:Hoo man]]; Last update: 2012-09-30
 */
if(!defined('IN_HOO_TS')) {
	exit();
}

$pagesCreated = apiPagesCreated::main();

// apiPagesCreated:: array('wiki' => 'enwiki_p', 'user_name' => 'foo')

$res = $pagesCreated->exec(array('wiki' => 'enwiki_p', 'user_name' => 'foo'));
$pages = $res['data'];
m_equal_strict('apiPagesCreated - 0 created pages', $pages, array(array()));

// apiPagesCreated:: array('wiki' => 'enwiki_p', 'user_name' => 'Hoo man', 'to' => 1244491000, 'from' => 1244511000)
// This returns exactly one page creation [[User:Hoo man]]
$res = $pagesCreated->exec(array('wiki' => 'enwiki_p', 'user_name' => 'Hoo man', 'to' => 1244491000, 'from' => 1244511000));
$pages = $res['data'];

m_equal('apiPagesCreated - One page, pageid', $pages[0]['pageid'], 23146884);
m_equal('apiPagesCreated - One page, ns', $pages[0]['ns'], 2);
m_equal('apiPagesCreated - One page, title', $pages[0]['title'], 'User:Hoo man');
m_equal('apiPagesCreated - One page, creation_time', $pages[0]['creation_time'], '20090608201819');
m_equal_strict('apiPagesCreated - One page, count', count($pages), 1);

// apiPagesCreated:: array('wiki' => 'enwiki_p', 'user_name' => 'Hoo man', 'to' => 1244491000, 'from' => 1244561000, 'namespaces' => 3)
// This returns exactly 6 pages
$res = $pagesCreated->exec(array(
	'wiki' => 'enwiki_p', 'user_name' => 'Hoo man', 'to' => 1244491000, 'from' => 1244561000, 'namespaces' => 3
));
$pages = $res['data'];

m_equal('apiPagesCreated - Pages with NS selection, pageid', $pages[0]['pageid'], 23154676);
m_equal('apiPagesCreated - Pages with NS selection, ns 1', $pages[1]['ns'], 3);
m_equal('apiPagesCreated - Pages with NS selection, ns 2', $pages[4]['ns'], 3);
m_equal('apiPagesCreated - Pages with NS selection, title', $pages[2]['title'], 'User talk:71.191.254.130');
m_equal('apiPagesCreated - Pages with NS selection, creation_time', $pages[3]['creation_time'], '20090609133951');
m_equal_strict('apiPagesCreated - Pages with NS selection, count', count($pages), 6);

// Force exceptions!

try{
	$pagesCreated->exec(array('wiki' => 'none', 'user_name' => 'foo'));
	went_wrong('apiPagesCreated - unexisting wiki');
}catch(Exception $e){
	went_right('apiPagesCreated - unexisting wiki');
}
try{
	$pagesCreated->exec(array('user_name' => 'foo'));
	went_wrong('apiPagesCreated - no wiki');
}catch(Exception $e){
	went_right('apiPagesCreated - no wiki');
}
try{
	$pagesCreated->exec(array('wiki' => 'enwiki_p'));
	went_wrong('apiPagesCreated - no user');
}catch(Exception $e){
	went_right('apiPagesCreated - no user');
}
