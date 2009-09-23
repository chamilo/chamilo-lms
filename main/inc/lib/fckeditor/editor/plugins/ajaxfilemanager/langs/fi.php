<?
	/**
	 * language pack
	 * @author Jani Peltoniemi (jani@janipeltoniemi.net)
	 * @link www.janipeltoniemi.net
	 * @since 14/10/2007
	 *
	 */
	define('DATE_TIME_FORMAT', 'd. M. Y H:i:s');
	//Label
		//Top Action
		define('LBL_ACTION_REFRESH', 'Päivitä');
		define("LBL_ACTION_DELETE", 'Poista');
		define('LBL_ACTION_CUT', 'Leikkaa');
		define('LBL_ACTION_COPY', 'Kopioi');
		define('LBL_ACTION_PASTE', 'Liitä');
		//File Listing
	define('LBL_NAME', 'Nimi');
	define('LBL_SIZE', 'Koko');
	define('LBL_MODIFIED', 'Muokattu');
		//File Information
	define('LBL_FILE_INFO', 'Tietoa:');
	define('LBL_FILE_NAME', 'Nimi:');
	define('LBL_FILE_CREATED', 'Luotu:');
	define("LBL_FILE_MODIFIED", 'Muokattu:');
	define("LBL_FILE_SIZE", 'Tiedostokoko:');
	define('LBL_FILE_TYPE', 'Tiedostotyyppi:');
	define("LBL_FILE_WRITABLE", 'Kirjoitettavissa?');
	define("LBL_FILE_READABLE", 'Luettavissa?');
		//Folder Information
	define('LBL_FOLDER_INFO', 'Tietoa kansiosta');
	define("LBL_FOLDER_PATH", 'Polku:');
	define("LBL_FOLDER_CREATED", 'Luotu:');
	define("LBL_FOLDER_MODIFIED", 'Muokattu:');
	define('LBL_FOLDER_SUDDIR', 'Alikansiot:');
	define("LBL_FOLDER_FIELS", 'Tiedostot:');
	define("LBL_FOLDER_WRITABLE", 'Kirjoitettavissa?');
	define("LBL_FOLDER_READABLE", 'Luettavissa?');
		//Preview
	define("LBL_PREVIEW", 'Esikatselu');
	//Buttons
	define('LBL_BTN_SELECT', 'Valitse');
	define('LBL_BTN_CANCEL', 'Peruuta');
	define("LBL_BTN_UPLOAD", 'Lähetä');
	define('LBL_BTN_CREATE', 'Luo');
	define('LBL_BTN_CLOSE', 'Sulje');
	define("LBL_BTN_NEW_FOLDER", 'Uusi kansio');
	define('LBL_BTN_EDIT_IMAGE', 'Muokkaa');
	//Cut
	define('ERR_NOT_DOC_SELECTED_FOR_CUT', 'Ei mitään valittuna leikkausta varten.');
	//Copy
	define('ERR_NOT_DOC_SELECTED_FOR_COPY', 'Ei mitään valittuna kopiointia varten');
	//Paste
	define('ERR_NOT_DOC_SELECTED_FOR_PASTE', 'Ei mitään valittuna liittämistä varten');
	define('WARNING_CUT_PASTE', 'Haluatko varmasti siirtää valitut tiedostot/kansiot tähän kansioon?');
	define('WARNING_COPY_PASTE', 'Haluatko varmasti kopioida valitut tiedostot/kansiot tähän kansioon?');

	//ERROR MESSAGES
		//deletion
	define('ERR_NOT_FILE_SELECTED', 'Valitse ensin tiedosto.');
	define('ERR_NOT_DOC_SELECTED', 'Ei mitään valittuna poistamista varten.');
	define('ERR_DELTED_FAILED', 'Valittuja tiedostoja/kansioita ei voitu poistaa');
	define('ERR_FOLDER_PATH_NOT_ALLOWED', 'Polku ei ole sallittu');
		//class manager
	define("ERR_FOLDER_NOT_FOUND", 'Polkua ei löytynyt: ');
		//rename
	define('ERR_RENAME_FORMAT', 'Uusi nimi voi sisältää vain kirjaimia, numeroita, välilyöntejä ja tavu- sekä alaviivoja.');
	define('ERR_RENAME_EXISTS', 'Annettu nimi on jo olemassa.');
	define('ERR_RENAME_FILE_NOT_EXISTS', 'Tiedostoa/kansiota ei ole olemassa');
	define('ERR_RENAME_FAILED', 'Ei voitu uudelleennimetä.');
	define('ERR_RENAME_EMPTY', 'Kirjoita nimi.');
	define("ERR_NO_CHANGES_MADE", 'Muutoksia ei tehty');
	define('ERR_RENAME_FILE_TYPE_NOT_PERMITED', 'Sinulla ei ole lupaa antaa tiedostolle tätä tiedostopäätettä');
		//folder creation
	define('ERR_FOLDER_FORMAT', 'Uusi nimi voi sisältää vain kirjaimia, numeroita, välilyöntejä ja tavu- sekä alaviivoja.');
	define('ERR_FOLDER_EXISTS', 'Annettu nimi on jo olemassa.');
	define('ERR_FOLDER_CREATION_FAILED', 'Ei voitu luoda kansiota. Yritä uudelleen.');
	define('ERR_FOLDER_NAME_EMPTY', 'Kansion nimi ei voi olla tyhjä');

		//file upload
	define("ERR_FILE_NAME_FORMAT", 'Uusi nimi voi sisältää vain kirjaimia, numeroita, välilyöntejä ja tavu- sekä alaviivoja.');
	define('ERR_FILE_NOT_UPLOADED', 'Tiedostoa ei valittuna.');
	define('ERR_FILE_TYPE_NOT_ALLOWED', 'Sinulla ei ole lupaa lähettää tämäntyyppisiä tiedostoja');
	define('ERR_FILE_MOVE_FAILED', 'Lähetetyn tiedoston siirto epäonnistui');
	define('ERR_FILE_NOT_AVAILABLE', 'Tiedostoa ei ole saatavilla');
	define('ERROR_FILE_TOO_BID', 'Tiedosto on liian suuri (suurin: %s)');


	//Tips
	define('TIP_FOLDER_GO_DOWN', 'Klikkaa kerran avataksesi kansion...');
	define("TIP_DOC_RENAME", 'Voit antaa uuden nimen tuplaklikkaamalla...');
	define('TIP_FOLDER_GO_UP', 'Klikkaa kerran siirtyäksesi ylempään kansioon...');
	define("TIP_SELECT_ALL", 'Valitse kaikki');
	define("TIP_UNSELECT_ALL", 'Poista valinnat');
	//WARNING
	define('WARNING_DELETE', 'Haluatko varmasti poistaa valitut tiedostot?');
	define('WARNING_IMAGE_EDIT', 'Valitse kuva muokattavaksi.');
	define('WARING_WINDOW_CLOSE', 'Haluatko varmasti sulkea ikkunan?');
	//Preview
	define('PREVIEW_NOT_PREVIEW', 'Ei esikatselua saatavilla.');
	define('PREVIEW_OPEN_FAILED', 'Tiedoston avaus epäonnistui.');
	define('PREVIEW_IMAGE_LOAD_FAILED', 'Ei voitu avata kuvaa.');

	//Login
	define('LOGIN_PAGE_TITLE', 'Ajax File Manager - Sisäänkirjautuminen');
	define('LOGIN_FORM_TITLE', 'Sisäänkirjautuminen');
	define('LOGIN_USERNAME', 'Käyttäjänimi:');
	define('LOGIN_PASSWORD', 'Salasana:');
	define('LOGIN_FAILED', 'Väärä käyttäjänimi tai salasana!');


	//88888888888   Below for Image Editor   888888888888888888888
		//Warning
		define('IMG_WARNING_NO_CHANGE_BEFORE_SAVE', "Et ole tehnyt kuvaan muutoksia.");

		//General
		define('IMG_GEN_IMG_NOT_EXISTS', 'Kuvaa ei ole olemassa');
		define('IMG_WARNING_LOST_CHANAGES', 'Kaikki tallentamattomat muutokset kuvaan menetetään. Haluatko jatkaa?');
		define('IMG_WARNING_REST', 'Kaikki tallentamattomat muutokset kuvaan menetetään. Haluatko jatkaa?');
		define('IMG_WARNING_EMPTY_RESET', 'Et ole tehnyt kuvaan muutoksia.');
		define('IMG_WARING_WIN_CLOSE', 'Haluatko varmasti sulkea ikkunan?');
		define('IMG_WARNING_UNDO', 'Haluatko varmasti kumota edellisen muutoksen?');
		define('IMG_WARING_FLIP_H', 'Käännetäänkö kuva vaakasuunnassa?');
		define('IMG_WARING_FLIP_V', 'Käännetäänkö kuva pystysuunnassa?');
		define('IMG_INFO', 'Tietoa kuvasta');

		//Mode
			define('IMG_MODE_RESIZE', 'Muuta kokoa:');
			define('IMG_MODE_CROP', 'Rajaa:');
			define('IMG_MODE_ROTATE', 'Kierrä:');
			define('IMG_MODE_FLIP', 'Käännä:');
		//Button

			define('IMG_BTN_ROTATE_LEFT', '90&deg; Vastapäivään');
			define('IMG_BTN_ROTATE_RIGHT', '90&deg; Myötäpäivään');
			define('IMG_BTN_FLIP_H', 'Käännä vaakasuunnassa');
			define('IMG_BTN_FLIP_V', 'Käännä pystysuunnassa');
			define('IMG_BTN_RESET', 'Palauta alkutilaan');
			define('IMG_BTN_UNDO', 'Kumoa');
			define('IMG_BTN_SAVE', 'Tallenna');
			define('IMG_BTN_CLOSE', 'Sulje');
		//Checkbox
			define('IMG_CHECKBOX_CONSTRAINT', 'Pidä kuvasuhde');
		//Label
			define('IMG_LBL_WIDTH', 'Leveys:');
			define('IMG_LBL_HEIGHT', 'Korkeus:');
			define('IMG_LBL_X', 'x:');
			define('IMG_LBL_Y', 'y:');
			define('IMG_LBL_RATIO', 'Kuvasuhde:');
			define('IMG_LBL_ANGLE', 'Kulma:');
		//Editor


		//Save
		define('IMG_SAVE_EMPTY_PATH', 'Tyhjä polku.');
		define('IMG_SAVE_NOT_EXISTS', 'Kuvaa ei ole olemassa.');
		define('IMG_SAVE_PATH_DISALLOWED', 'Sinulla ei ole oikeuksia tähän tiedostoon.');
		define('IMG_SAVE_UNKNOWN_MODE', 'Tunnistamaton operaatiomuoto.');
		define('IMG_SAVE_RESIZE_FAILED', 'Koon muutos ei onnistunut.');
		define('IMG_SAVE_CROP_FAILED', 'Rajaus ei onnistunut.');
		define('IMG_SAVE_FAILED', 'Tallennus epäonnistui.');
		define('IMG_SAVE_BACKUP_FAILED', 'Varmuuskopiointi epäonnistui.');
		define('IMG_SAVE_ROTATE_FAILED', 'Kiertäminen epäonnistui.');
		define('IMG_SAVE_FLIP_FAILED', 'Kääntäminen epäonnistui.');
		define('IMG_SAVE_SESSION_IMG_OPEN_FAILED', 'Kuvatiedoston avaaminen istunnosta epäonnistui.');
		define('IMG_SAVE_IMG_OPEN_FAILED', 'Kuvatiedoston avaaminen epäonnistui');

		//UNDO
		define('IMG_UNDO_NO_HISTORY_AVAIALBE', 'Ei muokkaushistoriaa');
		define('IMG_UNDO_COPY_FAILED', 'Palauttaminen epäonnistui');
		define('IMG_UNDO_DEL_FAILED', 'Kuvan poistaminen istunnosta epäonnistui.');

	//88888888888   Above for Image Editor   888888888888888888888

	//88888888888   Session   888888888888888888888
		define("SESSION_PERSONAL_DIR_NOT_FOUND", 'Unable to find the dedicated folder which should have been created under session folder');
		define("SESSION_COUNTER_FILE_CREATE_FAILED", 'Unable to open the session counter file.');
		define('SESSION_COUNTER_FILE_WRITE_FAILED', 'Unable to write the session counter file.');
	//88888888888   Session   888888888888888888888


?>