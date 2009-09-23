<?php
	/**
	 * language pack
	 * @author Logan Cai (cailongqun [at] yahoo [dot] com [dot] cn)
	 * @link www.phpletter.com
	 * @since 22/April/2007
	 *
	 */
	define('DATE_TIME_FORMAT', 'd/M/Y H:i:s');
	//Label
		//Top Action
		define('LBL_ACTION_REFRESH', 'Neuladen');
		define("LBL_ACTION_DELETE", 'L&ouml;schen');
		define('LBL_ACTION_CUT', 'Ausschneiden');
		define('LBL_ACTION_COPY', 'Kopieren');
		define('LBL_ACTION_PASTE', 'Einf&uuml;gen');
		define('LBL_ACTION_CLOSE', 'Schlie&szlig;en');
		//File Listing
	define('LBL_NAME', 'Name');
	define('LBL_SIZE', 'Gr&ouml;&szlig;e');
	define('LBL_MODIFIED', 'Ge&auml;ndert am');
		//File Information
	define('LBL_FILE_INFO', 'Datei Information:');
	define('LBL_FILE_NAME', 'Name:');
	define('LBL_FILE_CREATED', 'Erstellt am:');
	define("LBL_FILE_MODIFIED", 'Ge&auml;ndert am:');
	define("LBL_FILE_SIZE", 'Datei Gr&ouml;&szlig;e:');
	define('LBL_FILE_TYPE', 'Datei Typ:');
	define("LBL_FILE_WRITABLE", 'Schreibbar?');
	define("LBL_FILE_READABLE", 'Lesbar?');
		//Folder Information
	define('LBL_FOLDER_INFO', 'Ordner Information');
	define("LBL_FOLDER_PATH", 'Pfad:');
	define("LBL_FOLDER_CREATED", 'Erstellt am:');
	define("LBL_FOLDER_MODIFIED", 'Ge&auml;ndert am:');
	define('LBL_FOLDER_SUDDIR', 'Unterordner:');
	define("LBL_FOLDER_FIELS", 'Dateien:');
	define("LBL_FOLDER_WRITABLE", 'Schreibbar?');
	define("LBL_FOLDER_READABLE", 'Lesbar?');
		//Preview
	define("LBL_PREVIEW", 'Vorschau');
	define('LBL_CLICK_PREVIEW', 'Klicke hier um die Vorschau zu sehen.');
	//Buttons
	define('LBL_BTN_SELECT', 'Ausw&auml;hlen');
	define('LBL_BTN_CANCEL', 'Abbrechen');
	define("LBL_BTN_UPLOAD", 'Hochladen');
	define('LBL_BTN_CREATE', 'Erstellen');
	define('LBL_BTN_CLOSE', 'Schlie&szlig;en');
	define("LBL_BTN_NEW_FOLDER", 'Neuer Ordner');
	define('LBL_BTN_EDIT_IMAGE', '&Auml;ndern');
	//Cut
	define('ERR_NOT_DOC_SELECTED_FOR_CUT', 'Kein(e) Dokument(e) ausgew&auml;hlt zum ausschneiden.');
	//Copy
	define('ERR_NOT_DOC_SELECTED_FOR_COPY', 'Kein(e) Dokument(e) ausgew&auml;hlt zum kopieren.');
	//Paste
	define('ERR_NOT_DOC_SELECTED_FOR_PASTE', 'Kein(e) Dokument(e) ausgew&auml;hlt zum einf&uuml;gen.');
	define('WARNING_CUT_PASTE', 'Bist du sicher, dass du die ausgew&auml;hlten Dokumente in diesem Ordner verschieben willst?');
	define('WARNING_COPY_PASTE', 'Bist du sicher, dass du die ausgew&auml;hlten Dokumente in diesem Ordner kopieren willst?');

	//ERROR MESSAGES
		//deletion
	define('ERR_NOT_FILE_SELECTED', 'Bitte w&auml;hle eine Datei.');
	define('ERR_NOT_DOC_SELECTED', 'Kein(e) Dokument(e) zum L&ouml;schen ausgew&auml;hlt.');
	define('ERR_DELTED_FAILED', 'Kann das/die Dokument/e nicht l&ouml;schen.');
	define('ERR_FOLDER_PATH_NOT_ALLOWED', 'Der Ordnerpfad ist nicht erlaubt.');
		//class manager
	define("ERR_FOLDER_NOT_FOUND", 'Kann diesen Ordner nicht auffinden: ');
		//rename
	define('ERR_RENAME_FORMAT', 'Bitte verwende einen Namen der nur Buchstaben, Punkte, Leerzeichen, Bindestriche oder Unterstriche beinhaltet.');
	define('ERR_RENAME_EXISTS', 'Bitte verwende einen Namen der in diesem Ordner noch nicht verwendet wird.');
	define('ERR_RENAME_FILE_NOT_EXISTS', 'Der/Die Ordner/Datei existiert nicht.');
	define('ERR_RENAME_FAILED', 'Kann es nicht umbennen, bitte versuche es nochmals.');
	define('ERR_RENAME_EMPTY', 'Bitte gibt ihm einen Namen.');
	define("ERR_NO_CHANGES_MADE", 'Es wurden keine Ver&auml;nderungen vollzogen.');
	define('ERR_RENAME_FILE_TYPE_NOT_PERMITED', 'Du hast nicht die Rechte diese Datei zu &Auml;ndern.');
		//folder creation
	define('ERR_FOLDER_FORMAT', 'Bitte verwenden einen Namen, welcher nur Buchstaben, Punkte, Leerzeichen, Bindestriche und Unterstriche verwendet.');
	define('ERR_FOLDER_EXISTS', 'Bitte verwende einen Namen der in diesem Ordner noch nicht verwendet wird.');
	define('ERR_FOLDER_CREATION_FAILED', 'Kann den Ordner nicht erstellen, bitte versuche es nochmals.');
	define('ERR_FOLDER_NAME_EMPTY', 'Bitte gib ihm einen Namen.');

		//file upload
	define("ERR_FILE_NAME_FORMAT", 'Bitte verwenden einen Namen, welcher nur Buchstaben, Punkte, Leerzeichen, Bindestriche und Unterstriche verwendet.');
	define('ERR_FILE_NOT_UPLOADED', 'Es wurde keine Datei f&uuml;r den Upload ausgew&auml;hlt.');
	define('ERR_FILE_TYPE_NOT_ALLOWED', 'Du darfst solche Dateitypen nicht hochladen.');
	define('ERR_FILE_MOVE_FAILED', 'Die Datei konnte nicht verschoben werden.');
	define('ERR_FILE_NOT_AVAILABLE', 'Die Datei ist nicht verf&uuml;gbar.');
	define('ERROR_FILE_TOO_BID', 'Datei zu gro&szlig;. (max: %s)');
	//file download
	define('ERR_DOWNLOAD_FILE_NOT_FOUND', 'Keine Dateien zum herunterladen ausgew&auml;hlt.');


	//Tips
	define('TIP_FOLDER_GO_DOWN', 'Einfach klicken um zu diesem Ordner zu gelangen...');
	define("TIP_DOC_RENAME", 'Doppelt klicken um zu editieren...');
	define('TIP_FOLDER_GO_UP', 'Einfach klicken um zum vorherigen Ordner zu gelangen...');
	define("TIP_SELECT_ALL", 'Alles ausw&auml;hlen');
	define("TIP_UNSELECT_ALL", 'Auswahl entfernen');
	//WARNING
	define('WARNING_DELETE', 'Bist du sicher, dass die diese Dateien l&ouml;schen m&ouml;chtest?.');
	define('WARNING_IMAGE_EDIT', 'Bitte w&auml;hle ein Bild zum editieren aus.');
	define('WARNING_NOT_FILE_EDIT', 'Bitte w&auml;hle eine Datei zum editieren.');
	define('WARING_WINDOW_CLOSE', 'Sicher, dass du dieses Fenster schlie�en m�chtest?');
	//Preview
	define('PREVIEW_NOT_PREVIEW', 'Keine Vorschau vorhanden.');
	define('PREVIEW_OPEN_FAILED', 'Kann diese Datei nicht &ouml;ffnen.');
	define('PREVIEW_IMAGE_LOAD_FAILED', 'Kann dieses Bild nicht laden.');

	//Login
	define('LOGIN_PAGE_TITLE', 'Ajax Filemanager Login Fomular');
	define('LOGIN_FORM_TITLE', 'Login Formular');
	define('LOGIN_USERNAME', 'Benutzer:');
	define('LOGIN_PASSWORD', 'Password:');
	define('LOGIN_FAILED', 'Falscher Benutzer/Password.');


	//88888888888   Below for Image Editor   888888888888888888888
		//Warning
		define('IMG_WARNING_NO_CHANGE_BEFORE_SAVE', "Du hast keine &auml;nderungen an diesem Bild gemacht.");

		//General
		define('IMG_GEN_IMG_NOT_EXISTS', 'Das Bild existiert nicht');
		define('IMG_WARNING_LOST_CHANAGES', 'Alle ungespeicherten &auml;nderungen an dem Bild gehen verloren. Bist du sicher, dass du fortfahren willst?');
		define('IMG_WARNING_REST', 'Alle ungespeicherten &auml;nderungen an dem Bild gehen verloren. Bist du sicher, dass du es Zur&uuml;cksetzten willst?');
		define('IMG_WARNING_EMPTY_RESET', 'Bis jetzt wurden keine &auml;nderungen an dem Bild vorgenommen');
		define('IMG_WARING_WIN_CLOSE', 'Bist du sicher, dass du das Fenster schlie&szlig;en m&ouml;chtest?');
		define('IMG_WARNING_UNDO', 'Bist du sicher, dass du das Bild zum voherigen Zustand zur&uuml;cksetzten m&ouml;chtest?');
		define('IMG_WARING_FLIP_H', 'Bist du siche, dass du das Bild horizontal spiegeln m&ouml;chtest?');
		define('IMG_WARING_FLIP_V', 'Bist du sicher das du das Bild vertikal spiegeln m&ouml;chtest?');
		define('IMG_INFO', 'Bildinformation');

		//Mode
			define('IMG_MODE_RESIZE', 'Gr&ouml;&szlig;e ver&Auml;ndern:');
			define('IMG_MODE_CROP', 'Beschneiden:');
			define('IMG_MODE_ROTATE', 'Drehen:');
			define('IMG_MODE_FLIP', 'Spiegeln:');
		//Button

			define('IMG_BTN_ROTATE_LEFT', '90&deg;CCW');
			define('IMG_BTN_ROTATE_RIGHT', '90&deg;CW');
			define('IMG_BTN_FLIP_H', 'Horizontal spiegeln');
			define('IMG_BTN_FLIP_V', 'Vertikal spiegeln');
			define('IMG_BTN_RESET', 'zur&uuml;ck setzten');
			define('IMG_BTN_UNDO', 'R&uuml;ckg&auml;ngig machen');
			define('IMG_BTN_SAVE', 'Speichern');
			define('IMG_BTN_CLOSE', 'Schlie&szlig;en');
			define('IMG_BTN_SAVE_AS', 'Speichern als...');
			define('IMG_BTN_CANCEL', 'Abbrechen');
		//Checkbox
			define('IMG_CHECKBOX_CONSTRAINT', 'Beschr&auml;nkung?');
		//Label
			define('IMG_LBL_WIDTH', 'Breite:');
			define('IMG_LBL_HEIGHT', 'H&ouml;he:');
			define('IMG_LBL_X', 'X:');
			define('IMG_LBL_Y', 'Y:');
			define('IMG_LBL_RATIO', 'Verh&auml;ltniss:');
			define('IMG_LBL_ANGLE', 'Winkel:');
			define('IMG_LBL_NEW_NAME', 'Neuer Name:');
			define('IMG_LBL_SAVE_AS', 'Speichern als Form');
			define('IMG_LBL_SAVE_TO', 'Speichern in...:');
			define('IMG_LBL_ROOT_FOLDER', 'Haupt Ordner');
		//Editor
		//Save as
		define('IMG_NEW_NAME_COMMENTS', 'Bitte schlie&szlig;e die Bilderweiterung  nicht mit ein.');
		define('IMG_SAVE_AS_ERR_NAME_INVALID', 'Bitte verwenden einen Namen, welcher nur Buchstaben, Punkte, Leerzeichen, Bindestriche und Unterstriche verwendet.');
		define('IMG_SAVE_AS_NOT_FOLDER_SELECTED', 'Kein Zielordner ausgew&auml;hlt.');
		define('IMG_SAVE_AS_FOLDER_NOT_FOUND', 'Der Zielordner existiert nicht.');
		define('IMG_SAVE_AS_NEW_IMAGE_EXISTS', 'Es existiert ein Bild mit dem selben Namen.');

		//Save
		define('IMG_SAVE_EMPTY_PATH', 'Leerer Bildpfad.');
		define('IMG_SAVE_NOT_EXISTS', 'Das Bild existiert nicht.');
		define('IMG_SAVE_PATH_DISALLOWED', 'Du hast keine Berechtigung, auf diese Datei zu zugreifen.');
		define('IMG_SAVE_UNKNOWN_MODE', 'Unerwartete Bildoperation');
		define('IMG_SAVE_RESIZE_FAILED', 'Die Bildgr&ouml;&szlig;e konnte nicht ver&auml;ndert werden.');
		define('IMG_SAVE_CROP_FAILED', 'Das Bild konnte nicht beschnitten werden.');
		define('IMG_SAVE_FAILED', 'Das Bild konnte nicht gespeichert werden.');
		define('IMG_SAVE_BACKUP_FAILED', 'Es konnte keine Sicherung von dem Bild erstellt werden.');
		define('IMG_SAVE_ROTATE_FAILED', 'Das bild kann nicht gedreht werden.');
		define('IMG_SAVE_FLIP_FAILED', 'Das Bild kann nicht umgedreht werden.');
		define('IMG_SAVE_SESSION_IMG_OPEN_FAILED', 'Kann das Bild nicht aus der Sitzung &ouml;ffnen.');
		define('IMG_SAVE_IMG_OPEN_FAILED', 'Kann das Bild nicht &ouml;ffnen');


		//UNDO
		define('IMG_UNDO_NO_HISTORY_AVAIALBE', 'Keine Chronik verf&uuml;gbar f&uuml;r die Undo-Funktion.');
		define('IMG_UNDO_COPY_FAILED', 'Das Bild kann nicht wiederhergestellt werden.');
		define('IMG_UNDO_DEL_FAILED', 'Kann das Sitzungs-Bild nicht l&ouml;schen');

	//88888888888   Above for Image Editor   888888888888888888888

	//88888888888   Session   888888888888888888888
		define("SESSION_PERSONAL_DIR_NOT_FOUND", 'Kann den bestimmten Ordner nicht finden, welcher als Sitzungsordner erstellt wordens ein sollte');
		define("SESSION_COUNTER_FILE_CREATE_FAILED", 'Kann die "Sitzungsz&auml;hler-Datei" im Odner nicht &ouml;ffnen.');
		define('SESSION_COUNTER_FILE_WRITE_FAILED', 'Kann die "Sitzungsz&auml;hlerdatei nicht beschreiben.');
	//88888888888   Session   888888888888888888888

	//88888888888   Below for Text Editor   888888888888888888888
		define('TXT_FILE_NOT_FOUND', 'Datei wurde nicht gefunden.');
		define('TXT_EXT_NOT_SELECTED', 'Bitte w&auml;hle die Dateiwerweiterung');
		define('TXT_DEST_FOLDER_NOT_SELECTED', 'Bitte w&auml;hle den Zielordner');
		define('TXT_UNKNOWN_REQUEST', 'Unbekannte R&uuml;ckmeldung.');
		define('TXT_DISALLOWED_EXT', 'Du hast nicht die Berechtigung eine solche Datei zu editieren oder hinzuzuf&uuml;gen.');
		define('TXT_FILE_EXIST', 'Eine solche Datei existiert bereits.');
		define('TXT_FILE_NOT_EXIST', 'Keine solche gefunden.');
		define('TXT_CREATE_FAILED', 'Konnte keine neue Datei erstellen.');
		define('TXT_CONTENT_WRITE_FAILED', 'Konnte keinen Inhalt in die Datei schreiben.');
		define('TXT_FILE_OPEN_FAILED', 'Die Datei konnte nicht ge&ouml;ffnet werden.');
		define('TXT_CONTENT_UPDATE_FAILED', 'Konnte den Inhalt der Datei nicht aktualisieren.');
		define('TXT_SAVE_AS_ERR_NAME_INVALID', 'Bitte verwenden einen Namen, welcher nur Buchstaben, Punkte, Leerzeichen, Bindestriche und Unterstriche verwendet.');
	//88888888888   Above for Text Editor   888888888888888888888


?>