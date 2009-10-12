<?php
	/**
	 * language pack
	 * @author Vladimir Maglakelidze (voloda) (maglakel@gmail.com)
	 * @link www.phpletter.com
	 * @since 23/August/2007
	 * @last update 06/July/2007
	 *
	 */
	define('DATE_TIME_FORMAT', 'd/M/Y H:i:s');
	//Label
		//Top Action
	define('LBL_ACTION_REFRESH', 'Obnoviť');
	define("LBL_ACTION_DELETE", 'Odstrániť');
	define('LBL_ACTION_CUT', 'Vystrihnúť');
	define('LBL_ACTION_COPY', 'Kopírovať');
	define('LBL_ACTION_PASTE', 'Vložiť');
	define('LBL_ACTION_CLOSE', 'Zatvoriť');
		//File Listing
	define('LBL_NAME', 'Názov');
	define('LBL_SIZE', 'Veľkosť');
	define('LBL_MODIFIED', 'Dátum');
		//File Information
	define('LBL_FILE_INFO', 'Info súboru:');
	define('LBL_FILE_NAME', 'Názov:');
	define('LBL_FILE_CREATED', 'Vytvorene:');
	define("LBL_FILE_MODIFIED", 'Upravene:');
	define("LBL_FILE_SIZE", 'Veľkosť súbora:');
	define('LBL_FILE_TYPE', 'Typ súbora:');
	define("LBL_FILE_WRITABLE", 'Zapisovateľný?');
	define("LBL_FILE_READABLE", 'Čitateľný?');
		//Folder Information
	define('LBL_FOLDER_INFO', 'Info priečinku');
	define("LBL_FOLDER_PATH", 'Cesta:');
	define("LBL_FOLDER_CREATED", 'Vytvorene:');
	define("LBL_FOLDER_MODIFIED", 'Upravene:');
	define('LBL_FOLDER_SUDDIR', 'Podpriečinky:');
	define("LBL_FOLDER_FIELS", 'Súbory:');
	define("LBL_FOLDER_WRITABLE", 'Zapisovateľný?');
	define("LBL_FOLDER_READABLE", 'Čitateľný?');
		//Preview
	define("LBL_PREVIEW", 'Náhľad');
	define('LBL_CLICK_PREVIEW', 'Pre náhľad súboru kliknite tú.');
	//Buttons
	define('LBL_BTN_SELECT', 'Vybrať');
	define('LBL_BTN_CANCEL', 'Zrušiť');
	define("LBL_BTN_UPLOAD", 'Upload');
	define('LBL_BTN_CREATE', 'Vytvoriť');
	define('LBL_BTN_CLOSE', 'Zatvoriť');
	define("LBL_BTN_NEW_FOLDER", 'Novy Priecinok');
	define('LBL_BTN_EDIT_IMAGE', 'Upraviť');
	//Cut
	define('ERR_NOT_DOC_SELECTED_FOR_CUT', 'Nevybrali ste súbor(y) pre akiciu ´Vystrihnúť´.');
	//Copy
	define('ERR_NOT_DOC_SELECTED_FOR_COPY', 'Nevybrali ste súbor(y) pre akiciu ´Kopírovať´.');
	//Paste
	define('ERR_NOT_DOC_SELECTED_FOR_PASTE', 'Nevybrali ste súbor(y) pre akiciu ´Vložiť´.');
	define('WARNING_CUT_PASTE', 'Ste si isty že chcete presunuť vybrane súbory do aktualného priečinku?');
	define('WARNING_COPY_PASTE', 'Ste si isty že chcete kopírovať vybrane súbory do aktualnáého priečinku?');

	//ERROR MESSAGES
		//deletion
	define('ERR_NOT_FILE_SELECTED', 'Prosím, vyberte si súbor.');
	define('ERR_NOT_DOC_SELECTED', 'Nevybrali ste súbor(y) pre akiciu ´Odstrániť´.');
	define('ERR_DELTED_FAILED', 'Nie je možne odstrániť vybrane súbor(y).');
	define('ERR_FOLDER_PATH_NOT_ALLOWED', 'Cesta priečinka nie je povolená.');
		//class manager
	define("ERR_FOLDER_NOT_FOUND", 'Nie je možne najsť určitý priečinok: ');
		//rename
	define('ERR_RENAME_FORMAT', 'Názov súboru/priečinku môže obsahovať iba písmena(BEZ DIAKRITIKY), číslice, medzery, pomlčky a podčiarkovniky.');
	define('ERR_RENAME_EXISTS', 'Priečinok už existuje, skúste iný unikátny názov.');
	define('ERR_RENAME_FILE_NOT_EXISTS', 'Súbor/priečinok neexistuje.');
	define('ERR_RENAME_FAILED', 'Nie je možne premenovať, skúste znovu.');
	define('ERR_RENAME_EMPTY', 'Vyplnte názov.');
	define("ERR_NO_CHANGES_MADE", 'Neboli vykonane žiadne zmeny.');
	define('ERR_RENAME_FILE_TYPE_NOT_PERMITED', 'Nie je povolene aby sa menila prípona súboru.');
		//folder creation
	define('ERR_FOLDER_FORMAT', 'Názov súboru/priečinku môže obsahovať iba písmena(BEZ DIAKRITIKY), číslice, medzery, pomlčky a podčiarkovniky.');
	define('ERR_FOLDER_EXISTS', 'Priečinok už existuje, skúste iný unikátny názov.');
	define('ERR_FOLDER_CREATION_FAILED', 'Nie je možne vytvoriť priečinok, skúste znovu.');
	define('ERR_FOLDER_NAME_EMPTY', 'Vyplnte názov.');

		//file upload
	define("ERR_FILE_NAME_FORMAT", 'Názov súboru/priečinku môže obsahovať iba písmena(BEZ DIAKRITIKY), číslice, medzery, pomlčky a podčiarkovniky.');
	define('ERR_FILE_NOT_UPLOADED', 'Vyberte si súbor pre upload.');
	define('ERR_FILE_TYPE_NOT_ALLOWED', 'Nemáte pravo nahadzovať súbory s takou príponou.');
	define('ERR_FILE_MOVE_FAILED', 'Nepodarilo sa presunúť súbor.');
	define('ERR_FILE_NOT_AVAILABLE', 'The file is unavailable.');
	define('ERROR_FILE_TOO_BID', 'Súbor je príliš veľký. (max: %s)');

		//file download
	define('ERR_DOWNLOAD_FILE_NOT_FOUND', 'Nevybrali ste súbory pre stiahnutie.');

	//Tips
	define('TIP_FOLDER_GO_DOWN', 'Jeden Klik aby ste sa dostali do priečinku...');
	define("TIP_DOC_RENAME", 'Dvojitý Klik pre úpravu názvu...');
	define('TIP_FOLDER_GO_UP', 'Jeden Klik aby ste sa dostali do rodičovského priečinku...');
	define("TIP_SELECT_ALL", 'Označiť všetko');
	define("TIP_UNSELECT_ALL", 'Zrušiť označene');
	//WARNING
	define('WARNING_DELETE', 'Naozaj chcete odstrániť označene súbory?');
	define('WARNING_NOT_FILE_EDIT', 'Vyberte si súbor pre úpravu, prosím.');
	define('WARNING_IMAGE_EDIT', 'Vyberte si obrázok pre úpravu, prosím.');
	define('WARING_WINDOW_CLOSE', 'Naozaj chcete zatvoriť okno?');
	//Preview
	define('PREVIEW_NOT_PREVIEW', 'Náhľad nie je dostupný.');
	define('PREVIEW_OPEN_FAILED', 'Nie je možne otvoriť súbor.');
	define('PREVIEW_IMAGE_LOAD_FAILED', 'Nie je možne načítať obrázok.');

	//Login
	define('LOGIN_PAGE_TITLE', 'Ajax File Manager Login Formulár');
	define('LOGIN_FORM_TITLE', 'Login Formulár');
	define('LOGIN_USERNAME', 'Užívateľské meno:');
	define('LOGIN_PASSWORD', 'Heslo:');
	define('LOGIN_FAILED', 'Neplatné užívateľské meno/heslo.');


	//88888888888   Below for Image Editor   888888888888888888888
		//Warning
		define('IMG_WARNING_NO_CHANGE_BEFORE_SAVE', "Obrázky neboli upravene.");

		//General
		define('IMG_GEN_IMG_NOT_EXISTS', 'Obrázok neexistuje');
		define('IMG_WARNING_LOST_CHANAGES', 'Všetky neuložený úpravy budu stratene, naozaj chcete pokračovať?');
		define('IMG_WARNING_REST', 'Všetky neuložený úpravy budu stratene, naozaj chcete zrušiť zmeny?');
		define('IMG_WARNING_EMPTY_RESET', 'Zatiaľ obrázok nebol upravený');
		define('IMG_WARING_WIN_CLOSE', 'Naozaj chcete zatvoriť okno?');
		define('IMG_WARNING_UNDO', 'Naozaj chcete vrátiť obrázok do pôvodného stavu?');
		define('IMG_WARING_FLIP_H', 'Naozaj chcete preklopiť obrázok vodorovne?');
		define('IMG_WARING_FLIP_V', 'Naozaj chcete preklopiť obrázok zvisle?');
		define('IMG_INFO', 'Info obrázku');

		//Mode
			define('IMG_MODE_RESIZE', 'Zmena Veľkosti :');
			define('IMG_MODE_CROP', 'Odrezať :');
			define('IMG_MODE_ROTATE', 'Otočiť :');
			define('IMG_MODE_FLIP', 'Preklopiť:');
		//Button

			define('IMG_BTN_ROTATE_LEFT', '90&deg;CCW');
			define('IMG_BTN_ROTATE_RIGHT', '90&deg;CW');
			define('IMG_BTN_FLIP_H', 'Preklopiť Vodorovne');
			define('IMG_BTN_FLIP_V', 'Preklopiť Zvisle');
			define('IMG_BTN_RESET', 'Zrusiť Zmeny');
			define('IMG_BTN_UNDO', 'Krok Späť');
			define('IMG_BTN_SAVE', 'Uložiť');
			define('IMG_BTN_SAVE_AS', 'Uložiť Ako');
			define('IMG_BTN_CANCEL', 'Zrusiť');
			define('IMG_BTN_CLOSE', 'Zatvoriť');
		//Checkbox
			define('IMG_CHECKBOX_CONSTRAINT', 'Obmedziť?');
		//Label
			define('IMG_LBL_WIDTH', 'Šírka:');
			define('IMG_LBL_HEIGHT', 'Výška:');
			define('IMG_LBL_X', 'X:');
			define('IMG_LBL_Y', 'Y:');
			define('IMG_LBL_RATIO', 'Pomer:');
			define('IMG_LBL_ANGLE', 'Uhol:');
			define('IMG_LBL_NEW_NAME', 'Nový názov:');
			define('IMG_LBL_SAVE_AS', 'Uložiť ako formulár');
			define('IMG_LBL_SAVE_TO', 'Uložiť kam:');
			define('IMG_LBL_ROOT_FOLDER', 'Rodičovský priečinok');

		//Editor
		//Save as
		define('IMG_NEW_NAME_COMMENTS', 'Typ súboru sa automaticky priradí.');
		define('IMG_SAVE_AS_ERR_NAME_INVALID', 'Názov súboru môže obsahovať iba písmena(BEZ DIAKRITIKY), číslice, medzery, pomlčky a podčiarkovniky.');
		define('IMG_SAVE_AS_NOT_FOLDER_SELECTED', 'Nevybrali ste cieľový adresár.');
		define('IMG_SAVE_AS_FOLDER_NOT_FOUND', 'Cieľový adresár neexistuje.');
		define('IMG_SAVE_AS_NEW_IMAGE_EXISTS', 'Taky súbor už existuje.');

		//Save
		define('IMG_SAVE_EMPTY_PATH', 'Cesta obrázku je prázdna.');
		define('IMG_SAVE_NOT_EXISTS', 'Obrázok neexistuje.');
		define('IMG_SAVE_PATH_DISALLOWED', 'Nemáte prístup do tohto súboru.');
		define('IMG_SAVE_UNKNOWN_MODE', 'Neočakávaný pracovný režim obrázku');
		define('IMG_SAVE_RESIZE_FAILED', 'Nie je možne zmeniť veľkosť obrázku.');
		define('IMG_SAVE_CROP_FAILED', 'Nie je možne odrezať obrázok.');
		define('IMG_SAVE_FAILED', 'Nie je možne uložiť obrázok.');
		define('IMG_SAVE_BACKUP_FAILED', 'Nie je schopný urobiť zálohu prvotného obrázku.');
		define('IMG_SAVE_ROTATE_FAILED', 'Nie je možne otočiť obrázok.');
		define('IMG_SAVE_FLIP_FAILED', 'Nie je schopný preklopiť obrázok.');
		define('IMG_SAVE_SESSION_IMG_OPEN_FAILED', 'Nie je schopný otvoriť obrazok zo session(relácie).');
		define('IMG_SAVE_IMG_OPEN_FAILED', 'Nie je schopný otvoriť obrazok');

		//UNDO
		define('IMG_UNDO_NO_HISTORY_AVAIALBE', 'História nie je dosptuná pre krok späť.');
		define('IMG_UNDO_COPY_FAILED', 'Nie je schopný obnoviť obrázok.');
		define('IMG_UNDO_DEL_FAILED', 'Nie je schopný odstrániť session obrázku');

	//88888888888   Above for Image Editor   888888888888888888888

	//88888888888   Session   888888888888888888888
		define("SESSION_PERSONAL_DIR_NOT_FOUND", 'Nie je možne nájsť priradený priečinok, ktorý mal byť vytvorený počas priečinka relácie(session).');
		define("SESSION_COUNTER_FILE_CREATE_FAILED", 'Nie je možne otvoriť súbor relácie(session).');
		define('SESSION_COUNTER_FILE_WRITE_FAILED', 'Nie je možne zapísať do súboru relácie(session).');
	//88888888888   Session   888888888888888888888

	//88888888888   Below for Text Editor   888888888888888888888
		define('TXT_FILE_NOT_FOUND', 'Súbor nebol nájdený.');
		define('TXT_EXT_NOT_SELECTED', 'Vyberte si typ súboru, prosím.');
		define('TXT_DEST_FOLDER_NOT_SELECTED', 'Vyberte si cieľový adresár');
		define('TXT_UNKNOWN_REQUEST', 'Neznáma požizdavka.');
		define('TXT_DISALLOWED_EXT', 'Mate pravo editovať/pridavať take typy súboru.');
		define('TXT_FILE_EXIST', 'Taky súbor už existuje.');
		define('TXT_FILE_NOT_EXIST', 'Nebol nájdený.');
		define('TXT_CREATE_FAILED', 'Nebol schopný vytvoriť nový súbor.');
		define('TXT_CONTENT_WRITE_FAILED', 'Nebol schopný zapísať obsah do súboru.');
		define('TXT_FILE_OPEN_FAILED', 'Nebol schopný otvoriť súbor.');
		define('TXT_CONTENT_UPDATE_FAILED', 'Nebol schopný obnoviť obsah súboru.');
		define('TXT_SAVE_AS_ERR_NAME_INVALID', 'Názov súboru môže obsahovať iba písmena(BEZ DIAKRITIKY), číslice, medzery, pomlčky a podčiarkovniky.');
	//88888888888   Above for Text Editor   888888888888888888888

?>