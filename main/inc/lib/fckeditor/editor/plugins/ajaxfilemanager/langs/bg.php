<?php
	/**
	 * Bulgarian language pack
	 * @author Rumen Gospodinov
	 * @link www.ultranet.bg
	 * @since 14/May/2008
	 *
	 */
	define('DATE_TIME_FORMAT', 'd/M/Y H:i:s');
	//Common
	//Menu
	
define('MENU_SELECT', 'Избор');
define('MENU_DOWNLOAD', 'Изтегли');
define('MENU_PREVIEW', 'Преглед');
define('MENU_RENAME', 'Преименуй');
define('MENU_EDIT', 'Редактирай');
define('MENU_CUT', 'Отрежи');
define('MENU_COPY', 'Копирай');
define('MENU_DELETE', 'Изтрий');
define('MENU_PLAY', 'Свири');
define('MENU_PASTE', 'Поставй');
//Label 
//Top Action 
define('LBL_ACTION_REFRESH', 'Опресни');
define('LBL_ACTION_DELETE', 'Изтрий');
define('LBL_ACTION_CUT', 'Отрежи');
define('LBL_ACTION_COPY', 'Копирай');
define('LBL_ACTION_PASTE', 'Постави');
define('LBL_ACTION_CLOSE', 'Затвори');
define('LBL_ACTION_SELECT_ALL', 'Избери всички');
//File Listing 
define('LBL_NAME', 'Име');
define('LBL_SIZE', 'Размер');
define('LBL_MODIFIED', 'Към Промяна');
//File Information 
define('LBL_FILE_INFO', 'Информация за файла:');
define('LBL_FILE_NAME', 'Име:');
define('LBL_FILE_CREATED', 'Създаден:');
define('LBL_FILE_MODIFIED', 'Променен:');
define('LBL_FILE_SIZE', 'Размер:');
define('LBL_FILE_TYPE', 'Тип файл:');
define('LBL_FILE_WRITABLE', 'За запис?');
define('LBL_FILE_READABLE', 'За четене?');
//Folder Information 
define('LBL_FOLDER_INFO', 'Информация за папка');
define('LBL_FOLDER_PATH', 'Папка:');
define('LBL_CURRENT_FOLDER_PATH', 'Път към текущата папка :');
define('LBL_FOLDER_CREATED', 'Създаден:');
define('LBL_FOLDER_MODIFIED', 'Променен:');
define('LBL_FOLDER_SUDDIR', 'Подпапки:');
define('LBL_FOLDER_FIELS', 'Файл:');
define('LBL_FOLDER_WRITABLE', 'За запис');
define('LBL_FOLDER_READABLE', 'За четене');
define('LBL_FOLDER_ROOT', 'Главна папка');
//Preview 
define('LBL_PREVIEW', 'Преглед');
define('LBL_CLICK_PREVIEW', 'Щракнете тук, за преглед.');
//Buttons 
define('LBL_BTN_SELECT', 'Избор');
define('LBL_BTN_CANCEL', 'Отказ');
define('LBL_BTN_UPLOAD', 'Качи на сървъра');
define('LBL_BTN_CREATE', 'Създй');
define('LBL_BTN_CLOSE', 'Затвори');
define('LBL_BTN_NEW_FOLDER', 'Нова папка');
define('LBL_BTN_NEW_FILE', 'Нов файл');
define('LBL_BTN_EDIT_IMAGE', 'Редактирй');
define('LBL_BTN_VIEW', 'Изберете Преглед');
define('LBL_BTN_VIEW_TEXT', 'Текст');
define('LBL_BTN_VIEW_DETAILS', 'Детайли');
define('LBL_BTN_VIEW_THUMBNAIL', 'Кадри');
define('LBL_BTN_VIEW_OPTIONS', 'Вижте в:');
//pagination 
define('PAGINATION_NEXT', 'Следваща');
define('PAGINATION_PREVIOUS', 'Назад');
define('PAGINATION_LAST', 'Последните');
define('PAGINATION_FIRST', 'Първа');
define('PAGINATION_ITEMS_PER_PAGE', 'Покажи %s елементи на страница');
define('PAGINATION_GO_PARENT', 'Върни към родителската папка');
//System 
define('SYS_DISABLED', 'Разрешение отказано: Системата е изключена.');
//Cut 
define('ERR_NOT_DOC_SELECTED_FOR_CUT', 'Няма избран документ (и) за разрязване.');
//Copy 
define('ERR_NOT_DOC_SELECTED_FOR_COPY', 'Няма избран документ (и) за копиране.');
//Paste 
define('ERR_NOT_DOC_SELECTED_FOR_PASTE', 'Няма избран документ (и) за заместване.');
define('WARNING_CUT_PASTE', 'Сигурни ли сте?');
define('WARNING_COPY_PASTE', 'Сигурни ли сте, за копирането на избраните документи в текущата папка?');
define('ERR_NOT_DEST_FOLDER_SPECIFIED', 'Не е посочена папка.');
define('ERR_DEST_FOLDER_NOT_FOUND', 'Папката не е намерена.');
define('ERR_DEST_FOLDER_NOT_ALLOWED', 'Вие нямате права за преместване на файлове в тази папка');
define('ERR_UNABLE_TO_MOVE_TO_SAME_DEST', 'Неуспех да преместите файла (%s): Оригиналният път е като дестинацията.');
define('ERR_UNABLE_TO_MOVE_NOT_FOUND', 'Неуспех да преместите файла (%s): файла не съществува.');
define('ERR_UNABLE_TO_MOVE_NOT_ALLOWED', 'Неуспех да преместите файла (%s): отказан достъп до файла.');

define('ERR_NOT_FILES_PASTED', 'Файла не е заместен.');

//Search 
define('LBL_SEARCH', 'Търсене');
define('LBL_SEARCH_NAME', 'Пълна / Част от име на файл:');
define('LBL_SEARCH_FOLDER', 'Погледнете в:');
define('LBL_SEARCH_QUICK', 'Бързо търсене');
define('LBL_SEARCH_MTIME', 'Дата на файла (диапазон ):');
define('LBL_SEARCH_SIZE', 'Размер на файла:');
define('LBL_SEARCH_ADV_OPTIONS', 'Разширени опции');
define('LBL_SEARCH_FILE_TYPES', 'Типове файлове:');
define('SEARCH_TYPE_EXE', 'Прилагане');
define('SEARCH_TYPE_IMG', 'Снимка');
define('SEARCH_TYPE_ARCHIVE', 'Архив');
define('SEARCH_TYPE_HTML', 'HTML');
define('SEARCH_TYPE_VIDEO', 'Видео');
define('SEARCH_TYPE_MOVIE', 'Филм');
define('SEARCH_TYPE_MUSIC', 'Музика');
define('SEARCH_TYPE_FLASH', 'Светкавица');
define('SEARCH_TYPE_PPT', 'PowerPoint');
define('SEARCH_TYPE_DOC', 'Документ');
define('SEARCH_TYPE_WORD', 'Дума');
define('SEARCH_TYPE_PDF', 'PDF');
define('SEARCH_TYPE_EXCEL', 'Excel');
define('SEARCH_TYPE_TEXT', 'Текст');
define('SEARCH_TYPE_XML', 'XML');
define('SEARCH_ALL_FILE_TYPES', 'Всички типове файлове');
define('LBL_SEARCH_RECURSIVELY', 'Търси и в подпапки:');
define('LBL_RECURSIVELY_YES', 'Да'); 
define('LBL_RECURSIVELY_NO', 'Не');
define('BTN_SEARCH', 'Търси сега');
//thickbox 
define('THICKBOX_NEXT', 'Следващ>');
define('THICKBOX_PREVIOUS', '<Предишен');
define('THICKBOX_CLOSE', 'Затвори');
//Calendar 
define('CALENDAR_CLOSE', 'Затвори');
define('CALENDAR_CLEAR', 'Изчисти');
define('CALENDAR_PREVIOUS', '<Предишен');
define('CALENDAR_NEXT', 'Следващ>');
define('CALENDAR_CURRENT', 'Текущ');
define('CALENDAR_MON', 'Пн');
define('CALENDAR_TUE', 'Вт');
define('CALENDAR_WED', 'Ср');
define('CALENDAR_THU', 'Чет');
define('CALENDAR_FRI', 'Пет');
define('CALENDAR_SAT', 'Съб');
define('CALENDAR_SUN', 'Нед');
define('CALENDAR_JAN', 'Ян');
define('CALENDAR_FEB', 'Фев');
define('CALENDAR_MAR', 'Мар');
define('CALENDAR_APR', 'Апр');
define('CALENDAR_MAY', 'Май');
define('CALENDAR_JUN', 'Юни');
define('CALENDAR_JUL', 'Юли');
define('CALENDAR_AUG', 'Авг');
define('CALENDAR_SEP', 'Сеп');
define('CALENDAR_OCT', 'Окт');
define('CALENDAR_NOV', 'Ное');
define('CALENDAR_DEC', 'Дек');
//ERROR MESSAGES 
//deletion 
define('ERR_NOT_FILE_SELECTED', 'Моля, изберете файл.');
define('ERR_NOT_DOC_SELECTED', 'Няма избран документ (и).');
define('ERR_DELTED_FAILED', 'Невъзможност да се изтрият избраните документ (и ).');
define('ERR_FOLDER_PATH_NOT_ALLOWED', ' Пътят към папката не е позволен.');
//class manager 
define('ERR_FOLDER_NOT_FOUND', 'Папката не е намерена:');
//rename 
define('ERR_RENAME_FORMAT', 'Въведете име, което да съдържа само букви, цифри, интервал, тире и подчертаване.');
define('ERR_RENAME_EXISTS', 'Името се повтаря. Въведете име, което е уникално в рамките на тази папка.');
define('ERR_RENAME_FILE_NOT_EXISTS', 'Този файл / папка не съществува.');
define('ERR_RENAME_FAILED', 'Не може да го преименувате, моля, опитайте отново.');
define('ERR_RENAME_EMPTY', 'Въведете име.');
define('ERR_NO_CHANGES_MADE', 'Няма извършени промени.');
define('ERR_RENAME_FILE_TYPE_NOT_PERMITED', 'Вие нямате права за промяна на име на файл с такова разширение.');
//folder creation 
define('ERR_FOLDER_FORMAT', 'Въведете име, което да съдържа само букви, цифри, интервал, тире и подчертаване.');
define('ERR_FOLDER_EXISTS', 'Името се повтаря. Въведете име, което е уникално.');
define('ERR_FOLDER_CREATION_FAILED', 'Невъзможно е да се създаде папка, моля, опитайте отново.');
define('ERR_FOLDER_NAME_EMPTY', 'Въведете име.');
define('FOLDER_FORM_TITLE', 'Нова папка');
define('FOLDER_LBL_TITLE', 'Папка Заглавие:');
define('FOLDER_LBL_CREATE', 'Създаване на папка');
//New File 
define('NEW_FILE_FORM_TITLE', 'Нов файл');
define('NEW_FILE_LBL_TITLE', 'Име на файла:');
define('NEW_FILE_CREATE', 'Създаване на файл');
//file upload 
define('ERR_FILE_NAME_FORMAT', 'Въведете име, което да съдържа само букви, цифри, интервал, тире и подчертаване.');
define('ERR_FILE_NOT_UPLOADED', 'Не е избран файл за качване.');
define('ERR_FILE_TYPE_NOT_ALLOWED', 'Не ви е позволено да качвате такъв тип файл.');
define('ERR_FILE_MOVE_FAILED', 'Неуспех при преместване на файл.');
define('ERR_FILE_NOT_AVAILABLE', 'Файлът е недостъпен.');
define('ERROR_FILE_TOO_BID', 'Файл твърде голям. (Макс:%s) ');
define('FILE_FORM_TITLE', 'Качване на файлове');
define('FILE_LABEL_SELECT', 'Избор на файл');
define('FILE_LBL_MORE', 'Добавяне на файл за качване');
define('FILE_CANCEL_UPLOAD', 'Отказ качване на файлове');
define('FILE_LBL_UPLOAD', 'Качване');
//file download 
define('ERR_DOWNLOAD_FILE_NOT_FOUND', 'Няма избрани файлове за изтегляне. ');
//Rename 
define('RENAME_FORM_TITLE', 'Преименуваи'); 
define('RENAME_NEW_NAME', 'Ново Име');
define('RENAME_LBL_RENAME', 'Преименуване');

//Tips 
define('TIP_FOLDER_GO_DOWN', 'Щракнете веднъж за да стигнем до тази папка ...');
define('TIP_DOC_RENAME', 'Щракнете два пъти да редактирате ...');
define('TIP_FOLDER_GO_UP', 'Щракнете веднъж за да стигнем до родителската папка ...');
define('TIP_SELECT_ALL', 'Избери всички');
define('TIP_UNSELECT_ALL', 'Премахнете всички');
//WARNING 
define('WARNING_DELETE', 'Сигурни ли сте, за изтриването');
define('WARNING_IMAGE_EDIT', 'Моля, изберете изображение за редактиране.');
define('WARNING_NOT_FILE_EDIT', 'Моля, изберете файла за редактиране.');
define('WARING_WINDOW_CLOSE', 'Сигурни ли сте, че искате за да затворите прозореца?');
//Preview 
define('PREVIEW_NOT_PREVIEW', 'Невъзможен преглед.');
define('PREVIEW_OPEN_FAILED', 'Невъзможно е да отворите файла.');
define('PREVIEW_IMAGE_LOAD_FAILED', 'Невъзможно зареждане на изображението');

//Login 
define('LOGIN_PAGE_TITLE', 'Аяакс файлов мениджър Форма за вход');
define('LOGIN_FORM_TITLE', 'Вход');
define('LOGIN_USERNAME', 'Потребител:');
define('LOGIN_PASSWORD', 'Парола:');
define('LOGIN_FAILED', 'Невалидно потребителско име / парола.');
//88888888888   Below for Image Editor   888888888888888888888 
//Warning 
define('IMG_WARNING_NO_CHANGE_BEFORE_SAVE', 'Няма промени в изображенията.');
//General 
define('IMG_GEN_IMG_NOT_EXISTS', 'Изображението не съществува');
define('IMG_WARNING_LOST_CHANAGES', 'Всички незапазени промени, направени в изображението ще се загубят, сигурни ли сте, че искате да продължите?');
define('IMG_WARNING_REST', 'Всички незапазени промени, направени в изображението ще бъдат загубени, Сигурни ли сте, че искате да продължите?');
define('IMG_WARNING_EMPTY_RESET', 'Няма промени до този момент на изображението');
define('IMG_WARING_WIN_CLOSE', 'Сигурни ли сте?');
define('IMG_WARNING_UNDO', 'Сигурни ли сте, че искате да възстановите предишното състояние на снимката?');
define('IMG_WARING_FLIP_H', 'Сигурни ли сте, че искате да завъртите изображението хоризонтално?');
define('IMG_WARING_FLIP_V', 'Сигурни ли сте, че искате да завъртите изображението вертикално');
define('IMG_INFO', 'Информация за изображението');
//Mode 
define('IMG_MODE_RESIZE', 'Промяна на размера >>');
define('IMG_MODE_CROP', 'Орязване>>');
define('IMG_MODE_ROTATE', 'Завъртане>>');
define('IMG_MODE_FLIP', 'Огледално>>');
//Button 
define('IMG_BTN_ROTATE_LEFT', '90 °наляво');
define('IMG_BTN_ROTATE_RIGHT', '90 ° надясно');
define('IMG_BTN_FLIP_H', 'Хоризонтално');
define('IMG_BTN_FLIP_V', 'Вертикално');
define('IMG_BTN_RESET', 'Изчисти');
define('IMG_BTN_UNDO', 'Отмяна');
define('IMG_BTN_SAVE', 'Запазване');
define('IMG_BTN_CLOSE', 'Затвори');
define('IMG_BTN_SAVE_AS', 'Запази Като');
define('IMG_BTN_CANCEL', 'Отказ');
//Checkbox 
define('IMG_CHECKBOX_CONSTRAINT', 'Съразмерно >>');
//Label 
define('IMG_LBL_WIDTH', 'Ширина:');
define('IMG_LBL_HEIGHT', 'Височина:');
define('IMG_LBL_X', 'X:');
define('IMG_LBL_Y', 'Y:');
define('IMG_LBL_RATIO', 'Система:');
define('IMG_LBL_ANGLE', 'Ъгъл:');
define('IMG_LBL_NEW_NAME', 'Ново име:');
define('IMG_LBL_SAVE_AS', 'Запази като');
define('IMG_LBL_SAVE_TO', 'Запазване в:');
define('IMG_LBL_ROOT_FOLDER', 'Главната папка');
//Editor 
//Save as 
define('IMG_NEW_NAME_COMMENTS', 'Моля, не поставяйте разширение на името.');
define('IMG_SAVE_AS_ERR_NAME_INVALID', 'Въведете име, което да съдържа само букви, цифри, интервал, тире и подчертаване.');
define('IMG_SAVE_AS_NOT_FOLDER_SELECTED', 'Няма избрана папка за зпзване като.');
define('IMG_SAVE_AS_FOLDER_NOT_FOUND', 'Папка не съществува.');
define('IMG_SAVE_AS_NEW_IMAGE_EXISTS', 'Съществува изображение със същото име.');

//Save 
define('IMG_SAVE_EMPTY_PATH', 'Няма път към изображението.');
define('IMG_SAVE_NOT_EXISTS', 'Изображението не съществува.');
define('IMG_SAVE_PATH_DISALLOWED', 'Вие нямате разрешение за достъп до този файл.');
define('IMG_SAVE_UNKNOWN_MODE', 'Неочаквана грешка');
define('IMG_SAVE_RESIZE_FAILED', 'Неуспех да промените размера на изображението.');
define('IMG_SAVE_CROP_FAILED', 'Неуспех.');
define('IMG_SAVE_FAILED', 'Неуспех да съхраните изображението.');
define('IMG_SAVE_BACKUP_FAILED', 'Невъзможно е да архивирате изображението.');
define('IMG_SAVE_ROTATE_FAILED', 'Невъзможна промяна на изображението.');
define('IMG_SAVE_FLIP_FAILED', 'Невъзможна промяна на изображението.');
define('IMG_SAVE_SESSION_IMG_OPEN_FAILED', 'Невъзможно отваряне на изображение.');
define('IMG_SAVE_IMG_OPEN_FAILED', 'Невъзможно отваряне на изображение');
//UNDO 
define('IMG_UNDO_NO_HISTORY_AVAIALBE', 'Няма история.');
define('IMG_UNDO_COPY_FAILED', 'Невъзможно е да се направи.');
define('IMG_UNDO_DEL_FAILED', 'Невъзможно е да се направи.');
//88888888888   Above for Image Editor   888888888888888888888 
//88888888888   Session   888888888888888888888 
define('SESSION_PERSONAL_DIR_NOT_FOUND', 'Невъзможно е да се направи');
define('SESSION_COUNTER_FILE_CREATE_FAILED', 'Невъзможно е да се направи.');
define('SESSION_COUNTER_FILE_WRITE_FAILED', 'Невъзможно е да се направи.');
//88888888888   Session   888888888888888888888 
//88888888888   Below for Text Editor   888888888888888888888 
define('TXT_FILE_NOT_FOUND', 'Файлът не е намерен.');
define('TXT_EXT_NOT_SELECTED', 'Моля, изберете файлово разширение');
define('TXT_DEST_FOLDER_NOT_SELECTED', 'Моля, изберете папка');
define('TXT_UNKNOWN_REQUEST', 'Неизвестна заявка.');
define('TXT_DISALLOWED_EXT', 'Вие нямате права да редактирате / добавяте такъв тип файл.');
define('TXT_FILE_EXIST', 'Такъв файл вече има.');
define('TXT_FILE_NOT_EXIST', 'Няма такъв  файл.');
define('TXT_CREATE_FAILED', 'Неуспех при създаване на нов файл.');
define('TXT_CONTENT_WRITE_FAILED', 'Неуспех при запис на съдържанието на файла.');
define('TXT_FILE_OPEN_FAILED', 'Неуспех при отварянето на файл.');
define('TXT_CONTENT_UPDATE_FAILED', 'Неуспех да се актуализира съдържанието на файла.');
define('TXT_SAVE_AS_ERR_NAME_INVALID', 'Въведете име, което да съдържа само букви, цифри, интервал, тире и подчертаване.');

	//88888888888   Above for Text Editor   888888888888888888888
	
	
?>