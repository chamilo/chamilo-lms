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
		define('LBL_ACTION_REFRESH', 'Atualizar');
		define("LBL_ACTION_DELETE", 'Excluir');
		define('LBL_ACTION_CUT', 'Recortar');
		define('LBL_ACTION_COPY', 'Copiar');
		define('LBL_ACTION_PASTE', 'Colar');
		//File Listing
	define('LBL_NAME', 'Nome');
	define('LBL_SIZE', 'Tamanho');
	define('LBL_MODIFIED', 'Modificado em');
		//File Information
	define('LBL_FILE_INFO', 'Informa&ccedil;&otilde;es do arquivo:');
	define('LBL_FILE_NAME', 'Nome:');
	define('LBL_FILE_CREATED', 'Criado em:');
	define("LBL_FILE_MODIFIED", 'Modificado em:');
	define("LBL_FILE_SIZE", 'Tamanho:');
	define('LBL_FILE_TYPE', 'Tipo:');
	define("LBL_FILE_WRITABLE", 'Perm. Escrita?');
	define("LBL_FILE_READABLE", 'Perm. Leitura?');
		//Folder Information
	define('LBL_FOLDER_INFO', 'Informa&ccedil;&otilde;es da pasta');
	define("LBL_FOLDER_PATH", 'Caminho:');
	define("LBL_FOLDER_CREATED", 'Criada em:');
	define("LBL_FOLDER_MODIFIED", 'Modificada em:');
	define('LBL_FOLDER_SUDDIR', 'Subpastas:');
	define("LBL_FOLDER_FIELS", 'Arquivos:');
	define("LBL_FOLDER_WRITABLE", 'Perm. Escrita?');
	define("LBL_FOLDER_READABLE", 'Perm. Leitura?');
		//Preview
	define("LBL_PREVIEW", 'Pr&eacute;-visualiza&ccedil;&atilde;o');
	//Buttons
	define('LBL_BTN_SELECT', 'Selecionar');
	define('LBL_BTN_CANCEL', 'Cancelar');
	define("LBL_BTN_UPLOAD", 'Copiar');
	define('LBL_BTN_CREATE', 'Criar');
	define('LBL_BTN_CLOSE', 'Fchar');
	define("LBL_BTN_NEW_FOLDER", 'Nova pasta');
	define('LBL_BTN_EDIT_IMAGE', 'Editar');
	//Cut
	define('ERR_NOT_DOC_SELECTED_FOR_CUT', 'Nenhum arquivo selecionado para recortar.');
	//Copy
	define('ERR_NOT_DOC_SELECTED_FOR_COPY', 'Nenhum arquivo selecionado para copiar.');
	//Paste
	define('ERR_NOT_DOC_SELECTED_FOR_PASTE', 'Nenhum arquivo selecionado para colar.');
	define('WARNING_CUT_PASTE', 'Tem certeza que deseja mover os arquivos selecionados para esta pasta?');
	define('WARNING_COPY_PASTE', 'Tem certeza que deseja copiar os arquivos selecionados para esta pasta?');

	//ERROR MESSAGES
		//deletion
	define('ERR_NOT_FILE_SELECTED', 'Por favor, selecione um arquivo.');
	define('ERR_NOT_DOC_SELECTED', 'Nenhum documento selecionado para deletar.');
	define('ERR_DELTED_FAILED', 'Imposs�vel deletar documento.');
	define('ERR_FOLDER_PATH_NOT_ALLOWED', 'O caminho desta pasta n&atilde;o &eacute; permitido.');
		//class manager
	define("ERR_FOLDER_NOT_FOUND", 'Imposs�vel encontrar a pasta: ');
		//rename
	define('ERR_RENAME_FORMAT', 'Por favor, digite um nome que contenha apenas letras, n&uacute;meros, espa&ccedil;os, h&iacute;fen e underscore.');
	define('ERR_RENAME_EXISTS', 'Por favor, digite um nome que ainda n&atilde;o exista dentro desta pasta.');
	define('ERR_RENAME_FILE_NOT_EXISTS', 'O arquivo/pasta n&atilde;o existe.');
	define('ERR_RENAME_FAILED', 'Imposs�vel renomear, tente novamente.');
	define('ERR_RENAME_EMPTY', 'Por favor, digite um nome.');
	define("ERR_NO_CHANGES_MADE", 'Nenhuma mudan&ccedil;a foi feita.');
	define('ERR_RENAME_FILE_TYPE_NOT_PERMITED', 'voc&ecirc; n&atilde;o tem permiss&otilde;es para alterar o arquivo para esta extensao.');
		//folder creation
	define('ERR_FOLDER_FORMAT', 'Por favor, digite um nome que contenha apenas letras, n&uacute;meros, espa&ccedil;os, h&iacute;fen e underscore.');
	define('ERR_FOLDER_EXISTS', 'Por favor, digite um nome que ainda n&atilde;o exista dentro desta pasta.');
	define('ERR_FOLDER_CREATION_FAILED', 'Imposs�vel criar a pasta, tente novamente.');
	define('ERR_FOLDER_NAME_EMPTY', 'Por favor, de um nome.');

		//file upload
	define("ERR_FILE_NAME_FORMAT", 'Por favor, digite um nome que contenha apenas letras, n&uacute;meros, espa&ccedil;os, h&iacute;fen e underscore.');
	define('ERR_FILE_NOT_UPLOADED', 'Nenhum arquivo selecionado para a c&oacute;pia.');
	define('ERR_FILE_TYPE_NOT_ALLOWED', 'voc&ecirc; n&atilde;o tem permiss&otilde;es para copiar arquivos com esta extensao.');
	define('ERR_FILE_MOVE_FAILED', 'Falha ao mover o arquivo.');
	define('ERR_FILE_NOT_AVAILABLE', 'O arquivo n&atilde;o est&aacute; dispon&iacute;vel.');
	define('ERROR_FILE_TOO_BID', 'Arquivo muito grande. (max: %s)');


	//Tips
	define('TIP_FOLDER_GO_DOWN', 'Clique para acessar esta pasta...');
	define("TIP_DOC_RENAME", 'Clique duas vezes para editar...');
	define('TIP_FOLDER_GO_UP', 'Clique para voltar a pasta anterior...');
	define("TIP_SELECT_ALL", 'Selecionar Tudo');
	define("TIP_UNSELECT_ALL", 'Selecionar Nenhum');
	//WARNING
	define('WARNING_DELETE', 'Tem certeza que deseja excluir os arquivos selecionados?');
	define('WARNING_IMAGE_EDIT', 'Por favor, selecione uma imagem para editar.');
	define('WARING_WINDOW_CLOSE', 'Tem certeza que deseja fechar esta janela?');
	//Preview
	define('PREVIEW_NOT_PREVIEW', 'Imagem Pr&eacute;via n&atilde;o dispon&iacute;vel.');
	define('PREVIEW_OPEN_FAILED', 'Imposs�vel abrir o arquivo.');
	define('PREVIEW_IMAGE_LOAD_FAILED', 'Imposs�vel carregar a imagem');

	//Login
	define('LOGIN_PAGE_TITLE', 'Ajax File Manager Login Form');
	define('LOGIN_FORM_TITLE', 'Login Form');
	define('LOGIN_USERNAME', 'Username:');
	define('LOGIN_PASSWORD', 'Password:');
	define('LOGIN_FAILED', 'Invalid username/password.');


	//88888888888   Below for Image Editor   888888888888888888888
		//Warning
		define('IMG_WARNING_NO_CHANGE_BEFORE_SAVE', "voc&ecirc; n&atilde;o fez modifica&ccedil;oes na imagem.");

		//General
		define('IMG_GEN_IMG_NOT_EXISTS', 'Imagem n&atilde;o existe');
		define('IMG_WARNING_LOST_CHANAGES', 'Todas as altera&ccedil;oes feitas na imagem at&eacute; o momento serao perdidas, tem certeza que deseja continuar?');
		define('IMG_WARNING_REST', 'Todas as altera&ccedil;oes n&atilde;o salvas serao perdidas, tem certeza que deseja reiniciar?');
		define('IMG_WARNING_EMPTY_RESET', 'Nenhuma altera&ccedil;ao at&eacute; o momento');
		define('IMG_WARING_WIN_CLOSE', 'Tem certeza que deseja fechar esta janela?');
		define('IMG_WARNING_UNDO', 'Tem certeza que deseja restaurar a imagem ao seu estado incial?');
		define('IMG_WARING_FLIP_H', 'Tem certeza que deseja espelhar a imagem horizontalmente?');
		define('IMG_WARING_FLIP_V', 'Tem certeza que deseja espelhar a imagem verticalmente?');
		define('IMG_INFO', 'Informa&ccedil;oes sobre a imagem');

		//Mode
			define('IMG_MODE_RESIZE', 'Redimensionar:');
			define('IMG_MODE_CROP', 'Cortar:');
			define('IMG_MODE_ROTATE', 'Rotacionar:');
			define('IMG_MODE_FLIP', 'Espelhar:');
		//Button

			define('IMG_BTN_ROTATE_LEFT', '90&deg;Anti Hor&aacute;rio');
			define('IMG_BTN_ROTATE_RIGHT', '90&deg;Hor&aacute;rio');
			define('IMG_BTN_FLIP_H', 'Espelhar horizontalmente');
			define('IMG_BTN_FLIP_V', 'Espelhar verticalmente');
			define('IMG_BTN_RESET', 'Reiniciar');
			define('IMG_BTN_UNDO', 'Desfazer');
			define('IMG_BTN_SAVE', 'Salvar');
			define('IMG_BTN_CLOSE', 'Fechar');
		//Checkbox
			define('IMG_CHECKBOX_CONSTRAINT', 'Manter propor&ccedil;&atildeo?');
		//Label
			define('IMG_LBL_WIDTH', 'Largura:');
			define('IMG_LBL_HEIGHT', 'Altura:');
			define('IMG_LBL_X', 'X:');
			define('IMG_LBL_Y', 'Y:');
			define('IMG_LBL_RATIO', 'propor&ccedil;&atildeo;:');
			define('IMG_LBL_ANGLE', '&Acirc;ngulo:');
		//Editor


		//Save
		define('IMG_SAVE_EMPTY_PATH', 'Caminho da imagem vazio.');
		define('IMG_SAVE_NOT_EXISTS', 'Imagem n&atilde;o existe.');
		define('IMG_SAVE_PATH_DISALLOWED', 'Voc&ecirc; n&atilde;o tem permiss&otilde;es para acessar o arquivo.');
		define('IMG_SAVE_UNKNOWN_MODE', 'Modo de Opera&ccedil;ao de Imagem Inesperado');
		define('IMG_SAVE_RESIZE_FAILED', 'Falha ao redimensionar.');
		define('IMG_SAVE_CROP_FAILED', 'Falha ao cortar.');
		define('IMG_SAVE_FAILED', 'Falha ao salvar.');
		define('IMG_SAVE_BACKUP_FAILED', 'Imposs�vel efetuar backup da imagem original.');
		define('IMG_SAVE_ROTATE_FAILED', 'Imposs�vel rotacionar a imagem.');
		define('IMG_SAVE_FLIP_FAILED', 'Imposs�vel espelhar a imagem.');
		define('IMG_SAVE_SESSION_IMG_OPEN_FAILED', 'Imposs�vel abrir imagem a partir da sess&atilde;o.');
		define('IMG_SAVE_IMG_OPEN_FAILED', 'Imposs�vel abrir a imagem');

		//UNDO
		define('IMG_UNDO_NO_HISTORY_AVAIALBE', 'Sem hist&oacute;rico dispon&iacute;vel para desfazer.');
		define('IMG_UNDO_COPY_FAILED', 'Imposs�vel restaurar a imagem.');
		define('IMG_UNDO_DEL_FAILED', 'Imposs�vel apagar a imagem da sess&atilde;o.');

	//88888888888   Above for Image Editor   888888888888888888888

	//88888888888   Session   888888888888888888888
		define("SESSION_PERSONAL_DIR_NOT_FOUND", 'Imposs�vel achar a pasta dedicada que deve ser criada dentro da pasta sess&atilde;o');
		define("SESSION_COUNTER_FILE_CREATE_FAILED", 'Imposs�vel criar o arquivo contador de sess&atilde;o.');
		define('SESSION_COUNTER_FILE_WRITE_FAILED', 'Imposs�vel escrever no arquivo contador de sess&atilde;o.');
	//88888888888   Session   888888888888888888888


?>