<?php //$id:$
//TODO migrate this into the scorm.class.php file
/*
==============================================================================
        Dokeos - elearning and course management software

        Copyright (c) 2004 Dokeos S.A.
        Copyright (c) 2004 Denes Nagy
        Copyright (c) 2003 Ghent University (UGent)
        Copyright (c) 2001 Universite catholique de Louvain (UCL)

	For a full list of contributors, see "credits.txt".
	The full license can be read in "license.txt".
        This program is free software; you can redistribute it and/or
        modify it under the terms of the GNU General Public License
        as published by the Free Software Foundation; either version 2
        of the License, or (at your option) any later version.
										        See the GNU General Public License for more details.

	Contact: Dokeos, 181 rue Royale, B-1000 Brussels, Belgium,
							info@dokeos.com
=============================================================================
*/
/**
=============================================================================
 * This file is a container for functions related to SCORM and other
 * standard or common course content types. It might later become a class
 * instead of a functions library, as several components are likely to be
 * re-used for different content types.
 * @package dokeos.learnpath.scorm
 * @author Yannick Warnier <ywarnier@beeznest.org>
 * @author Based on work from Denes NAgy, Isthvan Mandak and Roan Embrechts
=============================================================================
 */
/**
 * Delete a scorm directory (check for imsmanifest and if found, deletes the related rows in scorm tables also)
 * @param               string          Dir path
 * @return      boolean True on success, false otherwise
 */
function removescormDir($dir)
{
  global $_course;
  if(!@$opendir = opendir($dir))
  {
    return false;
  }
  while($readdir = readdir($opendir))
  {
    if($readdir != '..' && $readdir != '.')
    {
      if(is_file($dir.'/'.$readdir))
      {
        $pos=strpos('/'.$readdir, 'imsmanifest.xml');
        if ($pos) {  //so we have the imsmanifest in this dir
                     //from d:/myworks/dokeos/dokeos_cvs/dokeos/dokeos/courses/CVSCODE4/scorm/LP2/LP2
                    //we have to get /LP2/LP2
          $path=api_get_path('SYS_COURSE_PATH').$_course['official_code'].'/scorm';
          $pos=strpos($dir,$path);
          if ($pos==0) {
            $scormdir=substr($dir,strlen($path),strlen($dir)-strlen($path));
            $courseid=$_course['official_code'];
            $sql="SELECT * FROM ".Database::get_scorm_table(TABLE_SCORM_MAIN)." where (contentTitle='$scormdir' and dokeosCourse='$courseid')";
            $result=Database::query($sql,__FILE__,__LINE__);
            while ($row=mysql_fetch_array($result))
            {
	      $c=$row['contentId'];
	      $sql2="DELETE FROM ".Database::get_scorm_table(TABLE_SCORM_SCO_DATA)." where contentId=$c";
	      $result2=Database::query($sql2,__FILE__,__LINE__);
	    }
	    $sql="DELETE FROM ".Database::get_scorm_table(TABLE_SCORM_MAIN)." where (contentTitle='$scormdir' and dokeosCourse='$courseid')";
	    $result=Database::query($sql,__FILE__,__LINE__);
	  }
	}
	if(!@unlink($dir.'/'.$readdir))
	{
	  return false;
	}
      } elseif(is_dir($dir.'/'.$readdir)) {
        if(!removescormDir($dir.'/'.$readdir))
        {
	  return false;
	}
      }
    }
  }
  closedir($opendir);
  if(!@rmdir($dir)) {
    return false;
  }
  return true;
}
/**
 * This function removes a directory if it exists
 * @param               string          Dir path
 * @return      boolean True on success, false otherwise
 * @uses        removescormDir()        to actually remove the directory
 */
function scorm_delete($file)
{
  if ( check_name_exist($file) )
  {
    if ( is_dir($file) )
    {
      return removescormDir($file);
    }
  }
  else
  {
    return false; // no file or directory to delete
  }
}
/**
 * This function gets a list of scorm paths located in a given directory
 * @param	string	Base directory path
 * @param	string	Current directory
 * @param	array	Reference to a list of paths that exist in the database
 * @return	array	Array(type=>array(),size=>array(),date=>array())
 */
function get_scorm_paths_from_dir($basedir, $curdir, &$attribute){
	$scormcontent=false;
	$saved_dir = getcwd();
	$res = @chdir (realpath($basedir.$curdir));
	if($res === false){ return(null);}
	$handle = opendir(".");

	define('A_DIRECTORY', 1);
	define('A_FILE',      2);

	$fileList = array();
	// fill up $fileList for displaying the files list later on
	while ($file = readdir($handle))
	{
		if ($file == "." || $file == ".." || $file == '.htaccess')
		{
			continue;						// Skip current and parent directories
		}

		$fileList['name'][] = $file;

		//if ($file=='imsmanifest.xml') { $scormcontent=true; }

		if(is_dir($file))
		{
			$fileList['type'][] = A_DIRECTORY;
			$fileList['size'][] = false;
			$fileList['date'][] = false;
		}
		elseif(is_file($file))
		{
			$fileList['type'][] = A_FILE;
			$fileList['size'][] = filesize($file);
			$fileList['date'][] = filectime($file);
		}
		/*
		 * Make the correspondance between
		 * info given by the file system
		 * and info given by the DB
		 */
		if (is_array($attribute) && count($attribute['path'])>0)
		{
			$keyAttribute = array_search($curdir."/".$file, $attribute['path']);
		}

		if ($keyAttribute !== false)
		{
			$fileList['comment'   ][] = $attribute['comment'   ][$keyAttribute];
			$fileList['visibility'][] = $attribute['visibility'][$keyAttribute];
			unset ($attribute['comment'   ][$keyAttribute],
			  $attribute['visibility'][$keyAttribute],
			  $attribute['path'      ][$keyAttribute]);
		}
		else
		{
			$fileList['comment'   ][] = false;
			$fileList['visibility'][] = false;
		}
	}
	closedir($handle);
	chdir($saved_dir);
	return $fileList;
}
/**
 * Detects the SCORM version from an imsmanifest.xml file
 * @param	string	Path to imsmanifest.xml
 * @return	string	SCORM version (1.0,1.1,1.2,1.3)
 * @todo Implement this function
 */
function get_scorm_version($path){
	return '1.2';
}
?>
