<?

// Functions

function testKey($key) {
	$data = array("api_key" => $key);
	$url = "https://ptpimg.me/upload.php";
	$options = array(
        'http' => array(
			'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
        	'method'  => 'POST',
        	'content' => http_build_query($data),
    		)
    	);
	$context  = stream_context_create($options);
	$result = @file_get_contents($url, false, $context);
	if (gettype($result) == "string") {
		return true;
		} else {
		return false;
		}
	}

function showPrefs() {
	return exec("php ".__DIR__."/DropToPTPPrefs.php");
	}

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

// Pashua Stuff

/**
 * Static class which wraps the two simple methods used for communicating with Pashua
 */
class Pashua
{
    /**
     * Invokes a Pashua dialog window with the given window configuration
     *
     * @param string $conf           Configuration string to pass to Pashua
     * @param string $customLocation Filesystem path to directory containing Pashua
     *
     * @throws \RuntimeException
     * @return array Associative array of values returned by Pashua
     */
    public static function showDialog($conf, $customLocation = null)
    {
        if (ini_get('safe_mode')) {
            $msg = "To use Pashua you will have to disable safe mode or " .
                "change " . __FUNCTION__ . "() to fit your environment.\n";
            fwrite(STDERR, $msg);
            exit(1);
        }

        // Write configuration string to temporary config file
        $configfile = tempnam('/tmp', 'Pashua_');
        if (false === $fp = @fopen($configfile, 'w')) {
            throw new \RuntimeException("Error trying to open $configfile");
        }
        fwrite($fp, $conf);
        fclose($fp);

        $path = __DIR__."/Pashua.app/Contents/MacOS/Pashua";

        // Call pashua binary with config file as argument and read result
        $result = shell_exec(escapeshellarg($path) . ' ' . escapeshellarg($configfile));

        @unlink($configfile);

        // Parse result
        $parsed = array();
        foreach (explode("\n", $result) as $line) {
            preg_match('/^(\w+)=(.*)$/', $line, $matches);
            if (empty($matches) or empty($matches[1])) {
                continue;
            }
            $parsed[$matches[1]] = $matches[2];
        }

        return $parsed;
    }
}

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
	
	# Image resizing
	max_enable.rely = -18
	max_enable.type = checkbox
	max_enable.label = Resize images
	max_enable.default = ".$p['max_enable']."
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
	no_files_action.option = ".$strings[1][2]."
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

?>