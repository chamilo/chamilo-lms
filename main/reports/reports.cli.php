<?php
require_once 'reports.lib.php';

$longopts = array(
	'course:',
	'tool:',
	'ci:',
	'cn:',
	'sci:',
	'scn:',
	'ssci:',
	'sscn:',
	'link:',
	'addValue',
	'addKey',
	'help',
	'clearAll',
	'score:',
	'progress:',
	'time:',
	'attempt:',
	'session:',
	'attempt:',
	'uid:',
	'key:',
	'addDBKeys',
	'build');

$options = getopt("", $longopts);

echo "\n\n";

if (array_key_exists('help', $options))
	echo "help message\n";
else if (array_key_exists('clearAll', $options)) {
	reports_clearAll();
	echo Database::error();
} else if (array_key_exists('build', $options)) {
	reports_build();
	echo Database::error();
} else if (array_key_exists('addDBKeys', $options)) {
	reports_addDBKeys();
	echo Database::error();
} else if (array_key_exists('addValue', $options)) {
	reports_addValue($options['key'], $options['session'], $options['uid'],
			$options['attempt'], $options['score'], 
			$options['progress'], $options['time']);
	echo Database::error();
} else if (array_key_exists('addKey', $options)) {
	echo reports_addKey($options['course'], $options['tool'], 
			$options['ci'], $options['cn'], 
			$options['sci'], $options['scn'], 
			$options['ssci'], $options['sscn'], 
			$options['link']);
	echo "\n";
	echo Database::error();
} else
	echo "action not found\n";
	
echo "\n";
?>
