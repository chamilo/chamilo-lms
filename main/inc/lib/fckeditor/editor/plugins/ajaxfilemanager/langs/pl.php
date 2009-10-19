<?php

	/**
	 * polish language pack
	 * @polish translator Michał Zygor - winami.com (michal [at] winami [dot] com)
	 * This is extend translate of Tomasz Regdos - regdos.com (tomek [at] regdos [dot] com)
	 * @since 2008-08-14
	 *
	 */

	/**
	 * language pack
	 * @author Logan Cai (cailongqun [at] yahoo [dot] com [dot] cn)
	 * @link www.phpletter.com
	 * @since 22/April/2007
	 *
	 */
	define('DATE_TIME_FORMAT', 'Y-M-d H:i:s');
	//Common
	//Menu




	define('MENU_SELECT', 'Zaznacz');
	define('MENU_DOWNLOAD', 'Pobierz');
	define('MENU_PREVIEW', 'Podgląd');
	define('MENU_RENAME', 'Zmień nazwę');
	define('MENU_EDIT', 'Edycja');
	define('MENU_CUT', 'Wytnij');
	define('MENU_COPY', 'Kopiuj');
	define('MENU_DELETE', 'Usuń');
	define('MENU_PLAY', 'Odtwórz');
	define('MENU_PASTE', 'Wklej');

	//Label
		//Top Action
		define('LBL_ACTION_REFRESH', 'Odśwież');
		define("LBL_ACTION_DELETE", 'Usuń');
		define('LBL_ACTION_CUT', 'Wytnij');
		define('LBL_ACTION_COPY', 'Kopiuj');
		define('LBL_ACTION_PASTE', 'Wklej');
		define('LBL_ACTION_CLOSE', 'Zamknij');
		define('LBL_ACTION_SELECT_ALL', 'Zaznacz wszystko');
		//File Listing
	define('LBL_NAME', 'Nazwa');
	define('LBL_SIZE', 'Rozmiar');
	define('LBL_MODIFIED', 'Ostatnia zmiana');
		//File Information
	define('LBL_FILE_INFO', 'Informacje o pliku:');
	define('LBL_FILE_NAME', 'Nazwa:');
	define('LBL_FILE_CREATED', 'Utworzony:');
	define("LBL_FILE_MODIFIED", 'Zmieniony:');
	define("LBL_FILE_SIZE", 'Rozmiar pliku:');
	define('LBL_FILE_TYPE', 'Typ pliku:');
	define("LBL_FILE_WRITABLE", 'Zapis?');
	define("LBL_FILE_READABLE", 'Odczyt?');
		//Folder Information
	define('LBL_FOLDER_INFO', 'Informacje o katalogu');
	define("LBL_FOLDER_PATH", 'Ścieżka:');
	define('LBL_CURRENT_FOLDER_PATH', 'Aktualna ścieżka:');
	define("LBL_FOLDER_CREATED", 'Utworzony:');
	define("LBL_FOLDER_MODIFIED", 'Zmieniony:');
	define('LBL_FOLDER_SUDDIR', 'Podkatalogi:');
	define("LBL_FOLDER_FIELS", 'Pliki:');
	define("LBL_FOLDER_WRITABLE", 'Zapis?');
	define("LBL_FOLDER_READABLE", 'Odczyt?');
	define('LBL_FOLDER_ROOT', 'Folder główny');
		//Preview
	define("LBL_PREVIEW", 'Podgląd');
	define('LBL_CLICK_PREVIEW', 'Kliknij żeby zobaczyć podgląd.');
	//Buttons
	define('LBL_BTN_SELECT', 'Wybierz');
	define('LBL_BTN_CANCEL', 'Anuluj');
	define("LBL_BTN_UPLOAD", 'Wgraj');
	define('LBL_BTN_CREATE', 'Utwórz');
	define('LBL_BTN_CLOSE', 'Zamknij');
	define("LBL_BTN_NEW_FOLDER", 'Nowy katalog');
	define('LBL_BTN_NEW_FILE', 'Nowy plik');
	define('LBL_BTN_EDIT_IMAGE', 'Zmień');
	define('LBL_BTN_VIEW', 'Zaznacz widoczne');
	define('LBL_BTN_VIEW_TEXT', 'Tekst');
	define('LBL_BTN_VIEW_DETAILS', 'Szczegóły');
	define('LBL_BTN_VIEW_THUMBNAIL', 'Miniaturki');
	define('LBL_BTN_VIEW_OPTIONS', 'Podgląd w:');
	//pagination
	define('PAGINATION_NEXT', 'Dalej');
	define('PAGINATION_PREVIOUS', 'Wstecz');
	define('PAGINATION_LAST', 'Koniec');
	define('PAGINATION_FIRST', 'Początek');
	define('PAGINATION_ITEMS_PER_PAGE', 'Wyświetl %s pozycji na stronie');
	define('PAGINATION_GO_PARENT', 'Katalog nadrzędny');
	//System
	define('SYS_DISABLED', 'Brak dostępu: System nieaktywny');

	//Cut
	define('ERR_NOT_DOC_SELECTED_FOR_CUT', 'Nie wybrano żadnego dokumentu do wycięcia.');
	//Copy
	define('ERR_NOT_DOC_SELECTED_FOR_COPY', 'Nie wybrano żadnego dokumentu do skopiowania.');
	//Paste
	define('ERR_NOT_DOC_SELECTED_FOR_PASTE', 'Nie wybrano żadnego dokumentu do wklejenia.');
	define('WARNING_CUT_PASTE', 'Czy na pewno chcesz przenieść zaznaczone dokumenty do aktualnego katalogu?');
	define('WARNING_COPY_PASTE', 'Czy na pewno chcesz skopiować zaznaczone dokumenty do aktualnego katalogu?');
	define('ERR_NOT_DEST_FOLDER_SPECIFIED', 'Nie wybrano katalogu.');
	define('ERR_DEST_FOLDER_NOT_FOUND', 'Katalog nie istnieje.');
	define('ERR_DEST_FOLDER_NOT_ALLOWED', 'Nie możesz przenieść plików do tego katalogu.');
	define('ERR_UNABLE_TO_MOVE_TO_SAME_DEST', 'Błąd przeniesienia pliku (%s): Ścieżka do zapisu i orginalna są identyczne.');
	define('ERR_UNABLE_TO_MOVE_NOT_FOUND', 'Błąd przeniesienia pliku (%s): Orginał pliku nie istnieje.');
	define('ERR_UNABLE_TO_MOVE_NOT_ALLOWED', 'Błąd przeniesienia pliku (%s): Dostęp do pliku zabroniony.');

	define('ERR_NOT_FILES_PASTED', 'Żadne pliki nie zostały skopiowane.');

	//Search
	define('LBL_SEARCH', 'Wyszukiwanie');
	define('LBL_SEARCH_NAME', 'Pełna lub częściowa nazwa pliku:');
	define('LBL_SEARCH_FOLDER', 'Szukaj w:');
	define('LBL_SEARCH_QUICK', 'Szybkie wyszukiwanie');
	define('LBL_SEARCH_MTIME', 'Data zmiany pliku(zakres):');
	define('LBL_SEARCH_SIZE', 'Rozmiar pliku:');
	define('LBL_SEARCH_ADV_OPTIONS', 'Opcje zaawansowane');
	define('LBL_SEARCH_FILE_TYPES', 'Rozszerzenie:');
	define('SEARCH_TYPE_EXE', 'Plik wykonywalny exe');

	define('SEARCH_TYPE_IMG', 'Obraz');
	define('SEARCH_TYPE_ARCHIVE', 'Archiwum');
	define('SEARCH_TYPE_HTML', 'HTML');
	define('SEARCH_TYPE_VIDEO', 'Wideo');
	define('SEARCH_TYPE_MOVIE', 'Film');
	define('SEARCH_TYPE_MUSIC', 'Muzyka');
	define('SEARCH_TYPE_FLASH', 'Flash');
	define('SEARCH_TYPE_PPT', 'PowerPoint');
	define('SEARCH_TYPE_DOC', 'Dokument');
	define('SEARCH_TYPE_WORD', 'Word');
	define('SEARCH_TYPE_PDF', 'PDF');
	define('SEARCH_TYPE_EXCEL', 'Excel');
	define('SEARCH_TYPE_TEXT', 'Tekst');
	define('SEARCH_TYPE_UNKNOWN', 'Nieznany');
	define('SEARCH_TYPE_XML', 'XML');
	define('SEARCH_ALL_FILE_TYPES', 'Wszystkie pliki');
	define('LBL_SEARCH_RECURSIVELY', 'Szukaj w podkatalogach:');
	define('LBL_RECURSIVELY_YES', 'Tak');
	define('LBL_RECURSIVELY_NO', 'Nie');
	define('BTN_SEARCH', 'Znajdź teraz');
	//thickbox
	define('THICKBOX_NEXT', 'Dalej&gt;');
	define('THICKBOX_PREVIOUS', '&lt;Wstecz');
	define('THICKBOX_CLOSE', 'Zamknij');
	//Calendar
	define('CALENDAR_CLOSE', 'Zamknij');
	define('CALENDAR_CLEAR', 'Wyczyść');
	define('CALENDAR_PREVIOUS', '&lt;Wstecz');
	define('CALENDAR_NEXT', 'Dalej&gt;');
	define('CALENDAR_CURRENT', 'Dzisiaj');
	define('CALENDAR_MON', 'pn');
	define('CALENDAR_TUE', 'wt');
	define('CALENDAR_WED', 'śr');
	define('CALENDAR_THU', 'cz');
	define('CALENDAR_FRI', 'pt');
	define('CALENDAR_SAT', 'so');
	define('CALENDAR_SUN', 'nd');
	define('CALENDAR_JAN', 'sty');
	define('CALENDAR_FEB', 'lut');
	define('CALENDAR_MAR', 'mar');
	define('CALENDAR_APR', 'kwi');
	define('CALENDAR_MAY', 'maj');
	define('CALENDAR_JUN', 'cze');
	define('CALENDAR_JUL', 'lip');
	define('CALENDAR_AUG', 'sie');
	define('CALENDAR_SEP', 'wrz');
	define('CALENDAR_OCT', 'paź');
	define('CALENDAR_NOV', 'lis');
	define('CALENDAR_DEC', 'gru');
	//ERROR MESSAGES
		//deletion
	define('ERR_NOT_FILE_SELECTED', 'Proszę zaznaczyć plik.');
	define('ERR_NOT_DOC_SELECTED', 'Nie wybrano żadnego dokumentu do usunięcia.');
	define('ERR_DELTED_FAILED', 'Nie udało się usunąć dokumentu(ów).');
	define('ERR_FOLDER_PATH_NOT_ALLOWED', 'Katalog nie jest dostępny.');
		//class manager
	define("ERR_FOLDER_NOT_FOUND", 'Nie znaleziono katalogu: ');
		//rename
	define('ERR_RENAME_FORMAT', 'Nazwa może zawierać tylko znaki alfabetu, liczby, spację, myślnik i podkreślenie.');
	define('ERR_RENAME_EXISTS', 'Nazwa musi być unikalna w danym katalogu.');
	define('ERR_RENAME_FILE_NOT_EXISTS', 'Katalog lub plik nie istnieje.');
	define('ERR_RENAME_FAILED', 'Nie można dokonać zmiany nazwy, spróbuj ponownie.');
	define('ERR_RENAME_EMPTY', 'Proszę podać nazwę.');
	define("ERR_NO_CHANGES_MADE", 'Nie można dokonać zmian.');
	define('ERR_RENAME_FILE_TYPE_NOT_PERMITED', 'Nie masz wystarczających praw żeby zmienić plik o takim rozszerzeniu.');
		//folder creation
	define('ERR_FOLDER_FORMAT', 'Nazwa katalogu może zawierać tylko znaki alfabetu, liczby, spację, myślnik i podkreślenie.');
	define('ERR_FOLDER_EXISTS', 'Nazwa katalogu musi być unikalna w danym katalogu.');
	define('ERR_FOLDER_CREATION_FAILED', 'Nie można dokonać zmiany nazwy katalogu, spróbuj ponownie');
	define('ERR_FOLDER_NAME_EMPTY', 'Proszę podać nazwę.');
	define('FOLDER_FORM_TITLE', 'Nowy katalog');
	define('FOLDER_LBL_TITLE', 'Nazwa:');
	define('FOLDER_LBL_CREATE', 'Twórz katalog');
	//New File
	define('NEW_FILE_FORM_TITLE', 'Nowy plik');
	define('NEW_FILE_LBL_TITLE', 'Nazwa pliku:');
	define('NEW_FILE_CREATE', 'Twórz plik');
		//file upload
	define("ERR_FILE_NAME_FORMAT", 'Nazwa może zawierać tylko znaki alfabetu, liczby, spację, myślnik i podkreślenie.');
	define('ERR_FILE_NOT_UPLOADED', 'Nie wybrano pliku do wgrania.');
	define('ERR_FILE_TYPE_NOT_ALLOWED', 'Nie można wgrywać plików tego typu.');
	define('ERR_FILE_MOVE_FAILED', 'Błąd podczas przenoszenia pliku.');
	define('ERR_FILE_NOT_AVAILABLE', 'Plik jest niedostępny.');
	define('ERROR_FILE_TOO_BID', 'Plik jest za duży. (max: %s)');
	define('FILE_FORM_TITLE', 'File Upload Form');
	define('FILE_LABEL_SELECT', 'Zaznacz plik');
	define('FILE_LBL_MORE', 'Dodaj plik');
	define('FILE_CANCEL_UPLOAD', 'Anuluj wysyłanie');
	define('FILE_LBL_UPLOAD', 'Załaduj');
	//file download
	define('ERR_DOWNLOAD_FILE_NOT_FOUND', 'Nie wybrano pliku do pobrania.');
	//Rename
	define('RENAME_FORM_TITLE', 'Zmień nazwę');
	define('RENAME_NEW_NAME', 'Nowa nazwa');
	define('RENAME_LBL_RENAME', 'Zmień nazwę');

	//Tips
	define('TIP_FOLDER_GO_DOWN', 'Kliknij żeby wejść do katalogu...');
	define("TIP_DOC_RENAME", 'Kliknij podwójnie żeby zmienić nazwę...');
	define('TIP_FOLDER_GO_UP', 'Kliknij żeby wrócić do katalogu nadrzędnego...');
	define("TIP_SELECT_ALL", 'Zaznacz wszystko');
	define("TIP_UNSELECT_ALL", 'Odznacz wszystko');
	//WARNING
	define('WARNING_DELETE', 'Czy na pewno chcesz usunąć zaznaczone pliki?');
	define('WARNING_IMAGE_EDIT', 'Proszę wybrać obrazek do edycji.');
	define('WARNING_NOT_FILE_EDIT', 'Proszę wybrać plik do edycji.');
	define('WARING_WINDOW_CLOSE', 'Czy na pewno chcesz zamknąć okno?');
	//Preview
	define('PREVIEW_NOT_PREVIEW', 'Podgląd niedostępny.');
	define('PREVIEW_OPEN_FAILED', 'Nie można otworzyć pliku.');
	define('PREVIEW_IMAGE_LOAD_FAILED', 'Nie można wczytać obrazka');

	//Login
	define('LOGIN_PAGE_TITLE', 'Formularz logowania Ajax File Manager');
	define('LOGIN_FORM_TITLE', 'Formularz logowania');
	define('LOGIN_USERNAME', 'Nazwa użytkownika:');
	define('LOGIN_PASSWORD', 'hasło:');
	define('LOGIN_FAILED', 'Błędna nazwa użytkownika lub hasło.');


	//88888888888   Below for Image Editor   888888888888888888888
		//Warning
		define('IMG_WARNING_NO_CHANGE_BEFORE_SAVE', "Nie można dokonać zmian w obrazku.");

		//General
		define('IMG_GEN_IMG_NOT_EXISTS', 'Obrazek nie istnieje');
		define('IMG_WARNING_LOST_CHANAGES', 'Wszystkie niezapisane zmiany w obrazku zostaną utracone, czy na pewno chcesz kontynuować?');
		define('IMG_WARNING_REST', 'Wszystkie niezapisane zmiany w obrazku zostaną utracone, czy na pewno chcesz przywrócić do stanu początkowego ?');
		define('IMG_WARNING_EMPTY_RESET', 'Nie zostały dokonane żadne zmiany w obrazku.');
		define('IMG_WARING_WIN_CLOSE', 'Czy na pewno chcesz zamknąć okno?');
		define('IMG_WARNING_UNDO', 'Czy na pewno chcesz przywrócić obrazek do poprzedniego stanu?');
		define('IMG_WARING_FLIP_H', 'Czy na pewno chcesz odbić obrazek w poziomie ?');
		define('IMG_WARING_FLIP_V', 'Czy na pewno chcesz odbić obrazek w pionie ?');
		define('IMG_INFO', 'Informacje o obrazku');

		//Mode
			define('IMG_MODE_RESIZE', 'Zmień rozmiar:');
			define('IMG_MODE_CROP', 'Kadruj:');
			define('IMG_MODE_ROTATE', 'Obróć:');
			define('IMG_MODE_FLIP', 'Odbij:');
		//Button

			define('IMG_BTN_ROTATE_LEFT', '90&deg;w lewo');
			define('IMG_BTN_ROTATE_RIGHT', '90&deg;w prawo');
			define('IMG_BTN_FLIP_H', 'Odbij w poziomie');
			define('IMG_BTN_FLIP_V', 'Odbij w pionie');
			define('IMG_BTN_RESET', 'Reset');
			define('IMG_BTN_UNDO', 'Cofnij');
			define('IMG_BTN_SAVE', 'Zapisz');
			define('IMG_BTN_CLOSE', 'Zamknij');
			define('IMG_BTN_SAVE_AS', 'Zapisz jako');
			define('IMG_BTN_CANCEL', 'Anuluj');
		//Checkbox
			define('IMG_CHECKBOX_CONSTRAINT', 'Proporcjonalnie?');
		//Label
			define('IMG_LBL_WIDTH', 'Szerokość:');
			define('IMG_LBL_HEIGHT', 'Wysokość:');
			define('IMG_LBL_X', 'X:');
			define('IMG_LBL_Y', 'Y:');
			define('IMG_LBL_RATIO', 'Stosunek:');
			define('IMG_LBL_ANGLE', 'Kąt:');
			define('IMG_LBL_NEW_NAME', 'Nowa nazwa:');
			define('IMG_LBL_SAVE_AS', 'Formularz zapisu');
			define('IMG_LBL_SAVE_TO', 'Zapisz do:');
			define('IMG_LBL_ROOT_FOLDER', 'Katalog główny');
		//Editor
		//Save as
		define('IMG_NEW_NAME_COMMENTS', 'Proszę nie podawać rozszerzenia pliku.');
		define('IMG_SAVE_AS_ERR_NAME_INVALID', 'Nazwa może zawierać tylko znaki alfabetu, liczby, spację, myślnik i podkreślenie.');
		define('IMG_SAVE_AS_NOT_FOLDER_SELECTED', 'Nie wybrano katalogu docelowego.');
		define('IMG_SAVE_AS_FOLDER_NOT_FOUND', 'Wybrany katalog nie istnieje.');
		define('IMG_SAVE_AS_NEW_IMAGE_EXISTS', 'Plik o podanej nazwie już istnieje.');

		//Save
		define('IMG_SAVE_EMPTY_PATH', 'Pusta ścieżka do obrazka.');
		define('IMG_SAVE_NOT_EXISTS', 'Obrazek nie istnieje.');
		define('IMG_SAVE_PATH_DISALLOWED', 'Brak dostępu do tego pliku.');
		define('IMG_SAVE_UNKNOWN_MODE', 'Nieznany tryb operacji na obrazku');
		define('IMG_SAVE_RESIZE_FAILED', 'Wystąpił błąd podczas zmiany rozmiaru obrazka.');
		define('IMG_SAVE_CROP_FAILED', 'Wystąpił błąd podczas kadrowania obrazka.');
		define('IMG_SAVE_FAILED', 'Wystąpił błąd podczas zapisu obrazka.');
		define('IMG_SAVE_BACKUP_FAILED', 'Nie można zachować oryginalnej wersji obrazka.');
		define('IMG_SAVE_ROTATE_FAILED', 'Nie można obrócić obrazka.');
		define('IMG_SAVE_FLIP_FAILED', 'Nie można odbić obrazka.');
		define('IMG_SAVE_SESSION_IMG_OPEN_FAILED', 'Nie można otworzyć obrazka z sesji.');
		define('IMG_SAVE_IMG_OPEN_FAILED', 'Nie można otworzyć obrazka');


		//UNDO
		define('IMG_UNDO_NO_HISTORY_AVAIALBE', 'Nie można cofnąć operacji - brak historii.');
		define('IMG_UNDO_COPY_FAILED', 'Nie można przywrócić obrazka.');
		define('IMG_UNDO_DEL_FAILED', 'Nie można usunąć obrazka z sesji');

	//88888888888   Above for Image Editor   888888888888888888888

	//88888888888   Session   888888888888888888888
		define("SESSION_PERSONAL_DIR_NOT_FOUND", 'Nie można odnaleźć dedykowanego folderu, który powinien być utworzony podczas tworzenia sesji.');
		define("SESSION_COUNTER_FILE_CREATE_FAILED", 'Nie można otworzyć pliku z licznikiem sesji.');
		define('SESSION_COUNTER_FILE_WRITE_FAILED', 'Nie można zapisać pliku z licznikiem sesji.');
	//88888888888   Session   888888888888888888888

	//88888888888   Below for Text Editor   888888888888888888888
		define('TXT_FILE_NOT_FOUND', 'Plik nie istnieje.');
		define('TXT_EXT_NOT_SELECTED', 'Proszę wybrać rozszerzenie pliku');
		define('TXT_DEST_FOLDER_NOT_SELECTED', 'Proszę wybrać katalog docelowy');
		define('TXT_UNKNOWN_REQUEST', 'Nieznane wywołanie.');
		define('TXT_DISALLOWED_EXT', 'Brak dostępu do zmiany/dodanie dla plików tego typu.');
		define('TXT_FILE_EXIST', 'Taki plik już istnieje.');
		define('TXT_FILE_NOT_EXIST', 'Plik nie został znaleziony.');
		define('TXT_CREATE_FAILED', 'Błąd przy tworzeniu nowego pliku.');
		define('TXT_CONTENT_WRITE_FAILED', 'Błąd przy zapisie zawartości do pliku.');
		define('TXT_FILE_OPEN_FAILED', 'Bład przy otwarciu pliku.');
		define('TXT_CONTENT_UPDATE_FAILED', 'Błąd przy aktualizacji zawartości pliku.');
		define('TXT_SAVE_AS_ERR_NAME_INVALID', 'Nazwa może zawierać tylko znaki alfabetu, liczby, spację, myślnik i podkreślenie.');
	//88888888888   Above for Text Editor   888888888888888888888


?>