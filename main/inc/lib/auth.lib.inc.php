<?php
/*
============================================================================== 
	Dokeos - elearning and course management software
	
	Copyright (c) 2004 Dokeos S.A.
	Copyright (c) 2003 Ghent University (UGent)
	Copyright (c) 2001 Universite catholique de Louvain (UCL)
	Copyright (c) Olivier Brouckaert
	
	For a full list of contributors, see "credits.txt".
	The full license can be read in "license.txt".
	
	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.
	
	See the GNU General Public License for more details.
	
	Contact: Dokeos, 181 rue Royale, B-1000 Brussels, Belgium, info@dokeos.com
============================================================================== 
*/
 
/**
============================================================================== 
*	This is the authentication library for Dokeos.
*	Include/require it in your code to use its functionality.
*	@deprecated No function in this file is actually used somewhere in the code
*	@package dokeos.library
============================================================================== 
*/

/**
 * Build a string without logic
 * to be used as password
 *
 * @author Christophe Gesche <gesche@ipm.ucl.ac.be>
 * @version 1.0
 * @param  integer	$nbcar 			default 5   	define here  length of password
 * @param  boolean	$lettresseules	default false	fix  if pass can content digit
 * @return string password
 * @desc return a string to be use as password
 * @see rand()
 * @deprecated use api_generate_password located in main_api.lib.php
 */

function generePass($nbcar=5,$lettresseules = false)
{
	return api_generate_password($nbcar);
}
/**
 * ifsnow's email valid check function SnowCheckMail Ver 0.1
 * funtion SnowCheckMail ($Email,$debug=false)
 * $Email : E-Mail address to check.
 * $debug : Variable for debugging.
 * Can use everybody if use without changing the name of function.
 * Reference : O'REILLY - Internet Email Programming
 * HOMEPAGE : http://www.hellophp.com
 * ifsnow is korean phper. Is sorry to be unskillful to English. *^^*;;
 * @deprecated Function not in use
 */

function SnowCheckMail($Email,$debug=false)
{
	global $_SERVER;
	$return = array();
	// Variable for return.
	// $return[0] : [true|false]
	// $return[1] : Processing result save.

	if (!eregi("^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$", $Email))
	{
		$return[0]=false;
		$return[1]="${Email} is E-Mail form that is not right.";
		if ($debug) echo "Error : {$Email} is E-Mail form that is not right.<br />";
		return $return;
	}
	elseif ($debug)
	{
		echo "Confirmation : {$Email} is E-Mail form that is not right.<br />";
	}

	// E-Mail @ by 2 by standard divide. if it is $Email this "lsm@ebeecomm.com"..
	// $Username : lsm
	// $Domain : ebeecomm.com
	// list function reference : http://www.php.net/manual/en/function.list.php
	// split function reference : http://www.php.net/manual/en/function.split.php
	list ( $Username, $Domain ) = split ("@",$Email);

	// That MX(mail exchanger) record exists in domain check .
	// checkdnsrr function reference : http://www.php.net/manual/en/function.checkdnsrr.php
	if ( checkdnsrr ( $Domain, "MX" ) )
	{
		if($debug) echo "Confirmation : MX record about {$Domain} exists.<br />";
		// If MX record exists, save MX record address.
		// getmxrr function reference : http://www.php.net/manual/en/function.getmxrr.php
		$MXHost = array();
		if ( getmxrr ($Domain, $MXHost))
		{
			if($debug)
			{
				echo "Confirmation : Is confirming address by MX LOOKUP.<br />";
				for ( $i = 0,$j = 1; $i < count($MXHost); $i++,$j++ )
				{
					echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Result($j) - $MXHost[$i]<br />";
				}
			}
		}
		// Getmxrr function does to store MX record address about $Domain in arrangement form to $MXHost.
		// $ConnectAddress socket connection address.
		$ConnectAddress = $MXHost[0];
	}
	else
	{
		// If there is no MX record simply @ to next time address socket connection do .
		$ConnectAddress = $Domain;
		if ($debug) echo "Confirmation : MX record about {$Domain} does not exist.<br />";
	}

	// fsockopen function reference : http://www.php.net/manual/en/function.fsockopen.php
	$Connect = fsockopen ( $ConnectAddress, 25 );

	// Success in socket connection
	if ($Connect)
	{
		if ($debug) echo "Connection succeeded to {$ConnectAddress} SMTP.<br />";
		// Judgment is that service is preparing though begin by 220 getting string after connection .
		// fgets function reference : http://www.php.net/manual/en/function.fgets.php
		if ( ereg ( "^220", $Out = fgets ( $Connect, 1024 ) ) )
		{
			// Inform client's reaching to server who connect.
			fputs ( $Connect, "HELO ".$_SERVER['HTTP_HOST']."\r\n" );
				if ($debug) echo "Run : HELO ".$_SERVER['HTTP_HOST']."<br />";
			$Out = fgets ( $Connect, 1024 ); // Receive server's answering cord.

			// Inform sender's address to server.
			fputs ( $Connect, "MAIL FROM: <{$Email}>\r\n" );
				if ($debug) echo "Run : MAIL FROM: &lt;{$Email}&gt;<br />";
			$From = fgets ( $Connect, 1024 ); // Receive server's answering cord.

			// Inform listener's address to server.
			fputs ( $Connect, "RCPT TO: <{$Email}>\r\n" );
				if ($debug) echo "Run : RCPT TO: &lt;{$Email}&gt;<br />";
			$To = fgets ( $Connect, 1024 ); // Receive server's answering cord.

			// Finish connection.
			fputs ( $Connect, "QUIT\r\n");
				if ($debug) echo "Run : QUIT<br />";

			fclose($Connect);

			// Server's answering cord about MAIL and TO command checks.
			// Server about listener's address reacts to 550 codes if there does not exist
			// checking that mailbox is in own E-Mail account.
			if ( !ereg ( "^250", $From ) || !ereg ( "^250", $To ))
			{
				$return[0]=false;
				$return[1]="${Email} is address done not admit in E-Mail server.";
				if ($debug) echo "{$Email} is address done not admit in E-Mail server.<br />";
				return $return;
			}
		}
	}
	// Failure in socket connection
	else
	{
		$return[0]=false;
		$return[1]="Can not connect E-Mail server ({$ConnectAddress}).";
		if ($debug) echo "Can not connect E-Mail server ({$ConnectAddress}).<br />";
		return $return;
	}
	$return[0]=true;
	$return[1]="{$Email} is E-Mail address that there is no any problem.";
	return $return;
}

?>