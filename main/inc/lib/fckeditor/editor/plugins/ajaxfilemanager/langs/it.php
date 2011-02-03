<?php
	/**
	 * language pack
	 * @author Marco Zaccari (info [at] marcozaccari [dot] com)
	 * @link www.marcozaccari.com
	 * @since 31/March/2010
	 *
	 */
	define('DATE_TIME_FORMAT', 'd/M/Y H:i:s');
	//Common
	//Menu
	
	define('MENU_SELECT', 'Seleziona');
	define('MENU_DOWNLOAD', 'Download');
	define('MENU_PREVIEW', 'Anteprima');
	define('MENU_RENAME', 'Rinomina');
	define('MENU_EDIT', 'Modifica');
	define('MENU_CUT', 'Taglia');
	define('MENU_COPY', 'Copia');
	define('MENU_DELETE', 'Elimina');
	define('MENU_PLAY', 'Esegui');
	define('MENU_PASTE', 'Incolla');
	
	//Label
		//Top Action
		define('LBL_ACTION_REFRESH', 'Aggiorna');
		define('LBL_ACTION_DELETE', 'Elimina');
		define('LBL_ACTION_CUT', 'Taglia');
		define('LBL_ACTION_COPY', 'Copia');
		define('LBL_ACTION_PASTE', 'Incolla');
		define('LBL_ACTION_CLOSE', 'Chiudi');
		define('LBL_ACTION_SELECT_ALL', 'Seleziona tutto');
		//File Listing
	define('LBL_NAME', 'Nome');
	define('LBL_SIZE', 'Dimensioni');
	define('LBL_MODIFIED', 'Modificato il');
		//File Information
	define('LBL_FILE_INFO', 'Informazioni sul File:');
	define('LBL_FILE_NAME', 'Nome:');	
	define('LBL_FILE_CREATED', 'Creato:');
	define('LBL_FILE_MODIFIED', 'Modificato:');
	define('LBL_FILE_SIZE', 'Dimensioni:');
	define('LBL_FILE_TYPE', 'Tipo:');
	define('LBL_FILE_WRITABLE', 'Modificabile:');
	define('LBL_FILE_READABLE', 'Accessibile:');
		//Folder Information
	define('LBL_FOLDER_INFO', 'Informazioni sulla cartella');
	define('LBL_FOLDER_PATH', 'Cartella:');
	define('LBL_CURRENT_FOLDER_PATH', 'Percorso attuale:');
	define('LBL_FOLDER_CREATED', 'Creata:');
	define('LBL_FOLDER_MODIFIED', 'Modificata:');
	define('LBL_FOLDER_SUDDIR', 'Sottocartelle:');
	define('LBL_FOLDER_FIELS', 'File:');
	define('LBL_FOLDER_WRITABLE', 'Modificabile:');
	define('LBL_FOLDER_READABLE', 'Accessibile:');
	define('LBL_FOLDER_ROOT', 'Cartella principale');
		//Preview
	define('LBL_PREVIEW', 'Anteprima');
	define('LBL_CLICK_PREVIEW', "Clicca qui per l\'anteprima.");
	//Buttons
	define('LBL_BTN_SELECT', 'Seleziona');
	define('LBL_BTN_CANCEL', 'Annulla');
	define('LBL_BTN_UPLOAD', 'Upload');
	define('LBL_BTN_CREATE', 'Crea');
	define('LBL_BTN_CLOSE', 'Chiudi');
	define('LBL_BTN_NEW_FOLDER', 'Nuova cartella');
	define('LBL_BTN_NEW_FILE', 'Nuovo file');
	define('LBL_BTN_EDIT_IMAGE', 'Modifica');
	define('LBL_BTN_VIEW', 'Seleziona vista');
	define('LBL_BTN_VIEW_TEXT', 'Testo');
	define('LBL_BTN_VIEW_DETAILS', 'lista file');
	define('LBL_BTN_VIEW_THUMBNAIL', 'anteprime');
	define('LBL_BTN_VIEW_OPTIONS', 'Visualizza:');
	//pagination
	define('PAGINATION_NEXT', 'Succ.');
	define('PAGINATION_PREVIOUS', 'Prec.');
	define('PAGINATION_LAST', 'Ultima');
	define('PAGINATION_FIRST', 'Prima');
	define('PAGINATION_ITEMS_PER_PAGE', 'Mostra %s elementi per pagina');
	define('PAGINATION_GO_PARENT', 'Livello superiore');
	//System
	define('SYS_DISABLED', 'Permesso Negato: il sistema è disabilitato.');
	
	//Cut
	define('ERR_NOT_DOC_SELECTED_FOR_CUT', 'Nessun documento selezionato per l\'operazione Taglia.');
	//Copy
	define('ERR_NOT_DOC_SELECTED_FOR_COPY', 'Nessun documento selezionato per l\'operazione Copia.');
	//Paste
	define('ERR_NOT_DOC_SELECTED_FOR_PASTE', 'Nessun file selezionato da incollare.');
	define('WARNING_CUT_PASTE', 'Si è sicuri di voler spostare i file selezionati in questa cartella?');
	define('WARNING_COPY_PASTE', 'Si è sicuri di voler copiare i file selezionati in questa cartella?');
	define('ERR_NOT_DEST_FOLDER_SPECIFIED', 'Nessuna cartella di destinazione specificata.');
	define('ERR_DEST_FOLDER_NOT_FOUND', 'Cartella di destinazione non trovata.');
	define('ERR_DEST_FOLDER_NOT_ALLOWED', 'Non si è autorizzati a spostare file in questa cartella.');
	define('ERR_UNABLE_TO_MOVE_TO_SAME_DEST', 'Impossibile spostare il file (%s): il percorso origine e destinazione coincidono.');
	define('ERR_UNABLE_TO_MOVE_NOT_FOUND', 'Impossibile spostare il file (%s): il file originale non esiste più.');
	define('ERR_UNABLE_TO_MOVE_NOT_ALLOWED', "Impossibile spostare il file (%s): accesso negato per il file di origine.");
 
	define('ERR_NOT_FILES_PASTED', 'Non è stato incollato alcun file.');

	//Search
	define('LBL_SEARCH', 'Ricerca');
	define('LBL_SEARCH_NAME', 'Nome completo o parziale:');
	define('LBL_SEARCH_FOLDER', 'Cerca in:');
	define('LBL_SEARCH_QUICK', 'Ricerca rapida');
	define('LBL_SEARCH_MTIME', 'File modificati (da/a):');
	define('LBL_SEARCH_SIZE', 'Dimensioni del file:');
	define('LBL_SEARCH_ADV_OPTIONS', 'Opzioni Avanzate');
	define('LBL_SEARCH_FILE_TYPES', 'Tipo:');
	define('SEARCH_TYPE_EXE', 'Applicazione');
	
	define('SEARCH_TYPE_IMG', 'Immagine');
	define('SEARCH_TYPE_ARCHIVE', 'Archivio');
	define('SEARCH_TYPE_HTML', 'HTML');
	define('SEARCH_TYPE_VIDEO', 'Video');
	define('SEARCH_TYPE_MOVIE', 'Filmato');
	define('SEARCH_TYPE_MUSIC', 'Musica');
	define('SEARCH_TYPE_FLASH', 'Flash');
	define('SEARCH_TYPE_PPT', 'Powerpoint');
	define('SEARCH_TYPE_DOC', 'Documento');
	define('SEARCH_TYPE_WORD', 'MS-Word');
	define('SEARCH_TYPE_PDF', 'PDF');
	define('SEARCH_TYPE_EXCEL', 'MS-Excel');
	define('SEARCH_TYPE_TEXT', 'Testo');
	define('SEARCH_TYPE_UNKNOWN', 'Sconosciuto');
	define('SEARCH_TYPE_XML', 'XML');
	define('SEARCH_ALL_FILE_TYPES', 'Tutti i File');
	define('LBL_SEARCH_RECURSIVELY', 'Ricerca nelle sottocartelle:');
	define('LBL_RECURSIVELY_YES', 'Si');
	define('LBL_RECURSIVELY_NO', 'No');
	define('BTN_SEARCH', 'Cerca');
	//thickbox
	define('THICKBOX_NEXT', 'Successivo&gt;');
	define('THICKBOX_PREVIOUS', '&lt;Precedente');
	define('THICKBOX_CLOSE', 'Chiudi');
	//Calendar
	define('CALENDAR_CLOSE', 'Chiudi');
	define('CALENDAR_CLEAR', 'Pulisci');
	define('CALENDAR_PREVIOUS', '&lt;Precedente');
	define('CALENDAR_NEXT', 'Successivo&gt;');
	define('CALENDAR_CURRENT', 'Oggi');
	define('CALENDAR_MON', 'Lun');
	define('CALENDAR_TUE', 'Mar');
	define('CALENDAR_WED', 'Mer');
	define('CALENDAR_THU', 'Gio');
	define('CALENDAR_FRI', 'Ven');
	define('CALENDAR_SAT', 'Sab');
	define('CALENDAR_SUN', 'Dom');
	define('CALENDAR_JAN', 'Gen');
	define('CALENDAR_FEB', 'Feb');
	define('CALENDAR_MAR', 'Mar');
	define('CALENDAR_APR', 'Apr');
	define('CALENDAR_MAY', 'Mag');
	define('CALENDAR_JUN', 'Giu');
	define('CALENDAR_JUL', 'Lug');
	define('CALENDAR_AUG', 'Ago');
	define('CALENDAR_SEP', 'Set');
	define('CALENDAR_OCT', 'Ott');
	define('CALENDAR_NOV', 'Nov');
	define('CALENDAR_DEC', 'Dic');
	//ERROR MESSAGES
		//deletion
	define('ERR_NOT_FILE_SELECTED', 'Selezionare un File.');
	define('ERR_NOT_DOC_SELECTED', 'Nessun documento selezionato per la cancellazione.');
	define('ERR_DELTED_FAILED', 'Impossibile cancellare i file selezionati.');
	define('ERR_FOLDER_PATH_NOT_ALLOWED', 'Il percorso non è disponibile.');
		//class manager
	define('ERR_FOLDER_NOT_FOUND', 'Impossibile localizzare la cartella: ');
		//rename
	define('ERR_RENAME_FORMAT', 'Scegliere un nome che contenga solo lettere, numeri, spazi, trattini e underscore.');
	define('ERR_RENAME_EXISTS', 'Digitare un nome univoco.');
	define('ERR_RENAME_FILE_NOT_EXISTS', 'Il file/cartella non esiste.');
	define('ERR_RENAME_FAILED', 'Impossibile rinominare, riprovare.');
	define('ERR_RENAME_EMPTY', 'Digitare un nome.');
	define('ERR_NO_CHANGES_MADE', 'Non è stato eseguito alcun cambiamento.');
	define('ERR_RENAME_FILE_TYPE_NOT_PERMITED', "Non si ha il permesso di cambiare l'estensione.");
		//folder creation
	define('ERR_FOLDER_FORMAT', 'Scegliere un nome che contenga solo lettere, numeri, spazi, trattini e underscore.');
	define('ERR_FOLDER_EXISTS', 'Digitare un nome univoco.');
	define('ERR_FOLDER_CREATION_FAILED', 'Impossibile rinominare la cartella, riprovare.');
	define('ERR_FOLDER_NAME_EMPTY', 'Digitare un nome.');
	define('FOLDER_FORM_TITLE', 'Nuova cartella');
	define('FOLDER_LBL_TITLE', 'Nome cartella:');
	define('FOLDER_LBL_CREATE', 'Crea');
	//New File
	define('NEW_FILE_FORM_TITLE', 'Nuovo File');
	define('NEW_FILE_LBL_TITLE', 'Nome:');
	define('NEW_FILE_CREATE', 'Crea');
		//file upload
	define('ERR_FILE_NAME_FORMAT', 'Scegliere un nome che contenga solo lettere, numeri, spazi, trattini e underscore.');
	define('ERR_FILE_NOT_UPLOADED', "Nessun file selezionato per l'upload.");
	define('ERR_FILE_TYPE_NOT_ALLOWED', "Non si ha il permesso di eseguire l\'upload di file con questa estensione.");
	define('ERR_FILE_MOVE_FAILED', 'Impossibile spostare il file.');
	define('ERR_FILE_NOT_AVAILABLE', 'Il file non è disponibile.');
	define('ERROR_FILE_TOO_BID', 'File troppo grande. (max: %s)');
	define('FILE_FORM_TITLE', 'Carica nuovo file');
	define('FILE_LABEL_SELECT', 'Nome del file');
	define('FILE_LBL_MORE', 'Aggiungi file');
	define('FILE_CANCEL_UPLOAD', 'Cancella');
	define('FILE_LBL_UPLOAD', 'Carica');
	//file download
	define('ERR_DOWNLOAD_FILE_NOT_FOUND', 'Nessun file selezionato per il download.');
	//Rename
	define('RENAME_FORM_TITLE', 'Rinomina file');
	define('RENAME_NEW_NAME', 'Nuovo nome');
	define('RENAME_LBL_RENAME', 'Rinomina');

	//Tips
	define('TIP_FOLDER_GO_DOWN', 'Un solo click per arrivare a questa cartella...');
	define('TIP_DOC_RENAME', 'Doppio click per modificare...');
	define('TIP_FOLDER_GO_UP', 'Click singolo per salire di un livello...');
	define('TIP_SELECT_ALL', 'Seleziona tutti');
	define('TIP_UNSELECT_ALL', 'Deseleziona tutti');
	//WARNING
	define('WARNING_DELETE', 'Si è sicuri di voler eliminare i file selezionati?');
	define('WARNING_IMAGE_EDIT', 'Selezionare un\'immagine da modificare.');
	define('WARNING_NOT_FILE_EDIT', 'Selezionare un file da modificare.');
	define('WARING_WINDOW_CLOSE', 'Si è sicuri di voler chiudere la finestra?');
	//Preview
	define('PREVIEW_NOT_PREVIEW', 'Nessuna anteprima disponibile.');
	define('PREVIEW_OPEN_FAILED', 'Impossibile aprire il file.');
	define('PREVIEW_IMAGE_LOAD_FAILED', "Impossibile caricare l'immagine");

	//Login
	define('LOGIN_PAGE_TITLE', 'File Manager - Login');
	define('LOGIN_FORM_TITLE', 'Login');
	define('LOGIN_USERNAME', 'Username:');
	define('LOGIN_PASSWORD', 'Password:');
	define('LOGIN_FAILED', 'Invalida username/password.');
	
	
	//88888888888   Below for Image Editor   888888888888888888888
		//Warning 
		define('IMG_WARNING_NO_CHANGE_BEFORE_SAVE', 'Non hai fatto nessun cambiamento alle immagini.');
		
		//General
		define('IMG_GEN_IMG_NOT_EXISTS', "L'immagine non esiste.");
		define('IMG_WARNING_LOST_CHANAGES', "Tutte le modifiche effettuate all'immagine non verranno salvate, si è sicuri di voler continare?");
		define('IMG_WARNING_REST', "Tutte le modifiche effettuate all'immagine non verranno salvate, si è sicuri di voler resettare?");
		define('IMG_WARNING_EMPTY_RESET', "Nessun cambiamento è stato fatto finora all'immagine");
		define('IMG_WARING_WIN_CLOSE', 'Si è sicuri di voler chiudere la finestra?');
		define('IMG_WARNING_UNDO', 'Si è sicuri di voler tornare allo stato precedente?');
		define('IMG_WARING_FLIP_H', 'Si è sicuri di voler riflettere orizzontalmente?');
		define('IMG_WARING_FLIP_V', 'Si è sicuri di voler riflettere verticalmente');
		define('IMG_INFO', "Informazioni sull'immagine");
		
		//Mode
			define('IMG_MODE_RESIZE', 'Ridimensiona:');
			define('IMG_MODE_CROP', 'Ritaglia:');
			define('IMG_MODE_ROTATE', 'Ruota:');
			define('IMG_MODE_FLIP', 'Rifletti:');		
		//Button
		
			define('IMG_BTN_ROTATE_LEFT', '90&deg; Orari');
			define('IMG_BTN_ROTATE_RIGHT', '90&deg; Antiorari');
			define('IMG_BTN_FLIP_H', 'Rifletti orizzontalmente');
			define('IMG_BTN_FLIP_V', 'Rifletti verticalmente');
			define('IMG_BTN_RESET', 'Ripristina');
			define('IMG_BTN_UNDO', 'Annulla modifiche');
			define('IMG_BTN_SAVE', 'Salva');
			define('IMG_BTN_CLOSE', 'Chiudi');
			define('IMG_BTN_SAVE_AS', 'Salva come');
			define('IMG_BTN_CANCEL', 'Annulla');
		//Checkbox
			define('IMG_CHECKBOX_CONSTRAINT', 'Mantieni proporzioni');
		//Label
			define('IMG_LBL_WIDTH', 'Larghezza:');
			define('IMG_LBL_HEIGHT', 'Altezza:');
			define('IMG_LBL_X', 'X:');
			define('IMG_LBL_Y', 'Y:');
			define('IMG_LBL_RATIO', 'Rapporto:');
			define('IMG_LBL_ANGLE', 'Angolo:');
			define('IMG_LBL_NEW_NAME', 'Nuovo nome:');
			define('IMG_LBL_SAVE_AS', 'Salva');
			define('IMG_LBL_SAVE_TO', 'Salva in:');
			define('IMG_LBL_ROOT_FOLDER', 'Cartella principale');
		//Editor
		//Save as 
		define('IMG_NEW_NAME_COMMENTS', "Evitare di digitare l'estensione del file.");
		define('IMG_SAVE_AS_ERR_NAME_INVALID', 'Scegliere un nome che contenga solo lettere, numeri, spazi, trattini e underscore.');
		define('IMG_SAVE_AS_NOT_FOLDER_SELECTED', 'Nessuna cartella di destinazione selezionata.');	
		define('IMG_SAVE_AS_FOLDER_NOT_FOUND', 'La cartella di destinazione non esiste.');
		define('IMG_SAVE_AS_NEW_IMAGE_EXISTS', 'Esiste già un immagine con lo stesso nome.');

		//Save
		define('IMG_SAVE_EMPTY_PATH', 'Percorso immagine mancante.');
		define('IMG_SAVE_NOT_EXISTS', "L'immagine non esiste.");
		define('IMG_SAVE_PATH_DISALLOWED', 'Non si possiede i permessi per accedere al file.');
		define('IMG_SAVE_UNKNOWN_MODE', 'Modalità sconosciuta.');
		define('IMG_SAVE_RESIZE_FAILED', 'Ridimensiona: operazione fallita.');
		define('IMG_SAVE_CROP_FAILED', 'Ritaglia: operazione fallita.');
		define('IMG_SAVE_FAILED', "Impossibile salvare l'immagine.");
		define('IMG_SAVE_BACKUP_FAILED', "Impossibile salvare l'immagine originale.");
		define('IMG_SAVE_ROTATE_FAILED', 'Rotazione: operazione fallita.');
		define('IMG_SAVE_FLIP_FAILED', 'Riflessione: operazione fallita.');
		define('IMG_SAVE_SESSION_IMG_OPEN_FAILED', "Impossibile aprire l'immagine temporanea");
		define('IMG_SAVE_IMG_OPEN_FAILED', "Impossibile aprire l'immagine");
		
		
		//UNDO
		define('IMG_UNDO_NO_HISTORY_AVAIALBE', 'Storico annullamento modifiche non diponibile.');
		define('IMG_UNDO_COPY_FAILED', "Impossibile ripristinare l'immagine.");
		define('IMG_UNDO_DEL_FAILED', "Impossibile cancellare l'immagine temporanea");
	
	//88888888888   Above for Image Editor   888888888888888888888
	
	//88888888888   Session   888888888888888888888
		define('SESSION_PERSONAL_DIR_NOT_FOUND', 'Impossibile trovare la cartella temporanea');
		define('SESSION_COUNTER_FILE_CREATE_FAILED', 'Impossibile aprire il file contatore di sessione.');
		define('SESSION_COUNTER_FILE_WRITE_FAILED', 'Impossibile scrivere il file contatore di sessione.');
	//88888888888   Session   888888888888888888888
	
	//88888888888   Below for Text Editor   888888888888888888888
		define('TXT_FILE_NOT_FOUND', 'File non trovato.');
		define('TXT_EXT_NOT_SELECTED', 'Selezionare un estensione per il file.');
		define('TXT_DEST_FOLDER_NOT_SELECTED', 'Selezionare una cartella di destinazione.');
		define('TXT_UNKNOWN_REQUEST', 'Richiesta sconosciuta.');
		define('TXT_DISALLOWED_EXT', 'Non si è autorizzati a modificare/creare questo tipo di file.');
		define('TXT_FILE_EXIST', 'Il file esiste già.');
		define('TXT_FILE_NOT_EXIST', 'Il file non esiste.');
		define('TXT_CREATE_FAILED', 'Impossibile creare un nuovo file.');
		define('TXT_CONTENT_WRITE_FAILED', 'Impossibile salvare il file.');
		define('TXT_FILE_OPEN_FAILED', 'Impossibile aprire il file.');
		define('TXT_CONTENT_UPDATE_FAILED', 'Impossibile aggiornare il contentuto.');
		define('TXT_SAVE_AS_ERR_NAME_INVALID', 'Scegliere un nome che contenga solo lettere, numeri, spazi, trattini e underscore.');
	//88888888888   Above for Text Editor   888888888888888888888
	
	
?>