<?php
	/**	**File *** ukr.php	 ***
	 * sysem base config setting
	 * @author Logan Cai (cailongqun@yahoo.com.cn)
	 * @link www.phpletter.com
	 * @since 22/April/2007
	 *	 Translating into the Ukrainian language # oppo (www.end.if.ua)(mail parasolya@mail.ru) #
	 */
	define('DATE_TIME_FORMAT', 'd/M/Y H:i:s');
	//Label
		//Top Action
		define('LBL_ACTION_REFRESH', 'Відновити');
		define("LBL_ACTION_DELETE", 'Видалити');
		//File Listing
	define('LBL_NAME', 'Ім\'я');
	define('LBL_SIZE', 'Размер');
	define('LBL_MODIFIED', 'Змінений як');
		//File Information
	define('LBL_FILE_INFO', 'Інформація про файл:');
	define('LBL_FILE_NAME', 'Ім\'я:');
	define('LBL_FILE_CREATED', 'Создан как:');
	define("LBL_FILE_MODIFIED", 'Змінений як:');
	define("LBL_FILE_SIZE", 'Розмір файлу:');
	define('LBL_FILE_TYPE', 'Тип файлу:');
	define("LBL_FILE_WRITABLE", 'Доступ на запис?');
	define("LBL_FILE_READABLE", 'Доступ на читання?');
		//Folder Information
	define('LBL_FOLDER_INFO', 'Інформація про папку');
	define("LBL_FOLDER_PATH", 'Шлях:');
	define("LBL_FOLDER_CREATED", 'Створений як:');
	define("LBL_FOLDER_MODIFIED", 'Змінений як:');
	define('LBL_FOLDER_SUDDIR', 'Підпапки:');
	define("LBL_FOLDER_FIELS", 'Файли:');
	define("LBL_FOLDER_WRITABLE", 'Доступ на запис?');
	define("LBL_FOLDER_READABLE", 'Доступ на читання?');
		//Preview
	define("LBL_PREVIEW", 'Перегляд');
	//Buttons
	define('LBL_BTN_SELECT', 'Вибір');
	define('LBL_BTN_CANCEL', 'Відміна');
	define("LBL_BTN_UPLOAD", ' Завантаження');
	define('LBL_BTN_CREATE', ' Створити');
	define("LBL_BTN_NEW_FOLDER", 'New_folder');
	//ERROR MESSAGES
		//deletion
	define('ERR_NOT_FILE_SELECTED', 'Будь ласка виберіть теку.');
	define('ERR_NOT_DOC_SELECTED', 'Немає документа(iв), вибраного для видалення.');
	define('ERR_DELTED_FAILED', 'Не в змозi видалити вибраний документ(и).');
	define('ERR_FOLDER_PATH_NOT_ALLOWED', 'Невірний шлях до теки.');
		//class manager
	define("ERR_FOLDER_NOT_FOUND", 'Не в змозі розмістити специфічну теку: ');
		//rename
	define('ERR_RENAME_FORMAT', 'Вкажіть правильне ім\'я, яке містить тільки букви, цифри, пропуск, дефіс і підкреслення.');
	define('ERR_RENAME_EXISTS', 'Вкажіть унікальне ім\'я, яке під папкою.');
	define('ERR_RENAME_FILE_NOT_EXISTS', 'Файл / теки не існує.');
	define('ERR_RENAME_FAILED', 'Не в змозі перейменувати це, пробуйте знову.');
	define('ERR_RENAME_EMPTY', 'Вкажіть ім\'я.');
	define("ERR_NO_CHANGES_MADE", 'Немає змін.');
	define('ERR_RENAME_FILE_TYPE_NOT_PERMITED', 'Вам не дозволяється змінити файл до такого розширення.');
		//folder creation
	define('ERR_FOLDER_FORMAT', 'Вкажіть правильне ім\'я, яке містить тільки букви, цифри, пропуск, дефіс і підкреслення.');
	define('ERR_FOLDER_EXISTS', 'Вкажіть унікальне ім\'я, яке унікальне під текою.');
	define('ERR_FOLDER_CREATION_FAILED', 'Не в змозі створити теку, пробуйте знову.');
	define('ERR_FOLDER_NAME_EMPTY', 'Вкажіть ім\'я.');

		//file upload
	define("ERR_FILE_NAME_FORMAT", 'Вкажіть правильне ім\'я, яке містить тільки букви, цифри, пропуск, дефіс і підкреслення  .');
	define('ERR_FILE_NOT_UPLOADED', 'Не був вибраний файл для пересилки.');
	define('ERR_FILE_TYPE_NOT_ALLOWED', 'Вам не дозволяється переслати такий тип файлу.');
	define('ERR_FILE_MOVE_FAILED', 'Помилка переміщення.');
	define('ERR_FILE_NOT_AVAILABLE', 'Файл недоступний.');
	define('ERROR_FILE_TOO_BID', 'Файл великий. (макс: %s)');


	//Tips
	define('TIP_FOLDER_GO_DOWN', 'Натиснiть, щоб дістатися до цієї теки...');
	define("TIP_DOC_RENAME", 'Подвійне натиснення для редагування...');
	define('TIP_FOLDER_GO_UP', 'Натисніть, щоб повернутися в батьківську теку...');
	define("TIP_SELECT_ALL", 'Виділити все');
	define("TIP_UNSELECT_ALL", 'Зняти виділення');
	//WARNING
	define('WARNING_DELETE', 'Ви упевнені, що хочете видалити?');
	//Preview
	define('PREVIEW_NOT_PREVIEW', 'Поставте зліва *галочку* на катрінке для перегляду');
	define('PREVIEW_OPEN_FAILED', 'Неможливо відкрити файл.');
	define('PREVIEW_IMAGE_LOAD_FAILED', 'Неможливо завантажити картинку');

	//Login
	define('LOGIN_PAGE_TITLE', 'Ajax File Manager Login Form');
	define('LOGIN_FORM_TITLE', 'Форма логіна');
	define('LOGIN_USERNAME', 'Ім\'я:');
	define('LOGIN_PASSWORD', 'Пароль:');
	define('LOGIN_FAILED', 'Невірне ім\'я або пароль.');


?>