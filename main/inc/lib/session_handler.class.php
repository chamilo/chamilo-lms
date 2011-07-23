<?php
/* For licensing terms, see /license.txt */
/**
 *	This class allows to manage the session. Session is stored in the database.
 *
 *	@package chamilo.library
 */
/**
 *	@package chamilo.library
 */
class session_handler {

	// TODO: Hm, these variables are public.
	public $connection;
	public $connection_handler;
	public $lifetime;
	public $session_name;

	public function __construct() {
		global $_configuration;

		$this->lifetime = 60; // 60 minutes

		$this->connection = array(
			'server' => $_configuration['db_host'],
			'login' => $_configuration['db_user'],
			'password' => $_configuration['db_password'],
			'base' => $_configuration['main_database']
		);

		$this->connection_handler = false;
	}

	public function sqlConnect() {

		if (!$this->connection_handler) {
			$this->connection_handler = @mysql_connect($this->connection['server'], $this->connection['login'], $this->connection['password'], true);

			// The system has not been designed to use special SQL modes that were introduced since MySQL 5
			@mysql_query("set session sql_mode='';", $this->connection_handler);

			@mysql_select_db($this->connection['base'], $this->connection_handler);

			// Initialization of the database connection encoding to be used.
			// The internationalization library should be already initialized.
			@mysql_query("SET SESSION character_set_server='utf8';", $this->connection_handler);
			@mysql_query("SET SESSION collation_server='utf8_general_ci';", $this->connection_handler);
			$system_encoding = api_get_system_encoding();
			if (api_is_utf8($system_encoding)) {
				// See Bug #1802: For UTF-8 systems we prefer to use "SET NAMES 'utf8'" statement in order to avoid a bizarre problem with Chinese language.
				@mysql_query("SET NAMES 'utf8';", $this->connection_handler);
			} else {
				@mysql_query("SET CHARACTER SET '" . Database::to_db_encoding($system_encoding) . "';", $this->connection_handler);
			}
		}

		return $this->connection_handler ? true : false;
	}

	public function sqlClose() {

		if ($this->connection_handler) {
			mysql_close($this->connection_handler);
			$this->connection_handler = false;
			return true;
		}

		return false;
	}

	public function sqlQuery($query, $die_on_error = true) {

		$result = mysql_query($query, $this->connection_handler);

		if ($die_on_error && !$result) {
			$this->sqlClose();
			return;
		}

		return $result;
	}

	public function open($path, $name) {

		$this->session_name = $name;
		return true;
	}

	public function close() {
		return $this->garbage(0) ? true : false;
	}

	public function read($sess_id) {

		if ($this->sqlConnect()) {
			$result = $this->sqlQuery("SELECT session_value FROM ".$this->connection['base'].".php_session WHERE session_id='$sess_id'");

			if ($row = mysql_fetch_assoc($result)) {
				return $row['session_value'];
			}
		}

		return '';
	}

	public function write($sess_id, $sess_value) {
		$time = time();

		if ($this->sqlConnect()) {

			$result = $this->sqlQuery("INSERT INTO ".$this->connection['base'].".php_session(session_id,session_name,session_time,session_start,session_value) VALUES('$sess_id','".$this->session_name."','$time','$time','".addslashes($sess_value)."')", false);

			if (!$result) {
				$this->sqlQuery("UPDATE ".$this->connection['base'].".php_session SET session_name='".$this->session_name."',session_time='$time',session_value='".addslashes($sess_value)."' WHERE session_id='$sess_id'");
			}

			return true;
		}

		return false;
	}

	public function destroy($sess_id) {

		if ($this->sqlConnect()) {
			$this->sqlQuery("DELETE FROM ".$this->connection['base'].".php_session WHERE session_id='$sess_id'");
			return true;
		}

		return false;
	}

	public function garbage($lifetime) {

		if ($this->sqlConnect()) {

			$result = $this->sqlQuery("SELECT COUNT(session_id) FROM ".$this->connection['base'].".php_session");
			list($nbr_results) = Database::fetch_row($result);

			if ($nbr_results > 5000) {
				$this->sqlQuery("DELETE FROM ".$this->connection['base'].".php_session WHERE session_time<'".strtotime('-'.$this->lifetime.' minutes')."'");
			}

			$this->sqlClose();

			return true;
		}

		return false;
	}
}
