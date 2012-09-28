<?php
define('IN_HOO_TS', true);

require_once('./includes/config.php');
require_once($_CONFIG['include_path'] . 'functions.php');
require_once($_CONFIG['include_path'] . 'template.php');

$page_name = 'Local account creation';
$hoo = new hoofr();
$template = new html_output();
$hoo->view_count('sulAccountCreation', 'en');
/*
$template->set('additional_meta', '<script src="//ajax.googleapis.com/ajax/libs/jquery/1/jquery.min.js"></script>', false, 'append');
$template->set('additional_meta', '<script type="text/javascript" src="includes/sulAccountCreation.js"></script>', false, 'append');

$output = 'This script will generate all local accounts for your SUL account.<br />
It works using JavaScript and there is no need to give your user name or even password. You just need to have a SUL account and need to be globally logged in.<br /><br /><br />
<a id="startLink" href="javascript:offset = 0; createAccount(); void(0);">Start</a><br />
Done: <span id="done">0</span>; To do: <span id="todo"></span><br /><br /><br />
<b>Note:</b> The script will try to create accounts on all wikis, no matter whether there already is an account. Therefore it will always make the same amount of requests even if it can only create 5 accounts or none at all.';*/
$output = 'This script has been removed because Krinkle developed a better solution which can be found at <a href="//meta.wikimedia.org/wiki/User:Krinkle/Tools/Global_SUL">meta.wikimedia.org/wiki/User:Krinkle/Tools/Global_SUL</a>.';

$template->set('content', $output);
$template->parse_page();
?>
