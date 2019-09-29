<?php

// Functions

function updateProgress($num = 0, $total = 100) {
	$percent = floor(($num/$total)*100);
	echo "\nPROGRESS:".$percent."\n";
	}

function progresscallback($resource,$dltotal,$dl,$ultotal,$ul) {
	if ($ul) {
	    updateProgress($ul,$ultotal+($ultotal*.05));
		}
	}
	
function updateStatus($string) {
	echo "\n".$string."\n";
	}

function alert($string, $title = "Warning") {
	echo "\nALERT:".$title."|".$string."\n";
	}

function ncenter($string, $title = "DropToPTP") {
	exec("osascript -e 'display notification \"".$string."\" with title \"".$title."\"'");
	}

function getString($question) {
	return exec("osascript -e 'display dialog \"".$question."\" default answer \"\"' | cut -f3 -d\":\"");
	}

function ask($string) {
	$result = exec("osascript -e \"display dialog \\\"".$string."\\\"\" 2>&1");
	if (strpos($result,"canceled") !== false) {
		return 0;
		} else {
		return 1;
		}
	}

function askMulti($string, $buttons) {
	$buttonstring = "buttons {\\\"".implode("\\\", \\\"",$buttons)."\\\"} default button ".count($buttons);
	$result = exec("osascript -e \"display dialog \\\"".$string."\\\" ".$buttonstring."\" | cut -f2 -d':'");
	return array_search($result,$buttons);
	}

function quitme() {
	echo "\nQUITAPP\n";
	}

?>