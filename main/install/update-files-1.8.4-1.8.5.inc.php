<?php
/* For licensing terms, see /license.txt */

/**
 * Chamilo LMS
 *
 * Updates the Dokeos files from version 1.8.4 to version 1.8.5
 * This script operates only in the case of an update, and only to change the
 * active version number (and other things that might need a change) in the
 * current configuration file.
 * As of 1.8.5, the Dokeos version has been added to configuration.php to
 * allow for edition (inc/conf is one of the directories that needs write
 * permissions on upgrade).
 * Being in configuration.php, it benefits from the configuration.dist.php
 * advantages that a new version doesn't overwrite it, thus letting the old
 * version be available until the end of the installation.
 * @package chamilo.install
 */

Log::notice('Entering file');

if (defined('SYSTEM_INSTALLATION')) {

	// Edit the configuration file
	$file = file(api_get_path(CONFIGURATION_PATH).'configuration.php');
	$fh = fopen(api_get_path(CONFIGURATION_PATH).'configuration.php', 'w');
	$found_version = false;
	$found_stable = false;
	foreach ($file as $line) {
		$ignore = false;
		if (stripos($line, '$_configuration[\'dokeos_version\']') !== false) {
			$found_version = true;
			$line = '$_configuration[\'dokeos_version\'] = \''.$new_version.'\';'."\r\n";
		} elseif (stripos($line, '$_configuration[\'dokeos_stable\']') !== false) {
			$found_stable = true;
			$line = '$_configuration[\'dokeos_stable\'] = '.($new_version_stable ? 'true' : 'false').';'."\r\n";
		} elseif (stripos($line, '?>') !== false) {
			// Ignore the line
			$ignore = true;
		}
		if (!$ignore) {
			fwrite($fh, $line);
		}
	}
	if (!$found_version) {
		fwrite($fh, '$_configuration[\'dokeos_version\'] = \''.$new_version.'\';'."\r\n");
	}
	if (!$found_stable) {
		fwrite($fh, '$_configuration[\'dokeos_stable\'] = '.($new_version_stable ? 'true' : 'false').';'."\r\n");
	}
	fwrite($fh, '?>');
	fclose($fh);

} else {

	echo 'You are not allowed here !';

}
