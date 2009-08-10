// I18N constants

HTMLArea.I18N = {

	// the following should be the filename without .js extension
	// it will be used for automatically load plugin language.
	lang: "es",

	tooltips: {
		bold:           "Negrita",
		italic:         "Cursiva",
		underline:      "Subrayado",
		strikethrough:  "Tachado",
		subscript:      "Subíndice",
		superscript:    "Superíndice",
		justifyleft:    "Alinear a la Izquierda",
		justifycenter:  "Centrar",
		justifyright:   "Alinear a la Derecha",
		justifyfull:    "Justificar",
		orderedlist:    "Lista Ordenada",
		unorderedlist:  "Lista No Ordenada",
		outdent:        "Aumentar Sangría",
		indent:         "Disminuir Sangría",
		forecolor:      "Color del Texto",
		hilitecolor:    "Color del Fondo",
		inserthorizontalrule: "Línea Horizontal",
		createlink:     "Insertar Enlace",
		insertimage:    "Insertar Imagen",
		inserttable:    "Insertar Tabla",
		htmlmode:       "Ver Documento en HTML",
		popupeditor:    "Ampliar Editor",
		about:          "Acerca del Editor",
		showhelp:       "Ayuda",
		textindicator:  "Estilo Actual",
		undo:           "Deshacer",
		redo:           "Rehacer",
		cut:            "Cortar selección",
		copy:           "Copiar selección",
		paste:          "Pegar desde el portapapeles"
	},

	buttons: {
		"ok":           "Aceptar",
		"cancel":       "Cancelar"
	},

	msg: {
		"Path":         "Ruta",
		"TEXT_MODE":    "Esta en modo TEXTO. Use el boton [<>] para cambiar a WYSIWIG",
	 	////////////////////////////////////////////////////////////added for compatibility with INTERNET EXPLORER
		"IE-sucks-full-screen" :
		// translate here
		"The full screen mode is known to cause problems with Internet Explorer, " +
		"due to browser bugs that we weren't able to workaround. You might experience garbage " +
		"display, lack of editor functions and/or random browser crashes. If your system is Windows 9x " +
		"it's very likely that you'll get a 'General Protection Fault' and need to reboot.\n\n" +
		"You have been warned. Please press OK if you still want to try the full screen editor.",
	
		"Moz-Clipboard" :
		"Unprivileged scripts cannot access Cut/Copy/Paste programatically " +
		"for security reasons. Click OK to see a technical note at mozilla.org " +
		"which shows you how to allow a script to access the clipboard."
	},
	dialogs: {
		"Cancel" : "Cancel",
		"Insert/Modify Link" : "Insert/Modify Link",	
		"New window (_blank)" : "New window (_blank)",	
		"None (use implicit)" : "None (use implicit)",
		"OK" : "OK",
		"Other" : "Other",
		"Same frame (_self)" : "Same frame (_self)",
		"Target:" : "Target:",
		"Title (tooltip):" : "Title (tooltip):",
		"Top frame (_top)" : "Top frame (_top)",
		"URL:" : "URL:",
		"You must enter the URL where this link points to" : "You must enter the URL where this link points to"
		///////////////////////////////////////////////////////////////////////////////////////////////////
	}
};