<?
	/**
	 * sysem base config setting
	 * @Arabic language Translation
	 * @By Hassan GHazy (hassan_ghazy31@yahoo.com)
	 * @link www.teqanygroup.net
	 * @04/April/2008
	 */
	define('DATE_TIME_FORMAT', 'd/M/Y h:m:s');
	//Label
		//Top Action
		define('LBL_ACTION_REFRESH', 'تحديث');
		define("LBL_ACTION_DELETE", 'حذف');
		//File Listing
	define('LBL_NAME', 'الاسم');
	define('LBL_SIZE', 'حجم');
	define('LBL_MODIFIED', 'تعديل');
		//File Information
	define('LBL_FILE_INFO', 'معلومات الملف');
	define('LBL_FILE_NAME', 'اسم الملف :');	
	define('LBL_FILE_CREATED', 'الأنشاء :');
	define("LBL_FILE_MODIFIED", 'اخر تعديل :');
	define("LBL_FILE_SIZE", 'حجم الملف :');
	define('LBL_FILE_TYPE', 'نوع الملف :');
	define("LBL_FILE_WRITABLE", 'قابل للكتابة ؟');
	define("LBL_FILE_READABLE", 'قابل للقراءة ؟');
		//Folder Information
	define('LBL_FOLDER_INFO', 'معلومات المجلد');
	define("LBL_FOLDER_PATH", 'مسار المجلد :');
	define("LBL_FOLDER_CREATED", 'انشاء المجلد :');
	define("LBL_FOLDER_MODIFIED", 'تعديل المجلد :');
	define('LBL_FOLDER_SUDDIR', 'مجلد فرعي');
	define("LBL_FOLDER_FIELS", 'ملف :');
	define("LBL_FOLDER_WRITABLE", 'قابل للطباعة ؟');
	define("LBL_FOLDER_READABLE", 'قابل للقراءة');
		//Preview
	define("LBL_PREVIEW", 'معاينة');
	//Boutons
	define('LBL_BTN_SELECT', 'اختيار');
	define('LBL_BTN_CANCEL', 'الغاء');
	define("LBL_BTN_UPLOAD", 'رفع');
	define('LBL_BTN_CREATE', 'أنشاء');
	define("LBL_BTN_NEW_FOLDER", 'مجلد جديد');
	//ERROR MESSAGES
		//deletion
	define('ERR_NOT_FILE_SELECTED', 'الرجاء اختيار الملف.');
	define('ERR_NOT_DOC_SELECTED', 'لم يتم اختيار اي ملف.');
	define('ERR_DELTED_FAILED', 'غير قادر على مسح الملف المختار.');
	define('ERR_FOLDER_PATH_NOT_ALLOWED', 'مسار المجلد غير مسموح به');
		//class manager
	define("ERR_FOLDER_NOT_FOUND", 'هذا المجلد غير موجود : ');
		//rename
	define('ERR_RENAME_FORMAT', 'يجب ان يحتوي الاسم على احرف , ارقام , فراغ , . , _ فقط');
	define('ERR_RENAME_EXISTS', 'يجب عليك اختيار اسم غير موجود في المجلد.');
	define('ERR_RENAME_FILE_NOT_EXISTS', 'الملف \ المجلد غير موجود.');
	define('ERR_RENAME_FAILED', 'لم يتم التغيير , الرجاء المحاولة مرة اخرى.');
	define('ERR_RENAME_EMPTY', 'يجب عليك كتابة الاسم.');
	define("ERR_NO_CHANGES_MADE", 'لم يتم الغيير.');
	define('ERR_RENAME_FILE_TYPE_NOT_PERMITED', 'خطا في لاحقة الملف.');
		//folder creation
	define('ERR_FOLDER_FORMAT', 'يجب ان يحتوي الاسم على احرف , ارقام , فراغ , . , _ فقط');
	define('ERR_FOLDER_EXISTS', 'يجب عليك اختيار اسم غير موجود في المجلد.');
	define('ERR_FOLDER_CREATION_FAILED', 'لم يتم الأنشاء , الرجاء المحاولة مرة اخرى.');
	define('ERR_FOLDER_NAME_EMPTY', 'يجب عليك كتابة الاسم.');
	
		//file upload
	define("ERR_FILE_NAME_FORMAT", 'يجب ان يحتوي الاسم على احرف , ارقام , فراغ , . , _ فقط');
	define('ERR_FILE_NOT_UPLOADED', 'لم يتم اختيار اي ملف , الرجاء المحازلة مرة اخرى');
	define('ERR_FILE_TYPE_NOT_ALLOWED', 'خطا : نوع الملف غير مسموح');
	define('ERR_FILE_MOVE_FAILED', 'يمكننا ان نقو بتحريك الملف');
	define('ERR_FILE_NOT_AVAILABLE', 'الملف غير متوفر');
	define('ERROR_FILE_TOO_BID', 'حجم الملف كبير جدا , (اقصى حجم : %s )');
	

	//Tips
	define('TIP_FOLDER_GO_DOWN', ' اضغط للدخول الى المجلد...');
	define("TIP_DOC_RENAME", 'اضغظ مرتين للتغيير...');
	define('TIP_FOLDER_GO_UP', 'اضغط للصعود مستوى واحد');
	define("TIP_SELECT_ALL", 'تحديد الكل');
	define("TIP_UNSELECT_ALL", 'الغاء تحديد الكل');
	//WARNING
	define('WARNING_DELETE', 'هل انت متأكد في حذف الملفات ؟');
	//Preview
	define('PREVIEW_NOT_PREVIEW', 'المعاينة غير متوفرة');
	define('PREVIEW_OPEN_FAILED', 'غير قادر على فتح الملف');
	define('PREVIEW_IMAGE_LOAD_FAILED', 'غير قادر على تحميل الصورة');

	//Login
	define('LOGIN_PAGE_TITLE', 'مدير ملفات نوع اجاكس');
	define('LOGIN_FORM_TITLE', 'تسجيل الدخول');
	define('LOGIN_USERNAME', 'أسم المستخدم:');
	define('LOGIN_PASSWORD', 'كلمة المرور :');
	define('LOGIN_FAILED', 'اسم مستخدم \ كلمة مرور خطا!'); 
    	
	
?>