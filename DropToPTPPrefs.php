<?

// DropToPTPPrefs

require (__DIR__."/functions.php");

function makeWindowString($p, $strings) {

	$conf = "
	# Set window title
	*.title = Preferences
	*.floating = 1
	
	# PTPimg Key
	key.type = textfield
	key.mandatory = 1
	key.label = PTPImg key
	key.default = ".$p['key']."
	key.placeholder = xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx
	key.width = 400
	key.tooltip = Your PTPImg key can be found by checking the source code on PTPimg while logged in
	
	# Allowed Filetypes
	allowed.type = textfield
	allowed.mandatory = 1
	allowed.label = Allowed filetypes
	allowed.default = ".implode(", ",$p['allowed'])."
	allowed.width = 400
	allowed.tooltip = These are the types of files DropToPTP will attempt to upload to PTPimg
	
	# Limit
	limit.type = textfield
	limit.label = Max images to upload without warning
	limit.default = ".$p['limit']."
	limit.tooltip = DropToPTP will display a warning if more than x images are dropped
	limit.width = 80

	# Resize images?
	max_enable.type = popup
	max_enable.label = Resize images?
	max_enable.width = 400
	max_enable.option = ".$strings[3][0]."
	max_enable.option = ".$strings[3][1]."
	max_enable.option = ".$strings[3][2]."
	max_enable.default = ".$strings[3][$p['max_enable']]."
	max_size.type = textfield
	max_size.label = Max image dimension
	max_size.default = ".$p['max_size']."
	max_size.tooltip = DropToPTP will attempt to resize any image with a width or height greater than this value
	max_size.width = 80
	
	# Add tags
	add_tags.type = popup
	add_tags.label = Add [img] tags to URLs?
	add_tags.width = 400
	add_tags.option = ".$strings[0][0]."
	add_tags.option = ".$strings[0][1]."
	add_tags.option = ".$strings[0][2]."
	add_tags.default = ".$strings[0][$p['add_tags']]."
	
	# Subtitle
	subtitle.type = textfield
	subtitle.label = Image subtitle
	subtitle.default = ".$p['subtitle']."
	subtitle.width = 400
	subtitle.tooltip = This text will be added on a new line after each image URL
	
	# No files action
	no_files_action.type = popup
	no_files_action.label = If no files are dragged
	no_files_action.width = 400
	no_files_action.option = ".$strings[1][0]."
	no_files_action.option = ".$strings[1][1]."
	no_files_action.default = ".$strings[1][$p['no_files_action']]."
	
	# After upload
	after_upload.type = popup
	after_upload.label = After successful upload
	after_upload.width = 400
	after_upload.option = ".$strings[2][0]."
	after_upload.option = ".$strings[2][1]."
	after_upload.option = ".$strings[2][2]."
	after_upload.default = ".$strings[2][$p['after_upload']]."
	
	# Stay open?
	stay_open.type = checkbox
	stay_open.label = Stay open after successful upload
	stay_open.default = ".$p['stay_open']."
	
	# Buttons
	#gb.type = button
	#gb.label = Detect Key...
	cb.type = cancelbutton
	db.type = defaultbutton
	db.label = Save
	";
	
	return $conf;
	
	}

// Read Prefs

$prefs_file = "/Users/".get_current_user()."/Library/Preferences/org.anatidae.DropToPTP.php";
$p = unserialize(file_get_contents($prefs_file));

// Load strings

$strings[] = array("Never","Only if 2 or more files are uploaded","Always");
$strings[] = array("Do nothing","Capture screenshot");
$strings[] = array("Copy to clipboard","Open gallery","Do nothing");
$strings[] = array("Do not resize images","Resize all images","Only resize JPEGs");

// Launch Pashua and parse results

$path = __DIR__."/Pashua.app/Contents/MacOS/Pashua";
$raw = shell_exec("echo ".escapeshellarg(makeWindowString($p, $strings))." | ".escapeshellarg($path)." - ");
$result = array();
foreach (explode("\n", $raw) as $line) {
	preg_match('/^(\w+)=(.*)$/', $line, $matches);
    if (empty($matches) or empty($matches[1])) {
    	continue;
        }
    $result[$matches[1]] = $matches[2];
    }
    
// User cancelled

if (@$result['cb']) {
	echo "0";
	die;
	}

// Test API key

$validated = 0;

if (testKey($result['key'])) {
	$validated = 1;
	}

while (!$validated) {
	if (askMulti("PTPImg did not accept your API key",array("Continue", "Edit API key")) == 0) {
		$validated = 1;
		} else {
		$p['key'] = $result['key'];
		$result = makeWindowString($p, $strings));
		if (@$result['cb']) {
			echo "0";
			die;
			} elseif (testKey($result['key'])) {
			$validated = 1;
			}
		}
	}

// Fix strings

$result['allowed'] = explode(", ",$result['allowed']);
$result['add_tags'] = array_search($result['add_tags'],$strings[0]);
$result['no_files_action'] = array_search($result['no_files_action'],$strings[1]);
$result['after_upload'] = array_search($result['after_upload'],$strings[2]);
$result['max_enable'] = array_search($result['max_enable'],$strings[3]);

// Write Prefs

file_put_contents($prefs_file,serialize($result));
echo "1";

?>