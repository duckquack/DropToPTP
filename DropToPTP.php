<?php

// DropToPTP

$version = file_get_contents(__DIR__."/current_version.txt");

// Check PHP version

$required = "5.5";
if (version_compare(PHP_VERSION, $required) < 0) {
    alert("Your PHP version is ".PHP_VERSION,"PHP ".$required." Required");
    die;
	}

// Load includes

require (__DIR__."/functions.php");

// Test connectivity to PTPImg

if (!fsockopen("ssl://ptpimg.me", 443, $errno, $errstr, 30)) {
	alert("Can't connect to PTPImg: ".$errstr,"Error ".$errno);
	die;
	}

// Load preferences

$prefs_file = "/Users/".get_current_user()."/Library/Preferences/org.anatidae.DropToPTP.php";
if (!file_exists($prefs_file)) {
	if (!copy(__DIR__."/prefs.php",$prefs_file)) {
		echo "Error creating preferences file";
		die;
		}
	}
$p = unserialize(file_get_contents($prefs_file));

// Create work dir
	
$workdir = "/tmp/droptoptp/";
if (!file_exists($workdir)) {
	mkdir($workdir);
	}

// No files dropped

updateProgress();

switch (@$argv[1]) {
	case NULL:
		if ($p['no_files_action'] == 0) {
			die;
			} elseif ($p['no_files_action'] == 1) {
			$img = "/tmp/droptoptp/screen_".time().".png";
			exec("/usr/sbin/screencapture -i ".$img);
			if (file_exists($img)) {
				$argv[1] = $img;
				} else {
				alert("Error capturing screenshot");
				die;
				}
			}
		break;
	case "Preferences...":
		showPrefs();
		die;
	case "Check for Updates...":
		$curr_version = file_get_contents("https://raw.githubusercontent.com/duckquack/DropToPTP/master/current_version.txt");
		if (!$curr_version) {
			echo "\nALERT:Can't connect|Error checking for latest version\n";
			die;
			}
		if ($curr_version > $version) {
			if(askMulti("A new version of DropToPTP is available", array("Skip","Download")) == 1) {
				exec("open https://github.com/duckquack/DropToPTP");
				quitme();
				} else {
				die;
				}
			} else {
			alert($version." is the latest version","Up-to-date");
			die;
			}
	}

// Build list of valid dropped files

foreach ($argv as $target) {

	if (is_dir($target)) {
		$it = new RecursiveDirectoryIterator($target);
		foreach(new RecursiveIteratorIterator($it) as $file) {
    		if (in_array(strtolower(pathinfo($file,PATHINFO_EXTENSION)), $p['allowed'])) {
				$files[] = $file->getpathname();
				}
			}	
		} elseif (in_array(strtolower(pathinfo($target,PATHINFO_EXTENSION)), $p['allowed'])) {
			$files[] = $target;	
		}
		
	}

if (!@$files) {
	alert("Supported filetypes: ".implode(", ",$p['allowed']),"No supported files");
	die;
	} else {
	sort($files);
	}
	
if (count($files) > $p['limit']) {
	if (!ask("Really upload ".count($files)." files?")) {
		updateStatus("User cancelled");
		die;
		}
	}

// Resize images

if ($p['max_enable'] && $p['max_size']) {

	if ($p['max_enable'] == 1) {
		$resizeme = $p['allowed'];
		} elseif ($p['max_enable'] == 2) {
		$resizeme = array("jpg","jpeg");
		}

	updateStatus("Resizing images...");
	updateProgress();

	$i = 1;

	foreach ($files as $file) {
		
		$width = exec("sips -g pixelWidth ".escapeshellarg($file)." | tail -n1 | cut -f4 -d\" \"");
		$height = exec("sips -g pixelHeight ".escapeshellarg($file)." | tail -n1 | cut -f4 -d\" \"");
		
		if (($width > $p['max_size'] || $height > $p['max_size']) && in_array(strtolower(pathinfo($file,PATHINFO_EXTENSION)), $resizeme)) {
			
			updateStatus("Resizing ".$file);
			$ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
			$dest = $workdir.md5($file).".".$ext;
			if ($ext == "png") {
				exec("sips --resampleHeightWidthMax ".$p['max_size']." --matchTo '/System/Library/ColorSync/Profiles/sRGB Profile.icc' ".escapeshellarg($file)." --out ".$dest);
				} else {
				exec("sips --resampleHeightWidthMax ".$p['max_size']." ".escapeshellarg($file)." --out ".$dest);
				}
			if (file_exists($dest)) {
				$use[] = $dest;
				} else {
				updateStatus("Resizing failed, using original file");
				$use[] = $file;
				}
			
			} else {
			
			updateStatus($file." does not need to be resized");
			$use[] = $file;
			}

		updateProgress($i,count($files));
		$i++;

		}

	} else {
		
	$use = $files;
	
	}

// Upload images

updateStatus("Uploading images...");

foreach ($use as $id => $file) {
	
	$data_array["file-upload[".$id."]"] = new CurlFile($file);
	
	}
	
$data_array["api_key"] = $p['key'];

$ci = curl_init();

curl_setopt($ci, CURLOPT_URL, "https://ptpimg.me/upload.php");
curl_setopt($ci, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ci, CURLOPT_POST, true);
curl_setopt($ci, CURLOPT_CUSTOMREQUEST, "POST");
curl_setopt($ci, CURLOPT_POSTFIELDS, $data_array);
curl_setopt($ci, CURLOPT_NOPROGRESS, false);
curl_setopt($ci, CURLOPT_PROGRESSFUNCTION, 'progresscallback');
$json = curl_exec($ci);

$links = json_decode($json, TRUE);

updateProgress(100);

if (!@$links) {
	alert("PTPImg did not return any links!");
	die;
	}

// Parse JSON links

foreach ($links as $link) {
	$uselink = "https://ptpimg.me/".$link['code'].".".$link['ext'];
	if ($p['add_tags'] == 2 | ( $p['add_tags'] == 1 && count($links) > 1 )) {
		$outlinks[] = "[img]".$uselink."[/img]";
		if ($p['subtitle']) {
			$outlinks[] = $p['subtitle'];
			}
		} else {
		$outlinks[] = $uselink;
		}
	}

echo "\r".implode("\n",$outlinks);

// After upload

switch ($p['after_upload']) {
	
	case 0:

	fwrite(popen("pbcopy", "w"),implode("\n",$outlinks));
	ncenter(count($links)." link(s) copied to the clipboard");
	break;
	
	case 1:
	
	exec("open https://ptpimg.me/gallery.php");
	break;
	
	}
	
if (!$p['stay_open']) {
	quitme();
	}

?>