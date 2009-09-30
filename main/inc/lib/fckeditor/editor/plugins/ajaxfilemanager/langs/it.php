<?
	/**
	 * sysem base config setting
	 * @author Antonello Alonzi (info@smsoft.it)
	 * @link www.phpletter.com
	 * @since 31/May/2007
	 *
	 */
	define('DATE_TIME_FORMAT', 'd/M/Y H:i:s');
	//Label
		//Top Action
		define('LBL_ACTION_REFRESH', 'Aggiorna');
		define("LBL_ACTION_DELETE", 'Elimina');
		//File Listing
	define('LBL_NAME', 'Nome');
	define('LBL_SIZE', 'Dim.');
	define('LBL_MODIFIED', 'Modificato');
		//File Information
	define('LBL_FILE_INFO', 'Info File:');
	define('LBL_FILE_NAME', 'Nome:');
	define('LBL_FILE_CREATED', 'Creato:');
	define("LBL_FILE_MODIFIED", 'Modificato:');
	define("LBL_FILE_SIZE", 'Dimensione:');
	define('LBL_FILE_TYPE', 'Tipo:');
	define("LBL_FILE_WRITABLE", 'Modificabile?');
	define("LBL_FILE_READABLE", 'Leggibile?');
		//Folder Information
	define('LBL_FOLDER_INFO', 'Info Cartella');
	define("LBL_FOLDER_PATH", 'Percorso:');
	define("LBL_FOLDER_CREATED", 'Creata:');
	define("LBL_FOLDER_MODIFIED", 'Modificata:');
	define('LBL_FOLDER_SUDDIR', 'SottoCartelle:');
	define("LBL_FOLDER_FIELS", 'Files:');
	define("LBL_FOLDER_WRITABLE", 'Modificabile?');
	define("LBL_FOLDER_READABLE", 'Leggibile?');
		//Preview
	define("LBL_PREVIEW", 'Anteprima');
	//Buttons
	define('LBL_BTN_SELECT', 'Seleziona');
	define('LBL_BTN_CANCEL', 'Annulla');
	define("LBL_BTN_UPLOAD", 'Upload');
	define('LBL_BTN_CREATE', 'Crea');
	define("LBL_BTN_NEW_FOLDER", 'Nuova Cartella');
	//ERROR MESSAGES
		//deletion
	define('ERR_NOT_FILE_SELECTED', 'Per favore seleziona un file.');
	define('ERR_NOT_DOC_SELECTED', 'Nessun documento(i) selezionato per la cancellazione.');
	define('ERR_DELTED_FAILED', 'Impossibile eliminare il documento(i).');
	define('ERR_FOLDER_PATH_NOT_ALLOWED', 'Il percorso non e\' accessibile.');
		//class manager
	define("ERR_FOLDER_NOT_FOUND", 'Impossibile trovare la cartella specificata: ');
		//rename
	define('ERR_RENAME_FORMAT', 'Per favore inserisci un nome che contiene solo lettere, cifre, spazi, underscore e trattini.');
	define('ERR_RENAME_EXISTS', 'Per favore usa un nome univoco in questa cartella.');
	define('ERR_RENAME_FILE_NOT_EXISTS', 'Il File/Cartella non esiste.');
	define('ERR_RENAME_FAILED', 'Impossibile rinominare, prova ancora.');
	define('ERR_RENAME_EMPTY', 'Per favore inserisci un nome.');
	define("ERR_NO_CHANGES_MADE", 'Nessun cambiamento effettuato.');
	define('ERR_RENAME_FILE_TYPE_NOT_PERMITED', 'Non e\' possibile modificare l\'estensione del file.');
		//folder creation
	define('ERR_FOLDER_FORMAT', 'Per favore inserisci un nome che contiene solo lettere, cifre, spazi, underscore e trattini.');
	define('ERR_FOLDER_EXISTS', 'Per favore usa un nome univoco in questa cartella.');
	define('ERR_FOLDER_CREATION_FAILED', 'Impossibile creare la cartella, prova ancora.');
	define('ERR_FOLDER_NAME_EMPTY', 'Per favore indica un nome.');

		//file upload
	define("ERR_FILE_NAME_FORMAT", 'Per favore inserisci un nome che contiene solo lettere, cifre, spazi, underscore e trattini.');
	define('ERR_FILE_NOT_UPLOADED', 'Nessun file selezionato per il caricamento.');
	define('ERR_FILE_TYPE_NOT_ALLOWED', 'Non sei abilitato a caricare questo tipo di file.');
	define('ERR_FILE_MOVE_FAILED', 'Errore spostamento file.');
	define('ERR_FILE_NOT_AVAILABLE', 'File non disponivile.');
	define('ERROR_FILE_TOO_BID', 'File troppo grande. (max: %s)');


	//Tips
	define('TIP_FOLDER_GO_DOWN', 'Click singolo per accedere alla Cartella...');
	define("TIP_DOC_RENAME", 'Doppio Click per modificare...');
	define('TIP_FOLDER_GO_UP', 'Click singolo per tornare su...');
	define("TIP_SELECT_ALL", 'Seleziona tutto');
	define("TIP_UNSELECT_ALL", 'Deseleziona tutto');
	//WARNING
	define('WARNING_DELETE', 'Sicuro di voler eliminare il file?');
	//Preview
	define('PREVIEW_NOT_PREVIEW', 'Anteprima non disponibile.');
	define('PREVIEW_OPEN_FAILED', 'Impossibile aprire il file.');
	define('PREVIEW_IMAGE_LOAD_FAILED', 'Impossibile caricare il file.');

	//Login
	define('LOGIN_PAGE_TITLE', 'Ajax File Manager Login');
	define('LOGIN_FORM_TITLE', 'Login');
	define('LOGIN_USERNAME', 'Username:');
	define('LOGIN_PASSWORD', 'Password:');
	define('LOGIN_FAILED', 'Username/password non corretti.');


?>
