var ViewerAfterBilan = '';
var eventCatchScript = '';
var langueextend = '';
var externdata = '';
var allDiapoQuest = new Array();
var poff = new Array();
var BasePageTirageUnique = new Array();
var transitionPage = '';
var oldTransitionPage = '';
var domaddopt = false;
var IOS = 0;
var oriTypeSite = '';
var unikProgressionAll = true;
function localExec(){
    return true;
}
function appliqueDataQCM(obj){

}
function gebi(obj){
    return false;
}
function openXML(obj){

}
function appliqueFond(){
}
function getbasedisplayMessage(){

}

function StopAllSounds(){}
function stopAllSound(){}
function playallsounds(){}

function anim12(){}
function loadScormBase(){}
function execScriptLoop(){}
function loadProgressionAll(){}
function saveProgressionAll(){}
var orix = 0;
var oriy = 0;

var N_T = 0;
var N_F = 0;

var menu_html = '';
var menu_global = 'data/page0.xml';
var menu_load = 0

var remarques = '';
var MessageFalse = '';

var colorfond = "";
var colorfondsvg = "";

var colorfond2 = "";
var colorfondsvg2 = "";

var largefondx = "";
var largefondy = "";

var cadrefond = "";
var cadrefondsvg = "";

var cadrecolorfond = "";
var cadrebordercolorfond = "";

var headerfond = "";
var headercolorfond = "";
var headerbordercolorfond = "";

var footerfond = "";
var footercolorfond = "";
var footerbordercolorfond = "";

var acceptMoveObj = 1;

var exceptionForkDragDrop = false;

var scriptdiapo = "";
var scriptloop = "";
var lastPage1 = 0;
var lastPage0 = 0;

var nbpagesD = 1;
var tirageunique = 0;

var saveprogression = false;
var saveprogressioncheckpoint = false;
var saveprogressionident = '';

var scormProcessScore = 0;

var attemptProcess = 0;
var domainesliste = '';
var parametersapp = '';

//Objets CObjet
function CObjet(){
	
	var Ecran = document.getElementById("main");
	
	this.x;
	this.y;
	this.w;
	this.h;

	this.x2;
	this.y2;
	this.w2;
	this.h2;
	
	this.orix;
  	this.oriy;
	
	this.realx;
	
	this.objx;
  	this.objy;
	this.rotation;
	
	this.ind;
	this.text;
	this.initialtext;
	this.fontsize;
	this.fontsize2;
	this.color;
	this.align;
	this.url;
	this.note;
	this.negnote;
	this.border;
	this.css;
	this.cssadd;
	this.selectcolor;
	this.remarque;
	this.domaine;
	
	this.contenu2;	
	this.contenu3;
	this.contenu4;
	this.contenu5;
	this.contenu6;
	this.contenu7;
	this.contenu8;

	this.contentpath;
	this.contentpathsecond;
	
	this.extracont;
	
	this.linkcontenu;
	this.linkimage;
	this.linkx;
	this.linky;
	
	this.mymenu;
	
	this.an;

	this.de;
	this.di;
	this.dedi;
	
	this.de2;
	this.di2;
	this.dedi2;

	this.evol;
	this.option;
	this.option2;
	this.option3;
	this.option4;
	this.option7;

	this.out;

	this.pp;
	
	this.fctanim;
	this.AnimClic;
	
	this.id;
	this.idstr;
	this.idscript;
	
	this.type;
	this.theme;
	this.src;
	this.create;
	this.data;

	this.field1;
	this.field2;
	this.field3;
	this.field4;
	this.field5;
	this.fields;
	
	
	this.boite;
	
	this.onmove;
	this.oldzoom;
	
	this.bilan;
	this.bilandisplay;
	
	this.activepage;
	
	this.zoomslide;
	
	this.unLoad = function() {//**
		var sid = ".bloc" + this.id;
		$(sid).css("display","none");
	}//**
	
	//Fonctions de dessin du noeud
this.show_element = function() {//**
    
	var decx = parseInt(orix);
  
	if(this.type=='menu'){decx=0;}
	
	if(oriTypeSite=="classic-responsive"){
		if(typesite=="mobile"){
			if(this.x>480){
				this.x = this.orix - 480;
				this.y = this.oriy + 720;
			}
		}else{
			this.x = this.orix;
			this.y = this.oriy;
		}
	}
	
	var e_x = parseInt(decx) + parseInt(this.x * zoom);
	var e_y = parseInt(this.y * zoom);
	
	var wb = parseInt(this.w * zoom);
	var hb = parseInt(this.h * zoom);
	
	if(mobiSite){
		e_x = parseInt(decx) + parseInt(this.x2 * zoom);
		e_y = parseInt(this.y2 * zoom);
		wb = parseInt(this.w2 * zoom);
		hb = parseInt(this.h2 * zoom);
	}

	var posisty = "left:" + e_x + ";top:" + e_y + ";width:" + wb + ";height:" + hb + ";";
	
	if(this.an==1){
		posisty = "";
	}
	
	var color = "black";
	if(this.color){
		color = this.color;
	}
	
	var align = "center";
	if(this.align){
		align = this.align;
	}
  
	var cssPlus = "";
	if(this.css){
		cssPlus = this.css;
	}
	
	if(this.create==0){
		
		var h = '';
		var act = '';
		var actOnly = '';
		this.realx = 0;
		
		if(this.url!=''){
			
			if(this.url.indexOf("openPopupLight")!=-1
			||this.url.indexOf("openWindowsLight")!=-1
			||this.url.indexOf("playallsounds")!=-1
			||this.url.indexOf("openSuivi")!=-1
			||this.url.indexOf("loaddata(")!=-1
			||this.url.indexOf("loadDataScreen")!=-1
			||this.url.indexOf("isok")!=-1
			||this.url.indexOf("lastPage")!=-1
			||this.url.indexOf("displayLastPage")!=-1
			||this.url.indexOf("openCorrection(")!=-1
			||this.url.indexOf("launchPara(")!=-1
			||this.url.indexOf("openDialogYNDown")!=-1){
				
				act = ' onclick="' + this.url + '"';
				actOnly = this.url;
				
			}else{
			
				if(this.url.indexOf("link:")!=-1){
					
					var ur = this.url.replace('link:','');
					act = ' onclick="javascript:window.open(\'' ;
					act = act + ur + '\');return false;" ';
					
					actOnly = 'javascript:window.open(\'' + ur + '\');';

				}else{
					
					var transDir = '';
					
					if(this.x < 100){
						transDir = 'left';
					}
					
					if(this.x > 620&&typesite!="mobile"){
						transDir = 'right';
					}
					
					act = ' onclick="transitionDirection=\'' ;
					act = act + transDir + '\';loaddata(\'' + this.url ;
					act = act + '\',\'' + this.data + '\');" ';
					
					actOnly = 'loaddata(\'' + this.url + '\',\'' + this.data + '\');';
					
				}
			
			}
		
		}
		
		if(this.url.indexOf("openCorrection(")==-1){
			if(this.strscript!=''){
				act = act + ' ' +  this.strscript;
				actOnly = actOnly +  this.strscript ;
			}
		}
		
		installgeo(this);
		installgame(this);
		installherotarget(this);
		installslidepages(this);
		installludiplan(this);
		instalPhysics(this);
		installfluxitems(this);
		installMapTarget(this);
		installPlugins(this);
		installVideo(this);
		installAudio(this);
		installReportWrite(this);
		installExamBarre(this);
		installSimulBloc(this);
		installMotsaRelier(this);
		installBoiteTexte(this,act);
		installHandProcess(this);
		installIsoAvatar(this);
		
		if(this.type=='showstar'){
			installshow(this,posisty);
		}
		
		if(this.type=='automenu'){
			installmenuauto(this);
		}
		
		if(this.type=='ludirpt2'){
			installRptGraph(this);
		}
		
		if(this.type=='ludidialog'||this.type=='ludidialogrep'){
			instalDialog(this);
		}
		
		installludi(this);
		installfx(this,act);
		installflux(this);
		installforms(this);
		installshareresult(this);
		installbilanresult(this);
		installconnexion(this);
		installqcm(this);
		
		installbutton(this,act,posisty);
		installtext(this,act);
		installInputNumerique(this);
		
		if(this.type=='img'){
			installimg(this,posisty,act);
		}
		
		if(this.type=='timer'){
			installclock(this,actOnly);
		}

		if(this.type=='timercompteur'){
			installclockcompteur(this,actOnly);
		}
		
		if(this.type=='drag'||this.type=='drop'||this.type=='dragslide'||this.type=='bag'){
			installdragdrop(this);//responsive
		}
		
		if(this.type=='input'){
			installInputSimple(this);
			haveNoSyntaxInScreen = false;
		}
		
		if(this.type=='inputFocus'){
			installInputFocus(this);
			haveNoSyntaxInScreen = false;
		}
		
		if(this.type=='inputsyntaxique'){
			installInputTextAreaBloc(this,color);		
			haveNoSyntaxInScreen = false;
		}
		
		if(this.type=='textarea'){
		
			h = '<div style="color:' + color + ';overflow:hidden;border:dotted 1px gray;' + alignByObj(this)  + cssPlus + '" ';
			h += ' id="table' + this.id + '" class="bloc' + this.id + '"  >';
			h += '<div class="scrollbar" ><div class="track"><div class="thumb"><div class="end"></div></div></div></div>';
			h += '<div class="viewport" id="viewport' + this.id + '" ><div id="overview' + this.id + '"  class="overview">';
			h += '<p>' + this.text + '</p>';
			h += '</div></div></div>';
			haveNoSyntaxInScreen = false;
		}
		
		if(this.type=='hideword'||this.type=='anim'){	
			h = '<div style="display:none;overflow:hidden;color:' + color + ';' +  this.cssadd + '" ';
			h += ' id="table' + this.id + '" class="bloc' + this.id + '" ';
			h += ' >';
			h += '</div>';
		}
		
		installNotes(this,act);
		
		var ScreenAdd = document.getElementById("main");
		ScreenAdd.innerHTML = ScreenAdd.innerHTML + h;
	
	appliqueDataB(this);
	
	this.oldzoom = 0;
	this.create = 1;

	updatescore();

	}else{
     objetzoom(this,e_x,e_y);
	}

  }//**

	this.setX = function(v) {//**
		if(mobiSite){
			this.x2 = v;
		}else{
			this.x = v;
		}
	}//**
	this.setY = function(v) {//**
		if(mobiSite){
			this.y2 = v;
		}else{
			this.y = v;
		}
	}//**

	this.setW = function(v) {//**
		if(mobiSite){
			this.w2 = v;
		}else{
			this.w = v;
		}
	}//**
	this.setH = function(v) {//**
		if(mobiSite){
			this.h2 = v;
		}else{
			this.h = v;
		}
	}//**

	this.getFts = function() {//**
		if(mobiSite){
			return parseInt(this.fontsize2);
		}else{
			return parseInt(this.fontsize);
		}
	}//**
	this.getX = function() {//**
		if(mobiSite){
			return parseInt(this.x2);
		}else{
			return parseInt(this.x);
		}
	}//**
	this.getY = function() {//**
		if(mobiSite){
			return parseInt(this.y2);
		}else{
			return parseInt(this.y);
		}
	}//**
	this.getW = function() {//**
		if(mobiSite){
			return parseInt(this.w2);
		}else{
			return parseInt(this.w);
		}
	}//**
	this.getH = function() {//**
		if(mobiSite){
			return parseInt(this.h2);
		}else{
			return parseInt(this.h);
		}
	}//**

this.init = function() {//**
	
    var color = "black";
	
    if(this.color){
		color = this.color;
	}
    
    var selectcolor = "red";
			
    if(this.selectcolor){
		selectcolor = this.selectcolor;
	}
    
    //initialisation des effets
    $('.menuitem' + this.id ).hover(function(){
    	$(this).css("color" , selectcolor);
    }, function() {
    	$(this).css("color" ,color);
    });
	
    if(this.type=='hideword'){
    
      var wb = parseInt(this.w * zoom);
      var hb = parseInt(this.h * zoom);
      var larg = parseInt(parseInt(this.fontsize * 1.5) * zoom);
			
			if(jQuery().hideWords){
			
				if(window.console){console.log('hideWords: is a jQuery function');}
			
			}else{
				if(window.console){console.log('hideWords: is not a jQuery function');}
				if('function' == typeof(hideWords)){
				
				}else{
					$.getScript('javascript/jquery.hidewords.js', function(){
						logconsole("hideWords is include");
					});
				}
			}

			if(jQuery().hideWords){
				$('.bloc' + this.id).hideWords({
					large: larg ,
					words: this.text ,
					width: wb,
					height: hb
				});
			}
    }//**
	
	if(this.type=='flux'){
		loadFlux();
		loadObjetsFlux(this,0);
	}
	
    if(this.type=='anim'){
		
		installAnimateImages(this.id);
	  
    }
	
	if(this.type=='textarea'){
		var hb = parseInt(this.h * zoom);
		$("#viewport"+ this.id).css("height", hb + "px");
		$('.bloc' + this.id ).tinyscrollbar();
	}
 
	objetanim(this);
	//if(transitionPage=='Direct'){
	//$("#colorfond").fadeIn();
	$("#colorfond").css("display","block");
	
 }//**
 	
}//**

function installAnimateImages(i){
	
	if(jQuery().animateImages){
		
		var obj = CObjets[i];
		
		$('.bloc' + obj.id).animateImages({
			folder: "images",
			loop  : obj.text,
			data  : obj.src,
			time  : obj.data
		});
		
	}else{
		
		var tkanim = document.createElement('script');
		tkanim.src = 'javascript/jquery.anim.js';
		tkanim.type = 'text/javascript';
		var s = document.getElementsByTagName('script')[0];
		s.parentNode.insertBefore(tkanim, s);

		setTimeout('installAnimateImages(' + i+ ')',300);
		
	}
	
}

var page_id = "";

//Collections d'éléments arbos
var CObjets = new Array();
var CObjets_count = 0;

//Collections CArbos
function CObjets_Add(Elem){
  
    Elem.id = CObjets_count;
    CObjets.push(Elem);
    CObjets_count = CObjets_count +1;
  
}

function CObjets_Paint(){
	
	appliqueFond();
	
	for (var i = 0; i < CObjets_count; i++){
		CObjets[i].show_element();
	}
	
}

//paint=(setInterval("CObjets_Paint()",90));

function noresp(){
	$("#loaddiv,#loaddivbarre").css("display","none");
}

function lastPage(){
	var ur = "data/page" + lastPage1  + ".xml";
	loaddata(ur);
}

function loadFile(f){
	
	if(f!=''){
		
		f = formatLangUrl(f);
		
		if(typeof dataExterneXml!=='undefined'){
			externdata = 'electronxml';
			externdatafilterpage = 0;
		}
		
		var datamem = '';
		
		nbClickAct = 0;
		TouchDistant = 10;
		setTimeout(function(){nbClickAct = 1;},300);
		
		//Extern Data
		if(externdata!=''){
			
			externdatafilterpage = 0;
			try
			{
				externdatafilterpage = f.replace("data/page","");
				externdatafilterpage = externdatafilterpage.replace(langueextend,"");
				externdatafilterpage = externdatafilterpage.replace(".xml","");
			}
			catch(err)
			{
				externdatafilterpage = 0;
			}
			
			if (typeof dataExterneXml !== 'undefined'){
				
				datamem = dataExterneXml;
				datamemoire[f] = dataExterneXml;
				
			}else{
			
				$.ajax({
					type: "GET",
					url: externdata,
					dataType: (isMsie()) ? "text" : "xml",
					cache:false,async:false,
					success: function(data) {
						datamem = data;
						datamemoire[f] = data;
					},error: function(){
						alert('error');
					}
				});
			
			}
			
		}else{
		
			try
			{
				datamem = datamemoire[f];
				if (typeof datamem === "undefined"){datamem='';} 
			}
			catch(err)
			{
				datamem = '';
			}
			
		}
		
		if(datamem!=''){
			
			afficheData(datamemoire[f],f);
			
		}else{
			
			var d = new Date();
			var n = d.getMinutes();
			
			if(localExec()){
				
				var p = f.replace(".xml","");
				p = p.replace("data/","");
				p = p.replace(langueextend,"");
				p = p.replace("page","");
				data = poff[parseInt(p)];
				
				if(langueextend!=''){
					if(poff[parseInt(p) + langueextend] === undefined){
					}else{
						data = poff[parseInt(p)+ langueextend]
					}
				}
				
				if(data!=''){
					afficheData(data,f);
				}else{
					noresp();
				}
				
			}else{
			
				$.ajax({
					type: "GET",
					url: f,
					dataType: (isMsie()) ? "text" : "xml",
					cache:false,
					async:false,
					success: function(data) {
						afficheData(data,f);
						savestoragebyloadFile(f);
						evolinit = evolinit + 60;
					},error: function(){
						data = getstorage(f);
						if(data!=''){
							afficheData(data,f);
						}else{
							noresp();
						}
					}
				});
			}
		}
		
		if(oldTransitionPage=='Doors'){
			
			$('#globalcurtainanim2').animate({left:"51%",width:"49%"}, 500);
			$('#globalcurtainanim').animate({
			width: "49%"
			}, 500, function(){
				$('#globalcurtainanim2').animate({left:"100%",width:"0%"}, 500);
				$('#globalcurtainanim').animate({
				width: "0%"
				}, 500, function(){
					$("#globalcurtain").css("display","none");		
				});
			});
			
		}
		
		if(oldTransitionPage=='Curtain'){
			oldTransitionPage = '';
			$('#globalcurtainanim').animate({
			left:  "0%",
			width: "50%"
			}, 200, function() {
				$('#globalcurtainanim').animate({
					width: "0%"
					}, 200, function() {
						$("#globalcurtain").css("display","");
						$("#globalcurtainanim").css("display","");
						$("#globalcurtainanim2").css("display","none");
				});
			});
		}
	
	}
	
}

var datamemoire = new Array();
var soundmemoire = new Array();

//Reformatage de l'url
function formatLangUrl(ur){

	if(ur.indexOf(langueextend)==-1&&langueextend!=''){
		ur = ur.replace(".xml",langueextend + ".xml");
	}
	return ur;
}

function preloadFile(f,i){
	
		f = formatLangUrl(f);
		
		$.ajax({
			type: "GET",
			url: f,
			dataType: (isMsie()) ? "text" : "xml",
			cache:false,
			async:true,
			success: function(data){
				datamemoire[f] = data;
				savestoragebyloadFile(f);
				soundmemoire[f] = findSonLoad(data,i);
				evolinit = evolinit + 15;
			},
			error: function(){
				datamemoire[f] = '';
				evolinit = evolinit + 20;
			}
		});
	
}

function preloadAllFile(){
	
	//Pas d'ajax
	if(localExec()){
		
		preLoadImgPage(1);
		evolinit=evolinit + 60;
		
	}else{
		
		var nb = parseInt(gebi("DiapoNbDiapo").innerHTML);

		if(nb<3){
			evolinit = evolinit + 240;
		}
		
		if(oldnav){
			
			evolinit = evolinit + 110;
		
		}else{
			
			var timeecart = 250;
			
			for (i=0; i<nb; i++){
				
				//preloadFile('data/page' + i + '.xml');
				setTimeout("preloadFile('data/page" + i + ".xml','" + i + "');", timeecart);
				timeecart = timeecart + 250;
			}

		}
		
	}

}

function afficheData(data,f){
	
	f = formatLangUrl(f);
	
	orix = 0;
	
	if(transitionPage=='Direct'||transitionPage=='Slide'){
		$("#globaltransition").css("display","none");
		$("#globalcurtain").css("display","none");
	}
	
	Base_memoire = new Array();
	
	//Mémoire du tirage
	if(!BasePageTirageUnique.hasOwnProperty(f)){
		BasePageTirageUnique[f] = new Array();
	}
	
	transitionPage = 'Classic';
	transitionDirection = '';

	$(".outObjBody").remove();
	
	$("#main").empty();
	$("#main").append(getbasedisplayMessage());
	
	if(oldTransitionPage=='Slide'){
		installSliderScreen();
	}
	
	CPlan_count = 0;
	
	openXML(data);
	appliqueFond();
	addDom();
	
	TouchDistant = 10;
	
	CObjets_Paint();
	transitionX = 0;
	CObjets_Paint();
	
	if(oldTransitionPage=='Slide'){
		installSliderScreen2();
	}
	
	//noNavigueparam 
	if(!gebi("noNavigueparam")){
		var p = f.replace(".xml","");
		p = p.replace("data/","");
		p = p.replace(langueextend,"");
		location.hash = "#" + p;
		active_hash = "#" + p;
		current_hash = active_hash;
	}

	//Cancel old transition slide
	if(transitionPage!='Slide'&&oldTransitionPage=='Slide'){
		oldTransitionPage = '';
	}
	if(transitionPage=='Slide'){
		recupMainStateHtml();
	}

	if(transitionPage=='Gaussian'){
		
		sdn("#loaddiv,#loaddivbarre");
		
	}else{
	
		if(transitionPage!='Direct'){
			$("#loaddiv,#loaddivbarre").fadeOut(400);
		}else{
			sdn("#loaddiv,#loaddivbarre");
		}
		
	}

	if(IOS==0){
		StopAllSounds();
		stopAllSound();
		playallsounds();
	}
	
	for (var i = 0; i < CObjets_count; i++){
		CObjets[i].init();
	}
	
	LUDIrunScript = true;
	LUDIrunPage = true;	
	onCorrection = false;
	acceptCorrection = false;
	
	if(scriptdiapo!=''){
		eval(scriptdiapo);
	}
	
	screenTime = (new Date()).getTime();

	setTimeout("anim12();", 2000);
	
	setTimeout("loadScormBase();", 1000);
	
	setTimeout("execScriptLoop();", 500);
	
	//Retour en haut
	if(oriTypeSite=="classic-responsive"){
		if(typesite=="mobile"){
			$("body").scrollTop(0);
			window.scrollTo(0, 0);
		}
	}
	
	if(unikProgressionAll==false){
		setTimeout("loadProgressionAll();", 600);
	}else{
		saveProgressionAll();
	}
	
}

function loadScormBase(){
	
	var scormP = false;
	
	for (var i = 0; i < CObjets_count; i++){
		if(CObjets[i].type=='badge'
		||CObjets[i].type=='note'
		||CObjets[i].type=='finalprogressbar'){
			scormP = true;
		}
	}
	
	if(scormP){
		//Envoi vers la com SCORM
		if('function' == typeof(SetScormScore)){
			SetScormScore(parseInt(scormProcessScore));
			SetScormComplete();
		}
	}

}

function findSonLoad(data,i){
	
	var xml_p;
	var retourson = '';
	if (typeof data == "string") {
		xml_p = StringtoXML(data);
	}else{
		xml_p = data;
	}
	
	$(xml_p).find('bloc').each(function(){
		
		var Vtype = $(this).find('type').text();
		
		if(Vtype=='son'){
			retourson = $(this).find('text').text();
		}
		if(Vtype=='qcm'){
			allDiapoQuest[i] = '1';
		}

	 });
	
	return retourson;
	
}

var memimagepreload = "";

function preloadAllFileFd(i){

	if(i>1000){
		return false;
	}
	var data = poff[i];
	if(typeof data === 'undefined'){
	i++;data = poff[i];}

	if(typeof data !== 'undefined'){

	}

}

function preLoadImgPage(i){
	
	if(i>1000){
		return false;
	}
	
	var data = poff[i];
	
	if(typeof data === 'undefined'){
	i++;data = poff[i];}
	if(typeof data === 'undefined'){
	i++;data = poff[i];}
	if(typeof data === 'undefined'){
	i++;data = poff[i];}
	if(typeof data === 'undefined'){
	i++;data = poff[i];}
	if(typeof data === 'undefined'){
	i++;data = poff[i];}
	
	if(typeof data !== 'undefined'){
		
		var nbHit = 0;

		var xml_p;
		
		if (typeof data == "string") {
			xml_p = StringtoXML(data);
		}else{
			xml_p = data;
		}
			
			var tempFond;
			$(xml_p).find('fond').each(function(){
				tempFond=$(this).find('data').text();
				if(isImageC(tempFond)){
					var imgBack=new Image();imgBack.onload=function(){};
					imgBack.src=tempFond;
					memimagepreload=memimagepreload + tempFond + ';';
				}
			});

		$(xml_p).find('bloc').each(function(){
			
			var tempType = $(this).find('type').text();
			var tempBoite = $(this).find('boite').text();
			
			if(tempType=='img'){
				var tempSrc = $(this).find('src').text();
				if(isImageC(tempSrc)){
					var imgLoadProcess = new Image();
					imgLoadProcess.onload = function(){};
					imgLoadProcess.src = tempSrc;
					nbHit++;
				}
				var tempAlter = $(this).find('contenu7').text();
				if(isImageC(tempAlter)){
					var imgLoadProcessAlt = new Image();
					imgLoadProcessAlt.onload = function(){};
					imgLoadProcessAlt.src = tempAlter;
					nbHit++;
				}
			}
			
			if(tempType=='circletarget'){
				var tempdata = $(this).find('data').text();
				if(isImageC(tempdata)){
					var imgLoadcircletarget = new Image();
					imgLoadcircletarget.onload = function(){};
					imgLoadcircletarget.src='images/' + tempdata;
					nbHit++;
				}
			}
			
			if(tempType=='gamechangeimages'){
				var tempDataSrc = $(this).find('src').text();
				if(isImageC(tempDataSrc)){
					var imgLoadCir1 = new Image();
					imgLoadCir1.onload = function(){};
					imgLoadCir1.src = tempDataSrc;
					nbHit++;
				}
			}
			
			if(tempType=='qcm'&&tempBoite!=''){
				var tempSrcQcm0 = 'fx/qcm/' + tempBoite + '0.png';
				var tempSrcQcm1 = 'fx/qcm/' + tempBoite + '1.png';
				var imgQcm0 = new Image();
				imgQcm0.onload = function(){};
				imgQcm0.src = tempSrcQcm0;
				nbHit++;
				var imgQcm1 = new Image();
				imgQcm1.onload = function(){};
				imgQcm1.src = tempSrcQcm1;
				nbHit++;
			}

		});
		
	  if(nbHit==0){
			setTimeout(function(){preLoadImgPage(i+1);},1000);
		}else{
			if(i<5){
				setTimeout(function(){preLoadImgPage(i+1);},3000);
			}else{
				setTimeout(function(){preLoadImgPage(i+1);},9000);
			}
		}
		
	}else{
		
		preLoadImgPage(i+1);
		
	}
	
}

var domDataH = 'no';

//Dom Supp
function addDom(){
	
	if(domaddopt){
		
		if(domDataH=='no'){
			
			$.ajax({
				type: "GET",
				url: "data/domadd.html",
				dataType: "text",
				cache:true,
				async:true,
				success: function(data) {
					if (typeof(data) == 'undefined'){data='';}
					if (data=='undefined'){data='';}
					domDataH = data;
					if (data!=''){
						$("#main").append(data);
					}
				},
				error: function() {
					domDataH = '';
				}
			});
			
		}else{
			
			if(domDataH!=''){
				$("#main").append(domDataH);
			}
			
		}
	
	}

}

var transitionX = 0;

function loaddata(f,d){
	
	allaysOnTop();
	
	if(menu_global==f||f==''){return false;}
	
	f = formatLangUrl(f);
	
	if(d=='isok'){
		MessageHelp = '';
		if(isok()==false){
			modeTypeMessageIco = 1;
			if(MessageHelp!=''){
				displayMessage(MessageHelp);
			}else{
				displayMessage(MessageFalse);
			}
			modeTypeMessageIco = 0;
			return;
		}
	}
	attemptProcess = 0;
	globalPlayAudio = 0;
	
	stopSequencesSound();
	
	if(IOS==1){
		var snd = soundmemoire[f];
		if(snd!=''){
			if(globalMusic==1){
				StopAllSounds();
				stopAllSound();
				playSoundOne(snd,'');
			}
		}
	}
	
	clockId = ''; 
	activeNoeud = 0;//Identifiant du dialogue
	LUDIwait = 0;
	nbqcmunique = 0;
	
	menu_global = f;
	
	var ipage = 0;
	try
	{
	ipage = f.replace("data/page","");
	ipage = ipage.replace(langueextend,"");
	ipage = ipage.replace(".xml","");
	}
	catch(err)
	{
		ipage = 0;
	}
	
	lastPage1 = lastPage0;
	lastPage0 = parseInt(ipage);
	
	if(d!='nonote'&&d!='cross'){
		calculnote();
	}

	var onlyOneLF = true;
	
	//Explose
	if(transitionPage=='Explose'){
		
		for (var i = 0; i < CObjets_count; i++){
			
			var sid = ".bloc" + CObjets[i].id +",.alterbloc" + CObjets[i].id;
			
			var rand = Math.floor(Math.random() * 2) + 1;
			
			if(rand==1){
				if(CObjets[i].getY()<350){
					$(sid).animate({
					marginTop:"-1200px"
					}, 1000, function() {});
				}else{
					$(sid).animate({
					marginTop:"1200px"
					}, 1000, function() {});
				}
			}else{
				if(CObjets[i].getX()<450){
					$(sid).animate({
					marginLeft:"-1200px"
					}, 1000, function() {});
				}else{
					$(sid).animate({
					marginLeft:"1200px"
					}, 1000, function() {});
				}
			}
			
		}
		if(onlyOneLF){
			setTimeout("loadFile('" + f + "');", 1100);
			onlyOneLF = false;
		}
		
		
	}
	
	if(transitionPage=='Classic'){
		
		if(ludiHaveVideo==false){
			sdb("#loaddiv,#loaddivbarre");
		}
		
		transitionX = 1;
		ludiHaveVideo = false;
	
	}
	
	if(transitionPage=='Curtain'){

		oldTransitionPage = 'Curtain';

		sdb("#globalcurtain");
		$("#globalcurtainanim").css("display","");
		$("#globalcurtainanim2").css("display","none");
		$('#globalcurtainanim').css("left",'99%');
		$('#globalcurtainanim').css("top",'0px');
		$("#globalcurtainanim").css("width", '3%');
		$("#globalcurtainanim").css("height", '100%');
		$('#globalcurtainanim').css("opacity" , "1");
		
		if(gebi("loaddiv")){
			
			var loadDivObj = gebi("loaddiv");
			
			if(loadDivObj.tagName.toLowerCase()=='img'){
				var newurlimg =  gebi("loaddivtransiimage").innerHTML;
				$('#globalcurtainanim').css("background-image" , "url('" + newurlimg + "')");
				
			}else{
				$('#globalcurtainanim').css("background" , loadDivObj.style.backgroundColor);
			}
		
		}
		
		$('#globalcurtainanim').animate({
			left:  "2%",
			width: "99%"
			}, 500, function() {
				$('#globalcurtainanim').animate({
					left:  "0%",
					width: "100%"
					}, 500, function() {

						if(onlyOneLF){
							loadFile(f);
							onlyOneLF = false;
						}
					
				});
		});

		setTimeout(function(){
			$("#globalcurtain").css("display","none");
			$("#globalcurtainanim").css("display","none");
			$("#globalcurtainanim2").css("display","none");
		}, 1300);

	}

	if(transitionPage=='Doors'){

		oldTransitionPage = 'Doors';
	
		sdb("#globalcurtain,#globalcurtainanim,#globalcurtainanim2");

		$('#globalcurtainanim').css("left",'0%').css("top",'0px');
		$("#globalcurtainanim").css("width", '2%').css("height", '100%');
		$('#globalcurtainanim').css("opacity" , "1");
		$('#globalcurtainanim2').css("left",'100%').css("top",'0px').css("width", '2%');
		$("#globalcurtainanim2").css("height", '100%').css("opacity" , "1");
		
		if(gebi("loaddiv")){	

			var loadDivObj = document.getElementById("loaddiv");

			if(loadDivObj.tagName.toLowerCase()=='img'){
				var newurlimg =  gebi("loaddivtransiimage").innerHTML;
				$('#globalcurtainanim,#globalcurtainanim2').css("background-image" , "url('" + newurlimg + "')");
				
			}else{
				$('#globalcurtainanim,#globalcurtainanim2').css("background" , loadDivObj.style.backgroundColor);
			}
		
		}
		
		$('#globalcurtainanim').animate({
			width: "50%"
			}, 500, function(){loadFile(f);});
		
		$('#globalcurtainanim2').animate({
			left:  "50%",width: "51%"
		}, 500);

		setTimeout(function(){
			$("#globalcurtain").css("display","none");
			$("#globalcurtainanim").css("display","none");
			$("#globalcurtainanim2").css("display","none");
		}, 1300);

	}
	
	if(transitionPage=='Gaussian'){
		$("#loaddiv").css("background","transparent");
		sdb("#loaddiv,#loaddivbarre");
		$('.haveflou').animate({
			opacity: 0,
			marginLeft:"-12px"
			}, 500, function() {
			loadFile(f);
		});
	}
	
	if(transitionPage=='Zoom out'){
	
		zoomExterieur();
		
		$('#loaddiv').animate({
			opacity: 0.1}, 400, function() {
			loadFile(f);
		});
		
		transitionX = 1;
	
	}
	
	if(transitionPage=='Classic'){
		
		$('#loaddiv').animate({
			opacity: 0.9}, 500, function() {
		});
		loadFile(f);
	}
	
	if(transitionPage=='Direct'){
		fakeContent();
		if(onlyOneLF){
			loadFile(f);
			onlyOneLF = false;
		}
		
		transitionX = 0;
	}

	if(transitionPage=='Slide'){
		oldTransitionPage = 'Slide';
		if(onlyOneLF){
			loadFile(f);
			onlyOneLF = false;
		}
		transitionX = 0;
	}
	
}

var MessageHelp = '';

function openDialogYNDown(title,file){
	
	var inn = '<p class="dialogDownTitle" >' + title + '</p>';
	inn += '<p><a class="buttonDialogDownNo" href="javascript:closeYNDown();" >Non</a>&nbsp;';
	inn += '<a class="buttonDialogDownYes" target="_blank" ';
	inn += ' href="data/' + file + '" onClick="closeYNDown();" >Oui</a></p>';
	
	if(!gebi("dialogDown")){
		var h = '<div id="dialogDown" style="display:none;" >';
		h += inn;
		h += '</div>';
		addToM(h);	
	}else{
		gebi("dialogDown").innerHTML =  inn ;
	}
	
	$("#dialogDown").fadeIn();
	
	var wb = parseInt(350 * zoom);
	var hb = parseInt(60 * zoom);
	hb = gebi("dialogDown").offsetHeight;
	$("#dialogDown").css("width", wb + "px").css("z-index",'1000').css("margin-top", "-" + parseInt(hb/2) + "px");
	
}

function confirmLUDI(title,Msg){
	
	var inn = '<p class="dialogDownTitle" >' + title + '</p>';
	inn = inn + '<p><a class="buttonDialogDownNo" href="javascript:closeYNDown();" >Non</a>&nbsp;';
	inn = inn + '<a class="buttonDialogDownYes" href="javascript:LUDI.nextPage();" >Oui</a></p>';
	if(!gebi("dialogDown")){
		var h = '<div id="dialogDown" style="display:none;" >';
		h = h + inn;
		h = h + '</div>';
		addToM(h);
			
	}else{

		gebi("dialogDown").innerHTML =  inn ;

	}
	
	$("#dialogDown").fadeIn();
	
	var wb = parseInt(350 * zoom);
	var hb = parseInt(60 * zoom);
	hb = gebi("dialogDown").offsetHeight;
	$("#dialogDown").css("width", wb + "px").css("z-index",'1000').css("margin-top", "-" + parseInt(hb/2) + "px");
	
}

function closeYNDown(){
	$("#dialogDown").css("display","none");
}

$(function(){
	
	var control_hash = window.location.hash;
	
	if(current_hash==''){
		
		var numDiapo = 'page0';
		
		if(document.getElementById("numpagemem")){
			numDiapo = gebi("numpagemem").innerHTML;
		}
		
		if(numDiapo!='page0'){
			evolinit = evolinit + 180;
			lastPage0 =  parseInteger(numDiapo);
		}
		
		var hurl = 'data/' + numDiapo + '.xml';
		
		menu_global = hurl;
		loadFile(hurl);
		
	}else{
		
		var p = current_hash.replace("#","");
		if (typeof(p) == 'undefined'){return false;}
		if(numDiapo!='page0'){
			lastPage0 =  parseInteger(p);
		}
		menu_global = 'data/' + p + '.xml';
		
	}
	
	//preloadAllFile();
	
});

//HISTORY BACK
var current_hash = '';
var active_hash = '';

function verifPage(){
	
	if(!gebi("noNavigueparam")){
		
		current_hash = window.location.hash;
		
		if(active_hash!=current_hash&&active_hash!=''){
			if(current_hash!=''){
				active_hash = current_hash;
				var p = active_hash.replace("#","");
				if (typeof(p) == 'undefined'){return false;}
				menu_global = 'data/' + p + '.xml';
				Url = menu_global;
				if(p!=''){
					loadFile( 'data/' + p + '.xml');
				}
			}
		}
	
	}
	
}

//paint=(setInterval("verifPage()",500));
//HISTORY BACK

var nbqcmunique = 0;
var qcmRandomize = -1;

var StockRepQ = new Array();

function CRepQ(){	
    this.k;this.v;
}
function installqcm(obj){
	
	if(obj.type=='qcm'&&obj.theme=='barre'){
		installqcmbarre(obj);
	}
	
	if(obj.type=='qcm'&&(obj.theme==''||obj.theme=='tight')){

		var marginT = '';

		if(obj.cssadd.indexOf("fixe")!=-1){
			obj.theme ='tight';
			marginT = 'padding:0px;margin:0px';
		}
		
		allDiapoQuest[parseInt(lastPage0)] = '1';
		
		var Ecran = document.getElementById("main");
		
		var color = "black";
		
		if(obj.color){
			color = obj.color;
		}
		
		if(qcmRandomize==-1){
			qcmRandomize = Math.floor(Math.random() * 5);
			if(qcmRandomize==0){qcmRandomize=1;}if(qcmRandomize==5){qcmRandomize=4;}
		}
		
		var myString = "";
		
		appliqueDataQCM(obj);
		
		if(obj.text){
			myString = obj.text;
		}
		
		var eachElement = myString.split(';');
		
		var MyNotes7 = "";
		if(obj.contenu7){
			MyNotes7 = obj.contenu7;
		}
		if(MyNotes7==""){"||||||||||||||||||"}
		
		var eachElementNote = MyNotes7.split('|');
		
		var bilansource = '<div class="blockbilan" >';
		bilansource = bilansource + '<div class="questbilan" >-prv-' + obj.contenu3 + '</div>';
		bilansource = bilansource + '<ul>';
		
		var h = '<table style="' + marginT + 'display:none;color:' + color + ';' +  obj.cssadd + '" ';
		h = h + ' id="table' + obj.id + '" class="unselectable haveflou bloc' + obj.id +  ' ' +  obj.idscript + '" ';
		h = h + ' >';
		
		if(eachElement==null){
		
			h = h + '<tr style="' + marginT + '" ><td style="' + marginT + 'text-align:center; border:dotted 1px gray;" ' + act + ' >';
			h = h + 'Erreur';
			h = h + '</td></tr>';
		
		}else{
		
		var randomname = "qcm" + Math.floor(Math.random()*10000) + "qcm" ;
		
		//Calcul du nombre de reponses Multiple ou Non
		var nbrep = 0;
		var totalrep = 0;
		for(var e = 0 ; e < eachElement.length; e++){
			var reponse = eachElement[e];
			if(reponse.indexOf('*')==0){
				nbrep = nbrep + 1;
			}
			if(reponse!=''){
				totalrep = totalrep + 1;
			}
		}
		if(nbrep>1){randomname = '';}
		//Calcul du nombre de reponses Multiple ou Non
		
		//Ramdomize
		if(obj.option4==1){
			if(totalrep>2){
				
				if(qcmRandomize==1){
					var ElemRepo = eachElement[0];
					var ElemNote = eachElementNote[0];
					eachElement[0] = eachElement[2];
					eachElementNote[0] = eachElementNote[2];
					eachElement[2] = ElemRepo;
					eachElementNote[2] = ElemNote;
				}
				if(qcmRandomize==2){
					var ElemRepo = eachElement[0];
					var ElemNote = eachElementNote[0];
					eachElement[0] = eachElement[1];
					eachElementNote[0] = eachElementNote[1];
					eachElement[1] = ElemRepo;
					eachElementNote[1] = ElemNote;
				}
				if(qcmRandomize==3){
					var ElemRepo = eachElement[totalrep-1];
					var ElemNote = eachElementNote[totalrep-1];
					eachElement[totalrep-1] = eachElement[0];
					eachElementNote[totalrep-1] = eachElementNote[0];
					eachElement[0] = ElemRepo;
					eachElementNote[0] = ElemNote;
				}
				if(qcmRandomize==4){
					var ElemRepo = eachElement[totalrep-1];
					var ElemNote = eachElementNote[totalrep-1];
					eachElement[totalrep-1] = eachElement[totalrep-2];
					eachElementNote[totalrep-1] = eachElementNote[totalrep-2];
					eachElement[totalrep-2] = ElemRepo;
					eachElementNote[totalrep-2] = ElemNote;
				}
			}
		}
		
		var preImg = "";
		
		for(var e = 0 ; e < eachElement.length; e++){
			
			h += '<tr style="' + marginT + '" >';
			
			var checkclass = 'qcmn';
			
			var reponse = eachElement[e];
			
			var rep = eachElement[e];
			
			if(reponse.indexOf('*')==0){
				checkclass = 'qcmx';
				reponse = reponse.replace('*', '');
			}
			//reponse = '&nbsp;' + reponse;
			
			var caseHtml = '<td class="qcmcoche qcmcoche' + obj.id + '" style="' + marginT + 'cursor:pointer;text-align:center;" >';
			
			//Div
			caseHtml += '<div style="position:relative;z-index:0;" class="qcmcoche' + obj.id + '"  >';
			
			//CASE A COCHER
			caseHtml = caseHtml + '<table class="qcmt cochecase cochecase' + obj.id + '" ' ;
			caseHtml = caseHtml + ' style="z-index:1;position:absolute;' + marginT + 'left:0px;top:0px;color:' + color + ';';
			if(obj.boite==''){
				caseHtml = caseHtml + 'border:solid 1px ' + color + ';" >';
			}else{
				caseHtml = caseHtml + '" >';
			}
			
			var tdid = obj.id + 'td' + e;
			var classm = ' carre' + obj.id ;
			
			var namerep = rep;
			var idRep = LUDI.guid();
			var rq = new CRepQ();
			rq.k = idRep;
			rq.v = namerep;
			StockRepQ.push(rq);

			caseHtml += '<tr style="' + marginT + '" >';
			caseHtml += '<td id="' + tdid + '" name="' + idRep + '" style="' + marginT + '" class="' + checkclass + ' ' +  randomname + classm + '" ';
			
			if(obj.option3==1){
				caseHtml = caseHtml + ' data-note="' + eachElementNote[e] + '" ';
			}
			var actionmini = '';
			
			if(obj.boite==''){
				actionmini = 'unCheckJP(\'' +obj.id+'\',\'\');makechecked(\'' + tdid + '\',\'' + randomname + '\');';
				caseHtml = caseHtml + ' onclick="' + actionmini + '" >';
			}else{
				caseHtml = caseHtml + ' >';
			}
			
			caseHtml = caseHtml +'</td></tr></table>';
			
			//Style Particulier
			if(obj.boite!=''){
				var imgid = obj.id + 'img' + e;
				caseHtml += '<img id="' + imgid + '" src="fx/qcm/' + obj.boite + '0.png" class="cocheimg img' +  randomname + ' cocheimg' + obj.id + '" ';
				caseHtml += ' style="z-index:2;position:absolute;color:' + color + ';width:10px;height:10px;' + marginT + 'cursor:pointer;" ';
				actionmini = 'unCheckJP(\'' +obj.id+'\',\'' + obj.boite + '\');makecheckedimg(\'' + imgid + '\',\'' + tdid + '\',\'' + randomname + '\',\'' + obj.boite + '\');';
				caseHtml = caseHtml + ' onclick="' + actionmini + '" >';
				preImg = "fx/qcm/" + obj.boite + "1.png";
			}
			
			//CASE A COCHER
			caseHtml = caseHtml + '</div></td>';
			
			
			//A gauche
			if(obj.contenu8==0){
				h += caseHtml;
			}
			
			if(obj.contenu8==0){
				h += '<td class="selectqcmline" style="' + marginT + 'text-align:left;padding-left:10px;" ';
			}else{
				h += '<td class="selectqcmline" style="' + marginT + 'text-align:right;padding-right:10px;" ';
			}
			
			if(actionmini!=''){
				h = h + ' onclick="' + actionmini + '" ';
			}
			h += ' >';
			h += convertQcmToTexte(reponse);
			h += '</td>';

			//A droite
			if(obj.contenu8==1){
				h +=  caseHtml;
			}
			
			h += '</tr>';
			
			if(checkclass =='qcmx'){
				bilansource = bilansource + '<li>' + idRep + '<span class="goodRepBilan" >&nbsp;&#9632;</span></li>';
			}else{
				bilansource = bilansource + '<li>' + idRep + '</li>';
			}
			
		}//For
		
	   }//eachElement == null
	   
		//Je passe
		if(obj.option==1){
			h = h + getJePasse(randomname,obj,false);
		}
		
		h = h + '</table>';
		
		bilansource = bilansource + '</ul>-cmt-</div>';
		obj.bilan = bilansource;
		
		//Ecran.innerHTML = Ecran.innerHTML + h;
		
		return h;
		
		if(preImg!=""){
			$('body').append("<img class='previmg' src='" + preImg + "' style='position:absolute;right:0px;bottom:0px;width:2px;height:2px;' />" );
		}
		
		recupDataObjectMem(obj,lastPage0);
		
	}
	
	if(obj.type=='qcmunique'){
		
		var randomname = "qcm" + Math.floor(Math.random() * 10000) + "qcm" ;
		
		var Ecran = document.getElementById("main");
		var color = "black";
		if(obj.color){color = obj.color;}
		
		var h = '<table style="position:absolute;color:' + color + ';" ';
		h += ' id="table' + obj.id + '" class="bloc' + obj.id + '"  ><tr>';
		h += '<td style="padding:0;margin:0;" >';
		
		var tdid = obj.id + 'qcmunique';
		
		//CASE A COCHER
		h += '<table class="qcmt cochecase cochecase' + obj.id + '" ' ;
		h += ' style="color:' + color + ';width:20px;height:20px;';
		if(obj.boite==''){
			h += 'border:solid 1px ' + color + ';" >';
		}else{
			h += '" >';
		}
		
		var checkclass = 'qcmn';
		if(obj.data=='X'){
			checkclass = 'qcmx';
			nbqcmunique = nbqcmunique + 1;
		}
		
		h += '<tr><td id="' + tdid + '" ';
		h += ' name="' + checkclass + '" ';
		h += ' class="' + randomname + '" ';
		
		if(obj.boite==''){
			h += ' style="padding:0;margin:0;text-align:center;cursor:pointer;background:white;"  ';
			h += ' onclick="makecheckedUnik(this,\'' + randomname + '\');" >';
		}else{
			h += ' style="padding:0;margin:0;text-align:center;cursor:pointer;background:transparent;"  ';
			h += ' >';
		}
		
		h += '';
		h += '</td></tr></table>';
		
		//Style particulier
		if(obj.boite!=''){
			h += '<img src="fx/qcm/' + obj.boite + '0.png" class="cocheimg img' +  randomname + ' cocheimg' + obj.id + '" ';
			h += ' style="color:' + color + ';width:10px;height:10px;cursor:pointer;" ';
			h += ' onclick="makecheckedimgUnik(this,\'' + tdid + '\',\'' + randomname + '\',\'' + obj.boite + '\');" />';
		}
		
		h += '</td></tr></table>';
		
		Ecran.innerHTML = Ecran.innerHTML + h;
		
		recupDataObjectMem(obj,lastPage0);
		zoomQcm(obj);
	
	}

}

function installqcmbarre(obj){
	
		allDiapoQuest[parseInt(lastPage0)] = '1';
		
		var color = "black";
		
		if(obj.color){
			color = obj.color;
		}
		
		if(qcmRandomize==-1){
			qcmRandomize = Math.floor(Math.random() * 5);
			if(qcmRandomize==0){qcmRandomize=1;}if(qcmRandomize==5){qcmRandomize=4;}
		}
		
		var myString = "";
		
		appliqueDataQCM(obj);
		
		if(obj.text){
			myString = obj.text;
		}
		
		var eachElement = myString.split(';');
		
		var MyNotes7 = "";
		if(obj.contenu7){
			MyNotes7 = obj.contenu7;
		}
		if(MyNotes7==""){"||||||||||||||||||"}
		
		var eachElementNote = MyNotes7.split('|');
		
		var bilansource = '<div class="blockbilan" >';
		bilansource = bilansource + '<div class="questbilan" >-prv-' + obj.contenu3 + '</div>';
		bilansource = bilansource + '<ul>';
		
		
		var cols = obj.border.split('|');
		
		
		var h = '<table cellpadding=0 cellspacing=1 ';
		h = h + 'style="display:none;';
		h = h + 'color:' + color + ';';
		h = h + 'border:solid 1px ' + cols[0] + ';';
		h = h + 'background-color:' + cols[1] + ';';
		h = h + obj.cssadd + '" ';
		h = h + ' id="table' + obj.id + '" ';
		h = h + ' class="unselectable barBody haveflou bloc' + obj.id +  ' ' +  obj.idscript + '" ';
		h = h + ' >';
		
		if(eachElement==null){
		
			h = h + '<tr><td style="text-align:center; border:dotted 1px gray;" ' + act + ' >';
			h = h + 'Erreur';
			h = h + '</td></tr>';
		
		}else{
		
		var randomname= "qcm" + Math.floor(Math.random()*10000) + "qcm" ;
		
		//Calcul du nombre de r�ponses Multiple ou Non
		var nbrep = 0;
		var totalrep = 0;
		for(var e = 0 ; e < eachElement.length; e++){
			var reponse = eachElement[e];
			if(reponse.indexOf('*')==0){
				nbrep = nbrep + 1;
			}
			if(reponse!=''){
				totalrep = totalrep + 1;
			}
		}
		if(nbrep>1){randomname = '';}
		//Calcul du nombre de r�ponses Multiple ou Non
		
		//Ramdomize
		if(obj.option4==1){
			if(totalrep>2){
				
				if(qcmRandomize==1){
					var ElemRepo = eachElement[0];
					var ElemNote = eachElementNote[0];
					eachElement[0] = eachElement[2];
					eachElementNote[0] = eachElementNote[2];
					eachElement[2] = ElemRepo;
					eachElementNote[2] = ElemNote;
				}
				if(qcmRandomize==2){
					var ElemRepo = eachElement[0];
					var ElemNote = eachElementNote[0];
					eachElement[0] = eachElement[1];
					eachElementNote[0] = eachElementNote[1];
					eachElement[1] = ElemRepo;
					eachElementNote[1] = ElemNote;
				}
				if(qcmRandomize==3){
					var ElemRepo = eachElement[totalrep-1];
					var ElemNote = eachElementNote[totalrep-1];
					eachElement[totalrep-1] = eachElement[0];
					eachElementNote[totalrep-1] = eachElementNote[0];
					eachElement[0] = ElemRepo;
					eachElementNote[0] = ElemNote;
				}
				if(qcmRandomize==4){
					var ElemRepo = eachElement[totalrep-1];
					var ElemNote = eachElementNote[totalrep-1];
					eachElement[totalrep-1] = eachElement[totalrep-2];
					eachElementNote[totalrep-1] = eachElementNote[totalrep-2];
					eachElement[totalrep-2] = ElemRepo;
					eachElementNote[totalrep-2] = ElemNote;
				}
			}
		}
		
		var preImg = "";
		
		for(var e = 0 ; e < eachElement.length; e++){
			
			h = h + '<tr class="barSty" ';
			
			h = h + 'onmouseover="this.style.background=\'' + obj.selectcolor + '\';" ';
			h = h + 'onmouseout="this.style.background=\'' + cols[1] + '\';" ';
			
			h = h + ' >';
			
			var checkclass = 'qcmn';
			
			var reponse = eachElement[e];
			
			var rep = eachElement[e];
			
			if(reponse.indexOf('*')==0){
				checkclass = 'qcmx';
				reponse = reponse.replace('*', '');
			}
			
			var styleft = "barStyQcmLeft";
			var styright = "barStyQcmRight";
			var borcolor = "border-color: " + cols[0] + ";";
			
			if(e==0){
				styleft = "";
				styright = "";
				borcolor = "";
			}
			
			var caseHtml = '<td class="qcmcoche ' + styleft + ' qcmcoche';
			caseHtml = caseHtml + obj.id + '" style="cursor:pointer;text-align:center;' + borcolor + '" >';
			
			//Div
			caseHtml = caseHtml + '<div style="position:relative;z-index:0;" class="qcmcoche' + obj.id + '"  >';
			
			//CASE A COCHER
			caseHtml = caseHtml + '<table class="qcmt cochecase cochecase' + obj.id + '" ' ;
			caseHtml = caseHtml + ' style="z-index:1;position:absolute;left:0px;top:0px;color:' + color + ';';
			if(obj.boite==''){
				caseHtml = caseHtml + 'border:solid 1px ' + color + ';" >';
			}else{
				caseHtml = caseHtml + '" >';
			}
			
			var tdid = obj.id + 'td' + e;
			var classm = ' carre' + obj.id ;
			

			var namerep = rep;
			var idRep = LUDI.guid();
			var rq = new CRepQ();
			rq.k = idRep;
			rq.v = namerep;
			StockRepQ.push(rq);
			
			caseHtml = caseHtml + '<tr><td id="' + tdid + '" name="' + idRep + '" ';
			caseHtml = caseHtml + '	style="padding:0px;margin:0px;" ';
			caseHtml = caseHtml + '	class="' + checkclass + ' ' +  randomname + classm + '" ';
			
			if(obj.option3==1){
				caseHtml = caseHtml + ' data-note="' + eachElementNote[e] + '" ';
			}
			var actionmini = '';
			
			if(obj.boite==''){
				actionmini = 'unCheckJP(\'' +obj.id+'\',\'\');makechecked(\'' + tdid + '\',\'' + randomname + '\');';
				caseHtml = caseHtml + ' onclick="' + actionmini + '" >';
			}else{
				caseHtml = caseHtml + ' >';
			}
			
			caseHtml = caseHtml +'</td></tr></table>';
			
			//Style Particulier
			if(obj.boite!=''){
				var imgid = obj.id + 'img' + e;
				caseHtml = caseHtml + '<img id="' + imgid + '" src="fx/qcm/' + obj.boite + '0.png" class="cocheimg img' +  randomname + ' cocheimg' + obj.id + '" ';
				caseHtml = caseHtml + ' style="z-index:2;position:absolute;color:' + color + ';width:10px;height:10px;cursor:pointer;" ';
				actionmini = 'unCheckJP(\'' +obj.id+'\',\'' + obj.boite + '\');makecheckedimg(\'' + imgid + '\',\'' + tdid + '\',\'' + randomname + '\',\'' + obj.boite + '\');';
				caseHtml = caseHtml + ' onclick="' + actionmini + '" >';
				preImg = "fx/qcm/" + obj.boite + "1.png";
			}
			
			//CASE A COCHER
			caseHtml = caseHtml + '</div></td>';

			//A gauche
				h = h + caseHtml;
				h = h + '<td class="selectqcmline qcmline' + obj.id ;
				h = h + ' ' + styright + '" style="text-align:left;padding-left:10px;' + borcolor + '" ';
			
			if(actionmini!=''){
				h = h + ' onclick="' + actionmini + '" ';
			}
			h = h + ' >';
			h = h + convertQcmToTexte(reponse);
			h = h + '</td>';

			h = h + '</tr>';
			
			if(checkclass =='qcmx'){
				bilansource = bilansource + '<li>' + idRep + '<span class="goodRepBilan" >&nbsp;&#9632;</span></li>';
			}else{
				bilansource = bilansource + '<li>' + idRep + '</li>';
			}
			
		}//For
		
	   }//eachElement == null
	   
		//Je passe
		if(obj.option==1){
			h = h + getJePasse(randomname,obj,true);
		}
		
		h = h + '</table>';
		
		bilansource = bilansource + '</ul>-cmt-</div>';
		obj.bilan = bilansource;

		return h;
		
		recupDataObjectMem(obj,lastPage0);
	
}

function getJePasse(randomname,obj,barre){

	var color = "black";
	if(obj.color){
		color = obj.color;
	}

	var cc = ''
	
	var styleft = "barStyQcmLeft";
	var styright = "barStyQcmRight";
	var barSty = "barSty";
	if(barre==false){
		styleft = "";
		styright = "";
		barSty = "";
	}
	
	cc = cc + '<td class="qcmcoche ' + styleft + ' qcmcoche' + obj.id + '" style="cursor:pointer;text-align:center;" >';
	
	cc = cc + '<div style="position:relative;z-index:0;" class="qcmcoche' + obj.id + '"  >';
	var checkclass = 'qcmn';
	var reponse = '&nbsp;' + obj.contenu6;
	var rep = '';
	
	//CASE A COCHER
	cc = cc + '<table class="qcmt cochecase cochecase' + obj.id + '" ' ;
	cc = cc + ' style="z-index:1;position:absolute;left:0px;top:0px;color:' + color + ';';
	
	if(obj.boite==''){
		cc = cc + 'border:solid 1px ' + color + ';" >';
	}else{
		cc = cc + '" >';
	}
	
	var tdid = obj.id + 'td100';
	var classm = ' jepasse' + obj.id;
	
	cc = cc + '<tr><td id="' + tdid + '" name="' + rep + '" class="' + checkclass + ' ' +  randomname + classm + '" ';
	
	var actionmini = '';

	if(obj.boite==''){
		actionmini = 'unCheckAll(\'' + obj.id + '\',\'\');';
		actionmini = actionmini + 'makechecked(\'' + tdid + '\',\'' + randomname + '\');detectJP(\'' + obj.id  + '\');';
		cc = cc + ' onclick="' + actionmini + '" >';
	}else{
		cc = cc + ' >';
	}
	
	cc = cc +'</td></tr></table>';
	
	//Style particulier
	if(obj.boite!=''){
		var imgid = obj.id + 'imgjepasse';
		actionmini = 'unCheckAll(\'' + obj.id + '\',\'' + obj.boite + '\');';
		actionmini = actionmini + 'makecheckedimg(\'' + imgid + '\',\'' + tdid + '\',\'' + randomname + '\',\'' + obj.boite + '\');detectJP(\'' + obj.id  + '\');';
		
		cc = cc + '<img id="' + imgid + '" src="fx/qcm/' + obj.boite + '0.png" class="cocheimg img' +  randomname + ' cocheimg' + obj.id + ' jepassecocheimg' + obj.id + '" ';
		cc = cc + ' style="z-index:2;position:absolute;color:' + color + ';width:10px;height:10px;cursor:pointer;" ';
		cc = cc + ' onclick="' + actionmini + '" ';
		cc = cc + ' >';
	}
	
	cc = cc + '</div></td>';
	//CASE A COCHER
	
	var crep = '';
	
	if(obj.contenu8==0){
		crep = crep + '<td class="' + styright + ' selectqcmline" style="text-align:left;padding-left:10px;" ';
	}else{
		crep = crep + '<td class="' + styright + ' selectqcmline" style="text-align:right;padding-right:10px;" ';
	}

	if(actionmini!=''){
		crep = crep + ' onclick="' + actionmini + '" ';
	}
	crep = crep + ' >';
	crep = crep + '<i>' + reponse + '</i>';
	crep = crep + '</td>';

	var h = '<tr class="' + barSty + '" >' + cc + crep + '</tr>';
	
	if(obj.contenu8==1){
		h = '<tr class="' + barSty + '" >' + crep + cc + '</tr>';
	}

	return h;
	
}

function zoomQcm(obj){

	if(obj.type=='qcm'&&obj.theme=='barre'){
		
		var largqcm = parseInt(44 * zoom);
		var largQcmImg = parseInt((44-8) * zoom);
		
		var tid = ".cochecase" + obj.id;
		
		if(obj.boite==''){
			$(tid).css("font-size",parseInt((obj.fontsize * 0.6) * zoom) + "px");
		}else{
			$(tid).css("font-size","1px");
			$(tid).css("color","transparent");
		}
		
		var newlargCase = (largqcm-4) + "px";
		
		$(tid).css("width",largQcmImg);
		$(tid).css("height",largQcmImg);

		
		var initlargCase = largqcm + "px";
		
		$(tid).css("width",newlargCase);
		$(tid).css("height",newlargCase);
		
		$(tid).css("left",parseInt(4* zoom)+ "px");
		$(tid).css("top",parseInt(4* zoom)+ "px");
		
		if(obj.boite==''){
			$(".qcmcoche" + obj.id).css("width",initlargCase)
			$(".qcmcoche" + obj.id).css("height",initlargCase);
		}else{
			$(".qcmcoche" + obj.id).css("width",initlargCase);
			$(".qcmcoche" + obj.id).css("height",initlargCase);
			$(".qcmline" + obj.id).css("height",initlargCase);
			var initlargDiv = (largqcm - 2) + "px";
			$(".qcmcoche" + obj.id + ":first").css("width",initlargDiv);
			$(".qcmcoche" + obj.id + ":first").css("height",initlargDiv);
			$(".cocheimg" + obj.id).css("width",largQcmImg).css("height",largQcmImg);
			$(".cocheimg" + obj.id).css("left",parseInt(4* zoom)+ "px").css("top",parseInt(4* zoom)+ "px");
		}
		
		if(obj.boite==''){
			$(".qcmx,.qcmn").css("font-size",parseInt((obj.fontsize * 0.8) * zoom) + "px");
		}
		
	}
	
	if(obj.type=='qcm'&&(obj.theme==''||obj.theme=='tight')){
	
		var largqcm = parseInt(parseInt(obj.contenu4) * zoom);

		if(largqcm<10){largqcm=10;}
	
		if(largqcm<parseInt(obj.fontsize * zoom)){
			largqcm = parseInt(obj.fontsize * zoom);
		}

		if(obj.theme=='tight'){
			var ctrC = parseInt(((obj.fontsize * 1.437) + 8) * zoom);
			if(largqcm<ctrC){
				largqcm = ctrC;
			}
		}
		
		var tid = ".cochecase" + obj.id;
		
		if(obj.boite==''){
			$(tid).css("font-size",parseInt((obj.fontsize * 0.8) * zoom) + "px");
		}else{
			$(tid).css("font-size","1px");
			$(tid).css("color","transparent");
		}
		
		var newlargCase = parseInt(largqcm-2) + "px";
		
		$(tid).css("width",newlargCase);
		$(tid).css("height",newlargCase);

		if(obj.boite==''){
			var newlargCaseSpe = parseInt(largqcm * 1.1) + "px";
			$(".qcmcoche" + obj.id).css("width",newlargCaseSpe)
			$(".qcmcoche" + obj.id).css("height",newlargCase);
			
		}else{
			if(obj.theme=='tight'){
	
				$(".qcmcoche" + obj.id).css("width",newlargCase).css("height",parseInt(largqcm) + "px");
			}else{
				$(".qcmcoche" + obj.id).css("width",newlargCase).css("height",newlargCase);
			}
		}
		
		var newlarg = largqcm + "px";
		
		$(".cocheimg" + obj.id).css("width",newlarg).css("height",newlarg);
		
		if(obj.boite==''){
			$(".qcmx,.qcmn").css("font-size",parseInt((obj.fontsize * 0.8) * zoom) + "px");
		}
	}
	
	if(obj.type=='qcmunique'){
		
		var largqcm = parseInt(obj.getH() * zoom);
		
		var tid = ".cochecase" + obj.id;
		
		var num =  $(tid).length;
		
		$(tid).css("font-size",parseInt((obj.fontsize * 0.8) * zoom) + "px");
		
		var newlargCase = parseInt(largqcm-2) + "px";
		
		$(tid).css("width",newlargCase);
		$(tid).css("height",newlargCase);
		
		var newlarg = largqcm + "px";
		
		$(".cocheimg" + obj.id).css("width",newlarg).css("height",newlarg);
		
	}
}

function unCheckAll(objid,boite){
	
	if(ViewerAfterBilan){return true;}
	
	eventCatchScript = true;
	
	$('.cocheimg' + objid).attr('src','fx/qcm/' + boite + '0.png');
	
	$('.carre' + objid).each(
		function(index) {
			$(this).html("");
		}
	);

}

function unCheckJP(objid,boite){

	if(ViewerAfterBilan){return true;}

	eventCatchScript = true;
	
	$('.jepassecocheimg' + objid).attr('src','fx/qcm/' + boite + '0.png');
	$('.jepasse' + objid).each(
		function(index) {
			$(this).html("");
		}
	);
	detectJP(objid);
}

function makecheckedimg(imgid,objid,nm,boite){

	if(ViewerAfterBilan){return true;}

	eventCatchScript = true;
	
	var img = document.getElementById(imgid);

	if(nm!=''){
		$('.' + nm).each(
			function(index) {
				$(this).html("");
				$('.img' + nm).attr('src','fx/qcm/' + boite + '0.png');
			}
		);
	}

	var obj = $('#' + objid);
	if(obj.html()!="X"){
		obj.html("X");
		img.src = 'fx/qcm/' + boite + '1.png';
	}else{
		obj.html("");
		img.src = 'fx/qcm/' + boite + '0.png';
	}

}

function makechecked(objid,nm){
	
	if(ViewerAfterBilan){return true;}

	eventCatchScript = true;
	
	var obj = document.getElementById(objid);

	if(nm!=''){
		$('.' + nm).each(
			function(index){
				$(this).html("");
			}
		);
	}
	
	if(obj.innerHTML!="X"){
		obj.innerHTML = "X";
	}else{
		obj.innerHTML = "";
	}

}

function detectJP(ido){
	
	if(ViewerAfterBilan){return true;}

	if($('.jepasse' + ido).html()=="X"){
		CObjets[ido].options2 = 1;
	}else{
		CObjets[ido].options2 = 0;
	}
}

function makecheckedimgUnik(img,objid,nm,boite){

	if(ViewerAfterBilan){return true;}

	if(nbqcmunique==1){
		
		eventCatchScript = true;
		
		for (var j = 0; j < CObjets_count; j++){
			var ctrObj = CObjets[j];
			if(ctrObj.type=='qcmunique'){
				var objIMG = $('.cocheimg' + ctrObj.id);
				objIMG.attr('src','fx/qcm/' + ctrObj.boite + '0.png');
				var tdid = ctrObj.id + 'qcmunique';
				$('#' + tdid).html("");
			}
		}
		
	}
	
	
	var obj = $('#' + objid);
	if(obj.html()!="X"){
		obj.html("X");
		img.src = 'fx/qcm/' + boite + '1.png';
	}else{
		obj.html("");
		img.src = 'fx/qcm/' + boite + '0.png';
	}

}

function makecheckedUnik(obj,nm){

	if(ViewerAfterBilan){return true;}
	
	if(nbqcmunique==1){
		
		eventCatchScript = true;
		
		for (var j = 0; j < CObjets_count; j++){
			var ctrObj = CObjets[j];
			if(ctrObj.type=='qcmunique'){
				var objIMG = $('.cocheimg' + ctrObj.id);
				objIMG.attr('src','fx/qcm/' + ctrObj.boite + '0.png');
				var tdid = ctrObj.id + 'qcmunique';
				$('#' + tdid).html("");
			}
		}
	}
	
	if(obj.innerHTML!="X"){
		obj.innerHTML = "X";
	}else{
		obj.innerHTML = "";
	}

}

//ConvertDiaToHtml
function convertQcmToTexte(data){
	data = data.replace('.,', ';');
	data = data.replace('.,', ';');
	return data;
}

var LUDIlife = 0;
var LUDIlifegameover = 0;
var LUDIlifeheight = 0;
var LUDIlifeTotal = 0;

var LUDImoney = 0;
var LUDIscore = 0;
var LUDIspeed = 15;
var LUDIwait = 0;
var LUDIactualID = "";
var LUDIfuturID = "";
var LUDIrunScript = true;

//LaunchScrit
var LaScr = true;

var LUDIrunPage = true;
var LUDIactivePopUp = 0;
var LUDIrunPageIsOk = false;
var LUDIactualPageIsOk = false;
var runGameOver = false;
var runOneLife = false;
var LUDINbPageGlobal = -1;

function teachdocOel(){
	
  	this.load = function(id) {//**
		LUDIactualID = id;
	};
	
    this.pageIsOk = function(id){//**
		
		if(LUDIrunPageIsOk==false){
			PopUpLimitControl = -1;
			LUDIactualPageIsOk = isok();
			LUDIrunPageIsOk = true;
			setTimeout('LUDIrunPageIsOk=false;',200);
			return LUDIactualPageIsOk;
		}else{
			return LUDIactualPageIsOk;
		}
		
	};
	
	this.questionAreCompleted = function(){//**
		
		isok();
		if(interactLost==0){
			return true;
		}else{
			return false;
		}
		
	};
	
	this.miniIsOk = function(){//**
		
		PopUpLimitControl = LUDIactivePopUp;
		
		if(LUDIrunPageIsOk==false){
			LUDIactualPageIsOk = isok();
			
			PopUpLimitControl = -1;
			
			LUDIrunPageIsOk = true;
			setTimeout('LUDIrunPageIsOk=false;',200);
			return LUDIactualPageIsOk;
		}else{
			return LUDIactualPageIsOk;
		}
		
	};
	
	this.rotateAngle = function(s,rot) {//**
		LUDIactualID = s;
		var t = setTimeout( "LUDIrotateAll('" + LUDIactualID +  "'," + rot + ")" ,LUDIwait);	
	};

	this.rotate = function(rot) {//**
		var t = setTimeout( "LUDIrotateAll('" + LUDIactualID +  "'," + rot + ")" ,LUDIwait);	
	};
	
	this.closeMini = function() {//**
		closePopBarre();
		LUDIactivePopUp = 0;
	};
	
	this.replaySeqSounds = function() {//**
		replaySeqSounds();
	};

	this.startTimer = function() {//**
		if(clockFct!=''&&clockTimerG>0){
			setTimeout(clockFct,clockTimerG);
			clockFct = '';
			clockTimerG = 0;
		}
	};
	
	this.translate = function(x,y) {//**
		var t = setTimeout( "LUDItranslateAll('" + LUDIactualID +  "'," + x + "," + y + ")" ,LUDIwait);	
	};

	this.translateXY = function(s,x,y) {//**
		LUDIactualID = s;
		var t = setTimeout( "LUDItranslateAll('" + LUDIactualID +  "'," + x + "," + y + ")" ,LUDIwait);	
	};
	
	this.location = function(x,y) {//**
		
		var i = 0;
		for (i; i < CObjets_count; i++){
			if(CObjets[i].idscript==LUDIactualID){
				var Vobj =  CObjets[i];
				Vobj.setX(x);
				Vobj.setY(y);
				var ex = parseInt(Vobj.getX() * zoom);
				var ey = parseInt(Vobj.getY() * zoom);
				$(".bloc" + Vobj.id).css("left",ex + 'px').css("top",ey + 'px');
				zoomBoite(Vobj);
				zoomBoiteTexte(Vobj);
			}
		}
		
	};

	this.locationXY = function(s,x,y) {//**
		LUDIactualID = s;
		var i = 0;
		for (i; i < CObjets_count; i++){
			if(CObjets[i].idscript==LUDIactualID){
				var Vobj =  CObjets[i];
				Vobj.setX(x);
				Vobj.setY(y);
				var ex = parseInt(Vobj.getX() * zoom);
				var ey = parseInt(Vobj.getY() * zoom);
				$(".bloc" + Vobj.id).css("left",ex + 'px').css("top",ey + 'px');
				zoomBoite(Vobj);
				zoomBoiteTexte(Vobj);
			}
		}
	};
	
	this.mapTo = function(s,s2) {//**
		
		var Vobj;
		var Vobj2;
		
		var i = 0;
		
		for (i; i < CObjets_count; i++){
			if(CObjets[i].idscript==s){
				var Vobj =  CObjets[i];
			}
			if(CObjets[i].idscript==s2){
				var Vobj2 =  CObjets[i];
			}
		}
		
		Vobj.setX(Vobj2.getX());
		Vobj.setY(Vobj2.getY());
		Vobj.setW(Vobj2.getW());
		Vobj.setH( Vobj2.getH());
		
		var ex = parseInt(Vobj.getX() * zoom);
		var ey = parseInt(Vobj.getY() * zoom);
		var ew = parseInt(Vobj.getW() * zoom);
		var eh = parseInt(Vobj.getH() * zoom);
		
		$(".bloc" + Vobj.id).css("left",ex + 'px').css("top",ey + 'px');
		$(".bloc" + Vobj.id).css("width",ew + 'px').css("height",eh + 'px');
		
	};
	
	this.speed = function(s) {//**
		LUDIspeed = parseInt(s);
	};
	
	this.sound = function(s){//**
		if(globalSound==1){
			var urlS = clAudio(s);
			playSoundOne(urlS,'');
		}
	};
	
	this.timeNextPage = function(t) {//**
		
		if(runGameOver){return false;}
		if(LUDIrunPage){
			LUDIrunPage = false;
			setTimeout(function(){ 
				var ip = menu_global.replace("data/page","");
				ip = ip.replace(".xml","");
				ip = ip.replace(langueextend,"");
				ip = parseInt(ip) + 1;
				var ur = "data/page" + ip + ".xml";
				var t = setTimeout( "LUDIrunScript = false;loaddata('" + ur +  "','');" ,parseInt(LUDIwait + 200));
			},(t * 1000));
		}
		
	};
	
	this.nextPage = function(s) {//**
	
		if(runGameOver){return false;}
		
		if(LUDIrunPage){
			LUDIrunPage = false;
			var ip = menu_global.replace("data/page","");
			ip = ip.replace(".xml","");
			ip = ip.replace(langueextend,"");
			ip = parseInt(ip) + 1;
			var ur = "data/page" + ip + ".xml";
			var t = setTimeout( "LUDIrunScript = false;loaddata('" + ur +  "','');" ,parseInt(LUDIwait + 200));
		}
	};
	
	this.nextPageIsOK = function() {//**

		if(runGameOver){return false;}

		var ip = menu_global.replace("data/page","");
		ip = ip.replace(".xml","");
		ip = ip.replace(langueextend,"");
		ip = parseInt(ip) + 1;
		var ur = "data/page" + ip + ".xml";

		if(this.pageIsOk()){

			if(LUDIrunPage){
				LUDIrunPage = false;
				var t = setTimeout( "LUDIrunScript = false;loaddata('" + ur +  "','isok');" ,parseInt(LUDIwait + 200));
			}

		}else{

			loaddata(ur,'isok');

		}

	};

	this.nextPageNoNote = function(s) {//**
	
		if(runGameOver){return false;}
		
		if(LUDIrunPage){
			LUDIrunPage = false;
			var ip = menu_global.replace("data/page","");
			ip = ip.replace(".xml","");
			ip = ip.replace(langueextend,"");
			ip = parseInt(ip) + 1;
			var ur = "data/page" + ip + ".xml";
			var t = setTimeout( "LUDIrunScript = false;loaddata('" + ur +  "','nonote');" ,parseInt(LUDIwait + 200));
		}
	};

	this.nextPageAnd1 = function(s) {//**
	
		if(runGameOver){return false;}

		if(LUDIrunPage){
			LUDIrunPage = false;
			var ip = menu_global.replace("data/page","");
			ip = ip.replace(".xml","");
			ip = ip.replace(langueextend,"");
			ip = parseInt(ip) + 2;
			var ur = "data/page" + ip + ".xml";
			var t = setTimeout( "LUDIrunScript = false;loaddata('" + ur +  "','');" ,parseInt(LUDIwait + 200));
		}
		
	};
	
	this.getNumPage = function(){
			var ip = menu_global.replace("data/page","");
			ip = ip.replace(".xml","");
			ip = ip.replace(langueextend,"");
			ip = parseInt(ip);
			return ip;
	}

	this.getFullNbPage = function(){

		if(LUDINbPageGlobal==-1){

			var nbP = nbpagesD;
			if(typeof dataExterneXml!=='undefined'){
				nbP = 0;
				for(var i=0;i<800;i++){
					if(pageNumExistExtXml(i)){
						nbP++;
					}
				}
			}
			LUDINbPageGlobal = nbP;
			return nbP;

		}else{

			return LUDINbPageGlobal;

		}
	
	}

	this.nextPageAnd2 = function(s) {//**

		if(runGameOver){return false;}

		if(LUDIrunPage){
			LUDIrunPage = false;
			var ip = menu_global.replace("data/page","");
			ip = ip.replace(".xml","");
			ip = ip.replace(langueextend,"");
			ip = parseInt(ip) + 3;
			var ur = "data/page" + ip + ".xml";
			var t = setTimeout( "LUDIrunScript = false;loaddata('" + ur +  "','');" ,parseInt(LUDIwait + 200));
		}
	};
	
	this.nextPageAnd3 = function(s) {//**

		if(runGameOver){return false;}

		if(LUDIrunPage){
			LUDIrunPage = false;
			var ip = menu_global.replace("data/page","");
			ip = ip.replace(".xml","");
			ip = ip.replace(langueextend,"");
			ip = parseInt(ip) + 4;
			var ur = "data/page" + ip + ".xml";
			var t = setTimeout( "LUDIrunScript = false;loaddata('" + ur +  "','');" ,parseInt(LUDIwait + 200));
		}
	};
	
	this.nextPageAnd4 = function(s) {//**

		if(runGameOver){return false;}

		if(LUDIrunPage){
			LUDIrunPage = false;
			var ip = menu_global.replace("data/page","");
			ip = ip.replace(".xml","");
			ip = ip.replace(langueextend,"");
			ip = parseInt(ip) + 5;
			var ur = "data/page" + ip + ".xml";
			var t = setTimeout( "LUDIrunScript = false;loaddata('" + ur +  "','');" ,parseInt(LUDIwait + 200));
		}
	};
	
	this.goPageDec = function(dec) {//**

		if(runGameOver){return false;}

		if(LUDIrunPage){
			LUDIrunPage = false;
			var ip = menu_global.replace("data/page","");
			ip = ip.replace(".xml","");
			ip = ip.replace(langueextend,"");
			ip = parseInt(ip) + dec;
			var ur = "data/page" + ip + ".xml";
			var t = setTimeout( "LUDIrunScript=false;loaddata('" + ur +  "','');" ,parseInt(LUDIwait + 200));
		}
	};
	
	this.GoMini = function(num){//**
		if(LUDIactivePopUp>0){
			closePopBarre();
			LUDIactivePopUp = 0;
		}
		var objBarre = getPopBarre(num);
		var larg = objBarre.w;
		var haut = objBarre.contenu2;
		openWindowsLight(num,larg,haut);
	};
	
	this.prevPage = function(s) {//**

		if(runGameOver){return false;}

		if(LUDIrunPage){
			LUDIrunPage = false;
			var ip = menu_global.replace("data/page","");
			ip = ip.replace(".xml","");
			ip = ip.replace(langueextend,"");
			ip = parseInt(ip) - 1;
			if(ip!=-1){
				var ur = "data/page" + ip + ".xml";
				var t = setTimeout( "LUDIrunScript = false;loaddata('" + ur +  "','')" ,parseInt(LUDIwait + 200));
			}
		}
		
	};
	
	this.displayLastPage = function(s) {//**
		if(runGameOver){return false;}

		var ur = "data/page" + lastPage1  + ".xml";
		loaddata(ur);
	};
	
	this.goPage = function(ip){//**
		if(runGameOver){return false;}
		
		if(LUDIrunPage){
			
			LUDIrunPage = false;
			
			if(ip.length>6){
				ip = pidoff[ip];
				if (typeof ip === 'undefined'){
					return false;
				}
			}
			
			var ur = "data/page" + ip + ".xml";
			
			var t = setTimeout( "LUDIrunScript=false;loaddata('" + ur +  "','');" ,parseInt(LUDIwait + 200));
		
		}
	};
	
	this.callPage = function(urlParam){//**
		if(urlParam!=''){
			$.ajax({
				type: "GET",
				url: urlParam,
				cache:false,async:true,
				success: function(data){},error: function(){}
			});
		}
	};
	
	this.topPage = function(urlParam){//**
		if(urlParam!=''){
			top.location.href = urlParam;
		}
	};
	
	this.wait = function(s) {//**
		LUDIwait = LUDIwait + parseInt(s);
	};
	
	this.waitReset = function() {//**
		LUDIwait = 0;
	};
	
	this.uscore = function(v){//**
		LUDIscore = parseInt(LUDIscore) + parseInt(v);
		updatescore();
	}
	
	this.updateScore = function(v){//**
		LUDIscore = parseInt(LUDIscore) + parseInt(v);
		updatescore();
	}
	
	this.updateMoney = function(v){//**
		LUDImoney = parseInt(LUDImoney) + parseInt(v);
		if(LUDImoney<0){LUDImoney=0;}
	}
	
	this.updateNote = function(v){//**
		N_F = N_F + parseInt(v);
		if(N_F<0){N_F=0;}
	}
	
	this.updateNoteExam = function(){//**
		
		var nb = parseInt(document.getElementById("DiapoNbDiapo").innerHTML);
		
		var dec = 0;
		
		for (i=0; i<nb; i++){
			var ctrStr = parseInt(allDiapoQuest[i]);
			if(ctrStr==1){
				
				if(initExam==0){
					if(memNoteID.indexOf('p' + i + ';' )==-1){
						N_T = N_T + 1;
						dec = dec + 1;
					}
				}
				
				if(initExam==1){
					if(memNoteID.indexOf('p' + i + ';' )==-1){
						if(memNoteIdExamBarre.indexOf('p' + i + ';' )!=-1){
							N_T = N_T + 1;
							dec = dec + 1;
						}
					}
				}

			}
		}
		
		if(dec>0){
			var p = 'Pénalité';
			if(langue!='fr'){p = 'Penalty';}
			var rem = '<span style="color:red;" >Penalty -' + dec + '</span>';
			remarques = remarques + rem + '<br />';
			var tempBloc = new CObjet();
			tempBloc.id = 999;
			tempBloc.type = 'qcm';
			chargeNoteObjectMem(tempBloc,999,dec,0,0,rem,'qcm')
		}
		
	}
	
	this.penalty = function(p){//**
		PenaltyExamBarre = PenaltyExamBarre + p;
	};
	
	this.deleteLife = function() {//**
		
		if(runOneLife==false){
			
			runOneLife = true;
			
			var LIFEanim = LUDIlife - 1;
			
			if(LIFEanim<0||LIFEanim==LUDIlifeTotal){
				LIFEanim = 0;
			}
			//alert(LIFEanim);
			LUDIlife = LUDIlife - 1;
			
			if(LUDIlife==0||LUDIlife<1){
				runGameOver = true;
			}
			
			var objLife = $(".gamelife" + LIFEanim);
			
			objLife.animate({
			marginTop :'100px',
			marginLeft:'-100px',
			width: parseInt(LUDIlifeheight * 3) + 'px',
			height: parseInt(LUDIlifeheight * 3) + 'px'
			},1500, function(){
				
				objLife.animate({
					marginTop :'0px',
					opacity: 0.1
				},500, function(){
					
					objLife.css("display","none");
					
					runOneLife = false;
					
					if(LUDIlife==0||LUDIlife<1){
						if(LUDIrunPage){
							
							LUDIrunPage = false;
							runGameOver = true;
							var ur = "data/page" + LUDIlifegameover + ".xml";
							var t = setTimeout( "LUDIrunScript=false;loaddata('" + ur +  "','');runGameOver=false;" ,parseInt(LUDIwait + 100));
						
						}
					}
				
				});
				
			});
		}
	};
	
	this.addLife = function(){//**
		
		if(runOneLife==false){
			
			runOneLife = true;
			
			if(LUDIlife<LUDIlifeTotal){
				
				var LIFEanim = LUDIlife;
				
				if(LIFEanim<0||LIFEanim==LUDIlifeTotal){
					LIFEanim = 0;
				}
				//alert(LIFEanim);
				
				var objLife = $(".gamelife" + LIFEanim);
				
				LUDIlife = LUDIlife + 1;
				
				objLife.css("margin-top","0px");
				objLife.css("margin-left","0px");
				objLife.css("width","10px");
				objLife.css("height","10px");
				
				objLife.css("display","");
				
				objLife.animate({
					width: parseInt(LUDIlifeheight * zoom) + 'px',
					height: parseInt(LUDIlifeheight * zoom) + 'px',
					opacity: 0.9
				},500, function(){
					
					runOneLife = false;
					
				});
				
			}
			
		}
		
	};
	
	this.getValueInput = function(s) {//**
		var txt = $("." + s).val();		
		txt = parseTxt(txt);
		if(txt==''){
			txt = $("." + s + "inner").html();
			txt = parseTxt(txt);
		}
		allaysOnTop();
		return txt;
	};
	
	this.QcmControl= function(s,li) {//**
		
		var bret = false;
		
		$("." + s + ' .cochecase tr td').each(function(index) {
			if($(this).html()=="X"){
				if(li.indexOf(index + ';')!=-1){
					bret = true;
				}else{
					bret = false;
				}
			}
		});
		
		return bret;
		
	};
	
	this.getValueQcm = function(s) {//**
		
		var txt = "";		
		
		$("." + s + ' .qcmn').each(function(index) {
			if($(this).html()=="X"){
				var recolteW = $(this).attr('name');
				recolteW = findTxtStockQ(recolteW);
				recolteW = recolteW.replace('*', '');
				txt = txt + recolteW;
			}
		});
		
		$("." + s + ' .qcmx').each(function(index) {
			if($(this).html()=="X"){
				var recolteW = $(this).attr('name');
				recolteW = findTxtStockQ(recolteW);
				recolteW = recolteW.replace('*', '');
				txt = txt + recolteW;
			}
		});
		
		return txt;
	
	};
	
	this.getValueSelect=function(s){//**
		var txt=$(".tcm" + s).find('select').val();
		txt=parseTxt(txt);
		return txt;
	};

	this.setValueSelect=function(s,txt){//**
		$(".tcm" + s).find('select').val(txt);
	};

	this.setValueTxt = function(s,txt){
		
		if(LUDIwait==0){
			$("." + s + "inner").html(txt);
			$("." + s + " tbody tr td").html(txt);
			$("a." + s).html(txt);
		}else{
			var t = setTimeout( '$(".' + s + 'inner").html("' + txt + '");' ,parseInt(LUDIwait + 200));
		}
		
		
	};
	
	this.fadeIn = function(s){
		if(LUDIwait==0){
			this.fadeInProcess(s);
		}else{	
			var t = setTimeout( "LUDI.fadeInProcess('" + s +  "');" ,parseInt(LUDIwait + 200));
		}
	};
	
	this.fadeInProcess = function(s){
		
		$("." + s).css({ opacity: 1 }).css("margin-top", "0px");
		var i = 0;
		for (i; i < CObjets_count; i++){
			var obj = CObjets[i];
			if(obj.idscript==s){
				var sid = ".bloc" + obj.id +",.alterbloc" + obj.id;
				$(sid).css({ opacity: 1 }).css("margin-top","0px").css("margin-left","0px");
			}
		}
		allaysOnTop();
	};
	
	this.fadeOut = function(s){
		
		if(LUDIwait==0){
			this.fadeOutProcess(s);
		}else{	
			var t = setTimeout( "LUDI.fadeOutProcess('" + s +  "');" ,parseInt(LUDIwait + 200));
		}
		
	};
	
	this.fadeOutProcess = function(s){
		
		$("." + s).css({ opacity: 0 }).css("margin-top", "1000px");
		
		var i = 0;
		for (i; i < CObjets_count; i++){
			var obj = CObjets[i];
			if(obj.idscript==s){
				var sid = ".bloc" + obj.id +",.alterbloc" + obj.id;
				$(sid).css({ opacity: 0 }).css("margin-top", "-1000px");
				
			}
		}
		
	};
	
	this.changeSrcVideo = function(s,url){
		
		var i = 0;
		
		for (i; i < CObjets_count; i++){
			
			var obj = CObjets[i];
			
			if(obj.idscript==s){
				
				if(obj.type=='videohtml'||obj.type=='ludiplayerhtml'){
					
					var videoObj;
					if(document.getElementById('video' + s)){
						videoObj = document.getElementById('video' + s);
					}else{
						if(document.getElementById('video' + i)){
							videoObj = document.getElementById('video' + i);
						}
					}
					if(videoObj){
						if(videoObj.pause){
							videoObj.pause();
						}
						var source = document.getElementById('srcvid' + s);
						if(source){
							source.setAttribute('src',url);
							if(videoObj.load){
								videoObj.load();
							}
						}else{
							var source = document.createElement('source');
							source.src = url;
							source.type = 'video/mp4';
							source.id = 'srcvid' + s;
							videoObj.appendChild(source);
							logBilan("create source :  " + s);
						}
					}else{
						logBilan("changeSrcVideo : video object " + s + " not find !");
					}
					
				}
			}
			
		}
	
	};
	
	this.playSrcVideo = function(s){
		
		var i = 0;
		
		for (i; i < CObjets_count; i++){
			
			var obj = CObjets[i];
			
			if(obj.idscript==s){
				
				if(obj.type=='videohtml'||obj.type=='ludiplayerhtml'){
					
					var videoObj;
					if(document.getElementById('video' + s)){
						videoObj = document.getElementById('video' + s);
					}else{
						if(document.getElementById('video' + i)){
							videoObj = document.getElementById('video' + i);
						}
					}
					if(videoObj){
						
						if(videoObj.pause){
							videoObj.pause();
						}
						if(videoObj.play){
							videoObj.play();
							sdn('.init' + i);
						}else{
							logBilan("playSrcVideo : video object " + s + " not play !");
						}
					}else{
						logBilan("playSrcVideo : video object " + s + " not find !");
					}
					
				}
				
			}
		}
		
	};
	
	this.getScore = function(s){
		recalculAllNoteByPersistence();
		var prc = 0;
		
		try{
			if(N_T!=0&&N_F!=0){
				prc = parseInt((N_F / N_T) * 100);
				if(prc<0){prc=0;}
			}
		}catch(err){
			prc=0;
		}
		return prc;
	};
	
	this.changeTeam = function(){//**
		if(EquipMapMAX>0){
			if(EquipMapMAX==1){
				if(EquipMapTarg==1){
					EquipMapTarg = 0;
				}else{
					EquipMapTarg = 1;
				}
			}
		}
	};
	
	this.ResetGame = function(){
		runGameOver = true;
		LUDIlife = LUDIlifeTotal;
		if(LUDIlife<1){
			LUDIlife = 1;
		}
	};


	this.Reset = function(){//**
		initializeDomaines();
		CObjetMems = new Array();
		CObjetMems_count = 0;
		ViewerAfterBilan = false;
		ViewerAfterBilanList = "";
		learnerName = "";
		Variable1 = "";
		Variable2 = "";
		Variable3 = "";
		Variable4 = "";
		Variable5 = "";
		Variable6 = "";
		Variable7 = "";
		Variable8 = "";
		Variable9 = "";
		Variable10 = "";
		EquipMapTarg = 0;
		LUDI.goPage(0);
	};
	
	this.reverseTeam = function(c) {//**
		ObjectifMapTarget[EquipMapTarg] = ObjectifMapTarget[EquipMapTarg] - c;
		if(ObjectifMapTarget[EquipMapTarg]<0){
			ObjectifMapTarget[EquipMapTarg] = 0;
		}
	};
	
	this.reverseTeamAndAnim = function(c) {//**
		ObjectifMapTarget[EquipMapTarg] = c;
		var i = 0;
		for (i; i < CObjets_count; i++){
			var Vobj =  CObjets[i];
			if(Vobj.type=='maptarget'){
				reculeMapTargetID(i);
				animMapTarget(Vobj.id);
			}
		}
	};
	
	this.simulateDice = function(c) {//**
		ObjectifMapTarget[EquipMapTarg] = ObjectifMapTarget[EquipMapTarg] + c;
		RunActionDice = true;
	};
	
	this.processNoteMemory = function(name,FinalScore,TotalScore,Domaine,Remark){//**
		
		if(AutoSavePersistence==0){return false;}
		
		var ip = menu_global.replace("data/page","");
		ip = ip.replace(".xml","");
		ip = ip.replace(langueextend,"");
		ip = parseInt(ip);
			
		var idGlobal =  ip + "obj" + name;
		var detect = false;
		var objG;
		
		for(var i = 0; i < CObjetMems_count; i++){
			if(CObjetMems[i].idGlobal==idGlobal){
				detect = true;
				objG = CObjetMems[i];
			}
		}
		
		var bilansource = '<div class="blockbilan" >';
		bilansource = bilansource + '<div class="questbilan" >-prv-' + name + '</div>';
		bilansource = bilansource + '<ul>';
		bilansource = bilansource + '<li>' + FinalScore + '/' + TotalScore + '</li>';
		bilansource = bilansource + '</ul>-cmt-</div>';
		
		if(FinalScore<TotalScore){
		bilansource = bilansource.replace('-prv-','<span style="color:red;" >&#9632;&nbsp;</span>');
		bilansource = bilansource.replace('-cmt-','<span style="color:red;" >' + Remark + '</span>');
		}else{
		bilansource = bilansource.replace('-prv-','<span style="color:green;" >&#9632;&nbsp;</span>');
		bilansource = bilansource.replace('-cmt-','<span style="color:green;" >' + Remark + '</span>');
		}
		
		if(detect==false){
			var tempObjectMem = new CObjetMem();
			tempObjectMem.numPage = ip;
			tempObjectMem.idGlobal = idGlobal;
			tempObjectMem.type = 'input';
			tempObjectMem.note_T = TotalScore;
			tempObjectMem.note_F = FinalScore;
			tempObjectMem.domaine = Domaine;
			tempObjectMem.remarque = Remark;
			
			tempObjectMem.bhtml = bilansource;
			
			CObjetMems_Add(tempObjectMem);
		}else{
			objG.type = 'input';
			objG.note_T = TotalScore;
			objG.note_F = FinalScore;
			objG.domaine = Domaine;
			objG.remarque = Remark;
			objG.numPage = ip;
			objG.bhtml = bilansource;
		}
		
	};
	
	this.createImg = function(url,x,y,w,h) {//**
		var t = this.createBase('img');
		t.x=x;t.y=y;t.w=w;t.h=h;
		t.src='fx/close.png';
		if(url!=''){t.src=url;}
		CObjets_Add(t);
	};
	
	this.randomId = function() {//**
		function s5() {
		return Math.floor((1 + Math.random()) * 0x10000)
		  .toString(16)
		  .substring(1);
		}
		return s5()+s5()+'-'+s5()+'-'+s5()+'-'+s5()+'-'+s5()+s5()+s5();
	};
	
	this.guid = function() {//**
		return this.randomId();
	};
	
	this.createBase = function(type) {//**
		var t = new CObjet();
		t.idscript = '';
		t.strscript = '';t.type = type;t.text = '';t.url = '';t.data = '';t.align = '';
		t.initialtext = '';t.color = 'black';t.css = '';
		t.contenu7 = '';
		t.fontsize = 18;
		t.x = 0;t.y = 0;
		t.w = 40;t.h = 40;
		t.x2 = 0;t.y2 = 0;
		t.w2 = 40;t.h2 = 40;
		t.an = 1;
		t.de = 0;
		t.cssadd = '';
		t.di = 0;
		t.dedi = 0;
		t.ind = 2;
		t.create = 0;
		t.boite = '';
		t.linkcontenu =  '';
		t.linkimage =  '';
		t.linkx =  '';
		t.linky =  '';
		t.field1 =  '';
		t.field2 =  '';
		t.field3 =  '';
		t.field4 =  '';
		t.AnimClic = 0;
		return t;
	};
	
}

var LUDI = new teachdocOel();

function LUDIrotateAll(id,rot){
	
	var i = 0;
	
	for (i; i < CObjets_count; i++){
		if(CObjets[i].idscript==id){
			
			var Vobj =  CObjets[i];
			Vobj.rotation = rot;
			
			if(rotateIE()){
				degreeToWEBMatrix(Vobj,Vobj.rotation);
				degreeToIEMatrix(Vobj,Vobj.rotation);
			}else{
				rotationObjetPlug('.' + Vobj.idscript,Vobj.rotation);
			}

		}
	}

}

function rotateIE(){

	var oldnav = false;

	if(navigator.userAgent.toUpperCase().indexOf("TRIDENT/5.0") != -1){
		oldnav = true;
	}
	if(navigator.userAgent.toUpperCase().indexOf("TRIDENT/4.0") != -1){
		oldnav = true;
	}
	if(navigator.userAgent.toUpperCase().indexOf("TRIDENT/3.0") != -1){
		oldnav = true;
	}
	if(navigator.userAgent.toUpperCase().indexOf("MSIE 6.0") != -1){
		oldnav = true;
	}
	return oldnav;
}

function updatescore(){
	
	var i = 0;
	for (i; i < CObjets_count; i++){
		var Vobj =  CObjets[i];
		if(Vobj.type=='text'){
			if(Vobj.initialtext.indexOf('{score}')!=-1){
				var nh = Vobj.initialtext.replace('{score}',LUDIscore);
				$("#innerbloc" + Vobj.id).html(nh);
			}
			if(Vobj.initialtext.indexOf('{time}')!=-1){
				var tim = MillisecondsToTime((new Date()).getTime() - ScormStartTime);
				var nh = Vobj.initialtext.replace('{time}', tim);
				$("#innerbloc" + Vobj.id).html(nh);
			}
		}
		if(Vobj.type=='textimg'){
			if(Vobj.initialtext.indexOf('{score}')!=-1){
				var nh = Vobj.initialtext.replace('{score}',LUDIscore);
				$("#innerbloc" + Vobj.id).html(nh);
			}
		}
	}

}

function LUDItranslateAll(id,x,y){
		
	var i = 0;
	
	for (i; i < CObjets_count; i++){
		if(CObjets[i].idscript==id){
			var Vobj =  CObjets[i];
			LUDItranslate(Vobj,x,y);
		}
	}

}

function LUDItranslate(Vobj,x,y){
	Vobj.objx = x;
	Vobj.objy = y;
	animLUDItranslate(Vobj);
}

function animLUDItranslate(obj){
	
	var angleLUDI = 0;
	
	try{
		angleLUDI = getAngle(obj.getX(),obj.getY(),obj.objx,obj.objy);
	}catch(err){}
	
	var dist = distancepyta(obj.objx,obj.objy,obj.getX(),obj.getY());
	var deplace = parseInt(LUDIspeed * zoom);
	
	if(dist>LUDIspeed){
		
		var evolx = parseFloat(obj.getX()) + ((deplace) * Math.cos(angleLUDI));
		var evoly = parseFloat(obj.getY()) + ((deplace) * Math.sin(angleLUDI));
		
		obj.setX(evolx);
		obj.setY(evoly);
		
		var ex = parseInt(obj.getX() * zoom);
		var ey = parseInt(obj.getY() * zoom);
		
		$(".bloc" + obj.id).css("left",ex + 'px').css("top",ey + 'px');
		
		zoomBoite(obj);
		
		if(LUDIrunScript){
			var t = setTimeout(function(){animLUDItranslate(obj);},50);
		}
		
	}
	
	if(dist<=LUDIspeed){
		obj.setX(obj.objx);
		obj.setY(obj.objy);
		var ex = parseInt(obj.getX() * zoom);
		var ey = parseInt(obj.getY() * zoom);
		$(".bloc" + obj.id).css("left",ex + 'px').css("top",ey + 'px');
		zoomBoite(obj);
	}

}

function parseTxt(str){
	if (typeof(str) == 'undefined'){str = '';}
	if (str === null){str = '';}
	return str;
}



