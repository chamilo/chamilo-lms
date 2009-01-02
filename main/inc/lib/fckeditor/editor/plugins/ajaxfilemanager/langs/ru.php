<?php
	/**
	 * language pack
	 * @author Logan Cai (cailongqun@yahoo.com.cn)
	 * @link www.phpletter.com
	 * @since 22/April/2007
	 *
	 */
	define('DATE_TIME_FORMAT', 'd/M/Y H:i:s');
	//Label
		//Top Action
		define('LBL_ACTION_REFRESH', 'Обновить');
		define("LBL_ACTION_DELETE", 'Удалить');
		define('LBL_ACTION_CUT', 'Вырезать');
		define('LBL_ACTION_COPY', 'Копировать');
		define('LBL_ACTION_PASTE', 'Вставить');
		//File Listing
	define('LBL_NAME', 'Имя');
	define('LBL_SIZE', 'Размер');
	define('LBL_MODIFIED', 'Изменено');
		//File Information
	define('LBL_FILE_INFO', 'Информация:');
	define('LBL_FILE_NAME', 'Имя:');
	define('LBL_FILE_CREATED', 'Создан:');
	define("LBL_FILE_MODIFIED", 'Изменен:');
	define("LBL_FILE_SIZE", 'Размер:');
	define('LBL_FILE_TYPE', 'Тип:');
	define("LBL_FILE_WRITABLE", 'Запись:');
	define("LBL_FILE_READABLE", 'Чтение:');
		//Folder Information
	define('LBL_FOLDER_INFO', 'Информация');
	define("LBL_FOLDER_PATH", 'Путь:');
	define("LBL_FOLDER_CREATED", 'Создана:');
	define("LBL_FOLDER_MODIFIED", 'Изменена:');
	define('LBL_FOLDER_SUDDIR', 'Вложенных папок:');
	define("LBL_FOLDER_FIELS", 'Файлов:');
	define("LBL_FOLDER_WRITABLE", 'Запись:');
	define("LBL_FOLDER_READABLE", 'Чтение:');
		//Preview
	define("LBL_PREVIEW", 'Предпросмотр');
	//Buttons
	define('LBL_BTN_SELECT', 'Выбрать');
	define('LBL_BTN_CANCEL', 'Отмена');
	define("LBL_BTN_UPLOAD", 'Загрузить');
	define('LBL_BTN_CREATE', 'Создать');
	define('LBL_BTN_CLOSE', 'Закрыть');
	define("LBL_BTN_NEW_FOLDER", 'New_Folder');
	define('LBL_BTN_EDIT_IMAGE', 'Ред.');
	//Cut
	define('ERR_NOT_DOC_SELECTED_FOR_CUT', 'Выберите документ(ы), которые Вы хотите вырезать.');
	//Copy
	define('ERR_NOT_DOC_SELECTED_FOR_COPY', 'Выберите документ(ы), которые Вы хотите копировать.');
	//Paste
	define('ERR_NOT_DOC_SELECTED_FOR_PASTE', 'Выберите документ(ы), которые Вы хотите вставить.');
	define('WARNING_CUT_PASTE', 'Вы уверенны, что хотите переместить выбранные документы в эту папку?');
	define('WARNING_COPY_PASTE', 'Вы уверенны, что хотите скопировать выбранные документы в эту папку?');
	
	//ERROR MESSAGES
		//deletion
	define('ERR_NOT_FILE_SELECTED', 'Пожалуйста, выберите файл.');
	define('ERR_NOT_DOC_SELECTED', 'Выберите документ(ы), которые Вы хотите удалить.');
	define('ERR_DELTED_FAILED', 'Невозможно удалить выбранные документ(ы).');
	define('ERR_FOLDER_PATH_NOT_ALLOWED', 'Недопустимый путь к папке.');
		//class manager
	define("ERR_FOLDER_NOT_FOUND", 'Невозможно найти папку: ');
		//rename
	define('ERR_RENAME_FORMAT', 'Пожалуйста, укажите корректное имя. Разрешены буквы латинского алфавита, цифры, пробел, дефис и нижнее подчеркивание.');
	define('ERR_RENAME_EXISTS', 'Это имя уже используется в данной папке. Пожалуйста, укажите другое имя.');
	define('ERR_RENAME_FILE_NOT_EXISTS', 'Файл или папка не существует.');
	define('ERR_RENAME_FAILED', 'Невозможно переименовать. Пожалуйста, повторите позже.');
	define('ERR_RENAME_EMPTY', 'Пожалуйста, укажите имя.');
	define("ERR_NO_CHANGES_MADE", 'Изменения не были произведены.');
	define('ERR_RENAME_FILE_TYPE_NOT_PERMITED', 'Переименование в файл с таким расширением запрещено.');
		//folder creation
	define('ERR_FOLDER_FORMAT', 'Пожалуйста, укажите корректное имя. Разрешены буквы латинского алфавита, цифры, пробел, дефис и нижнее подчеркивание.');
	define('ERR_FOLDER_EXISTS', 'Это имя уже используется в данной папке. Пожалуйста, укажите другое имя.');
	define('ERR_FOLDER_CREATION_FAILED', 'Невозможно создать папку. Пожалуйста, повторите позже.');
	define('ERR_FOLDER_NAME_EMPTY', 'Пожалуйста, укажите имя.');
	
		//file upload
	define("ERR_FILE_NAME_FORMAT", 'Пожалуйста, укажите корректное имя. Разрешены буквы латинского алфавита, цифры, пробел, дефис и нижнее подчеркивание.');
	define('ERR_FILE_NOT_UPLOADED', 'Не выбран файл для загрузки.');
	define('ERR_FILE_TYPE_NOT_ALLOWED', 'Загрузка файлов с таким расширением запрещена.');
	define('ERR_FILE_MOVE_FAILED', 'Не удалось переместить файл.');
	define('ERR_FILE_NOT_AVAILABLE', 'Файл недоступен.');
	define('ERROR_FILE_TOO_BID', 'Файл слишком большой. (Максимально допустимый размер: %s)');
	

	//Tips
	define('TIP_FOLDER_GO_DOWN', 'Кликните, чтобы войти в эту папку...');
	define("TIP_DOC_RENAME", 'Кликните дважды для редактирования...');
	define('TIP_FOLDER_GO_UP', 'Кликните, чтобы переместится в родительску папку...');
	define("TIP_SELECT_ALL", 'Выделить все');
	define("TIP_UNSELECT_ALL", 'Снять выделение');
	//WARNING
	define('WARNING_DELETE', 'Вы действительно хотите удалить выбранные файлы?.');
	define('WARNING_IMAGE_EDIT', 'Пожалуйста, выберите изображение для редактирования.');
	define('WARING_WINDOW_CLOSE', 'Вы действительно хотите закрыть это окно?');
	//Preview
	define('PREVIEW_NOT_PREVIEW', 'Предпросмотр недоступен.');
	define('PREVIEW_OPEN_FAILED', 'Невозможно открыть файл.');
	define('PREVIEW_IMAGE_LOAD_FAILED', 'Невозможно загрузить изображение.');

	//Login
	define('LOGIN_PAGE_TITLE', 'Вход в менеджер файлов');
	define('LOGIN_FORM_TITLE', 'Вход');
	define('LOGIN_USERNAME', 'Имя:');
	define('LOGIN_PASSWORD', 'Пароль:');
	define('LOGIN_FAILED', 'Неверное имя или пароль..');
	
	
	//88888888888   Below for Image Editor   888888888888888888888
		//Warning 
		define('IMG_WARNING_NO_CHANGE_BEFORE_SAVE', "Не было сделано никаких изменений в изображении.");
		
		//General
		define('IMG_GEN_IMG_NOT_EXISTS', 'Изображение не существует.');
		define('IMG_WARNING_LOST_CHANAGES', 'Все несохраненные изменения будут потеряны. Вы уверенны, что хотите продолжить?');
		define('IMG_WARNING_REST', 'Все несохраненные изменения будут потеряны. Вы уверенны, что хотите сбросить изменения?');
		define('IMG_WARNING_EMPTY_RESET', 'Не было сделано никаких изменений в изображении до настоящего времени.');
		define('IMG_WARING_WIN_CLOSE', 'Вы уверены, что хотите закрыть окно?');
		define('IMG_WARNING_UNDO', 'Вы уверены, что хотите восстановить изображение к предыдущему состоянию?');
		define('IMG_WARING_FLIP_H', 'Вы уверены, что хотите отразить изображение горизонтально?');
		define('IMG_WARING_FLIP_V', 'Вы уверены, что хотите отразить изображение вертикально?');
		define('IMG_INFO', 'Информация об изображении');
		
		//Mode
			define('IMG_MODE_RESIZE', 'Изменить размер');
			define('IMG_MODE_CROP', 'Обрезать');
			define('IMG_MODE_ROTATE', 'Повернуть');
			define('IMG_MODE_FLIP', 'Отобразить зеркально');
		//Button
		
			define('IMG_BTN_ROTATE_LEFT', '90&deg; против часовй');
			define('IMG_BTN_ROTATE_RIGHT', '90&deg; по часовой');
			define('IMG_BTN_FLIP_H', 'Отразить горизонтально');
			define('IMG_BTN_FLIP_V', 'Отразить вертикально');
			define('IMG_BTN_RESET', 'Сбросить');
			define('IMG_BTN_UNDO', 'Отменить');
			define('IMG_BTN_SAVE', 'Сохранить');
			define('IMG_BTN_CLOSE', 'Закрыть');
		//Checkbox
			define('IMG_CHECKBOX_CONSTRAINT', 'Сохранять пропорции');
		//Label
			define('IMG_LBL_WIDTH', 'Ширина:');
			define('IMG_LBL_HEIGHT', 'Высота:');
			define('IMG_LBL_X', 'X:');
			define('IMG_LBL_Y', 'Y:');
			define('IMG_LBL_RATIO', 'Коэффициент:');
			define('IMG_LBL_ANGLE', 'Угол поворота:');
		//Editor

			
		//Save
		define('IMG_SAVE_EMPTY_PATH', 'Путь к изображению пуст.');
		define('IMG_SAVE_NOT_EXISTS', 'Изображение не существует.');
		define('IMG_SAVE_PATH_DISALLOWED', 'Доступ к файлу запрещен.');
		define('IMG_SAVE_UNKNOWN_MODE', 'Неподдерживаемая операция.');
		define('IMG_SAVE_RESIZE_FAILED', 'Не удалось изменить размер изображения.');
		define('IMG_SAVE_CROP_FAILED', 'Не удалось обрезать изображение.');
		define('IMG_SAVE_FAILED', 'Не удалось сохранить изображение.');
		define('IMG_SAVE_BACKUP_FAILED', 'Не удалось создать архивную копию оригинального изображения.');
		define('IMG_SAVE_ROTATE_FAILED', 'Не удалось повернуть изображение.');
		define('IMG_SAVE_FLIP_FAILED', 'Не удалось зеркально отобразить изображение.');
		define('IMG_SAVE_SESSION_IMG_OPEN_FAILED', 'Не удалось открыть изображение из сессии.');
		define('IMG_SAVE_IMG_OPEN_FAILED', 'Не удалось открыть изображение.');
		
		//UNDO
		define('IMG_UNDO_NO_HISTORY_AVAIALBE', 'Невозможно отменить операцию, так как история изменений отсутствует.');
		define('IMG_UNDO_COPY_FAILED', 'Невозможно восстановить изображение.');
		define('IMG_UNDO_DEL_FAILED', 'Невозможно удалить сессию изображения.');
	
	//88888888888   Above for Image Editor   888888888888888888888
	
	//88888888888   Session   888888888888888888888
		define("SESSION_PERSONAL_DIR_NOT_FOUND", 'Невозможно найти папку, предназначенную для хранения сессии.');
		define("SESSION_COUNTER_FILE_CREATE_FAILED", 'Невозможно открыть файл сессии.');
		define('SESSION_COUNTER_FILE_WRITE_FAILED', 'Невозможно сделать запись в файл сессии.');
	//88888888888   Session   888888888888888888888
	
	
?>
