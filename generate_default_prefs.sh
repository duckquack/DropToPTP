#!/usr/bin/php
<?

$p['max_size'] = 1800;
$p['max_enable'] = 1;
$p['add_tags'] = 1;
$p['subtitle'] = "Uploaded with [url=https://is.gd/XVG49v]DropToPTP[/url]";
$p['allowed'] = array("jpeg","jpg","png","gif");
$p['limit'] = 10;
$p['stay_open'] = 0;
$p['no_files_action'] = 0;
$p['after_upload'] = 0;
$p['key'] = "";

file_put_contents("prefs.php",serialize($p));

//$p = unserialize(file_get_contents(__DIR__."/prefs.php"));
//print_r($p);

?>