<?php
define('CONFIG_VERSION', "7");
define('LOG_FILE', "../msqur.log"); //Ew

require "config_rusefi.php";

define('LOCAL', TRUE);
define('DEBUG', TRUE/*FALSE*/);	// [andreika]: todo: temporary enabled!
define('DISABLE_MSQ_CACHE', FALSE);

error_reporting(E_ALL);

ini_set('display_errors', DEBUG ? '1' : '0');
ini_set('display_startup_errors', '1');
ini_set('log_errors', '1');

//Default in case it's not set in php.ini
//MSQUR-1
//date_default_timezone_set('UTC');

assert_options(ASSERT_ACTIVE, DEBUG ? 1 : 0);
assert_options(ASSERT_WARNING, 0);
assert_options(ASSERT_QUIET_EVAL, 1);

//Could move these to msqur class, but php
function debuglog($type, $message)
{
	global $test;
	if (isset($test)) {
		$test->debugLog($type, $message);
	}
	else if (!error_log("$type: $message\n", 3, LOG_FILE))
		error_log("Error writing to logfile: " . LOG_FILE);
}

function debug($message)
{
	debuglog("DEBUG", $message);
}

function warn($message)
{
	debuglog("WARN", $message);
}

function error($message)
{
	debuglog("ERROR", $message);
}

//Setup assert() callback
function msqur_assert_handler($file, $line, $code)
{
    error("Assertion Failed: '$code'\nFile '$file', line '$line'");
}
assert_options(ASSERT_CALLBACK, 'msqur_assert_handler');

if (!function_exists('array_key_first')) {
    function array_key_first(array $arr) {
        foreach($arr as $key => $unused) {
            return $key;
        }
        return NULL;
    }
}

if (isset($_SERVER['QUERY_STRING']))
	parse_str($_SERVER['QUERY_STRING'], $_GET);
?>
