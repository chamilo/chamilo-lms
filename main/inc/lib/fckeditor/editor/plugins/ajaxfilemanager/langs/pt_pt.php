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
		define('LBL_ACTION_REFRESH', 'Recarregar');
		define("LBL_ACTION_DELETE", 'Apagar');
		define('LBL_ACTION_CUT', 'Cortar');
		define('LBL_ACTION_COPY', 'Copiar');
		define('LBL_ACTION_PASTE', 'Colar');
		define('LBL_ACTION_CLOSE', 'Fechar');
		//File Listing
	define('LBL_NAME', 'Nome');
	define('LBL_SIZE', 'Tamanho');
	define('LBL_MODIFIED', 'Modificado em');
		//File Information
	define('LBL_FILE_INFO', 'Informações do ficheiro:');
	define('LBL_FILE_NAME', 'Nome:');
	define('LBL_FILE_CREATED', 'Criado em:');
	define("LBL_FILE_MODIFIED", 'Modificado em:');
	define("LBL_FILE_SIZE", 'Tamanho:');
	define('LBL_FILE_TYPE', 'Tipo:');
	define("LBL_FILE_WRITABLE", 'Escrita?');
	define("LBL_FILE_READABLE", 'Leitura?');
		//Folder Information
	define('LBL_FOLDER_INFO', 'Informações da pasta:');
	define("LBL_FOLDER_PATH", 'Caminho:');
	define("LBL_FOLDER_CREATED", 'Criado em:');
	define("LBL_FOLDER_MODIFIED", 'Modificado em:');
	define('LBL_FOLDER_SUDDIR', 'Sub-pastas:');
	define("LBL_FOLDER_FIELS", 'Ficheiros:');
	define("LBL_FOLDER_WRITABLE", 'Escrita?');
	define("LBL_FOLDER_READABLE", 'Leitura?');
		//Preview
	define("LBL_PREVIEW", 'Pré-visualizar');
	define('LBL_CLICK_PREVIEW', 'Carregue aqui para pré-visualizar.');
	//Buttons
	define('LBL_BTN_SELECT', 'Escolher');
	define('LBL_BTN_CANCEL', 'Cancelar');
	define("LBL_BTN_UPLOAD", 'Enviar');
	define('LBL_BTN_CREATE', 'Criar');
	define('LBL_BTN_CLOSE', 'Fechar');
	define("LBL_BTN_NEW_FOLDER", 'Nova pasta');
	define('LBL_BTN_EDIT_IMAGE', 'Editar');
	//Cut
	define('ERR_NOT_DOC_SELECTED_FOR_CUT', 'Não foram seleccionado(s) documento(s) para cortar.');
	//Copy
	define('ERR_NOT_DOC_SELECTED_FOR_COPY', 'Não foram seleccionado(s) documento(s) para copiar.');
	//Paste
	define('ERR_NOT_DOC_SELECTED_FOR_PASTE', 'Não foram seleccionado(s) documento(s) para colar.');
	define('WARNING_CUT_PASTE', 'Tem a certeza que pretende mover os ficheiros seleccionados para a pasta actual?');
	define('WARNING_COPY_PASTE', 'Tem a certeza que pretende copiar os ficheiros seleccionados para a pasta actual?');

	//ERROR MESSAGES
		//deletion
	define('ERR_NOT_FILE_SELECTED', 'Por favor, escolha um ficheiro.');
	define('ERR_NOT_DOC_SELECTED', 'Não foram seleccionado(s) documento(s) para apagar.');
	define('ERR_DELTED_FAILED', 'Não foi possível apagar o(s) documento(s) seleccionado(s).');
	define('ERR_FOLDER_PATH_NOT_ALLOWED', 'O caminho da pasta não é permitido.');
		//class manager
	define("ERR_FOLDER_NOT_FOUND", 'Não foi possível localizar a pasta: ');
		//rename
	define('ERR_RENAME_FORMAT', "Por favor, escolha um nome que contenha apenas letras, dígitos, espaços, \'-\' e \'_\'.");
	define('ERR_RENAME_EXISTS', 'Por favor, escolha um nome que seja único nesta pasta.');
	define('ERR_RENAME_FILE_NOT_EXISTS', 'Esse ficheiro/pasta não existe.');
	define('ERR_RENAME_FAILED', 'Não foi possível renomear, tente novamente.');
	define('ERR_RENAME_EMPTY', 'Por favor, escolha um nome.');
	define("ERR_NO_CHANGES_MADE", 'Não foram feitas alterações.');
	define('ERR_RENAME_FILE_TYPE_NOT_PERMITED', 'Não pode alterar o ficheiro para essa extensão.');
		//folder creation
	define('ERR_FOLDER_FORMAT', "Por favor, escolha um nome que contenha apenas letras, dígitos, espaços, \'-\' e \'_\'.");
	define('ERR_FOLDER_EXISTS', 'Por favor, escolha um nome que seja único nesta pasta.');
	define('ERR_FOLDER_CREATION_FAILED', 'Não foi possível criar a pasta, tente novamente.');
	define('ERR_FOLDER_NAME_EMPTY', 'Por favor, escolha um nome.');

		//file upload
	define("ERR_FILE_NAME_FORMAT", "Por favor, escolha um nome que contenha apenas letras, dígitos, espaços, \'-\' e \'_\'.");
	define('ERR_FILE_NOT_UPLOADED', 'Não foi seleccionado nenhum ficheiro para ser enviado.');
	define('ERR_FILE_TYPE_NOT_ALLOWED', 'Não está autorizado a enviar esse tipo de ficheiros.');
	define('ERR_FILE_MOVE_FAILED', 'Ocorreu um erro ao mover o ficheiro.');
	define('ERR_FILE_NOT_AVAILABLE', 'O ficheiro não está disponível.');
	define('ERROR_FILE_TOO_BID', 'Ficheiro muito grande. (máx: %s)');
	//file download
	define('ERR_DOWNLOAD_FILE_NOT_FOUND', 'Não foram seleccionados ficheiros para download.');


	//Tips
	define('TIP_FOLDER_GO_DOWN', 'Clique para ir para esta pasta...');
	define("TIP_DOC_RENAME", 'Clique duas vezes para editar...');
	define('TIP_FOLDER_GO_UP', 'Clique para ir para a pasta acima...');
	define("TIP_SELECT_ALL", 'Seleccionar todos');
	define("TIP_UNSELECT_ALL", 'Deseleccionar todos');
	//WARNING
	define('WARNING_DELETE', 'Tem a certeza que quer apagar os ficheiros seleccionados.');
	define('WARNING_IMAGE_EDIT', 'Por favor, seleccione uma imagem para editar.');
	define('WARNING_NOT_FILE_EDIT', 'Por favor, seleccione um ficheiro para editar.');
	define('WARING_WINDOW_CLOSE', 'Tem a certeza que pretende fechar a janela?');
	//Preview
	define('PREVIEW_NOT_PREVIEW', 'Pré-visualização não disponível.');
	define('PREVIEW_OPEN_FAILED', 'Não foi possível abrir o ficheiro.');
	define('PREVIEW_IMAGE_LOAD_FAILED', 'Não foi possível carregar a imagem');

	//Login
	define('LOGIN_PAGE_TITLE', 'Formulário de login do Ajax File Manager');
	define('LOGIN_FORM_TITLE', 'Formulário de login');
	define('LOGIN_USERNAME', 'ID do utilizador:');
	define('LOGIN_PASSWORD', 'Password:');
	define('LOGIN_FAILED', ' ID do utilizador/password inválidos.');


	//88888888888   Below for Image Editor   888888888888888888888
		//Warning
		define('IMG_WARNING_NO_CHANGE_BEFORE_SAVE', "Não fez qualquer alteração à imagem.");

		//General
		define('IMG_GEN_IMG_NOT_EXISTS', 'A imagem não existe');
		define('IMG_WARNING_LOST_CHANAGES', 'Todas as alterações feitas á imagem que ainda não guardou, vão ser perdidas, tem a certeza que deseja continuar?');
		define('IMG_WARNING_REST', 'Todas as alterações feitas á imagem que ainda não guardou, vão ser perdidas, tem a certeza que deseja voltar ao estado inicial?');
		define('IMG_WARNING_EMPTY_RESET', 'Ainda não foram feitas quaisquer alterações à imagem');
		define('IMG_WARING_WIN_CLOSE', 'Tem a certeza que deseja fechar a janela?');
		define('IMG_WARNING_UNDO', 'Tem a certezz que pretende colocar a imagem no seu estado anterior?');
		define('IMG_WARING_FLIP_H', 'Tem a certeza que pretende virar a imagem horizontalmente?');
		define('IMG_WARING_FLIP_V', 'Tem a certeza que pretende virar a imagem verticalmente?');
		define('IMG_INFO', 'Informações da imagem');

		//Mode
			define('IMG_MODE_RESIZE', 'Redimensionar:');
			define('IMG_MODE_CROP', 'Recortar:');
			define('IMG_MODE_ROTATE', 'Rodar:');
			define('IMG_MODE_FLIP', 'Virar:');
		//Button

			define('IMG_BTN_ROTATE_LEFT', '90&graus;CCW');
			define('IMG_BTN_ROTATE_RIGHT', '90&graus;CW');
			define('IMG_BTN_FLIP_H', 'Virar horizontalmente');
			define('IMG_BTN_FLIP_V', 'Virar verticalmente');
			define('IMG_BTN_RESET', 'Voltar ao ínicio');
			define('IMG_BTN_UNDO', 'Anular');
			define('IMG_BTN_SAVE', 'Guardar');
			define('IMG_BTN_CLOSE', 'Fechar');
			define('IMG_BTN_SAVE_AS', 'Guardar como');
			define('IMG_BTN_CANCEL', 'Cancelar');
		//Checkbox
			define('IMG_CHECKBOX_CONSTRAINT', 'Constante?');
		//Label
			define('IMG_LBL_WIDTH', 'Largura:');
			define('IMG_LBL_HEIGHT', 'Altura:');
			define('IMG_LBL_X', 'X:');
			define('IMG_LBL_Y', 'Y:');
			define('IMG_LBL_RATIO', 'Percentagem:');
			define('IMG_LBL_ANGLE', 'Angulo:');
			define('IMG_LBL_NEW_NAME', 'Novo nome:');
			define('IMG_LBL_SAVE_AS', 'Save As Form');
			define('IMG_LBL_SAVE_TO', 'Guardar para:');
			define('IMG_LBL_ROOT_FOLDER', 'Pasta raíz');
		//Editor
		//Save as
		define('IMG_NEW_NAME_COMMENTS', 'Por favor, não coloque a extensão da imagem.');
		define('IMG_SAVE_AS_ERR_NAME_INVALID', "Por favor, escolha um nome que contenha apenas letras, dígitos, espaços, \'-\' e \'_\'.");
		define('IMG_SAVE_AS_NOT_FOLDER_SELECTED', 'Não foi escolhida a pasta de destino.');
		define('IMG_SAVE_AS_FOLDER_NOT_FOUND', 'A pasta de destino não existe.');
		define('IMG_SAVE_AS_NEW_IMAGE_EXISTS', 'Já existe uma imagem com esse nome.');

		//Save
		define('IMG_SAVE_EMPTY_PATH', 'O caminho da imagem está vazio.');
		define('IMG_SAVE_NOT_EXISTS', 'A imagem não existe.');
		define('IMG_SAVE_PATH_DISALLOWED', 'Não tem permissão para aceder a este ficheiro.');
		define('IMG_SAVE_UNKNOWN_MODE', 'Operação de imagem não esperada');
		define('IMG_SAVE_RESIZE_FAILED', 'Ocorreu um erro ao redimensionar a imagem.');
		define('IMG_SAVE_CROP_FAILED', 'Ocorreu um erro ao recortar a imagem.');
		define('IMG_SAVE_FAILED', 'Ocorreu um erro ao guardar a imagem.');
		define('IMG_SAVE_BACKUP_FAILED', 'Não foi possível fazer um backup da imagem original.');
		define('IMG_SAVE_ROTATE_FAILED', 'Não foi possível rodar a imagem.');
		define('IMG_SAVE_FLIP_FAILED', 'Não foi possível virar a imagem.');
		define('IMG_SAVE_SESSION_IMG_OPEN_FAILED', 'Não foi possível abrir a imagem da sessão.');
		define('IMG_SAVE_IMG_OPEN_FAILED', 'Não foi possível abrir a imagem');

		//UNDO
		define('IMG_UNDO_NO_HISTORY_AVAIALBE', 'Não existe história de anulações.');
		define('IMG_UNDO_COPY_FAILED', 'Não foi possível colocar a imagem no estado inicial.');
		define('IMG_UNDO_DEL_FAILED', 'Não foi possível apagar a imagem da sessão');

	//88888888888   Above for Image Editor   888888888888888888888

	//88888888888   Session   888888888888888888888
		define("SESSION_PERSONAL_DIR_NOT_FOUND", 'Não foi possível encontrar a pasta dedicada que deveria ter sido criada na pasta de sessões');
		define("SESSION_COUNTER_FILE_CREATE_FAILED", 'Não foi possível abrir o ficheiro que tem o contador da sessão.');
		define('SESSION_COUNTER_FILE_WRITE_FAILED', 'Não foi possível escrever no ficheiro que tem o contador da sessão.');
	//88888888888   Session   888888888888888888888

	//88888888888   Below for Text Editor   888888888888888888888
		define('TXT_FILE_NOT_FOUND', 'O ficheiro não foi encontrado.');
		define('TXT_EXT_NOT_SELECTED', 'Por favor, escolha a extensão do ficheiro');
		define('TXT_DEST_FOLDER_NOT_SELECTED', 'Por favor, escolha a pasta de destino');
		define('TXT_UNKNOWN_REQUEST', 'Pedido desconhecido.');
		define('TXT_DISALLOWED_EXT', 'Pode alterar/adicionar esse tipo de ficheiros.');
		define('TXT_FILE_EXIST', 'Esse ficheiro já existe.');
		define('TXT_FILE_NOT_EXIST', 'Não foi encontrado nenhum ficheiro.');
		define('TXT_CREATE_FAILED', 'Não foi possível criar um novo ficheiro.');
		define('TXT_CONTENT_WRITE_FAILED', 'Não foi possível escrever no ficheiro.');
		define('TXT_FILE_OPEN_FAILED', 'Não foi possível abrir o ficheiro.');
		define('TXT_CONTENT_UPDATE_FAILED', 'Não foi possível actualizar o ficheiro.');
		define('TXT_SAVE_AS_ERR_NAME_INVALID', "Por favor, escolha um nome que contenha apenas letras, dígitos, espaços, \'-\' e \'_\'.");
	//88888888888   Above for Text Editor   888888888888888888888


?>