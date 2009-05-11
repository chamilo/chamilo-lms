<?php
/*
vim: set expandtab tabstop=4 shiftwidth=4:
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
*	This is the file display library for Dokeos.
*	Include/require it in your code to use its functionality.
*
*	@package dokeos.library
============================================================================== 
*/


/*
============================================================================== 
		GENERIC FUNCTIONS : FOR OLDER PHP VERSIONS
============================================================================== 
*/
if ( ! function_exists('array_search') )
{
	/**
	 * Searches haystack for needle and returns the key
	 * if it is found in the array, FALSE otherwise.
	 *
	 * Natively implemented in PHP since 4.0.5 version.
	 * This function is intended for previous version.
	 *
	 * @author - Hugues Peeters <peeters@ipm.ucl.ac.be>
	 * @param  - needle (mixed)
	 * @param  - haystack (array)
	 * @return - array key or FALSE
	 *
	 * @see    - http://www.php.net/array_search
	 */
	function array_search($needle, $haystack)
	{
		while (list($key, $val) = each($haystack))
			if ($val == $needle)
				return $key;
		return false;
	}
}

/*
============================================================================== 
		FILE DISPLAY FUNCTIONS
============================================================================== 
*/ 
/**
 * Define the image to display for each file extension.
 * This needs an existing image repository to work.
 *
 * @author - Hugues Peeters <peeters@ipm.ucl.ac.be>
 * @param  - $file_name (string) - Name of a file
 * @return - The gif image to chose
 */
function choose_image($file_name)
{
	static $type, $image;

	/* TABLES INITIALISATION */
	if (!$type || !$image)
	{
		$type['word'      ] = array('doc', 'dot',  'rtf', 'mcw',  'wps', 'psw', 'docm', 'docx', 'dotm',  'dotx');
		$type['web'       ] = array('htm', 'html', 'htx', 'xml',  'xsl',  'php', 'xhtml');
		$type['image'     ] = array('gif', 'jpg',  'png', 'bmp',  'jpeg');
		$type['audio'     ] = array('wav', 'mid',  'mp2', 'mp3',  'midi', 'sib', 'amr', 'kar');
		$type['video'     ] = array('mp4', 'mov',  'rm',  'pls',  'mpg',  'mpeg', 'au', 'flv', 'avi', 'wmv', 'asf', '3gp');
		$type['excel'     ] = array('xls', 'xlt',  'xls', 'xlt', 'pxl', 'xlsx', 'xlsm', 'xlam', 'xlsb', 'xltm', 'xltx');
		$type['compressed'] = array('zip', 'tar',  'rar', 'gz');
		$type['code'      ] = array('js',  'cpp',  'c',   'java', 'phps');
		$type['acrobat'   ] = array('pdf');
		$type['powerpoint'] = array('ppt', 'pps', 'pptm', 'pptx', 'potm', 'potx', 'ppam', 'ppsm', 'ppsx');
		$type['flash'     ] = array('fla', 'swf');
		$type['text'      ] = array('txt','log');
		$type['oo_writer' ] = array('odt', 'ott', 'sxw', 'stw');
		$type['oo_calc'   ] = array('ods', 'ots', 'sxc', 'stc');
		$type['oo_impress'] = array('odp', 'otp', 'sxi', 'sti');
		$type['oo_draw'   ] = array('odg', 'otg', 'sxd', 'std');

		$image['word'      ] = 'word.gif';
		$image['web'       ] = 'file_html.gif';
		$image['image'     ] = 'file_image.gif';
		$image['audio'     ] = 'file_sound.gif';
		$image['video'     ] = 'film.gif';
		$image['excel'     ] = 'excel.gif';
		$image['compressed'] = 'file_zip.gif';
		$image['code'      ] = 'file_txt.gif';
		$image['acrobat'   ] = 'file_pdf.gif';
		$image['powerpoint'] = 'powerpoint.gif';
		$image['flash'     ] = 'file_flash.gif';
		$image['text'      ] = 'file_txt.gif';
		//$image['oo_writer' ] = 'word.gif';
		//$image['oo_calc'   ] = 'excel.gif';
		//$image['oo_impress'] = 'powerpoint.gif';
		$image['oo_writer' ] = 'file_oo_writer.gif';
		$image['oo_calc'   ] = 'file_oo_calc.gif';
		$image['oo_impress'] = 'file_oo_impress.gif';
		$image['oo_draw'   ] = 'file_oo_draw.gif';
	}

	/* FUNCTION CORE */
	$extension = array();
	if (ereg('\.([[:alnum:]]+)$', $file_name, $extension))
	{
		$extension[1] = strtolower($extension[1]);

		foreach ($type as $generic_type => $extension_list)
		{
			if (in_array($extension[1], $extension_list))
			{
				return $image[$generic_type];
			}
		}
	}

	return 'defaut.gif';
}

/**
 * Transform the file size in a human readable format.
 *
 * @param  int      Size of the file in bytes
 * @return string A human readable representation of the file size
 */
function format_file_size($file_size)
{
	if($file_size >= 1073741824)
	{
		$file_size = round($file_size / 1073741824 * 100) / 100 . 'G';
	}
	elseif($file_size >= 1048576)
	{
		$file_size = round($file_size / 1048576 * 100) / 100 . 'M';
	}
	elseif($file_size >= 1024)
	{
		$file_size = round($file_size / 1024 * 100) / 100 . 'k';
	}
	else
	{
		$file_size = $file_size . 'B';
	}

	return $file_size;
}

/**
 * Transform a UNIX time stamp in human readable format date.
 *
 * @author - Hugues Peeters <peeters@ipm.ucl.ac.be>
 * @param  - $date - UNIX time stamp
 * @return - A human readable representation of the UNIX date
 */
function format_date($date)
{
	return date('d.m.Y', $date);
}

/**
 * Transform the file path to a URL.
 *
 * @param  - $file_path (string) - Relative local path of the file on the hard disk
 * @return - Relative url
 */
function format_url($file_path)
{
	$path_component = explode('/', $file_path);

	$path_component = array_map('rawurlencode', $path_component);

	return implode('/', $path_component);
}

/**
 * Get the most recent time the content of a folder was changed.
 *
 * @param  - $dir_name (string)   - Path of the dir on the hard disk
 * @param  - $do_recursive (bool) - Traverse all folders in the folder?
 * @return - Time the content of the folder was changed
 */
function recent_modified_file_time($dir_name, $do_recursive = true)
{
	$dir = dir($dir_name);
	$last_modified = 0;

	while(($entry = $dir->read()) !== false)
	{
		if ($entry != '.' && $entry != '..')
			continue;

		if (!is_dir($dir_name.'/'.$entry))
			$current_modified = filemtime($dir_name.'/'.$entry);
		elseif ($do_recursive)
			$current_modified = recent_modified_file_time($dir_name.'/'.$entry, true);

		if ($current_modified > $last_modified)
			$last_modified = $current_modified;
	}

	$dir->close();

	//prevents returning 0 (for empty directories)
	return ($last_modified == 0) ? filemtime($dir_name) : $last_modified;
}

/**
 * Get the total size of a directory.
 *
 * @param  - $dir_name (string) - Path of the dir on the hard disk
 * @return - Total size in bytes
 */
function folder_size($dir_name)
{
	$size = 0;

	if ($dir_handle = opendir($dir_name))
	{
		while (($entry = readdir($dir_handle)) !== false) 
		{
			if($entry == '.' || $entry == '..') 
				continue;

        	if(is_dir($dir_name.'/'.$entry))
        		$size += folder_size($dir_name.'/'.$entry);
        	else
        		$size += filesize($dir_name.'/'.$entry);
		}
		
		closedir($dir_handle);
    }

    return $size;
}

/**
 * Calculates the total size of a directory by adding the sizes (that
 * are stored in the database) of all files & folders in this directory.
 *
 * @param 	string  $path
 * @param 	boolean $can_see_invisible
 * @return 	Total size
 */
function get_total_folder_size($path, $can_see_invisible = false)
{	
	$table_itemproperty = Database::get_course_table(TABLE_ITEM_PROPERTY);
	$table_document = Database::get_course_table(TABLE_DOCUMENT);
	$tool_document = TOOL_DOCUMENT;
	
	$visibility_rule = 'props.visibility ' . ($can_see_invisible ? '<> 2' : '= 1');

	$sql = <<<EOQ
SELECT SUM(size)
	FROM $table_itemproperty AS props, $table_document AS docs
	WHERE docs.id = props.ref
		AND props.tool = '$tool_document'
		AND path LIKE '$path/%'
		AND $visibility_rule
EOQ;

	$result = api_sql_query($sql,__FILE__,__LINE__);
		
	if($result && mysql_num_rows($result) != 0)
	{	
		$row = mysql_fetch_row($result);
		return $row[0] == null ? 0 : $row[0];
	}
	else
	{
		return 0;
	} 
}
?>
