<?php
/*
 * Unit tests for machine_readable
 * [[m:User:Hoo man]]; Last update: 2012-09-30
 */
if(!defined('IN_HOO_TS')) {
	exit();
}

//JSON tests

$data = array('foo' => 'bar');
$json = machine_readable::format_output('Test', $data, 42, 'false', false, 'json');
$result = json_decode($json, true);
m_equal('machine_readable:: JSON test 1', $result['api']['test']['foo'], 'bar');
m_equal('machine_readable:: JSON test 2', $result['api']['replag'], 42);
m_equal('machine_readable:: JSON test 3', $result['api']['error'], 'false');

// XML tests

$xml = machine_readable::format_xml(array('foo' => array('dummy' => 'bar')));
m_equal('machine_readable:: XML test 1', $xml, '<foo dummy="bar"/>');

$xml = machine_readable::format_output('activeSysops', array('count' => 339), 42, false, false, 'xml');
m_equal('machine_readable:: XML test 2', $xml,
	'<?xml version="1.0" encoding="UTF-8"?><api replag="42" error="false"><activesysops count="339"/></api>'
);

$xml = machine_readable::format_xml(array('a' => array('b', 'c')));
m_equal('machine_readable:: XML test 3', $xml, '<a><a>b</a><a>c</a></a>');

//"evil" data, which we still can handle

$xml = machine_readable::format_xml(array('a' => '<b>b</b>'));
m_equal('machine_readable:: XML test 4', $xml, '<a>&lt;b&gt;b&lt;/b&gt;</a>');

$xml = machine_readable::format_xml(array('a' => array('b' => 'c"""')));
m_equal('machine_readable:: XML test 5', $xml, '<a b="c&quot;&quot;&quot;"/>');

$xml = machine_readable::format_xml(array('a b c' => array('d e f' => 'g h i')));
m_equal('machine_readable:: XML test 6', $xml, '<a_b_c d_e_f="g h i"/>');

$xml = machine_readable::format_xml(array());
m_equal_strict('machine_readable:: XML test 7', $xml, '');

$xml = machine_readable::format_xml(array('a' => array('b' => '&p')));
m_equal('machine_readable:: XML test 8', $xml, '<a b="&amp;p"/>');

// Invalid data for machine_readable::format_xml (should throw exceptions)

try{
	echo(machine_readable::format_xml(array(array())));
	went_wrong('machine_readable:: XML test 9');
}catch(Exception $e){
	went_right('machine_readable:: XML test 9');
}
try{
	machine_readable::format_xml(array('_f_' => array(1,2,3)));
	went_wrong('machine_readable:: XML test 10');
}catch(Exception $e){
	went_right('machine_readable:: XML test 10');
}
try{
	machine_readable::format_xml(array('a' => array(' b ' => 'c')));
	went_wrong('machine_readable:: XML test 11');
}catch(Exception $e){
	went_right('machine_readable:: XML test 11');
}
