<?php 
/* For licensing terms, see /license.txt */

/**
*	This is the statistics library for Dokeos.
*	Include/require it in your code to use its functionality.
*
*	@author Sebastien Piraux
*	@package chamilo.library
*
*/

/*
	List of functions :
	-------------------

	addBrowser			--		OK
	addCountry 			--		OK
	addOs				--		OK
	addProvider 			-- 		OK
	addReferer			--		OK
	cleanProcessedRecords 	        --		OK
	decodeOpenInfos 		--		OK
	extractAgent			--		OK	MUST BE IMPROVED
	extractCountry 			--		OK 	MUST BE IMPROVED
	extractProvider 		--		OK	MUST BE IMPROVED
	fillCountriesTable 		--		OK
	fillBrowsersTable		--		OK
	fillOsTable			--		OK
	fillProvidersTable 		-- 		OK
	fillReferersTable 		--		OK
	loadCountries 			--		OK
	loadOs 				-- 		OK
	loadBrowsers 			-- 		OK
                                                    ----------
							OK BUT MAY BE OPTIMIZED

*/

/*
	   Variables
*/

// regroup table names for maintenance purpose
//we can use the database class: this file is only called in the /index.php file before the global.inc.php

$TABLETRACK_OPEN            = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_OPEN);
$TABLESTATS_PROVIDERS       = Database::get_statistic_table(TABLE_STATISTIC_TRACK_C_PROVIDERS);
$TABLESTATS_COUNTRIES       = Database::get_statistic_table(TABLE_STATISTIC_TRACK_C_COUNTRIES);
$TABLESTATS_BROWSERS        = Database::get_statistic_table(TABLE_STATISTIC_TRACK_C_BROWSERS);
$TABLESTATS_OS              = Database::get_statistic_table(TABLE_STATISTIC_TRACK_C_OS);
$TABLESTATS_REFERERS        = Database::get_statistic_table(TABLE_STATISTIC_TRACK_C_REFERERS);
/*

	   Main : decodeOpenInfos launch all processes
*/


/**
 * @author Sebastien Piraux <piraux_seb@hotmail.com>
 * @desc uses $TABLETRACK_OPEN to split recorded
     information, to count occurences (for os, provider,...)
     and to increment the number of occurrences of each
     different element into the corresponding tables
 */
function decodeOpenInfos() {
    global $TABLETRACK_OPEN;
    // record initial value of ignore_user_abort
    $ignore = ignore_user_abort();
    // prevent script from being stopped while executing, the following can be considered
    // as a transaction
    ignore_user_abort(1) ;
    // we take the last event id to prevent miss of some recorded event
    // only processed record have to be cleaned

    $sql = "SELECT open_id
                FROM $TABLETRACK_OPEN
                WHERE open_date <= NOW()
                ORDER BY open_id DESC
                LIMIT 1";
    //$processBegin = getOneResult($sql);
    $query = Database::query($sql);
    $res = @Database::fetch_array($query);
    $processBegin = $res[0];
    // process

    //--Providers And Countries-------------------------------------------//

     $sql = "SELECT open_remote_host
                FROM $TABLETRACK_OPEN
                WHERE   open_remote_host != ''
                AND     open_id <= '".$processBegin."' ";
    $query = Database::query($sql);

    if(Database::num_rows($query) != 0) {
    	// load list of countries
    	$list_countries = loadCountries();

       	while ($row = Database::fetch_row ($query)) {
            $remote_host = $row[0];
            /*****Provider*****/
            //extract provider
            $provider = extractProvider( $remote_host );
            // add or increment provider in the providers array
            $providers_array = addProvider( $provider,$providers_array );

            /*****Countries*****/
            // extract country
            $country = extractCountry( $remote_host, $list_countries );
            // increment country in the countries table
            $countries_array = addCountry( $country, $countries_array );
        }
        // update tables
    	fillProvidersTable( $providers_array );
    	fillCountriesTable( $countries_array );
    }
    // provider and countries done
    //--Browsers and OS---------------------------------------------------//
    $sql = "SELECT open_agent
                FROM $TABLETRACK_OPEN
                WHERE   open_remote_host != ''
                AND     open_id <= '".$processBegin."' ";
    $query =Database::query( $sql );
    if(Database::num_rows($query) != 0) {
    	// load lists
        // of browsers
        $list_browsers = loadBrowsers();
        // of OS
        $list_os = loadOs();
        while ($row = Database::fetch_row ($query)) {
            $agent = $row[0];
            /*****Browser and OS*****/
            // extract browser and OS
            list( $browser,$os ) = split( "[|]",extractAgent( $agent , $list_browsers , $list_os ) );
            // increment browser and OS in the corresponding arrays
            $browsers_array = addBrowser( $browser , $browsers_array );
            $os_array = addOs( $os , $os_array );
        }
        fillBrowsersTable( $browsers_array );
    	fillOsTable( $os_array );
    }
    // browsers and OS done

    //--Referers----------------------------------------------------------//

    $sql = "SELECT open_referer
                FROM $TABLETRACK_OPEN
                WHERE	open_referer != ''
                AND 	open_id <= '".$processBegin."' ";
    $query = Database::query( $sql );

    if(Database::num_rows($query) != 0) {
    	$i=0;
    	while ($row = Database::fetch_row ($query)) {
    		$ref = $row[0];
    		$referers_array = addReferer( $ref , $referers_array );
    	}
    	fillReferersTable( $referers_array );
    }
    // referers done
    //-------------------------------------------------------------------//
    // end of process
    // cleaning of $TABLETRACK_OPEN table
    cleanProcessedRecords($processBegin);
    // reset to the initial value
    ignore_user_abort($ignore);
}

/*
 *
 *		Utils
 *
 */

/**
 * @author Sebastien Piraux <piraux_seb@hotmail.com>
 * @param limit : all records BEFORE $limit will be affected
 * @desc this function will delete the remote_host, user_agent
 	and referer rows from the track_open table recorded before
 	the date $limit.  OPTIMIZE is called to get back the memory
 	espaces deleted
 */
function cleanProcessedRecords( $limit ) {
    global $TABLETRACK_OPEN;
    $limit = Database::escape_string($limit);
    $sql = "UPDATE $TABLETRACK_OPEN
                            SET open_remote_host = '',
                                    open_agent = '',
                                    open_referer =''
                            WHERE open_id <= '".$limit."'";
    $query = Database::query( $sql );
    Database::query("OPTIMIZE TABLE $TABLETRACK_OPEN");
}

/**
 * 	Provider
 *
 **/

/**
 * @author Sebastien Piraux <piraux_seb@hotmail.com>
 * @param remhost : must be @getHostByAddr($_SERVER['REMOTE_ADDR']
 * @desc this function will extract the provider name from a given
 	remote host and record this occurence in the corresponding
 	table
 */
function extractProvider($remhost) {
    if($remhost == "Unknown")
    return $remhost;
    $explodedRemhost = explode(".", $remhost);
    $provider = $explodedRemhost[sizeof( $explodedRemhost )-2]
    			."."
    			.$explodedRemhost[sizeof( $explodedRemhost )-1];

    if($provider == "co.uk" || $provider == "co.jp")
    	return $explodedRemhost[sizeof( $explodedRemhost )-3].$provider;
    else return $provider;
}


/**
 * @author Sebastien Piraux <piraux_seb@hotmail.com>
 * @param provider : name of the provider
 * @param providers_array : list of providers  and their counter
 * @desc this function will :
 	- if the provider is already in the array it will increment
 		the corresponding value
 	- if the provider doesn't exist it will be added and set to 1
 */
function addProvider($provider,$providers_array) {
    if(isset($providers_array[$provider])) {
            // add one unity to this provider occurrences
            $providers_array[$provider] = $providers_array[$provider] + 1;
    } else {
            // first occurrence of this provider
            $providers_array[$provider] = 1;
    }
    return $providers_array;
}

/**
 * @author Sebastien Piraux <piraux_seb@hotmail.com>
 * @param providers_array : list of providers  and their counter
 * @desc update the providers'table with new values
 */
function fillProvidersTable($providers_array) {
    global $TABLESTATS_PROVIDERS;

    if(is_array($providers_array)) {
        foreach ( $providers_array as $prov=>$number ) {
            $sql = "SELECT counter
                                    FROM ".$TABLESTATS_PROVIDERS."
                                    WHERE provider = '".$prov."'";
            $res = Database::query($sql);

            // if this provider already exists in the DB
            if($row = Database::num_rows($res)) {
                    // update
                    $sql2 = "UPDATE $TABLESTATS_PROVIDERS
                                                    SET counter = counter + '$number'
                                                    WHERE provider = '".$prov."'";
            } else {
                    // insert
                    $sql2 = "INSERT INTO $TABLESTATS_PROVIDERS
                                            (provider,counter)
                                            VALUES ('".$prov."','".$number."')";
            }
            Database::query($sql2);;
        }
    }
}

/***************************************************************************
 *
 *		Country
 *
 ***************************************************************************/

/**
 * @author Sebastien Piraux <piraux_seb@hotmail.com>
 * @return a 2D array filled with code and name of countries
 * @desc This function is used to build an array containing
 	countries informations
 */
function loadCountries() {
    global $TABLESTATS_COUNTRIES;
    $sql = "SELECT code, country
                            FROM $TABLESTATS_COUNTRIES";
    $res = Database::query($sql);
    $i = 0 ;
    if (Database::num_rows($res) > 0){
	    while ($row = Database::fetch_array($res)) {
	            $list_countries[$i][0] = $row["code"];
	            $list_countries[$i][1] = $row["country"];
	            $i++;
	    }
    }
    Database::free_result($res);
    return $list_countries;
}

/**
 * @author Sebastien Piraux <piraux_seb@hotmail.com>
 * @param remhost : must be @getHostByAddr($_SERVER['REMOTE_ADDR']
 * @param list_countries : list of countries -__-
 * @return Name of the country or "Unknown" if not found
 * @desc this function will extract the country from a given remote
 	host and increment the good value in the corresponding table
 */
function extractCountry($remhost,$list_countries) {
    if($remhost == "Unknown")
        return $remhost;
    // country code is the last value of remote host
    $explodedRemhost = explode(".",$remhost);
    $countryCode = $explodedRemhost[sizeof( $explodedRemhost )-1];
    for($i = 0 ; $i < sizeof( $list_countries );$i++) {
            if($list_countries[$i][0] == $countryCode)
                    return $list_countries[$i][1];
    }
}

/**
 * @author Sebastien Piraux <piraux_seb@hotmail.com>
 * @param country : name of the country or 'Unknown'
 * @param countries_array : list of countries and their
 	number of occurence
 * @desc this function will increment number of occurrence
 	for $country in the countries' tables
 */
function addCountry($country,$countries_array) {
    if(isset($countries_array[$country])) {
            // add one unity to this provider occurrences
            $countries_array[$country] = $countries_array[$country] + 1;
    } else {
            // first occurrence of this provider
            $countries_array[$country] = 1;
    }
    return $countries_array;
}

/**
 * @author Sebastien Piraux <piraux_seb@hotmail.com>
 * @param countries_array : list of countries and their counter
 * @desc update the countries'table with new values
 */
function fillCountriesTable($countries_array) {
    global $TABLESTATS_COUNTRIES;
    if(is_array($countries_array)) {
        foreach ( $countries_array as $country=>$number ) {
		// update
		$sql = "UPDATE $TABLESTATS_COUNTRIES
						SET counter = counter + '$number'
						WHERE country = '".$country."'";
		Database::query($sql);
		}
    }
}

/***************************************************************************
 *
 *		Agent : Browser and OS
 *
 ***************************************************************************/

/**
 * @author Sebastien Piraux <piraux_seb@hotmail.com>
 * @return a 2D array filled with code and name of browsers
 * @desc This function is used to build an array containing
 	browser informations
 */
function loadBrowsers() {

    $buffer = split ("#","Gecko|Gecko#Mozilla/3|Mozilla 3.x#Mozilla/4.0|Mozilla 4.0x#Mozilla/4.5|Mozilla 4.5x#Mozilla/4.6|Mozilla 4.6x#Mozilla/4.7|Mozilla 4.7x#Mozilla/5.0|Mozilla 5.0x#MSIE 1.2|MSIE 1.2#MSIE 3.01|MSIE 3.x#MSIE 3.02|MSIE 3.x#MSIE 4.0|MSIE 4.x#MSIE 4.01|MSIE 4.x#MSIE 4.5|MSIE 4.5#MSIE 5.0b1|MSIE 5.0x#MSIE 5.0b2|MSIE 5.0x#MSIE 5.0|MSIE 5.0x#MSIE 5.01|MSIE 5.0x#MSIE 5.1|MSIE 5.1#MSIE 5.1b1|MSIE 5.1#MSIE 5.5|MSIE 5.5#MSIE 5.5b1|MSIE 5.5#MSIE 5.5b2|MSIE 5.5#MSIE 6.0|MSIE 6#MSIE 6.0b|MSIE 6#MSIE 6.5a|MSIE 6.5#Lynx/2.8.0|Lynx 2#Lynx/2.8.1|Lynx 2#Lynx/2.8.2|Lynx 2#Lynx/2.8.3|Lynx 2#Lynx/2.8.4|Lynx 2#Lynx/2.8.5|Lynx 2#HTTrack 3.0x|HTTrack#OmniWeb/4.0.1|OmniWeb#Opera 3.60|Opera 3.60#Opera 4.0|Opera 4#Opera 4.01|Opera 4#Opera 4.02|Opera 4#Opera 5|Opera 5#Opera/3.60|Opera 3.60#Opera/4|Opera 4#Opera/5|Opera 5#Opera/6|Opera 6#Opera 6|Opera 6#Netscape6|NS 6#Netscape/6|NS 6#Netscape7|NS 7#Netscape/7|NS 7#Konqueror/2.0|Konqueror 2#Konqueror/2.0.1|Konqueror 2#Konqueror/2.1|Konqueror 2#Konqueror/2.1.1|Konqueror 2#Konqueror/2.1.2|Konqueror 2#Konqueror/2.2|Konqueror 2#Teleport Pro|Teleport Pro#WebStripper|WebStripper#WebZIP|WebZIP#Netcraft Web|NetCraft#Googlebot|Googlebot#WebCrawler|WebCrawler#InternetSeer|InternetSeer#ia_archiver|ia archiver");

    //$list_browser[x][0] is the name of browser as in $_SERVER['HTTP_USER_AGENT']
    //$list_browser[x][1] is the name of browser that will be used in display and tables
    $i=0;
    foreach( $buffer as $buffer1 ) {
       list ( $list_browsers[$i][0], $list_browsers[$i][1]) = split ('[|]', $buffer1 );
       $i++;
    }
    return $list_browsers;
}

/**
 * @author Sebastien Piraux <piraux_seb@hotmail.com>
 * @return a 2D array filled with code and name of OS
 * @desc This function is used to build an array containing
 	OS informations
 */
function loadOs() {
    $buffer = split ("#","Windows 95|Win 95#Windows_95|Win 95#Windows 98|Win 98#Windows NT|Win NT#Windows NT 5.0|Win 2000#Windows NT 5.1|Win XP#Windows 2000|Win 2000#Windows XP|Win XP#Windows ME|Win Me#Win95|Win 95#Win98|Win 98#WinNT|Win NT#linux-2.2|Linux 2#Linux|Linux#Linux 2|Linux 2#Macintosh|Mac#Mac_PPC|Mac#Mac_PowerPC|Mac#SunOS 5|SunOS 5#SunOS 6|SunOS 6#FreeBSD|FreeBSD#beOS|beOS#InternetSeer|InternetSeer#Googlebot|Googlebot#Teleport Pro|Teleport Pro");
    $i=0;
    foreach( $buffer as $buffer1 ) {
       list ( $list_os[$i][0], $list_os[$i][1]) = split ('[|]', $buffer1 );
       $i+=1;
    }
    return $list_os;
}

/**
 * @author Sebastien Piraux <piraux_seb@hotmail.com>
 * @param remhost : must be $_SERVER['HTTP_USER_AGENT']
 * @param list_browsers : browsers list :x
 * @param list_os : os list :x
 * @return a string formatted like : browser|OS
 	browser and OS are the 'viewable' names
 * @desc this function will extract browser and OS from
 	$_SERVER['HTTP_USER_AGENT']
 */
function extractAgent( $user_agent, $list_browsers, $list_os ) {
	// default values, if nothing corresponding found
	$viewable_browser = "Unknown";
	$viewable_os = "Unknown";

	// search for corresponding pattern in $_SERVER['HTTP_USER_AGENT']
	// for browser
	for($i = 0; $i < count( $list_browsers ); $i++) {
		$pos = strpos( $user_agent, $list_browsers[$i][0] );
		if( $pos !== false ) {
			$viewable_browser = $list_browsers[$i][1];
		}
	}

	// for os
	for($i = 0; $i < count($list_os); $i++) {
		$pos = strpos( $user_agent, $list_os[$i][0] );
		if( $pos !== false ) {
			$viewable_os = $list_os[$i][1];
		}
	}
	return $viewable_browser."|".$viewable_os;

}

/**
 * @author Sebastien Piraux <piraux_seb@hotmail.com>
 * @param browser : name of the browser or 'Unknown'
 * @param browsers_array :
 * @desc this function will :
 	- if the browser is already in the table it will increment
 		the corresponding value
 	- if the browser doesn't exist it will be added and set to 1
 */
function addBrowser($browser,$browsers_array) {
	if(isset( $browsers_array[$browser])) {
		// add one unity to this provider occurrences
		$browsers_array[$browser] = $browsers_array[$browser] + 1;
	} else {
		// first occurrence of this provider
		$browsers_array[$browser] = 1;
	}
	return $browsers_array;
}

/**
 * @author Sebastien Piraux <piraux_seb@hotmail.com>
 * @param os : name of the OS or 'Unknown'
 * @param os_array : list of os and number of occurences
 * @desc this function will :
 	- if the os is already in the table it will increment
 		the corresponding value
 	- if the os doesn't exist it will be added and set to 1
 */
function addOs($os,$os_array) {
	if(isset($os_array[$os])) {
		// add one unity to this provider occurrences
		$os_array[$os] = $os_array[$os] + 1;
	} else {
		// first occurrence of this provider
		$os_array[$os] = 1;
	}
	return $os_array;
}

/**
 * @author Sebastien Piraux <piraux_seb@hotmail.com>
 * @param browsers_array : list of browsers and their counter
 * @desc update the browsers'table with new values
 */
function fillBrowsersTable($browsers_array) {
    global $TABLESTATS_BROWSERS;
    if (is_array($browsers_array)) {
        foreach ( $browsers_array as $browser=>$number ) {
			$sql = "SELECT counter
						FROM $TABLESTATS_BROWSERS
						WHERE browser = '".$browser."'";
			$res = Database::query($sql);

			// if this provider already exists in the DB
			if($row = Database::num_rows($res)) {
				// update
				$sql2 = "UPDATE $TABLESTATS_BROWSERS
								SET counter = counter + '$number'
								WHERE browser = '".$browser."'";
			} else {
				// insert
				$sql2 = "INSERT INTO $TABLESTATS_BROWSERS
							(browser,counter)
							VALUES ('".$browser."','".$number."')";
			}
			Database::query($sql2);
		}
    }
}

/**
 * @author Sebastien Piraux <piraux_seb@hotmail.com>
 * @param os_array : list of os and their counter
 * @desc update the os'table with new values
 */
function fillOsTable($os_array) {
    global $TABLESTATS_OS;
    if (is_array($os_array)) {
        foreach ($os_array as $os=>$number) {
			$sql = "SELECT counter
						FROM $TABLESTATS_OS
						WHERE os = '".$os."'";
			$res = Database::query($sql);

			// if this provider already exists in the DB
			if($row = Database::num_rows($res)) {
				// update
				$sql2 = "UPDATE $TABLESTATS_OS
								SET counter = counter + '$number'
								WHERE os = '".$os."'";
			} else {
				// insert
				$sql2 = "INSERT INTO $TABLESTATS_OS
							(os,counter)
							VALUES ('".$os."','".$number."')";
			}
			Database::query($sql2);
		}
    }
}

/***************************************************************************
 *
 *		Referers
 *
 ***************************************************************************/

/**
 * @author Sebastien Piraux <piraux_seb@hotmail.com>
 * @param referer : name of the referer
 * @param referers_array : list of referer and number of occurences
 * @desc this function will :
 	- if the referer is already in the table it will increment
 		the corresponding value
 	- if the referer doesn't exist it will be added and set to 1
*/
function addReferer($referer,$referers_array) {
    if(isset($referers_array[$referer])) {
            // add one unity to this provider occurrences
            $referers_array[$referer] = $referers_array[$referer] + 1;
    } else {
            // first occurrence of this provider
            $referers_array[$referer] = 1;
    }
    return $referers_array;
}

/**
 * @author Sebastien Piraux <piraux_seb@hotmail.com>
 * @param referers_array : list of referers and their counter
 * @desc update the referers'table with new values
*/
function fillReferersTable($referers_array) {
    global $TABLESTATS_REFERERS;
    if (is_array($referers_array)) {
        foreach ( $referers_array as $referer=>$number ) {
            $sql = "SELECT counter
                                    FROM $TABLESTATS_REFERERS
                                    WHERE referer = '".$referer."'";
            $res = Database::query($sql);

            // if this provider already exists in the DB
            if($row = Database::num_rows($res)) {
                    // update
                    $sql2 = "UPDATE $TABLESTATS_REFERERS
                                                    SET counter = counter + '$number'
                                                    WHERE referer = '".$referer."'";
            } else {
                    // insert
                    $sql2 = "INSERT INTO $TABLESTATS_REFERERS
                                            (referer,counter)
                                            VALUES ('".$referer."','".$number."')";
            }
            Database::query($sql2);
        }
    }
}
