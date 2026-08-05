
//WORKINGOBJ

var globalSortable;

var cstudioButtonEditorMessages = {
    en: {
        main: 'Main',
        game: 'Game',
        fx: 'Fx',
        availableActions: 'Available actions',
        currentActions: 'Current button actions',
        dragActionsHere: 'Drag actions here',
        helpTitle: 'Button behavior help',
        helpText: 'Choose a simple behavior with the radio buttons, or use the no-code editor to build advanced behavior. Drag actions from the left list to the right list. Conditions run the following actions only when question objects are OK or KO.',
        ifQuestionOk: 'If the question objects are OK',
        ifQuestionKo: 'If the question objects are KO',
        deleteLife: 'Delete life',
        addLife: 'Add life',
        nextPage: 'Next page',
        nextPageIfOk: 'Next page if OK',
        previousPage: 'Previous page',
        reset: 'Reset',
        functionCall: 'Function',
        goPage: 'Go to page',
        autoLevel: 'Auto level',
        viewErrors: 'View errors',
        noCodeEditor: 'No-code editor'
    },
    es: {
        main: 'Principal',
        game: 'Juego',
        fx: 'Efectos',
        availableActions: 'Acciones disponibles',
        currentActions: 'Acciones del botón',
        dragActionsHere: 'Arrastra acciones aquí',
        helpTitle: 'Ayuda del comportamiento del botón',
        helpText: 'Elige un comportamiento simple con los botones de opción, o usa el editor sin código para crear un comportamiento avanzado. Arrastra acciones de la lista izquierda a la lista derecha. Las condiciones ejecutan las acciones siguientes solo cuando los objetos de pregunta están correctos o incorrectos.',
        ifQuestionOk: 'Si los objetos de pregunta están correctos',
        ifQuestionKo: 'Si los objetos de pregunta están incorrectos',
        deleteLife: 'Quitar vida',
        addLife: 'Agregar vida',
        nextPage: 'Página siguiente',
        nextPageIfOk: 'Página siguiente si está correcto',
        previousPage: 'Página anterior',
        reset: 'Reiniciar',
        functionCall: 'Función',
        goPage: 'Ir a la página',
        autoLevel: 'Nivel automático',
        viewErrors: 'Ver errores',
        noCodeEditor: 'Editor sin código'
    },
    fr: {
        main: 'Principal',
        game: 'Jeu',
        fx: 'Effets',
        availableActions: 'Actions disponibles',
        currentActions: 'Actions du bouton',
        dragActionsHere: 'Glissez les actions ici',
        helpTitle: 'Aide sur le comportement du bouton',
        helpText: 'Choisissez un comportement simple avec les boutons radio, ou utilisez l’éditeur sans code pour créer un comportement avancé. Glissez les actions de la liste de gauche vers la liste de droite. Les conditions exécutent les actions suivantes uniquement lorsque les objets de question sont corrects ou incorrects.',
        ifQuestionOk: 'Si les objets de question sont corrects',
        ifQuestionKo: 'Si les objets de question sont incorrects',
        deleteLife: 'Retirer une vie',
        addLife: 'Ajouter une vie',
        nextPage: 'Page suivante',
        nextPageIfOk: 'Page suivante si correct',
        previousPage: 'Page précédente',
        reset: 'Réinitialiser',
        functionCall: 'Fonction',
        goPage: 'Aller à la page',
        autoLevel: 'Niveau automatique',
        viewErrors: 'Voir les erreurs',
        noCodeEditor: 'Éditeur sans code'
    },
    it: {
        main: 'Principale',
        game: 'Gioco',
        fx: 'Effetti',
        availableActions: 'Azioni disponibili',
        currentActions: 'Azioni del pulsante',
        dragActionsHere: 'Trascina qui le azioni',
        helpTitle: 'Aiuto sul comportamento del pulsante',
        helpText: 'Scegli un comportamento semplice con i pulsanti di opzione, oppure usa l’editor senza codice per creare un comportamento avanzato. Trascina le azioni dalla lista di sinistra alla lista di destra. Le condizioni eseguono le azioni successive solo quando gli oggetti domanda sono corretti o errati.',
        ifQuestionOk: 'Se gli oggetti domanda sono corretti',
        ifQuestionKo: 'Se gli oggetti domanda sono errati',
        deleteLife: 'Rimuovi vita',
        addLife: 'Aggiungi vita',
        nextPage: 'Pagina successiva',
        nextPageIfOk: 'Pagina successiva se corretta',
        previousPage: 'Pagina precedente',
        reset: 'Reimposta',
        functionCall: 'Funzione',
        goPage: 'Vai alla pagina',
        autoLevel: 'Livello automatico',
        viewErrors: 'Vedi errori',
        noCodeEditor: 'Editor senza codice'
    }
};

function cstudioButtonEditorLanguage() {
    var lang = '';

    try {
        if (window.parent && window.parent.langselectUI) {
            lang = window.parent.langselectUI;
        }
    } catch (e) {
        lang = '';
    }

    if (!lang && window.navigator) {
        lang = window.navigator.language || window.navigator.userLanguage || '';
    }

    lang = (lang || 'en').toLowerCase();

    if (lang.indexOf('es') === 0) {
        return 'es';
    }

    if (lang.indexOf('fr') === 0) {
        return 'fr';
    }

    if (lang.indexOf('it') === 0) {
        return 'it';
    }

    return 'en';
}

function cstudioButtonEditorText(key) {
    var lang = cstudioButtonEditorLanguage();
    var messages = cstudioButtonEditorMessages[lang] || cstudioButtonEditorMessages.en;

    return messages[key] || cstudioButtonEditorMessages.en[key] || key;
}

function cstudioButtonEditorEscape(value) {
    return String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function cstudioButtonEditorHelpHtml() {
    var h = '';

    h += '<div class="cstudio-button-editor-help">';
    h += '<strong>' + cstudioButtonEditorEscape(cstudioButtonEditorText('helpTitle')) + '</strong>';
    h += '<p>' + cstudioButtonEditorEscape(cstudioButtonEditorText('helpText')) + '</p>';
    h += '</div>';

    return h;
}


//init
function actionspersoShow(){
	
	var obj = WORKINGOBJ;
	
	globalSortable = 'list' + guid();
	
	var h = fctInnerActionsEdit(obj.text3,obj.text5);
	
	$('#actionseditzone').html(h);
	$('.actionslistehelp').css("display","block");
	
	var adjustment;
	
	$("ul." + globalSortable).sortable({
	  group: globalSortable,
	  pullPlaceholder: false,
	  // animation on drop
	  onDrop: function  ($item, container, _super) {
			var $clonedItem = $('<li/>').css({height: 0});
			$item.before($clonedItem);
			$clonedItem.animate({'height': $item.height()},100);
			$('.actionslistehelp').css("display","none");
			$item.animate($clonedItem.position(),100, function  () {
				$clonedItem.detach();
				_super($item, container);
			});
	  },
		
	  // set $item relative to cursor position
	  onDragStart: function ($item, container, _super) {
		var offset = $item.offset(),
			pointer = container.rootGroup.pointer;
		$('.actionslistehelp').css("display","none");
		adjustment = {
		  left: pointer.left - offset.left,
		  top: pointer.top - offset.top
		};

		_super($item, container);
	  },
	  onDrag: function ($item, position) {
			$item.css({
				left: position.left - adjustment.left,
				top: position.top - adjustment.top
			});
	  }
	});
	
	$("." + globalSortable).sortable('enable');
	
	saveActionsPerso();

}
actionspersoShow();

function fctInnerActionsEdit(lst,lst2){
	
	var p = '';
	
	var actions =  lst.split('|');
	var params =  lst2.split('|');

	p += '<div class="actionsThemes" >';
	p += '<a class="tabMain tabBtn" onClick="showTabMain();" title="' + cstudioButtonEditorEscape(cstudioButtonEditorText('availableActions')) + '" >' + cstudioButtonEditorEscape(cstudioButtonEditorText('main')) + '</a>';
	p += '<a class="tabGame tabBtn" onClick="showTabGame();" >' + cstudioButtonEditorEscape(cstudioButtonEditorText('game')) + '</a>';
	p += '<a class="tabFx tabBtn" onClick="showTabFx();" >' + cstudioButtonEditorEscape(cstudioButtonEditorText('fx')) + '</a>';
	p += '</div>';

	p += cstudioButtonEditorHelpHtml();

	p += '<ul id="listactions2" style="background-color:#BDBDBD;" ';
	p += ' class="actionsliste '+ globalSortable +'" >';
	
	if(lst.indexOf("cod1",'',1)==-1){p += lineActionsEdit("cod1",'',1);}
	if(lst.indexOf("cod2",'',1)==-1){p += lineActionsEdit("cod2"),'',1;}

	p += lineActionsEdit("act3",'',1);
	p += lineActionsEdit("act4",'',1);
	p += lineActionsEdit("act5",'',1);
	p += lineActionsEdit("act6",'',1);
	//p += lineActionsEdit("act8",'',1);
	p += lineActionsEdit("act7",'',1);
	p += lineActionsEdit("act9",'',0);
	p += lineActionsEdit("cor3",'',1);

	if(lst.indexOf("act1",'',1)==-1){p += lineActionsEdit("act1",'',0);}
	if(lst.indexOf("act2",'',1)==-1){p += lineActionsEdit("act2",'',0);}

	p += '</ul>';

	if(lst.indexOf("|")==-1){
		p += '<div class="actionslistehelp" >' + cstudioButtonEditorEscape(cstudioButtonEditorText('dragActionsHere')) + '</div>';
	}

	p += '<ul id="listactions1" style="border:dotted 1px gray;" class="actionsliste '+globalSortable+'" >';

	var i = 0;
	for (i=0;i<actions.length;i++){
		p += lineActionsEdit(actions[i],params[i],1);
	}
	p += '</ul>';

	return p;
		
}

function noneTabM(){

	$("#listactions2 .cod1").css("display","none");
	$("#listactions2 .cod2").css("display","none");
	$("#listactions2 .act1").css("display","none");
	$("#listactions2 .act2").css("display","none");
	$("#listactions2 .act3").css("display","none");
	$("#listactions2 .act4").css("display","none");
	$("#listactions2 .act5").css("display","none");
	$("#listactions2 .act6").css("display","none");
	$("#listactions2 .act7").css("display","none");
	$("#listactions2 .act8").css("display","none");
	$("#listactions2 .act9").css("display","none");
	$("#listactions2 .cor3").css("display","none");
}

function showTabMain(){

	$("#listactions2").css("background-color","#BDBDBD");

	noneTabM();

	$("#listactions2 .cod1").css("display","block");
	$("#listactions2 .cod2").css("display","block");
	$("#listactions2 .act3").css("display","block");
	$("#listactions2 .act4").css("display","block");
	$("#listactions2 .act5").css("display","block");
	$("#listactions2 .act6").css("display","block");
	
	$("#listactions2 .act8").css("display","block");
	$("#listactions2 .cor3").css("display","block");

}

function showTabGame(){

	$("#listactions2").css("background-color","#A9E2F3");

	noneTabM();

	$("#listactions2 .act1").css("display","block");
	$("#listactions2 .act2").css("display","block");

}

function showTabFx(){
	
	$("#listactions2").css("background-color","#D4EFDF");

	noneTabM();

	$("#listactions2 .act7").css("display","block");
	$("#listactions2 .act9").css("display","block");

}

function lineActionsEdit(id,pa,vi){

	var sty = '';
	if(vi==0){
		sty = 'style="display:none;" ';
	}
	var p = '';
	
	switch(id){
		case "cod1":
			p = '<li ' + sty + ' id="cod1" class="cod1" ><div class="minCondi Pos" >&nbsp;' + cstudioButtonEditorEscape(cstudioButtonEditorText('ifQuestionOk')) + '</div></li>';
			break;
		case "cod2":
			p = '<li ' + sty + ' id="cod2" class="cod2" ><div class="minCondi Neg" >&nbsp;' + cstudioButtonEditorEscape(cstudioButtonEditorText('ifQuestionKo')) + '</div></li>';
			break;
		case "act1":
			p = '<li ' + sty + ' id="act1" class="act1" ><div class="minAction" >' + cstudioButtonEditorEscape(cstudioButtonEditorText('deleteLife')) + '&nbsp;<img src="img/life.png" /></div></li>';
			break;
		case "act2":
			p = '<li ' + sty + ' id="act2" class="act2" ><div class="minAction" >' + cstudioButtonEditorEscape(cstudioButtonEditorText('addLife')) + '&nbsp;<img src="img/life.png" /></div></li>';
			break;
		case "act3":
			p = '<li ' + sty + ' id="act3" class="act3" ><div class="minAction" >' + cstudioButtonEditorEscape(cstudioButtonEditorText('nextPage')) + '</div></li>';
			break;
		case "act4":
			p = '<li ' + sty + ' id="act4" class="act4" ><div class="minAction" >' + cstudioButtonEditorEscape(cstudioButtonEditorText('nextPageIfOk')) + '</div></li>';
			break;
		case "act5":
			p = '<li ' + sty + ' id="act5" class="act5" ><div class="minAction" >' + cstudioButtonEditorEscape(cstudioButtonEditorText('previousPage')) + '</div></li>';
			break;
		case "act6":
			p = '<li ' + sty + ' id="act6" class="act6" ><div class="minAction" >' + cstudioButtonEditorEscape(cstudioButtonEditorText('reset')) + '</div></li>';
			break;
		
		case "act7":
			var pasc = parseFctTxt(pa);
			p = '<li ' + sty + ' id="act7" class="act7" ><div class="minAction" >' + cstudioButtonEditorEscape(cstudioButtonEditorText('functionCall')) + ':&nbsp;';
			p += '<input type="text" class="minFct valFct" value="'+pasc+'" /></div></li>';
			break;
		case "act8":
			var pasc = parseFctTxt(pa);
			p = '<li ' + sty + ' id="act8" class="act8" ><div class="minAction" >' + cstudioButtonEditorEscape(cstudioButtonEditorText('goPage')) + ':&nbsp;';
			p += '<input type="number" class="numFct valFct" value="'+pasc+'" /></div></li>';
			break;
		case "act9":
			p = '<li ' + sty + ' id="act9" class="act9" ><div class="minAction" >' + cstudioButtonEditorEscape(cstudioButtonEditorText('autoLevel')) + '&nbsp;';
			p += '<span style="position:relative;display:inline-block;width:12px;height:12px;background-color:#52BE80;"></span>';
			p += '<span style="position:relative;display:inline-block;width:12px;height:12px;background-color:#3b97e3;"></span>';
			p += '<span style="position:relative;display:inline-block;width:12px;height:12px;background-color:#EB984E;"></span>';
			p += '</div></li>';
			break;
		case "cor3":
			p = '<li ' + sty + ' id="cor3" class="cor3" ><div class="minAction" >&nbsp;' + cstudioButtonEditorEscape(cstudioButtonEditorText('viewErrors')) + '&nbsp;</div></li>';
			break;
	}
	
	return p;
	
}

function scActionsEdit(id,pa){
	
	var p = '';
	
	pa = parseFctTxt(pa);
	
	switch(id){
		case "cod1":
			p = 'if(LUDI.pageIsOk()){';
			break;
		case "cod2":
			p = 'if(!LUDI.pageIsOk()){';
			break;
		case "act1":
			p = 'LUDI.deleteLife();';
			break;
		case "act2":
			p = 'LUDI.addLife();';
			break;
		case "act3":
			p = 'LUDI.nextPage();';
			break;
		case "act4":
			p = 'LUDI.nextPageIfOK();';
			break;
		case "act5":
			p = 'LUDI.prevPage();';
			break;
		case "act6":
			p = 'window.location.reload();';
			break;
		case "act7":
			if(pa!=''){
				p = pa + '();';
			}
			break;
		case "act8":
			if(pa!=''){
				p = 'LUDI.goPage(' + parseInt(pa) + ');';
			}
			break;
		case "cor3":
			p = "LUDI.checkAll();";
			break;
		case "act9":
			p = 'LUDI.autoLevel();';
			break;
	}
	
	return p;
	
}

//Save to temp object WORKINGOBJ
function saveActionsPerso(){
	
	var obj = WORKINGOBJ;
	
	var lst = recupActionsPerso();
	//blocks
	obj.text3 = lst;
	
	//blocks
	var lst2 = recupParamsPerso();
	obj.text5 = lst2;
	
	var actions = lst.split('|');
	var params  = lst2.split('|');
	
	var sc = '';
	var condiopen = false;
	
	var i = 0;
	
	for (i=0;i<actions.length;i++){
		
		//Si condition
		if(actions[i].indexOf('cod')!=-1){
			if(condiopen){sc = sc+'}';}
			condiopen = true;
		}
		sc += scActionsEdit(actions[i],params[i]);
		
	}
	
	if(condiopen){sc = sc+'}';}
	
	sc += 'LUDI.waitReset();';
	
	//final script
	obj.text4 = sc;
	objetSendToString();
	
	setTimeout(function(){ saveActionsPerso() }, 300);

}

function recupActionsPerso(){
	
	var r = '';
	
	var idul = '#listactions1 li';
	
	$(idul).each(function(n){
        r = r + $(this).attr('id') + "|";
    });
	
    return r;
    
}

function recupParamsPerso(){
	
	var r = '';
	
	var idul = '#listactions1 li';
	
	$(idul).each(function(n){
        r += parseFctTxt($(this).find('.valFct').val()) + "|";
    });
	
	return r;
}

function parseFctTxt(str) {
	
	if(typeof(str)=='undefined'){
		return "";
	}
	if(str=='undefined'){
		return "";
	}
	str = str.replace("...",'');
	str = str.replace(" ",'');
	str = str.replace(" ",'');
	str = str.replace("(",'');
	str = str.replace(")",'');
	str = str.replace(";",'');
	str = str.replace("|",'');
	if(str==null){str = "";}
	
	return (str);
}

function guid(){
	
	var tirage = new Array;
	var nombres="";
	var nombre = 0;
	nb = 7;
	
	for (i=1 ;i<nb ;i++)
	{
		nombre = nb_random(50);
		tirage[i]= nombre;
		for (t=1 ; t<i ;t++){
			if (tirage[t]==nombre)
			{
				i=i-1;
			}
		}
	}
	
	var characts = 'abcdefghijklmnopqrstuvwzabcdefghijklmnopqrstuvwz';
	
	for (i=1 ;i<nb ;i++)
	{
		nombre = nb_random(50);
		c = characts.substr(nombre,1)
		nombres= nombres + tirage[i] + c ;
	}
	
	return nombres;
	
}

function nb_random(nb){
	return Math.floor(Math.random() * nb)+1;
}
