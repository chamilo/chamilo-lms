<?php
/*
============================================================================== 
	Dokeos - elearning and course management software
	
	Copyright (c) 2004 Dokeos S.A.
	Copyright (c) 2003 Ghent University (UGent)
	Copyright (c) 2001 Universite catholique de Louvain (UCL)
	
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
*	@package dokeos.user
============================================================================== 
*/


/*----------------------------------------
     CATEGORIES DEFINITION TREATMENT
 --------------------------------------*/
/**
 * create a new category definition for the user information
 *
 * @author - Hugues peeters <peeters@ipm.ucl.ac.be>
 * @author - Christophe Gesché <gesche@ipm.ucl.ac.be>
 * @param  - string $title - category title
 * @param  - string $comment - title comment
 * @param  - int$nbline - lines number for the field the user will fill.
 * @return - bollean true if succeed, else bolean false
 */

function create_cat_def($title="", $comment="", $nbline="5")
{
	global $TBL_USERINFO_DEF;

	if ( 0 == (int) $nbline || empty($title))
	{
		return false;
	}

	$sql = "SELECT MAX(`rank`) maxRank FROM ".$TBL_USERINFO_DEF."";
	$result = api_sql_query($sql,__FILE__,__LINE__);
	if ($result) $maxRank = mysql_fetch_array($result);

	$maxRank = $maxRank['maxRank'];

	$thisRank = $maxRank + 1;

	$title   = trim($title);
	$comment = trim($comment);

	$sql = "INSERT INTO ".$TBL_USERINFO_DEF." SET
			`title`		= '$title',
			`comment`	= '$comment',
			`line_count`	= '$nbline',
			`rank`		= '$thisRank'";

	api_sql_query($sql,__FILE__,__LINE__);

	return true;
}

/**
 * modify the definition of a user information category
 *
 * @author - Hugues peeters <peeters@ipm.ucl.ac.be>
 * @author - Christophe Gesché <gesche@ipm.ucl.ac.be>
 * @param  - int $id - id of the category
 * @param  - string $title - category title
 * @param  - string $comment - title comment
 * @param  - int$nbline - lines number for the field the user will fill.
 * @return - boolean true if succeed, else otherwise
 */

function edit_cat_def($id, $title, $comment, $nbline)
{
	global $TBL_USERINFO_DEF;

	if ( 0 == (int) $nbline || 0 == (int) $id )
	{
		return false;
	}
	$title   = trim($title);
	$comment = trim($comment);

	$sql = "UPDATE ".$TBL_USERINFO_DEF." SET
			`title`		= '$title',
			`comment`	= '$comment',
			`line_count`	= '$nbline'
			WHERE id	= '$id'";

	api_sql_query($sql,__FILE__,__LINE__);

	return true;
}

/**
 * remove a category from the category list
 *
 * @author - Hugues peeters <peeters@ipm.ucl.ac.be>
 * @author - Christophe Gesché <gesche@ipm.ucl.ac.be>
 *
 * @param  - int $id - id of the category
 *				or "ALL" for all category
 * @param  - boolean $force - FALSE (default) : prevents removal if users have
 *                            already fill this category
 *                            TRUE : bypass user content existence check
 * @param  - int $nbline - lines number for the field the user will fill.
 * @return - bollean  - TRUE if succeed, ELSE otherwise
 */

function remove_cat_def($id, $force = false)
{
	global $TBL_USERINFO_CONTENT, $TBL_USERINFO_DEF;

	if ( (0 == (int) $id || $id == "ALL") || ! is_bool($force))
	{
		return false;
	}

	if ( $id != "ALL")
	{
		$sqlCondition = " WHERE id = '$id'";
	} else {
		$sqlCondition = "";
	}

	if ($force == false)
	{
		$sql = "SELECT * FROM ".$TBL_USERINFO_CONTENT." ".$sqlCondition;
		$result = api_sql_query($sql,__FILE__,__LINE__);

		if ( mysql_num_rows($result) > 0)
		{
			return false;
		}
	}

	$sql = "DELETE FROM ".$TBL_USERINFO_DEF." ".$sqlCondition;
	api_sql_query($sql,__FILE__,__LINE__);
}

/**
 * move a category in the category list
 *
 * @author - Hugues peeters <peeters@ipm.ucl.ac.be>
 * @author - Christophe Gesché <gesche@ipm.ucl.ac.be>
 *
 * @param  - int $id - id of the category
 * @param  - direction "up" or "down" :
 *					"up"	decrease the rank of gived $id by switching rank with the just lower
 *					"down"	increase the rank of gived $id by switching rank with the just upper
 *
 * @return - boolean true if succeed, else bolean false
 */

function move_cat_rank($id, $direction) // up & down.
{
	global $TBL_USERINFO_DEF;

	if ( 0 == (int) $id || ! ($direction == "up" || $direction == "down") )
	{
		return false;
	}

	$sql = "SELECT rank FROM ".$TBL_USERINFO_DEF." WHERE id = '$id'";
	$result = api_sql_query($sql,__FILE__,__LINE__);

	if (mysql_num_rows($result) < 1)
	{
		return false;
	}

	$cat = mysql_fetch_array($result);
	$rank = (int) $cat["rank"];
	return move_cat_rank_by_rank($rank, $direction);
}

/**
 * move a category in the category list
 *
 * @author - Hugues peeters <peeters@ipm.ucl.ac.be>
 * @author - Christophe Gesché <gesche@ipm.ucl.ac.be>
 *
 * @param  - int $rank - actual rank of the category
 * @param  - direction "up" or "down" :
 *					"up"	decrease the rank of gived $rank by switching rank with the just lower
 *					"down"	increase the rank of gived $rank by switching rank with the just upper
 *
 * @return - boolean true if succeed, else bolean false
 */

function move_cat_rank_by_rank($rank, $direction) // up & down.
{
	global $TBL_USERINFO_DEF;

	if ( 0 == (int) $rank || ! ($direction == "up" || $direction == "down") )
	{
		return false;
	}

	if ($direction == "down") // thus increase rank ...
	{
		$sort = "ASC";
		$compOp = ">=";
	}
	else	 // thus decrease rank ...
	{
		$sort = "DESC";
		$compOp = "<=";
	}

	// this request find the 2 line to be switched (on rank value)
	$sql = "SELECT id, rank FROM ".$TBL_USERINFO_DEF." WHERE rank $compOp $rank
	ORDER BY rank $sort LIMIT 2";

	$result = api_sql_query($sql,__FILE__,__LINE__);

	if (mysql_num_rows($result) < 2)
	{
		return false;
	}

	$thisCat = mysql_fetch_array($result);
	$nextCat = mysql_fetch_array($result);

	$sql1 = "UPDATE ".$TBL_USERINFO_DEF." SET rank ='".$nextCat['rank'].
			"' WHERE id = '".$thisCat['id']."'";
	$sql2 = "UPDATE ".$TBL_USERINFO_DEF." SET rank ='".$thisCat['rank'].
			"' WHERE id = '".$nextCat['id']."'";

	api_sql_query($sql1,__FILE__,__LINE__);
	api_sql_query($sql2,__FILE__,__LINE__);

	return true;
}


/**
 * @author Hugues Peeters - peeters@ipm.ucl.ac.be
 * @param  int     $user_id
 * @param  string  $course_code
 * @param  array   $properties - should contain 'role', 'status', 'tutor_id'
 * @return boolean true if succeed false otherwise
 */

function update_user_course_properties($user_id, $course_code, $properties)
{
	global $tbl_coursUser,$_user;
        $sqlChangeStatus = "";
        if ($user_id != $_user['user_id'])
            $sqlChangeStatus = "`status`     = '".$properties['status']."',";
	$result = api_sql_query("UPDATE $tbl_coursUser
	                        SET     `role`       = '".$properties['role']."',
	                                ".$sqlChangeStatus."
	                                `tutor_id`      = '".$properties['tutor']."'
	                        WHERE   `user_id`    = '".$user_id."'
	                        AND     `course_code` = '".$course_code."'",__FILE__,__LINE__);

	if (mysql_affected_rows() > 0)
	{
		return true;
	}
	else
	{
		return false;
	}
}





/*----------------------------------------
     CATEGORIES CONTENT TREATMENT
 --------------------------------------*/





/**
 * fill a bloc for information category
 *
 * @author - Hugues peeters <peeters@ipm.ucl.ac.be>
 * @author - Christophe Gesché <gesche@ipm.ucl.ac.be>
 * @param  - $definition_id,
 * @param  - $user_id,
 * @param  - $user_ip,
 * @param  - $content
 * @return - boolean true if succeed, else bolean false
 */

function fill_new_cat_content($definition_id, $user_id, $content="", $user_ip="")
{
	global $TBL_USERINFO_CONTENT;

	if (empty($user_ip))
	{
		global $REMOTE_ADDR;
		$user_ip = $REMOTE_ADDR;
	}

	$content = trim($content);


	if ( 0 == (int) $definition_id || 0 == (int) $user_id || $content == "")
	{
		// Here we should introduce an error handling system...

		return false;
	}

	// Do not create if already exist

	$sql = "SELECT id FROM ".$TBL_USERINFO_CONTENT."
			WHERE	`definition_id`	= '$definition_id'
			AND		`user_id`	= '$user_id'";

	$result = api_sql_query($sql,__FILE__,__LINE__);

	if (mysql_num_rows($result) > 0)
	{
		return false;
	}


	$sql = "INSERT INTO ".$TBL_USERINFO_CONTENT." SET
			`content`	= '$content',
			`definition_id`	= '$definition_id',
			`user_id`	= '$user_id',
			`editor_ip`		= '$user_ip',
			`edition_time`	= now()";

	api_sql_query($sql,__FILE__,__LINE__);

	return true;
}

/**
 * edit a bloc for information category
 *
 * @author - Hugues peeters <peeters@ipm.ucl.ac.be>
 * @author - Christophe Gesché <gesche@ipm.ucl.ac.be>
 * @param  - $definition_id,
 * @param  - $user_id,
 * @param  - $user_ip, DEFAULT $REMOTE_ADDR
 * @param  - $content ; if empty call delete the bloc
 * @return - boolean true if succeed, else bolean false
 */

function edit_cat_content($definition_id, $user_id, $content ="", $user_ip="")
{
	global $TBL_USERINFO_CONTENT;

	if (empty($user_ip))
	{
		global $REMOTE_ADDR;
		$user_ip = $REMOTE_ADDR;
	}

	if (0 == (int) $user_id || 0 == (int) $definition_id)
	{
		return false;
	}

	$content = trim($content);

	if ( trim($content) == "")
	{
		return cleanout_cat_content($user_id, $definition_id);
	}


	$sql= "UPDATE ".$TBL_USERINFO_CONTENT." SET
			`content`	= '$content',
			`editor_ip`		= '$user_ip',
			`edition_time`	= now()
			WHERE definition_id = '$definition_id' AND user_id = '$user_id'";

	api_sql_query($sql,__FILE__,__LINE__);

	return true;
}

/**
 * clean the content of a bloc for information category
 *
 * @author - Hugues peeters <peeters@ipm.ucl.ac.be>
 * @author - Christophe Gesché <gesche@ipm.ucl.ac.be>
 * @param  - $definition_id,
 * @param  - $user_id
 * @return - boolean true if succeed, else bolean false
 */

function cleanout_cat_content($user_id, $definition_id)
{
	global $TBL_USERINFO_CONTENT;

	if (0 == (int) $user_id || 0 == (int) $definition_id)
	{
		return false;
	}

	$sql = "DELETE FROM ".$TBL_USERINFO_CONTENT."
			WHERE user_id = '$user_id'  AND definition_id = '$definition_id'";

	api_sql_query($sql,__FILE__,__LINE__);

	return true;
}



/*----------------------------------------
     SHOW USER INFORMATION TREATMENT
 --------------------------------------*/

/**
 * get the user info from the user id
 * @author - Hugues Peeters <peeters@ipm.ucl.ac.be>
 * @author - Christophe Gesché <gesche@ipm.ucl.ac.be>
 * @param - int $user_id user id as stored in the Dokeos main db
 * @return - array containg user info sort by categories rank
 *           each rank contains 'title', 'comment', 'content', 'cat_id'
 */


function get_course_user_info($user_id)
{
	global $TBL_USERINFO_CONTENT, $TBL_USERINFO_DEF;

	$sql = "SELECT	cat.id catId,	cat.title,
					cat.comment ,	content.content
			FROM  	".$TBL_USERINFO_DEF." cat LEFT JOIN ".$TBL_USERINFO_CONTENT." content
			ON cat.id = content.definition_id 	AND content.user_id = '$user_id'
			ORDER BY cat.rank, content.id";

	$result = api_sql_query($sql,__FILE__,__LINE__);

	if (mysql_num_rows($result) > 0)
	{
		while ($userInfo = mysql_fetch_array($result, MYSQL_ASSOC))
		{
			$userInfos[]=$userInfo;
		}

		return $userInfos;
	}

	return false;
}



/**
 * get the main user information
 * @author - Hugues Peeters <peeters@ipm.ucl.ac.be>
 * @author - Christophe Gesché <gesche@ipm.ucl.ac.be>
 * @param -  int $user_id user id as stored in the Dokeos main db
 * @return - array containing user info as 'lastName', 'firstName'
 *           'email', 'role'
 */

function get_main_user_info($user_id, $courseCode)
{
	if (0 == (int) $user_id)
	{
		return false;
	}


	$table_course_user = Database::get_main_table(TABLE_MAIN_COURSE_USER);
	$table_user = Database::get_main_table(TABLE_MAIN_USER);
	$sql = "SELECT	u.lastname lastName, u.firstname firstName, 
	                u.email, u.picture_uri picture, cu.role, 
	                cu.`status` `status`, cu.tutor_id
	        FROM    $table_user u, $table_course_user cu
	        WHERE   u.user_id = cu.user_id
	        AND     u.user_id = '$user_id'
	        AND     cu.course_code = '$courseCode'";

	$result = api_sql_query($sql,__FILE__,__LINE__);

	if (mysql_num_rows($result) > 0)
	{
		$userInfo = mysql_fetch_array($result, MYSQL_ASSOC);
		return $userInfo;
	}

	return false;
}




/**
 * get the user content of a categories plus the categories definition
 * @author - Hugues Peeters <peeters@ipm.ucl.ac.be>
 * @author - Christophe Gesché <gesche@ipm.ucl.ac.be>
 * @param  - int $userId - id of the user
 * @param  - int $catId - id of the categories
 * @return - array containing 'catId', 'title', 'comment',
 *           'nbline', 'contentId' and 'content'
 */

function get_cat_content($userId, $catId)
{
	global $TBL_USERINFO_CONTENT, $TBL_USERINFO_DEF;

	$sql = "SELECT	cat.id catId,	cat.title,
					cat.comment ,	cat.line_count,
					content.id contentId, 	content.content
			FROM  	".$TBL_USERINFO_DEF." cat LEFT JOIN ".$TBL_USERINFO_CONTENT." content
			ON cat.id = content.definition_id
			AND content.user_id = '$userId'
			WHERE cat.id = '$catId' ";

	$result = api_sql_query($sql,__FILE__,__LINE__);

	if (mysql_num_rows($result) > 0)
	{
		$catContent = mysql_fetch_array($result, MYSQL_ASSOC);
		$catContent['nbline'] = $catContent['line_count'];
		return $catContent;
	}

	return false;
}


/**
 * get the definition of a category
 *
 * @author - Christophe Gesché <gesche@ipm.ucl.ac.be>
 * @author - Hugues Peeters <peeters@ipm.ucl.ac.be>
 * @param  - int $catId - id of the categories
 * @return - array containing 'id', 'title', 'comment', and 'nbline',
 */


function get_cat_def($catId)
{
	global $TBL_USERINFO_DEF;

	$sql = "SELECT id, title, comment, line_count, rank FROM ".$TBL_USERINFO_DEF." WHERE id = '$catId'";

	$result = api_sql_query($sql,__FILE__,__LINE__);

	if (mysql_num_rows($result) > 0)
	{
		$catDef = mysql_fetch_array($result, MYSQL_ASSOC);
		$catDef['nbline'] = $catDef['line_count'];
		return $catDef;
	}

	return false;
}


/**
 * get list of all this course categories
 *
 * @author - Christophe Gesché <gesche@ipm.ucl.ac.be>
 * @author - Hugues Peeters <peeters@ipm.ucl.ac.be>
 * @return - array containing a list of arrays.
 *           And each of these arrays contains
 *           'catId', 'title', 'comment', and 'nbline',
 */


function get_cat_def_list()
{
	global $TBL_USERINFO_DEF;

	$sql = "SELECT	id catId,	title,	comment , line_count
			FROM  ".$TBL_USERINFO_DEF."
			ORDER BY rank";

	$result = api_sql_query($sql,__FILE__,__LINE__);

	if (mysql_num_rows($result) > 0)
	{
		while ($cat_def = mysql_fetch_array($result, MYSQL_ASSOC))
		{
			$cat_def_list[]=$cat_def;
		}

		return $cat_def_list;
	}

	return false;
}

/**
 * transform content in a html display
 * @author - Hugues Peeters <peeters@ipm.ucl.ac.be>
 * @param  - string $string string to htmlize
 * @ return  - string htmlized
 */

function htmlize($phrase)
{
	global $charset;
	return nl2br(htmlspecialchars($phrase,ENT_QUOTES,$charset));
}


/**
 * replaces some dangerous character in a string for HTML use
 *
 * @author - Hugues Peeters <peeters@ipm.ucl.ac.be>
 * @param  - string (string) string
 * @return - the string cleaned of dangerous character
 */

function replace_dangerous_char($string)
{
	$search[]="/" ; $replace[]="-";
	$search[]="\|"; $replace[]="-";
	$search[]="\""; $replace[]=" ";

	foreach($search as $key=>$char )
	{
		$string = str_replace($char, $replace[$key], $string);
	}

	return $string;
}