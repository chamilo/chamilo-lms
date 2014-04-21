<?php

class Rights {
	private static $rights_cache = array();
	private static $rights = array (
		'show_tabs:reports' => 
			array (
				'type' => 'const',
				'const' => 'true' )
		);

	// warning the goal of this function is to enforce rights managment in Chamilo
	// thus default return value is always true
	public static function hasRight($handler) {
		if (array_key_exists($handler, self::$rights_cache)) 
			return self::$rights_cache[$handler];

		if (!array_key_exists($handler, self::$rights))
			return true; // handler does not exists

		if (self::$rights[$handler]['type'] == 'sql') {
			$result = Database::query(self::$rights[$handler]['sql']);
			if (Database::num_rows($result) > 0) 
				$result = true;
			else
				$result = false;
		} else if (self::$rights[$handler]['type'] == 'const')
			$result = self::$rights[$handler]['const'];
		else if (self::$rights[$handler]['type'] == 'func')
			$result = self::$rights[$handler]['func']();
		else // handler type not implemented
			return true;
		self::$rights_cache[$handler] = $result;
		return $result;
	}
			 
	public static function hasRightClosePageWithError($handler) {
		if (hasRight($handler) == false)
			die("You are not allowed here"); //FIXME
	}

}
