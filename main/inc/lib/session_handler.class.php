<?php
/* For licensing terms, see /license.txt */
/**
*	This class allows to manage the session. Session is stored in
*	the database
*
*	@package chamilo.library
*/

class session_handler {
	public $connexion;
	public $idConnexion;

	public $lifetime;

	public $sessionName;

	public function __construct () {
		global $_configuration;

		$this->lifetime=60; // 60 minutes

		$this->connexion=array('server' => $_configuration['db_host'],'login' => $_configuration['db_user'],'password' => $_configuration['db_password'],'base' => $_configuration['main_database']);

		$this->idConnexion=false;
	}

	public function sqlConnect () {
		if(!$this->idConnexion)
		{
			$this->idConnexion=@mysql_connect($this->connexion['server'],$this->connexion['login'],$this->connexion['password'],true);

			// The system has not been designed to use special SQL modes that were introduced since MySQL 5
			@mysql_query("set session sql_mode='';", $this->idConnexion);
		}

		return $this->idConnexion?true:false;
	}

	public function sqlClose() {
		if($this->idConnexion)
		{
			mysql_close($this->idConnexion);

			$this->idConnexion=false;

			return true;
		}

		return false;
	}

	public function sqlQuery ($query,$die_on_error=true) {

		$result=mysql_query($query,$this->idConnexion);

		if($die_on_error && !$result)
		{
			$this->sqlClose();
			return;
			//exit();
		}

		return $result;
	}

	public function open ($path,$name) {
		$this->sessionName=$name;

		return true;
	}

	public function close () {
		return $this->garbage(0)?true:false;
	}

	public function read ($sess_id) {
		if($this->sqlConnect())
		{
			$result=$this->sqlQuery("SELECT session_value FROM ".$this->connexion['base'].".php_session WHERE session_id='$sess_id'");

			if($row=mysql_fetch_assoc($result))
			{
				return $row['session_value'];
			}
		}

		return '';
	}

	public function write ($sess_id,$sess_value) {
		$time=time();

		if($this->sqlConnect())
		{
			$result=$this->sqlQuery("INSERT INTO ".$this->connexion['base'].".php_session(session_id,session_name,session_time,session_start,session_value) VALUES('$sess_id','".$this->sessionName."','$time','$time','".addslashes($sess_value)."')",false);

			if(!$result)
			{
				$this->sqlQuery("UPDATE ".$this->connexion['base'].".php_session SET session_name='".$this->sessionName."',session_time='$time',session_value='".addslashes($sess_value)."' WHERE session_id='$sess_id'");
			}

			return true;
		}

		return false;
	}

	public function destroy ($sess_id) {
		if($this->sqlConnect())
		{
			$this->sqlQuery("DELETE FROM ".$this->connexion['base'].".php_session WHERE session_id='$sess_id'");

			return true;
		}

		return false;
	}

	public function garbage ($lifetime) {
		if($this->sqlConnect())
		{
			$result=$this->sqlQuery("SELECT COUNT(session_id) FROM ".$this->connexion['base'].".php_session");

			list($nbr_results)=Database::fetch_row($result);

			if($nbr_results > 5000)
			{
				$this->sqlQuery("DELETE FROM ".$this->connexion['base'].".php_session WHERE session_time<'".strtotime('-'.$this->lifetime.' minutes')."'");
			}

			$this->sqlClose();

			return true;
		}

		return false;
	}
};
?>
