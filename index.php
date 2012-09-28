<?php
define('IN_HOO_TS', true);

require_once('./includes/config.php');
require_once($_CONFIG['include_path'] . 'functions.php');
require_once($_CONFIG['include_path'] . 'template.php');

$page_name = 'main';
$hoo = new hoofr();
$template = new html_output();
$hoo->view_count($page_name, 'en');

$output = 'Hello, I\'m <a href="http://meta.wikimedia.org/wiki/User:Hoo_man">Hoo man</a>, <br />
from time to time I\'m going to upload some useful tools. Everything that I\'ve done so far can be found at the left.';

$template->set('content', $output);
$template->parse_page();
?>