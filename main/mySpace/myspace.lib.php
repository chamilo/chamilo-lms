<?php

/* For licensing terms, see /dokeos_license.txt */

/**
 * This function serves exporting data in CSV format.
 * @param array $header			The header labels.
 * @param array $data			The data array.
 * @param string $file_name		The name of the file which contains exported data.
 * @return mixed				Returns a message (string) if an error occurred.
 */
function export_csv($header, $data, $file_name = 'export.csv') {

	$archive_path = api_get_path(SYS_ARCHIVE_PATH);
	$archive_url = api_get_path(WEB_CODE_PATH).'course_info/download.php?archive=';

	if (!$open = fopen($archive_path.$file_name, 'w+')) {
		$message = get_lang('noOpen');
	} else {
		$info = '';

		foreach ($header as $value) {
			$info .= $value.';';
		}
		$info .= "\r\n";

		foreach ($data as $row) {
			foreach ($row as $value) {
				$info .= $value.';';
			}
			$info .= "\r\n";
		}

		fwrite($open, $info);
		fclose($open);
		$perm = api_get_setting('permissions_for_new_files');
		$perm = octdec(!empty($perm) ? $perm : '0660');
		@chmod($file_name, $perm);

		header("Location:".$archive_url.$file_name);
	}
	return $message;
}
