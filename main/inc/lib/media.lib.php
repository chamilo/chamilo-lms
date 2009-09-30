<?php

// These constants specify multi-media related resource files (scripts, players, etc.).
define('FLASH_PLAYER_AUDIO', 'FLASH_PLAYER_AUDIO');
define('FLASH_PLAYER_VIDEO', 'FLASH_PLAYER_VIDEO');
define('SCRIPT_SWFOBJECT', 'SCRIPT_SWFOBJECT');
define('SCRIPT_ASCIIMATHML', 'SCRIPT_ASCIIMATHML');

/**
 * A static class for serving the Dokeos system's multi-media features.
 * @author Ivan Tcholakov, July 2009.
 */
class Media {

	/**
	 * This method returns the path (location) of a specified multi-media resource file.
	 * @param string $media_resource		The identificator of the requested resource: FLASH_PLAYER_AUDIO, FLASH_PLAYER_VIDEO, SCRIPT_SWFOBJECT, SCRIPT_ASCIIMATHML
	 * @param string $path_type (optional)	Type (or base) of the returned path, it can be: WEB_PATH, SYS_PATH, REL_PATH (default)
	 * @return string						Path to access the requeted media-related file.
	 * Note: At the moment returned paths are based on hard-coded data. Configuration data may be used in the future.
	 */
	public function get_path($media_resource, $path_type = REL_PATH) {
		switch ($media_resource) {
			case FLASH_PLAYER_AUDIO:
				$relative_path = 'main/inc/lib/mediaplayer/player.swf';
				break ;
			case FLASH_PLAYER_VIDEO:
				$relative_path = 'main/inc/lib/mediaplayer/player.swf';
				break;
			case SCRIPT_SWFOBJECT:
				$relative_path = 'main/inc/lib/swfobject/swfobject.js';
				break;
			case SCRIPT_ASCIIMATHML:
				$relative_path = 'main/inc/lib/asciimath/ASCIIMathML.js';
				break;
			default:
				return '';
		}
		$base_path = api_get_path($path_type);
		if (empty($base_path)) {
			return '';
		}
		return $base_path.$relative_path;
	}

}
