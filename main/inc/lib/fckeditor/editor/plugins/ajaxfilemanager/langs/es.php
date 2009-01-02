<?
	/**
	 * Archivo de idioma
	 * @autor PandaBoy (argoflo@hotmail.com)
	 * @desde 12/Agosto/2007
	 *
	 */
	define('DATE_TIME_FORMAT', 'd/M/Y H:i:s');
	//Etiquetas
		//Principales
		define('LBL_ACTION_REFRESH', 'Actualizar');
		define("LBL_ACTION_DELETE", 'Borrar');
		define('LBL_ACTION_CUT', 'Cortar');
		define('LBL_ACTION_COPY', 'Copiar');
		define('LBL_ACTION_PASTE', 'Pegar');
		define('LBL_ACTION_CLOSE', 'Cerrar');
		//Listado de archivos
	define('LBL_NAME', 'Nombre');
	define('LBL_SIZE', 'Tama&ntilde;o');
	define('LBL_MODIFIED', 'Modifiaci&oacute;n');
		//Informacion de archivos
	define('LBL_FILE_INFO', 'Informaci&oacute;n del archivo:');
	define('LBL_FILE_NAME', 'Nombre:');	
	define('LBL_FILE_CREATED', 'Creaci&oacute;n:');
	define("LBL_FILE_MODIFIED", 'Modificaci&oacute;n:');
	define("LBL_FILE_SIZE", 'Tama&ntilde;o de archivo:');
	define('LBL_FILE_TYPE', 'Tipo de archivo:');
	define("LBL_FILE_WRITABLE", 'Escritura?');
	define("LBL_FILE_READABLE", 'Lectura?');
		//Informacion de directorios
	define('LBL_FOLDER_INFO', 'Informaci&oacute;n de directorio');
	define("LBL_FOLDER_PATH", 'Ruta:');
	define("LBL_FOLDER_CREATED", 'Creaci&oacute;n:');
	define("LBL_FOLDER_MODIFIED", 'Modificaci&oacute;n:');
	define('LBL_FOLDER_SUDDIR', 'Subdirectorios:');
	define("LBL_FOLDER_FIELS", 'Archivos:');
	define("LBL_FOLDER_WRITABLE", 'Escritura?');
	define("LBL_FOLDER_READABLE", 'Lectura?');
		//Previsualizar
	define("LBL_PREVIEW", 'Previsualizar');
	define('LBL_CLICK_PREVIEW', 'Click aqui para previsualizar');
	//Botones
	//Botones
	define('LBL_BTN_SELECT', 'Seleccionar');
	define('LBL_BTN_CANCEL', 'Cancelar');
	define("LBL_BTN_UPLOAD", 'Publicar');
	define('LBL_BTN_CREATE', 'Crear');
	define('LBL_BTN_CLOSE', 'Cerrar');
	define("LBL_BTN_NEW_FOLDER", 'Nuevo directorio');
	define('LBL_BTN_EDIT_IMAGE', 'Editar');
	//Cortar
	define('ERR_NOT_DOC_SELECTED_FOR_CUT', 'No hay documentos seleccionados para cortar.');
	//Copiar
	define('ERR_NOT_DOC_SELECTED_FOR_COPY', 'No hay documentos seleccionados para copiar.');
	//Pegar
	define('ERR_NOT_DOC_SELECTED_FOR_PASTE', 'No hay documentos seleccionados para pegar.');
	define('WARNING_CUT_PASTE', 'Esta seguro que desea mover estos documentos a la carpeta actual?');
	define('WARNING_COPY_PASTE', 'Esta seguro que desea copiar estos documentos a la carpeta actual?');
	
	//MENSAJES DE ERROR
		//borrado
	define('ERR_NOT_FILE_SELECTED', 'Seleccione un archivo.');
	define('ERR_NOT_DOC_SELECTED', 'No se ha(n) seleccionado documento(s) para borrar.');
	define('ERR_DELTED_FAILED', 'No se puede borrar el documento seleccionado.');
	define('ERR_FOLDER_PATH_NOT_ALLOWED', 'Ruta de archivo no permitida.');
		//administrador de clases
	define("ERR_FOLDER_NOT_FOUND", 'No se puede encontrar el directorio especificado: ');
		//renombrar
	define('ERR_RENAME_FORMAT', 'El nombre s&oacute;lo debe contener letras, n&uacute;meros, espacios, guiones y guiones bajos.');
	define('ERR_RENAME_EXISTS', 'Debe establecer un nombre de archivo &uacute;nico (que no exista) en el directorio.');
	define('ERR_RENAME_FILE_NOT_EXISTS', 'El archivo/directorio no existe.');
	define('ERR_RENAME_FAILED', 'No se pudo renombrar, intente nuevamente.');
	define('ERR_RENAME_EMPTY', 'Debe establecer un nombre.');
	define("ERR_NO_CHANGES_MADE", 'No se han efectuado cambios.');
	define('ERR_RENAME_FILE_TYPE_NOT_PERMITED', 'No se puede establecer tal extensi&oacute;n.');
		//creacion de directorios
	define('ERR_FOLDER_FORMAT', 'El nombre s&oacute;lo debe contener letras, n&uacute;meros, espacios, guiones y guiones bajos.');
	define('ERR_FOLDER_EXISTS', 'Debe establecer un nombre de archivo &uacute;nico (que no exista) en el directorio.');
	define('ERR_FOLDER_CREATION_FAILED', 'No se pudo crear el directorio, intente nuevamente.');
	define('ERR_FOLDER_NAME_EMPTY', 'Debe establecer un nombre.');
	
		//carga de archivos
	define("ERR_FILE_NAME_FORMAT", 'El nombre s&oacute;lo debe contener letras, n&uacute;meros, espacios, guiones y guiones bajos.');
	define('ERR_FILE_NOT_UPLOADED', 'No se ha seleccionado ning&uacute;n archivo para publicaci&oacute;n.');
	define('ERR_FILE_TYPE_NOT_ALLOWED', 'No est&aacute; permitido publicar archivos de ese tipo.');
	define('ERR_FILE_MOVE_FAILED', 'Nos pudo mover el archivo.');
	define('ERR_FILE_NOT_AVAILABLE', 'Archivo no disponible.');
	define('ERROR_FILE_TOO_BID', 'El archivo es demasiado grande. (max: %s)');

		//descarga de archivos
	define('ERR_DOWNLOAD_FILE_NOT_FOUND', 'No hay documentos seleccionados para descargar.');
	
	//Trucos
	define('TIP_FOLDER_GO_DOWN', 'Haga Click para acceder al directorio...');
	define("TIP_DOC_RENAME", 'Haga doble Click para editar...');
	define('TIP_FOLDER_GO_UP', 'Haga Click para subir un directorio...');
	define("TIP_SELECT_ALL", 'Seleccione todo');
	define("TIP_UNSELECT_ALL", 'Deseleccione todo');
	//ADVERTENCIAS
	define('WARNING_DELETE', 'Esta seguro de borrar los archivos seleccionados?.');
	define('WARNING_IMAGE_EDIT', 'Por favor seleccione una imagen para editar.');
	define('WARING_WINDOW_CLOSE', 'Seguro que quiere cerrar la ventana?');
	//Previsualizar
	define('PREVIEW_NOT_PREVIEW', 'Vista previa no disponible.');
	define('PREVIEW_OPEN_FAILED', 'No se pudo abrir el archivo.');
	define('PREVIEW_IMAGE_LOAD_FAILED', 'No se pudo cargar la imagen');

	//Identificarse
	define('LOGIN_PAGE_TITLE', 'Ajax File Manager Login Form');
	define('LOGIN_FORM_TITLE', 'Login Form');
	define('LOGIN_USERNAME', 'Ususario:');
	define('LOGIN_PASSWORD', 'Contrase&ntilde;a:');
	define('LOGIN_FAILED', 'Usuario o contrase&ntilde;a invalida.'); 
	
	
	//88888888888   Abajo para el editor de imagenes   888888888888888888888
		//Advertencia 
		define('IMG_WARNING_NO_CHANGE_BEFORE_SAVE', "No ha hecho ningun cambio a la imagen.");
		
		//General
		define('IMG_GEN_IMG_NOT_EXISTS', 'La imagen no existe.');
		define('IMG_WARNING_LOST_CHANAGES', 'Todos los cambios hechos a la imagen que no hayan sido guardados se perderan, Seguro que desea continuar?');
		define('IMG_WARNING_REST', 'Todos los cambios hechos a la imagen que no hayan sido guardados se perderan, Seguro que desea resetear?');
		define('IMG_WARNING_EMPTY_RESET', 'No se han hecho cambios a la imagen.');
		define('IMG_WARING_WIN_CLOSE', 'Seguro que quiere cerrar la ventana?');
		define('IMG_WARNING_UNDO', 'Seguro que quiere restaurar la imagen a su estado previo?');
		define('IMG_WARING_FLIP_H', 'Seguro que desea voltear la imagen horizontalmente?');
		define('IMG_WARING_FLIP_V', 'Seguro que desea voltear la imagen verticalmente?');
		define('IMG_INFO', 'Informaci&oacute;n de la imagen');
		
		//Modo
			define('IMG_MODE_RESIZE', 'Redimensionar:');
			define('IMG_MODE_CROP', 'Cortar:');
			define('IMG_MODE_ROTATE', 'Rotar:');
			define('IMG_MODE_FLIP', 'Voltear:');		
		//Boton		
			define('IMG_BTN_ROTATE_LEFT', '90&deg;CCW');
			define('IMG_BTN_ROTATE_RIGHT', '90&deg;CW');
			define('IMG_BTN_FLIP_H', 'Voltear Horizontal');
			define('IMG_BTN_FLIP_V', 'Voltear Vertical');
			define('IMG_BTN_RESET', 'Resetear');
			define('IMG_BTN_UNDO', 'Deshacer');
			define('IMG_BTN_SAVE', 'Guardar');
			define('IMG_BTN_CLOSE', 'Cerrar');
			define('IMG_BTN_SAVE_AS', 'Guardar Como...');
			define('IMG_BTN_CANCEL', 'Cancelar');
		//Cuadro de Seleccion
			define('IMG_CHECKBOX_CONSTRAINT', 'Contraer?');
		//Etiqueta
			define('IMG_LBL_WIDTH', 'Ancho:');
			define('IMG_LBL_HEIGHT', 'Alto:');
			define('IMG_LBL_X', 'X:');
			define('IMG_LBL_Y', 'Y:');
			define('IMG_LBL_RATIO', 'Radio:');
			define('IMG_LBL_ANGLE', 'Angulo:');
			define('IMG_LBL_NEW_NAME', 'Nuevo Nombre:');
			define('IMG_LBL_SAVE_AS', 'Guardar Como Formulario');
			define('IMG_LBL_SAVE_TO', 'Guardar En:');
			define('IMG_LBL_ROOT_FOLDER', 'Directorio Principal');
		//Editor

		//Save as 
		define('IMG_NEW_NAME_COMMENTS', 'Por favor no escriba la extension de la imagen.');
		define('IMG_SAVE_AS_ERR_NAME_INVALID', 'El nombre debe contener solo letras, digitos, espacio, guion y guion bajo.');
		define('IMG_SAVE_AS_NOT_FOLDER_SELECTED', 'No ha seleccionado un directorio de destino.');	
		define('IMG_SAVE_AS_FOLDER_NOT_FOUND', 'El directorio de destino no existe.');
		define('IMG_SAVE_AS_NEW_IMAGE_EXISTS', 'Existe una imagen con el mismo nombre.');

			
		//Guardar
		define('IMG_SAVE_EMPTY_PATH', 'Ruta de imagen vacia.');
		define('IMG_SAVE_NOT_EXISTS', 'La imagen no existe.');
		define('IMG_SAVE_PATH_DISALLOWED', 'No tiene permiso para acceder a este archivo.');
		define('IMG_SAVE_UNKNOWN_MODE', 'Modo de operacion de la imagen no esperado');
		define('IMG_SAVE_RESIZE_FAILED', 'Error al redimensionar la imagen.');
		define('IMG_SAVE_CROP_FAILED', 'Error al cortar la imagen.');
		define('IMG_SAVE_FAILED', 'Error al guardar la imagen');
		define('IMG_SAVE_BACKUP_FAILED', 'No se puede hacer respaldo a la imagen.');
		define('IMG_SAVE_ROTATE_FAILED', 'No se puede rotar la imagen.');
		define('IMG_SAVE_FLIP_FAILED', 'No se puede voltear la iamgen.');
		define('IMG_SAVE_SESSION_IMG_OPEN_FAILED', 'No se puede abrir la imagen por sesion.');
		define('IMG_SAVE_IMG_OPEN_FAILED', 'No se puede abrir la imagen');
		
		//Deshacer
		define('IMG_UNDO_NO_HISTORY_AVAIALBE', 'No se puede deshacer.');
		define('IMG_UNDO_COPY_FAILED', 'No se puede restaurar la imagen.');
		define('IMG_UNDO_DEL_FAILED', 'No se puede borrar la imagen.');
	
	//88888888888   Arriba para el editor de imagenes   888888888888888888888
	
	//88888888888   Sesion   888888888888888888888
		define("SESSION_PERSONAL_DIR_NOT_FOUND", 'No se puede encontrar la carpeta indicada, la cual deberia haber sido creada bajo esta sesion.');
		define("SESSION_COUNTER_FILE_CREATE_FAILED", 'No se puede abrir el archivo de sesion.');
		define('SESSION_COUNTER_FILE_WRITE_FAILED', 'No se puede escribir en el archivo de sesion');
	//88888888888   Sesion   888888888888888888888
	
	//88888888888   Abajo para el editor de texto   888888888888888888888
		define('TXT_FILE_NOT_FOUND', 'Archivo no encontrado.');
		define('TXT_EXT_NOT_SELECTED', 'Por favor seleccone la extension del archivo');
		define('TXT_DEST_FOLDER_NOT_SELECTED', 'Por favor seleccione el directorio de destino');
		define('TXT_UNKNOWN_REQUEST', 'Requirimiento desconocido.');
		define('TXT_DISALLOWED_EXT', 'No le esta permitido editar/agregar este tipo de archivos.');
		define('TXT_FILE_EXIST', 'Este archivo ya existe.');
		define('TXT_FILE_NOT_EXIST', 'Archivo no encontrado.');
		define('TXT_CREATE_FAILED', 'Erro al intentar crear el nuevo archivo.');
		define('TXT_CONTENT_WRITE_FAILED', 'Error al escribir el contenido del archivo.');
		define('TXT_FILE_OPEN_FAILED', 'Error al abrir el archivo.');
		define('TXT_CONTENT_UPDATE_FAILED', 'Error al actualizar el contenido del archivo.');
		define('TXT_SAVE_AS_ERR_NAME_INVALID', 'El nombre debe contener solo letras, digitos, espacio, guion y guion bajo.');
	//88888888888   Arriba para el editor de texto   888888888888888888888
	
	
?>