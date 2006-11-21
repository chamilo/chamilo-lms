<?php // $Id: session_handler.class.php 10082 2006-11-21 19:08:15Z pcool $
/*
===============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2004 Dokeos S.A.
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

class session_handler
{
	var $connexion;
	var $idConnexion;

	var $lifetime;

	var $sessionName;

	function session_handler()
	{
		global $_configuration;

		$this->lifetime=60; // 60 minutes

		$this->connexion=array('server' => $_configuration['db_host'],'login' => $_configuration['db_user'],'password' => $_configuration['db_password'],'base' => $_configuration['main_database']);

		$this->idConnexion=false;
	}

	function sqlConnect()
	{
		if(!$this->idConnexion)
		{
			$this->idConnexion=@mysql_connect($this->connexion['server'],$this->connexion['login'],$this->connexion['password'],true);
		}

		return $this->idConnexion?true:false;
	}

	function sqlClose()
	{
		if($this->idConnexion)
		{
			mysql_close($this->idConnexion);

			$this->idConnexion=false;

			return true;
		}

		return false;
	}

	function sqlQuery($query,$die_on_error=true)
	{
		$result=mysql_query($query,$this->idConnexion);

		if($die_on_error && !$result)
		{
			$this->sqlClose();

			exit();
		}

		return $result;
	}

	function open($path,$name)
	{
		$this->sessionName=$name;

		return true;
	}

	function close()
	{
		return $this->garbage(0)?true:false;
	}

	function read($sess_id)
	{
		if($this->sqlConnect())
		{
			$result=$this->sqlQuery("SELECT sess_value FROM ".$this->connexion['base'].".session WHERE sess_id='$sess_id'");

			if($row=mysql_fetch_assoc($result))
			{
				return $row['sess_value'];
			}
		}

		return '';
	}

	function write($sess_id,$sess_value)
	{
		$time=time();

		if($this->sqlConnect())
		{
			$result=$this->sqlQuery("INSERT INTO ".$this->connexion['base'].".session(sess_id,sess_name,sess_time,sess_start,sess_value) VALUES('$sess_id','".$this->sessionName."','$time','$time','".addslashes($sess_value)."')",false);

			if(!$result)
			{
				$this->sqlQuery("UPDATE ".$this->connexion['base'].".session SET sess_name='".$this->sessionName."',sess_time='$time',sess_value='".addslashes($sess_value)."' WHERE sess_id='$sess_id'");
			}

			return true;
		}

		return false;
	}

	function destroy($sess_id)
	{
		if($this->sqlConnect())
		{
			$this->sqlQuery("DELETE FROM ".$this->connexion['base'].".session WHERE sess_id='$sess_id'");

			return true;
		}

		return false;
	}

	function garbage($lifetime)
	{
		if($this->sqlConnect())
		{
			$result=$this->sqlQuery("SELECT COUNT(sess_id) FROM ".$this->connexion['base'].".session");

			list($nbr_results)=mysql_fetch_row($result);

			if($nbr_results > 5000)
			{
				$this->sqlQuery("DELETE FROM ".$this->connexion['base'].".session WHERE sess_time<'".strtotime('-'.$this->lifetime.' minutes')."'");
			}

			$this->sqlClose();

			return true;
		}

		return false;
	}
};
?>