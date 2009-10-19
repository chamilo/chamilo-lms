<?php
	/**
	 * language pack
	 * @author Juan Carlos Ra�a Trabado
	 * @since 19/January/2009
	 *
	 */
	define('DATE_TIME_FORMAT', 'd/M/Y H:i:s');
	//Common
	//Menu

	define('MENU_SELECT', 'Seleccionar');
	define('MENU_DOWNLOAD', 'Descargar');
	define('MENU_PREVIEW', 'Previsualizar');
	define('MENU_RENAME', 'Renombrar');
	define('MENU_EDIT', 'Editar');
	define('MENU_CUT', 'Cortar');
	define('MENU_COPY', 'Copiar');
	define('MENU_DELETE', 'Eliminar');
	define('MENU_PLAY', 'Ejecutar');
	define('MENU_PASTE', 'Pegar');

	//Label
		//Top Action
		define('LBL_ACTION_REFRESH', 'Actualizar');
		define('LBL_ACTION_DELETE', 'Eliminar');
		define('LBL_ACTION_CUT', 'Cortar');
		define('LBL_ACTION_COPY', 'Copiar');
		define('LBL_ACTION_PASTE', 'Pegar');
		define('LBL_ACTION_CLOSE', 'Cerrar');
		define('LBL_ACTION_SELECT_ALL', 'Seleccionar todo');
		//File Listing
	define('LBL_NAME', 'Nombre');
	define('LBL_SIZE', 'Tama&ntilde;o');
	define('LBL_MODIFIED', 'Modificado el');
		//File Information
	define('LBL_FILE_INFO', 'Informaci&oacute;n del archivo:');
	define('LBL_FILE_NAME', 'Nombre:');
	define('LBL_FILE_CREATED', 'Creado:');
	define('LBL_FILE_MODIFIED', 'Modificado:');
	define('LBL_FILE_SIZE', 'Tama&ntilde;o:');
	define('LBL_FILE_TYPE', 'Tipo:');
	define('LBL_FILE_WRITABLE', '&iquest;Escribible?');
	define('LBL_FILE_READABLE', '&iquest;Legible?');
		//Folder Information
	define('LBL_FOLDER_INFO', 'Informaci&oacute;n de la carpeta');
	define('LBL_FOLDER_PATH', 'Carpeta:');
	define('LBL_CURRENT_FOLDER_PATH', 'Situaci&oacute;n actual de la carpeta:');
	define('LBL_FOLDER_CREATED', 'Creada:');
	define('LBL_FOLDER_MODIFIED', 'Modificada:');
	define('LBL_FOLDER_SUDDIR', 'Subcarpetas:');
	define('LBL_FOLDER_FIELS', 'Archivos:');
	define('LBL_FOLDER_WRITABLE', '&iquest;Escribible?');
	define('LBL_FOLDER_READABLE', '&iquest;Legible?');
	define('LBL_FOLDER_ROOT', 'Carpeta principal');
		//Preview
	define('LBL_PREVIEW', 'Previsualizar');
	define('LBL_CLICK_PREVIEW', 'Haga clic aqu&iacute; para previsualizarlo.');
	//Buttons
	define('LBL_BTN_SELECT', 'Seleccionar');
	define('LBL_BTN_CANCEL', 'Cancelar');
	define('LBL_BTN_UPLOAD', 'Subir un archivo');
	define('LBL_BTN_CREATE', 'Crear');
	define('LBL_BTN_CLOSE', 'Cerrar');
	define('LBL_BTN_NEW_FOLDER', 'Nueva carpeta');
	define('LBL_BTN_NEW_FILE', 'Nuevo archivo');
	define('LBL_BTN_EDIT_IMAGE', 'Editar');
	define('LBL_BTN_VIEW', 'Seleccionar vista');
	define('LBL_BTN_VIEW_TEXT', 'Texto');
	define('LBL_BTN_VIEW_DETAILS', 'Lista de archivos');
	define('LBL_BTN_VIEW_THUMBNAIL', 'Vista en miniatura');
	define('LBL_BTN_VIEW_OPTIONS', 'Ver:');
	//pagination
	define('PAGINATION_NEXT', 'Siguiente');
	define('PAGINATION_PREVIOUS', 'Anterior');
	define('PAGINATION_LAST', 'Ultimo');
	define('PAGINATION_FIRST', 'Primero');
	define('PAGINATION_ITEMS_PER_PAGE', 'Mostrar %s elementos por p&aacute;gina');
	define('PAGINATION_GO_PARENT', 'Ir a la carpeta superior');
	//System
	define('SYS_DISABLED', 'Permiso denegado: el sistema est&aacute; deshabilitado.');

	//Cut
	define('ERR_NOT_DOC_SELECTED_FOR_CUT', 'No hay seleccionado un documento para cortar.');
	//Copy
	define('ERR_NOT_DOC_SELECTED_FOR_COPY', 'No hay seleccionado un documento para copiar.');
	//Paste
	define('ERR_NOT_DOC_SELECTED_FOR_PASTE', 'No hay seleccionado un documento para pegar.');
	define('WARNING_CUT_PASTE', 'Seguro que quiere mover los documentos seleccionados a la carpeta actual?');
	define('WARNING_COPY_PASTE', 'Seguro que quiere copiar los documentos seleccionados a la carpeta actual?');
	define('ERR_NOT_DEST_FOLDER_SPECIFIED', 'No se ha especificado una carpeta de destino.');
	define('ERR_DEST_FOLDER_NOT_FOUND', 'Carpeta de destino no encontrada.');
	define('ERR_DEST_FOLDER_NOT_ALLOWED', 'No tiene permiso para mover archivos a esta carpeta');
	define('ERR_UNABLE_TO_MOVE_TO_SAME_DEST', 'Error al mover el archivo (%s): El path de origen es el mismo que el de destino.');
	define('ERR_UNABLE_TO_MOVE_NOT_FOUND', 'Error al mover el archivo (%s): El archivo que quiere mover no existe.');
	define('ERR_UNABLE_TO_MOVE_NOT_ALLOWED', 'Error al mover el archivo (%s): Tiene denegado el acceso al archivo que quiere mover.');

	define('ERR_NOT_FILES_PASTED', 'Los archivos no han sido pegados.');

	//Search
	define('LBL_SEARCH', 'Buscar');
	define('LBL_SEARCH_NAME', 'Nombre de archivo (Completo/Parcial):');
	define('LBL_SEARCH_FOLDER', 'Buscar en:');
	define('LBL_SEARCH_QUICK', 'B&uacute;squeda r&aacute;pida');
	define('LBL_SEARCH_MTIME', 'Fecha de modificaci&oacute;n del archivo (Rango):');
	define('LBL_SEARCH_SIZE', 'Tama&ntilde;o del archivo:');
	define('LBL_SEARCH_ADV_OPTIONS', 'opciones avanzadas');
	define('LBL_SEARCH_FILE_TYPES', 'Tipos de archivo:');
	define('SEARCH_TYPE_EXE', 'Aplicaci&oacute;n');

	define('SEARCH_TYPE_IMG', 'Imagen');
	define('SEARCH_TYPE_ARCHIVE', 'Archivo');
	define('SEARCH_TYPE_HTML', 'HTML');
	define('SEARCH_TYPE_VIDEO', 'Video');
	define('SEARCH_TYPE_MOVIE', 'Pelicula');
	define('SEARCH_TYPE_MUSIC', 'Musica');
	define('SEARCH_TYPE_FLASH', 'Flash');
	define('SEARCH_TYPE_PPT', 'PowerPoint');
	define('SEARCH_TYPE_DOC', 'Documento');
	define('SEARCH_TYPE_WORD', 'Word');
	define('SEARCH_TYPE_PDF', 'PDF');
	define('SEARCH_TYPE_EXCEL', 'Excel');
	define('SEARCH_TYPE_TEXT', 'Texto');
	define('SEARCH_TYPE_UNKNOWN', 'Unknown');
	define('SEARCH_TYPE_XML', 'XML');
	define('SEARCH_ALL_FILE_TYPES', 'Todos los tipos de archivos');
	define('LBL_SEARCH_RECURSIVELY', 'Buscar en todos:');
	define('LBL_RECURSIVELY_YES', 'S&iacute;');
	define('LBL_RECURSIVELY_NO', 'No');
	define('BTN_SEARCH', 'Buscar');
	//thickbox
	define('THICKBOX_NEXT', 'Siguiente&gt;');
	define('THICKBOX_PREVIOUS', '&lt;Anterior');
	define('THICKBOX_CLOSE', 'Cerrar');
	//Calendar
	define('CALENDAR_CLOSE', 'Cerrar');
	define('CALENDAR_CLEAR', 'Despejar');
	define('CALENDAR_PREVIOUS', '&lt;Anterior');
	define('CALENDAR_NEXT', 'Siguiente&gt;');
	define('CALENDAR_CURRENT', 'Hoy');
	define('CALENDAR_MON', 'Lun');
	define('CALENDAR_TUE', 'Mar');
	define('CALENDAR_WED', 'Mie');
	define('CALENDAR_THU', 'Jue');
	define('CALENDAR_FRI', 'Vie');
	define('CALENDAR_SAT', 'Sab');
	define('CALENDAR_SUN', 'Dom');
	define('CALENDAR_JAN', 'Ene');
	define('CALENDAR_FEB', 'Feb');
	define('CALENDAR_MAR', 'Mar');
	define('CALENDAR_APR', 'Abr');
	define('CALENDAR_MAY', 'May');
	define('CALENDAR_JUN', 'Jun');
	define('CALENDAR_JUL', 'Jul');
	define('CALENDAR_AUG', 'Aug');
	define('CALENDAR_SEP', 'Sep');
	define('CALENDAR_OCT', 'Oct');
	define('CALENDAR_NOV', 'Nov');
	define('CALENDAR_DEC', 'Dec');
	//ERROR MESSAGES
		//deletion
	define('ERR_NOT_FILE_SELECTED', 'Por favor, seleccione un archivo.');
	define('ERR_NOT_DOC_SELECTED', 'Ho hay seleccionado un documento para eliminar.');
	define('ERR_DELTED_FAILED', 'No se pueden eliminar los documentos seleccionados.');
	define('ERR_FOLDER_PATH_NOT_ALLOWED', 'El path de la carpeta no est&aacute; permitido.');
		//class manager
	define('ERR_FOLDER_NOT_FOUND', 'No se ha podido localizar la carpeta especificada: ');
		//rename
	define('ERR_RENAME_FORMAT', 'Por favor, introduzca un nombre que solamente contenga letras, numeros, espacios, guiones o subrayado.');
	define('ERR_RENAME_EXISTS', 'Ya existe una carpeta con este nombre.');
	define('ERR_RENAME_FILE_NOT_EXISTS', 'El archivo/carpeta no existe.');
	define('ERR_RENAME_FAILED', 'No se puede renombrar, por favor int&eacute;ntelo de nuevo.');
	define('ERR_RENAME_EMPTY', 'Por favor, introduzca un nombre.');
	define('ERR_NO_CHANGES_MADE', 'No se han producido cambios.');
	define('ERR_RENAME_FILE_TYPE_NOT_PERMITED', 'No tiene permiso para cambiar este tipo de archivos.');
		//folder creation
	define('ERR_FOLDER_FORMAT', 'Por favor, introduzca un nombre que solamente contenga letras, numeros, espacios, guiones o subrayado.');
	define('ERR_FOLDER_EXISTS', 'ya existe una carpeta con este nombre.');
	define('ERR_FOLDER_CREATION_FAILED', 'No se puede crear la carpeta, por favor int&eacute;ntelo de nuevo.');
	define('ERR_FOLDER_NAME_EMPTY', 'Por favor, introduzca un nombre.');
	define('FOLDER_FORM_TITLE', 'Creaci&oacute;n de carpetas');
	define('FOLDER_LBL_TITLE', 'T&iacute;tulo:');
	define('FOLDER_LBL_CREATE', 'Crear carpeta');
	//New File
	define('NEW_FILE_FORM_TITLE', 'Formulario de Creaci&oacute;n de archivos');
	define('NEW_FILE_LBL_TITLE', 'Nombre de archivo:');
	define('NEW_FILE_CREATE', 'Crear archivo');
		//file upload
	define('ERR_FILE_NAME_FORMAT', 'Por favor, introduzca un nombre que solamente contenga letras, numeros, espacios, guiones o subrayado.');
	define('ERR_FILE_NOT_UPLOADED', 'No ha sido seleccionado un archivo para ser enviado.');
	define('ERR_FILE_TYPE_NOT_ALLOWED', 'No tiene permiso para enviar este tipo de archivos.');
	define('ERR_FILE_MOVE_FAILED', 'Error al mover el archivo.');
	define('ERR_FILE_NOT_AVAILABLE', 'El archivo no est&aacute; disponible.');
	define('ERROR_FILE_TOO_BID', 'Archivo demasiado largo. (max: %s)');
	define('FILE_FORM_TITLE', 'Env&iacute;o de archivos');
	define('FILE_LABEL_SELECT', 'Seleccionar:');
	define('FILE_LBL_MORE', 'A&ntilde;adir m&aacute;s archivos para enviar');
	define('FILE_CANCEL_UPLOAD', 'Cancelar el env&iacute;o del archivo');
	define('FILE_LBL_UPLOAD', 'Enviar');

	//file download
	define('ERR_DOWNLOAD_FILE_NOT_FOUND', 'No hay seleccionados archivos para descargar.');
	//Rename
	define('RENAME_FORM_TITLE', 'Formulario Renombrar');
	define('RENAME_NEW_NAME', 'Nuevo nombre');
	define('RENAME_LBL_RENAME', 'Renombrar');

	//Tips
	define('TIP_FOLDER_GO_DOWN', 'Un solo clic para ir a esta carpeta...');
	define('TIP_DOC_RENAME', 'Doble clic para editar...');
	define('TIP_FOLDER_GO_UP', 'Un solo clic para ir a la carpeta superior...');
	define('TIP_SELECT_ALL', 'Seleccionar todo');
	define('TIP_UNSELECT_ALL', 'No seleccionar todo');
	//WARNING
	define('WARNING_DELETE', 'Seguro que quiere eliminar los documentos seleccionados?');
	define('WARNING_IMAGE_EDIT', 'Por favor, seleccione una imagen para su edici&oacute;n.');
	define('WARNING_NOT_FILE_EDIT', 'por favor, seleccione un archivo para su edici&oacute;n.');
	define('WARING_WINDOW_CLOSE', 'Seguro que quiere cerrar la ventana?');
	//Preview
	define('PREVIEW_NOT_PREVIEW', 'No est&aacute; disponible la previsualizaci&oacute;n.');
	define('PREVIEW_OPEN_FAILED', 'No es posible abrir el archivo.');
	define('PREVIEW_IMAGE_LOAD_FAILED', 'No es posible cargar la imagen');

	//Login
	define('LOGIN_PAGE_TITLE', 'Formulario de autentificaci&oacute;n del gestor avanzado de ficheros');
	define('LOGIN_FORM_TITLE', 'Formulario de autentificaci&oacute;n');
	define('LOGIN_USERNAME', 'Nombre de usuario:');
	define('LOGIN_PASSWORD', 'Contrase&ntilde;a:');
	define('LOGIN_FAILED', 'Nombre de usuario o contrase&ntilde;a no v&aacute;lidos.');


	//88888888888   Below for Image Editor   888888888888888888888
		//Warning
		define('IMG_WARNING_NO_CHANGE_BEFORE_SAVE', 'No se han realizado cambios en la imagen.');

		//General
		define('IMG_GEN_IMG_NOT_EXISTS', 'La imagen no existe');
		define('IMG_WARNING_LOST_CHANAGES', 'Todos los cambios de la imagen que no se hayan guardado se perder&aacute;n. &iquest; Est&aacute; seguro de querer continuar?');
		define('IMG_WARNING_REST', 'Todos los cambios de la imagen que no se hayan guardado se perder&aacute;n. Seguro que quiere restaurar?');
		define('IMG_WARNING_EMPTY_RESET', 'Hasta ahora no se han realizado cambios en la imagen');
		define('IMG_WARING_WIN_CLOSE', 'Seguro que quiere cerrar la ventana?');
		define('IMG_WARNING_UNDO', 'Seguro que quiere restaurar la imagen al estado anterior?');
		define('IMG_WARING_FLIP_H', 'Seguro que quiere voltear horizontalmente la imagen?');
		define('IMG_WARING_FLIP_V', 'Seguro que quiere voltear verticalmente la imagen?');
		define('IMG_INFO', 'Informaci&oacute;n de la imagen');

		//Mode
			define('IMG_MODE_RESIZE', 'Cambiar tama&ntilde;o:');
			define('IMG_MODE_CROP', 'Recortar:');
			define('IMG_MODE_ROTATE', 'Rotar:');
			define('IMG_MODE_FLIP', 'Voltear:');
		//Button

			define('IMG_BTN_ROTATE_LEFT', '90&deg;CCW');
			define('IMG_BTN_ROTATE_RIGHT', '90&deg;CW');
			define('IMG_BTN_FLIP_H', 'Voltear horizontal');
			define('IMG_BTN_FLIP_V', 'Voltear vertical');
			define('IMG_BTN_RESET', 'Restaurar');
			define('IMG_BTN_UNDO', 'Deshacer');
			define('IMG_BTN_SAVE', 'Guardar');
			define('IMG_BTN_CLOSE', 'Cerrar');
			define('IMG_BTN_SAVE_AS', 'Guardar como');
			define('IMG_BTN_CANCEL', 'Cancelar');
		//Checkbox
			define('IMG_CHECKBOX_CONSTRAINT', '&iquest;Limitar?');
		//Label
			define('IMG_LBL_WIDTH', 'Ancho:');
			define('IMG_LBL_HEIGHT', 'Alto:');
			define('IMG_LBL_X', 'X:');
			define('IMG_LBL_Y', 'Y:');
			define('IMG_LBL_RATIO', 'Ratio:');
			define('IMG_LBL_ANGLE', 'Angulo:');
			define('IMG_LBL_NEW_NAME', 'Nuevo nombre:');
			define('IMG_LBL_SAVE_AS', 'Formulario guardar como');
			define('IMG_LBL_SAVE_TO', 'Guardar en:');
			define('IMG_LBL_ROOT_FOLDER', 'Carpeta principal');
		//Editor
		//Save as
		define('IMG_NEW_NAME_COMMENTS', 'Por favor, no incluya la extensi&oacute;n de la imagen.');
		define('IMG_SAVE_AS_ERR_NAME_INVALID', 'Por favor, introduzca un nombre que solamente contenga letras, numeros, espacios, guiones o subrayado.');
		define('IMG_SAVE_AS_NOT_FOLDER_SELECTED', 'No hay seleccionado una carpeta de destino.');
		define('IMG_SAVE_AS_FOLDER_NOT_FOUND', 'La carpeta de destino no existe.');
		define('IMG_SAVE_AS_NEW_IMAGE_EXISTS', 'Ya existe una imagen con el mismo nombre.');

		//Save
		define('IMG_SAVE_EMPTY_PATH', 'Path de imagen vac&iacute;o.');
		define('IMG_SAVE_NOT_EXISTS', 'la imagen no existe.');
		define('IMG_SAVE_PATH_DISALLOWED', 'No tiene permiso para acceder a este archivo.');
		define('IMG_SAVE_UNKNOWN_MODE', 'Modo de imagen desconocido');
		define('IMG_SAVE_RESIZE_FAILED', 'Error al cambiar de tama&ntilde;o la imagen.');
		define('IMG_SAVE_CROP_FAILED', 'Error al recortar la imagen.');
		define('IMG_SAVE_FAILED', 'Error al guardar la imagen.');
		define('IMG_SAVE_BACKUP_FAILED', 'No se puede hacer backup con la imagen original.');
		define('IMG_SAVE_ROTATE_FAILED', 'No se puede rotar la imagen.');
		define('IMG_SAVE_FLIP_FAILED', 'No se puede voltear la imagen.');
		define('IMG_SAVE_SESSION_IMG_OPEN_FAILED', 'No  se puede abrir la imagen desde la sesi&oacute;n.');
		define('IMG_SAVE_IMG_OPEN_FAILED', 'No se puede abrir la imagen');


		//UNDO
		define('IMG_UNDO_NO_HISTORY_AVAIALBE', 'No hay cambios que deshacer.');
		define('IMG_UNDO_COPY_FAILED', 'No se puede restaurar la imagen.');
		define('IMG_UNDO_DEL_FAILED', 'No se puede eliminar la imagen de sesi&oacute;n');

	//88888888888   Above for Image Editor   888888888888888888888

	//88888888888   Session   888888888888888888888
		define('SESSION_PERSONAL_DIR_NOT_FOUND', 'No se puede encontrar la carpeta dedicada que deber&iacute;a haber sido creada bajo la carpeta de sesi&oacute;n');
		define('SESSION_COUNTER_FILE_CREATE_FAILED', 'No se puede abrir un archivo contador de sesi&oacute;n.');
		define('SESSION_COUNTER_FILE_WRITE_FAILED', 'No se puede escribir el archivo contador de sesi&oacute;n.');
	//88888888888   Session   888888888888888888888

	//88888888888   Below for Text Editor   888888888888888888888
		define('TXT_FILE_NOT_FOUND', 'El archivo no ha sido encontrado.');
		define('TXT_EXT_NOT_SELECTED', 'Por favor, seleccione una extensi&oacute;n de archivo');
		define('TXT_DEST_FOLDER_NOT_SELECTED', 'Por favor, seleccione una carpeta de destino');
		define('TXT_UNKNOWN_REQUEST', 'Petici&oacute;n desconocida.');
		define('TXT_DISALLOWED_EXT', 'Tiene permiso para editar/a&ntilde; este tipo de archivos.');
		define('TXT_FILE_EXIST', 'El archivo ya existe.');
		define('TXT_FILE_NOT_EXIST', 'No ha sido encontrado ninguno.');
		define('TXT_CREATE_FAILED', 'Error al crear un nuevo archivo.');
		define('TXT_CONTENT_WRITE_FAILED', 'Error al escribir contenidos en el archivo.');
		define('TXT_FILE_OPEN_FAILED', 'Error al abrir el archivo.');
		define('TXT_CONTENT_UPDATE_FAILED', 'Error al actualizar el contenido del archivo.');
		define('TXT_SAVE_AS_ERR_NAME_INVALID', 'Por favor, introduzca un nombre que solamente contenga letras, numeros, espacios, guiones o subrayado.');
	//88888888888   Above for Text Editor   888888888888888888888


?>