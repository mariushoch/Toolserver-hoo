<?php
/*
 * Unit tests for apiActiveSysops
 * [[m:User:Hoo man]]; Last update: 2012-09-30
 */
if(!defined('IN_HOO_TS')) {
	exit();
}

$activeSysops = apiActiveSysops::main();

// apiActiveSysops:: array('wiki' => 'enwiki_p')

$res = $activeSysops->exec(array('wiki' => 'enwiki_p'));
$count = $res['data']['count'];

if(200 < $count && $count < 550) {
	went_right('apiActiveSysops - enwiki_p');
}else{
	went_wrong('apiActiveSysops - enwiki_p');
}

// apiActiveSysops:: array('wiki' => 'enwiki_p', 'last_action' => 1728000) (20days)

$res = $activeSysops->exec(array('wiki' => 'enwiki_p', 'last_action' => 1728000));
$count2 = $res['data']['count'];
if(200 < $count && $count < $count2) {
	went_right('apiActiveSysops - enwiki_p 20days');
}else{
	went_wrong('apiActiveSysops - enwiki_p 20days');
}

// Force exceptions!

try{
	$activeSysops->exec(array('wiki' => 'none', 'last_action' => 1728000));
	went_wrong('apiActiveSysops - unexisting wiki');
}catch(Exception $e){
	went_right('apiActiveSysops - unexisting wiki');
}
try{
	$activeSysops->exec(array('last_action' => 1728000));
	went_wrong('apiActiveSysops - no wiki');
}catch(Exception $e){
	went_right('apiActiveSysops - no wiki');
}
