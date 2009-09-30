<?php // $Id: session_handler.class.php 22311 2009-07-23 15:39:23Z iflorespaz $
/*
===============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2004-2007 Dokeos S.A.
	Copyright (c) 2003 Ghent University (UGent)
	Copyright (c) 2001 Universite catholique de Louvain (UCL)
	Copyright (c) Hugues Peeters
	Copyright (c) Christophe Gesche
	Copyright (c) Roan Embrechts
	Copyright (c) Patrick Cool
	Copyright (c) Olivier Brouckaert
	Copyright (c) Toon Van Hoecke
	Copyright (c) Denes Nagy

	For a full list of contributors, see "credits.txt".
	The full license can be read in "license.txt".

	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	See the GNU General Public License for more details.

	Contact: Dokeos, 181 rue Royale, B-1000 Brussels, Belgium, info@dokeos.com
===============================================================================
*/
/**
==============================================================================
*	This class allows to manage the session. Session is stored in
*	the database
*
*	@package dokeos.library
==============================================================================
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

			// The Dokeos system has not been designed to use special SQL modes that were introduced since MySQL 5
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

			exit();
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

			list($nbr_results)=mysql_fetch_row($result);

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
