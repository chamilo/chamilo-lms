// I18N constants

// LANG: "en", ENCODING: UTF-8 | ISO-8859-1
// Author: Mihai Bazon, http://dynarch.com/mishoo

// FOR TRANSLATORS:
//
//   1. PLEASE PUT YOUR CONTACT INFO IN THE ABOVE LINE
//      (at least a valid email address)
//
//   2. PLEASE TRY TO USE UTF-8 FOR ENCODING;
//      (if this is not possible, please include a comment
//       that states what encoding is necessary.)

HTMLArea.I18N = {

	// the following should be the filename without .js extension
	// it will be used for automatically load plugin language.
	lang: "en", 

	tooltips: {
		bold:           "Krepko",
		italic:         "Ležeèe",
		underline:      "Podèrtano",
		strikethrough:  "Predèrtano",
		subscript:      "Podpisano",
		superscript:    "Nadpisano",
		justifyleft:    "Poravnaj evo",
		justifycenter:  "Na sredino",
		justifyright:   "Desno",
		justifyfull:    "Porazdeli vsebino",
		orderedlist:    "Oštevilèevanje",
		unorderedlist:  "Oznaèevanje",
		outdent:        "Zmanjšaj zamik",
		indent:         "Poveèaj zamik",
		forecolor:      "Barva pisave",
		hilitecolor:    "Barva ozadja",
		inserthorizontalrule: "Horizontalno ravnilo",
		createlink:     "Vstavi spletno povezavo",
		insertimage:    "Vstavi/Uredi sliko",
		inserttable:    "Vstavi Tabelo",
		htmlmode:       "Preklopi HTML izvorno kodo",
		popupeditor:    "Poveèaj urejevalnik",
		about:          "O urejevalniku",
		showhelp:       "Pomoè: uporaba urejevalnika",
		textindicator:  "Trenuten stil",
		undo:           "Razveljavi",
		redo:           "Uveljavi",
		cut:            "Izreži",
		copy:           "Kopiraj",
		paste:          "Prilepi",
		lefttoright:    "Usmeritev iz leve proti desni",
		righttoleft:    "Usmeritev iz desne proti levi"
	},

	buttons: {
		"ok":           "OK",
		"cancel":       "Preklièi"
	},

	msg: {
		"Path":         "Pot",
		"TEXT_MODE":    "Si v TEKSTovnem naèinu. Uporabi gumb [<>] za preklop v predogledni(WYSIWYG) naèin.",

		"IE-sucks-full-screen" :
		// translate here
		"Celozaslonski naèin povzroèa probleme vsled uporabe MS Internet Explorer, " +
		"zaradi hrošèev v implementaciji, katerim se programsko ni moè izogniti. Zaslon lahko vsebuje nakljuène podatke " +
		", opazite lahko manjkajoèo funkcionalnost, v nekaterih primerih pa celo razpad delovanja brskalnika.  Windows 9x " +
		"pogosto javi 'General Protection Fault' in poziv k ponovnem zagonu sistema.\n\n" +
		"Bil si opozorjen/a. Kliknite OK èe kljub opozorilu želite preklopiti v celozaslonski naèin.",

		"Moz-Clipboard" :
		"Nepriviligirane skripte ne morejo dostopati do Izreži/Kopiraj/Prilepi programsko " +
		"iz varnostnih razlogov. Klikni OK za dostop do tehniènih navodil na mozilla.org " +
		", kjer boste našli navodilo za omogoèanje delovanja skript z odložišèem."
	},

	dialogs: {
		"Cancel"                                            : "Preklièi",
		"Insert/Modify Link"                                : "Vstavi/Uredi povezavo",
		"New window (_blank)"                               : "Novo okno (_blank)",
		"None (use implicit)"                               : "Niè (uporabi isto okno)",
		"OK"                                                : "OK",
		"Other"                                             : "Drugo",
		"Same frame (_self)"                                : "Isti okvir (_self)",
		"Target:"                                           : "Cilj:",
		"Title (tooltip):"                                  : "Naslov (tooltip):",
		"Top frame (_top)"                                  : "Zgornji okvir (_top)",
		"URL:"                                              : "URL:",
		"You must enter the URL where this link points to"  : "Vnesti je potrebno URL, na katerega kaže povezava"
	}
};
