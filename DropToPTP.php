<?

// DropToPTP

$version = "0.5.3.3";

// Load includes

require (__DIR__."/functions.php");

// Check for translocation

if (!@touch(__DIR__."/test")) {
	alert("DropToPTP cannot run from the Downloads folder");
	quitme();
	die;
	}

// Test connectivity to PTPImg

if (!fsockopen("ssl://ptpimg.me", 443, $errno, $errstr, 30)) {
	alert("Can't connect to PTPImg: ".$errstr,"Error ".$errno);
	die;
	}

// Version check

$checkfile = __DIR__."/vcheck";

if (!file_exists($checkfile)) {
	touch($checkfile, time()-90000);
	}

if (time()-filemtime($checkfile) > 86400) {
	$curr_version = file_get_contents("https://raw.githubusercontent.com/duckquack/DropToPTP/master/current_version.txt");
	if ($curr_version > $version) {
		if(askMulti("A new version of DropToPTP is available", array("Skip","Download")) == 1) {
			exec("open https://github.com/duckquack/DropToPTP");
			quitme();
			}
		} else {
		touch($checkfile);
		}
	}

// Load preferences

$prefs = __DIR__."/prefs.php";
if (!file_exists($prefs)) {
	alert("Can't read prefs file");
	die;
	} else {
	$p = unserialize(file_get_contents(__DIR__."/prefs.php"));
	}

// If SHIFT key is held down, force opening preferences

if(exec(__DIR__."/keys") == 512) {
	$p['no_files_action'] = 0;
	}
	
// Create work dir
	
$workdir = "/tmp/droptoptp/";
if (!file_exists($workdir)) {
	mkdir($workdir);
	}

// Check PHP version

$required = "5.5";
if (version_compare(PHP_VERSION, $required) < 0) {
    alert("Your PHP version is ".PHP_VERSION,"PHP ".$required." Required");
    die;
	}

updateProgress();

// No files action

if (!@$argv[1]) {
	if ($p['no_files_action'] == 0) {
		showPrefs();
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
		} elseif ($p['no_files_action'] == 2) {
		die;
		}
	}

// Build list of valid dropped files

foreach ($argv as $target) {

	if (is_dir($target)) {
		$it = new RecursiveDirectoryIterator($target);
		foreach(new RecursiveIteratorIterator($it) as $file) {
    		if (in_array(strtolower(@array_pop(explode('.', $file))), $p['allowed'])) {
				$files[] = $file->getpathname();
				}
			}	
		} elseif (in_array(strtolower(@array_pop(explode('.', $target))), $p['allowed'])) {
			$files[] = $target;	
		}
		
	}

if (!@$files) {
	alert("Support filetypes: ".implode(", ",$p['allowed']),"No supported files");
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

if ($p['max_enable'] & $p['max_size']) {

	updateStatus("Resizing images...");
	updateProgress();

	$i = 1;

	foreach ($files as $file) {
		
		$width = exec("sips -g pixelWidth ".escapeshellarg($file)." | tail -n1 | cut -f4 -d\" \"");
		$height = exec("sips -g pixelHeight ".escapeshellarg($file)." | tail -n1 | cut -f4 -d\" \"");
		
		if ($width > $p['max_size'] | $height > $p['max_size']) {
			
			updateStatus("Resizing ".$file);
			$dest = $workdir.md5($file).".".pathinfo($file, PATHINFO_EXTENSION);
			exec("sips --resampleHeightWidthMax ".$p['max_size']." ".escapeshellarg($file)." --out ".$dest." > /dev/null 2>&1");
			$use[] = $dest;
			
			} else {
			
			updateStatus($file." is not oversize");
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