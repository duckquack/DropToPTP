<?

// DropToPTPPrefs 0.5.3

// Load includes

require (__DIR__."/functions.php");

// Read Prefs

$p = unserialize(file_get_contents(__DIR__."/prefs.php"));

$strings[] = array("Never","Only if 2 or more files are uploaded","Always");
$strings[] = array("Show preferences","Capture screenshot","Do nothing");
$strings[] = array("Copy to clipboard","Open gallery","Do nothing");

$result = Pashua::showDialog(makeWindowString($p, $strings));

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
		$result = Pashua::showDialog(makeWindowString($p, $strings));
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

// Write Prefs

file_put_contents("prefs.php",serialize($result));
echo "1";

?>