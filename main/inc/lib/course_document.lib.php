<?php
/* For licensing terms, see /license.txt */
/**
 * @package chamilo.include
 */
/**
 * Code
 */
function getlist ($directory) {
	//global $delim, $win;
	if ($d = @opendir($directory)) {

		while (($filename = @readdir($d)) !== false) {

			$path = $directory . $filename;

			if ($filename != '.' && $filename != '..' && $filename != '.svn')
			{
				$file = array(
					"lastedit_date" =>date("Y-m-d H:i:s",9876),//date ("Y-m-d H:i:s", filemtime($path)),
					"visibility" => 1,
					"path" => $path,
					"title" => basename($path),
					"filetype" => filetype($path),
					"size" => filesize ($path)
				);

				$files[] = $file;
			}
		}

		return $files;
	}
	else
	{
		return false;
	}
}

function check_and_create_resource_directory($repository_path, $resource_directory, $resource_directory_name)
{
	global $permissions_for_new_directories;

	$resource_directory_full_path = substr($repository_path, 0, strlen($repository_path) - 1) . $resource_directory . '/';

	if (!is_dir($resource_directory_full_path))
	{
		if (@mkdir($resource_directory_full_path, $permissions_for_new_directories))
		{
			// While we are in a course: Registering the newly created folder in the course's database.
			if (api_is_in_course())
			{
				global $_course, $_user;
				global $group_properties, $to_group_id;
				$group_directory = !empty($group_properties['directory']) ? $group_properties['directory'] : '';

				$doc_id = add_document($_course, $group_directory.$resource_directory, 'folder', 0, $resource_directory_name);
				api_item_property_update($_course, TOOL_DOCUMENT, $doc_id, 'FolderCreated', $_user['user_id'], $to_group_id);
			}
			return true;
		}
		return false;
	}
	return true;
}

?>
