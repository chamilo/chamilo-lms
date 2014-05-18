<?php
	/**
	 * sysem base config setting
	 * @author Logan Cai (cailongqun@yahoo.com.cn)
	 * @link www.phpletter.com
	 * @since 22/April/2007
	 *
	 */
	define('DATE_TIME_FORMAT', 'd/M/Y H:i:s');
	//Label
		//Top Action
		define('LBL_ACTION_REFRESH', 'Opdater');
		define("LBL_ACTION_DELETE", 'Slet');
		//File Listing
	define('LBL_NAME', 'Navn');
	define('LBL_SIZE', 'Størrelse');
	define('LBL_MODIFIED', 'Ændret');
		//File Information
	define('LBL_FILE_INFO', 'Fil Information:');
	define('LBL_FILE_NAME', 'Navn:');
	define('LBL_FILE_CREATED', 'Uploaded:');
	define("LBL_FILE_MODIFIED", 'Ændret:');
	define("LBL_FILE_SIZE", 'Fil Str:');
	define('LBL_FILE_TYPE', 'Fil Type:');
	define("LBL_FILE_WRITABLE", 'Kan overskrives?');
	define("LBL_FILE_READABLE", 'Kan læses?');
		//Folder Information
	define('LBL_FOLDER_INFO', 'Mappe Information');
	define("LBL_FOLDER_PATH", 'Sti:');
	define("LBL_FOLDER_CREATED", 'Kreeret:');
	define("LBL_FOLDER_MODIFIED", 'Ændret:');
	define('LBL_FOLDER_SUDDIR', 'Undermapper:');
	define("LBL_FOLDER_FIELS", 'Filer:');
	define("LBL_FOLDER_WRITABLE", 'Kan overskrives?');
	define("LBL_FOLDER_READABLE", 'Kan læses?');
		//Preview
	define("LBL_PREVIEW", 'Smugkig');
	//Buttons
	define('LBL_BTN_SELECT', 'Vælg');
	define('LBL_BTN_CANCEL', 'Fortryd');
	define("LBL_BTN_UPLOAD", 'Upload');
	define('LBL_BTN_CREATE', 'Opret');
	define("LBL_BTN_NEW_FOLDER", 'Ny mappe');
	//ERROR MESSAGES
		//deletion
	define('ERR_NOT_FILE_SELECTED', 'Vælg venligst en fil.');
	define('ERR_NOT_DOC_SELECTED', 'Ingen dokumente(r) valgt til slettelse.');
	define('ERR_DELTED_FAILED', 'Kan ikke slette valgte dokumente(r).');
	define('ERR_FOLDER_PATH_NOT_ALLOWED', 'Mappestien er ikke tilladt.');
		//class manager
	define("ERR_FOLDER_NOT_FOUND", 'Kan ikke finde mappen: ');
		//rename
	define('ERR_RENAME_FORMAT', 'Brug kun bogstaver, tal, mellemrum, bindestreg og understregning.');
	define('ERR_RENAME_EXISTS', 'Giv venligst filen et unikt navn.');
	define('ERR_RENAME_FILE_NOT_EXISTS', 'Filen/mappen findes ikke.');
	define('ERR_RENAME_FAILED', 'Kan ikke ændre navnet, prøv igen.');
	define('ERR_RENAME_EMPTY', 'Skriv venligst et navn.');
	define("ERR_NO_CHANGES_MADE", 'Ingen ændringer.');
	define('ERR_RENAME_FILE_TYPE_NOT_PERMITED', 'Filen kan ikke have denne extension.');
		//folder creation
	define('ERR_FOLDER_FORMAT', 'Brug kun bogstaver, tal, mellemrum, bindestreg og understregning.');
	define('ERR_FOLDER_EXISTS', 'Giv venligst mappen et unikt navn.');
	define('ERR_FOLDER_CREATION_FAILED', 'Kan ikke oprette mappen, prøv igen.');
	define('ERR_FOLDER_NAME_EMPTY', 'Skriv venligst et navn.');

		//file upload
	define("ERR_FILE_NAME_FORMAT", 'Brug kun bogstaver, tal, mellemrum, bindestreg og understregning.');
	define('ERR_FILE_NOT_UPLOADED', 'Vælg venligst en fil, der skal uploades.');
	define('ERR_FILE_TYPE_NOT_ALLOWED', 'Denne type fil kan ikke uploades.');
	define('ERR_FILE_MOVE_FAILED', 'Kan ikke flytte filen.');
	define('ERR_FILE_NOT_AVAILABLE', 'Filen findes ikke.');
	define('ERROR_FILE_TOO_BID', 'Filen er for stor. (max: %s)');


	//Tips
	define('TIP_FOLDER_GO_DOWN', 'Enkelt klik for at se denne mappe...');
	define("TIP_DOC_RENAME", 'Dobbelt klik for at ændre navnet...');
	define('TIP_FOLDER_GO_UP', 'Enkelt klik for at se overliggende mappe...');
	define("TIP_SELECT_ALL", 'Vælg alt');
	define("TIP_UNSELECT_ALL", 'Fravælg alt');
	//WARNING
	define('WARNING_DELETE', 'Vil du slette den valgte fil?');
	//Preview
	define('PREVIEW_NOT_PREVIEW', 'Intet smugkig.');
	define('PREVIEW_OPEN_FAILED', 'Kan ikke åbne filen.');
	define('PREVIEW_IMAGE_LOAD_FAILED', 'Kan ikke åbne billedet');

	//Login
	define('LOGIN_PAGE_TITLE', 'Ajax File Manager Login Form');
	define('LOGIN_FORM_TITLE', 'Login Form');
	define('LOGIN_USERNAME', 'Brugernavn:');
	define('LOGIN_PASSWORD', 'Password:');
	define('LOGIN_FAILED', 'Forkert bruger/password.');


?>