
var lstUlLoad = '<ul class="list-teachdoc"><li>&nbsp;</li><li>...</li><ul>';

//CodeMirror lib => For editing code
$(document).ready(function(){
	
	insertMRight();
	
	$('.gjs-pn-buttons').prepend('<span onClick="saveSourceFrame(false,false,0)" class="gjs-pn-btn fa fa-save"></span>');
	
	var bdDiv = '<div id="loadsave" ><img src="img/loadsave.gif" /></div>';
	bdDiv += '<div id="logMsgLoad" ';
	bdDiv += ' style="z-index:10;position:absolute;border:solid 1px orange;';
	bdDiv += 'left:22%;bottom:60px;right:22%;height:60px;border-radius:5px;';
	bdDiv += 'background-color:white;overflow:auto;display:none;" ></div>';
	
	bdDiv += '<div id="workingProcessSave" class="workingProcessSave" ><img src="img/cube-oe.gif" /></div>';
	
	$('body').append(bdDiv);
	
	var dataImg = [{
		"type":"image",
		"height":0,
		"width":0
	}];
	
	localStorage.setItem("gjs-assets",dataImg);
	
	setTimeout(function(){
		restyleCadre();
	},100);

	setTimeout(function(){
		traductAll();
	},200);

	if (renderFromSvg!=''){
		setTimeout(function(){
			upldImgRenderFromSvg();
		},300);
	}
	setTimeout(function(){
		processRenderSco();
	},1500);
});

var modeUIeol='a';

function upldImgRenderFromSvg(){

	var formData = {
		id : idPageHtmlTop,
		ur : encodeURI(renderFromSvg)
	};
	$.ajax({
		url : '../ajax/ajax.upltorender.php?id=' + formData.id + '&ur=' + formData.ur + '&cotk=' + $('#cotk').val(),
		type : "POST",
		data : formData,
		success: function(data,textStatus,jqXHR){
			console.log('renderFromSvg : ' + renderFromSvg + ' is ' + data);
			upldImgRenderFromHtml();
		},
		error: function (jqXHR, textStatus, errorThrown)
		{
			console.log('renderFromSvg : ' + renderFromSvg + ' is error ajax');
		}
	});

}

function upldImgRenderFromHtml(){

	var renderFromHtml = renderFromSvg.replace('.svg','.html');
	var formData = {
		id : idPageHtmlTop,
		ur : encodeURI(renderFromHtml)
	};
	$.ajax({
		url : '../ajax/ajax.upltorender.php?id=' + formData.id + '&ur=' + formData.ur + '&cotk=' + $('#cotk').val(),
		type : "POST",
		data : formData,
		success: function(data,textStatus,jqXHR){
			console.log('renderFromSvg : ' + renderFromSvg + ' is ' + data);
		},
		error: function (jqXHR, textStatus, errorThrown)
		{
			console.log('renderFromSvg : ' + renderFromSvg + ' is error ajax');
		}
	});

}

function restyleCadre(){
	
	if($("div[title='Map']").length>0){

		$("div[title='Map']").css("display","none");
		$("div[title='Price Bloc']").addClass("myCustomBlock");
		$('head').append('<style>.myCustomBlock:before{content:url("img/bloctext.png");}</style>');

		//$(".gjs-one-bg").css("background-color","#0B2161");
		$('.fa-code').css("display","none");
		$('.fa-save').css("display","none");
		
		$("div[title='Video']").css("display","none");
		$("div[title='Quote']").css("display","none");
		$("div[title='3 Columns']").css("display","none");
		$("div[title='Navbar']").css("display","none");
		$("div[title='Countdown']").css("display","none");
		$("div[title='Link']").css("display","none");
		$("div[title='Text']").css("display","none");
		$("div[title='Link Block']").css("display","none");
		
		$("div[title='2 Columns 3/7']").css("display","none");
		$("div[title='Text section']").css("display","none");
		
		$(".fa-download").css("opacity","0");
		$(".fa-download").css("visible","hidden")
		$(".fa-bars").css("display","none");
		$(".fa-trash").css("display","none");
		
		$(".fa-paint-brush").css("opacity","0");
		$(".fa-paint-brush").css("visible","hidden");

		$(".qlab").append("<div class=qlabicon >?</div>");

		$(".gjs-title").css("display","none");
	/**/

		$(".fa-save").each(function(index){

			if(index==3){
				$(this).css("display","block");
				$(this).html("&nbsp;&nbsp;|&nbsp;");
				$(this).css("color","#5858FA");
				$(this).attr("id","Teachdoc");
				$(this).removeClass("fa-save");
				$(this).css("display","none");
				$(this).before(getMenuTop());
			}
			if(index==1){
				$(this).css("display","block");
				$(this).css("color","#0B0B61");
				$(this).attr("id","btnsave");
			}
		
		});
		
		$("div[title='Interactive Map']").css("display","none").addClass("extraPluginRightPanel");
		$("div[title='Section collapse']").css("display","none").addClass("extraPluginRightPanel");
		$("div[title='Slides']").css("display","none").addClass("extraPluginRightPanel");

		$("#Teachdoc").attr("onclick", "").unbind("click");

		$("#Teachdoc").click(function(){

			$('#btnsave').css("display","none");
			$('#loadsave').css("display","block");
			
			saveSourceFrame(true,true,0);
			
		});
		
		$(".gjs-block-category").css("display","none");
		$(".gjs-block-category").each(function(index){
			if(index==0){
				$(this).css("display","block");
			}
			if(index==1){
				$(this).css("display","block");
			}
		});

		if (typeNodePg==4) {
			$(".gjs-block-categories").css("visibility","hidden");
			displayFileEdit();
		}
		
		setTimeout(function(){
			
			var dataImg = [{
				"type":"image",
				"height":0,
				"width":0
			}];		
			localStorage.setItem("gjs-assets",dataImg);
			restyleCadreImage();
			
			var first = getParamValueForOeLEditor('first');
			if(first==1){
				processRender(false);
			}

		},1000);

		reloadHtmlToGrap();
		
	}else{

		setTimeout(function(){
			restyleCadre();
		},200);
	
	}

}

function getParamValueForOeLEditor(param){
	var u = window.top.location.href;var reg=new RegExp('(\\?|&|^)'+param+'=(.*?)(&|$)');
	matches=u.match(reg);
	if(matches==null){return '';}
	var vari=matches[2] != undefined ? decodeURIComponent(matches[2]).replace(/\+/g,' ') : '';
	return vari;
}

function reloadHtmlToGrap(){

	var iframe = $('.gjs-frame');

	var iframeBody = iframe.contents().find("body");

	iframeBody.find(".editRapidIcon").unbind('hover');
	iframeBody.find(".editRapidIcon").unbind('mouseenter mouseleave');

	var allVideos = iframeBody.find("video");
	allVideos.removeClass().addClass("videoByLudi");
	allVideos.unbind('hover').unbind('mouseenter mouseleave');
	
	allVideos.each(function(index){
		var container = $(this).parent();
		var src = container.html();
		container.html(src);
	});
	
	var allAudios = iframeBody.find("audio");
	allAudios.removeClass().addClass("audioByLudi");
	allAudios.unbind('hover').unbind('mouseenter mouseleave');
	allAudios.each(function(index){
		var container = $(this).parent();
		var src = container.html();
		container.html(src);
	});

	reloadTableToGrap();
	$('#jscssedit').css("display","none");
	
}

function detectNeedReload(){

	var detect = false;

	var iframe = $('.gjs-frame');
	var iframeBody = iframe.contents().find("body");
	var allTables = iframeBody.find("table");

	allTables.each(function(index){

		var src = $(this).html();
		
		if (src.indexOf('td data-gjs-type="cell"')!=-1) {
			detect = true;
		}
		if (src.indexOf('tbody data-gjs-type="tbody"')!=-1) {
			//detect = true;
		}

	});

	return detect;
}

//Control Edition Line
setTimeout(function(){
	controlEditionLine();
},500);
function controlEditionLine() {
	if (detectNeedReload()) {
		reloadTableToGrap();
		setTimeout(function(){
			controlEditionLine();
		},500);
	} else {
		setTimeout(function(){
			controlEditionLine();
		},300);
	}
}
//Control Edition Line

function switchToolsEdit(){
	$(".ludiEditIco").css('display','none');
}

function displayToolsCarre(){

	var toolsGraps = $('.gjs-blocks-cs').parent();
	if(toolsGraps.css('display') == 'none'){
		eventFireAuto(document.querySelector('.fa-th-large'),'click');
	}

}

function eventFireAuto(el, etype){
	if (el.fireEvent) {
	  el.fireEvent('on' + etype);
	} else {
	  var evObj = document.createEvent('Events');
	  evObj.initEvent(etype, true, false);
	  el.dispatchEvent(evObj);
	}
}

function ajustHtmlToGrap(){

	var iframe = $('.gjs-frame');
	var iframeBody = iframe.contents().find("body");
	var allImgs = iframeBody.find("img");

	allImgs.each(function(index){
		
		var container = $(this);
		//container.css("width","auto");
		//var widthPx = container.width();
		//if(container.hasClass("gjs-comp-selected")){
		container.css("width","100%");
		//}

	});

}

function insertMRight(){

	$("body").css("position","relative");
	$('.gjs-editor-cont').before(getMenuR());
	
	microEvents();

	resizeMenuTools();
	$('#labelMenuLudi'+idPageHtml).parent().addClass('activeli');
	refreshMenu(-1);

}

function microEvents(){

	$("body").append("<div class='maskobjMenu' ></div>");
	$( ".maskobjMenu" ).click(function() {
		isTabActive = true;
		decTabActiv = false;
		tickScrollEvt = 0;
		$( ".maskpause" ).css("display","none");
		$( ".maskobjMenu" ).css("display","none");
		$( ".objMenuParamFloat" ).css("display","none");
		$( "#menuStyleFloat" ).css("display","none");
	});

	$("body").append("<div class='maskpause' ></div>");
	$( ".maskpause" ).click(function() {
		isTabActive = true;
		decTabActiv = false;
		tickScrollEvt = 0;
		$( ".maskpause" ).css("display","none");
	});

	$( ".maskpause" ).on("mousemove", function(e) {
		document.getElementById("tool-colors-paste").focus();
	})
	
	$( ".gjs-one-bg" ).on("mousedown", function(e) {
		isTabActive = true;
		decTabActiv = false;
		$( ".maskpause" ).css("display","none");
	})

	$( ".maskpause" ).bind('mousewheel', function(e){
		tickScrollEvt++;
		if(tickScrollEvt>2) {
			$( ".maskpause" ).css("display","none");
		}
	});

}

$(window).resize(function(){
	resizeMenuTools();
});

function resizeMenuTools(){
	
	var bodypw = $('body').outerWidth();
	var bodyph = $('body').outerHeight();
	
	$(".ludimenuteachdoc").css("height",(bodyph - 326) + "px")

	$(".gjs-editor-cont").css("right","0%").css("position","absolute");

	if(bodypw>1200||bodypw==1200){
	
		$(".gjs-editor-cont").css("width","86%");
	
	}else{

		var editorypw = $('#gjs').outerWidth();
		
		var large = bodypw - 170;
		var pourc = (large / bodypw) * 100;
		$(".gjs-editor-cont").css("width",pourc + "%");

		var large = editorypw - 170;
		var pourc = (large / editorypw) * 100;
		$(".gjs-cv-canvas").css("width",pourc + "%");
		$(".gjs-pn-commands").css("width",pourc + "%");

	}

}

function reloadTableToGrap(){

	var iframe = $('.gjs-frame');
	var iframeBody = iframe.contents().find("body");

	var allTables = iframeBody.find("table");

	allTables.each(function(index){
		
		var oriClass = getTableClassOrigine($(this));

		if($(this).hasClass("qcmbarre")){
			$(this).removeClass().addClass("qcmbarre").addClass(oriClass);
			$(this).unbind('hover').unbind('mouseenter mouseleave');
			$(this).attr('style','width:100%;');
			var src = $(this).html();
			src = cleanCodeBeforeLoad(src);
			$(this).html(src);
		}
		
		if($(this).hasClass("teachdoctext")){
			var objsClass = getObjClassOrigine($(this));
			$(this).removeClass().addClass("teachdoctext").addClass(oriClass).addClass(objsClass);
			$(this).unbind('hover').unbind('mouseenter mouseleave');
			var src = $(this).html();
			src = cleanCodeBeforeLoad(src);
			$(this).html(src);	
		}
		
		if($(this).hasClass("teachdocplugteach")){
			
			var noReload = false;

			var src = $(this).html();
			
			if (src.indexOf(">oelcontent")!=-1){	
				$(this).find(".photo").removeClass().addClass("photo");
				$(this).find(".datatext1").removeClass().addClass("datatext1");
				$(this).find(".datatext2").removeClass().addClass("datatext2");
				src = $(this).html();
				src = cleanCodeBeforeLoad(src);

                if (src.indexOf("plugteachcontain")!=-1){
					var extractText1 = $(this).find('span.datatext1').html();
					if(extractText1===undefined){extractText1 = '';}
					if(extractText1==="undefined"){extractText1 = '';}
					src = applyImgContentTiny1(src,extractText1);
				}
			}

			if (src.indexOf(">schemasvgobj")!=-1||src.indexOf(">mapsvgobj")!=-1){	
				$(this).find(".datatext1").removeClass().addClass("datatext1");
				$(this).find(".datatext2").removeClass().addClass("datatext2");
				var curSrc = $(this).find("img").attr('src');
				$(this).find("img").attr('src', curSrc.split('?')[0] + '?v=' + LUDI.guid());
				src = $(this).html();
				src = cleanCodeBeforeLoad(src);
			}

			if (src.indexOf(">txtmathjax")!=-1){
				$(this).find(".plugteachcontain").css("text-align","left");
				src = $(this).html();
				src = cleanCodeBeforeLoad(src);
			}
			
			$(this).removeClass().addClass("teachdocplugteach").addClass(oriClass);
			$(this).unbind('hover').unbind('mouseenter mouseleave');
			src = src.replace(' data-gjs-type="cell" ',' ');
			
			// Special UI
			if (src.indexOf(">lifebar<")!=-1){
				if (src.indexOf("plugteachcontain")!=-1){
					src = src.replace('plugteachcontain','plugteachuicontain');
				}
			}

			// Detect quizz plugin quizzcontentplug
			if ( src.indexOf(">blank<")!=-1
				||src.indexOf(">markwords<")!=-1
				||src.indexOf(">filltext<")!=-1
				||src.indexOf(">sorttheparagraphs<")!=-1
				||src.indexOf(">findwords<")!=-1
			){
				if ( src.indexOf("quizzcontentplug")==-1 ){
					src = src.replace('teachdocplugteach ','teachdocplugteach quizzcontentplug');
				}
				$(this).removeClass('quizzcontentplug').addClass("quizzcontentplug");
			}
			
			$(this).html(src);
			
		}

		if($(this).hasClass("teachdocbtnteach")){
			
			var srcB = $(this).html();
		
			var datatext3 = $(this).find('a').attr("datatext3");
			var datatext4 = $(this).find('a').attr("datatext4");
			var datatext5 = $(this).find('a').attr("datatext5");
			var datatext6 = $(this).find('a').attr("datatext6");
			
			if(datatext3!=''){
				datatext3 = $(this).find('span.datatext3').html();
				datatext3 = $(this).find('span.datatext4').html();
				datatext5 = $(this).find('span.datatext5').html();
			}

			$(this).find('span.datatext3').removeClass().addClass("datatext3");
			$(this).find('span.datatext4').removeClass().addClass("datatext4");
			$(this).find('span.datatext5').removeClass().addClass("datatext5");
			$(this).find('span.datatext6').removeClass().addClass("datatext6");

			$(this).removeClass().addClass("teachdocbtnteach").addClass(oriClass);
			$(this).unbind('hover').unbind('mouseenter mouseleave');
			$(this).attr('style','width:100%;');
			
			var src = $(this).html();
			src = cleanCodeBeforeLoad(src);
			$(this).html(src);

			if(datatext3!=''){
				$(this).find('a').attr("datatext3",datatext3);
				$(this).find('a').attr("datatext5",datatext5);
			}
		
		}
		
	});

	reloadVideosToGrap(iframeBody);

}

function reloadVideosToGrap(iframeBody){

	if(navigator.userAgent.toLowerCase().indexOf('firefox') > -1){
		
		var allVideos = iframeBody.find("video");
		allVideos.each(function(index){
			$(this).on("mouseup mouseover", function () {
				var containDiv = $(this).parent();
				if (containDiv.find('rapidselectorgrapvideo').length==0) {
					containDiv.css("position","relative");
					var bdDiv = '<div class="rapidselectorgrapvideo" ';
					bdDiv += ' onMouseDown="parent.displayEditButon(this);" ></div>';
					containDiv.prepend(bdDiv);
				}
			});
		});
		
	}

}

function getTableClassOrigine(objT){
	var dhC = '';
	if (objT.hasClass("dhcondiMA")) {
		dhC = "dhcondiMA";
	}
	if (objT.hasClass("dhcondiMB")) {
		dhC = "dhcondiMB";
	}
	if (objT.hasClass("dhcondiMC")) {
		dhC = "dhcondiMC";
	}
	if (objT.hasClass("dhcondiMD")) {
		dhC = "dhcondiMD";
	}
	if (objT.hasClass("dhcondiME")) {
		dhC = "dhcondiME";
	}
	if (objT.hasClass("dhcondiMF")) {
		dhC = "dhcondiMF";
	}
	return dhC;
}

function getObjClassOrigine(objT){
	var dhC = '';
	if (objT.hasClass("BoxTxtClean")) {
		dhC = "BoxTxtClean";
	}
	if (objT.hasClass("BoxTxtRound")) {
		dhC = "BoxTxtRound";
	}
	if (objT.hasClass("BoxDashBlue")) {
		dhC = "BoxDashBlue";
	}
	if (objT.hasClass("BoxPostit")) {
		dhC = "BoxPostit";
	}
	if (objT.hasClass("BoxShadowA")) {
		dhC = "BoxShadowA";
	}
	if (objT.hasClass("BoxAzur")) {
		dhC = "BoxAzur";
	}
	if (objT.hasClass("BoxCadre")) {
		dhC = "BoxCadre";
	}
	return dhC;
}

setTimeout(function(){
	controlPosition();
},600);

var globalControlTime = 350;

function controlPosition(){

	var itoolbar = $('.gjs-toolbar');
	var obJoffset = itoolbar.offset();
	
	if (typeof obJoffset === "undefined" || typeof obJoffset.left === "undefined"){

		setTimeout(function(){
			controlPosition();
		},4000);

	} else {

		var left_position = obJoffset.left;

		var igjs = $('#gjs-tools');
		//var igJoffset = parseInt(igjs.offset());
		//var canvas_left = obJoffset.left;
		var canvas_width = parseInt($('#gjs-tools').width());
	
		var frame_width = parseInt($('.gjs-frame').width());
		
		if (frame_width>800) {
		
			var canvas_right = 0;
	
			if (canvas_width>810) {
				canvas_right = parseInt((canvas_width - 800)/2);
			}
			
			//console.log("left_position : " + left_position + " > " + (canvas_width-canvas_right) );
			if (left_position>canvas_width-canvas_right) {
				//console.log("Error");
				
				window.dispatchEvent(new Event('resize'));
				
				$('.gjs-cv-canvas').css("width","85.2%");
				setTimeout(function(){
					$('.gjs-cv-canvas').css("width","85%");
				},100);
				globalControlTime = globalControlTime + 5000;
			}
			
		}

		setTimeout(function(){
			controlPosition();
		},globalControlTime);
		
	}

}

function displayLifeBar() {

	if (optionsGlobalPage.indexOf("@")!=-1) {

		var containwidth = $('.gjs-pn-views-container').width() + 10;

		var getObjD = optionsGlobalPage.split("@")[1];

		if (getObjD.indexOf("V")!=-1) {

			if (!document.getElementById("ui-life-bar")) {
				$("body").append("<div onClick='startTab=3;displayGlobalParams();' id='ui-life-bar' class='ui-life-bar' ></div>");
			} else {
				$('.ui-life-bar').css("display","block");
			}

			var nbLife = 3;
			if(getObjD.indexOf("H4")!=-1){nbLife=4;}
			if(getObjD.indexOf("H5")!=-1){nbLife=5;}
			if(getObjD.indexOf("H6")!=-1){nbLife=6;}
			if(getObjD.indexOf("H7")!=-1){nbLife=7;}
			if(getObjD.indexOf("H8")!=-1){nbLife=8;}

			var oh = '';
			for (var i=1;i<(nbLife+1);i++) {
				oh += '<div id="lifeopt'+i+'" class="onelifeopt" ></div>';
			}
			$('.ui-life-bar').html(oh);
			$('.ui-life-bar').css("right",containwidth + "px");
			
		} else {

			if (document.getElementById("ui-life-bar")) {
				$('.ui-life-bar').css("display","none");
			}

		}

	}
	setTimeout(function(){
		displayLifeBar();
	},1400);

}

setTimeout(function(){
	displayLifeBar();
},600);
var OneCloseImage = true;

function restyleCadreImage(){

	if(!document.getElementById("chamiloImages")){

		//showFileManagerStudio(0,0,0);
		var bh = "<div class='trd' id='chamiloImages' onClick='showFileManagerStudio2(13,\"imgprocessclipart\",\"pushToCollAfterSelect\");' ";
		bh += " style='width:100%;height:128px;border:solid 1px gray;";
		bh += "background-image:url(img/galery-01.png);";
		bh += "text-align:center;cursor:pointer;' >";
		bh += "</div>";

		$('.gjs-am-file-uploader').html(bh);

		setTimeout(function(){
			restyleCadreImage();
			traductAll();
		},300);

	}

}

function restyleLstImage(){

	$('.gjs-am-meta').css("display","none");
	$('.gjs-am-asset').css("width","50%");
	$('.gjs-am-preview-cont').css("width","90%");
	$('.gjs-am-file-uploader').css("width","100%");
	$('.gjs-am-assets-cont').css("width","100%");
	$('.gjs-am-assets-cont').css("height","auto");
	$('.gjs-am-assets-cont').css("padding","2px");

	$('.gjs-am-close').css("display","none");

	restyleLateral();
	setTimeout(function(){
		restyleLateral();
	},10);
	setTimeout(function(){
		restyleLateral();
	},300);
	
	$('.gjs-am-preview').click(function(){
		//$('.gjs-mdl-container').css("display","none");
	});

}

function restyleLateral(){
	
	var gjsD = $('#chamiloImages').parent().parent().parent().parent().parent();
	gjsD.css("width","20%");
	gjsD.css("max-width","280px");
	gjsD.css("margin-right","0%");
	gjsD.css("margin-top","0%");
	gjsD.css("margin-bottom","0%");
	gjsD.css("border-radius","0");
	gjsD.css("border-left","solid 3px gray");
	gjsD.css("border-right","solid 3px gray");
	gjsD.css("background-color","#585858");
	gjsD.css("color","#F2F2F2");
	gjsD.parent().css("background-color","rgba(0,0,0,0.2)");

	gjsD.find(".gjs-btn-prim").addClass('img-plus-gjs');
	
	gjsD.find(".gjs-btn-prim").parent().css("display","none");

	var gjscontent = gjsD.find('.gjs-mdl-content');
	
	var ph = gjsD.outerHeight();
	gjscontent.css("height",parseInt(ph-80)+"px");
	gjscontent.css("border-top","solid 1px #F2F2F2");
	gjscontent.css("padding","5px");

	var gjsassets = gjsD.find('.gjs-am-assets');
	gjsassets.css("height",parseInt(ph-195)+"px");
	gjsassets.css("border","solid 0px blue");

}

var RedirectToLP = "";
var saveOHtml = "";
var saveOCss = "";
var saveEventG = false;
var onRenderUpdate = false;
var globalQuitAction = false;

var alcLogs = "";

function activeEventSave(){
	
	if(saveEventG==false){
		saveEventG = true;
		console.log("oel teachdoc need Update");
		resetEditEditorWin();
	}

}

function saveSourceFrame(CreateRedirect,HaveRender,idNode){
	
	processScoExport = false;
	if (modeHistory) {
		return false;
	}
	if(saveEventG==false&&idNode!=0){
		window.location.href = "index.php?action=edit&id=" + parseInt(idNode) + '&cotk=' + $('#cotk').val();
		return false;
	}

	if(onlyOneUpdate){
	
		if(localStorage){
			
			onlyOneUpdate = false;
			
			$('#btnsave').css("display","none");
			$('#loadsave').css("display","block");
			
			resetEditEditorWin();

			if(CreateRedirect){
				$('.workingProcessSave').css("display","block");
			}
			
			var Ghtml = editor.getHtml();
			var Gcss = editor.getCss();
			
			var gjsHtml = localStorage.getItem("gjs-html-" + idPageHtml);
			var gjsCss = localStorage.getItem("gjs-css-"+ idPageHtml);
			
			if (gjsHtml == null || gjsHtml == '') {
				gjsHtml = Ghtml;
				gjsCss = Gcss;
			}

			amplify.store("page-html", gjsHtml);
			amplify.store("page-css" , gjsCss);
			
			var formData = {
				id : idPageHtml,
				bh : gjsHtml, //amplify.store("page-html"),
				bc : gjsCss ,// amplify.store("page-css")
				logsactions : alcLogs
			};
			
			var noChange = false;
			if(saveOHtml!=''&&gjsCss!=''&&gjsHtml==saveOHtml&&gjsCss==saveOCss){
				noChange = true;
				formData = {
					id : idPageHtml,
					bh : '', bc : '',
					logsactions : ''
				};
			}
			
			var extraRedi = "&r=0";
			if (CreateRedirect) {
				extraRedi = "&r=1&pt=" + idPageHtmlTop;
				if (HaveRender) {
					$('#dataFileEditWindows').css("display","none");
					installFakeLoad();
					onRenderUpdate = true;
				}
			}

			if(noChange&&CreateRedirect==false&&idNode==0){

				addOutput('No recording because no changes', 'logtc-command-info');

				setTimeout(function(){
					$('#btnsave').css("display","block");
					$('#loadsave').css("display","none");
				},200);
				onlyOneUpdate = true;
			}else{

				$.ajax({
					url : '../ajax/save/ajax.save.php?id=' + idPageHtml + extraRedi + '&cotk=' + $('#cotk').val(),
					type : "POST",data : formData,
					success : function(data,textStatus,jqXHR){
						
						addOutput('Saving is ok', 'logtc-command-info');

						if (localStorage) {
							amplify.store("renderImgPg" + idPageHtml,0);
						}
						
						alcLogs = "";

						saveSourceComponents();

						if (data.indexOf("error")==-1) {
							saveOHtml = gjsHtml;
							saveOCss = gjsCss;
							if (idNode!=0&&idNode!=idPageHtml) {
								window.location.href = "index.php?action=edit&id=" + parseInt(idNode) + '&cotk=' + $('#cotk').val();
							} else {
								if (CreateRedirect) {
									RedirectToLP = data;
									RedirectToLP = getUrlFormUrlCode(RedirectToLP);
									if (HaveRender) {
										processRender(CreateRedirect);
									}
								} else {
									if (data.indexOf("OK")!=-1) {
										$('#btnsave').css("display","block");
										$('#loadsave').css("display","none");
									} else {
										$('#btnsave').css("display","block");
										$('#loadsave').css("display","none");
									}
									onlyOneUpdate = true;
									if (loadFXObjectevent) {
										var location = window.location.href;
										location = location.replace("#page0","");
										location = location.replace("#","");
										location = location.replace("&fxload=1","");
										location += "&fxload=1";
										window.location.href = location;
									}
								}
							}
						} else {
							$('#logMsgLoad').css("display","block");
							$('#logMsgLoad').html(data);
							onlyOneUpdate = true;
						}
					},
					error: function (jqXHR, textStatus, errorThrown)
					{
						// Detect 404 error
						if (jqXHR.status == 404) {
							setTimeout(function(){
								onlyOneUpdate = true;
								saveSourceFrame(CreateRedirect,HaveRender,idNode);
							},200);
						} else {
							$('#logMsgLoad').css("display","block");
							$('#logMsgLoad').html(textStatus);
							onlyOneUpdate = true;
						}
						
					}
				});
				
			}

		}else{
			$('#logMsgLoad').css("display","block");
			$('#logMsgLoad').html("localStorage error !");
			onlyOneUpdate = true;
		}
			
	}
}

function saveSourceComponents(){

	if (modeHistory) {
		return false;
	}
	
	const Gcomps = editor.getComponents();
	const Gstyle = editor. getStyle();

	var formData = {
		id : idPageHtml,
		GpsComps : JSON.stringify(Gcomps),
		GpsStyle : JSON.stringify(Gstyle)
	};

	$.ajax({
		url : '../ajax/save/ajax.save-compo.php?id=' + idPageHtml + '&cotk=' + $('#cotk').val(),
		type: "POST",data : formData,
		success: function(data,textStatus,jqXHR){

			if(data.indexOf("error")==-1){
				
				addOutput('Save components is ok', 'logtc-command-info');

			}else{
				$('#logMsgLoad').css("display","block");
				$('#logMsgLoad').html(data);
				resetEditEditorWin();
			}

		},
		error: function (jqXHR, textStatus, errorThrown)
		{
			$('#logMsgLoad').css("display","block");
			$('#logMsgLoad').html(textStatus);
		}
	});

}

function processRender(CreateRedirect){
	
	if (modeHistory) {
		return false;
	}
	
	processScoExport = false;
	
	$('#loadsave').css("display","block");
	
	if (CreateRedirect&&RedirectToLP!=''&&RedirectToLP.indexOf('.php')!=-1) {
		
		var quitVar = '&quit=0';
		if (globalQuitAction) {
			quitVar = '&quit=1';
		}
		
		if (RedirectToLP.indexOf('http') === 0) {
			window.location.href = 'iredirteachdoc.php?i=' + idPageHtmlTop + '&redir=' + RedirectToLP + quitVar + '&cotk=' + $('#cotk').val();
		} else {
			reloadPageErr();
		}
	
	} else {

		var urRend = '../ajax/teachdoc-render.php?id=' + idPageHtmlTop + '&cotk=' + $('#cotk').val();
	
		$.ajax({
			url :urRend,type: "POST",
			success: function(data,textStatus,jqXHR){
				
				if(CreateRedirect&&RedirectToLP!=''&&RedirectToLP.indexOf('.php')!=-1){
					window.location.href = RedirectToLP;
					onlyOneUpdate = true;
				}else{
					$('#loadsave').css("display","none");
					onlyOneUpdate = true;
				}
				
			},
			error: function (jqXHR, textStatus, errorThrown)
			{
				$('#loadsave').css("display","none");
				onlyOneUpdate = true;
			}
		});

	}

}

function getUrlFormUrlCode(urlCode) {
	urlCode = urlCode.replace('&gidReq=0&gradebook=0&origin=&','&');
	urlCode = urlCode.replace('&origin=&','&');
	urlCode = urlCode.replace(/\//g, "t@d");
	urlCode = urlCode.replace(/\?/g, "t@@d");
	urlCode = urlCode.replace(/\&/g, "t@@@d");
	return urlCode;
}

function reloadPageErr() {

	$('#loadsave').css("display","block");
	window.location.href = "index.php?action=edit&id=" + parseInt(idPageHtml) + '&cotk=' + $('#cotk').val();

}

function controlStringInSource(src){

	var gjsHtml = localStorage.getItem("gjs-html-" + idPageHtml);

	if(gjsHtml.indexOf(src)!=-1){
		return true;
	} else {
		return false;
	}

}

function shouldRunProcessToday() {
	const now = new Date();
    const currentHour = now.getFullYear() + '-' + 
		(now.getMonth() + 1).toString().padStart(2, '0') + '-' + 
		now.getDate().toString().padStart(2, '0') + ' ' + 
		now.getHours().toString().padStart(2, '0') + 'h';
    const lastRun = localStorage.getItem('processRenderSco_lastRun'+ idPageHtmlTop );
    
    return lastRun !== currentHour;
}

function markProcessAsRun() {
	const now = new Date();
    const currentHour = now.getFullYear() + '-' + 
		(now.getMonth() + 1).toString().padStart(2, '0') + '-' + 
		now.getDate().toString().padStart(2, '0') + ' ' + 
		now.getHours().toString().padStart(2, '0') + 'h';
    localStorage.setItem('processRenderSco_lastRun'+ idPageHtmlTop , currentHour);
}

function forceProcessRenderSco() {
    localStorage.removeItem('processRenderSco_lastRun'+ idPageHtmlTop);
    processRenderSco();
}

function processRenderSco() {
	
    if (!shouldRunProcessToday()) {
        addOutput('processRenderSco is alrealdy launch', 'logtc-command-line');
        return;
    }
    var urRend = '../ajax/teachdoc-render-scorm.php?id=' + idPageHtmlTop + '&cotk=' + $('#cotk').val();
    
    $.ajax({
        url: urRend,
        type: "POST",
        success: function(data, textStatus, jqXHR) {
        	addOutput('processRenderSco is ok', 'logtc-command-line');
            markProcessAsRun();
        },
        error: function (jqXHR, textStatus, errorThrown) {
        }
    });
}
var haveprogressiveLevels = false;

function getMenuTop(){
	
	var h = '';
	h += '<div class="topmenuteachdoc" onmouseover="safeMenuTop();" >';
	h += '<div class="topmenublock trd" onClick="showFileMenu();" >File</div>';
	h += '<div class="topmenublock trd" onClick="showEditMenu();" >Edit</div>';
	h += '<div class="topmenublock trd" style="width:20px;" onClick="showAboutMenu();" >?</div>';
	h += '</div>';

	h += '<div class="topmenufile" >';
	h += '<div class="topsubmenublock trd" onClick="deleteAllTopMenu();saveSourceFrame(false,false,0);" >Save</div>';
	h += '<div class="topsubmenublock trd" onClick="deleteAllTopMenu();showExportMenu();" >Export ...</div>';
	h += '<div class="topsubmenublock trd" onClick="deleteAllTopMenu();showImportMenu();" >Import ...</div>';
	h += '<div class="topsubmenublock trd" onClick="deleteAllTopMenu();displaySelectLanguage();" >UI Language</div>';
	if (modeUIeol=='a') {// alpha version
		h += '<div class="topsubmenublock trd" onClick="deleteAllTopMenu();displayExportToManager(\'pdf\');" >Print</div>';
	}
	h += '<div class="topsubmenublock trd" onClick="deleteAllTopMenu();quitEditorAll();" >Quit</div>';
	h += '</div>';
	
	h += '<div class="topmenuexport" >';
	h += '<div class="topsubmenublock trd" onClick="deleteAllTopMenu();displaySubExportScorm();" >Export to SCORM</div>';
	if (modeUIeol=='a') { // alpha version
		h += '<div class="topsubmenublock trd" onClick="deleteAllTopMenu();displayExportToPdf();" >Export to PDF</div>';
	}
	h += '<div class="topsubmenublock trd" onClick="deleteAllTopMenu();displaySubExportXapi();" >Export to xApi Package</div>';
	h += '<div class="topsubmenublock trd" onClick="deleteAllTopMenu();displaySubExportProject();" >Export Project</div>';
	if (modeUIeol=='a') {// alpha version
		h += '<div class="topsubmenublock trd" onClick="deleteAllTopMenu();displaySubToTheWeb();" >Publish to the web</div>';
	}
	h += '</div>';
	
	h += '<div class="topmenuimport" >';
	h += '<div class="topsubmenublock trd" onClick="deleteAllTopMenu();displaySubImportProject();" >Import Project</div>';
	h += '<div class="topsubmenublock trd" onClick="deleteAllTopMenu();displaySubImportProject();" >Extensions</div>';
	h += '</div>';
	
	h += '<div class="topmenuedit" >';
	if (modeUIeol=='a') {// alpha version
		h += '<div class="topsubmenublock topsubmenublockEdit trd" onClick="deleteAllTopMenu();displayFileManagerSlider(0);" >File manager</div>';
	}
	h += '<div class="topsubmenublock topsubmenublockEdit trd" onClick="deleteAllTopMenu();displayGlobalHistory();" >History</div>';
	h += '<div class="topsubmenublock topsubmenublockEdit trd" onClick="deleteAllTopMenu();displayGlossaryManager();" >Glossary</div>';

	h += '<div class="topsubmenublock topsubmenublockEdit trd" onClick="deleteAllTopMenu();displaySubPageExport();" >Save your page as a template</div>';
	// h += '<div class="topsubmenublock topsubmenublockEdit trd" onClick="deleteAllTopMenu();loadTerminalStudio();" >Terminal</div>';

	if (modeUIeol=='a') {
		h += '<div class="topsubmenublock topsubmenublockEdit trd" onClick="deleteAllTopMenu();" ><a style="text-decoration:none;" target="_blank" href="https://www.batisseurs-numeriques.fr/c-studio-help.html" >Help and pro services</a></div>';
	} else {
		h += '<div class="topsubmenublock topsubmenublockEdit trd" onClick="deleteAllTopMenu();" ><a style="text-decoration:none;" target="_blank" href="https://www.batisseurs-numeriques.fr/c-studio-help.html" >Help and services</a></div>';
	}
	h += '</div>';
	
	h += '<div class="topmenuabout" >';
	h += '<p>CS engine 2018 - 2025</p>';
	h += '<p>Version : ' + versionCS;
	h += '<p><a target="_blank" href="https://www.batisseurs-numeriques.fr/c-studio-help.html" >Help and services</a></p>';
	
	h += '<a href="#" style="position:absolute;right:0px;bottom:0px;color:#E5E8E8;" onClick="displayDevAdminParams()" >...</a>';
	h += '</p>';
	
	h += '</div>';
	
	h += '<div class="topmenubackarea" onmouseover="deleteAllTopMenu();" >';
	h += '</div>';

	return h;
}

function safeMenuTop(){
	$('.gjs-comp-selected').removeClass("gjs-comp-selected");
	$('.topmenubackarea').css("display","block");
}

function showFileMenu(){
	deleteAllTopMenu();
	$('.gjs-comp-selected').removeClass("gjs-comp-selected");
	$('.topmenubackarea').css("display","block");
	$('.topmenufile').css("display","block");
	loadaFunction();
}
function showExportMenu(){
	deleteAllTopMenu();
	$('.gjs-comp-selected').removeClass("gjs-comp-selected");
	$('.topmenubackarea').css("display","block");
	$('.topmenufile').css("display","block");
	$('.topmenuexport').css("display","block");
	loadaFunction();
}
function showImportMenu(){
	deleteAllTopMenu();
	$('.gjs-comp-selected').removeClass("gjs-comp-selected");
	$('.topmenubackarea').css("display","block");
	$('.topmenufile').css("display","block");
	$('.topmenuimport').css("display","block");
	loadaFunction();
}

function showEditMenu(){
	deleteAllTopMenu();
	$('.gjs-comp-selected').removeClass("gjs-comp-selected");
	$('.topmenubackarea').css("display","block");
	$('.topmenuedit').css("display","block");
	loadaFunction();
}

function showAboutMenu(){
	deleteAllTopMenu();
	$('.gjs-comp-selected').removeClass("gjs-comp-selected");
	$('.topmenubackarea').css("display","block");
	$('.topmenuabout').css("display","block");
	loadaFunction();
}

function deleteAllTopMenu(){
	$('.topmenufile').css("display","none");
	$('.topmenuedit').css("display","none");
	$('.topmenubackarea').css("display","none");
	$('.topmenuabout').css("display","none");
	$('.topmenuexport').css("display","none");
	$('.topmenuimport').css("display","none");
	loadaFunction();
}

function getMenuR(){
	
	var h = '<div class="ludimenu" onMouseMove="if(windowEditorIsOpen==false){$(this).css(\'z-index\',\'1000\');displayToolsCarre();}" >';
	h += '<div class="luditopheader" ></div>';
	
	h += '<div class="ludimenuteachdoc" >';
	
	var loadM = amplify.store("menuHtmlInLocal" + idPageHtmlTop +'_'+refIdPageLudi);
	if(loadM!=undefined||loadM!=''||loadM!='undefined'){
		h += loadM;
	}else{
		h += lstUlLoad;
	}
	
	h += '</div>';
	h += displayParamsTeachEdit();
	h += '</div>';

	//Context Menu
	h += '<div class="ludiEditMenuContext" >';
	h += '<input id="changeTitlePage" type="text" value="" style="width:333px;margin:11px;font-size:12px;padding:5px;" />';
	
	h += '<div class="uPIcon minIcon" onClick="upContextMenuSub(0);" >'+getISVG('arrow',0)+'</div>';
	h += '<div class="dowNIcon minIcon" onClick="upContextMenuSub(1);" >'+getISVG('arrow',180)+'</div>';
	
	h += '<p style="display:inline-flex;align-items:center;min-height:28px;line-height:1;font-size:14px;position:absolute;left:12px;top:90px;padding:0px;margin:0 5px 0;" >';
	h += '<input style="margin:0 5px 0 0;" type="radio" class=checkBehaviorWind id="Behavior0" name="behaviorPage" ></input>';
	h += '<label class="trd" for="Behavior0">Free page</label>';
	h += '</p>';
	
	h += '<p style="display:inline-flex;align-items:center;min-height:28px;line-height:1;font-size:14px;position:absolute;left:12px;top:118px;padding:0px;margin:0 5px 0;" >';
	h += '<input style="margin:0 5px 0 0;" type="radio" class=checkBehaviorWind id="Behavior1" name="behaviorPage" ></input>';
	h += '<label class="trd" for="Behavior1">The page is subject to the progression</label>';
	h += '</p>';

	h += '<p style="display:inline-flex;align-items:center;min-height:28px;line-height:1;font-size:14px;position:absolute;left:12px;top:146px;padding:0px;margin:0 5px 0;" >';
	h += '<input style="margin:0 5px 0 0;" type="radio" class=checkBehaviorWind id="Behavior2" name="behaviorPage" ></input>';
	h += '<label class="trd" for="Behavior2">You must resolve this page to continue</label>';
	h += '</p>';

	h += '<p id="Behavior3Zone" style="display:inline-flex;align-items:center;min-height:28px;line-height:1;font-size:14px;position:absolute;left:12px;top:174px;padding:0px;margin:0 5px 0;" >';
	h += '<input style="margin:0 5px 0 0;" type="radio" class=checkBehaviorWind id="Behavior3" name="behaviorPage" ></input>';
	h += '<label class="trd" for="Behavior3">Not published</label>';
	h += '</p>';

	/*
	h += '<p id="Behavior4Zone" style="position:absolute;left:12px;top:180px;padding:5px;margin:5px;display:none;" >';
	h += '<input type="radio" class=checkBehaviorWind id="Behavior4" name="behaviorPage" ></input>';
	h += '<label class="trd" for="Behavior4" >Custom display</label>';
	h += '</p>';
	*/
	
	h += '<div id="cursorleveldifficulty" class="cursorleveldifficulty" >';
	h += '<div id="cursordifficultya" class="cursordifficultya" onClick="selectLayerA()" ></div>';
	h += '<div id="cursordifficultyb" class="cursordifficultyb" onClick="selectLayerB()" ></div>';
	h += '<div id="cursordifficultyc" class="cursordifficultyc" onClick="selectLayerC()" ></div>';
	h += '</div>';
	h += '<div id="docleveldifficulty" class="docleveldifficulty" >';
	h += '<div class="leveldifficultya" onClick="selectLayerA()" ></div>';
	h += '<div class="leveldifficultyb" onClick="selectLayerB()" ></div>';
	h += '<div class="leveldifficultyc" onClick="selectLayerC()" ></div>';
	h += '</div>';

	h += '<a onClick="deleteContextMenuSub();" ';
	h += ' style="position:absolute;bottom:10px;left:5px;"  >';
	h += '<img src="icon/delete-icon-24.png" /></a>';

	h += '<a onClick="closeAllEditWindows();" ';
	h += ' style="position:absolute;bottom:10px;right:120px;" ';
	h += ' class="ludiButtonCancel trd" type="button" >Cancel</a>';
	
	h += '<a onClick="saveContextMenuSub();" ';
	h += ' style="position:absolute;bottom:10px;right:10px;" ';
	h += ' class="ludiButtonSaveMenu trd" type="button" value="Save" >Save</a>';
	
	h += '</div>';
	
	return h;

}

var selectLevelDoc = 2;

function selectLayerA(){
	$('#cursordifficultya').css('opacity','1');
	$('#cursordifficultyb').css('opacity','0');
	$('#cursordifficultyc').css('opacity','0');
	selectLevelDoc = 1;
}
function selectLayerB(){
	$('#cursordifficultya').css('opacity','0');
	$('#cursordifficultyb').css('opacity','1');
	$('#cursordifficultyc').css('opacity','0');
	selectLevelDoc = 2;
}
function selectLayerC(){
	$('#cursordifficultya').css('opacity','0');
	$('#cursordifficultyb').css('opacity','0');
	$('#cursordifficultyc').css('opacity','1');
	selectLevelDoc = 3;
}

function refreshMenu(refEditNode){

	$delay = 10;

	if (refEditNode==-1) {
		var loadM = amplify.store("menuHtmlInLocal" + idPageHtmlTop+'_'+refIdPageLudi);
		if(loadM!=undefined||loadM!=''||loadM!='undefined'){
			$delay = 500;
		}
	}

	setTimeout(function(){
		$.ajax({
			url : '../ajax/list-menu.php?v=2&id=' + idPageHtml + '&cotk=' + $('#cotk').val(),
			type: "GET",
			cache: false,
			success: function(data,textStatus,jqXHR){
				if(data.indexOf("ul")!=-1){
					$('.ludimenuteachdoc').html(data);
					$('.addli').html(getISVG('addbutton',0));
					if (refEditNode!=-1) {
						$('#labelMenuLudi'+refEditNode).css('background','#F3E2A9');
					}
					saveContextMenuInLocal();
				}else{

				}
			},
			error: function (jqXHR, textStatus, errorThrown)
			{
				alert("Error !");
				alert(textStatus);
			}
		});
	},$delay);

}

var onlyOneUpdate = true;
var subTitleLoadData = new Array();
var oldTitleLoadData = '';
var scrollTeachdoc = 0;

var updateMenuEvent = false;

//Load the context menu
function loadContextMenuSub(i,posi){

	if(onlyOneUpdate){
		
		loadaFunction();
		
		haveprogressiveLevels = false;
		if (optionsGlobalPage.indexOf("@")!=-1) {
			var getObjD = optionsGlobalPage.split("@")[1];
			if (getObjD.indexOf("D")!=-1) {
				haveprogressiveLevels = true;
			}
		}

		scrollTeachdoc = $('.ludimenuteachdoc').scrollTop();

		refIdPageLudi = i;
		refPosiPageLudi = posi;
		
		$('.miniMenuLudi').css('color','black');
		$('.miniMenuLudi').css('background','transparent');
		$('#labelMenuLudi'+refIdPageLudi).css('background','#F3E2A9');

		var behavior = parseInt($('#labelMenuLudi'+i).attr("behavior"));
		var pageLevelDoc = parseInt($('#labelMenuLudi'+i).attr("leveldoc"));

		if (typeof pageLevelDoc == 'undefined') {
			pageLevelDoc = 2;
		}

		if (behavior==0) {
			$('#Behavior0').attr('checked',true);
			$('#Behavior1').attr('checked',false);
			$('#Behavior2').attr('checked',false);
			$('#Behavior3').attr('checked',false);
			$('#Behavior4').attr('checked',false);
		}
		if (behavior==1) {
			$('#Behavior0').attr('checked',false);
			$('#Behavior1').attr('checked',true);
			$('#Behavior2').attr('checked',false);
			$('#Behavior3').attr('checked',false);
			$('#Behavior4').attr('checked',false);
		}
		if (behavior==2) {
			$('#Behavior0').attr('checked',false);
			$('#Behavior1').attr('checked',false);
			$('#Behavior2').attr('checked',true);
			$('#Behavior3').attr('checked',false);
			$('#Behavior4').attr('checked',false);
		}
		if (behavior==3) {
			$('#Behavior0').attr('checked',false);
			$('#Behavior1').attr('checked',false);
			$('#Behavior2').attr('checked',false);
			$('#Behavior3').attr('checked',true);
			$('#Behavior4').attr('checked',false);
		}
		if (behavior==4) {
			$('#Behavior0').attr('checked',false);
			$('#Behavior1').attr('checked',false);
			$('#Behavior2').attr('checked',false);
			$('#Behavior3').attr('checked',false);
			$('#Behavior4').attr('checked',true);
		}

		if (pageLevelDoc==1){
			selectLayerA();
		}
		if (pageLevelDoc==2){
			selectLayerB();
		}
		if (pageLevelDoc==3){
			selectLayerC();
		}
		
		if(subTitleLoadData[i]== undefined||subTitleLoadData[i]==''){
			subTitleLoadData[i] = $('#labelMenuLudi'+i).text();
		}
		if(subTitleLoadData[i]!=''){
			$('#labelMenuLudi'+refIdPageLudi).css('color','black');
			$('#changeTitlePage').val(subTitleLoadData[i])
		}

		if (haveprogressiveLevels==true) {
			if (idPageHtmlTop==refIdPageLudi) {
				$('.ludiButtonDelete').css("display","none");
				$('.cursorleveldifficulty').css("display","none");
				$('.docleveldifficulty').css("display","none");
			} else {
				$('.ludiButtonDelete').css("display","block");
				$('.cursorleveldifficulty').css("display","block");
				$('.docleveldifficulty').css("display","block");
			}
		} else {
			$('.ludiButtonDelete').css("display","none");
			$('.cursorleveldifficulty').css("display","none");
			$('.docleveldifficulty').css("display","none");
		}

		$('.ludiEditMenuContext').css("display","block");
		$('.ludiEditMenuContext').css("top",parseInt(78 + (posi * 35)-scrollTeachdoc)+"px");
		
		windowEditorIsOpen = true;

		if(refIdPageLudi==idPageHtmlTop ){
			$('.uPIcon,.dowNIcon').css("opacity","0");
			$('#Behavior3Zone').css('display',"none");
			$('#Behavior4Zone').css('display',"none");
		}else{
			$('.uPIcon,.dowNIcon').css("opacity","1");
			$('#Behavior3Zone').css('display',"inline-flex");
			$('#Behavior4Zone').css('display',"");
		}

	}

}

function saveContextMenuSub(){

	if (onlyOneUpdate) { 

		var strLib = $('#changeTitlePage').val();
		$('#labelMenuLudi'+refIdPageLudi).html(strLib);
		subTitleLoadData[refIdPageLudi] = strLib;
		
		var behav = 0;
		if($('#Behavior1').is(':checked')){behav = 1;}
		if($('#Behavior2').is(':checked')){behav = 2;}
		if($('#Behavior3').is(':checked')){behav = 3;}
		if($('#Behavior4').is(':checked')){behav = 4;}

		var formData = {
			id : refIdPageLudi,
			title : strLib,
			behavior : behav,
			leveldoc : selectLevelDoc
		};

		$('#labelMenuLudi'+refIdPageLudi).attr("behavior",behav)
		$('#labelMenuLudi'+refIdPageLudi).attr("leveldoc",selectLevelDoc)

		onlyOneUpdate = false;
		updateMenuEvent = true;

		$('#labelMenuLudi'+refIdPageLudi).css('color','orange');

		if (selectLevelDoc==1) {
			$('.dotSubLudi'+refIdPageLudi).css('background','#52BE80');
		}
		if (selectLevelDoc==2) {
			$('.dotSubLudi'+refIdPageLudi).css('background','#3b97e3');
		}
		if (selectLevelDoc==3) {
			$('.dotSubLudi'+refIdPageLudi).css('background','#EB984E');
		}
		
		$.ajax({
			url : '../ajax/save/ajax.uptsubdoc.php?id=' + refIdPageLudi + '&pt=' + idPageHtmlTop + '&cotk=' + $('#cotk').val(),
			type: "POST",
			data : formData,
			success: function(data,textStatus,jqXHR){
				
				$('#labelMenuLudi'+refIdPageLudi).css('background','transparent');
				if(data.indexOf('KO')==-1){
					$('#labelMenuLudi' + refIdPageLudi).css('color','green');
				}else{
					$('#labelMenuLudi' + refIdPageLudi).css('color','red');
				}
				onlyOneUpdate = true;
				
			},
			error: function (jqXHR, textStatus, errorThrown)
			{
				$('#logMsgLoad').css("display","block");
				alert("Error !");
				alert(textStatus);
				onlyOneUpdate = true;
			}
		});

		closeAllEditWindows();
			
	}
	
}

function deleteContextMenuSub(){

	if(idPageHtmlTop!=refIdPageLudi&&onlyOneUpdate){

		var formData = {id:refIdPageLudi};

		onlyOneUpdate = false;
		updateMenuEvent = true;
		$('.ludiEditMenuContext').css("display","none");

		$('#labelMenuLudi'+refIdPageLudi).css('text-decoration','line-through');
		$('#labelMenuLudi'+refIdPageLudi).css('color','red');
		
		$.ajax({
			url : '../ajax/save/ajax.uptsubdoc.php?a=666&id=' + refIdPageLudi + '&pt=' + idPageHtmlTop + '&cotk=' + $('#cotk').val(),
			type: "POST",
			data : formData,
			success: function(data,textStatus,jqXHR){

				if(data.indexOf('KO')==-1){
					$('#labelMenuLudi'+refIdPageLudi).css('color','black');
				}else{
					$('#labelMenuLudi'+refIdPageLudi).css('color','orange');
					$('#labelMenuLudi'+refIdPageLudi).css('text-decoration','none');
				}

				setTimeout(function(){

					if(idPageHtml==refIdPageLudi){
						window.location.href = "index.php?action=edit&id=" + parseInt(idPageHtmlTop) + '&cotk=' + $('#cotk').val();
					}else{
						$('#labelMenuLudi'+refIdPageLudi).parent().css("display","none");
						onlyOneUpdate = true;
					}
	
				},1000);
				
			},
			error: function (jqXHR, textStatus, errorThrown)
			{
				$('#logMsgLoad').css("display","block");
				alert("Error !");
				alert(textStatus);
				onlyOneUpdate = true;
			}
		});

		closeAllEditWindows();
		
	}

}

function upContextMenuSub(u){

	if(idPageHtmlTop!=refIdPageLudi&&onlyOneUpdate&&(refPosiPageLudi>1||u==1)){

		var formData = {id:refIdPageLudi};

		onlyOneUpdate = false;
		updateMenuEvent = true;
		
		scrollTeachdoc = $('.ludimenuteachdoc').scrollTop();

		$('.minIcon').css("display","none");
		$('#labelMenuLudi'+refIdPageLudi).css('color','orange');
		
		if(u==0){

			if(refPosiPageLudi>0){
				refPosiPageLudi = refPosiPageLudi - 1;
				$('.ludiEditMenuContext').css("top",parseInt(78 + (refPosiPageLudi * 35) - scrollTeachdoc)+"px");
			}
			
			var $current = $('#labelMenuLudi'+refIdPageLudi).parent();
			var $previous = $current.prev('li');
			if($previous.length !== 0){
			  $current.insertBefore($previous);
			}
		}

		if(u==1){

			if(refPosiPageLudi>0){
				refPosiPageLudi = refPosiPageLudi + 1;
				$('.ludiEditMenuContext').css("top",parseInt(78 + (refPosiPageLudi * 35) - scrollTeachdoc)+"px");
			}

			var $current = $('#labelMenuLudi'+refIdPageLudi).parent().next();
			var $previous = $current.prev('li');
			if($previous.length !== 0){
			  $current.insertBefore($previous);
			}
		}

		$.ajax({
			url : '../ajax/save/ajax.subdocmoveup.php?a='+u+'&id=' + refIdPageLudi + '&pt=' + idPageHtmlTop + '&cotk=' + $('#cotk').val(),
			type: "POST",
			data : formData,
			success: function(data,textStatus,jqXHR){

				if(data.indexOf('KO')==-1){
					$('#labelMenuLudi'+refIdPageLudi).css('color','black');
					$('#labelMenuLudi'+refIdPageLudi).css('background','#F3E2A9');
					$('.minIcon').css("display","block");
				}else{
					$('#labelMenuLudi'+refIdPageLudi).css('color','orange');
					$('#labelMenuLudi'+refIdPageLudi).css('text-decoration','none');
				}
				refreshMenu(refIdPageLudi);
				onlyOneUpdate = true;
				
			},error: function (jqXHR, textStatus, errorThrown)
			{
				$('#logMsgLoad').css("display","block");
				$('#logMsgLoad').html("Error !");
				onlyOneUpdate = true;
			}
		});

	}

}

function saveContextMenuInLocal() {

	if (localStorage) {
		
		var menuHtmlInLocal = cleText($('.ludimenuteachdoc').html());
		
		if (menuHtmlInLocal!="") {
			
			menuHtmlInLocal = menuHtmlInLocal.replace('activeli','');

			if(updateMenuEvent){	
				menuHtmlInLocal = menuHtmlInLocal.replace(/onclick/g,'dataclick');
				menuHtmlInLocal = menuHtmlInLocal.replace(/onClick/g,'dataclick');
			}

			amplify.store("menuHtmlInLocal" + idPageHtmlTop, menuHtmlInLocal);
			amplify.store("menuHtmlInLocal" + idPageHtmlTop+'_'+refIdPageLudi, menuHtmlInLocal);
		}
	
	}

}

function displaySubImportProject() {

	

	window.location.href = "import-project/import.php?id=" + parseInt(idPageHtmlTop) + '&cotk=' + $('#cotk').val();
	
}

var oldUrlVideo = "";
var tmpNameDom = "editnode";
var tmpNameObj = "";
var tmpNameObj2 = "";
var tmpObjDom;
var windowEditorIsOpen = false;
var textEditorIsOpen = false;
var lastPosiLudiIco = 450;

function actionEditButon(){

	if(typeof tmpObjDom === 'undefined'&&GlobalIDGrappesObj!=''){	
		tmpObjDom = getAbstractObjDom(GlobalIDGrappesObj);
	}
	
	if(tmpObjDom&&GlobalIDGrappesObj!=''){

		var actionFind = false;
		var domObj = $(tmpObjDom);

		if(domObj.hasClass("rapidselectorgrapvideo")){
			//displayVideoEdit(tmpObjDom);
			//actionFind = true;
		}
		if(domObj.is("video")){
			displayVideoEdit(tmpObjDom);
			actionFind = true;
		}
		if(domObj.is("audio")){
			displayAudioEdit(tmpObjDom);
			actionFind = true;
		}
		if(domObj.is("table")){
			if(domObj.hasClass("qcmbarre")){
				displayQcmEdit(tmpObjDom);
				actionFind = true;
			}
			if(domObj.hasClass("teachdoctext")){
				displayTeachDocTextEdit(tmpObjDom);
				actionFind = true;
			}
			if(domObj.hasClass("teachdocbtnteach")){
				displayBtnEdit(tmpObjDom);
				actionFind = true;
			}
			if(domObj.hasClass("teachdocplugteach")){
				displayPlugTeachEdit(tmpObjDom);
				actionFind = true;
			}
		}

		if (actionFind==false) {

			// list of class
			var clsnams = domObj.attr('class');
			var tagname = domObj.prop("tagName");
			var innerObj = domObj.innerHTML;
			if (typeof innerObj =='undefined'||innerObj==''||innerObj=='undefined') {
				if (typeof domObj[0] !='undefined') {
					innerObj = domObj[0].innerHTML;
					tmpObjDom = domObj[0];
					if (innerObj.slice(0, 6)=='<table') {
						if (innerObj.indexOf('teachdoctext')!=-1){
							displayTeachDocTextEdit(tmpObjDom);
						}
					}
				}
			}
	
		}

		if(actionFind==false&&GlobalIDGrappesObj!=''){
			tmpObjDom = getAbstractObjDom(GlobalIDGrappesObj);
		}

	}

}

function placeEditButonDyna(){
	
	if(tmpObjDom){

		var domObj = $(tmpObjDom);
		
		if(domObj.is("video")||domObj.is("audio")){
			displayEditButonY(tmpObjDom);
			tmpNameObj = "video";
			tmpNameObj2 = "";
		}
		if(domObj.hasClass("rapidselectorgrapvideo")){
			displayEditButonY(tmpObjDom);
			tmpNameObj = "video";
			tmpNameObj2 = "";
		}
		if(domObj.is("img")||domObj.is("image")){
			displayEditButonY(tmpObjDom);
			tmpNameObj = "image";
			tmpNameObj2 = "";
		}
		if(domObj.is("h1")){
			displayEditButonY(tmpObjDom);
			tmpNameObj = "titleh1";
			tmpNameObj2 = "";
		}
		if(domObj.is("table")){
			if(domObj.hasClass("qcmbarre")){
				displayEditButonY(tmpObjDom);
				tmpNameObj = "qcmbarre";
				tmpNameObj2 = "";
			}
			if(domObj.hasClass("teachdoctext")){
				displayEditButonY(tmpObjDom);
				tmpNameObj = "teachdoctext";
				tmpNameObj2 = "tabletxt";
			}
			if(domObj.hasClass("teachdocplugteach")){
				if (domObj.html().indexOf(">animfx<")!=-1) {
					tmpNameObj = "animfx";
				} else {
					tmpNameObj = "plugteach";
				}
				if (tmpNameObj!="animfx") {
					displayEditButonY(tmpObjDom);
				}
				tmpNameObj2 = "";
			}
			if(domObj.hasClass("teachdocbtnteach")){
				displayEditButonY(tmpObjDom);
				tmpNameObj = "button";
				tmpNameObj2 = "";
			}
		}

	}
	setTimeout(function(){placeEditButonDyna();},100);
}
setTimeout(function(){ placeEditButonDyna(); },1000);

function displayFromGrappes(iDGrappesObj){

	var domObj = getAbstractObjDom(iDGrappesObj);
	if(domObj!=-1){
		tmpObjDom = domObj;
	}
	
}

function displayEditButon(myObj){

	if(typeof myObj === 'undefined'){	
		return false;
	}

	var domObj = $(myObj);
	tmpObjDom = domObj;
}

function displayEditButonY(myObj){

	if(typeof myObj === 'undefined'){	
		return false;
	}

	var domObj = $(myObj);
	
	var maxTop = $(".ludimenu").height();
	var posW = $(".ludimenu").width();

	var position = searchGlobalPosi(domObj);
	var posX = posW + parseInt(position[0]) + ((domObj.width()) - 48);
	var posy = 45 + parseInt(position[1]);

	if(posy>maxTop-50){
		posy = maxTop-50;
	}

	$(".ludiEditIco").css('left',posX + 'px');
	$(".ludiEditIco").css('top', posy + 'px');
	
	if (posX<300) {
		$(".ludiEditIco").css('display','none');
		lastPosiLudiIco = posX;
	}
	
	if (haveSpeedTools(tmpNameObj,tmpNameObj2)) {

		var maxTopImg = $(".gjs-blocks-c").height() - 370;
		if (posy>maxTopImg) {
			posy=maxTopImg;
		}
		if (posy<45) {
			posy=45;
		}
		$(".ludiSpeedTools").css('top', parseInt(posy -8) + 'px');
		var posR = $(".gjs-blocks-c").width() + 20;
		var posR2 = $(".gjs-pn-views").width() + 20;
		if (posR<posR2) {
			posR = posR2;
		}

		var gjsW = $("#gjs").width() - posR;
		if (gjsW > 890) {
			$(".ludiSpeedTools").css('right', parseInt(((gjsW-800)/2) + (posR -44)) + 'px');
		} else {
			$(".ludiSpeedTools").css('right', posR + 'px');
		}
		
		installSpeedTools();
	}


}

function searchGlobalPosi(domObj){

	if(typeof domObj === 'undefined'){	
		return false;
	}

	var obJoffset = domObj.offset();

	var topScrollTop = getTopScroll(domObj);

	var left_position = obJoffset.left;
	var top_position = obJoffset.top - topScrollTop;
	var posiDomObj = [left_position,top_position];

	return posiDomObj;

}

function getTopScroll(domObj){

	if(typeof domObj === 'undefined'){	
		return false;
	}

	var topScrollTop = $(document).scrollTop();

	var parentObj = domObj.parent();
	
	//#wrapper
	if(parentObj.is("body")){
		topScrollTop = $(parentObj).scrollTop();
	}else{
		var parentObj2 = parentObj.parent();
		if(parentObj2.is("body")){
			topScrollTop = $(parentObj2).scrollTop();
		}else{
			var parentObj3 = parentObj2.parent();
			if(parentObj3.is("body")){
				topScrollTop = $(parentObj3).scrollTop();
			}else{
				var parentObj4 = parentObj3.parent();
				if(parentObj4.is("body")){
					topScrollTop = $(parentObj4).scrollTop();
				}else{
					var parentObj5 = parentObj4.parent();
					if(parentObj5.is("body")){
						topScrollTop = $(parentObj5).scrollTop();
					}else{
						var parentObj6 = parentObj5.parent();
						if(parentObj6.is("body")){
							topScrollTop = $(parentObj6).scrollTop();
						}else{
							var parentObj7 = parentObj6.parent();
							if(parentObj7.is("body")){
								topScrollTop = $(parentObj7).scrollTop();
							}else{
								var parentObj8 = parentObj7.parent();
								if(parentObj8.is("body")){
									topScrollTop = $(parentObj8).scrollTop();
								}else{
									var parentObj9 = parentObj8.parent();
									if(parentObj9.is("body")){
										topScrollTop = $(parentObj9).scrollTop();
									}else{
										
									}
								}
							}
						}
					}
				}
			}
		}
	}

	return topScrollTop;

}

function closeAllEditWindows() {
	
	$('.miniMenuLudi').css('background','transparent');
	$('#BtnEditWindows').css("display","none");
	$('#VideoEditLinks').css("display","none");
	$('#frameCustomCode').css("display","none");
	
	$('#AudioEditLinks').css("display","none");
	$('#QcmEditLinks').css("display","none");
	$('.WinEditColorsTeach').css("display","none");
	
	$('.BtnEditPlugTeach').css("display","none");

	$('#pageEditProgressClean').css("display","none");
	$('#TeachDocTextEditWindows').css("display","none");
	
	$('.ludiEditMenuContext').css("display","none");
	$("#pageEditExportScorm").css("display","none");
	$("#BtnFXTeachList").css("display","none");
	
	$("#pageEditGlobalParams").css("display","none");
	$('#panel-view-history').css("display","none");
	$('#pageEditAdd').css("display","none");

	$('#pageEditHistory').css("display","none");
	$('#pageEditTemplates').css("display","none");
	$('#pageDevAdminParams').css("display","none");
	
	$('#TeachDocPasteEditWindows').css("display","none");
	$("#ImageActiveEdit").css("display","none");
	$("#FileManagerStudio").css("display","none");
	$("#pageEditExportProject").css("display","none");
	$("#pageEditExportXapi").css("display","none");
	$("#pageEditToTheWeb").css("display","none");
	$("#ExportToManager").css("display","none");
	$("#ExportToPdf").css("display","none");
	$("#pageEditExportTestXapi").css("display","none");
	$('.ludimenu').css("display","");
	$('.ludimenu').css("z-index","1000");
	$('.tox-toolbar__group').css("display","none");
	
	$('#ImageSchemaEdit').css("display","none");
	$('#ImageMapEdit').css("display","none");
	
	$("#pageEditThemeParams").css("display","none");
	$('#SelectLanguageWindows').css("display","none");
	
	$('#glossaryManager').css("display","none");
	$('#pageDevUpdate').css("display","none");
	
	$('.objMenuParamFloat').css("display","none");
	$('#menuStyleFloat').css("display","none");
	$('.styleMenuParamFloat').css("display","none");
	
	$('.maskobjMenu').css("display","none");
	$('.maskpause').css("display","none");
	
	$('#pageEditAddExport').css("display","none");
	$('#pageRenderToImg').css("display","none");
	
	$("#ImageSlideEdit").css("display","none");
	
	$('#txtEditWinplace').removeClass("winPlaceLeft");
	$('#txtEditWinplace').removeClass("winPlaceRight");
	$('#TeachDocTextEditWindows').removeClass("winPlaceClear");
	// remove all dom with .autodestroy-windows
	$('.autodestroy-windows').each(function(){
		$(this).remove();
	});
	deleteAllTopMenu();
	isTabActive = true;
	displayToolsCarre();
	onePasteOnly = true;
	windowEditorIsOpen = false;
	textEditorIsOpen = false;
	
}

var onlyOneRedirect = false;

function loadSubLudi(i){
	
	if(onlyOneUpdate){ 
	
		if (onlyOneRedirect==false) {

			loadaFunction();
			saveSourceFrame(false,false,i);
			
			$('.list-teachdoc li').removeClass('activeli');
			$('#labelMenuLudi'+i).parent().addClass('activeli');
			
			refIdPageLudi = i;
			onlyOneRedirect = true;
			$('#dataFileEditWindows').css("display","none");
			$('.gjs-frame').css("visibility","hidden");
			$('.gjs-pn-devices-c').css("display","none");
			$('.gjs-cv-canvas').css("position","relative");
			$('.gjs-cv-canvas').css("background-color","white");
			
			installFakeLoad();

			$(".loadbarre").animate({
				width: '480px'
			},1500, function(){
				$(".loadbarre").animate({
					width: '240px'
				},1000, function(){
					$(".loadbarre").animate({
						width: '480px'
					},1000, function(){
					});
				});
			});
			
		}
		
	}

}

function installFakeLoad(){
	
	$('.ludiEditIco').css('display','none');
	$('.ludiSpeedTools').css('display','none');

	var fakeLoad = "<div class='fakeBodyFrame' style='" + getcontextStyleBack() + "' ><div class=fakeLoadFrame >";
	fakeLoad += "<br/>";
	fakeLoad += "<img class='loadbarre' style='width:240px;height:20px;margin:5px;' src='img/rectangle-loader.gif' />";
	fakeLoad += "<br/><br/>";
	fakeLoad += "<img style='width:150px;height:20px;margin:5px;' src='img/rectangle-loader.gif' />";
	fakeLoad += "<br/><br/>";
	fakeLoad += "<img style='width:250px;height:20px;margin:5px;' src='img/rectangle-loader.gif' />";
	fakeLoad += "<br/><br/>";
	fakeLoad += "<img style='width:150px;height:20px;margin:5px;' src='img/rectangle-loader.gif' />";
	fakeLoad += "<br/><br/>";
	fakeLoad += "<img style='width:50px;height:20px;margin:5px;' src='img/rectangle-loader.gif' />";
	fakeLoad += "<br/><br/>";
	fakeLoad += "<img style='width:150px;height:20px;margin:5px;' src='img/rectangle-loader.gif' />";
	fakeLoad += "</div>";
	fakeLoad += "</div>";

	$('.gjs-cv-canvas').html(fakeLoad);

}

function getcontextStyleBack(){

	var sty = "";

	if (colorsPath=="white-chami.css"||colorsPath=="") {
		sty = "background-color : #d1d1e0!important;"
	}
	if (colorsPath=="orange-chami.css") {
		sty = "background-color :#d1d1e0!important;"
	}
	if (colorsPath=="eco-chami.css") {
		sty = "background-image : url(img/classique/leafs.png);"
		sty += "background-position: left bottom;";
		sty += "background-repeat: no-repeat;";
		sty += "background-color :#E8F6F3!important;";
		sty += "background-attachment: fixed;"
	}
	if (colorsPath=="paper-chami.css") {
		sty = "background-color :#d1d1e0!important;"
	}
	if (colorsPath=="office-chami.css") {
		sty = "background-image : url(img/classique/office-a.png);";
		sty += "background-position: left bottom;";
		sty += "background-repeat: no-repeat;";
		sty += "background-color :#EBEDEF!important;";
		sty += "background-attachment: fixed;";
	}
	if (colorsPath=="white-sky.css") {
		sty = "background-image : url(img/classique/sky-azure.jpg);";
		sty += "background-position: left bottom;";
		sty += "background-repeat: repeat-x;";
		sty += "background-color :#E8F6F3!important;";
		sty += "background-attachment: fixed;";
	}
	if (colorsPath=="white-road.css") {
		sty = "background-image : url(img/classique/road.jpg);";
		sty += "background-position: left bottom;";
		sty += "background-repeat: repeat-x;";
		sty += "background-color :#E8F6F3!important;";
		sty += "background-attachment: fixed;";
	}

	return sty;

}

var typeWindEditLink = 0;

function showFileManagerStudio(t,src,callbackfct){
	
	if(src==0){src='';}
	
	typeWindEditLink = t;
	
	$('<div \>').dialog({modal:true,width:"80%",title:"Select your file",zIndex: 99999,
		create:function(event,ui){
			$(this).elfinder({
				resizable: false,
				url: "../../../main/inc/lib/elfinder/connectorAction.php",
				commandsOptions: {
					getfile: {
					oncomplete: 'destroy' 
					}
				},                       
				getFileCallback: function(file) {

					//alert(file.url);
					$('.ui-dialog').css("display","none");
					
					$('.workingProcessSave').css("display","block");

					if(typeWindEditLink==0){

						var formData = {
							id : idPageHtmlTop,
							ur : encodeURI(file.url)
						};
						$.ajax({
							url : '../ajax/ajax.upldimg.php?id=' + formData.id + '&ur=' + formData.ur + '&cotk=' + $('#cotk').val(),
							type: "POST",data : formData,
							success: function(data,textStatus,jqXHR){

								if(data.indexOf("error")==-1&&data.indexOf("img_cache")!=-1){
									pushImageToColl(data);
								}else{
									pushImageToColl(file.url);
								}

							},
							error: function (jqXHR, textStatus, errorThrown)
							{
								pushImageToColl(file.url);
							}
						});
						
					}
					if(typeWindEditLink==1){
						$('#inputVideoLink').val(file.url);
						deleteLoadSave();
					}
					if(typeWindEditLink==2){
						$('#inputAudioLink').val(file.url);
						deleteLoadSave();
					}
					if(typeWindEditLink==11){
						$('#datatext1').val(file.url);
						deleteLoadSave();
					}
					if(typeWindEditLink==12){
						$('#datatext2'+src).val(file.url);
						deleteLoadSave();
					}

					if(typeWindEditLink==13){
						
						var formData = {
							id : idPageHtmlTop,
							ur : encodeURI(file.url)
						};
						$.ajax({
							url : '../ajax/ajax.upldimg.php?id=' + formData.id + '&ur=' + formData.ur + '&cotk=' + $('#cotk').val(),
							type: "POST",data : formData,
							success: function(data,textStatus,jqXHR){

								if(data.indexOf("error")==-1&&data.indexOf("img_cache")!=-1){
									$('#'+src).val(data);
								}else{
									$('#'+src).val(file.url);
								}
								window[callbackfct]();
							},
							error: function (jqXHR, textStatus, errorThrown)
							{
								$('#'+src).val(file.url);
								window[callbackfct]();
							}
						});
						
					}

					if(typeWindEditLink==14){

						var formData = {
							id : idPageHtmlTop,
							ur : encodeURI(file.url)
						};
						$.ajax({
							url : '../ajax/ajax.upldimg.php?id=' + formData.id + '&ur=' + formData.ur + '&cotk=' + $('#cotk').val(),
							type: "POST",data : formData,
							success: function(data,textStatus,jqXHR){
								if(data.indexOf("error")==-1&&data.indexOf("img_cache")!=-1){
									$('#'+src).val(data);
								}else{
									alert(data);
									$('#'+src).val("");
								}
								window[callbackfct]();
							},
							error: function (jqXHR, textStatus, errorThrown)
							{
								$('#'+src).val(file.url);
								window[callbackfct]();
							}
						});
						
					}


				}
			}).elfinder('instance')
		}
	});

}

function isImageStudio(imgA){
	
	var r = false;

	if (imgA==''||
    (imgA.toLowerCase().indexOf('.png')==-1
    &&imgA.toLowerCase().indexOf('.jpg')==-1
    &&imgA.toLowerCase().indexOf('.jpeg')==-1
    &&imgA.toLowerCase().indexOf('.gif')==-1
	&&imgA.toLowerCase().indexOf('.svg')==-1
    &&imgA.toLowerCase().indexOf('cache')==-1)
    ){
		r = false;
	} else {
		r = true;
	}
	
	return r;

}

function isImageFile(imgA){
	
	var r = false;

	if (imgA==''||
    (imgA.toLowerCase().indexOf('.png')==-1
    &&imgA.toLowerCase().indexOf('.jpg')==-1
    &&imgA.toLowerCase().indexOf('.jpeg')==-1
	&&imgA.toLowerCase().indexOf('.svg')==-1
    &&imgA.toLowerCase().indexOf('.gif')==-1)
    ){
		r = false;
	} else {
		r = true;
	}
	
	return r;

}

function pushImageToColl(fileurl){

	if(fileurl.indexOf('img_cache/') === 0){
		fileurl = _p.web_plugin + 'CStudio/img-cache.php?path=' + encodeURIComponent(fileurl.substring('img_cache/'.length));
	}

	$('.ui-widget-overlay').css("display","none");
	$('.workingProcessSave').css("display","none");
	$('.gjs-am-add-asset').find("input").val(fileurl);
	$('.gjs-am-add-asset button').css("background","cornflowerblue");
	$('.gjs-am-add-asset button').css("color","white");
	$('.img-plus-gjs').click();

}

function deleteLoadSave(){
	$('.ui-widget-overlay').css("display","none");
	$('.workingProcessSave').css("display","none");
}

var refIdPageLudi = 0;
var refPosiPageLudi = 0;
var refLedtAddPage = 0;
var tplSelectPg = -1;
var reftypeNodeV = 0;

function selectTplPage(i){
	tplSelectPg = i;
	$('.tpl-page-select').css("border","solid 3px #BDBDBD");
	$('.tplpage'+tplSelectPg).css("border","solid 3px gray");
}

function saveNextSubLudi(){
	
	var inputTitlePageStr = $('#inputTitlePage').val();	
	
	if(inputTitlePageStr!=""){

		if(inputTitlePageStr=="test_"){
			$('.oelTitlePage').css("display","none");
			$('.oelChosePage').css("display","");
			if (nbContainLstA>0) {
				$('.oelChoseTemplatesPage').css("display","");
			}
			$('.oelInputAdd1').css("display","none");
			$('.oelInputAdd2').css("display","");
			return false;
		}
		
		var typeNodeV = 2;
		if($('#typenode3').is(':checked')){
			typeNodeV = 3;
		}
		if($('#typenode4').is(':checked')){
			typeNodeV = 4;
		}
		reftypeNodeV = typeNodeV;
		
		// $('#inputAddSubPage').css("display","none");
		$('#loadsave').css("display","block");

		updateMenuEvent = true;
		var formData = {
			id : refIdPageLudi,
			title : inputTitlePageStr,
			typenode : typeNodeV
		};

		if (typeNodeV==2) {
		
			$('.oelTitlePage').css("display","none");
			$('.oelChosePage').css("display","");
			$('.pageDefautTplRight').css("display","");
			if (nbContainLstA>0) {
				$('.oelChoseTemplatesPage').css("display","");
				$('.pageTemplateRight').css("display","");
			}
			$('.oelInputAdd1').css("display","none");
			$('.oelInputAdd2').css("display","");

		}
		if (typeNodeV==3||typeNodeV==4) {
			
			$('.oelTitlePage').css("display","none");
			$('.oelChoseTemplatesPage').css("display","none");
			$('#oelTitleload').css("display","");
			
		}

		$.ajax({
			url : '../ajax/save/ajax.addsubdoc.php?id=' + refIdPageLudi + '&pt=' + idPageHtmlTop + '&cotk=' + $('#cotk').val(),
			type : "POST",
			data : formData,
			success : function(data,textStatus,jqXHR) {
				if (data.indexOf("KO")==-1&&data.indexOf("error")==-1) {
					var idNp = parseInt(data);
					if (isNaN(idNp)) {
					} else {
						$('#loadsave').css("display","none");
						refLedtAddPage = idNp;
						//title section
						if (reftypeNodeV==3) {
							refreshMenu(-1);
							$('#oelTitleload').css("display","none");
							$('.oelInputAdd2').css("display","none");
							$('.tpl-page-select').css("display","none");
							$('.tpl-page-title').css("display","none");
							$('.tpl-page-loader').css("display","block");
							closeAllEditWindows();
						}
						if (reftypeNodeV==4) {
							tplSelectPg = 1;
							saveNextSubLudiFinal();
						}
					}
				}
			},
			error: function (jqXHR, textStatus, errorThrown)
			{
				$('#logMsgLoad').css("display","block");
				$('#logMsgLoad').html("Error !");
			}
		});

	}

}

function saveNextSubLudiFinal(){
	
	if (refLedtAddPage>0&&tplSelectPg>-1) {
		
		$('.oelInputAdd2').css("display","none");
		$('.tpl-page-select').css("display","none");
		$('.tpl-page-title').css("display","none");
		$('.tpl-page-loader').css("display","block");

		$('.contain-pagetpl-select').css("display","none");
		$('.pageDefautTplRight').css("display","none");
		$('.pageTemplateRight').css("display","none");

		if (tplSelectPg<100) {
			window.location.href = "index.php?action=edit&id=" + parseInt(refLedtAddPage)+ "&first=1&pty=p" + tplSelectPg + "&ty=0" + '&cotk=' + $('#cotk').val();
		} else {
			var tplref = $('.tplpage'+tplSelectPg).attr("tplref");
			window.location.href = "index.php?action=edit&id=" + parseInt(refLedtAddPage)+ "&first=1&pty=" + tplref + "&ty=0" + '&cotk=' + $('#cotk').val();
		}
		
		tplSelectPg = -1;
	}

}

function displaySubProgressClean(){
	
	$('.ludimenu').css("z-index","2");

	cleanSourceLocation = 0;
	saveSourceLocations();

	if($("#pageEditProgressClean").length==0){
		
		var bdDiv = '<div id="pageEditProgressClean" class="gjs-mdl-container" >';
		bdDiv += '<div class="gjs-mdl-dialog-v2 gjs-one-bg gjs-two-color" style="max-width:575px;" >';
		bdDiv += '<div class="gjs-mdl-header">';
		bdDiv += '<div class="gjs-mdl-title">Edition</div>';
		bdDiv += '<div class="gjs-mdl-btn-close" onClick="closeAllEditWindows()" data-close-modal="">⨯</div>';
		bdDiv += '</div>';
		
		bdDiv += '<div class="gjs-am-add-asset" ';
		bdDiv += 'style="padding:25px;font-size:16px;" >';
		bdDiv += '<br/>';
		bdDiv += '<img class="brossIcons" src="img/bross.png" />';
		bdDiv += '<br/>';
		bdDiv += '<br/>';
		bdDiv += '</div>';
		bdDiv += '</div>';

		bdDiv += '<div class="gjs-mdl-collector" style="display: none"></div>';
		bdDiv += '</div>';
		$('body').append(bdDiv);

	}

	if($("#pageEditProgressClean").length==1){

		loadaFunction();
		
		$(".brossIcons").css("margin-left","300px");
		
		$(".brossIcons").animate({
			marginLeft: "60px"
		},1000, function(){
			
			cleanLessonsLocations();

			$(".brossIcons").animate({
				marginLeft: "300px"
			},2000, function(){
				if(cleanSourceLocation==1){
					closeAllEditWindows();
				}else{
					$(".brossIcons").animate({
						marginLeft: "60px"
					},5000, function(){
						if(cleanSourceLocation==1){
							closeAllEditWindows();
						}
					});
				}
				
			});
		});

		$('.ludimenu').css("display","none");
		$('#pageEditProgressClean').css("display","");
		traductAll();
		
	}

}

var cleanSourceLocation = 0;

function cleanLessonsLocations() {
	var key;
	for (var i = 0; i < localStorage.length; i++) {
		key = localStorage.key(i);
		if (key.indexOf('essonlocationstudio-' + idPageHtmlTop+ '-')!=-1) {
			localStorage.removeItem(key);
		}
		if (key.indexOf('renderImgPg')!=-1) {
			localStorage.removeItem(key);
		}
		if (key.indexOf('-' + idPageHtml)==-1) {
			if (key.indexOf('-' + idPageHtmlTop)==-1) {
				if(key.indexOf('gjs-html-')!=-1){
					localStorage.removeItem(key);
				}
				if(key.indexOf('gjs-css-')!=-1){
					localStorage.removeItem(key);
				}
			}
		}
	}
}

function saveSourceLocations() {

	if (localStorage) {
		window.localStorage.setItem('data'+idPageHtmlTop,"@@@@@@");
	}

	$.ajax({
		url : '/plugin/CStudio/ajax/sco/scorm-save-location.php?idteach=' + idPageHtmlTop + '&cotk=' + $('#cotk').val(),
		type: "POST",
		success: function(data,textStatus,jqXHR){
			cleanSourceLocation = 1;
		},
		error: function (jqXHR, textStatus, errorThrown)
		{
			cleanSourceLocation = 0;
		}
	});

}

function cleanScoFolder() {

	$.ajax({
		url :'../ajax/export/prepare-sco.php?id=' + idPageHtmlTop+'&step=2000&p=0' + '&cotk=' + $('#cotk').val(),
		type: "POST",
		success: function(data,textStatus,jqXHR){
			
		},
		error: function (jqXHR, textStatus, errorThrown)
		{
			
		}
	});

}

var oldUrlAudio = "";

function displayAudioEdit(myObj){

	var audioObj = $(myObj);
	tmpObjDom = audioObj;

	if($("#AudioEditLinks").length==0){

		var bdDiv = '<div id="AudioEditLinks" class="gjs-mdl-container" style="" >';
		bdDiv += '<div class="gjs-mdl-dialog-v2 gjs-one-bg gjs-two-color">';
		bdDiv += '<div class="gjs-mdl-header">';
		bdDiv += '<div class="gjs-mdl-title trd">Edition</div>';
		bdDiv += '<div class="gjs-mdl-btn-close gjs-mdl-btn-close-audio" onClick="closeAllEditWindows()" ';
		bdDiv += ' data-close-modal="">⨯</div>';
		bdDiv += '</div>';
		
		bdDiv += '<div class="gjs-am-add-asset" ';
		bdDiv += 'style="padding:25px;font-size:16px;" >';
		bdDiv += 'File&nbsp;:&nbsp;';
		bdDiv += '<input id="inputAudioLink" type="text" value="http://" style="width:450px;font-size:12px;padding:5px" />';
		bdDiv += '&nbsp;<input onClick="filterGlobalFiles=\'.mp3\';showFileManagerStudio2(23,\'inputAudioLink\',0);" ';
		bdDiv += ' style="width:50px;" ';
		bdDiv += ' class="gjs-one-bg ludiButtonSave" type="button" value="..." />';
		
		bdDiv += '<br/>';
		bdDiv += '<div style="padding:25px;text-align:right;" >';
		bdDiv += '<input onClick="saveAudioEdit()" ';
		bdDiv += ' class="gjs-one-bg ludiButtonSave trd" type="button" value="Save" /><br/>';
		bdDiv += '</div>';
		
		bdDiv += '</div>';
		bdDiv += '</div>';

		bdDiv += '<div class="gjs-mdl-collector" style="display: none"></div>';
		bdDiv += '</div>';

		$('body').append(bdDiv);

	}

	if($("#AudioEditLinks").length==1){
		
		strLinkString = audioObj.attr("datahref");
		var idm = Math.floor(Math.random() * Math.floor(200));
		tmpNameDom = 'tempnode' + idm;
		audioObj.attr("id",tmpNameDom);

		oldUrlAudio = strLinkString;
		$('#inputAudioLink').val(strLinkString);	
		$('.ludimenu').css("z-index","2");
		$('#AudioEditLinks').css("display","");
		windowEditorIsOpen = true;
		loadaFunction();
		traductAll();

	}

}

function saveAudioEdit(){

	var inputAudioLink = $('#inputAudioLink').val();

	var audioObj = tmpObjDom;
	audioObj.attr("datahref",inputAudioLink);
	audioObj.attr("src",inputAudioLink);

	audioObj.load();

	var gjsHtml = localStorage.getItem("gjs-html-" + idPageHtml);
	if(oldUrlAudio!=""&&inputAudioLink!=""&&oldUrlAudio!=inputAudioLink){
		gjsHtml = gjsHtml.replace(oldUrlAudio,inputAudioLink);
		gjsHtml = gjsHtml.replace(oldUrlAudio,inputAudioLink);
		oldUrlAudio = inputAudioLink;
		localStorage.setItem("gjs-html-" + idPageHtml,gjsHtml);
		$('.ludimenu').css("z-index","1000");
		saveSourceFrame(false,false,0);
	}
	
	closeAllEditWindows();
}

function installVideoEdit(){
	
}

function displayVideoEdit(myObj){

	var vidObj = $(myObj);
	tmpObjDom = vidObj;

	if($("#VideoEditLinks").length==0){

		var bdDiv = '<div id="VideoEditLinks" class="gjs-mdl-container" style="" >';
		bdDiv += '<div class="gjs-mdl-dialog-v2 gjs-one-bg gjs-two-color">';
		bdDiv += '<div class="gjs-mdl-header">';
		bdDiv += '<div class="gjs-mdl-title">Edition</div>';
		bdDiv += '<div class="gjs-mdl-btn-close gjs-mdl-btn-close-video" onClick="closeAllEditWindows()" data-close-modal="">⨯</div>';
		bdDiv += '</div>';
		
		bdDiv += '<div class="gjs-am-add-asset" ';
		bdDiv += 'style="padding:25px;font-size:16px;" >';
		bdDiv += 'File&nbsp;:&nbsp;';
		bdDiv += '<input id="inputVideoLink" type="text" value="http://" style="width:450px;font-size:12px;padding:5px;" />';
		bdDiv += '&nbsp;<input onClick="filterGlobalFiles=\'.mp4\';showFileManagerStudio2(23,\'inputVideoLink\',0);" ';
		bdDiv += ' class="gjs-one-bg ludiButtonSave" type="button" value="..." />';
		
		bdDiv += '<br/>';
		bdDiv += '<div style="padding:25px;text-align:right;" >';
		bdDiv += '<input onClick="saveVideoEdit()" ';
		bdDiv += ' class="gjs-one-bg ludiButtonSave trd" type="button" value="Save" /><br/>';
		bdDiv += '</div>';
		
		bdDiv += '</div>';
		bdDiv += '</div>';

		bdDiv += '<div class="gjs-mdl-collector" style="display: none"></div>';
		bdDiv += '</div>';
		
		$('body').append(bdDiv);

		/*
		$('.gjs-mdl-btn-close-video').click(function(){
			if(OneCloseImage){
				OneCloseImage = false;
				ajustHtmlToGrap();
				setTimeout(function(){
					OneCloseImage = true;
				},1000);
			}
		});
		*/
	}

	if($("#VideoEditLinks").length==1){
		
		strLinkString = vidObj.attr("datahref");
		var idm = Math.floor(Math.random() * Math.floor(200));
		tmpNameDom = 'tempnode' + idm;
		vidObj.find(".sourcevid").attr("name",tmpNameDom);
		vidObj.attr("id",tmpNameDom);

		oldUrlVideo = strLinkString;
		$('#inputVideoLink').val(strLinkString);	
		$('.ludimenu').css("z-index","2");
		$('#VideoEditLinks').css("display","");
		windowEditorIsOpen = true;
		loadaFunction();
		traductAll();
	}

}

function saveVideoEdit(){

	var inputVideoLink = $('#inputVideoLink').val();

	var vidObj = tmpObjDom;
	vidObj.attr("datahref",inputVideoLink);
	vidObj.find(".sourcevid").attr("src",inputVideoLink);

	vidObj.load();

	var gjsHtml = localStorage.getItem("gjs-html-" + idPageHtml);
	if(oldUrlVideo!=""&&inputVideoLink!=""&&oldUrlVideo!=inputVideoLink){
		gjsHtml = gjsHtml.replace(oldUrlVideo,inputVideoLink);
		gjsHtml = gjsHtml.replace(oldUrlVideo,inputVideoLink);
		oldUrlVideo = inputVideoLink;
		localStorage.setItem("gjs-html-" + idPageHtml,gjsHtml);
		$('.ludimenu').css("z-index","1000");
		saveSourceFrame(false,false,0);
	}
	
	var rH = baseButton;
    rH = rH.replace("video/oel-teachdoc.mp4",inputVideoLink);
	rH = rH.replace("video/oel-teachdoc.mp4",inputVideoLink);
    if(GlobalTagGrappeObj=='div'){
		
	}
	setAbstractObjContent(rH);

	closeAllEditWindows();
}

var haveAchange = false;
var changeDataBase = new Array();
var identchange = '';
var indexChangeObj = -1;
var indexQcmIteration = 1;

function displayQcmEdit(myObj) {
	
	var qcmObj = $(myObj);
	tmpObjDom = qcmObj;
	
	identchange = getUnikId();
	tmpObjDom.attr("data-ref",identchange);
	searchPosiIndex(identchange,"qcmbarre");
	
	if ($("#QcmEditLinks").length==0) {

		var bdDiv = '<div id="QcmEditLinks" class="gjs-mdl-container" style="" >';
		bdDiv += '<div class="gjs-mdl-dialog-v2 gjs-one-bg gjs-two-color" ';
		bdDiv += ' style="max-width:520px!important;" >';

		bdDiv += '<div class="gjs-mdl-header">';
		bdDiv += '<div class="gjs-mdl-title">Edition</div>';
		bdDiv += '<div class="gjs-mdl-btn-close gjs-mdl-btn-close-audio" ';
		bdDiv += ' onClick="closeAllEditWindows()" ';
		bdDiv += ' data-close-modal="">⨯</div>';
		bdDiv += '</div>';

		bdDiv += '<div class="innerQcmEdit-area">';
		bdDiv += '</div>';

		bdDiv += '<div class="gjs-am-add-asset" ';
		bdDiv += 'style="padding:25px;padding-top:5px;padding-bottom:15px;font-size:16px;" >';
		

		bdDiv += '<div style="position:absolute;left:5px;bottom:20px;width:200px;" >';
		bdDiv += addMultiOptions();
		bdDiv += '</div>';

		bdDiv += '<div style="padding:10px;text-align:right;" >';

		bdDiv += '<input onClick="saveQcmEdit()" ';
		bdDiv += ' class="gjs-one-bg ludiButtonSave trd" type="button" value="Save" /><br/>';
		bdDiv += '</div>';
	
		bdDiv += '</div>';
	
		bdDiv += '<div class="gjs-mdl-collector" style="display:none"></div>';

		bdDiv += '</div>';

		$('body').append(bdDiv);


	}

	if($("#QcmEditLinks").length==1){
		
		$('#QcmEditLinks').css("display",'');

		indexQcmIteration++;

		var quizzTextQcm = qcmObj.find('.quizzTextqcm').html();

		$('.innerQcmEdit-area').html(innerQcmEdit());

		$('#areaQuizzText'+indexQcmIteration).val(quizzTextQcm);
		
		$('#checkboxMulti').prop("checked",false);

		$('#checkAnswerA').prop("checked",false);
		$('#checkAnswerB').prop("checked",false);
		$('#checkAnswerC').prop("checked",false);
		$('#checkAnswerD').prop("checked",false);
		$('#checkAnswerE').prop("checked",false);
		$('#checkAnswerF').prop("checked",false);

		var index = 0;
		var nbCheck = 0;
		qcmObj.find('tr td').each(function(index){

			//line 1
			if(index==1){
				var cocheA = $(this).find('img');
				if(cocheA.attr('src').indexOf('1.png')!=-1){
					$('#checkAnswerA').prop("checked",true);
					nbCheck++;
				}
			}
			if(index==2){
				var VtextAnswerA = $(this).html();
				$('#textAnswerA').val(VtextAnswerA);
				
			}
			
			//line 2
			if(index==3){
				var cocheB = $(this).find('img');
				if(cocheB.attr('src').indexOf('1.png')!=-1){
					$('#checkAnswerB').prop("checked",true);
					nbCheck++;
				}
			}
			if(index==4){
				var VtextAnswerB = $(this).html();
				$('#textAnswerB').val(VtextAnswerB);
			}

			//line 3
			if(index==5){
				var cocheC = $(this).find('img');
				var imgSrc = cocheC.attr('src');
				if(typeof imgSrc == 'undefined'||imgSrc=='undefined'){
					imgSrc = '';
				}
				if(imgSrc.indexOf('1.png')!=-1){
					$('#checkAnswerC').prop("checked",true);
					nbCheck++;
				}
			}
			if(index==6){
				var VtextAnswerC = $(this).html();
				$('#textAnswerC').val(VtextAnswerC);
			}

			//line 4
			if(index==7){
				var cocheD = $(this).find('img');
				var imgSrc = cocheD.attr('src');
				if(typeof imgSrc == 'undefined'||imgSrc=='undefined'){
					imgSrc = '';
				}
				if(imgSrc.indexOf('1.png')!=-1){
					$('#checkAnswerD').prop("checked",true);
					nbCheck++;
				}

			}
			if(index==8){
				var VtextAnswerD = $(this).html();
				$('#textAnswerD').val(VtextAnswerD);
			}

			//line 5
			if(index==9){
				var cocheD = $(this).find('img');
				var imgSrc = cocheD.attr('src');
				if(typeof imgSrc == 'undefined'||imgSrc=='undefined'){
					imgSrc = '';
				}
				if(imgSrc.indexOf('1.png')!=-1){
					$('#checkAnswerE').prop("checked",true);
					nbCheck++;
				}

			}
			if(index==10){
				var VtextAnswerD = $(this).html();
				$('#textAnswerE').val(VtextAnswerD);
			}

			//line 6
			if(index==11){
				var cocheD = $(this).find('img');
				var imgSrc = cocheD.attr('src');
				if(typeof imgSrc == 'undefined'||imgSrc=='undefined'){
					imgSrc = '';
				}
				if(imgSrc.indexOf('1.png')!=-1){
					$('#checkAnswerF').prop("checked",true);
					nbCheck++;
				}
			}
			if(index==12){
				var VtextAnswerD = $(this).html();
				$('#textAnswerF').val(VtextAnswerD);
			}

			index = index + 1;

		});

		var mopt = qcmObj.html();
		if (mopt.indexOf('multiqcmopts')!=-1||nbCheck>1) {
			$('#checkboxMulti').prop("checked",true);
		}

		$('.ludimenu').css("z-index",'2');
		windowEditorIsOpen = true;
		loadaFunction();
		$('textarea#areaQuizzText'+indexQcmIteration).tinymce(
			{menubar: false,statusbar: false}
		);
		traductAll();
		autoQcmEdit();

	}

}

function innerQcmEdit() {

	var bdv = '';

	bdv += inLineTextArea("QuizzText"+indexQcmIteration);
	bdv += inLineInput("AnswerA");
	bdv += inLineInput("AnswerB");
	bdv += inLineInput("AnswerC");
	bdv += inLineInput("AnswerD");
	bdv += inLineInput("AnswerE");
	bdv += inLineInput("AnswerF");

	return bdv;

}

function searchPosiIndex(idRef,classname) {

	var iframe = $('.gjs-frame');
	var iframeBody = iframe.contents().find("body");
	var allTables = iframeBody.find("table");
	var indexObj = 0;
	
	indexChangeObj = -1;

	allTables.each(function(index){
		if($(this).hasClass(classname)){
			indexObj++;
			var ObjIdRef = $(this).attr("data-ref");
			if(ObjIdRef==idRef){
				indexChangeObj = indexObj;
			}
		}
	});

}

function inLineTextArea(idRef) {
	
	var bdDiv = "";
	bdDiv += '<p style="padding:4px;margin:5px;" >';
	bdDiv += '<textarea id="area'+idRef+'" name="area'+idRef+'" ';
	bdDiv += 'rows="5" cols="38" ';
	bdDiv += 'style="width:485px;font-size:13px;padding:2px;margin-left:27px;resize:none;" ></textarea>';
	bdDiv += '</p>';
	return bdDiv;

}

function inLineInput(idRef) {
	var bdDiv = "";
	bdDiv += '<p style="padding:5px;margin:5px;" >';
	bdDiv += '<input type="checkbox" ';
	bdDiv += ' onClick="onClickCheckBox(this)" ';
	bdDiv += ' class=checkRapidWindows id="check'+idRef+'" ';
	bdDiv += ' name="check'+idRef+'" ></input>';
	bdDiv += '<input id="text'+idRef+'" class="checkInputAnswer" ';
	bdDiv += ' onChange="autoQcmEdit();" ';
	bdDiv += ' onkeyup="autoQcmEdit()" ';
	bdDiv += ' type="text" value="" ';
	bdDiv += ' style="width:450px;font-size:17px;padding:2px;" />';
	bdDiv += '</p>';
	return bdDiv;
}

function onClickCheckBox(objT) {

	if($(objT).is(':checked')){
		if(!document.getElementById('checkboxMulti').checked){
			$('#checkAnswerA').prop("checked",false);
			$('#checkAnswerB').prop("checked",false);
			$('#checkAnswerC').prop("checked",false);
			$('#checkAnswerD').prop("checked",false);
			$('#checkAnswerE').prop("checked",false);
			$('#checkAnswerF').prop("checked",false);
		}
		$(objT).prop("checked",true);
	}

}

function saveQcmEdit() {

	if(onlyOneUpdate==false||indexChangeObj==-1){
		return false;
	}

	var TareaQuizzText = $('#areaQuizzText'+indexQcmIteration).val();

	var TtextAnswerA = $('#textAnswerA').val();
	var TtextAnswerB = $('#textAnswerB').val();
	var TtextAnswerC = $('#textAnswerC').val();
	var TtextAnswerD = $('#textAnswerD').val();
	var TtextAnswerE = $('#textAnswerE').val();
	var TtextAnswerF = $('#textAnswerF').val();

	var renderH = "<tbody>";

	renderH += "<div class='quizzblockdeco' ></div>";
	var letbeg = 'm';
	if (document.getElementById('checkboxMulti').checked) {
		renderH += "<div class='multiqcmopts' ></div>";
		letbeg = 'c';
	}

	if(TareaQuizzText==''){
		TtextAnsTareaQuizzTextwerA = '?';
	}

	renderH += "<tr>";
	renderH += "<td colspan='2' class='quizzTextqcm' >" + TareaQuizzText;
	renderH += "</td>";
	renderH += "</tr>";

	if(TtextAnswerA==''){
		TtextAnswerA = 'Answer 1';
	}
	
	

	if(TtextAnswerA!=''){
		renderH += "<tr class='quizzTextTr' ><td class=quizzTextTd >";
		if($('#checkAnswerA').is(':checked')){
			renderH += "<img src='img/qcm/"+letbeg+"atgreen1.png' class='checkboxqcm' />";
		}else{
			renderH += "<img src='img/qcm/"+letbeg+"atgreen0.png' class='checkboxqcm' />";
		}
		renderH += "</td>";
		renderH += "<td style='text-align:left;' >" + TtextAnswerA + "</td>";
		renderH += "</tr>";	
	}
	if(TtextAnswerB!=''){
		renderH += "<tr class='quizzTextTr' ><td class=quizzTextTd >";
		if($('#checkAnswerB').is(':checked')){
			renderH += "<img src='img/qcm/"+letbeg+"atgreen1.png' class='checkboxqcm' />";
		}else{
			renderH += "<img src='img/qcm/"+letbeg+"atgreen0.png' class='checkboxqcm' />";
		}
		renderH += "</td>";
		renderH += "<td style='text-align:left;'  >" + TtextAnswerB + "</td>";
		renderH += "</tr>";	
	}
	if(TtextAnswerC!=''){
		renderH += "<tr class='quizzTextTr' ><td class=quizzTextTd >";
		if($('#checkAnswerC').is(':checked')){
			renderH += "<img src='img/qcm/"+letbeg+"atgreen1.png' class='checkboxqcm' />";
		}else{
			renderH += "<img src='img/qcm/"+letbeg+"atgreen0.png' class='checkboxqcm' />";
		}
		renderH += "</td>";
		renderH += "<td style='text-align:left;'  >" + TtextAnswerC + "</td>";
		renderH += "</tr>";	
	}
	if(TtextAnswerD!=''){
		renderH += "<tr class='quizzTextTr' ><td class=quizzTextTd >";
		if($('#checkAnswerD').is(':checked')){
			renderH += "<img src='img/qcm/"+letbeg+"atgreen1.png' class='checkboxqcm' />";
		}else{
			renderH += "<img src='img/qcm/"+letbeg+"atgreen0.png' class='checkboxqcm' />";
		}
		renderH += "</td>";
		renderH += "<td style='text-align:left;'  >" + TtextAnswerD + "</td>";
		renderH += "</tr>";	
	}
	if(TtextAnswerE!=''){
		renderH += "<tr class='quizzTextTr' ><td class=quizzTextTd >";
		if($('#checkAnswerE').is(':checked')){
			renderH += "<img src='img/qcm/"+letbeg+"atgreen1.png' class='checkboxqcm' />";
		}else{
			renderH += "<img src='img/qcm/"+letbeg+"atgreen0.png' class='checkboxqcm' />";
		}
		renderH += "</td>";
		renderH += "<td style='text-align:left;'  >" + TtextAnswerE + "</td>";
		renderH += "</tr>";	
	}
	if(TtextAnswerF!=''){
		renderH += "<tr class='quizzTextTr' ><td class=quizzTextTd >";
		if($('#checkAnswerF').is(':checked')){
			renderH += "<img src='img/qcm/"+letbeg+"atgreen1.png' class='checkboxqcm' />";
		}else{
			renderH += "<img src='img/qcm/"+letbeg+"atgreen0.png' class='checkboxqcm' />";
		}
		renderH += "</td>";
		renderH += "<td style='text-align:left;'  >" + TtextAnswerF + "</td>";
		renderH += "</tr>";	
	}

	renderH += "</tbody>";
	
	if(GlobalTagGrappeObj=='div'){
		var rdrFull = '<table onMouseDown="parent.displayEditButon(this);" ';
		rdrFull += ' class="qcmbarre" style="width:100%;">';
		rdrFull += renderH + '</table>';
		renderH = rdrFull;
	}

	//var qcmObj = $(tmpObjDom);
	//qcmObj.html(renderH);

	setAbstractObjContent(renderH);

	closeAllEditWindows();

	$('.ludimenu').css("z-index","1000");
	saveSourceFrame(false,false,0);

}

function getUnikId() {

	var idNum = Math.floor(Math.random() * 100);
	var iLetter = '';
	var characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
	var charactersLength = characters.length;
	for(var i=0;i<15; i++){
		iLetter += characters.charAt(Math.floor(Math.random() * charactersLength));
	}
  
	return idNum + iLetter;
  
}

function autoQcmEdit() {

	if ($('#textAnswerB').val()!='') {
		$('#checkAnswerC').css('display','');
		$('#textAnswerC').css('display','');
	} else {
		$('#checkAnswerC').css('display','none');
		$('#textAnswerC').css('display','none');
		$('#checkAnswerC').prop("checked",false);
	}

	if ($('#textAnswerC').val()!='') {
		$('#checkAnswerD').css('display','');
		$('#textAnswerD').css('display','');
	} else {
		$('#checkAnswerD').css('display','none');
		$('#textAnswerD').css('display','none');
		$('#checkAnswerD').prop("checked",false);
	}

	if ($('#textAnswerD').val()!='') {
		$('#checkAnswerE').css('display','');
		$('#textAnswerE').css('display','');
	} else {
		$('#checkAnswerE').css('display','none');
		$('#textAnswerE').css('display','none');
		$('#checkAnswerE').prop("checked",false);
	}
	
	if ($('#textAnswerE').val()!='') {
		$('#checkAnswerF').css('display','');
		$('#textAnswerF').css('display','');
	} else {
		$('#checkAnswerF').css('display','none');
		$('#textAnswerF').css('display','none');
		$('#checkAnswerF').prop("checked",false);
	}
	
}

function addMultiOptions(){

	var code = 'Multi';
	var label = 'Multi answer';
    var bdDiv = '<div style="position:relative;margin-left:10px;';
    bdDiv += 'width:240px;margin-bottom:4px;" >';
    bdDiv += '<label style="margin-top:1px;" class="el-switch el-switch-green" >';
    bdDiv += '<input id="checkbox'+code+'" type="checkbox" name="switch" >';
    bdDiv += '<span class="el-switch-style"></span>';
    bdDiv += '</label>';
    bdDiv += '<div class="margin-r trd" ';
    bdDiv += ' style="position:absolute;left:50px;top:0px;padding:5px;" >';
    bdDiv += '&nbsp;'+label+'</div>';
    bdDiv += '</div>';

    return bdDiv;

}
var timerSecCount = 0;

function loadTerminalStudio() {

    document.body.insertAdjacentHTML('beforeend', `
    <div class="logtc-command-panel" id="commandPanel">
        <div id="logtc-panelHeader" class="logtc-panel-header"">
            <span>&#128187; Terminal</span>
            <button class="logtc-minimize-btn" id="minimizeBtn">−</button>
        </div>
        <div class="logtc-output-area" id="outputArea">
            <div class="logtc-command-line">Type 'help' to see available commands.</div>
        </div>
        <div class="logtc-input-area">
            <span class="logtc-prompt">$</span>
            <input type="text" class="logtc-command-input" id="commandInput" placeholder="..." autocomplete="off">
        </div>
    </div>
    `);

    const outputArea = document.getElementById('outputArea');
    const commandInput = document.getElementById('commandInput');
    const commandPanel = document.getElementById('commandPanel');
    const minimizeBtn = document.getElementById('minimizeBtn');
    const panelHeader = document.getElementById('logtc-panelHeader');

    let commandHistory = [];
    let historyIndex = -1;
    
    let projectState = {
        saved: false,
        rendered: false,
        dependencies: ['react', 'webpack', 'babel']
    };

    function executeCommand(command) {
        const cmd = command.trim().toLowerCase();
        const args = command.trim().split(' ');
        
        addOutput(`$ ${command}`, 'logtc-command-line');

        timerSecCount = new Date().getTime();

        switch(cmd) {
            case 'save':
                addOutput('Saving...', 'logtc-command-info');
                saveSourceFrame(false,false,0);
                break;
            case 'render':

                break;
            case 'render-sco -w' , 'render-sco -rw':
                addOutput('Generating SCORM files...', 'logtc-command-info');
                forceProcessRenderSco();
                break;
            case 'render-sco':
                addOutput('Generating SCORM files...', 'logtc-command-info');
                processRenderSco();
                 break;
            case 'install':
                 addOutput('Show plugins...', 'logtc-command-info');
                $('.extraPluginRightPanel').css("display","flex");
                break;
            case 'clear':
                outputArea.innerHTML = '';
                break;
            case 'close':
                destroyTerminalStudio();
                return;
                break;
            case 'help':
                addOutput('Available commands:', 'logtc-command-info');
                addOutput('  save       - Save the project', 'logtc-command-line');
                addOutput('  render     - Generate the project output', 'logtc-command-line');
                addOutput('  render-sco - Init scorm files (-w -rw)', 'logtc-command-line');
                addOutput('  install    - Install plugin', 'logtc-command-line');
                addOutput('  clear      - Clear the terminal', 'logtc-command-line');
                addOutput('  close      - Close the terminal', 'logtc-command-line');
                addOutput('  help       - Show this help', 'logtc-command-line');
                addOutput('  status     - Show project status', 'logtc-command-line');
                break;
                
            case 'status':
                addOutput('Project state:', 'logtc-command-info');
                addOutput('  localFolder :' + localFolder, 'logtc-command-line');
                addOutput('  colorsPath  :' + colorsPath, 'logtc-command-line');
                break;
                
            default:
                addOutput(`'${command}' not recognized.`, 'logtc-command-error');
                break;
        }
    }

    function togglePanel() {
        commandPanel.classList.toggle('logtc-minimized');
        minimizeBtn.textContent = commandPanel.classList.contains('logtc-minimized') ? '+' : '−';
    }

    // Gestion des événements clavier
    commandInput.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            const command = this.value;
            if (command.trim()) {
                commandHistory.push(command);
                historyIndex = commandHistory.length;
                executeCommand(command);
            }
            this.value = '';
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            if (historyIndex > 0) {
                historyIndex--;
                this.value = commandHistory[historyIndex];
            }
        } else if (e.key === 'ArrowDown') {
            e.preventDefault();
            if (historyIndex < commandHistory.length - 1) {
                historyIndex++;
                this.value = commandHistory[historyIndex];
            } else {
                historyIndex = commandHistory.length;
                this.value = '';
            }
        }
    });

    // Auto-focus sur l'input
    commandInput.focus();

    panelHeader.addEventListener('click', togglePanel);

    // Maintenir le focus
    document.addEventListener('click', function(e) {
        if (commandPanel.contains(e.target)) {
            setTimeout(() => commandInput.focus(), 0);
        }
    });

}

function destroyTerminalStudio() {
    const commandPanel = document.getElementById('commandPanel');
    if (commandPanel) {
        commandPanel.remove();
    }
}

function addOutput(text, type = 'logtc-command-line') {

    if (document.getElementById('outputArea')) {
        var timerStepsCount = new Date().getTime();
        var t = timerStepsCount - timerSecCount;
        var tc = type;
        if (t < 0) { t = 0; }
        if (t > 1000) { t = (t / 1000).toFixed(1) + "s"; } else { t = t + "ms"; }
        var textTime = " (" + t + ")";
        if (tc < 100) { textTime = "";}
        if (t=="0ms") { textTime = ""; }

        const outputArea = document.getElementById('outputArea');
        const div = document.createElement('div');
        div.className = type;
        div.innerHTML = text + textTime;
        outputArea.appendChild(div);
        outputArea.scrollTop = outputArea.scrollHeight;
    }

}

var indexTxtEdition = 0;

function displayTeachDocTextEdit(myObj) {

	var QObj = $(myObj);
	tmpObjDom = QObj;

	identchange = getUnikId();
	tmpObjDom.attr("data-ref",identchange);

	if (!isTeachDocTextEdit(QObj)) {
		alert('error');
		return false;
	}
	
	indexTxtEdition++;

	if ($("#TeachDocTextEditWindows").length==0) {
		var bdDiv = '<div id="TeachDocTextEditWindows" class="gjs-mdl-container" style="" >';
		bdDiv += getInnerTextEngine();
		bdDiv += '</div>';
		$('body').append(bdDiv);
	} else {
		$('#TeachDocTextEditWindows').html(getInnerTextEngine());
	}

	if ($("#TeachDocTextEditWindows").length==1) {
		
		var getTextContent = QObj.find('.teachdoctextContent').html();
		$('#areaTeachDocText' + indexTxtEdition ).val(getTextContent);
		$('.ludimenu').css("z-index",'2');
		$('#TeachDocTextEditWindows').css("display",'');
		windowEditorIsOpen = true;
		textEditorIsOpen = true;
		loadaFunction();
		$('#areaTeachDocText'+indexTxtEdition).tinymce({
			menubar: false, statusbar: false,
			toolbar: 'undo redo | formatselect forecolor | bold italic underline | bullist numlist outdent indent link unlink removeformat | alignleft aligncenter alignright alignjustify | fontselect fontsizeselect blockquote paste code ',
			block_formats: 'Paragraph=p;Heading 1=h1;Heading 2=h2;Heading 3=h3;Heading 4=h4;Infos=samp;Warning=address;',
			plugins: 'link lists paste table', contextmenu: 'link lists',
			content_css: 'templates/colors/minicss/min-' + colorsPath,
			formats: {
				alignleft: {selector : 'p,h1,h2,h3,h4,h5,h6,td,th,div,ul,ol,li,table,img', classes : 'oelAlignLeft'},
				aligncenter: {selector : 'p,h1,h2,h3,h4,h5,h6,td,th,div,ul,ol,li,table,img', classes : 'oelAligncenter'},
				alignright: {selector : 'p,h1,h2,h3,h4,h5,h6,td,th,div,ul,ol,li,table,img', classes : 'oelAlignright'},
				alignjustify: {selector : 'p,h1,h2,h3,h4,h5,h6,td,th,div,ul,ol,li,table,img', classes : 'oelAlignjustify'},
			}
		});

		traductAll();

		if (typeof saveHistoTeachTextEdit === "function") {
			setTimeout(function(){
				saveHistoTeachTextEdit();
			},10000);
		}
	}

}

function getInnerTextEngine() {

	var bdDiv = '<div id="txtEditWinplace" class="gjs-mdl-dialog-v2 winPlace gjs-one-bg gjs-two-color" ';
	bdDiv += ' style="max-width:800px!important;" >';
	bdDiv += '<div class="gjs-mdl-header">';
	bdDiv += '<div class="gjs-mdl-title">Edition</div>';

	if (typeof extrasUItextEditor === "function") {
		bdDiv += extrasUItextEditor();
	}

	bdDiv += '<div onClick="goToPlace(3);" class="winbtn-right" ></div>';
	bdDiv += '<div onClick="goToPlace(2);" class="winbtn-center" ></div>';
	bdDiv += '<div onClick="goToPlace(1);" class="winbtn-left" ></div>';

	bdDiv += '<div class="gjs-mdl-btn-close gjs-mdl-btn-close-audio" ';
	bdDiv += ' onClick="closeAllEditWindows()" ';
	bdDiv += ' data-close-modal="">⨯</div>';
	bdDiv += '</div>';
	
	bdDiv += '<div class="gjs-am-add-asset" ';
	bdDiv += 'style="padding:15px;padding-top:10px;font-size:16px;" >';
	
	bdDiv += '<p style="padding:5px;margin:0px;" >';
	bdDiv += '<textarea id="areaTeachDocText'+indexTxtEdition+'" name="areaTeachDocText'+indexTxtEdition+'" ';
	bdDiv += 'rows="25"';
	bdDiv += 'style="width:100%;font-size:13px;padding:2px;margin-left:20px;resize:none;" ></textarea>';
	bdDiv += '</p>';

	bdDiv += '<div style="padding:25px;padding-top:10px;padding-bottom:5px;text-align:right;" >';
	bdDiv += '<input onClick="saveTeachDocTextEdit()" ';
	bdDiv += ' class="gjs-one-bg ludiButtonSave trd" type="button" value="Save" /><br/>';
	bdDiv += '</div>';
	
	bdDiv += '</div>';
	bdDiv += '</div>';

	bdDiv += '<div class="list-text-histo" ></div>';

	bdDiv += '<div class="gjs-mdl-collector" style="display:none"></div>';

	return bdDiv;

}

function goToPlace(i) {

	$('#txtEditWinplace').removeClass("winPlaceLeft");
	$('#txtEditWinplace').removeClass("winPlaceRight");
	$('#TeachDocTextEditWindows').removeClass("winPlaceClear");
	var wback = $('#TeachDocTextEditWindows').width();
	var wplace = (wback - $('#txtEditWinplace').width())/2;
	
	if (i==1) {
		$('#txtEditWinplace').css("margin-right","auto");
		$('#txtEditWinplace').css("margin-left",wplace + "px");
		$('#TeachDocTextEditWindows').addClass("winPlaceClear");
		setTimeout(function(){
			$('#txtEditWinplace').addClass("winPlaceLeft");
		},50);
	}
	if (i==3) {
		$('#txtEditWinplace').css("margin-left","auto");
		$('#txtEditWinplace').css("margin-right",wplace + "px");
		$('#TeachDocTextEditWindows').addClass("winPlaceClear");
		setTimeout(function(){
			$('#txtEditWinplace').addClass("winPlaceRight");
		},50);
	}

}

function isTeachDocTextEdit(QObj) {

	var b = true;
	var tagnam = QObj.prop("tagName");
	tagnam = tagnam.toLowerCase();
	
	if (QObj.hasClass("teachdoctext")&&tagnam=='table') {
		b = true;
	} else {
		b = false;
	}

	var innerObj = QObj.innerHTML;
	if (typeof innerObj =='undefined'||innerObj==''||innerObj=='undefined') {
		if (typeof QObj[0] !='undefined') {
			innerObj = QObj[0].innerHTML;
			if (innerObj.slice(0, 6)=='<table') {
				if (innerObj.indexOf('teachdoctext')!=-1){
					b = true;
				}
			}
		}
	}

	var btnObjDivhref = QObj.find('div');
	typesource = btnObjDivhref.parent().find('span.typesource').html();
	if(typesource===undefined){typesource = '';}
	if (typesource!='') {
		b = false;
	}

	return b;

}

function saveTeachDocTextEdit() {

	if (onlyOneUpdate==false) {
		return false;
	}

	var TareaTeachDocText = $('#areaTeachDocText'+indexTxtEdition).val();
	
	if (TareaTeachDocText.indexOf('data:image/gif;base64,')!=-1 ) {
		return false;
	}
	if (TareaTeachDocText.indexOf('data:image/png;base64,')!=-1 ) {
		return false;
	}
	if (TareaTeachDocText.indexOf('data:image/jpg;base64,')!=-1 ) {
		return false;
	}

	var renderH = "<tbody>";

	if (TareaTeachDocText=='') {
		TareaTeachDocText = '?';
	}

	renderH += "<tr>";
	renderH += "<td class='teachdoctextContent' >" + TareaTeachDocText + "</td>";
	renderH += "</tr>";

	renderH += "</tbody>";

	if (GlobalTagGrappeObj=='div') {
		var rdrFull = '<table onMouseDown="parent.displayEditButon(this);" ';
		rdrFull += ' class="teachdoctext" style="width:97%;">';
		rdrFull += renderH + '</table>';
		renderH = rdrFull;
	}
	
	setAbstractObjContent(renderH);
	
	closeAllEditWindows();
	$('.ludiSpeedTools').css("display","none");
	$('.ludimenu').css("z-index","1000");
	saveSourceFrame(false,false,0);

}

function cleanPasteEdit() {
	
	var html = $('#areaTeachDocText'+indexTxtEdition).val();

    html = html.replace(/<(\/)*(\\?xml:|meta|link|span|font|del|ins|st1:|[ovwxp]:)((.|\s)*?)>/gi, ''); // Unwanted tags
    html = html.replace(/(class|style|type|start)=("(.*?)"|(\w*))/gi, '');
    html = html.replace(/<style(.*?)style>/gi, '');
    html = html.replace(/<script(.*?)script>/gi, '');
    html = html.replace(/<!--(.*?)-->/gi,'');
    
    return html;
}


// Load the btn extras text editor
function extrasUItextEditor() {

	var bdDiv = '<div onClick="listHistotextEditor()" class="winbtn-right-2" ></div>';
    return bdDiv;

}

function listHistotextEditor() {
    
    goToPlace(3);
    var windowWidth = $(window).width();
    var wwid = (windowWidth / 2);
    $(".list-text-histo").css("width",wwid + "px");
    $(".list-text-histo").css("display","block");
    
}

function saveHistoTeachTextEdit() {

	if (textEditorIsOpen==true) {
		if (document.getElementById('areaTeachDocText' + indexTxtEdition)) {
			var areaTeachText = $('#areaTeachDocText' + indexTxtEdition).val();
			var incr5 = (1000 * 60 * 5);
			var date5m = Date.now();
			date5m = parseInt(date5m / incr5);
			amplify.store("teachdoctext-html-"+idPageHtml+"-"+date5m,areaTeachText);
		}
	}

	setTimeout(function(){
		saveHistoTeachTextEdit();
	},10000);

}
var VirtualObjectOel;
var virtualObjStyleNum = 1;
var virtualObjColor1 = '#337ab7';
var virtualObjColor2 = '#229954';
var btnOpenAcessTxt = false;

function displayBtnEdit(myObj){

	var btnObj = $(myObj);
	tmpObjDom = btnObj;
	
	identchange = getUnikId();
	tmpObjDom.attr("data-ref",identchange);

	if ($("#BtnEditWindows").length==0) {
		
		var bdDiv = '<div id="BtnEditWindows" class="gjs-mdl-container" >';

		// div 1
		bdDiv += '<div class="gjs-mdl-dialog-v2 gjs-one-bg gjs-two-color" ';
		bdDiv += ' style="max-width:880px!important;" >';
		
		bdDiv += getTitleBar('Edition button');

		// div 2
		bdDiv += '<div class="gjs-am-add-asset" ';
		bdDiv += ' style="padding:25px;padding-top:10px;font-size:16px;" >';

		// div 3
		bdDiv += '<div style="border:solid 0px purple;padding:5px;padding-top:1px;margin:0px;margin-bottom:1px;height:30px;" >';
		
		bdDiv += '<div style="padding:5px;width:70px;float:left;text-align:right;" >';
		bdDiv += '<span class="trd" >Bouton</span>&nbsp;1&nbsp;:&nbsp;</div>';

		bdDiv += '<input id="inputButtonLink" type="text" value="" ';
		bdDiv += ' style="width:150px;font-size:12px;padding:5px;float:left;" />';

		bdDiv += '<div class="trd" style="padding:5px;width:66px;float:left;text-align:right;" >';
		bdDiv += 'Style&nbsp;:&nbsp;</div>';

		bdDiv += '<input style="float:left;display:none;" ';
		bdDiv += ' id="inputButtonStyle" type="text" value="" />';

		bdDiv += '<div class="buttonOverV" onClick="showWinBtnSty()" >';
		bdDiv += '<div id="buttonOverCss" class="buttonOverCss" ></div>';
		bdDiv += '<img class="buttonOverVico" src="img/btnblue.png" />';
		bdDiv += '</div>&nbsp;&nbsp;';

		if (typeof oelBtnSelectColors === 'function') {
			bdDiv += oelBtnSelectColors();
		}
		
		bdDiv += '<input id="colorButton6" type="text" value="" ';
		bdDiv += ' style="float:left;margin-left:5px;width:20px;font-size:12px;padding:5px;display:none;" />';

		bdDiv += '<a id="showButtonLink" onClick="$(\'#infosButtonLink\').show();" ';
		bdDiv += ' style="float:left;width:25px;font-size:12px;padding:5px;cursor:pointer;" >';
		bdDiv += getISVG('access',0) + '</a>';

		bdDiv += '<input id="infosButtonLink" type="text" value="" ';
		bdDiv += ' style="display:none;margin-left:5px;width:210px;font-size:12px;padding:5px;float:left;" />';

		bdDiv += '</div>';
		// div 3

		bdDiv += '<div style="position:relative;padding:5px;margin:5px;border:dotted 1px #353739;" >';
		
		bdDiv += '<div style="padding:5px;padding-top:1px;margin:0px;margin-bottom:5px;height:50px;" >';
		bdDiv += oelActionsBarBtn();
		bdDiv += '</div>';

		bdDiv += '<div id="editEditorFrameBtn" style="padding:0px;margin-left:68px;display:none;" >';
		bdDiv += '<p>iframe</p>';
		bdDiv += '</div>';

		bdDiv += '<div id="editEditorPagesLink" style="padding:0px;margin-left:-10px;margin-top:10px;display:none;" >';
		bdDiv += '<p>List of pages</p>';
		bdDiv += '</div>';

		bdDiv += '<div id="editEditorFrameLink" style="padding:0px;margin-left:-10px;margin-top:10px;display:none;" >';
		bdDiv += '<p>links</p>';
		bdDiv += '</div>';

		bdDiv += '<div id="editEditorDownloadLink" style="padding:0px;margin-left:-10px;margin-top:10px;display:none;" >';
		bdDiv += '<p>download</p>';
		bdDiv += '</div>';

		bdDiv += '<div style="padding:25px;padding-top:10px;padding-bottom:5px;text-align:right;" >';
		bdDiv += '<input id="saveBtnBoutonStyle" onClick="saveBtnEditWindows()" ';
		bdDiv += ' class="gjs-one-bg ludiButtonSave trd" type="button" value="Save" /><br/>';
		bdDiv += '</div>';
		
		bdDiv += '</div>';

		bdDiv += '</div>';

		bdDiv += oelBtnStyles();

		if (typeof oelBtnColors === 'function') {
			bdDiv += oelBtnColors();
		}

		bdDiv += '<div class="gjs-mdl-collector" style="display:none"></div>';
		bdDiv += '</div>';

		$('body').append(bdDiv);

	}

	if ($("#BtnEditWindows").length==1) {
		
		var hObj = tmpObjDom.html();

		$('#editEditorFrameLink').html(oeLinksShow(datatext4));
		$('#editEditorDownloadLink').html(oeLinksDownload(datatext4));
		$('#editEditorPagesLink').html(oePagesShow(datatext4));

		var haveBtnSty = false;

		//line 1
		if(hObj.indexOf("btnteachblue")!=-1) {
			$('#inputButtonStyle').val(1);
			virtualObjStyleNum = 1;
			haveBtnSty = true;
		}
		if(hObj.indexOf("btnteachgreen")!=-1) {
			$('#inputButtonStyle').val(2);
			virtualObjStyleNum = 2;
			haveBtnSty = true;
		}
		if(hObj.indexOf("btnroundblue")!=-1) {
			$('#inputButtonStyle').val(3);
			virtualObjStyleNum = 3;
			haveBtnSty = true;
		}
		if(hObj.indexOf("btnroundblueprev")!=-1) {
			$('#inputButtonStyle').val(4);
			virtualObjStyleNum = 4;
			haveBtnSty = true;
		}

		//line 2
		if (hObj.indexOf("btnroundbluecheck")!=-1) {
			$('#inputButtonStyle').val(5);
			virtualObjStyleNum = 5;
			haveBtnSty = true;
		}
		if (hObj.indexOf("btnicondownload")!=-1) {
			$('#inputButtonStyle').val(6);
			virtualObjStyleNum = 6;
			haveBtnSty = true;
		}
		if (hObj.indexOf("btniconhelp")!=-1) {
			$('#inputButtonStyle').val(7);
			virtualObjStyleNum = 7;
			haveBtnSty = true;
		}
		if (hObj.indexOf("btniconattach")!=-1) {
			$('#inputButtonStyle').val(8);
			virtualObjStyleNum = 8;
			haveBtnSty = true;
		}

		//line 3
		if (hObj.indexOf("btniconhome")!=-1) {
			$('#inputButtonStyle').val(9);
			virtualObjStyleNum = 9;
			haveBtnSty = true;
		}
		if (hObj.indexOf("btnicondialog")!=-1) {
			$('#inputButtonStyle').val(10);
			virtualObjStyleNum = 10;
			haveBtnSty = true;
		}

		if (haveBtnSty==false) {
			$('#inputButtonStyle').val(1);
			virtualObjStyleNum = 1;
		}

		//Style Button
		var getTextContent = btnObj.find('a').html();
		
		var datatext3 = btnObj.find('a').attr("datatext3");
		var datatext4 = btnObj.find('a').attr("datatext4");
		var datatext5 = btnObj.find('a').attr("datatext5");

		var datatext6 = btnObj.find('a').attr("datatext6");
		if (typeof datatext6===undefined) {datatext6 = '';}
		if(datatext6==undefined||datatext6==''||datatext6=='undefined'){
			var ObjAhref = btnObj.find('a');
			datatext6 = ObjAhref.parent().find('span.datatext6').html();
		}
		if (typeof datatext6===undefined) {datatext6 = '';}

		$('#colorButton6').val(datatext6);

		var iv = $('#inputButtonStyle').val();
		applyBtnSty(iv);

		var datatitle = btnObj.find('a').attr("title");
		if (datatitle===undefined) {datatitle = getTextContent;}

		if (datatitle=='') {
			$('#infosButtonLink').hide();
		} else {
			if (btnOpenAcessTxt==true) {
				$('#infosButtonLink').show();
			}
		}
		
		$('#infosButtonLink').val(datatitle);

		$('#behaviorBtn0').attr('checked',false);
		$('#behaviorBtn1').attr('checked',false);
		$('#behaviorBtn2').attr('checked',false);
		$('#behaviorBtn3').attr('checked',false);
		$('#behaviorBtn4').attr('checked',false);
		$('#behaviorBtn5').attr('checked',false);

		if (datatext3===undefined) {datatext3 = '';}
		if (datatext4===undefined) {datatext4 = '';}
		if (datatext5===undefined) {datatext5 = '';}

		if (datatext3==''||datatext4==''||datatext5=='') {
			var btnObjAhref = btnObj.find('a');
			datatext3 = btnObjAhref.parent().find('span.datatext3').html();
			datatext4 = btnObjAhref.parent().find('span.datatext4').html();
			datatext5 = btnObjAhref.parent().find('span.datatext5').html();
		}

		if (datatext3===undefined) {datatext3 = '';}
		if (datatext4===undefined) {datatext4 = '';}
		if (datatext5===undefined) {datatext5 = '';}
		
		if (datatext3==''||datatext4==''||datatext5=='') {
			datatext3 = 'act3|';
			datatext4 = 'LUDI.nextPage();';
			datatext5 = '|';
		}

		if (datatext3=="act3|"||datatext3=='') {
			$('#behaviorBtn0').attr('checked',true);
		} else {
			if (datatext3=="act5|") {
				$('#behaviorBtn1').attr('checked',true);
			} else {
				if (datatext3=="link|") {
					$('#behaviorBtn2').attr('checked',true);
					if (datatext4.indexOf("url@")!=-1) {
						var linkW = datatext4.replace('url@','');
						linkW = linkW.replace('dow@','');
						$('#inputWebLink').val(linkW);
					}
				} else {
					if (datatext3=="download|") {
						var linkW = datatext4.replace('dow@','');
						linkW = linkW.replace('url@','');
						$('#inputDonwloadLink').val(linkW);
						$('#behaviorBtn4').attr('checked',true);
					} else {
						if (datatext3=="page|") {
							var linkW = datatext4.replace('page@','');
							linkW = linkW.replace('page@','');
							$('#inputPageNumberLink').val(linkW);
							$('#behaviorBtn5').attr('checked',true);
						} else {
							$('#behaviorBtn3').attr('checked',true);
						}
					}
				}
			}
		}

		VirtualObjectOel = objetVirtualBtnFromContext(identchange,getTextContent,datatext3,datatext4,datatext5)

		var renderNoCodeEditor = oeEditorShow(VirtualObjectOel);

		$('#editEditorFrameBtn').html(renderNoCodeEditor);

		$('#inputButtonLink').val(getTextContent);
		$('.ludimenu').css("z-index",'2');
		$('#BtnEditWindows').css("display",'');
		windowEditorIsOpen = true;
		loadaFunction();
		ctrEditionNoCode()
		
		$('#saveBtnBoutonStyle').css("display","inline-block");
		$('.oel-btn-collection').css('display','none');

		traductAll();

	}

}

function oelActionsBarBtn() {

	var ctd20 = '<td style="width:16%;user-select:none;text-align:center;';
	ctd20 += 'border:dotted 1px #353739;padding:5px;margin:0px;" >';

	var h = '';

	h += '<table style="width:100%;border-spacing:0px;" >';
	h += '<tr style="padding:0px;margin:0px;" >';
	
	h += ctd20;
	h += '<input onChange="ctrEditionNoCode()" type="radio" class=checkBehaviorWind id="behaviorBtn0" name="behaviorBtn" ></input>';
	h += '<label style="cursor:pointer;" class="trd" for="behaviorBtn0">&nbsp;<span class="trd" >Next&nbsp;page</span></label>&nbsp;</td>';

	h += ctd20;
	h += '<input onChange="ctrEditionNoCode()" type="radio" class=checkBehaviorWind id="behaviorBtn1" name="behaviorBtn" ></input>';
	h += '<label style="cursor:pointer;" class="trd" for="behaviorBtn1">&nbsp;<span class="trd" >Prev&nbsp;page</span></label>&nbsp;</td>';

	h += ctd20;
	h += '<input onChange="ctrEditionNoCode()" type="radio" class=checkBehaviorWind id="behaviorBtn5" name="behaviorBtn" ></input>';
	h += '<label style="cursor:pointer;" class="trd" for="behaviorBtn5">&nbsp;<span class="trd" >Page&nbsp;N</span></label>&nbsp;</td>';
	
	h += ctd20;
	h += '<input onChange="ctrEditionNoCode()" type="radio" class=checkBehaviorWind id="behaviorBtn2" name="behaviorBtn" ></input>';
	h += '<label style="cursor:pointer;" class="trd" for="behaviorBtn2">&nbsp;<span class="trd" >Link</span></label>&nbsp;</td>';

	h += ctd20;
	h += '<input onChange="ctrEditionNoCode()" type="radio" class=checkBehaviorWind id="behaviorBtn4" name="behaviorBtn" ></input>';
	h += '<label style="cursor:pointer;" class="trd" for="behaviorBtn4">&nbsp;<span class="trd" >Download</span></label>&nbsp;</td>';

	h += ctd20.replace('16%','20%');
	h += '<input onChange="ctrEditionNoCode()" type="radio" class=checkBehaviorWind id="behaviorBtn3" name="behaviorBtn" ></input>';
	h += '<label style="cursor:pointer;" class="trd" for="behaviorBtn3">&nbsp;no-code&nbsp;editor</label>&nbsp;</td>';
	h += '</tr></table>';

	return h;

}

function oelBtnStyles() {

	var bdDiv = '';
	bdDiv = '<div class="oel-btn-collection" >';
	
	bdDiv += '<div class="oel-btn-title" >';
	bdDiv += '<span class="trad" >Choose a style<span>';
	bdDiv += '<img onClick="hideWinBtnSty();" style="float:right;cursor:pointer;" src="img/crosstrans.png" />';
	bdDiv += '</div>';

	bdDiv += '<div class="oel-btn-block" onClick="applyBtnSty(1);" >';
	bdDiv += '<div class="buttonOverCss" style="left:5px;right:5px;top:20px;bottom:20px;';
	bdDiv += 'border-radius:5px;background-color:' + virtualObjColor1 + ';" ></div>';
	bdDiv += '</div>';

	bdDiv += '<div class="oel-btn-block" onClick="applyBtnSty(2);" >';
	bdDiv += '<div class="buttonOverCss" style="left:5px;right:5px;top:20px;bottom:20px;';
	bdDiv += 'border-radius:5px;background-color:' + virtualObjColor2 + ';" ></div>';
	bdDiv += '</div>';

	bdDiv += '<div class="oel-btn-block" onClick="applyBtnSty(3);" >';
	bdDiv += '<img id="styleBtn3" class="imgStyleBtn oel-btn-block-img" src="img/btnroundblue.png" />';
	bdDiv += '</div>';

	bdDiv += '<div class="oel-btn-block" onClick="applyBtnSty(4);" >';
	bdDiv += '<img id="styleBtn4" class="imgStyleBtn oel-btn-block-img" src="img/btnroundblueprev.png" />';
	bdDiv += '</div>';

	bdDiv += '<div class="oel-btn-block" onClick="applyBtnSty(5);" >';
	bdDiv += '<img id="styleBtn5" class="imgStyleBtn oel-btn-block-img" src="img/btnroundbluecheck.png" />';
	bdDiv += '</div>';
	
	bdDiv += '<div class="oel-btn-block" onClick="applyBtnSty(6);" >';
	bdDiv += '<img id="styleBtn6" class="imgStyleBtn oel-btn-block-img" src="img/btniconnone.png" />';
	bdDiv += '<img class="imgStyleIconOv"  src="img/btn/mat-down.png" />';
	bdDiv += '</div>';

	bdDiv += '<div class="oel-btn-block" onClick="applyBtnSty(7);" >';
	bdDiv += '<img id="styleBtn6" class="imgStyleBtn oel-btn-block-img" src="img/btniconnone.png" />';
	bdDiv += '<img class="imgStyleIconOv"  src="img/btn/mat-help.png" />';
	bdDiv += '</div>';

	bdDiv += '<div class="oel-btn-block" onClick="applyBtnSty(8);" >';
	bdDiv += '<img id="styleBtn6" class="imgStyleBtn oel-btn-block-img" src="img/btniconnone.png" />';
	bdDiv += '<img class="imgStyleIconOv"  src="img/btn/mat-attach.png" />';
	bdDiv += '</div>';

	bdDiv += '<div class="oel-btn-block" onClick="applyBtnSty(9);" >';
	bdDiv += '<img id="styleBtn6" class="imgStyleBtn oel-btn-block-img" src="img/btniconnone.png" />';
	bdDiv += '<img class="imgStyleIconOv"  src="img/btn/mat-home.png" />';
	bdDiv += '</div>';

	bdDiv += '<div class="oel-btn-block" onClick="applyBtnSty(10);" >';
	bdDiv += '<img id="styleBtn6" class="imgStyleBtn oel-btn-block-img" src="img/btniconnone.png" />';
	bdDiv += '<img class="imgStyleIconOv"  src="img/btn/mat-dialog.png" />';
	bdDiv += '</div>';

	bdDiv += '</div>';
	return bdDiv;

}

function getBtnStyles(i) {

	var img = '';
	if (i==1) {
		img = 'img/btnblue.png';
	}
	if (i==2) {
		img = 'img/btngreen.png';
	}
	if (i==3) {
		img = 'img/btnroundblue.png';
	}
	if (i==4) {
		img = 'img/btnroundblueprev.png';
	}
	if (i==5) {
		img = 'img/btnroundbluecheck.png';
	}
	if (i==6||i==7||i==8||i==9||i==10) {
		img = 'img/btniconnone.png';
	}

	return img;
}

function getBtnIcon(i) {

	var img = '';
	if (i==3) {
		img = 'img/btn/mat-right.png';
	}
	if (i==4) {
		img = 'img/btn/mat-left.png';
	}
	if (i==5) {
		img = 'img/btn/mat-check.png';
	}
	if (i==6) {
		img = 'img/btn/mat-down.png';
	}
	if (i==7) {
		img = 'img/btn/mat-help.png';
	}
	if (i==8) {
		img = 'img/btn/mat-attach.png';
	}
	if (i==9) {
		img = 'img/btn/mat-home.png';
	}
	if (i==10) {
		img = 'img/btn/mat-dialog.png';
	}

	return img;
}

function applyBtnSty(i) {
	
	virtualObjStyleNum = i;
	
	$('#inputButtonStyle').val(i);
	$('.buttonOverVimg').attr('src',getBtnStyles(i));
	
	var ico = getBtnIcon(i);
	if (ico!='') {
		$('.buttonOverVico').attr('src',ico);
		$('.buttonOverVico').css('display','block');
	} else {
		$('.buttonOverVico').css('display','none');
	}
	
	$('.oel-btn-collection').css('display','none');
	$('.oel-btn-color').css('display','none');

	if (i==1||i==2) {

		$('.buttonOverVimg').css('display','none');

		document.getElementById("buttonOverCss").style.inset = "0px 0px 0px 0px";

		$('#buttonOverCss').css('display','block');
		$('#buttonOverCss').css('position','absolute');
		$('#buttonOverCss').css('left','1px').css('top','8px');
		$('#buttonOverCss').css('width','28px').css('height','17px');
		$('#buttonOverCss').css('border-radius','5px');

		$('#buttonOverCss').removeClass().addClass('buttonOverCss');
		
		var colBtn = $('#colorButton6').val();
		
		if (colBtn!=0) {
			$('#buttonOverCss').addClass('globcolor'+colBtn);
		} else {
			if (i==1) {
				$('#buttonOverCss').css('background-color',virtualObjColor1);
			}
			if (i==2) {
				$('#buttonOverCss').css('background-color',virtualObjColor2);
			}
		}

	}

	if (i>2&&i<11) {

		$('#buttonOverCss').removeClass().addClass('buttonOverCss');
		
		var colBtn = $('#colorButton6').val();
		
		if (colBtn!=0) {
			$('#buttonOverCss').addClass('globcolor'+colBtn);
		} else {
			$('#buttonOverCss').css('background-color',virtualObjColor1);
		}

		$('.buttonOverVimg').css('display','none');

		$('#buttonOverCss').css('display','block');
		$('#buttonOverCss').css('left','0px').css('top','0px');
		$('#buttonOverCss').css('width','31px').css('height','31px');
		$('#buttonOverCss').css('border-radius','50%');

	}

}

function showWinBtnSty(){
	$('.oel-btn-collection').css('display','block');
	$('.oel-btn-color').css('display','none');
}

function hideWinBtnSty(){
	$('.oel-btn-collection').css('display','none');
}

function oeEditorShow(VObjectOel){
    
    var wbpath = 'plug/editor-action/forms/index.html?v='+randomoeEditor();

	var p = ''
	p += '<iframe data="' + objetSendToString(VObjectOel) + '" scrolling=no ';
	p += ' id="editEditorFrame" name="editEditorFrame" ';
	p += ' src="' + wbpath + '" width="650px" height="440px" ';
	p += ' frameBorder="0" >';
	p += '</iframe>';
	
  	return p;
	
}

function oeLinksShow(datatext4){
    
	var p = ''

	p += '<div style="padding:5px;width:120px;float:left;text-align:right;" >';
	p += 'Link&nbsp;:&nbsp;</div>';
	p += '<input id="inputWebLink" type="text" value="" ';
	p += ' style="width:400px;font-size:12px;padding:5px;float:left;" />';

  	return p;
	
}

function oeLinksDownload(datatext4){
    
	var p = ''
	p += '<div style="padding:5px;width:120px;float:left;text-align:right;" >';
	p += 'File&nbsp;:&nbsp;</div>';
	p += '<input id="inputDonwloadLink" readonly="readonly" type="text" value="" ';
	p += ' style="background:#D5D8DC;width:450px;font-size:12px;padding:5px;float:left;" />';
	p += '&nbsp;<input onClick="filterGlobalFiles=\'\';oeApplyDownloadTxt();showFileManagerStudio2(23,\'inputDonwloadLink\',0);" ';
	p += ' class="gjs-one-bg ludiButtonSave plugInputMin trd" type="button" value="..." />';
	
  	return p;
	
}

function oePagesShow(datatext4){

	var h = '';
	h += '<div style="padding:5px;width:120px;float:left;text-align:right;font-size:17px;" >';
	h += '<span class="trd" >Page</span>&nbsp;:&nbsp;</div>';

    var ct  = $('.ludimenuteachdoc').find('li').length;

    if (ct>0) {
        h += '<select id="inputPageNumberLink" style="font-size:17px;padding:4px;" >';
        
		$('.ludimenuteachdoc li').each(function(index) {
            
			var itemLi = $(this);
			var typenode = parseInt(itemLi.attr('typenode'));
            var text = itemLi.text();
            if (text!=''&&typenode!=3) {
                h += '<option value="' + parseInt(index+1) + '" >' + text + '</option>';
            }
        
		});
        
		h += '</select>';
    
	}

	return h;

}

function oeApplyDownloadTxt(){

	if ($('#inputButtonLink').val()=='Check the answers') {
		if (langselectUI=='fr_FR') {
			$('#inputButtonLink').val('Télécharger');
			$('#infosButtonLink').val('Télécharger');
		} else {
			$('#inputButtonLink').val('Download');
			$('#infosButtonLink').val('Download');
		}
	}

}

function randomoeEditor() {
	return Math.floor((1 + Math.random()) * 0x10000)
		.toString(16)
		.substring(1);
}

function ctrEditionNoCode() {

	if ($('#behaviorBtn3').is(':checked')) {
		
		$('#editEditorFrameBtn').css("display",'');
		$('#editEditorFrameBtn').css("height",'20px');

		$( "#editEditorFrameBtn" ).animate({
			height: "440px"
		},500,function(){});

		$('#editEditorFrameLink').css("display",'none');
		$('#editEditorDownloadLink').css("display",'none');
		$('#editEditorPagesLink').css("display",'none');

	} else {

		if ($('#behaviorBtn4').is(':checked')) {

			$('#editEditorFrameLink').css("display",'none');
			$('#editEditorPagesLink').css("display",'none');
			$('#editEditorDownloadLink').css("display",'');
			$('#editEditorDownloadLink').css("height",'20px');

			$( "#editEditorDownloadLink" ).animate({
				height: "100px"
			},500,function(){});

			$( "#editEditorFrameBtn" ).animate({
				height: "20px"
			},400,function(){
				$('#editEditorFrameBtn').css("display",'none');
			});

		} else {

			if ($('#behaviorBtn2').is(':checked')) {
				
				$('#editEditorDownloadLink').css("display",'none');
				$('#editEditorFrameLink').css("display",'');
				$('#editEditorFrameLink').css("height",'20px');

				$('#editEditorPagesLink').css("display",'none');

				$( "#editEditorFrameLink" ).animate({
					height: "100px"
				},500,function(){});

				$( "#editEditorFrameBtn" ).animate({
					height: "20px"
				},400,function(){
					$('#editEditorFrameBtn').css("display",'none');
				});

			} else {

				if ($('#behaviorBtn5').is(':checked')) {
					
					$('#editEditorDownloadLink').css("display",'none');
					$('#editEditorFrameLink').css("display",'none');
					$('#editEditorPagesLink').css("display",'');

				} else {

					$('#editEditorDownloadLink').css("display",'none');
					$('#editEditorFrameLink').css("display",'none');
					$('#editEditorPagesLink').css("display",'none');

					$( "#editEditorFrameBtn" ).animate({
						height: "20px"
					},400,function(){
						$('#editEditorFrameBtn').css("display",'none');
					});

				}

			}

		}
	}

}

function saveBtnEditWindows() {

	if (onlyOneUpdate==false) {
		return false;
	}
	
	var colBtn = $('#colorButton6').val();
	
	$('#saveBtnBoutonStyle').css("display","none");

	var TButtonText = $('#inputButtonLink').val();

	var Tobj = validEditorObject()
	
	var clueSrc = TButtonText;

	if(TButtonText==''){
		TButtonText = '?';
	}

	if($('#behaviorBtn0').is(':checked')){
		Tobj.text3 = "act3|";
		Tobj.text4 = "LUDI.nextPage();";
		Tobj.text5 = "|";
	}
	if($('#behaviorBtn1').is(':checked')){
		Tobj.text3 = "act5|";
		Tobj.text4 = "LUDI.prevPage();";
		Tobj.text5 = "|";
	}
	if($('#behaviorBtn2').is(':checked')){
		Tobj.text3 = "link|";
		Tobj.text4 = 'url@'+$('#inputWebLink').val();
		Tobj.text5 = "|";
		clueSrc = $('#inputWebLink').val();
	}
	if($('#behaviorBtn4').is(':checked')){
		Tobj.text3 = "download|";
		Tobj.text4 = 'dow@'+$('#inputDonwloadLink').val();
		Tobj.text5 = "|";
		clueSrc = $('#inputDonwloadLink').val();
	}
	if($('#behaviorBtn5').is(':checked')){
		Tobj.text3 = "page|";
		Tobj.text4 = 'page@'+$('#inputPageNumberLink').val();
		Tobj.text5 = "|";
		clueSrc = $('#inputDonwloadLink').val();
	}

	var renderH = "<tr>";
	renderH += '<td style="text-align:center;padding:10px;width:100%;" >';
	
	var iv = $('#inputButtonStyle').val();

	if (colBtn=='') { colBtn = 0; }
	colBtn = parseInt(colBtn);

	Tobj.text6 = colBtn;

	var colClass = '';
	if(colBtn!=0){
		colClass = 'globcolor'+colBtn;
	}

	if(iv==1){
		renderH += '<a href="" class="btn-btnTeach btnteachblue '+colClass+'" ';
	}
	if(iv==2){
		renderH += '<a href="" class="btn-btnTeach btnteachgreen '+colClass+'" ';
	}
	if(iv==3){
		renderH += '<a href="" class="btn-btnTeach btnroundblue '+colClass+'" ';
	}
	if(iv==4){
		renderH += '<a href="" class="btn-btnTeach btnroundblueprev '+colClass+'" ';
	}
	if(iv==5){
		renderH += '<a href="" class="btn-btnTeach btnroundbluecheck '+colClass+'" ';
	}
	if(iv==6){
		renderH += '<a href="" class="btn-btnTeach btnroundblue btnicondownload '+colClass+'" ';
	}
	if(iv==7){
		renderH += '<a href="" class="btn-btnTeach btnroundblue btniconhelp '+colClass+'" ';
	}
	if(iv==8){
		renderH += '<a href="" class="btn-btnTeach btnroundblue btniconattach '+colClass+'" ';
	}
	if(iv==9){
		renderH += '<a href="" class="btn-btnTeach btnroundblue btniconhome '+colClass+'" ';
	}
	if(iv==10){
		renderH += '<a href="" class="btn-btnTeach btnroundblue btnicondialog '+colClass+'" ';
	}
	
	renderH += ' datatext3 = "' + Tobj.text3 + '" ';
	renderH += ' datatext4 = "' + Tobj.text4 + '" ';
	renderH += ' datatext5 = "' + Tobj.text5 + '" ';
	renderH += ' datatext6 = "' + Tobj.text6 + '" ';

	renderH += ' title="' + cleTextTitle($('#infosButtonLink').val()) + '" ';

	renderH += 'name="submit" type="button" >';
	renderH += TButtonText + '</a>';

	renderH += '<span class=datatext3 style="display:none;" >' + Tobj.text3 + '</span>';
	renderH += '<span class=datatext4 style="display:none;" >' + Tobj.text4 + '</span>';
	renderH += '<span class=datatext5 style="display:none;" >' + Tobj.text5 + '</span>';
	renderH += '<span class=datatext6 style="display:none;" >' + Tobj.text6 + '</span>';

	renderH += "</td></tr>";

	if(GlobalTagGrappeObj=='div'){
		var rdrFull = '<table onMouseDown="parent.displayEditButon(this);" ';
		rdrFull += ' class="teachdocbtnteach" style="width:100%;">';
		rdrFull += renderH + '</table>';
		renderH = rdrFull;
	}
	
	setAbstractObjContent(renderH);
	
	//setAbstractObjAttribute('datatext3',Tobj.text3);

	$('.ui-widget-overlay').css("display","block");
	$('.workingProcessSave').css("display","block");

	if (controlStringInSource(clueSrc)) {
		
		setTimeout(function(){
			saveSourceFrame(false,false,0);
			$('.ui-widget-overlay').css("display","none");
			$('.workingProcessSave').css("display","none");
			$('.ludimenu').css("z-index","1000");
			closeAllEditWindows();
		},200);

	} else {

		setTimeout(function(){
			saveSourceFrame(false,false,0);
			setTimeout(function(){
				if (controlStringInSource(clueSrc)) {
					saveSourceFrame(false,false,0);
				}
				$('.ui-widget-overlay').css("display","none");
				$('.workingProcessSave').css("display","none");
				$('.ludimenu').css("z-index","1000");
				closeAllEditWindows();
			},3000);
		},500);
		
	}

}

function getTitleBar(title) {

	title = returnTradTerm(title);

	var bdDiv = '<div class="gjs-mdl-header" ';
	bdDiv += ' style="background-color:#E6E6E6;" >';
	bdDiv += '<div class="gjs-mdl-title trd">' + title + '</div>';
	bdDiv += '<div class="gjs-mdl-btn-close gjs-mdl-btn-close-audio" ';
	bdDiv += ' onClick="closeAllEditWindows()" ';
	bdDiv += ' data-close-modal="">⨯</div>';
	bdDiv += '</div>';

	return bdDiv;

}

function objetVirtualBtnFromContext(idBtn,textBtn,datatext3,datatext4,datatext5) {

	var Tobj = new Object();
	Tobj.id = idBtn;
	Tobj.idFab = idBtn;
	Tobj.unikid = GlobalIDGrappesObj;
	Tobj.idString = GlobalIDGrappesObj;
	Tobj.type = 'teachdocbtnteach';
	Tobj.subtype = 'teachdocbtnteach';
	Tobj.text = textBtn;
	Tobj.text2 = '';
	Tobj.text3 = datatext3;
	Tobj.text4 = datatext4;
	Tobj.text5 = datatext5;
	Tobj.text6 = '';
	Tobj.val = '';
	Tobj.val2 = '';
	Tobj.val3 = '';
	Tobj.val4 = '';
	Tobj.val5 = '';
	Tobj.val6 = '';
	return Tobj;

}

function objetSendToString(Tobj) {
    
    var str = "";
	str += Tobj.id + '@';
	str += Tobj.idFab + '@';
	str += Tobj.unikid + '@';
    str += Tobj.idString + '@';
    str += Tobj.type + '@';
	str += Tobj.subtype + '@';
    str += Tobj.text + '@';
    str += Tobj.text2 + '@';
    str += Tobj.text3 + '@';
    str += Tobj.text4 + '@';
    str += Tobj.text5 + '@';
    str += Tobj.text6 + '@';
    str += Tobj.val + '@';
    str += Tobj.val2 + '@';
    str += Tobj.val3 + '@';
    str += Tobj.val4 + '@';
    str += Tobj.val5 + '@';
    str += Tobj.val6 + '@';
	return str;
}

function validEditorObject() {

	var Tobj = new Object();

	transfertTextPlugins = $('#editEditorFrame').contents().find('#finalcode').val();
	
	if(transfertTextPlugins==''){
	
		alert('Failure of registration');
	
	}else{

		var getObjD = transfertTextPlugins.split("@");

		Tobj.id = getObjD[0];
		Tobj.idFab = getObjD[1];
		Tobj.unikid = getObjD[2];
		Tobj.idString = getObjD[3];
		Tobj.type = getObjD[4];
		Tobj.subtype = getObjD[5];
		Tobj.text = getObjD[6];
		Tobj.text2 = getObjD[7];
		Tobj.text3 = getObjD[8];
		Tobj.text4 = getObjD[9];
		Tobj.text5 = getObjD[10];
		Tobj.text6 = getObjD[11];
		Tobj.val = getObjD[12];
		Tobj.val2 = getObjD[13];
		Tobj.val3 = getObjD[14];
		Tobj.val4 = getObjD[15];
		Tobj.val5 = getObjD[16];
		Tobj.val6 = getObjD[17];

	}

	return Tobj;
	
}

var MEMTagCol = '';
var MEMcontent = '';
var MEMCreateCols = '';
var MEMlogsCrea;

detectTraductAll();

var pathcustomcode = $('#filcustomcode').html();

var editor = grapesjs.init({
	height: '100%',
	showOffsets: 1,
	noticeOnUnload: 0,
	storageManager: { autoload: 0 },
	container: '#gjs',
	fromElement: true,
	plugins: ['gjs-preset-webpage'],
	pluginsOpts: {
		'gjs-preset-webpage': {}
	},
	canvas: {
		styles: [
			'templates/styles/classic.css',
			'templates/colors/' + colorsPath,
			'templates/quizztheme/' + quizzthemePath,
			'templates/styles/plug.css?v=1',
			'templates/styles/base-title.css',
			pathcustomcode
		]
	}
});

/*,
keymaps: {
	defaults: {
		'your-namespace:keymap-name' {
		keys: '⌘+s, ctrl+s',
		handler: 'some-command-id'
		}
	}
}

"<video class="videoByLudi"  datahref="video/oel-teachdoc.mp4"  controls  controlsList="nofullscreen nodownload" ><source name="sourcevid" class="sourcevid"  src="video/oel-teachdoc.mp4"  type="video/mp4" ></video>"

*/
editor.on('component:create', function (createComponent) {
	
	if (optionsCS.indexOf('ALC')!=-1) {
		MEMTagCol = createComponent.get('tagName') ;
		var ObjTitle = createComponent.get('name') ;
		MEMCreateCols = MEMCreateCols + ObjTitle;
		MEMcontent = MEMcontent + createComponent.get('content') ;
		MEMlogsCrea = setTimeout(function(){
			addToAlcLogs('create')
		},300);
	}

});
editor.on('component:remove', function (removeComponent) {

	if (optionsCS.indexOf('ALC')!=-1) {
		MEMTagCol = removeComponent.get('tagName') ;
		var ObjTitle = removeComponent.get('name') ;
		MEMCreateCols = MEMCreateCols + ObjTitle;
		MEMcontent = MEMcontent + removeComponent.get('content') ;
		MEMlogsCrea = setTimeout(function(){
			addToAlcLogs('remove')
		},300);
	}

});

function addToAlcLogs(action){
	var idObj = MEMTagCol + MEMCreateCols;
	if (idObj!='') {
		if (idObj=='divCellCellRow') { idObj = '2columns'; }
		if (idObj=='divCellRow') { idObj = '1column'; }

		if (MEMcontent.indexOf('videoByLudi')!=-1
		||MEMcontent.indexOf('<video ')!=-1
		||MEMTagCol=='video') { 
			idObj = 'video';
		}
		if (MEMcontent.indexOf('audioByLudi')!=-1
		||MEMcontent.indexOf('<audio ')!=-1
		||MEMTagCol=='audio') { 
			idObj = 'audio';
		}
		var hveName = false;
		if (MEMcontent.indexOf('separatorteach')!=-1) { 
			idObj = 'separator';
			hveName = true;
		}
		if (MEMcontent.indexOf('teachdocbtnteach')!=-1) { 
			idObj = 'button';
			hveName = true;
		}
		if (MEMcontent.indexOf('plugteachcontain')!=-1
		||MEMcontent.indexOf('plugcard-front')!=-1) { 
			idObj = 'card';
			hveName = true;
		}
		if (MEMcontent.indexOf('teachdoctext')!=-1) { 
			idObj = 'textbloc';
			hveName = true;
		}
		if (MEMcontent.indexOf('qcmbarre')!=-1) { 
			idObj = 'quiz';
			hveName = true;
		}
		if (MEMcontent.indexOf('>blank<')!=-1) { 
			idObj = 'dragdroph5p';
			hveName = true;
		}
		if (MEMcontent.indexOf('>minidia<')!=-1) { 
			idObj = 'minidia';
			hveName = true;
		}
		if (MEMcontent.indexOf('plugimageactive')!=-1) { 
			idObj = 'imgactive';
			hveName = true;
		}
		if (MEMcontent.indexOf('sectioncollapse')!=-1) { 
			idObj = 'collapse';
			hveName = true;
		}
		if (MEMcontent.indexOf('plugteachcontain')!=-1&&hveName==false) { 
			idObj = 'fxobject';
		}
		alcLogs = alcLogs + action + '-' + idObj + ' ';
		MEMTagCol = '';
		MEMCreateCols = '';
		MEMcontent = '';
	}
}

// components: GpsCompsPage,
editor.on('component:selected', function (droppedComponent) {

	var idTrait = droppedComponent.get('traits')['id'];

	if(typeof idTrait == 'undefined'){
		idTrait = getUnikIdGrappesObj();
		droppedComponent.get('traits')['id'] = idTrait;
	}
	
	var idUnik = droppedComponent.get('tagName') + idTrait;

	if (GlobalIDGrappesObj!=idUnik) {
		hookSelectAnObject();
	}

	GlobalIDGrappesObj = idUnik;
	GlobalTagGrappeObj = droppedComponent.get('tagName') ;

	//console.log('idUnik',idUnik);
	
	//Display classic menu on left
	displayToolsCarre();
	switchToolsEdit();
	
});

editor.on('component:drag:end', function (component) {
	activeEventSave();
	const el = component.getEl();
	const hasChildren = component.components().length;
});

editor.on('block:drag:stop', function (droppedComponent) {
	
	activeEventSave();
	if(droppedComponent){
		if(droppedComponent.attributes){
			if(droppedComponent.attributes.tagName){
				
				if(droppedComponent.attributes.tagName=="img"){
					droppedComponent.addAttributes({
						class: 'bandeImg'
					});
					
				}
				if(droppedComponent.attributes.content){
					if(droppedComponent.attributes.content.indexOf("pluginfx-obj")!=-1){
						moveAFxObj = true;
					}
				}
			}
		}
	}

});

editor.DomComponents.addType('image', {
	model: {
		defaults: {
			resizable: {
				tl: 0, // Top left
				tc: 0, // Top center
				tr: 0, // Top right
				cl: 0, // Center left
				bl: 0, // Bottom left
				br: 0, // Bottom right
				cr: 0, // Center right
				bc: 0 // Bottom Center
			}
		}
	}
})

editor.on('component:toggled',function (droppedComponent){
	activeEventSave();
});

editor.on('run:open-assets',function (droppedComponent){
	$('.ludimenu').css("z-index","2");
	restyleLstImage();
});

editor.on('change:selectedComponent', model => {
	console.log('New content selected', model.get('content'));
});

var dragOpts = ' data-gjs-draggable="false" data-gjs-droppable="false" data-gjs-editable="false" ';
var cssI = " style='position:absolute;cursor:pointer;background-image:url(\"img/editdoc.png\");background-position:center center;background-repeat:no-repeat;right:2px;top:3px;width:50px;height:50px;z-index: 1000;' ";

//var baseButton = '<div class="row" ' + dragOpts + ' style="position:relative;" id="i25td">';
//baseButton += '<div class="cell" ' + dragOpts + ' style="text-align:center;position:relative;" >';

//baseButton += '<div class="editRapidIcon" ' + cssI + ' onClick="parent.displayVideoEdit(this);" ></div>';

var baseButton = '<video onMouseDown="parent.displayEditButon(this);" ';
baseButton += ' oncontextmenu="return false;" class="videoByLudi" ';
baseButton += ' datahref="video/oel-teachdoc.mp4" ';
baseButton += ' controls  controlsList="nofullscreen nodownload" >';
baseButton += '<source name="sourcevid" class="sourcevid" ';
baseButton += ' src="video/oel-teachdoc.mp4" ';
baseButton += ' type="video/mp4" ></video>';

//baseButton += '</div>';
//baseButton += '</div>';

editor.BlockManager.add('VideoTeach',{
	label: '',
	attributes: {class: 'fa fa-text icon-action'},
	category: 'Basic',
	content: {
		content: baseButton,
		script: "",
		style: {
		width: '100%',
		minHeight: '100px',
		droppable: false,
		removable: true,
		draggable: false,
		copyable: false
	}
	}
});

//var baseButtonAudio = '<div class="row" ' + dragOpts + ' style="position:relative;" id="i26td">';
//baseButtonAudio += '<div class="cell" ' + dragOpts + ' style="text-align:center;position:relative;" >';

//baseButtonAudio += '<div class="editRapidIcon" ' + cssI + ' onClick="parent.displayAudioEdit(this);" ></div>';

var baseButtonAudio = '<audio onMouseUp="parent.displayEditButon(this);" oncontextmenu="return false;" class="audioByLudi" ';
baseButtonAudio += ' datahref="audio/teachdoc-sample.mp3" ';
baseButtonAudio += ' src="audio/teachdoc-sample.mp3" ';
baseButtonAudio += ' controls controlsList="nodownload" ></audio>';

//baseButtonAudio += '</div>';
//baseButtonAudio += '</div>';
  
editor.BlockManager.add('AudioTeach',{
	label: 'Audio',
	attributes: {class: 'fa fa-text icon-audio'},
	category: 'Basic',
	content: {
		content: baseButtonAudio,
		script: "",
		style: {
		width: '100%',
		minHeight: '100px'
		}
	}
});

//editor.setStyle(GpsStylePage);
//editor.setComponent(GpsCompsPage);

//if(GpsCompH!=''){
	//editor.load(GpsCompH);
//}

/*
	removable: true, // Can't remove it
	draggable: true, // Can't move it
	copyable: true, // Disable copy/past
*/

function correctPositionsEditor(){
	
	var wrapperChildren = editor.getWrapper();
	var modelComponent = editor.getComponents();
	var wrapperChildren = editor.getWrapper();
	const allBody = wrapperChildren.findType('body');
	
}


// Beta functions
function oelBtnSelectColors() {

    var bdDiv = '<a id="showButtonColors" onClick="showWinBtnCol();" ';
    bdDiv += ' style="float:left;width:25px;font-size:12px;padding:5px;';
    bdDiv += 'padding-top:0px;margin-top:0px;cursor:pointer;" >';
    bdDiv += '<img style="width:30px;height:29px;" src="icon/rapid-style.png" /></a>';
    return bdDiv;

}

function oelBtnColors() {

	var bdDiv = '';
	
	bdDiv = '<div class="oel-btn-color" >';
	
	bdDiv += '<div class="oel-btn-title" >';
	bdDiv += '<span class="trad" >Choose a color</span>';
	bdDiv += '<img onClick="hideWinBtnCol();" style="float:right;cursor:pointer;" src="img/crosstrans.png" />';
	bdDiv += '</div>';

	bdDiv += '<div class="oel-btn-block-color globcolor0" onClick="applyBtnColor(0);" ></div>';
	bdDiv += '<div class="oel-btn-block-color globcolor1" onClick="applyBtnColor(1);" ></div>';
	bdDiv += '<div class="oel-btn-block-color globcolor2" onClick="applyBtnColor(2);" ></div>';
	bdDiv += '<div class="oel-btn-block-color globcolor3" onClick="applyBtnColor(3);" ></div>';
	bdDiv += '<div class="oel-btn-block-color globcolor4" onClick="applyBtnColor(4);" ></div>';
	bdDiv += '<div class="oel-btn-block-color globcolor5" onClick="applyBtnColor(5);" ></div>';
	bdDiv += '<div class="oel-btn-block-color globcolor6" onClick="applyBtnColor(6);" ></div>';

	bdDiv += '<div class="oel-btn-block-color globcolor7" onClick="applyBtnColor(7);" ></div>';
	bdDiv += '<div class="oel-btn-block-color globcolor8" onClick="applyBtnColor(8);" ></div>';
	bdDiv += '<div class="oel-btn-block-color globcolor9" onClick="applyBtnColor(9);" ></div>';
	bdDiv += '<div class="oel-btn-block-color globcolor10" onClick="applyBtnColor(10);" ></div>';
	bdDiv += '<div class="oel-btn-block-color globcolor11" onClick="applyBtnColor(11);" ></div>';
	bdDiv += '<div class="oel-btn-block-color globcolor12" onClick="applyBtnColor(12);" ></div>';
	bdDiv += '<div class="oel-btn-block-color globcolor13" onClick="applyBtnColor(13);" ></div>';

	bdDiv += '<div class="oel-btn-block-color globcolor14" onClick="applyBtnColor(14);" ></div>';


	bdDiv += '</div>';

	return bdDiv;

}

function applyBtnColor(i){
	
	$('#colorButton6').val(i);
	$('.oel-btn-color').css('display','none');
	applyBtnSty(virtualObjStyleNum);

}

function showWinBtnCol(){
	$('.oel-btn-color').css('display','block');
	$('.oel-btn-collection').css('display','none');
}

function hideWinBtnCol(){
	$('.oel-btn-color').css('display','none');
}

editor.BlockManager.add('spaceteach',{
	label: 'Space',
	attributes: {
		class: 'fa fa-text icon-plugTeach',
        style: "background-image: url('icon/space.png');background-repeat:no-repeat;background-position:center center;"
	},
    category: 'Basic',
    content: {
		content: '<div class=spaceteach ></div>',
        script: "",
		style: {
			width: '100%'
		}
	}
});

editor.BlockManager.add('sectionSeparator',{
	label: 'Separator',
	attributes: {
		class: 'fa fa-text icon-plugTeach',
        style: "background-image: url('icon/separator.png');background-repeat:no-repeat;background-position:center center;"
	},
    category: 'Basic',
    content: {
		content: '<div class=separatorteach ></div>',
        script: "",
		style: {
			width: '100%'
		}
	}
});

editor.BlockManager.add('h1block',{
	label: 'Title 1',
	content: '<h1>Put your title here</h1>',
	category: 'Basic',
	attributes: {
		title: 'h1 block',
		class: 'fa fa-text icon-h1'
	}
});

editor.BlockManager.add('h2block',{
	label: 'Title 2',
	content: '<h2>Put your title here</h2>',
	category: 'Basic',
	attributes: {
		title: 'h2 block',
		class: 'fa fa-text icon-h2'
	}
});

var bTxt = '<table class="teachdoctext" ';
bTxt += 'onMouseDown="parent.displayEditButon(this);" style="width:100%;" >';
bTxt += '<tr><td class="teachdoctextContent" colspan=1 style="padding-top:15px;padding-bottom:15px;" >';
bTxt += 'Nec sane haec sola pernicies orientem diversis cladibus adfligebat.';
bTxt += 'Namque et Isauri, quibus est usitatum saepe pacari saepeque inopinis ';
bTxt += 'excursibus cuncta miscere, ex latrociniis occultis et raris, alente inpunitate adulescentem ';
bTxt += 'in peius audaciam ad bella gravia proruperunt, diu quidem perduelles spiritus inrequietis motibus ';
bTxt += 'erigentes, hac tamen indignitate perciti vehementer, ut iactitabant, quod eorum capiti quidam ';
bTxt += 'consortes apud Iconium Pisidiae oppidum in amphitheatrali spectaculo feris praedatricibus obiecti ';
bTxt += 'sunt praeter morem.'
bTxt += '</td></tr>';
bTxt +='</table>';

editor.BlockManager.add('TxtTeach',{
	label: 'Text Bloc',
	attributes: {class: 'fa fa-text icon-txtteach'},
	category: 'Basic',
	content: {
		content: bTxt,
		script: "",
		style: {
		width: '100%',
		minHeight: '100px'
		}
	}
});
var btnSrc = '<table class="teachdocbtnteach" ';
btnSrc += 'onMouseDown="parent.displayEditButon(this);" style="width:100%;text-align:center;" >';
btnSrc += '<tr><td style="text-align:center;padding:10px;width:100%;" >';

btnSrc += '<a href="" class="btn-btnTeach btnteachblue" ';
btnSrc += 'name="submit" type="button"  >';
btnSrc += 'Check the answers</a>';

btnSrc += '</td></tr></table>';

editor.BlockManager.add('btnTeach',{
	label: 'Button',
	attributes: {class: 'fa fa-text icon-btnTeach'},
	category: 'Basic',
	content: {
		content: btnSrc,
		script: "",
		style: {
			width: '100%',
			minHeight: '70px'
		}
	}
});

var componentText ={
    tagName: 'div',
    components: [
      {
        type: 'image',
        attributes: { src: 'https://path/image' },
      }, {
        tagName: 'span',
        type: 'text',
        attributes: { title: 'foo' },
        components: [{
          type: 'textnode',
          content: 'Hello world!!!'
        }]
      }
    ]
  };

// grapeJS
/*
editor.BlockManager.add('buttonbar',{
	label: 'Button Bar',
	attributes: {
		class: 'fa fa-text icon-plugTeach',
        style: "background-image: url('icon/buttonbar.png');background-repeat:no-repeat;background-position:center center;"
	},
    category: 'Basic',
    content: {
        content: '<div data-gjs-type="default" class=buttonblock1 >Element A</div>',
        script: "",
        style: {
            width: '100%'
        },
        activate: true
    }
});
*/

var contentBar = '<div class=buttonbar ><div class=buttonblock1 ></div><div class=buttonblock1 ></div><div class=buttonblock1 ></div></div>';
// contentBar = '<div class=buttonblock1 ></div><div class=buttonblock1 ></div><div class=buttonblock1 ></div>';
// contentBar = '<div class=buttonbar ><div data-gjs-type="default" class=buttonblock1 ></div><div data-gjs-type="default" class=buttonblock1 ></div><div data-gjs-type="default" class=buttonblock1 ></div></div>';

var contentB = [
    { type: 'box', attributes : { class : 'buttonblock1'}},
    { type: 'box', attributes : { class : 'buttonblock1'}},
    { type: 'box', attributes : { class : 'buttonblock1'}}
];

var contentT = {type : 'table',columns : 3,rows : 1};
contentT = {type : 'box',columns : 3,rows : 1};

//,style : {width: '100%'}
editor.BlockManager.add('buttonbar',{
	label : 'Button Bar',
	attributes : {
		class : 'fa fa-text icon-plugTeach',
        style : "background-image: url('icon/buttonbar.png');background-repeat:no-repeat;background-position:center center;"
	},
    category : 'Basic',
    content : contentBar,
    activate : true
	}
);

var GlobalIDGrappesObj = '';
var GlobalTagGrappeObj = '';

function grappesObj(){
	
	this.id;
	this.create;
	this.show = function() {
		if (this.create==0) {
			this.create = 1;
		} else {
			paintObjToGraph(this);
		}
	}
	
}

function setAbstractObjContent(renderH){
    
    var wrapperChildrenTbl = editor.getWrapper();
    const allBody = wrapperChildrenTbl.findType('table');
    
    var  findObject = false;
    
	editor.DomComponents.getWrapper().onAll(comp =>{

        var idTrait = comp.get('traits')['id'];

        if(typeof idTrait != 'undefined'&&idTrait!='undefined'){
           
            var makeAnId = comp.get('tagName') + idTrait;

            if(GlobalIDGrappesObj==makeAnId){
                
                /*
                var view = comp.getView();
                var el = comp.getEl();
                el.innerHTML = renderH;
                */
				findObject = true;
				var compContent = comp.get("content");
                
				if(compContent!=renderH){
					comp.set({selectable: true, hoverable: true, resizable:false, draggable:true});
					comp.components('');
					comp.set("content",renderH);
					changeDataBase[identchange] = renderH;
					comp.updated();
				}
                // var test = comp.toHTML();
                
            }

        }

	});

    if(findObject==false){
        alert('Echec de mise à jour');
    }

	var Elem = new grappesObj()
	Elem.x = 10;
	Elem.y = 10;
	Elem.w = 170;
	Elem.h = 100;
	Elem.uid = getUnikIdGrappesObj();
	Elem.idparent = 0;
	Elem.create = 0;
    
}

function setAbstractObjAttribute(name,value){

	if(value==''){
		return false;
	}
	if(name!='datatext3'){
		return false;
	}

    var wrapperChildrenTbl = editor.getWrapper();
    const allBody = wrapperChildrenTbl.findType('table');
    
    var  findObject = false;
    
	editor.DomComponents.getWrapper().onAll(comp =>{

        var idTrait = comp.get('traits')['id'];

        if(typeof idTrait != 'undefined'&&idTrait!='undefined'){
           
            var makeAnId = comp.get('tagName') + idTrait;
			
            if(GlobalIDGrappesObj==makeAnId){
               
            	findObject = true;
				
				if(name=='datatext3'){

					var datatext3 = comp.get('traits')['datatext3'];
					if(datatext3 === undefined) {
						comp.addTrait(name);
						datatext3 = comp.get('traits')['datatext3'];
					}
					if(datatext3 !== undefined) {
						datatext3.set(value);
					}
				}
            }

        }

	});

    if(findObject==false){
        alert('Echec de mise à jour');
	}
	
}

function setAbstractObjClass(nameClass){
    
    var wrapperChildrenTbl = editor.getWrapper();
    const allBody = wrapperChildrenTbl.findType('table');
    
    var  findObject = false;
    
	editor.DomComponents.getWrapper().onAll(comp =>{

        var idTrait = comp.get('traits')['id'];
		
        if(typeof idTrait != 'undefined'&&idTrait!='undefined'){
           
            var makeAnId = comp.get('tagName') + idTrait;

            if(GlobalIDGrappesObj==makeAnId){
				
				findObject = true;
				var compContent = comp.get("content");
				comp.removeClass('bandeImg');
				comp.removeClass('initialImg');
				comp.removeClass('bandeImgFull');
				comp.removeClass('bandeImgOverview');

				comp.removeClass('titleClassicH1');
				comp.removeClass('titleClassicH1Left');
				comp.removeClass('titleFullH1');
				comp.removeClass('titleIconDocH1');
				comp.removeClass('titleIconGradeH1');
				comp.removeClass('titleiconknowledge');
				comp.removeClass('titleReminderNote');
				comp.removeClass('titletarget');
				comp.removeClass('titleBlackH1');
				comp.removeClass('titleOrangeH1');
				comp.removeClass('titleGreenH1');
				comp.removeClass('titleBlueH1');
				comp.removeClass('titlePurpleH1');
				comp.removeClass('titleRedH1');
				comp.removeClass('titleGoldH1');
				comp.removeClass('titleNavyH1');
				comp.removeClass('titleGrayH1');
				comp.removeClass('titleArrowH1');
				
				comp.removeClass('BoxTxtClean');
				comp.removeClass('BoxTxtRound');
				comp.removeClass('BoxDashBlue');
				comp.removeClass('BoxPostit');
				comp.removeClass('BoxShadowA');
				comp.removeClass('BoxAzur');
				comp.removeClass('BoxCadre');

				comp.addClass(nameClass);
                
            }

        }

	});

    if(findObject==false){
        alert('Echec de mise à jour');
    }

}

function setAbstractObjOneClass(nameClass,removeClass,removeClass2){
    
    var wrapperChildrenTbl = editor.getWrapper();
    const allBody = wrapperChildrenTbl.findType('table');
    
    var  findObject = false;
    
	editor.DomComponents.getWrapper().onAll(comp =>{

        var idTrait = comp.get('traits')['id'];
		
        if(typeof idTrait != 'undefined'&&idTrait!='undefined'){
           
            var makeAnId = comp.get('tagName') + idTrait;

            if(GlobalIDGrappesObj==makeAnId){
				
				findObject = true;
				var compContent = comp.get("content");
				if (removeClass!='') {
					comp.removeClass(removeClass);
				}
				if (removeClass2!='') {
					comp.removeClass(removeClass2);
				}
				if (nameClass!='') {
					comp.addClass(nameClass);
				}
				
            }

        }

	});

    if(findObject==false){
        alert('Echec de mise à jour');
    }

}

function abstractHaveClass(nameClass){
    
    var wrapperChildrenTbl = editor.getWrapper();
    const allBody = wrapperChildrenTbl.findType('table');

    var findObject = false;
    var haveClass = false;

	editor.DomComponents.getWrapper().onAll(comp =>{

        var idTrait = comp.get('traits')['id'];
		
        if(typeof idTrait != 'undefined'&&idTrait!='undefined'){
           
            var makeAnId = comp.get('tagName') + idTrait;

            if(GlobalIDGrappesObj==makeAnId){
				
				findObject = true;
				var compContent = comp.get("content");
				
				if (nameClass!='') {
					var arrClas = comp.getClasses();
					if (arrClas.includes(nameClass)) {
						haveClass = true;
					}
				}
				
            }

        }

	});

    if(findObject==false){
        return false;
    } else {
		return haveClass;
	}

}

function detectLudiEditIco() {

    var comp = getAbstractObjContent();
    
	if (comp==-1) {
	
		setTimeout(function(){ detectLudiEditIco(); },450);
	
	} else {

		var type = comp.get('type');
		var type2 = '';
		var tagName = comp.get('tagName');
		
		if (tagName=='img'||tagName=='IMG') {
			type = 'image';
			tmpObjDom = getAbstractObjDom(GlobalIDGrappesObj);
		}
		
		if(type!='wrapper'&&(type=='div'||tagName=='div')){

			var compContent = comp.get("content");

			var view = comp.getView();
			var el = comp.getEl();
			var compContentH = el.innerHTML;

			if (compContentH.slice(0, 6)=='<audio') {
				type = 'audio';
			}
			if (compContentH.slice(0, 6)=='<video') {
				type = 'video';
			}
			if (compContentH.indexOf('rapidselectorgrapvideo')!=-1) {
				type = 'video';
			}
			
			if (compContentH.slice(0, 4)=='<img'){
				type = 'image';
			}

			if (compContent.slice(0, 6)=='<table'
			&&compContent.indexOf('teachdoctext')!=-1){
				type = 'table';
				type2 = 'tabletxt';
			}

			if(compContent.slice(0, 6)=='<table'
			&&compContent.indexOf('qcmbarre')!=-1){
				type = 'table';
			}

			if(compContent.slice(0, 6)=='<table'
			&&compContent.indexOf('teachdocbtnteach')!=-1){
				type = 'table';
			}
			
			if(compContent.slice(0, 6)=='<table'
			&&compContent.indexOf('teachdocplugteach')!=-1){
				type = 'table';
			}
			
			if(compContent.indexOf('audio')!=-1||type=='audio'){
				type = 'audio';
				var domObj = $(tmpObjDom);
				if(!domObj.is("audio")){
					tmpObjDom = getAbstractObjDom(GlobalIDGrappesObj);
				}
			}
			if(compContent.indexOf('video')!=-1){
				type = 'video';
				var domObj = $(tmpObjDom);
				if(!domObj.is("video")){
					tmpObjDom = getAbstractObjDom(GlobalIDGrappesObj);
				}
			}

			var deepCOntent = false;
			
			if (deepCOntent) {
				
				if(compContentH.indexOf('table')!=-1
				&&compContentH.indexOf('qcmbarre')!=-1){
					type = 'table';
				}
				if(compContentH.indexOf('table')!=-1
				&&compContentH.indexOf('teachdoctext')!=-1){
					type = 'table';
					type2 = 'tabletxt';
				}
				if(compContentH.indexOf('table')!=-1
				&&compContentH.indexOf('teachdocbtnteach')!=-1){
					type = 'table';
				}
				if (compContentH.indexOf('<audio ')!=-1 ){
					type = 'audio';
				}
				if (compContentH.indexOf('<video ')!=-1 ){
					type = 'video';
				}
				
			}
			
			var classN = comp.get('classes');
			var classMod = classN.models;
			for (var modelA in classMod) {
				if(classMod[modelA].attributes.name=='panel'){
					type = 'panel';
				}
			}

		}

		if (type=='wrapper'||type=='panel'||onRenderUpdate) {
			$(".gjs-toolbar").css('display','none');
			$(".gjs-badge").css('display','none');
			$(".gjs-toolbar").css('opacity','0');
			type = 'panel';
		} else {
			$(".gjs-toolbar").css('display','');
			$(".gjs-badge").css('display','block');
			$(".gjs-toolbar").css('opacity','1');	
		}

		if (type!='wrapper') {
		
			var compContent = comp.get("content");
			var domObj = $(tmpObjDom);
			var view = comp.getView();
			var el = comp.getEl();
			var compContentH = el.innerHTML;

			if (type=='text') {
				if (
					(compContent.slice(0, 3)=='<h1'
					&&compContent.indexOf('h1')!=-1)
					||(compContentH.slice(0, 3)=='<h1'
					&&compContentH.indexOf('h1')!=-1)
					||(domObj.is("h1"))||comp.get('tagName')=='h1'
				) {
					type = 'titleh1';
					type2 = 'titleh1';
					tmpObjDom = getAbstractObjDom(GlobalIDGrappesObj);
				}
			}
			//<tbody><tr> teachdoctextContent
			if (type=='table') {
				if (
					(compContent.slice(0, 6)=='<table'
					&&compContent.indexOf('teachdoctext')!=-1)
					||(compContentH.slice(0, 6)=='<table'
					&&compContentH.indexOf('teachdoctext')!=-1)
					||(compContentH.slice(0, 11)=='<tbody><tr>'
					&&compContentH.indexOf('teachdoctext')!=-1)
					||(compContentH.slice(0, 11)=='<tbody><tr>'
					&&compContentH.indexOf('teachdoctextContent')!=-1)
					||(compContentH.slice(0, 6)=='<table'
					&&compContentH.indexOf('teachdoctextContent')!=-1)
				) {
					type2 = 'tabletxt';
					// tmpObjDom = getAbstractObjDom(GlobalIDGrappesObj);
				}
			}
		
		}

        if (type=='table'||type=='audio'||type=='video') {
			if (tmpNameObj!="animfx") {
				if (lastPosiLudiIco>300) {
					$(".ludiEditIco").css('display','block');
				}else {
					lastPosiLudiIco = 450;
				}
			} else {
				$(".ludiEditIco").css('display','none');
			}
        }else{
            $(".ludiEditIco").css('display','none');
        }
		
		if (haveSpeedTools(type,type2)) {
			$(".ludiSpeedTools").css('display','block');
			if (type=='titleh1') {
				tmpObjDom = getAbstractObjDom(GlobalIDGrappesObj);
			}
			if (type2 == 'tabletxt') {
				tmpObjDom = getAbstractObjDom(GlobalIDGrappesObj);
			}
		} else {
            $(".ludiSpeedTools").css('display','none');
        }
        setTimeout(function(){ detectLudiEditIco(); },500);
    }
}
setTimeout(function(){ detectLudiEditIco(); },1000);

function getAbstractObjDom(iDGrappesObj){

    var rcomp = -1;

    editor.DomComponents.getWrapper().onAll(comp =>{

        var idTrait = comp.get('traits')['id'];

        if(typeof idTrait != 'undefined'&&idTrait!='undefined'){
           
            var makeAnId = comp.get('tagName') + idTrait;

            if(iDGrappesObj==makeAnId){
				rcomp = comp.getEl();
				var compContentH = $(rcomp).html();
				if(compContentH.indexOf('<audio ')!=-1){
					rcomp = $(rcomp).find("audio").get(0);
				}
				if(compContentH.indexOf('<video ')!=-1){
					rcomp = $(rcomp).find("video").get(0);
				}
            }

        }

    });
    
    return rcomp;

}

function getAbstractObjContent(){

    var rcomp = -1;

    editor.DomComponents.getWrapper().onAll(comp =>{

        var idTrait = comp.get('traits')['id'];

        if(typeof idTrait != 'undefined'&&idTrait!='undefined'){
           
            var makeAnId = comp.get('tagName') + idTrait;

            if(GlobalIDGrappesObj==makeAnId){
                rcomp = comp;
            }

        }

    });
    
    return rcomp;

	

}

function getUnikIdGrappesObj(){

	var idNum = Math.floor(Math.random() * 100);
    
	var iLetter = '';
	var characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
	var charactersLength = characters.length;
	for(var i=0;i<15; i++){
	  iLetter += characters.charAt(Math.floor(Math.random() * charactersLength));
	}
  
	return idNum + iLetter;
  
}

//before select
function hookSelectAnObject() {

    console.log('hookSelectAnObject');

    setTimeout(function(){
        installMicroMenu();
    },100);

}

function installMicroMenu() {

    var toolItem = $('div.fa.fa-plus-square.gjs-toolbar-item'); //32 935
    if (toolItem.length>0) {
        $('.objMenuParamFloat').css("display","none");
    }

}

//load a windows or a function
function loadaFunction() {
	$('.objMenuParamFloat').css("display","none");
	$('.maskobjMenu').css("display","none");
	$('.maskpause').css("display","none");
}

//before select
function displayMicroMenu() {

    var toolItem = $('div.fa.fa-plus-square.gjs-toolbar-item'); //32 935
    if (toolItem.length==1) {
        displayObjectMenu(toolItem);
        console.log('click menu');
    }
    
}
if (!jQuery.ui) {
    $('body').append('<script src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.11.4/jquery-ui.min.js"></script>');
}

var heightObjMenu = 260;

function displayObjectMenu(toolItem) {

    if ($("#ObjMenuParams").length==0) {
    
        var bdDiv = '<div id="ObjMenuParams" class="objMenuParamFloat" >';
        bdDiv += '<div id="objMenuTitleFloat" class="objMenuTitleFloat" >Objet</div>';
        
        var styl = 'style="float:left;padding:5px;margin:3px;width:96%;"';

        bdDiv += '<p class="trd" style="padding:5px;margin:5px;margin-top:28px;margin-bottom:0px;" >Display / Hide :</p>';
        
        var bdDiv2 = '<p ' + styl + ' >';
        bdDiv2 += '<input type="checkbox" onclick="onObjMenuCheckBox(this)" class="checkRapidWindows" id="checkObjMA" name="checkObjMA"></input>';
        bdDiv2 += '<span>' + returnTradTerm('Display in all cases')+'</span></p>';
        
        bdDiv2 += '<p ' + styl + ' >';
        bdDiv2 += '<input type="checkbox" onclick="onObjMenuCheckBox(this)" class="checkRapidWindows" id="checkObjMB" name="checkObjMB"></input>';
        bdDiv2 += '<span>' + returnTradTerm('Display in case of a wrong answer on this page')+'</span></p>';

        //HOOK 
        if (typeof displayObjectMenuExtend == 'function') {
            bdDiv2 += displayObjectMenuExtend(toolItem);
            heightObjMenu = 340;
        }

        bdDiv2 += '<p ' + styl + ' >';
        bdDiv2 += '<input type="checkbox" onclick="onObjMenuCheckBox(this)" ';
        bdDiv2 += ' class="checkRapidWindows" id="checkObjME" name="checkObjME"></input>';
        bdDiv2 += '<span>' + returnTradTerm('Display when the document has been completed')+'</span></p>';

        bdDiv2 += '<p ' + styl + ' >';
        bdDiv2 += '<input type="checkbox" onclick="onObjMenuCheckBox(this)" ';
        bdDiv2 += 'class="checkRapidWindows" id="checkObjMF" name="checkObjMF"></input>';
        bdDiv2 += '<span>' + returnTradTerm('Hide when the document has been completed')+'</span></p>';

        bdDiv2 = bdDiv2.replace(/Display/g,"<span style='color:green;font-weight:bold;' >Display</span>");
        bdDiv2 = bdDiv2.replace(/Hide/g,"<span style='color:red;' >Hide</span>");

        bdDiv2 = bdDiv2.replace(/Afficher/g,"<span style='color:green;font-weight:bold;' >Afficher</span>");
        bdDiv2 = bdDiv2.replace(/Masquer/g,"<span style='color:red;' >Masquer</span>");

        bdDiv += bdDiv2;

        bdDiv += '<p style="float:right;text-align:right;" >';
        bdDiv += '<input style="width:110px;display:inline-block;cursor:pointer;margin-right:10px;" ';
        bdDiv += ' onClick="saveObjMenuCheckBox();" ';
        bdDiv += ' class="gjs-one-bg ludiButtonSave trd" type="button" value="Save" /><br/>';
        bdDiv += '</p>';

        bdDiv += '</div>';

        $('body').append(bdDiv);

    }

    if ($("#ObjMenuParams").length==1) {
        
        inactivjMenuCheckBox();

        $('.maskobjMenu').css("display","block");
        $('.objMenuParamFloat').css("display","block");

        var position = toolItem.parent().parent().position();
        var positionMain = toolItem.parent().parent().parent().position();
        
        var Mtop = position.top;
        var Maintop = positionMain.top;
        var Mleft = position.left;

        $('#ObjMenuParams').css("width",'20px').css("height",'20px').css("margin-left",'20px');
        $('#ObjMenuParams').css("top",parseInt((Mtop + Maintop) + 40) + 'px');
        $('#ObjMenuParams').css("left",parseInt(Mleft + $('.ludimenuteachdoc').width() ) + 'px');

        $( "#ObjMenuParams" ).animate({ 
            width : '450px', 
            marginLeft : '-455px'
	    },200,function(){

            $( "#ObjMenuParams" ).animate({ 
                height: heightObjMenu + "px"
            },200,function(){
            });

        });
        
        traductAll();

        loadjMenuCheckBox();

    }

}

function onObjMenuCheckBox(objT){

	if($(objT).is(':checked')){
        inactivjMenuCheckBox();
		$(objT).prop("checked",true);
	}

}

function inactivjMenuCheckBox(){

    $('#checkObjMA').prop("checked",false);
    $('#checkObjMB').prop("checked",false);
    $('#checkObjMC').prop("checked",false);
    $('#checkObjMD').prop("checked",false);
    $('#checkObjME').prop("checked",false);
    $('#checkObjMF').prop("checked",false);
    $('#checkObjMG').prop("checked",false);
    $('#checkObjMH').prop("checked",false);

}

function loadjMenuCheckBox(){

    var haveSpecialClass = false;
    if (abstractHaveClass('dhcondiMA') ) {
        haveSpecialClass = true;
        $('#checkObjMA').prop("checked",true);
    }
    if (abstractHaveClass('dhcondiMB') ) {
        haveSpecialClass = true;
        $('#checkObjMB').prop("checked",true);
    }
    if (abstractHaveClass('dhcondiMC') ) {
        haveSpecialClass = true;
        $('#checkObjMC').prop("checked",true);
    }
    if (abstractHaveClass('dhcondiMD') ) {
        haveSpecialClass = true;
        $('#checkObjMD').prop("checked",true);
    }
    if (abstractHaveClass('dhcondiME') ) {
        haveSpecialClass = true;
        $('#checkObjME').prop("checked",true);
    }
    if (abstractHaveClass('dhcondiMF') ) {
        haveSpecialClass = true;
        $('#checkObjMF').prop("checked",true);
    }
    if (abstractHaveClass('dhcondiMG') ) {
        haveSpecialClass = true;
        $('#checkObjME').prop("checked",true);
    }
    if (abstractHaveClass('dhcondiMH') ) {
        haveSpecialClass = true;
        $('#checkObjMF').prop("checked",true);
    }
    if (haveSpecialClass==false) {
        $('#checkObjMA').prop("checked",true);
    }
    //setAbstractObjOneClass('','');

}

function saveObjMenuCheckBox(){

    setAbstractObjOneClass('','dhcondiMA','dhcondiMB');
    setAbstractObjOneClass('','dhcondiMC','dhcondiMD');
    setAbstractObjOneClass('','dhcondiME','dhcondiMF');
    setAbstractObjOneClass('','dhcondiMG','dhcondiMH');

    if ($('#checkObjMA').is(':checked')) {
        setAbstractObjOneClass('dhcondiMA','','');
    }
    if ($('#checkObjMB').is(':checked')) {
        setAbstractObjOneClass('dhcondiMB','','');
    }
    if ($('#checkObjMC').is(':checked')) {
        setAbstractObjOneClass('dhcondiMC','','');
    }
    if ($('#checkObjMD').is(':checked')) {
        setAbstractObjOneClass('dhcondiMD','','');
    }
    if ($('#checkObjME').is(':checked')) {
        setAbstractObjOneClass('dhcondiME','','');
    }
    if ($('#checkObjMF').is(':checked')) {
        setAbstractObjOneClass('dhcondiMF','','');
    }
    if ($('#checkObjMG').is(':checked')) {
        setAbstractObjOneClass('dhcondiMG','','');
    }
    if ($('#checkObjMH').is(':checked')) {
        setAbstractObjOneClass('dhcondiMH','','');
    }
    
    $('.maskobjMenu').css("display","none");

    $( "#ObjMenuParams" ).animate({ 
        width : '20px',
        height: "20px",
        marginLeft : '0px'
    },200,function(){
        $('#ObjMenuParams').css("display","none");
    });
    
}

var contentSourceEdition = "";
var contentSourceEditionV1 = "";
var contentSourceEditionV2 = "";
var identSourceEdition = 1;

function displayPlugTeachEdit(myObj){
	
	var btnObj = $(myObj);
	tmpObjDom = btnObj;
	
	identchange = getUnikId();
	tmpObjDom.attr("data-ref",identchange);
	
	var typesource = "";
	var btnObjDivhref = btnObj.find('div');
	typesource = btnObjDivhref.parent().find('span.typesource').html();
	if (typesource===undefined){typesource = '';}
	
	if (typesource=='slidesactive') {
		displaySlidesActiveEdit(myObj);
		return false;
	}

	if (typesource=='imageactive') {
		displayImageActiveEdit(myObj);
		return false;
	}
	
	if (typesource=='schemasvgobj') {
		displayImageSchemaEdit(myObj);
		return false;
	}

	if (typesource=='mapsvgobj') {
		displayImageMapEdit(myObj);
		return false;
	}
	
	contentSourceEdition = '';
	identSourceEdition++;

	if (isTypeSourceCont(typesource)) {
		var objDivSrc = btnObj.find('div');
		contentSourceEdition = objDivSrc.html();
		if(contentSourceEdition===undefined){
			contentSourceEdition = '';
		}
	}

	//error
	if (typesource=="") {
		var redir = true;
		var objDivSrc = btnObj.find('div');
		var logscontext = objDivSrc.context.outerHTML;
		if ( logscontext.indexOf("teachdocplugteach")!=-1
		&& logscontext.indexOf("<table")!=-1 ) {
			reloadTableToGrap();
			$('#ludiEditIco').css("display","none");
			// redir = false;
		}
		if (redir==true) {
			reloadPageErr();
		}
		return false;
	}
	
	if ($("#BtnEditPlugTeach"+typesource).length==0) {
		
		var autodestroy = '';
		if (typesource=='minidia') {
			autodestroy = ' autodestroy-windows';
		}

		var bdDiv = '<div id="BtnEditPlugTeach' + typesource + '" ';
		bdDiv += ' class="gjs-mdl-container BtnEditPlugTeach'+autodestroy+'" >';
		bdDiv += '<div class="gjs-mdl-dialog-v2 gjs-one-bg gjs-two-color" ';
		if (typesource.indexOf('oelcontent')!=-1||typesource=='txtmathjax') {
			var screenWidth = window.innerWidth || document.documentElement.clientWidth || document.body.clientWidth;
			if (screenWidth < 850) {
				bdDiv += ' style="max-width:100%!important;" >';
			} else {
				bdDiv += ' style="max-width:850px!important;" >';
			}
		} else {
			bdDiv += ' style="max-width:680px!important;" >';
		}

		bdDiv += getTitleBar('Edition ' + typesource);
		
		bdDiv += '<div class="gjs-am-add-asset" ';
		bdDiv += 'style="padding:25px;padding-top:10px;font-size:16px;" >';
		if (typesource=='card') {
			bdDiv += txtParamsPlugTeach(1,typesource,'Text');
			bdDiv += imgParamsPlugTeach(2,typesource,'Image');
		}
		if (typesource=='blank') {
			bdDiv += txtParamsPlugTeach(1,typesource,'Title');
			bdDiv += areaParamsPlugTeach(2,typesource,'Text');
			bdDiv += helperParamsPlugTeach(typesource,'Text');
			bdDiv += helperSolutionCheckPlugTeach(3,typesource,'Text');
		}
		if (typesource=='filltext') {
			bdDiv += txtParamsPlugTeach(1,typesource,'Title');
			bdDiv += areaParamsPlugTeach(2,typesource,'Text');
			bdDiv += helperParamsPlugTeach(typesource,'Text');
			bdDiv += helperSolutionCheckPlugTeach(3,typesource,'Text');
		}
		if (typesource=='markwords') {
			bdDiv += txtParamsPlugTeach(1,typesource,'Title');
			bdDiv += areaParamsPlugTeach(2,typesource,'Text');
			bdDiv += helperParamsPlugTeach(typesource,'Text');
			bdDiv += helperSolutionCheckPlugTeach(3,typesource,'Text');
		}
		if (typesource=='findwords') {
			bdDiv += txtParamsPlugTeach(1,typesource,'Title');
			bdDiv += listWordsParamsTeach(2,typesource,'Words');
			bdDiv += helperParamsPlugTeach(typesource,'Text');
			bdDiv += helperSolutionCheckPlugTeach(3,typesource,'Text');
		}
		if (typesource=='sorttheparagraphs') {
			bdDiv += txtParamsPlugTeach(1,typesource,'Title');
			bdDiv += listparagraphsParamsTeach(2,typesource,'Words');
			bdDiv += helperParamsPlugTeach(typesource,'Text');
			bdDiv += helperSolutionCheckPlugTeach(3,typesource,'Text');
		}
		if (typesource=='indextable') {
			bdDiv += txtParamsPlugTeach(1,typesource,'Title');
		}
		if (typesource=='iframe-obj') {
			bdDiv += txtParamsPlugTeach(1,typesource,'Link');
			bdDiv += heightParamsPlugTeach(2,typesource,'Height');
		}
		if (typesource=='minidia') {
			var tmpdatatext1 = btnObjDivhref.parent().find('span.datatext1').html();
			var tmpdatatext2 = btnObjDivhref.parent().find('span.datatext2').html();
			if (typeof tmpdatatext1 === "undefined"){
				tmpdatatext1 = '';
			}
			var btnObjDivhref = btnObj.find('div');
			bdDiv += select2minidia(tmpdatatext2);
			bdDiv += areaRichParamsPlugTeach(1,typesource,tmpdatatext1);
		}

		if (isTypeSourceCont(typesource)) {

			if (typesource=='oelcontentsummarywomen'
				||typesource=='oelcontentnumericcards'
				||typesource=='oelcontentkeypointsblock') {
				bdDiv += '<div id="plugPmargAreaContent'+typesource+'" class="plugPmargAreaContentMax" ></div>';
			} else {
				bdDiv += '<div id="plugPmargAreaContent'+typesource+'" class="plugPmargAreaContent" ></div>';
			}

		}

		bdDiv += getinputFXObj(typesource);
		
		bdDiv += '<div style="padding:25px;padding-top:10px;padding-bottom:5px;text-align:right;" >';
		bdDiv += '<input onClick="savePlugTeach(\''+typesource+'\')" ';
		bdDiv += ' class="gjs-one-bg ludiButtonSave trd" type="button" value="Save" /><br/>';
		bdDiv += '</div>';
		
		bdDiv += '</div>';
		bdDiv += '</div>';

		bdDiv += '<div class="gjs-mdl-collector'+autodestroy+'" style="display:none"></div>';
		bdDiv += '</div>';

		$('body').append(bdDiv);

		if (typesource=='minidia') {
			
			$('#datatext1' + typesource + identSourceEdition).tinymce({
				menubar: false,
				statusbar: false,
				toolbar: 'undo redo| fontselect fontsizeselect forecolor | bold italic underline | bullist numlist outdent indent |link unlink removeformat blockquote code ',
				plugins: 'link lists',
				contextmenu: 'link lists'
			});
		}

	}
    
	if ($("#BtnEditPlugTeach"+typesource).length==1) {
		
        var datatext1 = '';
		var datatext2 = '';
		var datatext3 = '';
		
        if(datatext1==''){
			var btnObjDivhref = btnObj.find('div');
			datatext1 = btnObjDivhref.parent().find('span.datatext1').html();
			datatext2 = btnObjDivhref.parent().find('span.datatext2').html();
			if (isTypeHVPCont(typesource)) {
				datatext3 = btnObjDivhref.parent().find('span.datatext3').html();
			}
		}
		
        if (datatext1===undefined) { datatext1 = ''; }
		if (datatext2===undefined) { datatext2 = ''; }
		if (datatext1==="undefined") { datatext1 = ''; }
		if (datatext2==="undefined") { datatext2 = ''; }
		
		if (isTypeHVPCont(typesource)) {

			datatext2 = datatext2.replace(/!br!/g,'\n')
			datatext2 = datatext2.replace(/!slash47!/g,'/');
			datatext2 = datatext2.replace(/!slash92!/g,'\\');
			datatext2 = datatext2.replace(/!aro!/g,'@');
			datatext2 = datatext2.replace(/!pourc!/g,'%');
			if (typesource!='findwords'&&typesource!='sorttheparagraphs') {
				datatext2 = datatext2.replace(/!spe44!/g,',');
			}
			
			datatext1 = datatext1.replace(/!slash47!/g,'/');
			datatext1 = datatext1.replace(/!slash92!/g,'\\');
			datatext1 = datatext1.replace(/!aro!/g,'@');
			datatext1 = datatext1.replace(/!pourc!/g,'%');

			if(datatext3===undefined){datatext3 = '';}
			if(datatext3==="undefined"){datatext3 = '';}

			if (datatext3.indexOf('sol')!=-1) {
				document.getElementById('checkSol3' + typesource).checked = true;
			}else{
				document.getElementById('checkSol3' + typesource).checked = false;
			}

			if (typesource=='findwords') {
				loadAllWords(datatext2);
			}
			if (typesource=='sorttheparagraphs') {
				loadAllParas(datatext2);
			}
			
		}

		if (typesource=='iframe-obj') {
			if (datatext2=='') {
				datatext2 = 350;
			}
		}
		if (typesource=='minidia') {
			selectAvatarminidia(datatext2);
		}

		$('#datatext1'+ typesource).val(datatext1);

		txtAutoFillPlugTeach(i,typesource);

		$('#datatext2'+ typesource).val(datatext2);
		
		$('.ludimenu').css("z-index",'2');
		$('#BtnEditPlugTeach'+typesource).css("display",'');
		
		windowEditorIsOpen = true;
		loadaFunction();
		if (typesource.indexOf("animfx")!=-1) {
			$('#BtnEditPlugTeach'+typesource).css("display",'none');
		}

		if (isTypeSourceCont(typesource)) {
			var hcontent = htmlParamsContentEdit(identSourceEdition,typesource);
			$('#plugPmargAreaContent'+typesource).html(hcontent);
			if (contentSourceEdition!='') {
				contentSourceEditionV1 = datatext1;
				contentSourceEditionV2 = datatext2;
				installDirectContentEdit(identSourceEdition);
			}
		}

		traductAll();

	}
	
}

function isTypeHVPCont(typesource){
	var b = false;
	if (typesource=='blank'||typesource=='filltext'
	||typesource=='sorttheparagraphs'
	||typesource=='markwords'||typesource=='findwords') {
		b = true;
	}
	return b;
}

function isTypeSourceCont(typesource){
	var b = false;
	if (typesource.indexOf('elcontent')!=-1
	||typesource=='txtmathjax') {
		b = true;
	}
	return b;
}

function savePlugTeach(typesource){

	if(onlyOneUpdate==false){
		return false;
	}
	
	var isQuizzElem = false;

	var datatext1 = $('#datatext1'+ typesource).val();

	if (typesource=='minidia') {
		datatext1 = $('#datatext1' + typesource + identSourceEdition).val();
	}
	
	var datatext2 = $('#datatext2'+ typesource).val();
	var datatext3 = '';

	var rP = '';
	if (typesource=='card') {
		rP = renderplugincard(datatext1,datatext2);
	}

	if (isTypeHVPCont(typesource)) {
		
		//Exeption list of contents
		if (typesource!='sorttheparagraphs'&&typesource!='findwords') {
			datatext2 = datatext2.replace(/\r\n|\r|\n/g,"!br!")
			datatext2 = encodeHVPToTxt(datatext2);
		}
		datatext1 = encodeHVPToTxt(datatext1);
		if(document.getElementById('checkSol3'+ typesource).checked){
			datatext3 += 'sol';
		}

	}

	if (typesource=='blank') {
		isQuizzElem = true;
		rP = renderpluginblank(datatext1,datatext2);
	}
	if (typesource=='filltext') {
		isQuizzElem = true;
		rP = renderpluginfilltext(datatext1,datatext2);
	}
	if (typesource=='markwords') {
		isQuizzElem = true;
		rP = renderpluginmarkwords(datatext1,datatext2);
	}
	if (typesource=='findwords') {
		compilAllWords();
		isQuizzElem = true;
		rP = renderpluginfindwords(datatext1,datatext2);
	}
	if (typesource=='sorttheparagraphs') {
		compilAllParas();
		isQuizzElem = true;
		rP = renderpluginsorttheparagraphs(datatext1,datatext2);
	}

	if (typesource=='iframe-obj') {
		rP = renderpluginiframe(datatext1,datatext2);
	}
	if (typesource=='minidia') {
		rP = renderplugminidia(datatext1,datatext2);
	}
	if (typesource=='indextable') {
		rP = renderpluginIndextable(datatext1,datatext2);
	}
	
	if (rP=="") {
		rP = renderFXObj(datatext1,datatext2,typesource);
	}
	//error
	if (rP=="") {
		reloadPageErr();
		return false;
	}

	var paramsDB = '<span class=datatext1 >' ;
    paramsDB +=  datatext1 + '</span>';

    paramsDB += '<span class=datatext2 >' ;
    paramsDB +=  datatext2 + '</span>';

	if (isTypeHVPCont(typesource)) {
		paramsDB += '<span class=datatext3 >' ;
		paramsDB +=  datatext3 + '</span>';
	}

	paramsDB += '<span class=typesource >' ;
    paramsDB +=  typesource + '</span>';

    var rH = GplugSrcT;
	//Special UI
	if (typesource=='lifebar') {
		rH = rH.replace('plugteachcontain','plugteachuicontain');
	}

    rH = rH.replace("{content}",rP + paramsDB);

    if(GlobalTagGrappeObj=='div'){

		if (isQuizzElem) {
			rH = GquizzSrcTop + rH + GplugSrcBottom;
		} else {
			rH = GplugSrcTop + rH + GplugSrcBottom;
		}
		
	}
    
	setAbstractObjContent(rH);
	
	closeAllEditWindows();
	
	$('.ludimenu').css("z-index","1000");
	saveSourceFrame(false,false,0);

}

//Auto fill
function txtAutoFillPlugTeach(i,typesource){

	var obj = $('#datatext'+ i + typesource);

	if (obj.val()=='') {
		if (typesource=='sorttheparagraphs') {
			obj.val(returnTradTerm('Sort the Paragraphs'));
		}
		if (typesource=='findwords') {
			obj.val(returnTradTerm('Find the words'));
		}
	}

}

//params plug
function txtParamsPlugTeach(i,typesource,txt){

	var bdDiv = '<div class="plugPmarg" >';
	bdDiv += '<div class="plugLabelDiv trd" >';
	bdDiv += txt;
	bdDiv += '&nbsp;:&nbsp;</div>';
	bdDiv += '<input id="datatext'+ i + typesource + '" type="text" value="" ';
	bdDiv += ' class="plugInputDiv" />';
	bdDiv += '</div>';
	return bdDiv;

}

function listWordsParamsTeach(i,typesource,txt){

	var bdDiv = '<div class="plugPmarg" >';
	bdDiv += '<div class="plugLabelDiv trd" >';
	bdDiv += txt + '&nbsp;:&nbsp;</div>';
	bdDiv += '<input id="findwordsA" onblur="compilAllWords()" type="text" value="" class="plugInputWord" />';
	bdDiv += '<input id="findwordsB" onblur="compilAllWords()" type="text" value="" class="plugInputWord" />';
	bdDiv += '</div>';

	bdDiv += '<div class="plugPmarg" >';
	bdDiv += '<div class="plugLabelDiv" >&nbsp;&nbsp;</div>';
	bdDiv += '<input id="findwordsC" onblur="compilAllWords()" type="text" value="" class="plugInputWord" />';
	bdDiv += '<input id="findwordsD" onblur="compilAllWords()" type="text" value="" class="plugInputWord" />';
	bdDiv += '</div>';

	bdDiv += '<input id="datatext2findwords" type="text" value="" style="display:none;" />';

	return bdDiv;

}

function listparagraphsParamsTeach(i,typesource,txt){

	var bdDiv = '<div class="plugPmarg" >';
	bdDiv += '<div class="plugLabelDiv trd" >';
	bdDiv += '&nbsp;1&nbsp;</div>';
	bdDiv += '<input id="findParasA" onblur="compilAllParas()" type="text" value="" class="plugInputDiv" />';
	bdDiv += '</div>';

	bdDiv += '<div class="plugPmarg" >';
	bdDiv += '<div class="plugLabelDiv" >&nbsp;2&nbsp;</div>';
	bdDiv += '<input id="findParasB" onblur="compilAllParas()" type="text" value="" class="plugInputDiv" />';
	bdDiv += '</div>';

	bdDiv += '<div class="plugPmarg" >';
	bdDiv += '<div class="plugLabelDiv" >&nbsp;3&nbsp;</div>';
	bdDiv += '<input id="findParasC" onblur="compilAllParas()" type="text" value="" class="plugInputDiv" />';
	bdDiv += '</div>';

	bdDiv += '<div class="plugPmarg" >';
	bdDiv += '<div class="plugLabelDiv" >&nbsp;4&nbsp;</div>';
	bdDiv += '<input id="findParasD" onblur="compilAllParas()" type="text" value="" class="plugInputDiv" />';
	bdDiv += '</div>';

	bdDiv += '<div class="plugPmarg" >';
	bdDiv += '<div class="plugLabelDiv" >&nbsp;5&nbsp;</div>';
	bdDiv += '<input id="findParasE" onblur="compilAllParas()" type="text" value="" class="plugInputDiv" />';
	bdDiv += '</div>';

	bdDiv += '<div class="plugPmarg" >';
	bdDiv += '<div class="plugLabelDiv" >&nbsp;6&nbsp;</div>';
	bdDiv += '<input id="findParasF" onblur="compilAllParas()" type="text" value="" class="plugInputDiv" />';
	bdDiv += '</div>';

	bdDiv += '<input id="datatext2sorttheparagraphs" type="text" value="" style="display:none;" />';

	return bdDiv;

}

function heightParamsPlugTeach(i,typesource,txt){

	var bdDiv = '<div class="plugPmarg" >';
	bdDiv += '<div class="plugLabelDiv trd" >';
	bdDiv += txt;
	bdDiv += '&nbsp;:&nbsp;</div>';
	bdDiv += '<input id="datatext'+ i + typesource + '" type="number" min="350" max="1200" ';
	bdDiv += ' class="plugInputDiv" />';
	bdDiv += '</div>';
	return bdDiv;

}

function numbe10ParamsPlugTeach(i,typesource,txt){

	var bdDiv = '<div class="plugPmarg" >';
	bdDiv += '<div class="plugLabelDiv trd" >';
	bdDiv += txt;
	bdDiv += '&nbsp;:&nbsp;</div>';
	bdDiv += '<input id="datatext'+ i + typesource + '" type="number" min="1" max="10" ';
	bdDiv += ' class="plugInputDiv" />';
	bdDiv += '</div>';
	return bdDiv;

}

function areaParamsPlugTeach(i,typesource,txt){

	var bdDiv = '<div class="plugPmargArea" >';
	bdDiv += '<div class="plugLabelDiv trd" >';
	bdDiv += txt;
	bdDiv += '&nbsp;:&nbsp;</div>';
	bdDiv += '<textarea id="datatext'+ i + typesource + '" type="text" value="" ';
	bdDiv += ' class="plugAreaDiv" ></textarea>';
	bdDiv += '</div>';
	return bdDiv;

}

function areaRichParamsPlugTeach(i,typesource,txt){
	
	var bdDiv = '<div class="plugPmargAreaRich" >';
	bdDiv += '<textarea id="datatext'+ i + typesource + identSourceEdition + '" type="text" value="" ';
	bdDiv += ' class="plugAreaDivRich" >'+txt+'</textarea>';
	bdDiv += '</div>';
	return bdDiv;

}

function imgParamsPlugTeach(i,typesource,txt){

	var bdDiv = '<div class="plugPmarg" >';
	bdDiv += '<div class="plugLabelDiv trd" >';
	bdDiv += txt;
	bdDiv += '&nbsp;:&nbsp;</div>';
	bdDiv += '<input id="datatext' + i + typesource + '" type="text" value="" ';
	bdDiv += ' style="width:360px;" class="plugInputDiv" />';
	bdDiv += '&nbsp;<input onClick="showFileManagerStudio2(13,\'datatext2'+typesource+'\',0);" ';
	bdDiv += ' class="gjs-one-bg ludiButtonSave plugInputMin" type="button" value="..." />';
	bdDiv += '</div>';
	return bdDiv;

}

function helperParamsPlugTeach(typesource,txt){

	var bdDiv = '<div class="plugPmarg" >';
	bdDiv += '<div class="plugLabelDiv" >';
	bdDiv += '&nbsp;&nbsp;</div>';
	bdDiv += '<a onClick="helperProcessPlugTeach(\''+typesource+'\')" ';
	bdDiv += ' style="cursor:pointer;" id="dataexample'+ i + typesource + '" ';
	bdDiv += ' class="plugInputDiv trd" >Insert example</a>';
	bdDiv += '</div>';
	return bdDiv;

}

function helperSolutionCheckPlugTeach(i,typesource,txt){

	var bdDiv = '<div class="plugPmarg" >';
	bdDiv += '<div class="plugLabelDiv" >';
	bdDiv += '&nbsp;&nbsp;</div>';
	bdDiv += '<label style="margin-top:1px;float:left;" class="el-switch el-switch-green" >';
    bdDiv += '<input id="checkSol'+ i + typesource +'" type="checkbox" name="switch" >';
    bdDiv += '<span class="el-switch-style"></span>';
    bdDiv += '</label>';

    bdDiv += '<div style="position:relative;float:left;width:350px;left:15px;top:0px;padding:5px;" >';
    bdDiv += '<span class="trd" >Show solution button</span></div>';

	bdDiv += '</div>';
	return bdDiv;

}

function helperProcessPlugTeach(typesource){

	if (langselectUI=='fr_FR'&&typesource=='blank') {
		$('#datatext1blank').val("École de Lyon");
		var txtBlank = "L'École de Lyon est un terme désignant un groupe d'artistes *français* qui se sont réunis autour de Paul Chenavard.";
		txtBlank += "Elle a été fondée par *Pierre Revoil*, l'un des représentants du style *troubadour*.";
		$('#datatext2blank').val(txtBlank);
	} else if (typesource=='blank') {
		$('#datatext1blank').val("Lyon School");
		var txtBlank = "The Lyon School is a term for a group of *French* artists which gathered around Paul Chenavard.";
		txtBlank += "It was founded by *Pierre Revoil*, one of the representatives of the *Troubadour* style.";
		$('#datatext2blank').val(txtBlank);
	}

	if (langselectUI=='fr_FR'&&typesource=='filltext') {
		$('#datatext1filltext').val("Remplir les blancs");
		var txtFill = "Utilisez Remplir les blancs pour tester la *mémoire* des *étudiants* sur les idées importantes des supports de cours.";
		txtFill += "Les étudiants doivent être capables de *se souvenir* de détails spécifiques et de taper les réponses dans les espaces.";
		$('#datatext2filltext').val(txtFill);
	} else if (typesource=='filltext') {
		$('#datatext1filltext').val("Fill the Blanks");
		var txtFill = "Use Fill the Blanks to test *students* recall of important ideas from the teaching materials.";
		txtFill += "Students must be able to *remember* specific details and be able to type the answers into the spaces.";
		$('#datatext2filltext').val(txtFill);
	}

	if (langselectUI=='fr_FR'&&typesource=='markwords') {
		$('#datatext1markwords').val("Marquer les mots");
		$('#datatext2markwords').val("Un type de *question* basé sur la *créativité* qui permet aux créateurs de créer des *défis* où l'utilisateur doit marquer des types spécifiques de verbes dans un texte.");
	} else if (typesource=='markwords') {
		$('#datatext1markwords').val("Marks the Words");
		$('#datatext2markwords').val("A free based *question* type allowing creatives to create *challenges* where the user is to mark specific types of verbs in a text.");
	}

	if (langselectUI=='fr_FR'&&typesource=='sorttheparagraphs') {
		$('#datatext1sorttheparagraphs').val("Trier les paragraphes");
		$('#findParasA').val("D'abord, je me réveille à 7h00");
		$('#findParasB').val("Ensuite, je m'habille");
		$('#findParasC').val("Puis, je prends mon petit déjeuner");
		$('#findParasD').val("Après, je me brosse les dents");
		$('#findParasE').val("Enfin, je vais à l'école");
		$('#findParasF').val("");
		compilAllParas();
	} else if (typesource=='sorttheparagraphs') {
		$('#datatext1sorttheparagraphs').val(returnTradTerm('Sort the paragraphs'));
		$('#findParasA').val('First, I wake up at 7:00 am');
		$('#findParasB').val('Next, I get dressed');
		$('#findParasC').val('Then, I have breakfast');
		$('#findParasD').val('Afterward, I brush my teeth');
		$('#findParasE').val('Finally, I go to school');
		$('#findParasF').val('');
		compilAllParas();
	}

	if (langselectUI=='fr_FR'&&typesource=='findwords') {
		$('#datatext1findwords').val("Trouver les mots");
		$('#findwordsA').val("CHAMILO");
		$('#findwordsB').val("STUDIO");
		$('#findwordsC').val("OPEN");
		$('#findwordsD').val("LIBRE");
		compilAllWords();
	} else if (typesource=='findwords') {
		$('#datatext1findwords').val(returnTradTerm('Find the words'));
		$('#findwordsA').val('CHAMILO');
		$('#findwordsB').val('STUDIO');
		$('#findwordsC').val('OPEN');
		$('#findwordsD').val('LIBRE');
		compilAllWords();
	}

}

function encodeHVPToTxt(src) {

    src = src.replace(/\//g,'!slash47!');
    src = src.replace(/\\/g,'!slash92!');
    src = src.replace(/@/g,'!aro!');
    src = src.replace(/%/g,'!pourc!');
    src = src.replace(/#/g,'!djez!');
    src = src.replace(/,/g,'!spe44!');
    return src;

}

function encodeTxtToHVP(src){
    src = src.replace(/!slash47!/g,'/');
    src = src.replace(/!slash92!/g,"\\");
    src = src.replace(/!spe44!/g,',');
    src = src.replace(/!aro!/g,'@');
    src = src.replace(/!pourc!/g,'%');
    src = src.replace(/!djez!/g,'#');
    return src;
}

var GplugSrcTop = '<table class="teachdocplugteach" ';
GplugSrcTop += 'onMouseDown="parent.displayEditButon(this);" ';
GplugSrcTop += ' style="width:100%;text-align:center;" >';


var GquizzSrcTop = '<table class="teachdocplugteach quizzcontentplug" ';
GquizzSrcTop += 'onMouseDown="parent.displayEditButon(this);" ';
GquizzSrcTop += ' style="width:100%;text-align:center;" >';

var GplugSrcT = '<tr><td style="text-align:center;padding:10px;width:100%;position:relative;" >';
GplugSrcT += '<div class="plugteachcontain" >';
GplugSrcT += '{content}';
GplugSrcT += '</div>';
GplugSrcT += '</td></tr>';

var GplugSrcBottom = '</table>';

var firstSrcT = GplugSrcT.replace("{content}",renderplugincard('',''));

editor.BlockManager.add('plugTeachCard',{
	label: 'Card',
	attributes: {
		class: 'fa fa-text icon-plugTeach',
		style: "background-image: url('icon/plug-card.png');background-repeat:no-repeat;background-position:center center;"
	},
	category: 'Basic',
	content: {
		content: GplugSrcTop+firstSrcT+GplugSrcBottom,script: "",
		style: {
			width: '100%',
			minHeight: '70px'
		}
	}
});


var bCMQ = '<div class="row" style="position:relative;" id="i27td">';

var baseRenderLUDIQcm = '<table class="qcmbarre" onMouseDown="parent.displayEditButon(this);" style="width:100%;" >';

baseRenderLUDIQcm += '<tr><td colspan=2 style="padding:15px;" class=quizzTextqcm >'+returnTradTerm("Quizz text")+'</td></tr>';

baseRenderLUDIQcm += '<tr class=quizzTextTr ><td class=quizzTextTd ><img class=checkboxqcm src="img/qcm/matgreen0.png" />';
baseRenderLUDIQcm += '</td><td style="text-align:left;" >'+returnTradTerm("Answer 1")+'</td></tr>';
baseRenderLUDIQcm += '<tr class=quizzTextTr ><td class=quizzTextTd ><img class=checkboxqcm src="img/qcm/matgreen0.png" />';
baseRenderLUDIQcm += '</td><td style="text-align:left;" >'+returnTradTerm("Answer 2")+'</td></tr>';
baseRenderLUDIQcm += '<tr class=quizzTextTr ><td class=quizzTextTd ><img class=checkboxqcm src="img/qcm/matgreen0.png" />';
baseRenderLUDIQcm += '</td><td style="text-align:left;" >'+returnTradTerm("Answer 3")+'</td></tr>';
baseRenderLUDIQcm +='</table>';

bCMQ = baseRenderLUDIQcm;

// bCMQ += '</div>';

editor.BlockManager.add('CmqTeach',{
	label: 'Quiz',
	attributes: {class: 'fa fa-text qlab icon-cmq'},
	category: 'Basic',
	content: {
		content: bCMQ,
		script: "",
		style: {
		width: '100%',
		minHeight: '100px'
		}
	}
});

firstSrcT = GplugSrcT.replace("{content}",renderpluginblank('',''));

editor.BlockManager.add('plugTeachBlank',{
	label: 'Drag Drop',
	attributes: {
		class: 'fa fa-text qlab icon-plugTeach',
		style: "background-image: url('icon/plug-blank.png');background-repeat:no-repeat;background-position:center center;"
	},
	category: 'Basic',
	content: {
		content: GquizzSrcTop+firstSrcT+GplugSrcBottom,script: "",
		style: {
			width: '100%',
			minHeight: '70px'
		}
	}
});


firstSrcT = GplugSrcT.replace("{content}",renderpluginfilltext('',''));

editor.BlockManager.add('plugTeachFillText',{
	label: 'Fill text',
	attributes: {
		class: 'fa fa-text qlab icon-plugTeach',
		style: "background-image: url('icon/plug-filltext.png');background-repeat:no-repeat;background-position:center center;"
	},
	category: 'Basic',
	content: {
		content: GquizzSrcTop+firstSrcT+GplugSrcBottom,script: "",
		style: {
			width: '100%',
			minHeight: '70px'
		}
	}
});




firstSrcT = GplugSrcT.replace("{content}",renderpluginiframeInit('',''));

editor.BlockManager.add('plugTeachIframe',{
	label: 'iframe',
	attributes: {
		class: 'fa fa-text icon-plugTeach',
		style: "background-image: url('icon/plug-iframe.png');background-repeat:no-repeat;background-position:center center;"
	},
	category: 'Basic',
	content: {
		content: GplugSrcTop+firstSrcT+GplugSrcBottom,script: "",
		style: {
			width: '100%',
			minHeight: '300px'
		}
	}
});


firstSrcT = GplugSrcT.replace("{content}",renderplugminidia('',''));

editor.BlockManager.add('plugTeachMinidia',{
	label: 'mini dia',
	attributes: {
		class: 'fa fa-text icon-plugTeach',
		style: "background-image: url('icon/plug-minidia.png');background-repeat:no-repeat;background-position:center center;"
	},
	category: 'Basic',
	content: {
		content: GplugSrcTop+firstSrcT+GplugSrcBottom,script: "",
		style: {
			width: '100%',
			minHeight: '200px'
		}
	}
});


firstSrcT = GplugSrcT.replace("{content}",renderimgactive('',''));

editor.BlockManager.add('plugTeachImgActive',{
	label: 'Hotspot Img',
	attributes: {
		class: 'fa fa-text icon-plugTeach',
		style: "background-image: url('icon/plug-image-active.png');background-repeat:no-repeat;background-position:center center;"
	},
	category: 'Basic',
	content: {
		content: GplugSrcTop+firstSrcT+GplugSrcBottom,script: "",
		style: {
			width: '100%',
			minHeight: '200px'
		}
	}
});



firstSrcT = GplugSrcT.replace("{content}",renderslidesactive('',''));

editor.BlockManager.add('plugTeachSlides',{
	label: 'Slides',
	attributes: {
		class: 'fa fa-text icon-plugTeach',
		style: "background-image: url('icon/plug-slides.png');background-repeat:no-repeat;background-position:center center;"
	},
	category: 'Basic',
	content: {
		content: GplugSrcTop+firstSrcT+GplugSrcBottom,script: "",
		style: {
			width: '100%',
			minHeight: '200px'
		}
	}
});

firstSrcT = GplugSrcT.replace("{content}",renderpluginmarkwords('',''));

editor.BlockManager.add('plugTeachMarkwords',{
	label: 'Mark Words',
	attributes: {
		class: 'fa fa-text qlab icon-plugTeach',
		style: "background-image: url('icon/plug-markwords.png');background-repeat:no-repeat;background-position:center center;"
	},
	category: 'Basic',
	content: {
		content: GquizzSrcTop+firstSrcT+GplugSrcBottom,script: "",
		style: {
			width: '100%',
			minHeight: '70px'
		}
	}
});


firstSrcT = GplugSrcT.replace("{content}",renderpluginfindwords('',''));

editor.BlockManager.add('plugTeachFindwords',{
	label: 'Find Words',
	attributes: {
		class: 'fa fa-text qlab icon-plugTeach',
		style: "background-image: url('icon/plug-findwords.png');background-repeat:no-repeat;background-position:center center;"
	},
	category: 'Basic',
	content: {
		content: GquizzSrcTop+firstSrcT+GplugSrcBottom,script: "",
		style: {
			width: '100%',
			minHeight: '70px'
		}
	}
});

firstSrcT = GplugSrcT.replace("{content}",renderpluginsorttheparagraphs('',''));

editor.BlockManager.add('plugTeachSorttheparagraphs',{
	label: 'Sort paragraphs',
	attributes: {
		class: 'fa fa-text qlab icon-plugTeach',
		style: "background-image: url('icon/plug-sorttheparagraphs.png');background-repeat:no-repeat;background-position:center center;"
	},
	category: 'Basic',
	content: {
		content: GquizzSrcTop+firstSrcT+GplugSrcBottom,script: "",
		style: {
			width: '100%',
			minHeight: '70px'
		}
	}
});

firstSrcT = GplugSrcT.replace("{content}",renderpluginschemasvgobj('',''));

editor.BlockManager.add('plugTeachschemasvgobj',{
	label: 'Schema obj',
	attributes: {
		class: 'fa fa-text icon-plugTeach',
		style: "background-image: url('icon/plug-schemasvgobj.png');background-repeat:no-repeat;background-position:center center;"
	},
	category: 'Basic',
	content: {
		content: GplugSrcTop+firstSrcT+GplugSrcBottom,script: "",
		style: {
			width: '100%',
			minHeight: '200px'
		}
	}
});

firstSrcT = GplugSrcT.replace("{content}",renderpluginmapsvgobj('',''));

editor.BlockManager.add('plugTeachmapsvgobj',{
	label: 'Interactive Map',
	attributes: {
		class: 'fa fa-text icon-plugTeach',
		style: "background-image: url('icon/plug-mapsvgobj.png');background-repeat:no-repeat;background-position:center center;"
	},
	category: 'Basic',
	content: {
		content: GplugSrcTop+firstSrcT+GplugSrcBottom,script: "",
		style: {
			width: '100%',
			minHeight: '200px'
		}
	}
});

var bTable = '<table class="teachdoctext" ';
bTable += 'onMouseDown="parent.displayEditButon(this);" style="width:100%;" >';
bTable += '<tr><td class="teachdoctextContent" colspan=1 style="padding-top:15px;padding-bottom:15px;" >';

bTable += '<table class="teachdoctable" >';
bTable += '<tr><th>Col 1</th><th>Col 2</th><th>Col 3</th></tr>';
bTable += '<tr><td>Col 1</td><td>Col 2</td><td>Col 3</td></tr>';
bTable += '</table>'

bTable += '</td></tr>';
bTable +='</table>';

editor.BlockManager.add('TableTeach',{
	label : 'Table Bloc',
	attributes : {class: 'fa fa-text icon-txtteach'},
	category : 'Basic',
	content : {
		content : bTable,
		script : "",
		style : {
			width : '100%',
			minHeight : '100px'
		}
	}
});
function renderplugincard(var1,var2) {

    var h = '';
    
    h = '<div class="plugcard" tabindex="0" >';
    h += '<div class="plugcard-front" ';
    if (var2!='') {
        h += ' style="background-image: url(' + var2 + ');" ';
    }else{
        h += ' style="background-image: url(img/classique/cardbase.jpg);" ';
    }
    h += '></div>';
    h += '<div class="plugcard-back">';
    h += '<h2>' + var1 + '</h2>';
    h += '</div>';
    
    h += '</div>';
    
    h += '<div class="forceplug300" ></div>';

    h += '<span class=typesource >card</span>';

    return h;

}


function renderpluginblank(var1,var2) {

    var h = '<iframe';
    h += ' style="width:100%;height:300px;overflow:hidden;" ';
    h += ' frameBorder="0" ';
    h += ' src="oel-plug/hvpdragthewords/dragthewords.html" ';
    h += '></iframe>';
    
    if (var1==""&&var2==""&&langselectUI=='fr_FR') {
        var1 = "Titre";
        var2 = "Modifier l'objet<br /><br />";
    } else if (var1==""&&var2=="") {
        var1 = "Title";
        var2 = "Edit object<br /><br />";
    }

    var2 = var2.replace(/!br!/g,'<br />');
    var2 = var2.replace(/\*/g,'starClue');
    // var regex = /starClue[a-z]*starClue/gi;
    //var regex = /starClue[a-zA-Z0-9]*starClue/gi;
    var regex = /starClue(.*?)starClue/gi;

    var2 = var2.replace(regex,"<span style='color:red;' >????</span>");
    
    h = '<p style="font-size:18px;text-align:left;" >' + encodeTxtToHVP(var1) + '</p>';
    h += '<p style="font-size:18px;text-align:left;" >' + encodeTxtToHVP(var2) + '</p>';
    h += '<img src="img/classique/hvp_check.png" ';
    h += ' style="width:130px;height:45px;float:left;" />';
    h += '<span class=typesource >blank</span>';
    
    return h;

}

function renderpluginiframe(var1,var2) {

    var h  = '<div class=topinactiveteach ></div>';
    
    h += '<iframe';
    h += ' style="width:100%;min-height:350px;z-index:1;';
    h += 'height:' + var2 + 'px;overflow:hidden;" ';
    h += ' frameBorder="0" ';
    h += ' src="' + var1 + '" ></iframe>';
    
    h += '<span class=typesource >iframe-obj</span>';

    return h;

}

function renderpluginiframeInit(var1,var2) {

    var h = '<div';
    h += ' style="width:100%;height:350px;background:#E5E7E9;" ';
    h += '></div>';
    
    h += '<span class=typesource  >iframe-obj</span>';

    return h;

}
function renderplugminidia(var1,var2) {

    if (typeof var2 === "undefined"){
        var2 = '';
    }
    if (typeof var1 === "undefined"){
        var1 = '';
    }

    var h  = '';
    h = '<div class="plugminidia" tabindex="0" >';
    h += '<div class="avatar-minidia" ';
    if (var2!='') {
        h += ' style="background-image: url(' + var2 + ');" ';
    }else{
        h += ' style="background-image: url(img/classique/man-conducting-survey.png);" ';
    }
    h += '></div>';
    
    h += '<div class="dialog-minidia bubble-minidia">';
    h += var1;
    h += '</div>';
    
    h += '</div>';

    h += '<span class=typesource style="display:none;" >minidia</span>';

    return h;

}

function select2minidia(var2) {
    if (typeof var2 === "undefined"){var2 = '';}
    if (var2=='') {var2 = 'img/classique/man-conducting-survey.png';}
	var bdDiv = '<div class="plugPmargDia" >';
    bdDiv += '<div class="plugLabelDiv" >Avatar';
	bdDiv += '&nbsp;:&nbsp;</div>';
    
    bdDiv += '<img class="avatarminidiasel avatarminidiasel1" onClick="selectAminidia(this)" ';
    bdDiv += 'style="border:2px #E6E6E6 solid;width:60px;height:84px;float:left;" src="img/classique/man-conducting-survey.png" />';
    
    bdDiv += '<img class="avatarminidiasel avatarminidiasel2" onClick="selectAminidia(this)" ';
    bdDiv += 'style="border:2px #E6E6E6 solid;width:54px;height:84px;float:left;" src="img/classique/woman-conducting-survey.png" />';
    
    bdDiv += '<img class="avatarminidiasel avatarminidiasel3" onClick="selectAminidia(this)" ';
    bdDiv += 'style="border:2px #E6E6E6 solid;width:50px;height:84px;float:left;" src="img/classique/woman-question.png" />';

    bdDiv += '<img class="avatarminidiasel avatarminidiasel9" onClick="selectMyAvatar(this)" ';
    bdDiv += 'style="border:2px #E6E6E6 solid;width:54px;height:84px;float:left;" src="img/classique/selectavatar.png" />';

    bdDiv += '<input id="datatext2minidia" type="text" value="' + var2 + '" ';
	bdDiv += ' class="plugInputDiv" style="display:none;" />';
	bdDiv += '</div>';
	return bdDiv;
    
}

function selectAminidia(objI) {
    $(".avatarminidiasel").css("border","#E6E6E6 solid 2px");
    $(objI).css("border","green solid 2px");
    $("#datatext2minidia").val($(objI).attr("src"));
}

function selectAvatarminidia(var2) {

    var findOriginal = false;
    $(".avatarminidiasel").css("border","#E6E6E6 solid 2px");
    $(".avatarminidiasel").each(function(index){
        if($(this).attr("src")==var2){
            $(this).css("border","green solid 2px");
            findOriginal = true;
            if(var2.indexOf("img/classique")!=-1){
                $('.avatarminidiasel9').attr("src","img/classique/selectavatar.png");
            }
        }
	});

    if(findOriginal==false&&var2!=""){
        $('.avatarminidiasel9').attr("src",var2);
        $('.avatarminidiasel9').css("border","green solid 2px");
    }else{
        if(var2==''){
            $('.avatarminidiasel1').css("border","green solid 2px");
            $('.avatarminidiasel9').attr("src","img/classique/selectavatar.png");
        }
    }

}

function selectMyAvatar(objI) {

    showFileManagerStudio2(13,'datatext2minidia','refreshMyAvatarDia');
    
    preSelectFileWrapper();
    
    $(".avatarminidiasel").css("border","#E6E6E6 solid 2px");
    $(objI).css("border","green solid 2px");

}

function refreshMyAvatarDia(){

    var imgA =$('#datatext2minidia').val();

    if (imgA==''||
    (imgA.toLowerCase().indexOf('.png')==-1
    &&imgA.toLowerCase().indexOf('.jpg')==-1
    &&imgA.toLowerCase().indexOf('.jpeg')==-1
    &&imgA.toLowerCase().indexOf('.gif')==-1)
    ) {
        $('#datatext2minidia').val("");
        $('.avatarminidiasel9').attr("src","img/classique/selectavatar.png");
    }else{
        $('.avatarminidiasel9').attr("src",imgA);
    }

    $('.ui-widget-overlay').css("display","none");
	$('.workingProcessSave').css("display","none");
    $('.ludimenu').css("display","none");
    $('#BtnEditPlugTeach'+"minidia").css("display","");

}

function renderpluginfilltext(var1,var2) {

    var h = '<iframe';
    h += ' style="width:100%;height:300px;overflow:hidden;" ';
    h += ' frameBorder="0" ';
    h += ' src="oel-plug/hvpfillintheblanks/fill-in-the-missing-words.html" ';
    h += '></iframe>';

    if (var1==""&&var2==""&&langselectUI=='fr_FR') {
        var1 = "Remplir les blancs";
        var2 = "Utilisez Remplir les blancs pour tester la mémoire des étudiants sur les idées importantes des matériaux d'enseignement.<br />";
        var2 += "Les étudiants doivent être capables de *se souvenir* de détails spécifiques et de pouvoir taper les réponses dans les espaces.";
    } else if (var1==""&&var2=="") {
        var1 = "Fill the Blanks";
        var2 = "Use Fill the Blanks to test students recall of important ideas from the teaching materials.<br />";
        var2 = "Students must be able to *remember* specific details and be able to type the answers into the spaces.";
    }

    var2 = var2.replace(/!br!/g,'<br />');
    var2 = var2.replace(/\*/g,'starClue');
    // var regex = /starClue[a-z]*starClue/gi;
    //var regex = /starClue[a-zA-Z0-9]*starClue/gi;
    var regex = /starClue(.*?)starClue/gi;

    var2 = var2.replace(regex,"<span style='color:blue;' >[______]</span>");
    
    h = '<p style="font-size:18px;text-align:left;" >' +  encodeTxtToHVP(var1) + '</p>';
    h += '<p style="font-size:18px;text-align:left;" >' +  encodeTxtToHVP(var2)+ '</p>';
    h += '<img src="img/classique/hvp_check.png" ';
    h += ' style="width:130px;height:45px;float:left;" />';
    h += '<span class=typesource >filltext</span>';
    
    return h;

}

function getISVG(name,rot) {

   var retSvg = '';
   var colorA = '#6495ed';
   var transform = '';
    if (rot == 180) {
        transform = 'transform="scale(1,-1)"';
    }
    if (name =='arrow') {
        retSvg = '<svg ' + transform + ' style="width:30px;height:30px;" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" ><path fill="'+colorA+'" d="M37.533 46.789l-15.7.097 28.611-29.96L79.4 46.53l-15.7.097.205 35.196-26.167.162z"/></svg>';
    }
    if (name =='access') {
        retSvg = '<svg ' + transform + ' style="margin-left:6px;margin-top:-1px;width:25px;height:25px;" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 6 6" ><g transform="scale(.125)" fill="'+colorA+'" ><path d="M24 2a22 22 0 1 0 22 22A21.9 21.9 0 0 0 24 2zm0 40a18 18 0 1 1 18-18 18.1 18.1 0 0 1-18 18z"/><circle cx="24" cy="13" r="3"/><path d="M35 17H13a2 2 0 0 0 0 4h7v4.8l-2 9.8a2.1 2.1 0 0 0 1.6 2.4h.4a2.1 2.1 0 0 0 2-1.6l1.8-9.4h.4l1.8 9.4a2.1 2.1 0 0 0 2 1.6h.4a2.1 2.1 0 0 0 1.6-2.4l-2-9.8V21h7a2 2 0 0 0 0-4z"/></g></svg>';
    }
    if (name =='colorwheel') {
        retSvg = '<?xml version="1.0" encoding="UTF-8"?><svg style="margin-left:6px;margin-top:-1px;width:25px;height:25px;" version="1.1" viewBox="0 0 73 73" xmlns="http://www.w3.org/2000/svg" ><g transform="matrix(1.3914 0 0 1.3987 1.1137 .9272)">';
        retSvg += '<path d="m3.4085 38.255c1.1039 1.9046 2.4622 3.6782 4.0602 5.2766 4.8167 4.8163 11.22 7.4688 18.031 7.4688 6.8116 0 13.215-2.6525 18.031-7.4688 1.5984-1.5984 2.9564-3.3719 4.0602-5.2766 2.2229-3.8354 3.4085-8.2034 3.4085-12.755s-1.1856-8.9193-3.4085-12.755c-1.1039-1.9046-2.4622-3.6785-4.0602-5.2766-4.8163-4.8163-11.22-7.4688-18.031-7.4688-6.8112 0-13.215 2.6525-18.031 7.4688-1.5984 1.598-2.9568 3.3719-4.0602 5.2766-2.2229 3.8354-3.4085 8.2038-3.4085 12.755s1.1856 8.9193 3.4085 12.755zm15.584-12.755c0-1.1844 0.31945-2.2945 0.8747-3.2525 1.1268-1.9439 3.2287-3.2556 5.633-3.2556 2.4042 0 4.5062 1.3117 5.6334 3.2556 0.55486 0.95757 0.8747 2.0681 0.8747 3.2525s-0.31984 2.2945-0.8747 3.2521c-1.1268 1.9443-3.2291 3.2556-5.6334 3.2556-2.4042 0-4.5062-1.3113-5.6334-3.2556-0.55486-0.95757-0.87431-2.0677-0.87431-3.2521z" fill="#ff8398"/>';
        retSvg += '<path d="m25.5 41.504v9.4964c6.8116 0 13.215-2.6525 18.031-7.4688 1.5984-1.5984 2.9564-3.3719 4.0602-5.2766l-8.2298-4.7517c-2.7673 4.7828-7.9384 8.0007-13.862 8.0007z" fill="#54e360"/><path d="m41.504 25.5c0 2.9155-0.78014 5.6489-2.1424 8.003l8.2298 4.7517c2.2229-3.8354 3.4085-8.2034 3.4085-12.755s-1.1856-8.9193-3.4085-12.755l-8.2298 4.7513c1.3622 2.3544 2.1424 5.0879 2.1424 8.0034z" fill="#008adf"/><path d="m11.638 33.503-8.2298 4.7517c1.1039 1.9046 2.4622 3.6782 4.0602 5.2766 4.8167 4.8163 11.22 7.4688 18.031 7.4688v-9.4964c-5.9233 0-11.094-3.2178-13.862-8.0007z" fill="#ffd400"/><path d="m39.362 17.497 8.2298-4.7513c-1.1039-1.9046-2.4622-3.6785-4.0602-5.2766-4.8163-4.8163-11.22-7.4688-18.031-7.4688v9.496c5.9233 0 11.094 3.2182 13.862 8.0007z" fill="#0065a3"/><path d="m3.4085 38.255 8.2298-4.7517c-1.3622-2.354-2.142-5.0875-2.142-8.003s0.77975-5.6489 2.142-8.003l-8.2298-4.7517c-2.2229 3.8354-3.4085 8.2038-3.4085 12.755s1.1856 8.9193 3.4085 12.755z" fill="#ff9100"/><path d="m25.5 9.496v-9.496c-6.8112 0-13.215 2.6525-18.031 7.4688-1.5984 1.598-2.9568 3.3719-4.0602 5.2766l8.2298 4.7517c2.7673-4.7828 7.9384-8.001 13.862-8.001z" fill="#ff4949"/><path d="m32.008 25.5c0 1.1844-0.31984 2.2945-0.8747 3.2521l8.2283 4.7509c1.3622-2.354 2.1424-5.0875 2.1424-8.003 0-2.9159-0.78014-5.6489-2.1424-8.0034l-8.2283 4.7509c0.55486 0.95757 0.8747 2.0681 0.8747 3.2525z" fill="#0065a3"/><path d="m31.133 22.248 8.2283-4.7505c-2.7673-4.7828-7.9384-8.001-13.862-8.001v9.496c2.4042 0 4.5062 1.3117 5.6334 3.2556z" fill="#005183"/><path d="m25.5 32.008v9.496c5.9233 0 11.094-3.2178 13.862-8.0007l-8.2283-4.7505c-1.1268 1.9439-3.2291 3.2552-5.6334 3.2552z" fill="#00ab5e"/><path d="m25.5 41.504v-9.496c-2.4042 0-4.5062-1.3113-5.6334-3.2556l-8.2283 4.7509c2.7673 4.7828 7.9384 8.0007 13.862 8.0007z" fill="#ff9f04"/><path d="m18.992 25.5c0-1.1844 0.31945-2.2945 0.8747-3.2525l-8.2287-4.7505c-1.3622 2.354-2.142 5.0875-2.142 8.003s0.77975 5.6489 2.142 8.003l8.2283-4.7509c-0.55486-0.95757-0.87431-2.0677-0.87431-3.2521z" fill="#ff4b00"/><path d="m25.5 18.992v-9.496c-5.9233 0-11.094 3.2182-13.862 8.001l8.2287 4.7505c1.1268-1.9439 3.2287-3.2556 5.633-3.2556z" fill="#e80048"/></g></svg>';
    }
    if (name =='flagde') {
        retSvg = '<svg xmlns="http://www.w3.org/2000/svg" width="104" height="60" viewBox="0 0 5 3"><rect width="5" height="3" y="0" x="0" fill="#000"/><rect width="5" height="2" y="1" x="0" fill="#D00"/><rect width="5" height="1" y="2" x="0" fill="#FFCE00"/></svg>';
    }
    if (name =='flagnl') {
        retSvg = '<svg xmlns="http://www.w3.org/2000/svg" width="104" height="60" viewBox="0 0 5 3"><rect width="5" height="1" y="0" fill="#AE1C28"/><rect width="5" height="1" y="1" fill="#FFF"/><rect width="5" height="1" y="2" fill="#21468B"/></svg>';
    }
    if (name =='addbutton') {
        retSvg = '<svg height="24" width="24" style="position:relative;left:50%;margin-left:-12px;" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 27.963 27.963" xml:space="preserve"><path fill="'+colorA+'" d="M13.98 0C6.259 0 0 6.26 0 13.982s6.259 13.981 13.98 13.981c7.725 0 13.983-6.26 13.983-13.981C27.963 6.26 21.705 0 13.98 0zm7.122 16.059h-4.939v5.042h-4.299v-5.042H6.862V11.76h5.001v-4.9h4.299v4.9h4.939v4.299h.001z"/></svg>';
    }

    return retSvg;
}

function renderpluginfindwords(var1,var2) {

    var h = '<img src="icon/grid450.png" class="gridfindwordsimg" />';
    h += '<span class=typesource >findwords</span>';
    
    return h;

}

function loadAllWords(lstW) {
    
    lstW = lstW.replace(/;/g,',');
    lstW = lstW + ',,,,,,,';
    
    var ArrayWords = lstW.split(',');
    
    $('#findwordsA').val(ArrayWords[0]);
    $('#findwordsB').val(ArrayWords[1]);
    $('#findwordsC').val(ArrayWords[2]);
    $('#findwordsD').val(ArrayWords[3]);

    $('#datatext2findwords').val(lstW);

    //Trouve les mots dans la grille

}

function compilAllWords() {
    
    ratioAllWords('findwordsA');
    ratioAllWords('findwordsB');
    ratioAllWords('findwordsC');
    ratioAllWords('findwordsD');

    var lstW  = '';
    if ($('#findwordsA').val()!='') {
        lstW += $('#findwordsA').val();
    }
    if ($('#findwordsB').val()!='') {
        if (lstW!='') {lstW+=',';}
        lstW += $('#findwordsB').val();
    }
    if ($('#findwordsC').val()!='') {
        if (lstW!='') {lstW+=',';}
        lstW += $('#findwordsC').val();
    }
    if ($('#findwordsD').val()!='') {
        if (lstW!='') {lstW+=',';}
        lstW += $('#findwordsD').val();
    }

    $('#datatext2findwords').val(lstW)

}

function ratioAllWords(idstr) {

    var terminput = $('#' + idstr).val();
    terminput = terminput.replace('é','e');
    terminput = terminput.replace('è','e');
    terminput = terminput.replace('ç','e');
    terminput = terminput.replace(/[^a-zA-Z ]/g, "")
    terminput = terminput.replace(';','');
    terminput = terminput.replace(',','');
    terminput = terminput.replace(' ','');
    terminput = terminput.toUpperCase();
    $('#' + idstr).val(terminput);

}

function installConsole(){

    var location = window.location.href;

    if (location.indexOf('&console=1')!=-1) {
        
        if ($("#consoleArea").length==0) {

            var bdDiv = '<div id="consoleArea" ';
            bdDiv += ' style="position:absolute;left:20px;';
            bdDiv += 'bottom:0px;width:350px;height:150px;background-color:black;z-index:1000;" ';
            bdDiv += ' >';
            bdDiv += '<input id="consolecommand" type="text" value="" onkeypress="if (event.keyCode==13) { processcommand(); }" ';
            bdDiv += ' style="position:absolute;background-color:black;color:white;border:none;';
            bdDiv += 'left:10px;bottom:10px;width:320px;height:20px;" />';
            bdDiv += '</div>';
            $('body').append(bdDiv);

        }
        $('#consoleArea').css("display","block");
        $('#consolecommand').focus();

    }

}

function processcommand(){

    var consolecommand =  $('#consolecommand').val();
    consolecommand = consolecommand.toLowerCase()
    $('#consolecommand').val('');

    if (consolecommand=='update') {
        displayPageDevUpdate(1);
        return false;
    }
    
    if (consolecommand=='glossary') {
        displayGlossaryManager();
        return false;
    }

    if (consolecommand=='pagerender'||consolecommand=='renderpage') {
        displayPageRenderToImg();
        return false;
    }

}

$(document).ready(function(){
    installConsole();
});

function launchCustomCode() {

    if ($("#frameCustomCode").length==0) {

		var bdDiv = '<div id="frameCustomCode" class="gjs-mdl-container" >';
		bdDiv += '<div class="gjs-mdl-dialog-v2 gjs-one-bg gjs-two-color" >';		
		bdDiv += '<div class="gjs-mdl-header">';
		bdDiv += '<div class="gjs-mdl-title trd">Custom Code</div>';
		bdDiv += '<div class="gjs-mdl-btn-close" onClick="closeAllEditWindows()" data-close-modal="">⨯</div>';
		bdDiv += '</div>';

        // Textarea for CSS
        bdDiv += '<div style="padding:15px;" >';
        bdDiv += '<div class="gjs-mdl-row saveCustomCode1">';
        bdDiv += '<div class="gjs-mdl-col-12">';
        bdDiv += '<textarea id="customCodeCss" class="gjs-mdl-textarea" rows="20" spellcheck="false" ';
        bdDiv += ' style="font-size:16px;background-color:black;color:white;margin-left:1%;width:97%;" ></textarea>';
        bdDiv += '</div></div>';
        bdDiv += '<div class="gjs-mdl-row saveCustomCode1" style="text-align:right;margin-bottom:20px;" >';
        bdDiv += '<input onClick="saveCustomCode()" style="margin-top:10px;float:right;" ';
        bdDiv += ' class="gjs-one-bg ludiButtonSave trd" type="button" value="Save" /><br/>';
        bdDiv += '</div>';
		bdDiv += '<div class="gjs-mdl-collector saveCustomCode2" style="display: none">';
        bdDiv += '<p style="text-align:center;" ><br/><img src="img/cube-oe.gif" /><br/><br/></p>';
        bdDiv += '</div>';
        
		bdDiv += '</div>';

        $('body').append(bdDiv);
    }

    if ($("#frameCustomCode").length==1) {
        windowEditorIsOpen = true;
		loadaFunction();
		$('#frameCustomCode').css("display","");
        $('.saveCustomCode1').css("display","none");
        $('.saveCustomCode2').css("display","block");
        loadCustomCode();
        $('.ludimenu').css("z-index","2");
		traductAll();
	}

}

function saveCustomCode() {

    $('.saveCustomCode1').css("display","none");
    $('.saveCustomCode2').css("display","block");

    var gjsCss = $('#customCodeCss').val();
    var formData = {
        id : idPageHtml,
        customcode : gjsCss
    };
    
    $.ajax({
        url : '../ajax/save/ajax.savecustom.php?id=' + idPageHtml + '&act=save&cotk=' + $('#cotk').val(),
        type: "POST",data : formData,
        success: function(data,textStatus,jqXHR){
            window.location.href = "index.php?action=edit&id=" + parseInt(idPageHtml) + '&cotk=' + $('#cotk').val();
        },
        error: function (jqXHR, textStatus, errorThrown)
        {

        }
    });

}

function loadCustomCode() {

    var formData = {
        id : idPageHtml
    };
    $.ajax({
        url : '../ajax/save/ajax.savecustom.php?id=' + idPageHtml + '&act=read&cotk=' + $('#cotk').val(),
        type: "POST",data : formData,
        success: function(data,textStatus,jqXHR){
            $('#customCodeCss').val(data);
            $('.saveCustomCode1').css("display","block");
            $('.saveCustomCode2').css("display","none");
        },
        error: function (jqXHR, textStatus, errorThrown)
        { }
    });
}
//plug-sorttheparagraphs.js
//sorttheparagraphs

function renderpluginsorttheparagraphs(var1,var2) {
    
    var stypara = ' class="sorttheparagraphsover" ';

    if (var1=='') {
        var1 = returnTradTerm('Sort the Paragraphs');
    }
    if (var2==''&&langselectUI=='fr_FR') {
        var2 = "Étape 1,Étape 2,Étape 3,Étape 4";
    } else if (var2=='') {
        var2 = 'Step 1,Step 2,Step 3,Step 4';
    }

    var h = '<p style="font-size:18px;text-align:left;" >' + encodeTxtToHVP(var1) + '</p>';
   
    var ArrayWords = (var2 + ',,,,,,').split(',');

    if (ArrayWords[0]!='') {
        h += '<p '+stypara+'" >&nbsp;' + encodeTxtToHVP(ArrayWords[0]) + '</p>';
    }
    if (ArrayWords[1]!='') {
        h += '<p '+stypara+'" >&nbsp;' + encodeTxtToHVP(ArrayWords[1]) + '</p>';
    }
    if (ArrayWords[2]!='') {
        h += '<p '+stypara+'" >&nbsp;' + encodeTxtToHVP(ArrayWords[2]) + '</p>';
    }
    if (ArrayWords[3]!='') {
        h += '<p '+stypara+'" >&nbsp;' + encodeTxtToHVP(ArrayWords[3]) + '</p>';
    }
    if (ArrayWords[4]!='') {
        h += '<p '+stypara+'" >&nbsp;' + encodeTxtToHVP(ArrayWords[4]) + '</p>';
    }
    if (ArrayWords[5]!='') {
        h += '<p '+stypara+'" >&nbsp;' + encodeTxtToHVP(ArrayWords[5]) + '</p>';
    }

    h += '<span class=typesource >sorttheparagraphs</span>';
    
    return h;

}

function loadAllParas(lstW) {
    
    lstW = lstW.replace(/;/g,',');
    lstW = lstW + ',,,,,,,,,';
    
    var ArrayWords = lstW.split(',');
    
    $('#findParasA').val(encodeTxtToHVP(ArrayWords[0]));
    $('#findParasB').val(encodeTxtToHVP(ArrayWords[1]));
    $('#findParasC').val(encodeTxtToHVP(ArrayWords[2]));
    $('#findParasD').val(encodeTxtToHVP(ArrayWords[3]));
    $('#findParasE').val(encodeTxtToHVP(ArrayWords[4]));
    $('#findParasF').val(encodeTxtToHVP(ArrayWords[5]));
    
    $('#datatext2sorttheparagraphs').val(lstW);

    //Trouve les mots dans la grille

}

function compilAllParas() {
    
    var lstW  = '';
    if ($('#findParasA').val()!='') {
        lstW += encodeHVPToTxt($('#findParasA').val());
    }
    if ($('#findParasB').val()!='') {
        if (lstW!='') {lstW+=',';}
        lstW += encodeHVPToTxt($('#findParasB').val());
    }
    if ($('#findParasC').val()!='') {
        if (lstW!='') {lstW+=',';}
        lstW += encodeHVPToTxt($('#findParasC').val());
    }
    if ($('#findParasD').val()!='') {
        if (lstW!='') {lstW+=',';}
        lstW += encodeHVPToTxt($('#findParasD').val());
    }
    if ($('#findParasE').val()!='') {
        if (lstW!='') {lstW+=',';}
        lstW += encodeHVPToTxt($('#findParasE').val());
    }
    if ($('#findParasF').val()!='') {
        if (lstW!='') {lstW+=',';}
        lstW += encodeHVPToTxt($('#findParasF').val());
    }

    $('#datatext2sorttheparagraphs').val(lstW)

}

function renderpluginmarkwords(var1,var2) {
    
    var h = '<iframe';
    h += ' style="width:100%;height:300px;overflow:hidden;" ';
    h += ' frameBorder="0" ';
    h += ' src="oel-plug/hvpmarkthewords/hvpmarkthewords.html" ';
    h += '></iframe>';
    
    if (var1==""&&var2==""&&langselectUI=='fr_FR') {
        var1 = "Marquer les mots";
        var2 = "Un type de *question* permettant aux créatifs de créer des *défis* ";
        var2 += "où l'utilisateur doit marquer des types spécifiques de verbes dans un texte.";
    } else if (var1==""&&var2=="") {
        var1 = "Marks the Words";
        var2 = "A free based *question* type allowing creatives to create *challenges* ";
        var2 += "where the user is to mark specific types of verbs in a text.";
    }

    var2 = var2.replace(/!br!/g,'<br />');
    var2 = var2.replace(/\*/g,'starClue');
    // var regex = /starClue[a-z]*starClue/gi;
    //var regex = /starClue[a-zA-Z0-9]*starClue/gi;
    var regex = /starClue(.*?)starClue/gi;

    //var2 = var2.replace(regex,"<span style='color:blue;' >[______]</span>");
    
    var result;
    while ((result = regex.exec(var2)) !== null) {
        var fullTerm = result[0];
        var myTerm = result[1];
        var2 = var2.replace(fullTerm,"<span style='color:blue;' >" + myTerm + "</span>");
    }

    h = '<p style="font-size:18px;text-align:left;" >' +  encodeTxtToHVP(var1) + '</p>';
    h += '<p style="font-size:18px;text-align:left;" >' +  encodeTxtToHVP(var2) + '</p>';
    h += '<img src="img/classique/hvp_check.png" ';
    h += ' style="width:130px;height:45px;float:left;" />';
    h += '<span class=typesource >markwords</span>';
    
    return h;

}
function renderpluginschemasvgobj(var1,var2) {

    var h  = '';
    
    h += '<img src="' + 'img/block_diagram.svg' + '" class="svgschema" />';
    
    h += '<span class=typesource >schemasvgobj</span>';

    return h;

}

function renderpluginschemasvgobjInit(var1,var2) {

    var h = '<img src="' + 'img/block_diagram.svg' + '" class="svgschema" />';
    
    h += '<span class=typesource  >schemasvgobj</span>';

    return h;

}

function renderpluginmapsvgobj(var1,var2) {

    if (typeof var2 === "undefined") {
        var2 = '';
    }
    if (typeof var1 === "undefined") {
        var1 = '';
    }
    if (var1 == "") {
        var1 =  'img/block_plan.svg';
    }
    
    var h  = '';
    h += '<img src="' + var1 + '" class="svgschema" />';
    h += '<span class=datatext1 >' +  var1 + '</span>';
    h += '<span class=datatext2 >' +  var2 + '</span>';
    h += '<span class=typesource >mapsvgobj</span>';
    return h;

}

//schemasvgobj
function renderpluginmapsvgobjInit(var1,var2) {

    var h = '<img src="' + 'img/block_plan.svg' + '" class="svgschema" />';
    h += '<span class=datatext1 >' +  'img/block_plan.svg' + '</span>';
    h += '<span class=datatext2 >' +  var2 + '</span>';
    h += '<span class=typesource  >mapsvgobj</span>';
    return h;

}
function displayImageSchemaEdit(myObj){

	$('.ludimenu').css("z-index","2");

	var SchemaActiveObj = $(myObj);
	tmpObjDom = SchemaActiveObj;
	
	var datatext1 = '';
	var datatext2 = '';
	var datatext3 = '';
	var ObjDivhref = SchemaActiveObj.find('div');
	
	datatext1 = ObjDivhref.parent().find('span.datatext1').html();
	datatext2 = ObjDivhref.parent().find('span.datatext2').html();
	datatext3 = ObjDivhref.parent().find('span.datatext3').html();
	
	if (datatext1===undefined) {datatext1 = '';}
	if (datatext2===undefined) {datatext2 = '';}
	if (datatext3===undefined) {datatext3 = '';}

	if (datatext1==="undefined") {datatext1 = '';}
	if (datatext2==="undefined") {datatext2 = '';}
	if (datatext3==="undefined") {datatext3 = '';}
	
	if ($("#ImageSchemaEdit").length==0) {

		var bdDiv = '<div id="ImageSchemaEdit" class="gjs-mdl-container" style="" >';
		bdDiv += '<div id="areaSchemaEdit" style="min-height:230px;" ';
		bdDiv += ' class="gjs-mdl-dialog-v2 gjs-one-bg gjs-two-color" >';
		bdDiv += '<div class="gjs-mdl-header">';
		bdDiv += '<div class="gjs-mdl-title">Edition</div>';
		bdDiv += '<div class="gjs-mdl-btn-close gjs-mdl-btn-close-audio" onClick="closeAllEditWindows()" ';
		bdDiv += ' data-close-modal="">⨯</div>';
		bdDiv += '</div>';
		
		bdDiv += '<div id="iframesvgarea" class="gjs-am-add-asset" ';
		bdDiv += 'style="padding:5px;font-size:16px;position : relative;" >';
		
		bdDiv += '</div>';
		bdDiv += '</div>';

		bdDiv += '<div class="gjs-mdl-collector" style="display: none"></div>';
		bdDiv += '</div>';

		$('body').append(bdDiv);

	}

	if ($("#ImageSchemaEdit").length==1) {

		$('.ludimenu').css("z-index","2");

		strLinkString = SchemaActiveObj.attr("datahref");
		var idm = Math.floor(Math.random() * Math.floor(200));
		tmpNameDom = 'tempnode' + idm;
		SchemaActiveObj.attr("id",tmpNameDom);
        
		$('.ludimenu').css("z-index","2");
		$('#ImageSchemaEdit').css("display","");
        
		if (datatext1.indexOf('.svg')==-1) {
			loadSelectAreaSchemaSvg();
		} else {
			loadSelectAreaSchemaImage(datatext1);
		}

		windowEditorIsOpen = true;
		loadaFunction();

	}

}

//Create Render from data
function redimWorkAreaSchemaImage() {
    var bih = $("body").height() - 50;
    var biw = $("body").width() - 50;
	biw = Math.floor(biw/252);
	biw = biw * 255;
	biw = biw + 20;

    $("#areaSchemaEdit").css("height",bih + 'px');
    $("#areaSchemaEdit").css("width", biw + 'px');
    $("#areaSchemaEdit").css("max-width", biw + 'px');
    $("#frameSchemaEdit").css("min-height", parseInt(bih-50) + 'px');
    $("#toolsSchemaList").css("height", parseInt(bih-70) + 'px');
}

function loadWorkAreaSchemaImage() {

	var bdFrm = '<div id="toolsSchemaList" class="toolsSchemaList" >';

	bdFrm += '<div onClick="initImageIntoSchema(\'block_diagram.svg\');" class="objectSchemaList" >';
	bdFrm += '<img src="../custom_code/graph-schemas/block_diagram.svg" />';
	bdFrm += '</div>';

	bdFrm += '<div onClick="initImageIntoSchema(\'block_diagram_2.svg\');" class="objectSchemaList" >';
	bdFrm += '<img src="../custom_code/graph-schemas/block_diagram_2.svg" />';
	bdFrm += '</div>';

	bdFrm += '<div onClick="initImageIntoSchema(\'animate_diagram_a.svg\');" class="objectSchemaList" >';
	bdFrm += '<img src="../custom_code/graph-schemas/animate_diagram_a.svg" />';
	bdFrm += '</div>';

	bdFrm += '<div onClick="initImageIntoSchema(\'animate_diagram_b.svg\');" class="objectSchemaList" >';
	bdFrm += '<img src="../custom_code/graph-schemas/animate_diagram_b.svg" />';
	bdFrm += '</div>';

	bdFrm += '<div onClick="initImageIntoSchema(\'animate_diagram_c.svg\');" class="objectSchemaList" >';
	bdFrm += '<img src="../custom_code/graph-schemas/animate_diagram_c.svg" />';
	bdFrm += '</div>';

	bdFrm += '<div onClick="initImageIntoSchema(\'form_diagram_a.svg\');" class="objectSchemaList" >';
	bdFrm += '<img src="../custom_code/graph-schemas/form_diagram_a.svg" />';
	bdFrm += '</div>';

	bdFrm += '<div onClick="initImageIntoSchema(\'animate_diagram_d.svg\');" class="objectSchemaList" >';
	bdFrm += '<img src="../custom_code/graph-schemas/animate_diagram_d.svg" />';
	bdFrm += '</div>';

	bdFrm += '<div onClick="initImageIntoSchema(\'animate_diagram_e.svg\');" class="objectSchemaList" >';
	bdFrm += '<img src="../custom_code/graph-schemas/animate_diagram_e.svg" />';
	bdFrm += '</div>';

	bdFrm += '<div onClick="initImageIntoSchema(\'uibase-dataprocess-fab.svg\');" class="objectSchemaList" >';
	bdFrm += '<img src="../custom_code/graph-schemas/uibase-dataprocess-fab.svg" />';
	bdFrm += '</div>';

	bdFrm += '<div onClick="initImageIntoSchema(\'animate_diagram_f.svg\');" class="objectSchemaList" >';
	bdFrm += '<img src="../custom_code/graph-schemas/animate_diagram_f.svg" />';
	bdFrm += '</div>';

	bdFrm += '<div onClick="initImageIntoSchema(\'uibase-dataprocess-gears.svg\');" class="objectSchemaList" >';
	bdFrm += '<img src="../custom_code/graph-schemas/uibase-dataprocess-gears.svg" />';
	bdFrm += '</div>';

	bdFrm += '<div onClick="initImageIntoSchema(\'anim_proces_e-a.svg\');" class="objectSchemaList" >';
	bdFrm += '<img src="../custom_code/graph-schemas/anim_proces_e-a.svg" />';
	bdFrm += '</div>';
	
	bdFrm += '<div onClick="initImageIntoSchema(\'animate_diagram_g.svg\');" class="objectSchemaList" >';
	bdFrm += '<img src="../custom_code/graph-schemas/animate_diagram_g.svg" />';
	bdFrm += '</div>';

	bdFrm += '</div>';

	$('#iframesvgarea').html(bdFrm);

}

function loadSelectAreaSchemaImage(srcImage) {

	var bdFrm = '<div id="toolsSchemaList" class="toolsSchemaList" ';
	bdFrm += ' style="height:170px;text-align:center;" >';

	bdFrm += '<div onClick="initEditionSchema(\''+ srcImage +'\')" class="objectSchemaList" ';
	bdFrm += ' style="margin-left:300px;" >';
	bdFrm += '<img src="' + srcImage + '" />';
	bdFrm += '<div class="ludiEditIcoLarge" ></div>';
	bdFrm += '</div>';

	bdFrm += '</div>';

	$('#iframesvgarea').html(bdFrm);

}

function loadSelectAreaSchemaSvg() {

	loadWorkAreaSchemaImage();
	redimWorkAreaSchemaImage();
	setTimeout(function(){
		redimWorkAreaSchemaImage();
	},1000);

}

function initEditionSchema(fileurl) {

	var ur = '../vendor/svgedit/dist/editor/index.php?id=' + idPageHtmlTop + '&idpg=' + idPageHtml + '&ur=' + encodeURI(fileurl) + '&cotk=' + $('#cotk').val();
	window.location.href = ur;

}

function initImageIntoSchema(namsrc) {
	
	var fileurl = 'web_plugin|CStudio/custom_code/graph-schemas/' + namsrc;

	var neoName = 'schema-'+ LUDI.guid() + '.svg';

	var formData = {
		id : idPageHtmlTop,
		ur : encodeURI(fileurl),
		neoname : encodeURI(neoName),
	};

	$.ajax({
		url : '../ajax/ajax.upldimg.php?id=' + idPageHtmlTop+ '&ur=' + encodeURI(fileurl) + '&cotk=' + $('#cotk').val(),
		type: "POST",
		data : formData,
		success: function(data,textStatus,jqXHR) {
			if (data.indexOf("error")==-1&&data.indexOf("img_cache")!=-1) {
				loadImageIntoDoc(data,neoName);
			}
		},
		error: function (jqXHR, textStatus, errorThrown) {

		}
	});

}

function loadImageIntoDoc(dataurl,neoname) {

	var rC = '<img class="svgschema" src="' + dataurl + '" />';
	rC += '<span class=typesource >schemasvgobj</span>';
	rC += '<span class=datatext1 >' + dataurl + '</span>';

	var rH = GplugSrcT;

	if(GlobalTagGrappeObj=='div'){
		rH = GplugSrcTop + rH + GplugSrcBottom;
	}
    
	rH = rH.replace("{content}",rC);
    
	setAbstractObjContent(rH);
	
	closeAllEditWindows();

	$('.ui-widget-overlay').css("display","block");
	$('.workingProcessSave').css("display","block");
	
	setTimeout(function(){
		saveSourceFrame(false,false,0);
	
		setTimeout(function(){
			saveSourceFrame(false,false,0);
			$('.ui-widget-overlay').css("display","none");
			$('.workingProcessSave').css("display","none");
			$('.ludimenu').css("z-index","1000");
	
		},3000);
	
	},500);

}
function displayImageMapEdit(myObj){

	$('.ludimenu').css("z-index","2");

	var MapActiveObj = $(myObj);
	tmpObjDom = MapActiveObj;
	
	var datatext1 = '';
	var datatext2 = '';
	var datatext3 = '';
	var ObjDivhref = MapActiveObj.find('div');
	
	datatext1 = ObjDivhref.parent().find('span.datatext1').html();
	datatext2 = ObjDivhref.parent().find('span.datatext2').html();
	datatext3 = ObjDivhref.parent().find('span.datatext3').html();
	
	if (datatext1===undefined) {datatext1 = '';}
	if (datatext2===undefined) {datatext2 = '';}
	if (datatext3===undefined) {datatext3 = '';}

	if (datatext1==="undefined") {datatext1 = '';}
	if (datatext2==="undefined") {datatext2 = '';}
	if (datatext3==="undefined") {datatext3 = '';}
	
	if ($("#ImageMapEdit").length==0) {

		var bdDiv = '<div id="ImageMapEdit" class="gjs-mdl-container" style="" >';
		bdDiv += '<div id="areaSchemaEdit" style="min-height:230px;" ';
		bdDiv += ' class="gjs-mdl-dialog-v2 gjs-one-bg gjs-two-color" >';
		bdDiv += '<div class="gjs-mdl-header">';
		bdDiv += '<div class="gjs-mdl-title">Edition</div>';
		bdDiv += '<div class="gjs-mdl-btn-close gjs-mdl-btn-close-audio" ';
		bdDiv += ' onClick="closeAllEditWindows()" data-close-modal="">⨯</div>';
		bdDiv += '</div>';
		
		bdDiv += '<div id="mapsvgarea" class="gjs-am-add-asset" ';
		bdDiv += 'style="padding:25px;font-size:16px;position:relative;" >';
		
		bdDiv += '<p id="workingProcessMapEdit" class="workingProcessMapEdit" ><img src="img/cube-oe.gif" /></p>';

		bdDiv += '<p class="controlMapEdit" >SVG&nbsp;Schema&nbsp;:&nbsp;';
		bdDiv += '<input id="inputMapLink" type="text" value="" style="width:450px;font-size:12px;padding:5px;" />';
		bdDiv += '&nbsp;<input onClick="filterGlobalFiles=\'.svg\';showFileManagerStudio2(13,\'inputMapLink\',0);" ';;
		bdDiv += ' class="gjs-one-bg ludiButtonSave" type="button" value="..." /><br/></p>';

		bdDiv += '<p class="controlMapEdit" >Alter&nbsp;Schema&nbsp;:&nbsp;';
		bdDiv += '<input id="inputAlterImgLink" type="text" value="" style="width:450px;font-size:12px;padding:5px;" />';
		bdDiv += '&nbsp;<input onClick="filterGlobalFiles=\'.svg\';showFileManagerStudio2(13,\'inputAlterImgLink\',0);" ';;
		bdDiv += ' class="gjs-one-bg ludiButtonSave" type="button" value="..." /><br/></p>';
		
		bdDiv += '<div class="controlMapLong" >';
		bdDiv += '<div class="controlMapviewA" ></div>';
		bdDiv += '<div class="controlMapviewB" ></div>';
		bdDiv += '</div>';

		bdDiv += '<div class="maphelper" >';
		bdDiv += 'svg 1080 * 720 object map-vanneb1';
		bdDiv += '</div>';


		bdDiv += '<div class="controlMapEdit" style="padding:25px;text-align:right;" >';
		bdDiv += '<input onClick="saveMapEdit()" ';
		bdDiv += ' class="gjs-one-bg ludiButtonSave trd" type="button" value="Save" /><br/>';
		bdDiv += '</div>';

		bdDiv += '</div>';
		bdDiv += '</div>';

		bdDiv += '<div class="gjs-mdl-collector" style="display: none"></div>';
		bdDiv += '</div>';

		$('body').append(bdDiv);

	}

	if ($("#ImageMapEdit").length==1) {

		$('.ludimenu').css("z-index","2");
		
		$('.controlMapEdit').css("display","block");
		$('.controlMapLong').css("display","block");
		$('.workingProcessMapEdit').css("display","none");
		
		var idm = Math.floor(Math.random() * Math.floor(200));
		tmpNameDom = 'tempnode' + idm;
		MapActiveObj.attr("id",tmpNameDom);

		$('.ludimenu').css("z-index","2");
		$('#ImageMapEdit').css("display","");
		
		// Apply options
        $('#inputMapLink').val(datatext1);
		if (datatext1.indexOf('.svg')!=-1) {
			$('.controlMapviewA').html("<img class='imgMapoverview' src='"+datatext1+"' />");
		}
		$('#inputAlterImgLink').val(datatext2);
		if (datatext2.indexOf('.svg')!=-1) {
			$('.controlMapviewB').html("<img class='imgMapoverview' src='"+datatext2+"' />");
		}
		windowEditorIsOpen = true;
		loadaFunction();
	}

}

function saveMapEdit() {
	
	var nameMapLink = $('#inputMapLink').val();
	var nameAlterMapLink = $('#inputAlterImgLink').val();

	var renderH = renderpluginmapsvgobj(nameMapLink,nameAlterMapLink);

	$('.controlMapviewA').html("<img class='imgMapoverview' alterimg='" + nameAlterMapLink+ "' src='" + nameMapLink+ "' />");
	
	if (GlobalTagGrappeObj=='div') {
		var renderExtra = GplugSrcT.replace("{content}",renderH);
		renderH = GplugSrcTop + renderExtra + GplugSrcBottom;
	}
	if (GlobalTagGrappeObj=='table') {
		var renderExtra = GplugSrcT.replace("{content}",renderH);
		renderH =  renderExtra ;
	}

	setAbstractObjContent(renderH);
	
	saveSourceFrame(false,false,0);

	$('.controlMapEdit').css("display","none");
	$('.controlMapLong').css("display","none");
	$('.workingProcessMapEdit').css("display","block");

	setTimeout(function(){

		saveSourceFrame(false,false,0);
		
		setTimeout(function(){
			prepareMapEdit(nameMapLink);
		},750);

		setTimeout(function(){
			if (nameAlterMapLink!='') {
				prepareMapEdit(nameAlterMapLink);
			}
		},950);

		setTimeout(function(){
			closeAllEditWindows();
			$('.ludimenu').css("z-index","1000");
		},1500);

	},750);

}

function prepareMapEdit(namsrc) {
	
	$.ajax({
		url : namsrc,
		type : "GET",
		dataType : "text",
		success : function(svgCode,textStatus,jqXHR) {
			if (svgCode.indexOf("<svg")!=-1) {
				// svgCode = svgCode.replace(/<\/svg>/g,"<style></style></svg>");
				// alert(htmCode);
				var htmCode = compilMapHtml(svgCode);
				if (htmCode!='') {
					saveMapEditToRender(namsrc,svgCode,htmCode);
				}
			}
		},
		error: function (jqXHR, textStatus, errorThrown) {
			alert("error");
		}
	});

}

function saveMapEditToRender(urlfile,svgCode,htmCode) {

	var formData = {
		id : idPageHtmlTop,
		urlfile : encodeURI(urlfile),
		src : svgCode,
		htm : htmCode,
	};
	$.ajax({
		url :  _p['web_plugin'] + 'CStudio/ajax/save/ajax.svgtobase.php?cotk=' + $('#cotk').val(),
		type : "POST",
		data : formData,
		success: function(data,textStatus,jqXHR) {
			if (data.indexOf("error")==-1) {
				directToRender(urlfile);
			}
		},
		error: function (jqXHR, textStatus, errorThrown) {

		}
	});

}

function compilMapHtml(svgCode) {

    var hLayer = '';

    var svgObj = $(svgCode);

    var svgObjWidth = parseInt(svgObj.attr('width'));
    var svgObjHeight = parseInt(svgObj.attr('height'));
	
	if (isNaN(svgObjWidth)||svgObjWidth==0) {
		if (document.getElementById('imgMapoverview')) {
			var imgObj = document.getElementById('imgMapoverview');
			svgObjWidth = parseInt(imgObj.width);
			svgObjHeight = parseInt(imgObj.height);
		}
	}
	
	if (isNaN(svgObjWidth)||svgObjWidth==0) {
		svgObjWidth = 1080;
		svgObjHeight = 720;
	}

	if (svgCode!='') {
	


		svgObj.find('rect').each(function() {
			
			var aobj = $(this);
			var objid = aobj.attr('id');
			
			if (objid.indexOf('map-')!=-1) {

				var left = parseInt(aobj.attr('x'));
				var top = parseInt(aobj.attr('y'));
				var width = parseInt(aobj.attr('width'));
				var height = parseInt(aobj.attr('height'));
				var leftPourc = Math.round(((left / svgObjWidth) * 100)*10)/10;
				var topPourc = Math.round(((top / svgObjHeight) * 100)*10)/10;
				var widthPourc = Math.round(((width / svgObjWidth) * 100)*10)/10;
				var heightPourc = Math.round(((height / svgObjHeight) * 100)*10)/10;

				hLayer += '<div id="' + objid + '" ';
				hLayer += ' onClick="mapEventActive(\'' + objid + '\',' + leftPourc + ',' + topPourc + ');" ';
				hLayer += ' class="map-active" style="pointer-events:auto!important;';
				hLayer += 'position:absolute;cursor:pointer!important;border:0px solid red;';
				hLayer += 'z-index:20;left:' + leftPourc + '%;';
				hLayer += 'width:' + widthPourc + '%;height:' + heightPourc + '%;';
				hLayer += 'top:' + topPourc + '%;" ></div>';

			}

		});	
	}

    return hLayer;
    
}

function launchPageRenderToImg(){

	var loadRIP = 0;
	if (localStorage) {
		loadRIP = amplify.store("renderImgPg" + idPageHtml);
	}
	if (loadRIP==undefined||loadRIP==''||loadRIP=='undefined') {
		loadRIP = 0;
	}
	if (loadRIP==0) {
		
		if ($("#frameRenderToImgRapid").length==0) {
			var bdDiv = '<div id="frameRenderToImgRapid" ';
			bdDiv += ' style="position:absolute;left:560px;top:0px;opacity:0.02;';
			bdDiv += 'width:190px;height:190px;overflow:hidden;z-index:1500;"  >';
			bdDiv += '<iframe id="pageToImgRapid" border=0 scrolling="no" ';
			bdDiv += ' src="view-page.php?mod=page&reload=1&id=' + idPageHtml + '" ';
			bdDiv += ' ></iframe>';
			bdDiv += '</div>';
			$('body').append(bdDiv);

			setTimeout(function(){
				var bodyph = window.innerHeight - 200;
				$('#frameRenderToImgRapid').css('top',bodyph + 'px');
			},4000);

			setTimeout(function(){
				$('#frameRenderToImgRapid').css('top','-100px');
			},4500);

			setTimeout(function(){
				$('#frameRenderToImgRapid').css('top','-300px');
				if (localStorage) {
					amplify.store("renderImgPg" + idPageHtml,1);
				}
			},8000);
			
		}
	
	}

}

setTimeout(function(){
	launchPageRenderToImg();
},1500);

function displayPageRenderToImg(){
	
	saveSourceFrame(false,false,0);
	
	$('.ludimenu').css("z-index","2");

	if($("#pageRenderToImg").length==0){

		var bdDiv = '<div id="pageRenderToImg" class="gjs-mdl-container" >';
		
		bdDiv += '<div class="gjs-mdl-dialog-v2 gjs-one-bg gjs-two-color" style="max-width:575px;" >';
		bdDiv += '<div class="gjs-mdl-header">';
		bdDiv += '<div class="gjs-mdl-title trd">Render page</div>';
		bdDiv += '<div class="gjs-mdl-btn-close" onClick="closeAllEditWindows()" data-close-modal="">⨯</div>';
		bdDiv += '</div>';
		
        bdDiv += '<div class="gjs-am-add-asset" ';
        bdDiv += 'style="padding:20px;font-size:16px;padding-bottom:0px;" >';
        bdDiv += '<div style="width:200px;height:280px;overflow:hidden;margin-left:auto;margin-right:auto;"  >';
        bdDiv += '<iframe id="pageToImg" border=0 scrolling="no" src="view-page.php" ';
		bdDiv += ' style="background:white;border:solid 1px gray;height:280px;" ></iframe>';
        bdDiv += '</div><br>';
        bdDiv += '</div>';

		bdDiv += '<div class="gjs-mdl-collector" style="display: none"></div>';
		bdDiv += '</div>';

		$('body').append(bdDiv);

	}

	if($("#pageRenderToImg").length==1){
		
		windowEditorIsOpen = true;
		$('.ludimenu').css("display","none");
		$('#pageRenderToImg').css("display","");
		$('#pageToImg').attr("src","view-page.php?mod=page&reload=1&id="+idPageHtml);
		
	}

}
function displayObjectMenuExtend(toolItem) {

    var styl2 = 'style="float:left;padding:5px;margin:3px;width:44%;" ';
    
    var bdDiv2 = ''; 
    bdDiv2 += '<p ' + styl2 + ' >';
    bdDiv2 += '<input type="checkbox" onclick="onObjMenuCheckBox(this)" ';
    bdDiv2 += ' class="checkRapidWindows" id="checkObjMC" name="checkObjMC"></input>';
    bdDiv2 += '<span>' + returnTradTerm('Display on level')+'</span>&nbsp;&nbsp;';
    bdDiv2 += '<span style="position:relative;display:inline-block;width:12px;height:12px;background-color:#52BE80;"></span>';
    bdDiv2 += '</p>';

    bdDiv2 += '<p ' + styl2 + ' >';
    bdDiv2 += '<input type="checkbox" onclick="onObjMenuCheckBox(this)" ';
    bdDiv2 += ' class="checkRapidWindows" id="checkObjMD" name="checkObjMD"></input>';
    bdDiv2 += '<span>' + returnTradTerm('Hide on level')+'</span>&nbsp;&nbsp;';
    bdDiv2 += '<span style="position:relative;display:inline-block;width:12px;height:12px;background-color:#52BE80;"></span>';
    bdDiv2 += '</p>';

    bdDiv2 += '<p ' + styl2 + ' >';
    bdDiv2 += '<input type="checkbox" onclick="onObjMenuCheckBox(this)" ';
    bdDiv2 += ' class="checkRapidWindows" id="checkObjMG" name="checkObjMG"></input>';
    bdDiv2 += '<span>' + returnTradTerm('Display on level')+'</span>&nbsp;&nbsp;';
    bdDiv2 += '<span style="position:relative;display:inline-block;width:12px;height:12px;background-color:#EB984E;"></span>';
    bdDiv2 += '</p>';

    bdDiv2 += '<p ' + styl2 + ' >';
    bdDiv2 += '<input type="checkbox" onclick="onObjMenuCheckBox(this)" ';
    bdDiv2 += ' class="checkRapidWindows" id="checkObjMH" name="checkObjMH"></input>';
    bdDiv2 += '<span>' + returnTradTerm('Hide on level')+'</span>&nbsp;&nbsp;';
    bdDiv2 += '<span style="position:relative;display:inline-block;width:12px;height:12px;background-color:#EB984E;"></span>';
    bdDiv2 += '</p>';

    return bdDiv2;

}
function displayPageDevUpdate(init) {
	
    var loadV = amplify.store("versionconsult");
    if (versionCS==loadV&&init==1) {
        return false;
    }

	$('.ludimenu').css("z-index","2");
    $('.topmenuabout').css("display","none");

	if ($("#pageDevUpdate").length==0) {
		
		var bdDiv = '<div id="pageDevUpdate" style="overflow:hidden;" class="gjs-mdl-container" >';

		bdDiv += '<div class="gjs-mdl-dialog-v2 gjs-one-bg gjs-two-color" >';
		
		bdDiv += '<div class="gjs-mdl-header">';
		bdDiv += '<div class="gjs-mdl-title trd">Here you can find information about updates</div>';
		bdDiv += '<div class="gjs-mdl-btn-close closeUpdateBtn" onClick="closeUpdateWin()" data-close-modal="">⨯</div>';
		bdDiv += '</div>';

        bdDiv += '<div style="padding:15px;padding-top:0px;" >';

        bdDiv += '<img class="updateimg" src="icon/updates.png" />';

        bdDiv += '<h3>Engine Studio Updates</h3>';
        bdDiv += '<p><b>Current version: ' + versionCS + '</b></p>';
		
        bdDiv += '<div id="divUpdateLogs" class="divUpdateLogs" ></div>';

        bdDiv += '<p><input onClick="closeUpdateWin();" ';
		bdDiv += ' style="position:relative;left:50%;border:solid 1px gray;padding:7px;cursor:pointer;color:white;" ';
		bdDiv += ' class="gjs-one-bg ludiButtonSave closeUpdateBtn trd" type="button" value="&nbsp;&nbsp;OK&nbsp;&nbsp;" /></p>';

        bdDiv += '</div>';

		bdDiv += '<div class="gjs-mdl-collector" style="display: none"></div>';
 
		bdDiv += '</div>';

		$('body').append(bdDiv);

	}

	if($("#pageDevUpdate").length==1){
		$('.ludimenu').css("display","none");
		$('#pageDevUpdate').css("display","");
        getDevUpdate();
	}

}

function getDevUpdate(){

    if (typeof getListUpdate == "function") {
        $('#divUpdateLogs').html(getListUpdate()); 
    } else {
        $('#divUpdateLogs').html("No update available");
    }

    $.ajax({
		url : '../controlinstall.php?cotk=' + $('#cotk').val(),
		type: "GET",cache: false,
		success: function(data,textStatus,jqXHR){
			if (data.indexOf("is install")!=-1) {
                location.reload();
			}
		}
	});

}

function closeUpdateWin() {

    $('.closeUpdateBtn').css("display","none");

    if (localStorage) {
        amplify.store("versionconsult",versionCS);
    }

    setTimeout(function(){
            closeAllEditWindows();
    },600);

}

function getListUpdate(){
    
    var b = "";
    
     b += "<h3>Features studio 19/06/2025</h3>";
    b += "<ul>";
    b += '<li>Automatic web language detection</li>';
    b += '<li>Optimize save page and loading</li>';
    b += '<li>Add hidden terminal</li>';
    b += '<li>Fix bug</li>';
    b += "</ul>";

    b += "<h3>Features studio 25/07/2024</h3>";

    b += "<h3>Features studio 17/02/2025</h3>";
    b += "<ul>";
    b += '<li>Add Automatic language translation with Mistral AI</li>';
    b += '<li>Fix bug on text bloc</li>';
    b += "</ul>";

    b += "<h3>Features studio 25/07/2024</h3>";
    
    b += "<ul>";
    b += '<li>New page render progressbar</li>';
    b += '<li>Fix bug on text bloc</li>';
    b += '<li>Fix custom.css 404</li>';
    b += '<li>Fix undefined on index table</li>';
    b += "</ul>";

    b += "<h3>Features studio 26/03/2024</h3>";
    
    b += "<ul>";
    b += '<li>Fix position ludiSpeedTools</li>';
    b += '<li>Fix bug on new bloc content</li>';
    b += '<li>Display errors on each attempt</li>';
    b += '<li>Fix bug on copy paste docx content</li>';
    b += '<li>Clean svg object in src img tag</li>';
    b += '<li>Add TextBox style</li>';
    b += '<li>Preserve class object on loading scorm</li>';
    b += "</ul>";

    b += "<h3>Features studio 27/11/2023</h3>";

    b += "<ul>";
    b += '<li>Fix bug on export windows</li>';
    b += '<li>Fix Inactivity Timer when window editor is open</li>';
    b += '<li>Inactivity Timer 45 minutes</li>';
    b += '<li>Add SVG icons</li>';
    b += '<li>Button bar system</li>';
    b += '<li>Color selection function for buttons</li>';
    b += '<li>Custom code for Template</li>';
    b += "</ul>";

    b += "<h3>Features studio 12/09/2023</h3>";
    b += "<ul>";
    b += '<li>Add CSRF token</li>';
    b += '<li>Fix clipart loading twice</li>';
    b += '<li>Collapsed menu</li>';
    b += '<li>Fix first page option</li>';
    b += '<li>New styles for title H1</li>';
    b += "</ul>";
    
    b += "<h3>Features studio 26/04/2023</h3>";
    b += "<ul>";
    b += '<li>New avatar image in littledialog</li>';
    b += '<li>New option Custom Display for a page</li>';
    b += '<li>Make your content visible to anyone by publishing it to the web.</li>';
    b += '<li>You can link to or embed your document.</li>';
    b += '<li>Accept pptx , odp and otp files in download action</li>';
    b += "</ul>";

    b += "<h3>Features studio 17/03/2023</h3>";
    b += "<ul>";
    b += '<li>New option lifebar</li>';
    b += '<li>Show lifebar on editor</li>';
    b += '<li>Fix CMQ bug on multiple option</li>';
    b += '<li>Fix lifebar Position</li>';
    b += '<li>New object schema</li>';
    b += '<li>Increased support for xAPI</li>';
    b += '<li>Fix Context Data Resolve</li>';
    b += "</ul>";

    b += "<h3>Features studio 17/02/2023</h3>";
    b += "<ul>";
    b += '<li>Add text format alignleft alignright aligncenter alignjustify</li>';
    b += '<li>Fix Speed tools position top</li>';
    b += '<li>Add Images Library Services</li>';
    b += '<li>Fix Context Menu Position</li>';
    b += '<li>Fix Text Editor background-color</li>';
    b += '<li>Arrange Left-Bottom Menu</li>';
    b += "</ul>";
    
    b += "<h3>Features lms 18/01/2023</h3>";
    b += "<ul>";
    b += '<li>Increased support for xAPI</li>';
    b += '<li>Support of LTI Provider mode</li>';
    b += '<li>Security: Many vulnerabilities have been reported to us and swiflty and safely fixed. </li>';
    b += "</ul>";

    b += "<h3>Features studio 15/12/2022</h3>";
    b += "<ul>";
    b += '<li>Fix object.top resizeEventSco()</li>';
    b += '<li>Fix {sendlogs} on no menu mode</li>';
    b += '<li>Fix {localIdTeachdoc} on no menu mode</li>';
    b += '<li>Fix table table-cell and block on context display</li>';
    b += "</ul>";
  
    b += "<h3>Features studio 14/10/2022</h3>";
    b += "<ul>";
    b += '<li>New object Find the words</li>';
    b += '<li>New object Sort the paragraphs</li>';
    b += '<li>HVp language traductor</li>';
    b += '<li>HVp objects works in offline mode</li>';
    b += '<li>Special character in HVp are fixed</li>';
    b += '<li>Fix size of logo title in strat menu</li>';
    b += '<li>Fix scorm bug in onlineFormaPro</li>';
    b += '<li>Fix first launch in xApi interface</li>';
    b += '<li>Better support xApi interface</li>';
    b += '<li>Add a tincan.xml file in xApi package</li>';
    b += '<li>New option DCT : Adding default templates (Upper section) with list of id</li>';
    b += '<li>Fix NextPage stop</li>';
    b += '<li>Detect no used file in filemanager</li>';
    b += '<li>Delete no used file in render</li>';
    b += "</ul>";
    
    b += "<h3>Features studio 20/09/2022</h3>";
    b += "<ul>";
    b += '<li>New option DTA : Display Template Area</li>';
    b += '<li>New sub option OUT : display Only User Templates</li>';
    b += '<li>File manager accept ods, odt files with icon</li>';
    b += '<li>Automatic translate button on Donwload Select</li>';
    b += "</ul>";

    b += "<h3>Features studio 19/09/2022</h3>";
    b += "<ul>";
    b += '<li>Import ODT File for download action</li>';
    b += '<li>Better support for Scorm Package</li>';
    b += '<li>Better support for offline Package</li>';
    b += '<li>New tab log in Option page</li>';
    b += '<li>New tab cache in Option page</li>';
    b += '<li>New verbs for Cron xApi tansfert</li>';
    b += '<li>Fix print right</li>';
    b += '<li>Traduct terme in print doc</li>';
    b += "</ul>";

    b += "<h3>Features studio 15/09/2022</h3>";
    b += "<ul>";
    b += '<li>Export xApi package option</li>';
    b += '<li>Test xApi package option</li>';
    b += '<li>Fix lesson location in OffLine mode/li>';
    b += '<li>Page next bugs fix</li>';
    b += "</ul>";

    b += "<h3>Features studio 13/09/2022</h3>";
    b += "<ul>";
    b += '<li>Save page as a template</li>';
    b += '<li>Print page break bugs fix</li>';
    b += '<li>Print image bugs fix</li>';
    b += '<li>Fix relative path for video in custom_code/page-templates folder</li>';
    b += "</ul>";

    b += "<h3>Features studio 05/09/2022</h3>";
    b += "<ul>";
    b += '<li>Recovering content in case of errors</li>';
    b += '<li>Detect and Fix Content Errors in data</li>';
    b += '<li>Save page as a template</li>';
    b += '<li>Add a general page option for Studio</li>';
    b += '<li>Add a logs table option for creator</li>';
    b += '<li>Add a logs table option for learning event</li>';
    b += "</ul>";
    
    b += "<h3>Features studio 21/07/2022</h3>";
    b += "<ul>";
    b += '<li>Improved printing</li>';
    b += '<li>Move text editor to left or right</li>';
    b += '<li>Add Separator object</li>';
    b += '<li>Fix upload file progress</li>';
    b += '<li>Fix text editor for quizz bartool</li>';
    b += '<li>Multiple-Choice Quiz</li>';
    b += "</ul>";
    
    b += "<h3>Features studio 13/07/2022</h3>";
    b += "<ul>";
    b += '<li>Add Glossary Function</li>';
    b += '<li>Fix Video edition on Firefox</li>';
    b += '<li>Optimize smartphone student view</li>';
    b += '<li>Conditionnal bloc menu</li>';
    b += "</ul>";
    
    b += "<h3>Features studio 23/04/2022</h3>";
    b += "<ul>";
    b += '<li>Add French and Spannish language</li>';
    b += '<li>button : add help text</li>';
    b += '<li>Fix Image Active message bubble</li>';
    b += '<li>Optimize student view loading</li>';
    b += "</ul>";

    b += "<h3>Features studio 16/02/2022</h3>";
    b += "<ul>";
    b += '<li>Add a top menu</li>';
    b += '<li>DownloadLinks: to add Download links to all button</li>';
    b += '<li>Dynamic center menu</li>';
    b += "</ul>";

    b += "<h3>Features studio 26/01/2022</h3>";
    b += "<ul>";
    b += '<li>New home page for your elearning content</li>';
    b += '<li>Fix page edition gap</li>';
    b += '<li>Dynamic center menu</li>';
    b += '<li>Add option to export source project</li>';
    b += "</ul>";

    b += "<h3>Features lms 20/08/2021</h3>";
    b += "<ul>";
    b += '<li>LTIProvider: Add support for LTI as provider (experimental)</li>';
    b += '<li>Learnpath: Offline courses: Generate index page when exporting backup</li>';
    b += "<li>Learning path: Add progress check to avoid saving if progress is lower than before, only when 'score as progress' option is enabled</li>";
    b += "</ul>";
    
    return b;

}

setTimeout(function(){
    if (idPageHtml==idPageHtmlTop) {
        displayPageDevUpdate(1);
    }
},600);
editor.BlockManager.add('sectioncollapse',{
	label: 'Section collapse',
	attributes: {
		class: 'fa fa-text icon-plugTeach',
        style: "background-image: url('icon/sectioncollapse.png');background-repeat:no-repeat;background-position:center center;"
	},
    category: 'Basic',
    content: {
		content: '<div class=sectioncollapse >SECTION</div>',
        script: "",
		style: {
			width: '100%'
		}
	}
});

/*
editor.BlockManager.add('indextable',{
	label: 'Index Table',
	attributes: {
		class: 'fa fa-text icon-plugTeach',
        style: "background-image: url('icon/indextable.png');background-repeat:no-repeat;background-position:center center;"
	},
    category: 'Basic',
    content: {
		content: '<div class=spaceteach ></div>',
        script: "",
		style: {
			width: '100%'
		}
	}
});
*/

firstSrcT = GplugSrcT.replace("{content}",renderpluginIndextable('',''));

editor.BlockManager.add('plugTeachIndextable',{
	label: 'Index Table',
	attributes: {
		class: 'fa fa-text icon-plugTeach',
		style: "background-image: url('icon/indextable.png');background-repeat:no-repeat;background-position:center center;"
	},
	category: 'Basic',
	content: {
		content: GplugSrcTop+firstSrcT+GplugSrcBottom,script: "",
		style: {
			width: '100%',
			minHeight: '70px'
		}
	}
});

function renderpluginIndextable(var1,var2) {

    var h = '<div class="indextable" >';
    if (var1!='') {
        h += '<div class="indextabletitle" >' + var1 + '</div>';
    }
    h += getCurrentIndexTable();
    h += '</div>';
    h += '<span class=typesource >indextable</span>';
    
    return h;

}

function getCurrentIndexTable(){

    var h = '';
    var ct  = $('.ludimenuteachdoc').find('li').length;

    if (ct>0) {

        h += '<ul class="indextablelist" >';
        $('.ludimenuteachdoc li').each(function(index) {
            
            var itemLi = $(this);
            var typenode = parseInt(itemLi.attr('typenode'));
            var text = itemLi.text();

            if (text!='') {
                if (typenode==3) {
                    h += '<li>' + text + '</li>';
                } else {
                    h += '<li>*&nbsp;' + text + '</li>';
                }
            }

        });
        h += '</ul>';

    } else {

        h += '<ul class="indextablelist" >';
        h += '<li>Item 1</li>';
        h += '<li>Item 2</li>';
        h += '<li>Item 3</li>';
        h += '<li>Item 4</li>';
        h += '<li>Item 5</li>';
        h += '</ul>';
    }

    return h;

}
var processScoExport = false;
var processScoData = 0;
var processScoConfig = 0;

function displaySubExportScorm(){
	
	$('.ludimenu').css("z-index","2");

	if($("#pageEditExportScorm").length==0){
		
		var bdDiv = '<div id="pageEditExportScorm" class="gjs-mdl-container" >';
		bdDiv += '<div class="gjs-mdl-dialog-v2 gjs-one-bg gjs-two-color" style="max-width:575px;" >';
		bdDiv += '<div class="gjs-mdl-header">';
		bdDiv += '<div class="gjs-mdl-title trd ">Export to SCORM package</div>';
		bdDiv += '<div class="gjs-mdl-btn-close" onClick="closeAllEditWindows();deleteExportScorm4();processScoExport=false;" data-close-modal="">⨯</div>';
		bdDiv += '</div>';
		
		bdDiv += '<div class="gjs-am-add-asset" ';
		bdDiv += 'style="padding:25px;font-size:16px;" >';
		
		bdDiv += '<div class="progressExport" ><div class="pourcentExport" ></div></div>';
		bdDiv += '<div class="logMsgLoadSco" ><br/></div>';

		bdDiv += '<div class="finaldonwloadsco" style="padding:25px;padding-top:10px;padding-bottom:5px;text-align:center;" >';
		bdDiv += '<input ';
		bdDiv += ' class="gjs-one-bg ludiButtonCancel trd" type="button" value="Download" />';
		bdDiv += '<br/></div>';

		bdDiv += '</div>';
		bdDiv += '</div>';

		bdDiv += '<div class="gjs-mdl-collector" style="display: none"></div>';
		bdDiv += '</div>';
		$('body').append(bdDiv);

	}

	if($("#pageEditExportScorm").length==1){

		$('.ludimenu').css("display","none");
		$('#pageEditExportScorm').css("display","");
		$('.pourcentExport').css("width","1%");
		$('.pourcentExport').css("background-color","#81a2e0");
		processScoExport = true;
		processScoData = 0;
		

		var bdDow = '<input ';
		bdDow += ' class="gjs-one-bg ludiButtonCancel trd" ';
		bdDow += ' type="button" value="Download" />';
		$('.finaldonwloadsco').html(bdDow);
		
		loadaFunction();
		launchExportScorm1();
		traductAll();
		
	}

}

function displaySubExportXapi(){
	
	$('.ludimenu').css("z-index","2");

	if($("#pageEditExportXapi").length==0){
		
		var bdDiv = '<div id="pageEditExportXapi" class="gjs-mdl-container" >';
		bdDiv += '<div class="gjs-mdl-dialog-v2 gjs-one-bg gjs-two-color" style="max-width:575px;" >';
		bdDiv += '<div class="gjs-mdl-header">';
		bdDiv += '<div class="gjs-mdl-title trd ">Export to xApi package</div>';
		bdDiv += '<div class="gjs-mdl-btn-close" onClick="closeAllEditWindows();processScoExport=false;" data-close-modal="">⨯</div>';
		bdDiv += '</div>';
		
		bdDiv += '<div class="gjs-am-add-asset" ';
		bdDiv += 'style="padding:25px;font-size:16px;" >';
		
		bdDiv += '<div class="progressExport" ><div class="pourcentExport" ></div></div>';
		bdDiv += '<div class="logMsgLoadSco" ><br/></div>';
		
		bdDiv += '<div class="configXapi" style="padding:25px;padding-top:0px;padding-bottom:5px;text-align:center;" >';
		bdDiv += '<select style="padding:5px;" name="projectconfigXapi" id="projectconfigXapi" class="projectconfigXapi" >';
        bdDiv += '<option value="0" >Internal package TinCan Lib v.1</option>';
        bdDiv += '<option value="1" >Distant CS TinCan. v.1</option>';
        bdDiv += '</select>';
		bdDiv += '</div>';

		bdDiv += '<div class="finaldonwloadsco" style="padding:25px;padding-top:10px;padding-bottom:5px;text-align:center;" >';
		bdDiv += '<input ';
		bdDiv += ' class="gjs-one-bg ludiButtonCancel trd" type="button" value="Download" />';
		bdDiv += '<br/></div>';

		bdDiv += '</div>';
		bdDiv += '</div>';

		bdDiv += '<div class="gjs-mdl-collector" style="display: none"></div>';
		bdDiv += '</div>';
		$('body').append(bdDiv);

	}

	if($("#pageEditExportXapi").length==1){

		$('.ludimenu').css("display","none");
		$('#pageEditExportXapi').css("display","");
		$('.pourcentExport').css("width","1%");
		$('.pourcentExport').css("background-color","#A569BD");
		processScoExport = true;
		processScoData = 2;
		
		var bdDow = '<input onClick="launchExportScorm1();" ';
		bdDow += ' class="gjs-one-bg ludiButtonSave trd" ';
		bdDow += ' type="button" value="Create" />';
		
		$('.finaldonwloadsco').html(bdDow);

		loadaFunction();
		traductAll();
		
	}

}


function launchExportScorm1(){

	if (processScoExport==false) {
		return false;
	}

	if (processScoData==2) {
		processScoConfig = $('#projectconfigXapi').val();
		$('.configXapi').css('display','none');
		var bdDow = '<input ';
		bdDow += ' class="gjs-one-bg ludiButtonCancel trd" ';
		bdDow += ' type="button" value="....." />';
		$('.finaldonwloadsco').html(bdDow);
	}

	if (processScoData==3) {
		var bdDow = '<input ';
		bdDow += ' class="gjs-one-bg ludiButtonCancel trd" ';
		bdDow += ' type="button" value="....." />';
		$('.finaldonwloadsco').html(bdDow);
		$('.progressExport3').css('display','');
	}

	$( ".pourcentExport" ).animate({ width: "20%"
	},5500,function(){});

	$.ajax({
		url : '../ajax/export/prepare-sco.php?id=' + idPageHtmlTop+'&step=1&p='+processScoData + '&cfg=' +processScoConfig + '&cotk=' + $('#cotk').val(),
		type: "get",
		success: function(data,textStatus,jqXHR){

			if(data.indexOf("KO")==-1){
				if (processScoExport) {
					launchExportScorm2();
				}
				$( ".pourcentExport" ).stop();
				$( ".pourcentExport" ).animate({ width: "40%"
				},6500,function(){});
			}else{
				$('.logMsgLoadSco').html(data);
			}

		},
		error: function (jqXHR, textStatus, errorThrown)
		{
			alert("Error : "+textStatus);
		}
	});

}

function launchExportScorm2(){

	if(processScoExport==false) {
		return false;
	}
	$.ajax({
		url : '../ajax/export/prepare-sco.php?id=' + idPageHtmlTop+'&step=2&p='+processScoData + '&cfg=' + processScoConfig + '&cotk=' + $('#cotk').val(),
		type: "get",
		success: function(data,textStatus,jqXHR){

			if(data.indexOf("KO")==-1
				&&data.indexOf("OK 2")!=-1){
				$( ".pourcentExport" ).stop();
				$( ".pourcentExport" ).animate({ width: "60%"
				},5500, function() {});
				if (processScoExport) {
					launchExportScorm3();
				}
			}else{
				$('.logMsgLoadSco').html(data);
			}

		},
		error: function (jqXHR, textStatus, errorThrown)
		{
			alert("Error : "+textStatus);
		}
	});

}

function launchExportScorm3(){

	if(processScoExport==false) {
		return false;
	}

	$.ajax({
		url : '../ajax/export/prepare-sco.php?id=' + idPageHtmlTop+'&step=3&p='+processScoData + '&cfg=' + processScoConfig + '&cotk=' + $('#cotk').val(),
		type: "get",
		success: function(data,textStatus,jqXHR){

			if(data.indexOf("KO")==-1){
				$( ".pourcentExport" ).stop();
				$( ".pourcentExport" ).animate({ width: "70%"
				},5500, function() {});

				if (processScoExport) {
					launchExportScorm4();
				}
			}else{
				$('.logMsgLoadSco').css("display","block");
				$('.logMsgLoadSco').html(data);
			}

		},
		error: function (jqXHR, textStatus, errorThrown)
		{
			$('.logMsgLoadSco').css("display","block");
			alert("Error : "+textStatus);
		}
	});

}

function launchExportScorm4(){

	if(processScoExport==false) {
		return false;
	}
	$( ".pourcentExport" ).stop();
	$( ".pourcentExport" ).animate({ width: "90%"
	},7500,function(){});
	
	$.ajax({
		url : '../ajax/export/prepare-sco.php?id=' + idPageHtmlTop+'&step=4&p='+processScoData  + '&cfg=' + processScoConfig + '&cotk=' + $('#cotk').val(),
		type: "get",
		success: function(data,textStatus,jqXHR){

			if(data.indexOf("KO")==-1){
				
				$( ".pourcentExport" ).stop();
				$( ".pourcentExport" ).animate({
					width: "100%"
				},500, function() {});

				if (processScoData!=2) {
					deleteExportScorm4();
				}
				
				if (data.indexOf(".zip")!=-1&&data.indexOf("Warning")==-1) {
					
					var bdDiv = '<a href="'+data+'" target="_blank" ';
					bdDiv += 'style="display:inline-block;" ';
					bdDiv += ' class="gjs-one-bg ludiButtonSave trd" >Download</a>';
					
					if (processScoData==2) {
						bdDiv += '&nbsp;&nbsp;<a onClick="displaySubTestXapi();" ';
						bdDiv += 'style="display:inline-block;" ';
						bdDiv += ' class="gjs-one-bg ludiButtonSave trd" ';
						bdDiv += ' >Test xApi</a>';
					}

					$('.finaldonwloadsco').html(bdDiv);
					
				} else {

					if (data.indexOf("ontw=")!=-1&&data.indexOf("Warning")==-1&&processScoData==3){
						var bdDiv = '<input style="width:99%;padding:10px;" ';
						bdDiv += ' value="'+ data + '" />';
						$('.finaldonwloadsco').html(bdDiv);
					} else {

						var bdDiv = '<a target="_blank" ';
						bdDiv += 'style="display:inline-block;" ';
						bdDiv += ' class="gjs-one-bg ludiButtonSave trd" >Error !</a>';
						
						$('.finaldonwloadsco').html(bdDiv);
						$('.logMsgLoadSco').html(data);

					}
					

		

				}

			}else{
				$('.logMsgLoadSco').html(data);
			}
			
			traductAll();

		},
		error: function (jqXHR, textStatus, errorThrown)
		{
			alert("Error : "+textStatus);
		}
	});

}

var deleteProcess = 1

function deleteExportScorm4(){

	if (deleteProcess<9) {
	
		deleteProcess++;
	
		$.ajax({
			url : '../ajax/export/prepare-sco.php?id=' + idPageHtmlTop+'&step=10&p='+processScoData  + '&cfg=' + processScoConfig + '&cotk=' + $('#cotk').val(),
			type: "get",
			success: function(data,textStatus,jqXHR){
				deleteExportScorm4(deleteProcess);
			},
			error: function (jqXHR, textStatus, errorThrown)
			{
				alert("Error : "+textStatus);
			}
		});
		
	}
}
function displaySubToTheWeb(){
	
	$('.ludimenu').css("z-index","2");

	if ($("#pageEditToTheWeb").length==0) {
		
		var bdDiv = '<div id="pageEditToTheWeb" class="gjs-mdl-container" >';
		bdDiv += '<div class="gjs-mdl-dialog-v2 gjs-one-bg gjs-two-color" style="max-width:650px;" >';
		bdDiv += '<div class="gjs-mdl-header">';
		bdDiv += '<div class="gjs-mdl-title trd ">Publish to the web</div>';
		bdDiv += '<div class="gjs-mdl-btn-close" onClick="closeAllEditWindows();processScoExport=false;" data-close-modal="">⨯</div>';
		bdDiv += '</div>';
		
		bdDiv += '<div class="gjs-am-add-asset" ';
		bdDiv += 'style="padding:25px;font-size:16px;" >';

		bdDiv += '<p class="trd" style="text-align:center;" >Make your content visible to anyone by publishing it to the web.</p>';
		
		bdDiv += '<div class="progressExport progressExport3" style="display:none;" ><div class="pourcentExport" ></div></div>';
		bdDiv += '<div class="logMsgLoadSco" ><br/></div>';
		
		bdDiv += '<div class="finaldonwloadsco" style="padding:25px;padding-top:10px;padding-bottom:5px;text-align:center;" ></div>';

		bdDiv += '</div>';
		bdDiv += '</div>';

		bdDiv += '<div class="gjs-mdl-collector" style="display: none"></div>';
		bdDiv += '</div>';
		$('body').append(bdDiv);

	}

	if ($("#pageEditToTheWeb").length==1) {
		
		$('.ludimenu').css("display","none");
		$('.progressExport3').css('display','none');
		$('#pageEditToTheWeb').css("display","");
		$('.pourcentExport').css("width","0%");
		$('.pourcentExport').css("background-color","#28E377");
		processScoExport = true;
		processScoData = 3;
		
		var bdDow = '<input id="PublishToTheWebProcess" onClick="launchExportScorm1();" ';
		bdDow += ' class="gjs-one-bg ludiButtonSave trd" ';
		bdDow += ' type="button" value="Publish" />';
		
		$('.finaldonwloadsco').html(bdDow);

		loadaFunction();
		traductAll();
		
	}

}
function displaySubExportProject(){
	$('.ludimenu').css("z-index","2");

	if($("#pageEditExportProject").length==0){
		
		var bdDiv = '<div id="pageEditExportProject" class="gjs-mdl-container" >';
		bdDiv += '<div class="gjs-mdl-dialog-v2 gjs-one-bg gjs-two-color" style="max-width:575px;" >';
		bdDiv += '<div class="gjs-mdl-header">';
		bdDiv += '<div class="gjs-mdl-title">Export project package</div>';
		bdDiv += '<div class="gjs-mdl-btn-close" onClick="closeAllEditWindows();processScoExport=false;" data-close-modal="">⨯</div>';
		bdDiv += '</div>';
		
		bdDiv += '<div class="gjs-am-add-asset" ';
		bdDiv += 'style="padding:25px;font-size:16px;" >';
		
		bdDiv += '<div class="progressExport" ><div class="pourcentExport" ></div></div>';
		bdDiv += '<div class="logMsgLoadSco" ><br/></div>';

		bdDiv += '<div class="finaldonwloadsco" style="padding:25px;padding-top:10px;padding-bottom:5px;text-align:center;" >';
		bdDiv += '<a class="gjs-one-bg ludiButtonCancel" style="display:inline-block" >Download</a>';
		bdDiv += '<br/></div>';
		
		bdDiv += '</div>';
		bdDiv += '</div>';

		bdDiv += '<div class="gjs-mdl-collector" style="display: none"></div>';
		bdDiv += '</div>';
		$('body').append(bdDiv);

	}

	if($("#pageEditExportProject").length==1){
		$('.ludimenu').css("display","none");
		$('#pageEditExportProject').css("display","");
		$('.pourcentExport').css("width","1%");
		processScoExport = true;
        processScoData = 1;
		launchExportScorm1();
		traductAll();
		
	}

}
function directToRender(filename) {

    var formData = {
        id : idPageHtmlTop,
        ur : encodeURI(filename)
    };

    $.ajax({
        url : '../ajax/ajax.upltorender.php?id=' + formData.id + '&ur=' + formData.ur + '&cotk=' + $('#cotk').val(),
        type: "POST",data : formData,
        success: function(data,textStatus,jqXHR){
            
            if(data.indexOf("error")==-1){
              
            } else {
               
            }

        },
        error: function (jqXHR, textStatus, errorThrown)
        {
            pushImageToColl(file.url);
        }
    });

}
function displaySubTestXapi(){
	
	$('.ludimenu').css("z-index","2");

	if($("#pageEditExportTestXapi").length==0){
		
		var bdDiv = '<div id="pageEditExportTestXapi" class="gjs-mdl-container" >';
		bdDiv += '<div class="gjs-mdl-dialog-v2 gjs-one-bg gjs-two-color" style="max-width:650px;" >';
		bdDiv += '<div class="gjs-mdl-header">';
		bdDiv += '<div class="gjs-mdl-title trd ">Test xApi</div>';
		bdDiv += '<div class="gjs-mdl-btn-close" onClick="closeReturnToXapi();" data-close-modal="">⨯</div>';
		bdDiv += '</div>';
		
		bdDiv += '<div class="gjs-am-add-asset" ';
		bdDiv += 'style="padding:25px;font-size:16px;" >';
		
		bdDiv += '<div class="gjs-am-add-asset oelTitlePage areaPageExportSave" style="padding:4px;" >';
		bdDiv += '&nbsp;<span class="ludilabelform trd" >EndPoint</span>&nbsp;:&nbsp;&nbsp;';
		bdDiv += '<input id="inputLRSendpoint" type="text" value="'+ _p['web_path'] +'plugin/XApi/lrs.php?/" ';
		bdDiv += 'style="width:439px;font-size:14px;padding:4px;" />';
		bdDiv += '</div>';

        bdDiv += '<div class="gjs-am-add-asset oelTitlePage areaPageExportSave" style="padding:4px;" >';
		bdDiv += '&nbsp;<span class="ludilabelform trd" >User</span>&nbsp;:&nbsp;&nbsp;';
		bdDiv += '<input id="inputLRSuser" type="text" value="toto" ';
		bdDiv += 'style="width:162px;font-size:14px;padding:4px;" />';
        bdDiv += '&nbsp;<span class="ludilabelform trd" >Password</span>&nbsp;:&nbsp;&nbsp;';
		bdDiv += '<input id="inputLRSpassword" type="text" value="toto" ';
		bdDiv += 'style="width:162px;font-size:14px;padding:4px;" />';
		bdDiv += '</div>';

        bdDiv += '<div class="gjs-am-add-asset oelTitlePage areaPageExportSave" style="padding:4px;" >';

		bdDiv += '</div>';

		bdDiv += '<div style="padding:25px;padding-top:10px;padding-bottom:5px;text-align:center;" >';
		bdDiv += '<input onClick="launcHxApiUrl()" ';
		bdDiv += ' class="gjs-one-bg ludiButtonSave trd" type="button" value="Launch" />';
		bdDiv += '<br/></div>';

		bdDiv += '</div>';
		bdDiv += '</div>';

		bdDiv += '<div class="gjs-mdl-collector" style="display: none"></div>';
		bdDiv += '</div>';
		$('body').append(bdDiv);

	}

	if($("#pageEditExportTestXapi").length==1){

		$('.ludimenu').css("display","none");
        $('#pageEditExportXapi').css("display","none");
		$('#pageEditExportTestXapi').css("display","");

		loadaFunction();
		traductAll();
		
	}

}

function closeReturnToXapi() {
    $('#pageEditExportTestXapi').css("display","none");
    $('#pageEditExportXapi').css("display","");
}

function launcHxApiUrl() {
    
    var LRSendpoint = $('#inputLRSendpoint').val();
    var LRSuser = $('#inputLRSuser').val();
    var LRSpassword = $('#inputLRSpassword').val();

    if (LRSendpoint!='') {
        if (LRSuser!=''&&LRSpassword!='') {
            var auth = 'Basic ' + Base64.encode(LRSuser + ':' + LRSpassword);
            var link = _p['web_render_cache'] + idPageHtmlTop + "/index.html?endpoint=" + encodeURIComponent(LRSendpoint) +
            "&auth=" + encodeURIComponent(auth) +
            "&Authorization="+ encodeURIComponent(auth) +
            "&actor=" + encodeURIComponent(JSON.stringify({"actor" : {"mbox" : ["testxapi@testxapi.com"],"name" : ["testxapi"]}})) + "&tablelogs=1";
            window.open(link, '_blank').focus();

			
        }
    }

}
function displayColorsTeachEdit(){

	if($("#WinEditColorsTeach").length==0){
		
		var bdDiv = '<div id="WinEditColorsTeach" ';
		bdDiv += ' style="background-color:#7F8C8D;" ';
		bdDiv += ' class="WinEditColorsTeach" >';
		bdDiv += getTitleBar('Colors');

		bdDiv += getCollectionsColorsThemes();
		
		bdDiv += '</div>';

		$('body').append(bdDiv);

	}
    
	if($("#WinEditColorsTeach").length==1){
		saveSourceFrame(false,false,0);
		$( "#WinEditColorsTeach" ).css("display",'').css("height",'50px');
		$( "#WinEditColorsTeach" ).animate({
			height: "420px"
		}, 500, function() {
		});
		traductAll();
	}
	
}

function displayEditThemeParamsProject(){

	$('.ludimenu').css("z-index","2");

	if($("#pageEditThemeParams").length==0){
		
		var bdDiv = '<div id="pageEditThemeParams" class="gjs-mdl-container" >';
		bdDiv += '<div class="gjs-mdl-dialog-v2 gjs-one-bg gjs-two-color" ';
        bdDiv += ' style="max-width:850px;background-color:white;" >';
		bdDiv += '<div class="gjs-mdl-header" style="background-color: #E6E6E6;" >';
		bdDiv += '<div class="gjs-mdl-title">Project theme</div>';
		
        bdDiv += '<a target="_blank" onClick="closeAllEditWindows();launchCustomCode();" class="params-right" ></a>';
		
		bdDiv += '<div class="gjs-mdl-btn-close" ';
        bdDiv += ' onClick="closeAllEditWindows();processScoExport=false;" ';
        bdDiv += ' data-close-modal="">⨯</div>';
		bdDiv += '</div>';
		
		bdDiv += '<div class="gjs-am-add-asset" ';
		bdDiv += 'style="padding:25px;font-size:16px;" >';
		
        bdDiv += '<div class="innerLeftBannerTeach" >';
        bdDiv += '</div>';

		bdDiv += '<div class="innerEditTitleTeach" style="margin-left:5px;" >Page style</div>';
		bdDiv += '<div class="innerEditTitleTeach" style="margin-left:10px;">Quiz style</div>';

        bdDiv += '<div class="innerEditColorsTeach" >';
        bdDiv += getCollectionsColorsThemes();
        bdDiv += '</div>';

        bdDiv += '<div class="innerEditQuizzTeach" >';
		bdDiv += getCollectionsQuizzThemes();
        bdDiv += '</div>';

		bdDiv += '</div>';
		bdDiv += '</div>';

		bdDiv += '<div class="gjs-mdl-collector" style="display: none"></div>';
		bdDiv += '</div>';
		$('body').append(bdDiv);

	}

	if($("#pageEditThemeParams").length==1){
        
        windowEditorIsOpen = true;
		loadaFunction();
		var bodypw = $('body').outerWidth();
        if(bodypw<1050){
            $('.ludimenu').css("display","none");
        }
		$('#pageEditThemeParams').css("display","");
       
        $('.ludimenu').css("z-index","2");
		traductAll();
	}

}

function getCollectionsColorsThemes(){

	var bdDiv = '<a title="White-Blue" onClick="changColor(\'white-chami\');" ';
	bdDiv += ' class="colorCube" >';
	bdDiv += '<img src="templates/colors/white-chami.jpg" />';
	bdDiv += '</a>';

	bdDiv += '<a title="Eco-green" onClick="changColor(\'eco-chami\');" ';
	bdDiv += ' class="colorCube" >';
	bdDiv += '<img src="templates/colors/eco-chami.jpg" />';
	bdDiv += '</a>';

	bdDiv += '<a title="White-Orange" onClick="changColor(\'orange-chami\');" ';
	bdDiv += ' class="colorCube" >';
	bdDiv += '<img src="templates/colors/orange-chami.jpg" />';
	bdDiv += '</a>';
	
	bdDiv += '<a title="Classic-Paper" onClick="changColor(\'paper-chami\');" ';
	bdDiv += ' class="colorCube" >';
	bdDiv += '<img src="templates/colors/paper-chami.jpg" />';
	bdDiv += '</a>';

	bdDiv += '<a title="Office" onClick="changColor(\'office-chami\');" ';
	bdDiv += ' class="colorCube" >';
	bdDiv += '<img src="templates/colors/office-chami.jpg" />';
	bdDiv += '</a>';

	bdDiv += '<a title="Sky" onClick="changColor(\'white-sky\');" ';
	bdDiv += ' class="colorCube" >';
	bdDiv += '<img src="templates/colors/white-sky.jpg" />';
	bdDiv += '</a>';
	
	bdDiv += '<a title="hahmlet-blue" onClick="changColor(\'hahmlet-blue\');" ';
	bdDiv += ' class="colorCube" >';
	bdDiv += '<img src="templates/colors/hahmlet-blue.jpg" />';
	bdDiv += '</a>';

	bdDiv += '<a title="Sky" onClick="changColor(\'white-road\');" ';
	bdDiv += ' class="colorCube" >';
	bdDiv += '<img src="templates/colors/white-road.jpg" />';
	bdDiv += '</a>';

	return bdDiv;

}

function changColor(t){
	
	var urlNc = 'index.php?action=edit&id='+ idPageHtml +'&changc=' + t + '&cotk=' + $('#cotk').val();
	window.location.href = urlNc;

}

function getCollectionsQuizzThemes(){

	var bdDiv = '';
	bdDiv += '<a title="White-quizz" onClick="changQuizzColor(\'white-quizz\');" ';
	bdDiv += ' class="colorCube" >';
	bdDiv += '<img src="templates/quizztheme/white-quizz.png" />';
	bdDiv += '</a>';
	
	bdDiv += '<a title="Yellow-contrast" onClick="changQuizzColor(\'yellow-contrast\');" ';
	bdDiv += ' class="colorCube" >';
	bdDiv += '<img src="templates/quizztheme/yellow-contrast.png" />';
	bdDiv += '</a>';

	bdDiv += '<a title="Blue-contrast" onClick="changQuizzColor(\'blue-contrast\');" ';
	bdDiv += ' class="colorCube" >';
	bdDiv += '<img src="templates/quizztheme/blue-contrast.png" />';
	bdDiv += '</a>';
	


	return bdDiv;

}

function changQuizzColor(t){
	
	var urlNc = 'index.php?action=edit&id='+ idPageHtml +'&changquizz=' + t + '&cotk=' + $('#cotk').val();
	window.location.href = urlNc;

}

function displayParamsTeachEdit() {

	if ($("#WinEditParamsTeach").length==0) {
		
        var h = '<div id="WinEditParamsTeach" ';
		h += ' class="WinEditParamsTeach" >';
        
        h +=  '<div class="gjs-mdl-header" style="background:#808B96;color:white;padding:6px!important;" >';
        h += '<div class="gjs-mdl-title">Tools</div>';
        h += '<div class="gjs-mdl-btn-close gjs-mdl-btn-close-audio" ';
        h += ' data-close-modal=""></div>';
        h += '</div>';
        
        h += '<a class="tool-clean-data tool-base" onClick="displaySubProgressClean();" style="cursor:pointer;" >';
        h += '<div class="trd" >Clean data</div>';
        h += '</a>';
        
        // reloadEditorAll();
        
        h += '<a class="tool-play tool-base" onClick="playAllinCore();" style="cursor:pointer;" >';
        h += '<div class="trd" >Play</div>';
        h += '</a>';
        
        h += '<a class="tool-colors-editor tool-base" onClick="displayEditThemeParamsProject();" style="cursor:pointer;" >';
        h += '<div class="trd" >Colors</div>';
        h += '</a>';

        h += '<a class="tool-colors-params tool-base" onClick="displayGlobalParams();" style="cursor:pointer;" >';
        h += '<div class="trd" >Options</div>';
        h += '</a>';
        
        h += '<a id="tool-colors-paste" name="tool-colors-paste" class="tool-colors-paste tool-base" onClick="pasteWindowsShow(false);" style="cursor:pointer;" >';
        h += '<div class="trd" >Integration</div>';
        h += '</a>';
        
        h += '<a id="tool-quit" name="tool-quit" class="tool-quit tool-base" onClick="quitEditorAll();"style="cursor:pointer;" >';
        h += '<div class="trd" >Quit</div>';
        h += '</a>';
        
		h += '</div>';
        
		return h;

	}
   	
}

function playAllinCore(){

    $('#btnsave').css("display","none");
    $('#loadsave').css("display","block");
    
    saveSourceFrame(true,true,0);

}

function reloadEditorAll(){
    
    $('.workingProcessSave').css("display","");
    loadFXObjectevent = true;
    saveSourceFrame(false,false,0);
    installFakeLoad();
    setTimeout(function(){  
        var location = window.location.href;
        location = location.replace("#page0","");
        location = location.replace("#","");
        location = location.replace("&fxload=1","");
        location += "&refresh=23";
        window.location.href = location;
    },3000);

}

function quitEditorAll(){

    globalQuitAction = true;
    $('#btnsave').css("display","none");
	$('#loadsave').css("display","block");
    saveSourceFrame(true,true,0);
    
}
var startTab = 1;

function displayGlobalParams(){
	
	$('.ludimenu').css("z-index","2");
    
	if ($("#pageEditGlobalParams").length==0) {
		
		var bdDiv = '<div id="pageEditGlobalParams" class="gjs-mdl-container" >';
		bdDiv += '<div class="gjs-mdl-dialog-v2 gjs-one-bg gjs-two-color" style="max-width:575px;" >';
		bdDiv += '<div class="gjs-mdl-header">';
		bdDiv += '<div class="gjs-mdl-title trd">Project options</div>';
        
        bdDiv += '<a target="_blank" onClick="closeAllEditWindows();" href="../options/edit-options.php" class="params-right" ></a>';

		bdDiv += '<div class="gjs-mdl-btn-close" ';
        bdDiv += 'onClick="closeAllEditWindows()" data-close-modal="">⨯</div>';
		bdDiv += '</div>';
		
        bdDiv += '<div id="allParamsAreaload" class="gjs-am-add-asset" ';
		bdDiv += 'style="padding:25px;font-size:16px;" >';
        bdDiv += '<p style="text-align:center;" ><br/><img src="img/cube-oe.gif" /><br/><br/></p>';
		bdDiv += '<br/>';
		bdDiv += '</div>';

        bdDiv += '<div id="paramstablist" class="gjs-am-add-asset" ';
		bdDiv += 'style="padding:25px;padding-top:5px;padding-bottom:5px;font-size:16px;display:none;" >';
        bdDiv += '<a class="monotablist monotablist1 trd monotablistselect noselect" onclick="displayParamsTab1();" >General</a>';
        bdDiv += '<a class="monotablist monotablist2 trd noselect" onclick="displayParamsTab2();" >Navigation</a>';
        bdDiv += '<a class="monotablist monotablist3 trd noselect" onclick="displayParamsTab3();" >Interactions</a>';
        bdDiv += '</div>';

        // allParamsArea 1
		bdDiv += '<div id="allParamsArea" class="gjs-am-add-asset containBlocParams" >';
        
        bdDiv += '<p class="trd" style="position:relative;margin-left:80px;" >Project image :</p>';
        
        bdDiv += '<div ';
        bdDiv += ' style="position:absolute;left:55%;top:14px;width:30%;height:79px;';
        bdDiv += 'max-height:80px;cursor:pointer;overflow:hidden;border:solid 1px gray;" >';
        bdDiv += '<img onClick="loadAnImage();" onerror="this.src=\'img/classique/oel_back.jpg\';" id="imgshow" src="img/classique/oel_back.jpg" ';
        bdDiv += ' style="position:absolute;width:100%;height:auto;cursor:pointer;" />';
        bdDiv += '</div>';

        bdDiv += '<img onClick="initProjectImage();" src="img/bross.png" ';
        bdDiv += ' style="position:absolute;right:40px;top:15px;';
        bdDiv += 'width:22px;height:22px;cursor:pointer;" />';

        bdDiv += '<input id="dataimgglobal" style="display:none;" type="text" value="" />';

        bdDiv += '<div style="position:relative;margin:15px;margin-top:45px;" >';
        bdDiv += '<span class="trd" >&nbsp;&nbsp;Message&nbsp;page&nbsp;Ko&nbsp;:&nbsp;</span>';
        bdDiv += '<input style="position:relative;width:300px;padding:5px;" id="messageNoOk" type="text" value="" />';
        bdDiv += '</div>';

        bdDiv += '<div style="position:relative;margin:15px;" >';
        bdDiv += '<span>&nbsp;&nbsp;<span class="trd" >Language of the project</span>&nbsp;:&nbsp;</span>';
        bdDiv += '<select name="projectLangSelect" id="projectLangSelect" style="padding:5px;" class="projectLangSelect" >';
        bdDiv += '<option value="en">English</option>';
        bdDiv += '<option value="fr">Francais</option>';
        bdDiv += '<option value="es">Spanish</option>';
        bdDiv += '</select>';
        bdDiv += '</div>';
        
        if (modeUIeol=='a') {
            bdDiv += addCheckOptions('Option Accessibility Tools','I');
        }
        bdDiv += addCheckOptions('Automatic language translation','G');
        
        bdDiv += '<div class="bottomSaveParamsBloc" >';
		bdDiv += '<input onClick="saveParamsGlobal()" ';
		bdDiv += ' class="gjs-one-bg ludiButtonSave trd" type="button" value="Save" /><br/>';
		bdDiv += '</div>';

		bdDiv += '</div>';
        // allParamsArea 1

        // allParamsArea 2
		bdDiv += '<div id="allParamsArea2" class="gjs-am-add-asset containBlocParams"  >';
        
        bdDiv += addCheckOptions('Disable top button','T');
        bdDiv += addCheckOptions('Disable navigation in menu','N');
        bdDiv += addCheckOptions('Hide Menu in left','L');
        bdDiv += addCheckOptions('Each attempt restart at the first page','R');
        bdDiv += addCheckOptions('Display errors on each attempt','E');
        bdDiv += addCheckOptions('Option full screen on video','F');
        bdDiv += addCheckOptions('Disable full menu page on start','M');
        if (modeUIeol=='a') {
            bdDiv += addCheckOptions('Progressive documents','D');
            bdDiv += addCheckOptions('Thumbnail window at bottom of screen','S');
        }
        
        bdDiv += '<div class="bottomSaveParamsBloc" >';
		bdDiv += '<input onClick="saveParamsGlobal()" ';
		bdDiv += ' class="gjs-one-bg ludiButtonSave trd" type="button" value="Save" /><br/>';
		bdDiv += '</div>';

		bdDiv += '</div>';
        // allParamsArea 2

        // allParamsArea 3
		bdDiv += '<div id="allParamsArea3" class="gjs-am-add-asset containBlocParams" >';
        
        bdDiv += addCheckOptions('Add Life bar','V');

        bdDiv += '<img src="img/life_bar.png" ';
        bdDiv += ' style="position:absolute;right:90px;top:15px;" />';

        bdDiv += '<img id="editgameover" src="img/editgo.png" onClick="initEditionSchema(\'img_cache/' + lfIdent + '/gameoverscreen.svg\')" ';
        bdDiv += ' style="position:absolute;right:90px;top:117px;';
        bdDiv += 'border-radius:15px;cursor:pointer;display:none" />';

        bdDiv += '<p style="margin-left:30px;" >';
        bdDiv += '<table class="tablelifes" >';
        bdDiv += '<tr>';
        bdDiv += '<td>&nbsp;<input type="radio" name="lifebarname" id="H3" value="H3" />&nbsp;</td>';
        bdDiv += '<td>&nbsp;<input type="radio" name="lifebarname" id="H4" value="H4" />&nbsp;</td>';
        bdDiv += '<td>&nbsp;<input type="radio" name="lifebarname" id="H5" value="H5" />&nbsp;</td>';
        bdDiv += '<td>&nbsp;<input type="radio" name="lifebarname" id="H6" value="H6" />&nbsp;</td>';
        bdDiv += '<td>&nbsp;<input type="radio" name="lifebarname" id="H7" value="H7" />&nbsp;</td>';
        bdDiv += '<td>&nbsp;<input type="radio" name="lifebarname" id="H8" value="H8" />&nbsp;</td>';
        bdDiv += '</tr>';
        bdDiv += '<tr>';
        bdDiv += '<td>3</td><td>4</td><td>5</td>';
        bdDiv += '<td>6</td><td>7</td><td>8</td>';
        bdDiv += '</tr>';
        bdDiv += '</table>';
        bdDiv += '</p>';

        bdDiv += addCheckOptions('Save context game and exercise resolutions','P');
        
        bdDiv += '<div class="bottomSaveParamsBloc" >';
		bdDiv += '<input onClick="saveParamsGlobal()" ';
		bdDiv += ' class="gjs-one-bg ludiButtonSave trd" type="button" value="Save" /><br/>';
		bdDiv += '</div>';

        bdDiv += '</div>';
        // allParamsArea 3
        
		bdDiv += '<div class="gjs-mdl-collector" style="display: none"></div>';
		bdDiv += '</div>';
		$('body').append(bdDiv);

	}

	if ($("#pageEditGlobalParams").length==1) {
        windowEditorIsOpen = true;
        loadaFunction();
        getParamsGlobal();
		$('.ludimenu').css("display","none");
		$('#pageEditGlobalParams').css("display","");
	}

}

function loadAnImage(){
    filterGlobalFiles='';
    showFileManagerStudio2(13,'dataimgglobal','refreshAnImageGlobal');
}

function initProjectImage(){
    $('#dataimgglobal').val("");
    $('#imgshow').attr("src","img/classique/oel_back.jpg");
}

function refreshAnImageGlobal(){

    var imgA =$('#dataimgglobal').val();

    if (imgA==''||
    (imgA.toLowerCase().indexOf('.png')==-1
    &&imgA.toLowerCase().indexOf('.jpg')==-1
    &&imgA.toLowerCase().indexOf('.jpeg')==-1
    &&imgA.toLowerCase().indexOf('.gif')==-1
    &&imgA.toLowerCase().indexOf('cache')==-1)
    ){
        $('#dataimgglobal').val("");
        $('#imgshow').attr("src","img/classique/oel_back.jpg");
    }else{
        $('#imgshow').attr("src",$('#dataimgglobal').val());
    }
    $('.ui-widget-overlay').css("display","none");
	$('.workingProcessSave').css("display","none");
    $('.ludimenu').css("display","none");
    $('#pageEditGlobalParams').css("display","");

}

function loadOptGlobal(){

    optionsGlobalPage = optionsGlobalPage + "@@@@@@";
        
    var getObjD = optionsGlobalPage.split("@");

    var imgA = getObjD[0];
    $('#dataimgglobal').val(imgA);
    if (imgA==''||
    (imgA.toLowerCase().indexOf('.png')==-1
    &&imgA.toLowerCase().indexOf('.jpg')==-1
    &&imgA.toLowerCase().indexOf('.jpeg')==-1
    &&imgA.toLowerCase().indexOf('.gif')==-1)
    ) {
        $('#dataimgglobal').val("");
        $('#imgshow').attr("src","img/classique/oel_back.jpg");
    }else{
        $('#imgshow').attr("src",imgA);
    }
    var checkB = getObjD[1];
    if (checkB.indexOf("T")!=-1) {
        document.getElementById('checkboxT').checked = true;
    }else{
        document.getElementById('checkboxT').checked = false;
    }
    if (checkB.indexOf("N")!=-1) {
        document.getElementById('checkboxN').checked = true;
    }else{
        document.getElementById('checkboxN').checked = false;
    }
    if (checkB.indexOf("L")!=-1) {
        document.getElementById('checkboxL').checked = true;
    }else{
        document.getElementById('checkboxL').checked = false;
    }
    if (checkB.indexOf("P")!=-1) {
        document.getElementById('checkboxP').checked = true;
    }else{
        document.getElementById('checkboxP').checked = false;
    }
    if (checkB.indexOf("R")!=-1) {
        document.getElementById('checkboxR').checked = true;
    }else{
        document.getElementById('checkboxR').checked = false;
    }
    if (checkB.indexOf("F")!=-1) {
        document.getElementById('checkboxF').checked = true;
    }else{
        document.getElementById('checkboxF').checked = false;
    }
    if (checkB.indexOf("M")!=-1) {
        document.getElementById('checkboxM').checked = true;
    }else{
        document.getElementById('checkboxM').checked = false;
    }
    if (checkB.indexOf("E")!=-1) {
        document.getElementById('checkboxE').checked = true;
    }else{
        document.getElementById('checkboxE').checked = false;
    }
    if (checkB.indexOf("G")!=-1) {
        document.getElementById('checkboxG').checked = true;
    }else{
        document.getElementById('checkboxG').checked = false;
    }
    if (modeUIeol=='a') {
        if (checkB.indexOf("I")!=-1) {
            document.getElementById('checkboxI').checked = true;
        }else{
            document.getElementById('checkboxI').checked = false;
        }
        if (checkB.indexOf("D")!=-1) {
            document.getElementById('checkboxD').checked = true;
            haveprogressiveLevels = true;
        }else{
            document.getElementById('checkboxD').checked = false;
            haveprogressiveLevels = false;
        }
        if (checkB.indexOf("S")!=-1) {
            document.getElementById('checkboxS').checked = true;
        }else{
            document.getElementById('checkboxS').checked = false;
        }
    }
    if (checkB.indexOf("V")!=-1) {
        document.getElementById('checkboxV').checked = true;
        $('#editgameover').css("display","block");
    }else{
        document.getElementById('checkboxV').checked = false;
    }

    document.getElementById('H3').checked = true;
    if (checkB.indexOf("H4")!=-1) {
        document.getElementById('H4').checked = true;
    }
    if (checkB.indexOf("H5")!=-1) {
        document.getElementById('H5').checked = true;
    }
    if (checkB.indexOf("H6")!=-1) {
        document.getElementById('H6').checked = true;
    }
    if (checkB.indexOf("H7")!=-1) {
        document.getElementById('H7').checked = true;
    }
    if (checkB.indexOf("H8")!=-1) {
        document.getElementById('H8').checked = true;
    }

    var messageNoOk = getObjD[2];
    if (messageNoOk!='') {
        $('#messageNoOk').val(messageNoOk);
    } else {
        $('#messageNoOk').val('Page incomplete');
    }

    var langLST = parseTexte(getObjD[3]);
    if (langLST!='') {
        $('#projectLangSelect').val(langLST);
    }
    
}

function getParamsGlobal() {

    $('#allParamsArea').css("display","none");
    $('#allParamsArea2').css("display","none");
    $('#allParamsArea3').css("display","none");
    $('#paramstablist').css("display","none");
    $('#allParamsAreaload').css("display","");

	$.ajax({
		url : '../ajax/params/params-get.php?idteach=' + idPageHtmlTop + '&cotk=' + $('#cotk').val(),
		type: "POST",
		success: function(data,textStatus,jqXHR){
			optionsGlobalPage = data;
            setTimeout(function(){
                loadOptGlobal();
                $('#allParamsAreaload').css("display","none");
                if (startTab==3) {
                    displayParamsTab3();
                } else {
                    if (startTab==2) {
                        displayParamsTab2();
                    } else {
                        displayParamsTab1();
                    }
                }
            },100);
            
		},
		error: function (jqXHR, textStatus, errorThrown)
		{
			$('#pageEditGlobalParams').css("display","none");
            closeAllEditWindows();
		}
	});
}

function saveParamsGlobal(){

    $('#allParamsArea').css("display","none");
    $('#allParamsArea2').css("display","none");
    $('#allParamsArea3').css("display","none");
    $('#paramstablist').css("display","none");

    $('#allParamsAreaload').css("display","");
	
    var optdata = $('#dataimgglobal').val() + "@";

    if (document.getElementById('checkboxT').checked) {
		optdata += "T";
    }
    if (document.getElementById('checkboxN').checked) {
		optdata += "N";
    }
    if (document.getElementById('checkboxL').checked) {
		optdata += "L";
    }
    if (document.getElementById('checkboxP').checked) {
		optdata += "P";
    }
    if (document.getElementById('checkboxR').checked) {
		optdata += "R";
    }
    if (document.getElementById('checkboxF').checked) {
		optdata += "F";
    }
    if (document.getElementById('checkboxM').checked) {
		optdata += "M";
    }
    if (document.getElementById('checkboxE').checked) {
		optdata += "E";
    }
    if (document.getElementById('checkboxG').checked) {
		optdata += "G";
    }
    if (modeUIeol=='a') {
        if (document.getElementById('checkboxI').checked) {
            optdata += "I";
        }
        if (document.getElementById('checkboxD').checked) {
            optdata += "D";
        }
    }
    if (document.getElementById('checkboxV').checked) {
		optdata += "V";
    }

    if (document.getElementById('checkboxS')){
        if (document.getElementById('checkboxS').checked) {
            optdata += "S";
        }
    }
    
    if (document.getElementById('H3').checked) {
		optdata += "H3";
    }
    if (document.getElementById('H4').checked) {
		optdata += "H4";
    }
    if (document.getElementById('H5').checked) {
		optdata += "H5";
    }
    if (document.getElementById('H6').checked) {
		optdata += "H6";
    }
    if (document.getElementById('H7').checked) {
		optdata += "H7";
    }
    if (document.getElementById('H8').checked) {
		optdata += "H8";
    }
    
    optdata += "@";
    optdata += $('#messageNoOk').val() + "@";
    optdata += $('#projectLangSelect').val() + "@";
    
    $.ajax({
		url : '../ajax/params/params-get.php?step=1&idteach=' + idPageHtmlTop+'&opt='+optdata + '&cotk=' + $('#cotk').val(),
		type: "POST",
		success: function(data,textStatus,jqXHR){
			optionsGlobalPage = optdata;
            $('#pageEditGlobalParams').css("display","none");
            closeAllEditWindows();
            if (document.getElementById('checkboxV').checked) {
                uploadGameOverImage();
            }
            forceProcessRenderSco();
		},
		error: function (jqXHR, textStatus, errorThrown)
		{
			$('#pageEditGlobalParams').css("display","none");
            closeAllEditWindows();
		}
	});

}

function uploadGameOverImage() {

    var fileurl = 'web_plugin|CStudio/resources/gameoverscreen.svg';

	var neoName = 'gameoverscreen.svg';

	var formData = {
		id : idPageHtmlTop,
		ur : encodeURI(fileurl),
		neoname : encodeURI(neoName),
	};

	$.ajax({
		url : '../ajax/ajax.upldimg.php?id=' + idPageHtmlTop+ '&ur=' + encodeURI(fileurl) + '&cotk=' + $('#cotk').val(),
		type: "POST",
		data : formData,
		success: function(data,textStatus,jqXHR) {
            
		},
		error: function (jqXHR, textStatus, errorThrown) {

		}
	});

}

function addCheckOptions(label,code){

    var bdDiv = '<div style="position:relative;margin-left:20px;';
    bdDiv += 'width:440px;margin-bottom:4px;" >';
    bdDiv += '<label style="margin-top:1px;" class="el-switch el-switch-green" >';
    bdDiv += '<input id="checkbox'+code+'" type="checkbox" name="switch" >';
    bdDiv += '<span class="el-switch-style"></span>';
    bdDiv += '</label>';
    bdDiv += '<div class="margin-r trd" ';
    bdDiv += ' style="position:absolute;left:50px;top:0px;padding:5px;" >';
    bdDiv += '&nbsp;'+label+'</div>';
    bdDiv += '</div>';

    return bdDiv;

}

function parseTexte(str) {
	if (typeof(str) == 'undefined'){str = '';}
	if (str === null){str = '';}
	return str;
}

function displayRemoveTab() {
    $('.monotablist').removeClass("monotablistselect");
    $('#allParamsArea').css("display","none");
    $('#allParamsArea2').css("display","none");
    $('#allParamsArea3').css("display","none");
}

function displayParamsTab1() {
    startTab = 1;
    displayRemoveTab();
    $('#paramstablist').css("display","block");
    $('.monotablist1').addClass("monotablistselect");
    $('#allParamsArea').css("display","block");
}
function displayParamsTab2() {
    startTab = 2;
    displayRemoveTab();
    $('#paramstablist').css("display","block");
    $('.monotablist2').addClass("monotablistselect");
    $('#allParamsArea2').css("display","block");
}
function displayParamsTab3() {
    startTab = 3;
    displayRemoveTab();
    $('#paramstablist').css("display","block");
    $('.monotablist3').addClass("monotablistselect");
    $('#allParamsArea3').css("display","block");
}

var optionsGlobalHistory;
var modeHistory = false;

function displayGlobalHistory(){
	
	$('.ludimenu').css("z-index","2");
    
	if ($("#pageEditHistory").length==0) {
		
		var bdDiv = '<div id="pageEditHistory" class="gjs-mdl-container" >';
		bdDiv += '<div class="gjs-mdl-dialog-v2 gjs-one-bg gjs-two-color" style="max-width:575px;" >';
		bdDiv += '<div class="gjs-mdl-header">';
		bdDiv += '<div class="gjs-mdl-title trd" >History</div>';
		bdDiv += '<div class="gjs-mdl-btn-close" onClick="closeAllEditWindows()" data-close-modal="">⨯</div>';
		bdDiv += '</div>';
		
        bdDiv += '<div id="allHistoryLoad" class="gjs-am-add-asset" ';
		bdDiv += 'style="padding:25px;font-size:16px;" >';
        bdDiv += '<p style="text-align:center;" ><br/><img src="img/cube-oe.gif" /><br/><br/></p>';
		bdDiv += '<br/>';
		bdDiv += '</div>';

		bdDiv += '<div id="allHistoryArea" class="gjs-am-add-asset" ';
		bdDiv += 'style="padding:25px;font-size:16px;display:none;" >';

        bdDiv += '<div id="allHistoryTable" >';
        bdDiv += '</div>';
        
		bdDiv += '</div>';

		bdDiv += '</div>';

		bdDiv += '<div class="gjs-mdl-collector" style="display: none"></div>';
		bdDiv += '</div>';

		bdDiv += '<div id="panel-view-history" class="panel-view-history" style="display: none"></div>';
		bdDiv += '</div>';

		$('body').append(bdDiv);

	}

	if ($("#pageEditHistory").length==1) {
        
        windowEditorIsOpen = true;
		loadaFunction();
        getParamsHistory();
		$('.ludimenu').css("display","none");
		$('#pageEditHistory').css("display","");

	}

}

function getParamsHistory(){

    $('#allHistoryArea').css("display","none");
    $('#allHistoryLoad').css("display","");
    
	$.ajax({
		url : 'history_cache/get-history.php?idteach=' + idPageHtml + '&cotk=' + $('#cotk').val(),
		type: "POST",
        dataType : 'json',
		success: function(data,textStatus,jqXHR){
			optionsGlobalHistory = data;
            if(optionsGlobalHistory.history.length>0){
                installTableHistory();
                $('#allHistoryArea').css("display","");
                $('#allHistoryLoad').css("display","none");
            }else{
                $('#allHistoryArea').css("display","none");
                $('#allHistoryLoad').css("display","");
            }
		},
		error: function (jqXHR, textStatus, errorThrown)
		{
            $('#allHistoryArea').css("display","none");
            $('#allHistoryLoad').css("display","");
		}
	});

}

function installTableHistory(){
    
    var tableH = "<table class='historyTable noselect' >";
    tableH += "<thead><tr>";
    tableH += "<th>Date</th>";
    tableH += "<th>Action</th>";
    tableH += "</tr>";
    tableH += "</thead>";
    tableH += "<tbody>";
    $.each(optionsGlobalHistory.history,function(){
        tableH += "<tr>";
        tableH += "<td style='text-align:center;' >" + nameFromHistory(this.data) + "</td>";
        tableH += "<td style='text-align:center;' >";
        tableH += "<a onClick='showFileHistoryInPanel(\""+this.folder+"\",\""+this.data+"\");' ";
        tableH += " style='cursor:pointer;' >";
        tableH += "<img src='img/view.png' /></a></td>";
        tableH += "</tr>";
    });
    tableH += "</tbody>";
    tableH += "</table>";

    $('#allHistoryTable').html(tableH);
}

function showFileHistoryInPanel(f,h){

    $('#allHistoryArea').css("display","none");
    $('#allHistoryLoad').css("display","");

    $.ajax({
		url : 'history_cache/' + f + '/' + h,
		type: "POST",
		success: function(data,textStatus,jqXHR){
            data = data.replace(/ href/g," dhref");
            data += "<div class=closecross onClick='closeAllEditWindows();' ></div>"
            var dh = h.replace(".html","");
            data += "<div class='installhisto trd' onClick='changHistoryLoad(\"" + dh + "\");' >Load</div>"

            $('#panel-view-history').html(data);
            $('#panel-view-history').css("display","");
            $('#allHistoryLoad').css("display","none");
		},
		error: function (jqXHR, textStatus, errorThrown)
		{
            $('#allHistoryArea').css("display","none");
            $('#allHistoryLoad').css("display","");
		}
	});

}

function nameFromHistory(f) {
    
    f = cleText(f);
    f = f.replace(".html","");
    f = f + '-0-0-0-0';
    var getObjD = f.split("-");

    var year = parseInt(getObjD[0]);
    var month = parseInt(getObjD[1]);
    var day = parseInt(getObjD[2]);
    var hour = parseInt(getObjD[3]);
    var period = "AM";

    if (hour>12) {
        period = "PM";
        hour = hour - 12;
    }

    var  months = ["December" ,"January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
    return day + '&nbsp;' + months[month] + '&nbsp;' + year + '&nbsp;&nbsp;<small>(' + hour + ':00' + period + ")</small>";

}

function changHistoryLoad(t){
	
	var urlNc = 'index.php?action=edit&id='+ idPageHtml +'&loadh=' + t + '&cotk=' + $('#cotk').val();
	window.location.href = urlNc;

}
var optionsTemplateCss = "";

function displayEditTemplates(){
	
	$('.ludimenu').css("z-index","2");
    
	if($("#pageEditTemplates").length==0){
		
		var bdDiv = '<div id="pageEditTemplates" ';
        bdDiv += ' style="overflow:hidden;" ';
        bdDiv += ' class="gjs-mdl-container" >';
		bdDiv += '<div class="gjs-mdl-dialog-v2 gjs-one-bg gjs-two-color" style="max-width:575px;" >';
		bdDiv += '<div class="gjs-mdl-header">';
		bdDiv += '<div class="gjs-mdl-title trd">Custom style</div>';
		bdDiv += '<div class="gjs-mdl-btn-close" onClick="closeAllEditWindows()" data-close-modal="">⨯</div>';
		bdDiv += '</div>';
		
        bdDiv += '<div id="allTemplatesAreaload" class="gjs-am-add-asset" ';
		bdDiv += 'style="padding:25px;font-size:16px;" >';
        bdDiv += '<p style="text-align:center;" ><br/><img src="img/cube-oe.gif" /><br/><br/></p>';
		bdDiv += '<br/>';
		bdDiv += '</div>';

        bdDiv += '<div id="allTemplatesArea" class="gjs-am-add-asset" ';
		bdDiv += 'style="overflow:hidden;padding:25px;padding-bottom:0px;font-size:16px;display:none;" >';

        bdDiv += '</div>';

		bdDiv += '<div id="allTemplatesSave" class="gjs-am-add-asset" ';
		bdDiv += 'style="padding:25px;padding-top:0px;font-size:16px;display:none;" >';
        bdDiv += '<div style="padding:25px;padding-top:5px;padding-right:15px;text-align:right;" >';
		bdDiv += '<input onClick="saveTemplatesGlobal()" ';
		bdDiv += ' class="gjs-one-bg ludiButtonSave trd" type="button" value="Save" /><br/>';
		bdDiv += '</div>';

		bdDiv += '</div>';

		bdDiv += '</div>';

		bdDiv += '<div class="gjs-mdl-collector" style="display: none"></div>';
		bdDiv += '</div>';
		$('body').append(bdDiv);

	}

	if($("#pageEditTemplates").length==1){
        getTemplatesGlobal();
		$('.ludimenu').css("display","none");
		$('#pageEditTemplates').css("display","");
	}

}

function getTemplatesGlobal(){

    $('#allTemplatesSave').css("display","none");
    $('#allTemplatesArea').css("display","none");
    $('#allTemplatesAreaload').css("display","");

	$.ajax({
		url : '../ajax/params/template-get.php?idteach=' + idPageHtmlTop + '&cotk=' + $('#cotk').val(),
		type: "POST",
		success: function(data,textStatus,jqXHR){
			optionsTemplateCss = data;
            $('#allTemplatesSave').css("display","");
            $('#allTemplatesArea').css("display","");
            loadTemplatesGlobal();
            $('#allTemplatesAreaload').css("display","none");
		},
		error: function (jqXHR, textStatus, errorThrown)
		{
			$('#pageEditTemplates').css("display","none");
            closeAllEditWindows();
		}
	});
}

function loadTemplatesGlobal(){

    var txtArea = '<textarea withspellcheck="false" id="customcsstxt" ';
    txtArea += ' class="customcsstxt" rows="24" cols="67" >';
    txtArea += optionsTemplateCss;
    txtArea += "</textarea>";
    $('#allTemplatesArea').html(txtArea);

}

function saveTemplatesGlobal(){

    $('#allTemplatesSave').css("display","none");
    $('#allTemplatesArea').css("display","none");
    $('#allTemplatesAreaload').css("display","");

    var formData = {
        content : $('#customcsstxt').val()
    };
    $.ajax({
		url : '../ajax/params/template-get.php?step=1&idteach=' + idPageHtmlTop + '&cotk=' + $('#cotk').val(),
		type: "POST",data : formData,
		success: function(data,textStatus,jqXHR){
			optionsTemplateCss = $('#customcsstxt').val();
            $('#pageEditTemplates').css("display","none");
            closeAllEditWindows();
		},
		error: function (jqXHR, textStatus, errorThrown)
		{
			$('#pageEditTemplates').css("display","none");
            closeAllEditWindows();
		}
	});

}

function displayExportToManager(typeprint) {

	if ($("#ExportToManager").length==0) {

		var bdDiv = '<div id="ExportToManager" class="gjs-mdl-container" style="z-index:3;" >';
		bdDiv += '<div class="gjs-mdl-dialog-v2 gjs-one-bg gjs-two-color">';
		bdDiv += '<div class="gjs-mdl-header">';
		bdDiv += '<div class="gjs-mdl-title">Overview</div>';
		bdDiv += '<div class="gjs-mdl-btn-close gjs-mdl-btn-close-audio" onClick="closeProcessSlider();" ';
		bdDiv += ' data-close-modal="">⨯</div>';
		bdDiv += '</div>';
		
		bdDiv += '<div class="gjs-am-add-asset innerExportToManager" ';
		bdDiv += 'style="padding:25px;font-size:16px;background:#f6f7f7;" >';
        
		bdDiv += '</div>';

		bdDiv += '</div>';

		bdDiv += '<div class="gjs-mdl-collector" style="display: none"></div>';
		bdDiv += '</div>';

		$('body').append(bdDiv);

	}

	if ($("#ExportToManager").length==1) {

		$('.ludimenu').css("z-index","2");
		$('#ExportToManager').css("display","");
        $('#ExportToManager').css("z-index",101);
		windowEditorIsOpen = true;
        loadaFunction();
        resizeExportToManager();
        traductAll();
        getPrintExportHtml(); 
	}

}

function resizeExportToManager() {
    
    var bheight = $("body").height() -130;
    $(".innerExportToManager").css("height",bheight + "px");

}

var exportprintPageNumber = 0;

// apply Print Options
function applyPrintOptions() {

    if ($('#printPageNumber').prop("checked") == true) {
        exportprintPageNumber = 1;
    } else {
        exportprintPageNumber = 0;
    }
    
    getPrintExportHtml();

}

function getPrintExportHtml() {

    var bdD = '<p style="text-align:center;" ><br/>';
    bdD += '<img src="img/cube-oe.gif" /><br/><br/></p>';
    
    $('.innerExportToManager').html(bdD);

    var urA = '../ajax/teachdoc-print.php?generepdf=0&id=' + idPageHtmlTop ;
    urA += '&lg=' + langselectUI + '&eppn=' + exportprintPageNumber + '&cotk=' + $('#cotk').val();

    $.ajax({
        url : urA,
        type: "POST",
        success: function(data,textStatus,jqXHR){
            
            oneExportOnly = true;

            if(data.indexOf('.html')!=-1){

                var bheight = $("body").height() - 130;
                
                var h = '<iframe id="printWindows" name="printWindows" ';
                h += 'frameBorder=0 src="' + data + '" ';
                h += ' style="width:100%;height:94%;';
                h += 'z-index:1;margin-top:50px;" ';
                h += '></iframe>';

                //CheckBox
                h += '<div class="printOptionArea" >';
                h += '<input type="checkbox" id="printPageNumber"  ';
                h += ' onChange="applyPrintOptions();" ';
                h += ' class="printPageNumber noselect" name="printPageNumber" ';
                if (exportprintPageNumber==0) {
                    h += '  />';
                } else {
                    h += ' checked="checked" />';
                }
                h += '<label for="printPageNumber" >';
                h += '<span class="labelPgNumLabel noselect trd" >';
                h += 'Print document number';
                h += '</span></label></div>';
                
                h += '<a onClick="if(oneExportOnly){printFrameProcess()}" class="butonPrint" ></a>';
                
                // h += '<a id="butonPdfShowFake" class="butonPdfShow" ';
                // ' onClick="if(oneExportOnly){confirmGenerationExport();}" href="#" ></a>';
                
                //h += '<a class="butonPdfWait"  >';
                //h += '<div class="progressExportIcon" ><div class="pourcentExportIcon" ></div></div>';
                //h += '</a>';

                //h += '<a id="butonPdfShowReal" class="butonPdfShow" href="#" download ></a>';

                $('.innerExportToManager').html(h);
                
                // $('.butonPdfWait').css("display","none");
                //$('#butonPdfShowReal').css("display","none");
                //$('#butonPdfShowFake').css("display","none");

            } else {
                
                if (data.indexOf('errorwrite')!=-1) {
                    var bdD = '<p style="text-align:center;" ><br/>';
                    bdD += 'Error<br/><br/></p>';
                    $('.innerExportToManager').html(bdD);
                } else {
                    setTimeout(function(){
                        getPrintExportHtml();
                    },1000);
                }
                
            }
            
        },
        error: function (jqXHR, textStatus, errorThrown)
        {
           
            setTimeout(function(){
                getPrintExportHtml();
            },1000);
            
        }

    });
    
}

var oneExportOnly = true;

function confirmGenerationExport() {

    if (window.confirm("Are you sure you are launching an export ?")) {
        getPrintExportPdf();
    }

}

function getPrintExportPdf() {

    if (oneExportOnly) {

        $( ".pourcentExportIcon" ).css('width','1%');
        $('#butonPdfShowFake').css("display","none");
        $('.butonPdfWait').css("display","");
        var urA = '../ajax/teachdoc-print.php?generepdf=1&id=' + idPageHtmlTop + '&cotk=' + $('#cotk').val();
        oneExportOnly = false;
        $.ajax({
            url : urA,
            type: "POST",
            success: function(data,textStatus,jqXHR){
                if(data.indexOf('.pdf')!=-1){
                    oneExportOnly = true;
                    $('.butonPdfWait').css("display","none");
                    $('#butonPdfShowReal').css("display","block");
                    $('#butonPdfShowReal').attr("href",data);
                }
            },
            error: function (jqXHR, textStatus, errorThrown)
            {
            }
        });
        
        $( ".pourcentExportIcon" ).animate({ width: "99%"
        },90000,function(){});
        
    }

}

function printFrameProcess() {

    window.frames["printWindows"].focus();
    window.frames["printWindows"].print();

}

function displayExportToPdf(typeprint) {

	if ($("#ExportToPdf").length==0) {

		var bdDiv = '<div id="ExportToPdf" class="gjs-mdl-container" style="z-index:3;" >';
		bdDiv += '<div class="gjs-mdl-dialog-v2 gjs-one-bg gjs-two-color">';
		bdDiv += '<div class="gjs-mdl-header">';
		bdDiv += '<div class="gjs-mdl-title">Export</div>';
		bdDiv += '<div class="gjs-mdl-btn-close gjs-mdl-btn-close-audio" onClick="closeProcessSlider();" ';
		bdDiv += ' data-close-modal="">⨯</div>';
		bdDiv += '</div>';
		
		bdDiv += '<div class="gjs-am-add-asset innerExportToPdf" ';
		bdDiv += 'style="padding:25px;font-size:16px;background:#f6f7f7;" >';
        
		bdDiv += '</div>';

		bdDiv += '</div>';

		bdDiv += '<div class="gjs-mdl-collector" style="display: none"></div>';
		bdDiv += '</div>';

		$('body').append(bdDiv);

	}

	if ($("#ExportToPdf").length==1) {

		$('.ludimenu').css("z-index","2");
		$('#ExportToPdf').css("display","");
        $('#ExportToPdf').css("z-index",101);
		windowEditorIsOpen = true;
        loadaFunction();
        resizeExportToPdf();
        getDataPdfExportHtml();
	}

}

function resizeExportToPdf() {
    
    var bheight = $("body").height() -130;
    $(".innerExportToPdf").css("height",bheight + "px");

}

function getDataPdfExportHtml() {

    var bdD = '<p style="text-align:center;" ><br/>';
    bdD += '<img src="img/cube-oe.gif" /><br/><br/></p>';
    
    $('.innerExportToManager').html(bdD);

    var urA = '../ajax/teachdoc-print.php?generepdf=0&id=' + idPageHtmlTop + '&cotk=' + $('#cotk').val() ;
    urA += '&lg=' + langselectUI + '&eppn=' + exportprintPageNumber;
    
    $.ajax({
        url : urA,
        type: "POST",
        success: function(data,textStatus,jqXHR){
            
            oneExportOnly = true;

            if(data.indexOf('.html')!=-1){

                var bheight = $("body").height() - 130;
                
                var h = '<iframe id="printWindows" name="printWindows" ';
                h += 'frameBorder=0 src="' + data + '" ';
                h += ' style="width:100%;height:94%;';
                h += 'z-index:1;margin-top:50px;" ';
                h += '></iframe>';

                //CheckBox
                h += '<div class="printOptionArea" >';
                h += '<input type="checkbox" id="printPageNumber"  ';
                h += ' onChange="applyPrintOptions();" ';
                h += ' class="printPageNumber noselect" name="printPageNumber" ';
                if (exportprintPageNumber==0) {
                    h += '  />';
                } else {
                    h += ' checked="checked" />';
                }
                h += '<label for="printPageNumber" >';
                h += '<span class="labelPgNumLabel noselect trd" >';
                h += 'Print document number';
                h += '</span></label></div>';
                
                h += '<a onClick="if(oneExportOnly){printFrameProcess()}" class="butonPrint" ></a>';
           

                $('.innerExportToPdf').html(h);
                
            } else {
                
                if (data.indexOf('errorwrite')!=-1) {
                    var bdD = '<p style="text-align:center;" ><br/>';
                    bdD += 'Error<br/><br/></p>';
                    $('.innerExportToPdf').html(bdD);
                } else {
                    setTimeout(function(){
                        getDataPdfExportHtml();
                    },1000);
                }
                
            }
            
        },
        error: function (jqXHR, textStatus, errorThrown)
        {
           
            setTimeout(function(){
                getDataPdfExportHtml();
            },1000);
            
        }

    });
    
}

var typeFiletManaged = '';
var srcInputManaged = '';
var ftcBackManaged = '';
var dataSelectImgManaged = '';
var selectFileNameManaged = '';

//showFileManagerStudio(13,'activeimg3','refreshAnActiveImageGlobal');

function showFileManagerStudio2(typeFile,idF,ftcBack) {
    
    srcInputManaged = idF;
    ftcBackManaged = ftcBack;
    typeFiletManaged = typeFile;
    
    // Option dialogue
    if (ftcBack=='refreshMyAvatarDia') {

    }
    displayFileManagerSlider(typeFile);
    
}

function displayFileManagerSlider(typeFile) {

	if ($("#FileManagerStudio").length==0) {

		var bdDiv = '<div id="FileManagerStudio" class="gjs-mdl-container" style="z-index:3;" >';
		bdDiv += '<div class="gjs-mdl-dialog-v2 gjs-one-bg gjs-two-color">';
		bdDiv += '<div class="gjs-mdl-header">';
		bdDiv += '<div class="gjs-mdl-title">File manager</div>';
		bdDiv += '<div class="gjs-mdl-btn-close gjs-mdl-btn-close-audio" onClick="closeProcessSlider()" ';
		bdDiv += ' data-close-modal="">⨯</div>';
		bdDiv += '</div>';
		
		bdDiv += '<div class="gjs-am-add-asset innerFileManagerStudio" ';
		bdDiv += 'style="padding:25px;font-size:16px;background:#f6f7f7;" >';
        bdDiv += innerFileManagerSlider();
		bdDiv += '</div>';
        
		bdDiv += '</div>';

		bdDiv += '<div class="gjs-mdl-collector" style="display: none"></div>';
		bdDiv += '</div>';

		$('body').append(bdDiv);

	}

	if ($("#FileManagerStudio").length==1) {

		$('.ludimenu').css("z-index","2");
		$('#FileManagerStudio').css("display","");
        $('#FileManagerStudio').css("z-index",101);
        
        $('.media-alarm').css("display",'none');

        $('.attachments-wrapper').css("display","block");
        $('.attachments-wrapper-files').css("display","none");
        $('.attachments-wrapper-clipart').css("display","none");
        $('.attachments-wrapper-upload').css("display","none");

        // Button
        if (typeFile==13||typeFile==23) {
            $('.select-process-slider').css("display","");
        } else {
            $('.select-process-slider').css("display","none");
        }
        
        var collDat = '';
        for(var itemImg in baseCollImgs) {
            collDat += createThumbAccessSlider(baseCollImgs[itemImg].src,0,'',baseCollImgs[itemImg].usefile,1);
        }
        $('.attachments-wrapper').html(collDat);
        
        if (typeFile==23) {
            $('.attachments-wrapper').css("display","none");
            $('.attachments-wrapper-upload').css("display","none");
            $('.attachments-wrapper-clipart').css("display","none");
            $('.attachments-wrapper-files').css("display","block");
            loadDataFileTab();
        }

        if (srcInputManaged=='imgprocessclipart') {
            directClipArtFileInStudio();
        }

		windowEditorIsOpen = true;
        loadaFunction();
        resizeFileManagerSlider();
        traductAll();
        loadInfosMedias();
        loadButtonSaveMedia();
        $('.bloc-img-manager').css("display","");
        initUploadFileInStudio();

	}

}

function resizeFileManagerSlider() {
    
    var bheight = $("body").height() -130;
    $(".innerFileManagerStudio").css("height",bheight + "px");

}

function innerFileManagerSlider() {

    var bdDiv = '<div class="media-frame-menu" >';
    bdDiv += '<div class="media-menu">';
    bdDiv += '<a onClick="preSelectFileTab();loadDataFileTab();" class="media-menu-item menu-insert trd" >Project files</a>';
    bdDiv += '<div role="presentation" class="separator"></div>';
    
    bdDiv += '<a onClick="directClipArtFileInStudio();" ';
    bdDiv += 'class="media-menu-item menu-clipart trd" >Image library</a>';

    if (modeUIeol=='b') {
        bdDiv += '<a onClick="uploadFileInSlider();" ';
        bdDiv += 'class="media-menu-item menu-chamilo trd" >Direct Upload</a>';
    } else {
        bdDiv += '<a onClick="directUploadFileInStudio();" ';
        bdDiv += 'class="media-menu-item menu-direct trd" >Direct Upload</a>';
    }

    bdDiv += '<div onMouseOver="hideMediaAlarm()" onClick="hideMediaAlarm()" class="media-alarm trad "></div>';

    bdDiv += '<div class="media-details">';

    bdDiv += '<div class="mediainfos-title mediainfos-line">---</div>';
    bdDiv += '<div class="mediainfos-width mediainfos-line">---</div>';
    bdDiv += '<div class="mediainfos-height mediainfos-line">---</div>';

    bdDiv += '<div class="mediainfos-delete"></div>';

    bdDiv += '</div>';

    bdDiv += '</div>';
    bdDiv += '</div>';

    bdDiv += '<div class="attachments-wrapper ludiscroolminimal">';
    bdDiv += '</div>';

    bdDiv += '<div id="attachments-wrapper-files" class="attachments-wrapper-files ludiscroolminimal">';
    bdDiv += '<p style="text-align:center;" ><br/><img src="img/cube-oe.gif" /><br/><br/></p>';
    bdDiv += '</div>';

    bdDiv += '<div id="attachments-wrapper-clipart" class="attachments-wrapper-clipart ludiscroolminimal">';
    bdDiv += '<p style="text-align:center;" ><br/><img src="img/cube-oe.gif" /><br/><br/></p>';
    bdDiv += '</div>';

    bdDiv += '<div id="attachments-wrapper-upload" class="attachments-wrapper-upload ludiscroolminimal">';
    bdDiv += '<p style="text-align:center;" ><br/><img src="img/cube-oe.gif" /><br/><br/></p>';
    bdDiv += '</div>';

    bdDiv += '<input id="urlFileInSlider" style="display:none;" />';

    bdDiv += '<div class="select-process-slider" >';

    bdDiv += '</div>';

    return bdDiv;

}

function loadButtonSaveMedia(){

    var bdDiv = '<input onClick="selectProcessSlider()" ';
    bdDiv += ' class="gjs-one-bg ludiButtonSave" type="button" value="Select" />';
    $('.select-process-slider').html(bdDiv);

}

function loadInfosMedias(usefile) {

    if (dataSelectImgManaged=='') {
        
        $(".mediainfos-title").html("-.-.-");
        $(".mediainfos-width").html("-.-.-");
        $(".mediainfos-height").html("-.-.-");
        $(".mediainfos-delete").html("");

    } else {

        $(".mediainfos-title").html('<strong><u>'+getFileNameByUrl(dataSelectImgManaged)+'</u></strong>');

        if (isImageFile(dataSelectImgManaged)) {
                var img = new Image();
            img.onload = function() {
                $(".mediainfos-width").html("width : " + this.width + "px");
                $(".mediainfos-height").html("height : " + this.height + "px");
            };
            img.src = dataSelectImgManaged;
        }

        if (dataSelectImgManaged.indexOf('.mp3')!=-1) {
            $(".mediainfos-width").html("-.-.-");
            $(".mediainfos-height").html("-.-.-");
        }
        if (dataSelectImgManaged.indexOf('.mp4')!=-1) {
            $(".mediainfos-width").html("-.-.-");
            $(".mediainfos-height").html("-.-.-");
        }
        if (dataSelectImgManaged.indexOf('.pdf')!=-1) {
            $(".mediainfos-width").html("-.-.-");
            $(".mediainfos-height").html("-.-.-");
        }
        if (dataSelectImgManaged.indexOf('img/classique')==-1
            &&dataSelectImgManaged.indexOf('img_cache')!=-1 ) {
            if (usefile==0) {
                var imgc = '<a onclick="deleteOneFileManger()" style=""><img src="icon/delete-icon-24.png" /></a>';
                $(".mediainfos-delete").html(imgc);
            } else {
                $(".mediainfos-delete").html("");
            }
        } else {
            $(".mediainfos-delete").html("");
        }
       
    }
    
}

function deleteOneFileManger() {

    if (dataSelectImgManaged=='') {

        $(".mediainfos-delete").html('<span>!</span>');
        
    } else {

        $(".mediainfos-delete").html('<img style="margin:4px;" src="img/loadsave.gif" />');

        var formData = {
            id : idPageHtmlTop,
            ur : encodeURI(dataSelectImgManaged)
        };

        $.ajax({
            url : '../ajax/ajax.del-img.php?id=' + formData.id + '&ur=' + formData.ur + '&cotk=' + $('#cotk').val(),
            type: "POST",data : formData,
            success: function(data,textStatus,jqXHR){

                //alert(data);

                if(data.indexOf("error")==-1&&data.indexOf("KO")==-1&&data.indexOf("OK")!=-1){
                    
                    $(".bloc-img-manager").each(function(index){
                        var obj = $(this);
                        var datasrc = obj.attr('datasrc');
                        if (datasrc==dataSelectImgManaged) {
                            obj.css('display','none');
                        }
                    });

                    $(".mediainfos-delete").html('<span></span>');
                    
                    dataSelectImgManaged = '';
                    selectFileNameManaged = '';
                    
                    loadInfosMedias();

                } else {
                    
                    showMediaAlarm('Error');

                }
                
            },
            error: function (jqXHR, textStatus, errorThrown)
            {
                pushImageToColl(file.url);
            }
        });

    }

}

function selectThumbSlider(obj,usefile) {

    var objS = $(obj);
    $(".bloc-img-view").removeClass("bloc-img-view-active");
    objS.find(".bloc-img-view").addClass("bloc-img-view-active");
    dataSelectImgManaged = objS.attr("dataSrc");
    loadInfosMedias(usefile);
}

//Save image
function selectProcessSlider() {
    
    if (dataSelectImgManaged=='') {
        return false;
    }

    if (dataSelectImgManaged.indexOf('cliparts-service')!=-1) {

        var bdLoad = '<p style="text-align:center;" >';
        bdLoad += '<br/><img src="img/cube-oe.gif" />';
        bdLoad += '<br/><br/></p>';
        $('.attachments-wrapper-clipart').html(bdLoad);

        var formData = {
            id : idPageHtmlTop,
            ur : encodeURI(dataSelectImgManaged)
        };
        $.ajax({
            url : '../ajax/ajax.upldimg.php?id=' + formData.id + '&ur=' + formData.ur + '&cotk=' + $('#cotk').val(),
            type : "POST",
            data : formData,
            success: function(data,textStatus,jqXHR){
                if(data.indexOf("error")==-1){

                    dataSelectImgManaged = '';

                    if(data.indexOf("img_cache")!=-1){
                        var cleanData = data.replace("img_cache/", "");
                        $('#'+srcInputManaged).val(cleanData);
                        dataSelectImgManaged = cleanData;
                    }else{
                        $('#'+srcInputManaged).val(formData.url);
                        dataSelectImgManaged = formData.url;
                    }    
                    if (ftcBackManaged!=''&&ftcBackManaged!=0) {
                        window[ftcBackManaged]();
                    }
                    $('#FileManagerStudio').css("display","none");

                    $(".gjs-am-assets").find(".gjs-am-asset-image").first().trigger("click");
                   
                    var globDiv = $("#chamiloImages").parent().parent().parent().parent().parent().parent();
                    globDiv.find(".gjs-mdl-header").find(".gjs-mdl-btn-close").first().trigger("click");

                }

            },
            error: function (jqXHR, textStatus, errorThrown)
            {
              
            }
        });

        return false;

    }

    if (typeFiletManaged==13) {
        $('#'+srcInputManaged).val(dataSelectImgManaged);
        if (ftcBackManaged!=''&&ftcBackManaged!=0) {
            window[ftcBackManaged]();
        }
    }
    
    //Download
    if (typeFiletManaged==23) {
        $('#'+srcInputManaged).val(dataSelectImgManaged);
        if (ftcBackManaged!=''&&ftcBackManaged!=0) {
            window[ftcBackManaged]();
        }
        directToRender(dataSelectImgManaged);
    }
    
    $('#FileManagerStudio').css("display","none");

}

function closeProcessSlider() {
    
    if (typeFiletManaged==13) {
        $('#FileManagerStudio').css("display","none");
    } else {
        closeAllEditWindows();
    }

}

//UPLOAD FROM CHAMILO
function uploadFileInSlider() {
    
    if (typeFiletManaged==13) {

        $('.media-menu-item').removeClass("media-menu-active");
        $('.menu-chamilo').addClass("media-menu-active");

        showFileManagerStudio(13,'urlFileInSlider','refreshAfterAnUpload');
        
        $('.attachments-wrapper').css("display","block");
        $('.attachments-wrapper-files').css("display","none");
        $('.attachments-wrapper-clipart').css("display","none");
        $('.attachments-wrapper-upload').css("display","none");
    
    }

    if (typeFiletManaged==23) {

        $('.media-menu-item').removeClass("media-menu-active");
        $('.menu-chamilo').addClass("media-menu-active");

        showFileManagerStudio(13,'urlFileInSlider','refreshAfterAnUpload');
        
        $('.attachments-wrapper').css("display","none");
        $('.attachments-wrapper-files').css("display","block");
        $('.attachments-wrapper-clipart').css("display","none");
        $('.attachments-wrapper-upload').css("display","none");
    
    }

    $('.media-alarm').css("display",'none');

}

function showMediaAlarm(m){
    $('.media-alarm').css("display",'block');
    $('.media-alarm').css("left",'10px');
    $('.media-alarm').animate({
        left: '205px'
    },500);
    $('.media-alarm').html(m);
}

function hideMediaAlarm() {

    $('.media-alarm').animate({
        left: "0px"
    },300,function(){
        $('.media-alarm').css("display",'none');
    });

}

function pushToCollAfterSelect() {

    var fileurl = dataSelectImgManaged;

    if (getAutorizedExtends(fileurl)) {
        if (isImageFile(fileurl) ) {
            if (isImageStudio(fileurl)) {
                pushImageToColl(fileurl);
            }
        }
    }

}

//UPLOAD DIRECT
function directUploadFileInStudio() {

    $('.media-menu-item').removeClass("media-menu-active");
    $('.menu-direct').addClass("media-menu-active");

    $('.attachments-wrapper').css("display","none");
    $('.attachments-wrapper-files').css("display","none");
    $('.attachments-wrapper-clipart').css("display","none");
    $('.attachments-wrapper-upload').css("display","block");

}

function initUploadFileInStudio() {
    
    setTimeout(function(){
        var sty = "style='overflow:hidden;margin:10px;margin-left:5%;margin-right:5%;width:90%;'";
        $('.attachments-wrapper-upload').html("<iframe "+sty+" scrolling='no'  height='450' frameBorder='0' src='import-project/import-file.php?id="+ idPageHtmlTop +"&action=step1&typefile="+ typeFiletManaged + '&cotk=' + $('#cotk').val() +"' ></iframe>");    
    },500);

}

// Refresh after upload
function refreshAfterAnUpload() {

    $(".bloc-img-view").removeClass("bloc-img-view-active");

	var imgA = $('#urlFileInSlider').val();
    
    // Images
    if (typeFiletManaged==13) {

        if (isImageStudio(imgA)==false) {
            $('#urlFileInSlider').val("");
        }else{
            $('#urlFileInSlider').attr("src",imgA);
            if (typeFiletManaged==13) {
                $('.bloc-img-manager').css("display","none");
                $('.attachments-wrapper').prepend(createThumbAccessSlider(imgA,1,'',1,1));
                dataSelectImgManaged = imgA;
            } else {
                $('.attachments-wrapper').prepend(createThumbAccessSlider(imgA,1,'',1,1));
            }
        }

    }
    // Files
    if (typeFiletManaged==23) {

        var goodFormat = false;
        if (filterGlobalFiles=='.mp3'&&imgA.indexOf('.mp3')!=-1) {
            goodFormat = true;
        }
        if (filterGlobalFiles=='.mp4'&&imgA.indexOf('.mp4')!=-1) {
            goodFormat = true;
        }

        if (filterGlobalFiles=='.mp4.pdf') {
            if (imgA.indexOf('.mp4')!=-1) {
                goodFormat = true;
            }
            if (imgA.indexOf('.pdf')!=-1) {
                goodFormat = true;
            }
        }
        if (filterGlobalFiles==''&&imgA!="") {
            goodFormat = true;
        }
        if (goodFormat&&getAutorizedExtends(imgA)) {
            
            $('.attachments-wrapper-files').prepend(createThumbAccessSlider(imgA,1,'',1,1));
            dataSelectImgManaged = imgA;

        } else {
            
            showMediaAlarm("Format Not Correct");

        }
        
    }
    
	$('.ui-widget-overlay').css("display","none");
	$('.workingProcessSave').css("display","none");
    $('.ludimenu').css("display","");
	
}

function receiveMessageDirectUpload(event)
{

    $('.attachments-wrapper').css("display","none");
    $('.attachments-wrapper-files').css("display","none");
    $('.attachments-wrapper-upload').css("display","none");
    $('.attachments-wrapper-clipart').css("display","none");

    if (typeof event.data === 'string' && event.data.indexOf('importfileok:')!=-1) {
        
        selectFileNameManaged = event.data;
        selectFileNameManaged = selectFileNameManaged.replace('importfileok:','');
        
        // Images
        if (typeFiletManaged==13) {
            
            if (isImageFile(selectFileNameManaged)) {
                $('.attachments-wrapper').css("display","block");
                $('.bloc-img-manager').css("display","none");
                dataSelectImgManaged = selectFileNameManaged;
                var bdDiv = createThumbAccessSlider(selectFileNameManaged,1,'',1,1);
                $('.attachments-wrapper').prepend(bdDiv);
            } else {
                showMediaAlarm("Format Not Correct !");
                $('.attachments-wrapper').css("display","block");
                $('.bloc-img-manager').css("display","block");
            }

        }
        // Files
        if (typeFiletManaged==23) {

            $('.attachments-wrapper-files').css("display","block");

            var goodFormat = false;
            if (filterGlobalFiles=='.mp3'&&selectFileNameManaged.indexOf('.mp3')!=-1) {
                goodFormat = true;
            }
            if (filterGlobalFiles=='.mp4'&&selectFileNameManaged.indexOf('.mp4')!=-1) {
                goodFormat = true;
            }
            if (filterGlobalFiles=='.mp4.pdf') {
                if (selectFileNameManaged.indexOf('.mp4')!=-1) {
                    goodFormat = true;
                }
                if (selectFileNameManaged.indexOf('.pdf')!=-1) {
                    goodFormat = true;
                }
            }
            if (filterGlobalFiles==''&&selectFileNameManaged!="") {
                goodFormat = true;
            }
            if (goodFormat&&getAutorizedExtends(selectFileNameManaged)) {
                
                $('.bloc-img-manager').css("display","none");
                $('.attachments-wrapper-files').prepend(createThumbAccessSlider(selectFileNameManaged,1,'',1,1));
                dataSelectImgManaged = selectFileNameManaged;
                
            } else {

                if (selectFileNameManaged=='filexist') {
                    showMediaAlarm("File exist !");
                } else {

                    if (selectFileNameManaged=='errorupload') {
                        showMediaAlarm("Error Upload!");
                    } else {

                        if (selectFileNameManaged=='filenot') {
                            showMediaAlarm("File not authorized !");
                        } else {
                            showMediaAlarm("Error !");
                        }
                    }
                }

            }

        }
      
    }

    initUploadFileInStudio();

}

window.addEventListener("message", receiveMessageDirectUpload, false);

function getAutorizedExtends(selectFileName) {

    var goodFormat = false;

    if (selectFileName.indexOf('.mp3')!=-1) {
        goodFormat = true;
    }
    if (selectFileName.indexOf('.mp4')!=-1) {
        goodFormat = true;
    }
    if (selectFileName.indexOf('.pdf')!=-1) {
        goodFormat = true;
    }
    if (selectFileName.indexOf('.jpg')!=-1) {
        goodFormat = true;
    }
    if (selectFileName.indexOf('.gif')!=-1) {
        goodFormat = true;
    }
    if (selectFileName.indexOf('.svg')!=-1) {
        goodFormat = true;
    }
    if (selectFileName.indexOf('.png')!=-1) {
        goodFormat = true;
    }
    if (selectFileName.indexOf('.docx')!=-1) {
        goodFormat = true;
    }
    if (selectFileName.indexOf('.odt')!=-1) {
        goodFormat = true;
    }
    if (selectFileName.indexOf('.ods')!=-1) {
        goodFormat = true;
    }
    if (selectFileName.indexOf('.odp')!=-1) {
        goodFormat = true;
    }
    if (selectFileName.indexOf('.otp')!=-1) {
        goodFormat = true;
    }
    if (selectFileName.indexOf('.xlsx')!=-1) {
        goodFormat = true;
    }
    if (selectFileName.indexOf('.docx')!=-1) {
        goodFormat = true;
    }
    if (selectFileName.indexOf('.txt')!=-1) {
        goodFormat = true;
    }
    if (selectFileName.indexOf('.zip')!=-1) {
        goodFormat = true;
    }
    if (selectFileName.indexOf('.zip')!=-1) {
        goodFormat = true;
    }
    if (selectFileName.indexOf('.pptx')!=-1) {
        goodFormat = true;
    }
    return goodFormat;

}
var optionsGlobalClipArt = 0;
var indexGlobalClipArt = 0;

var loadClipArtIsFinish = false;

//Clip Art Window
function directClipArtFileInStudio() {

    $('.media-menu-item').removeClass("media-menu-active");
    $('.menu-clipart').addClass("media-menu-active");

    $('.attachments-wrapper').css("display","none");
    $('.attachments-wrapper-upload').css("display","none");
    $('.attachments-wrapper-files').css("display","none");
    $('.attachments-wrapper-clipart').css("display","block");

    if (loadClipArtIsFinish==false) {
        loadClipArtFileTab();
    }

}

function loadClipArtFileTab() {

    if (optionsGlobalClipArt==0) {
        
        var bdDiv = '<p style="text-align:center;" ><br/><img src="img/cube-oe.gif" /><br/><br/></p>';
        $('.attachments-wrapper-clipart').html(bdDiv);
        
        $.ajax({
            url : '../custom_code/cliparts-service/connect.php?idteach=' + idPageHtml + '&cotk=' + $('#cotk').val(),
            type: "POST",dataType : 'json',
            success: function(data,textStatus,jqXHR){
                optionsGlobalClipArt = data;
                displayGlobalClipArt();
            },
            error: function (jqXHR, textStatus, errorThrown)
            {
                $('.attachments-wrapper-clipart').html("No image library !");
            }
        });

    } else {
        displayGlobalClipArt();
    }

}
function displayGlobalClipArt() {

    if (optionsGlobalClipArt.files.length>0) {
        
        var ind = 0;
        var tableH = '';
        
        $.each(optionsGlobalClipArt.files,function(){

            if (ind<12) {
                if (this.nameonly!='') {
                    tableH += createThumbAccessSlider(this.src,0,this.nameonly,this.usefile,2);
                    indexGlobalClipArt++;
                    ind++;
                }
            }
       
        });
        
        $('.attachments-wrapper-clipart').html(tableH);
    
        setTimeout(function(){
            displayGlobalClipArtFinal();
        },2000);

    } else {
        
        $('.attachments-wrapper-clipart').html("No image library !");
    
    }

}
function displayGlobalClipArtFinal() {
    var ind = 0;
    var tableH = '';
    $.each(optionsGlobalClipArt.files,function(){
            if (this.nameonly!='') {
                if (ind>11&&ind<26) {
                    tableH += createThumbAccessSlider(this.src,0,this.nameonly,this.usefile,2);
                }
                ind++;
            }
    });
    $('.attachments-wrapper-clipart').append(tableH);
    setTimeout(function(){
        displayGlobalClipArtFinal2();
    },2000);
}
function displayGlobalClipArtFinal2() {

    var ind = 0;
    var tableH = '';
    
    $.each(optionsGlobalClipArt.files,function(){
        if (this.nameonly!='') {
            if (ind>25) {
                tableH += createThumbAccessSlider(this.src,0,this.nameonly,this.usefile,2);
            }
            ind++;
        }
        loadClipArtIsFinish = false;
    });
    $('.attachments-wrapper-clipart').append(tableH);

    loadClipArtIsFinish = false;

}
function displayDevAdminParams(){
	
	$('.ludimenu').css("z-index","2");
    $('.topmenuabout').css("display","none");

	if ($("#pageDevAdminParams").length==0) {
		
		var bdDiv = '<div id="pageDevAdminParams" style="overflow:hidden;" class="gjs-mdl-container" >';

		bdDiv += '<div class="gjs-mdl-dialog-v2 gjs-one-bg gjs-two-color" style="max-width:575px;" >';
		
		bdDiv += '<div class="gjs-mdl-header">';
		bdDiv += '<div class="gjs-mdl-title trd">Studio DevTools</div>';
		bdDiv += '<div class="gjs-mdl-btn-close" onClick="closeAllEditWindows()" data-close-modal="">⨯</div>';
		bdDiv += '</div>';

		listPagesCS = listPagesCS.replace(/;/g,' ');
        bdDiv += '<p style="position:relative;margin-left:20px;" >list [pages] : <small>(' + listPagesCS + ')</small></p>';
		
		var statusStr = "SESSIONADMIN";
		
		if (userStatusCS==3) { statusStr = "SESSIONADMIN"; }
		if (userStatusCS==1) { statusStr = "COURSEMANAGER"; }
		if (userStatusCS==11) { statusStr = "PLATFORM_ADMIN"; }
		
		bdDiv += '<p style="position:relative;margin-left:20px;" > user[status] : <small>' + statusStr + ' (' + userStatusCS + ')</small></p>';
		
		bdDiv += '<p style="position:relative;margin-left:20px;" > colorsPath : <small>' + colorsPath + '</small></p>';
		bdDiv += '<p style="position:relative;margin-left:20px;" > quizzThemePath : <small>' + quizzthemePath + '</small></p>';

		bdDiv += '<p style="position:relative;margin-left:20px;" > optionsCS : <small>' + optionsCS + '</small></p>';
		bdDiv += '<p style="position:relative;margin-left:20px;" > optionsCS CDT : <small>' + optionsCSCDT + '</small></p>';
		bdDiv += '<p style="position:relative;margin-left:20px;" > optionsGlobalPage Project : <small>' + optionsGlobalPage + '</small></p>';
		
		bdDiv += '<div style="position:relative;margin-left:20px;" onClick="deleteAllTopMenu();closeAllEditWindows();loadTerminalStudio();" >&nbsp;>&nbsp;>&nbsp;Terminal</div>';
		bdDiv += '<p><br/></p>';
		
		bdDiv += '<div class="gjs-mdl-collector" style="display: none"></div>';
 
		bdDiv += '</div>';

		$('body').append(bdDiv);

	}

	if($("#pageDevAdminParams").length==1){
        getTemplatesGlobal();
		$('.ludimenu').css("display","none");
		$('#pageDevAdminParams').css("display","");
	}

}

function renderimgactive(var1,var2) {

    var h = '';
    
    h += '<div class="plugimageactive" >';
    h += '<img class="imageactive" src="img/imageactive.jpg" />';
    h += '</div>';

    h += '<span class=typesource >imageactive</span>';

    return h;

}



function displayImageActiveEdit(myObj){

	var ImageActiveObj = $(myObj);
	tmpObjDom = ImageActiveObj;
	
	var datatext1 = '';
	var datatext2 = '';
	var datatext3 = '';
	var ObjDivhref = ImageActiveObj.find('div');
	datatext1 = ObjDivhref.parent().find('span.datatext1').html();
	datatext2 = ObjDivhref.parent().find('span.datatext2').html();
	datatext3 = ObjDivhref.parent().find('span.datatext3').html();
	if(datatext1===undefined){datatext1 = '';}
	if(datatext2===undefined){datatext2 = '';}
	if(datatext3===undefined){datatext3 = '';}
	if(datatext1==="undefined"){datatext1 = '';}
	if(datatext2==="undefined"){datatext2 = '';}
	if(datatext3==="undefined"){datatext3 = '';}

	optionsIZA1 = datatext1;
	optionsIZA2 = datatext2;
	optionsIZA3 = datatext3;

	if ($("#ImageActiveEdit").length==0) {

		var bdDiv = '<div id="ImageActiveEdit" class="gjs-mdl-container" style="" >';
		bdDiv += '<div class="gjs-mdl-dialog-v2 gjs-one-bg gjs-two-color">';
		bdDiv += '<div class="gjs-mdl-header">';
		bdDiv += '<div class="gjs-mdl-title">Edition</div>';
		bdDiv += '<a id="logZA" class="buttonXY">0</a>';
		bdDiv += '<div class="gjs-mdl-btn-close gjs-mdl-btn-close-audio" onClick="closeAllEditWindows()" ';
		bdDiv += ' data-close-modal="">⨯</div>';
		bdDiv += '</div>';
		
		bdDiv += '<div class="gjs-am-add-asset" ';
		bdDiv += 'style="padding:25px;font-size:16px;" >';
		
		console.log("load Interface ImgActiv");

		bdDiv += loadInterfaceImgActiv();
		bdDiv += loadImgActivTools();
		bdDiv += loadWindowImgActiv();
		
		bdDiv += '</div>';
		bdDiv += '</div>';

		bdDiv += '<div class="gjs-mdl-collector" style="display: none"></div>';
		bdDiv += '</div>';

		$('body').append(bdDiv);

	}

	if ($("#ImageActiveEdit").length==1) {
		
		strLinkString = ImageActiveObj.attr("datahref");
		var idm = Math.floor(Math.random() * Math.floor(200));
		tmpNameDom = 'tempnode' + idm;
		ImageActiveObj.attr("id",tmpNameDom);
        
		$('.ludimenu').css("z-index","2");
		$('#ImageActiveEdit').css("display","");
		$('#activeimg3').val("");
		$('#activeimg3').css("display","none");
		//Load image
		var hi = '<img id="reviewImg" style="position:relative;width:100%;opacity:0.7;z-index:97;" src="img/imageactive.jpg" />';
		if (isImageStudio(optionsIZA1)){
			hi = '<img id="reviewImg" style="position:relative;width:100%;opacity:0.8;z-index:97;" src="'+optionsIZA1+'" />';
			$('#activeimg3').val(optionsIZA1);
		}

		$('#zoneButtonRightZA').html(hi);

		//Load Areas
		loadExtrasZonesA();
		overvZAaction();
		
		$("#zoneButtonRightZA").css("width","87%");
        $("#zoneButtonRightZA").css("margin-left","0%");

		redimWorkAreaActiveImage();
		setTimeout(function(){ redimWorkAreaActiveImage(); },1000);

		windowEditorIsOpen = true;
		loadaFunction();
	}

}

function saveImageActiveEdit() {

	optionsIZA1 = $('#activeimg3').val();

	var rC = '<div class="plugimageactive" >';

	if (isImageStudio(optionsIZA1)){
    	rC += '<img class="imageactive" src="' + optionsIZA1 + '" />';
	} else {
		rC += '<img class="imageactive" src="img/imageactive.jpg" />';
	}
	rC += createHtmlFromZoneFromCodes();
    rC += '</div>';
    rC += '<span class=typesource >imageactive</span>';

	if (isImageStudio(optionsIZA1)){
		rC += '<span class=datatext1 >' + optionsIZA1 + '</span>';
	} else {
		rC += '<span class=datatext1 ></span>';
	}
	rC += '<span class=datatext2 >' + optionsIZA2 + '</span>';
	rC += '<span class=datatext3 >' + optionsIZA3 + '</span>';

	if(GlobalTagGrappeObj=='div'){
		rH = GplugSrcTop + rH + GplugSrcBottom;
	}

	var rH = GplugSrcT;
    rH = rH.replace("{content}",rC);
    if(GlobalTagGrappeObj=='div'){
		rH = GplugSrcTop + rH + GplugSrcBottom;
	}

	setAbstractObjContent(rH);
	
	closeAllEditWindows();
	
	$('.ludimenu').css("z-index","1000");
	saveSourceFrame(false,false,0);

}

function loadAnActiveImage(){
    showFileManagerStudio2(13,'activeimg3','refreshAnActiveImageGlobal');
}

function refreshAnActiveImageGlobal(){

	var imgA = $('#activeimg3').val();

    if (isImageStudio(imgA)==false){
        $('#activeimg3').val("");
        $('#reviewImg').attr("src","img/imageactive.jpg");
    }else{
        $('#reviewImg').attr("src",imgA);
    }
	
	$('.ui-widget-overlay').css("display","none");
	$('.workingProcessSave').css("display","none");
    $('.ludimenu').css("display","none");
    $('#pageEditGlobalParams').css("display","");
	
}

var currentDroppable = false;
var GshiftX = 0;
var GshiftY = 0;
var idZA = 0;
var currentidZA = -1;

var optionsIZA1 = '';
var optionsIZA2 = '';
var optionsIZA3 = '';
var optionsIZA4 = '';
var optionsIZA5 = '';

//Center image
function loadInterfaceImgActiv() {

	var i = '<div id="zoneButtonRightZA" class="zoneButtonRightZA noselect">';
    i += '</div>';
	return i;

}

//Tools
function loadImgActivTools() {

	var i = '<div class="zoneButtonLeftZA noselect">';
    i += '<a onclick="addZoneToZoneA()" class="buttonZA">+</a>';
    i += '<a onclick="loadAnActiveImage()" class="buttonZAloadImage"></a>';
    
    i += '<input id=activeimg3 class=activeimg3 />';

	i += '<input onClick="saveImageActiveEdit()" ';
	i += ' class="gjs-one-bg btnZIA ludiButtonSave trd" type="button" value="Save" /><br/>';
	i += '</div>';

	return i;

}

//Windows events
function loadWindowImgActiv() {

	var interf = '<div class="actioneditorwin">';

	interf += '<div class="actioneditorwintitle">Edition';
	interf += '<div class="actioneditorwinclose" onclick="closeActionEditZA()">X</div>';
	interf += '</div>';

	interf += '<select name="actionZASelect" id="actionZASelect" class="actionZASelect">';
	interf += '<option value="0">No action</option>';
	interf += '<option value="1">Display message</option>';
	interf += '<option value="2">Next page</option>';
	interf += '<option value="3">Prev page</option>';
	interf += '<option value="4">Display image</option>';
    interf += '<option value="5">Speech Bubble</option>';
	interf += '</select>';


    interf += '<div class="checkCell01 noselect"></div>';
    interf += '<div class="checkCell02 noselect"></div>';
    interf += '<div class="checkCell03 noselect"></div>';

	interf += '<div class="checkTyp01 noselect">';
	interf += '<input type="radio" value="0" id="typeZA01" name="typeZA">';
	interf += '<label class="noselect" for="typeZA01">Transparent</label>';
	interf += '</div>';


	interf += '<div class="checkTyp03 noselect">';
	interf += '<input type="radio" value="2" id="typeZA03" name="typeZA">';
	interf += '<label class="noselect" for="typeZA03">Big cursor</label>';
	interf += '</div>';
    
    interf += '<img class="checkImgTyp03 noselect" src="img/classique/cursor.svg" />';


    interf += '<div class="checkTyp04 noselect">';
	interf += '<input type="radio" value="3" id="typeZA04" name="typeZA">';
	interf += '<label class="noselect" for="typeZA04">Small cursor</label>';
	interf += '</div>';

    interf += '<img class="checkImgTyp04" src="img/classique/cursor.svg" />';

    /* line 2 */

    interf += '<div class="checkCell04 noselect"></div>';
    interf += '<div class="checkCell05 noselect"></div>';
    interf += '<div class="checkCell06 noselect"></div>';

	interf += '<div class="checkTyp02 noselect" >';
	interf += '<input type="radio" value="1" id="typeZA02" name="typeZA">';
	interf += '<label class="noselect" for="typeZA02">MapPoint</label>';
	interf += '</div>';

    interf += '<img class="checkImgTyp02 noselect" src="img/classique/point-circle.svg" />';

    interf += '<div class="checkTyp05 noselect" >';
	interf += '<input type="radio" value="4" id="typeZA05" name="typeZA">';
	interf += '<label class="noselect" for="typeZA05">Pointing left</label>';
	interf += '</div>';

    interf += '<img class="checkImgTyp05 noselect" src="img/classique/pointing-left.svg" />';

	interf += '<textarea id="actionZAtextArea" name="actionZAtextArea" class="actionZAtextArea" rows="3" cols="46"></textarea>';

	interf += '<input id="urlextraimg" class="form-control extraimg" style="position:absolute;left:10px;top:85px;width:80%;display:none;" />';
	interf += '<a onclick="showFileManagerToInput(100);" style="position:absolute;right:15px;top:85px;display:none;" class="btn btn-info extraimg" >...</a>';

	interf += '<a class="btn btn-info actionBtnApplyZA ludiButtonSave trd" onclick="applyActionEditZA()">Apply</a>';

    interf += '<a class="actionBtndeleteZA" onclick="applyActionDeleteZA()">';
    interf += '<img src="icon/delete-icon-24.png" /></a>';

	interf += '</div>';

    return interf;

}

//Load Areas from data
function loadExtrasZonesA() {

    if (optionsIZA2.indexOf('$')!=-1) {
        
        optionsIZA3 = optionsIZA3 + '$$$$$$';

        var ArrayObjects = optionsIZA2.split('$');
        var ArrayOptions = optionsIZA3.split('$');

        var i = 0;

        for (i = 0; i < ArrayObjects.length; i++) {
        
            var objInfos = ArrayObjects[i];
            var objValues = ArrayOptions[i];

            if (objInfos.indexOf('|')!=-1) {
                var objdet = objInfos.split('|');
                addZoneToZoneAFromOpt2(objdet[1],objdet[2],objValues);
            }
            
            document.getElementById('zoneButtonRightZA').ondragstart = function() { return false; };
            document.getElementById('zoneButtonRightZA').onmouseup = function(event) { currentDroppable = false; };

        }

    }

    document.getElementById('zoneButtonRightZA').addEventListener('mousemove',calculCoordZA);

}

//Create Render from data
function redimWorkAreaActiveImage() {
    
    var bi = $("body").height()-100;
    var hi = $("#reviewImg").height();

    if (hi>bi){
        $("#zoneButtonRightZA").css("width","70%");
        $("#zoneButtonRightZA").css("margin-left","10%");
    }

}

//Create Render from data
function createHtmlFromZoneFromCodes() {

    var renderH = "";

    if (optionsIZA2.indexOf('$')!=-1) {
        
        optionsIZA3 = optionsIZA3 + '$$$$$$';

        var ArrayObjects = optionsIZA2.split('$');
        var ArrayOptions = optionsIZA3.split('$');

        var i = 0;
        for (i = 0; i < ArrayObjects.length; i++) {

            var objInfos = ArrayObjects[i];
            var objValues = ArrayOptions[i];

            if (objInfos.indexOf('|')!=-1) {
                var objdet = objInfos.split('|');
                var lx = objdet[1];
                var ty = objdet[2];
                var para = "<div class='overViewZAedition' style='left:" + lx + "%;top:" + ty + "%;' ></div>";
                renderH += para;
            }
            
        }

    }

    return renderH;


}

//Install Area from code
function addZoneToZoneAFromOpt2(l,t,ovals) {
    
    ovals = ovals + '||||||';
    var objparam = ovals.split('|');

    var actZASelect = objparam[1];
    var zAtextArea = cleTextAct(objparam[2]);
    var typeZA = objparam[3];

    var para = "<div id='paramsZA' class='paramsZA' onClick='launchActionEditZA(" + idZA + ");' ></div>";
    para += "<div class='decoEditZA' ></div>";
    var divH = "<div action='"+actZASelect+"' typeZA='"+typeZA+"' zatext='"+zAtextArea+"' ";
    divH += " id='areaZA" + idZA + "' style='left:" + l + "%;top:" + t + "%;' ";
    divH += " class='areaZA noselect' >" + para + "</div>";
    $('#zoneButtonRightZA').append(divH);

    eventsZoneZA(idZA);
    idZA++;
    
}

//Add a new area
function addZoneToZoneA(){
    
    var para = "<div id='paramsZA' class='paramsZA' onClick='launchActionEditZA(" + idZA + ");' ></div>";

    $('#zoneButtonRightZA').append("<div id='areaZA" + idZA + "' typeZA=0 style='left:5%;top:5%;' class='areaZA noselect' >"+para+"</div>");
    document.getElementById('zoneButtonRightZA').addEventListener('mousemove',calculCoordZA);
    document.getElementById('zoneButtonRightZA').ondragstart = function() { return false; };
    document.getElementById('zoneButtonRightZA').onmouseup = function(event) { currentDroppable = false; };
    eventsZoneZA(idZA);
    idZA++;

}

function eventsZoneZA(i){

    var ZA = document.getElementById('areaZA' + i);
    ZA.onmousedown = function(event) {
        if (isClickOnArea(i) ) {
            currentDroppable = true;
            currentidZA = i;
        }
    };
    ZA.onmouseup = function(event) {
        currentDroppable = false;
        currentidZA = -1;
        
    };
    ZA.ondragstart = function() {
        return false;
    };

}

function isClickOnArea(i) {

    var posArea = $('#areaZA'+i).offset();

    var yObjCtr = posArea.top;
    yObjCtr = document.getElementById('areaZA' + i).offsetTop;

    var ycoordCtr = 0
    var documentHeight = $('#zoneButtonRightZA').height();

	var ycoordCtr = (ycoordPour * documentHeight) / 100;

    if (ycoordCtr<yObjCtr) {
        return false;
    }else{
        return true;
    }

}

//Edit action behavior
function launchActionEditZA(i) {

    $('.actioneditorwin').css("display",'block');

    var actZASelect = $('#areaZA' + i).attr("action");
    if (actZASelect=='') { actZASelect = 0; }
    if (actZASelect === undefined) { actZASelect = 0; }

    var zAtextArea = $('#areaZA' + i).attr("zatext");
    if (zAtextArea === undefined) { zAtextArea = ''; }
    if (zAtextArea == 'undefined') { zAtextArea = ''; }

    $('#actionZASelect').val(actZASelect);

    $('#urlextraimg').val('');
    $('#actionZAtextArea').val('');

    var typeZA = $('#areaZA' + i).attr("typeZA");
    if (typeZA === undefined) { typeZA = 0; }
    if (typeZA == 'undefined') { typeZA = 0; }
    if (typeZA == '') { typeZA = 0; }
    typeZA = parseInt(typeZA);

    launchActionCheckZA(typeZA);

    $('#actionZASelect').on('change', function() {
        arrangeActionEditZA();
    });
    
    if (actZASelect==4) {
        zAtextArea = zAtextArea.replace(/S!L/g,'/');
        $('#urlextraimg').val(zAtextArea);
    } else {
        $('#actionZAtextArea').val(zAtextArea);
    }

    currentidZA = i;
    
    arrangeActionEditZA();

}

var xcoord = 0;
var ycoord = 0;

var xcoordPour = 0;
var ycoordPour = 0;

function calculCoordZA(e) {

    if( !e ) {
      if( window.event ) {
        //Internet Explorer
        e = window.event;
      } else {
        //total failure, we have no way of referencing the event
        return;
      }
    }
    if( typeof( e.pageX ) == 'number' ) {
      //most browsers
      xcoord = e.pageX;
      ycoord = e.pageY;
    } else if( typeof( e.clientX ) == 'number' ) {
        
      //Internet Explorer and older browsers
      //other browsers provide this, but follow the pageX/Y branch
      
      xcoord = e.clientX;
      ycoord = e.clientY;
      
      var badOldBrowser = ( window.navigator.userAgent.indexOf( 'Opera' ) + 1 ) ||//**
       ( window.ScriptEngine && ScriptEngine().indexOf( 'InScript' ) + 1 ) ||//**
       ( navigator.vendor == 'KDE' );//**
       
      if( !badOldBrowser ) {//**
        if( document.body && ( document.body.scrollLeft || document.body.scrollTop ) ) {//**
          //IE 4, 5 & 6 (in non-standards compliant mode)
          xcoord += document.body.scrollLeft;
          ycoord += document.body.scrollTop;
        } else if( document.documentElement && ( document.documentElement.scrollLeft || document.documentElement.scrollTop ) ) {//**
          //IE 6 (in standards compliant mode)
          xcoord += document.documentElement.scrollLeft;//**
          ycoord += document.documentElement.scrollTop;//**
        }
      }
      
    } else {
      //total failure, we have no way of obtaining the mouse coordinates
      return;
    }
    var e_posx = 0;
    var e_posy = 0;
    var obj = this;
    //get parent element position in document
    if (obj.offsetParent){
        do { 
            e_posx += obj.offsetLeft;
            e_posy += obj.offsetTop;
        } while (obj = obj.offsetParent);
    }
    //var decX = 
    xcoord = (xcoord - e_posx);
    ycoord = (ycoord - e_posy);
   
    var documentWidth = $('#zoneButtonRightZA').width();
	var documentHeight = $('#zoneButtonRightZA').height();

    var pourcX = (xcoord / documentWidth)  * 100;
	var pourcY = (ycoord / documentHeight) * 100;
    
    pourcX = Math.round(pourcX * 10) / 10;
    pourcY = Math.round(pourcY * 10) / 10;
    if (pourcX>99) {pourcX = 99; }
    if (pourcY>99){ pourcY = 99; }

    xcoordPour = pourcX;
    ycoordPour = pourcY;

    var xCoordLab = parseInt(xcoord);
    if (xCoordLab<0) {xCoordLab = 0;}

    if (xCoordLab<10) {
        xCoordLab = "00" + xCoordLab;
    }else{
        if (xCoordLab<100) {
            xCoordLab = "0" + xCoordLab;
        }
    }

    var yCoordLab = parseInt(ycoord);

    if (yCoordLab<0) {yCoordLab = 0;}

    if (yCoordLab<10) {
        yCoordLab = "00" + yCoordLab;
    }else{
        if (yCoordLab<100) {
            yCoordLab = "0" + yCoordLab;
        }
    }

    $('#logZA').html('X:' + xCoordLab + '&nbsp;' + 'Y:' + yCoordLab );
    if (currentDroppable&&currentidZA!=-1) {
        var ZA = document.getElementById('areaZA'+currentidZA);
        ZA.style.left = (pourcX -5) + '%';
        ZA.style.top = (pourcY-5) + '%';
        calculZAdata();
    }
  
}

function calculZAdata() {

    var finaldata = "";

    $( ".areaZA" ).each(function( index ) {
        
        var documentWidth = $('#zoneButtonRightZA').width();
        var documentHeight = $('#zoneButtonRightZA').height();
        
        var position = $( this ).position();
        var idObj = $( this ).attr("id");
        idObj = idObj.replace("areaZA","");
        var pourcX = (position.left / documentWidth)  * 100;
        var pourcY = (position.top / documentHeight) * 100;
        
        pourcX = Math.round(pourcX * 10) / 10;
        pourcY = Math.round(pourcY * 10) / 10;

        finaldata += idObj + '|' + pourcX + '|' + pourcY;
        finaldata += '$';
    
    });
    
    optionsIZA2 = finaldata;
    
    calculZAaction();

}

function calculZAaction() {

    var finaldata = "";

    $( ".areaZA" ).each(function( index ) {
        
        var idObj = $( this ).attr("id");
        idObj = idObj.replace("areaZA","");
        
        var actStr = $( this ).attr("action");
        if (actStr=='') { actStr = 0; }
        if (actStr === undefined) { actStr = 0; }

        var zAtextArea = $( this ).attr("zatext");
        zAtextArea = cleTextAct(zAtextArea);

        var typeZA = $( this ).attr("typeZA");
        if (typeZA === undefined) { typeZA = 0; }
        if (typeZA == 'undefined') { typeZA = 0; }
        if (typeZA == '') { typeZA = 0; }

        var hDeco = "";
        if (typeZA==2) {
            hDeco = "<span>Cursor</span>"
        }
        $(this).find("decoEditZA").html(hDeco);

        finaldata += idObj + '|' + actStr + '|' + zAtextArea + '|' + typeZA;
        finaldata += '$';
    
    });
    
    optionsIZA3 = finaldata;

}

function overvZAaction() {

    $( ".areaZA" ).each(function( index ) {
        
        var typeZA = $( this ).attr("typeZA");
        if (typeZA === undefined) { typeZA = 0; }
        if (typeZA == 'undefined') { typeZA = 0; }
        if (typeZA == '') { typeZA = 0; }

        var hDeco = "";
        if (typeZA==1) {
            hDeco = "url(img/classique/point-circle.svg)";
        }
        if (typeZA==2) {
            hDeco = "url(img/rendcursor.png)";
        }
        if (typeZA==3) {
            hDeco = "url(img/rendcursor.png)";
        }
        if (typeZA==4) {
            hDeco = "url(img/pointing-left.png)";
        }
        $(this).find(".decoEditZA").css("background-image",hDeco);
        

    });

}

function closeActionEditZA() {

    currentDroppable = false;
    $('.actioneditorwin').css("display",'none');
    
}

//Update Actions
function arrangeActionEditZA() {

    var actZASelect = $('#actionZASelect').val();

    $('.extraimg').css("display","none");
    $('#actionZAtextArea').css("display","none");

    if (actZASelect==4) {
        $('.extraimg').css("display","block");
    }
    if (actZASelect==1||actZASelect==5) {
        $('#actionZAtextArea').css("display","block");
    }

}

//Load parameters check
function launchActionCheckZA(typeZA) {

    $('#typeZA01').prop("checked", false);
    $('#typeZA02').prop("checked", false);
    $('#typeZA03').prop("checked", false);
    $('#typeZA04').prop("checked", false);
    $('#typeZA04').prop("checked", true);

    if (typeZA==0) {
        $('#typeZA01').prop("checked", true);    
    } 
    if (typeZA==1) {
        $('#typeZA02').prop("checked", true);
    }
    if (typeZA==2) {
        $('#typeZA03').prop("checked", true);
    }
    if (typeZA==3) {
        $('#typeZA04').prop("checked", true);
    }
    if (typeZA==4) {
        $('#typeZA05').prop("checked", true);
    }
}

//Load parameters
function launchActionEditZA(i) {

    $('.actioneditorwin').css("display",'block');

    var actZASelect = $('#areaZA' + i).attr("action");
    if (actZASelect=='') { actZASelect = 0; }
    if (actZASelect === undefined) { actZASelect = 0; }

    var zAtextArea = $('#areaZA' + i).attr("zatext");
    if (zAtextArea === undefined) { zAtextArea = ''; }
    if (zAtextArea == 'undefined') { zAtextArea = ''; }

    $('#actionZASelect').val(actZASelect);

    $('#urlextraimg').val('');
    $('#actionZAtextArea').val('');

    var typeZA = $('#areaZA' + i).attr("typeZA");
    if (typeZA === undefined) { typeZA = 0; }
    if (typeZA == 'undefined') { typeZA = 0; }
    if (typeZA == '') { typeZA = 0; }
    typeZA = parseInt(typeZA);

    launchActionCheckZA(typeZA);

    $('#actionZASelect').on('change', function() {
        arrangeActionEditZA();
    });
    
    if (actZASelect==4) {
        zAtextArea = zAtextArea.replace(/S!L/g,'/');
        $('#urlextraimg').val(zAtextArea);
    } else {
        $('#actionZAtextArea').val(zAtextArea);
    }

    currentidZA = i;
    
    arrangeActionEditZA();

}

function applyActionEditZA() {

    $('.actioneditorwin').css("display",'none');

    var actZASelect = $('#actionZASelect').val();

    var zAtextArea = $('#actionZAtextArea').val();
    zAtextArea = zAtextArea.replace(/\n/g,' ');
    zAtextArea = zAtextArea.replace(/@/g,'');
    
    zAtextArea = cleTextAct(zAtextArea);

    if (actZASelect==4) {
        zAtextArea =  $('#urlextraimg').val();
        zAtextArea = zAtextArea.replace(/\//g,'S!L');
    }

    var typeZA = $('input:radio[name=typeZA]:checked').val();

    $('#areaZA' + currentidZA).attr("action",actZASelect);
    $('#areaZA' + currentidZA).attr("zatext",zAtextArea);
    $('#areaZA' + currentidZA).attr("typeZA",typeZA);
    
    calculZAaction();
    overvZAaction();
    currentDroppable = false;

}

function applyActionDeleteZA() {

    $('#areaZA' + currentidZA).remove();
    calculZAdata();
    calculZAaction();
    overvZAaction();
    $('.actioneditorwin').css("display",'none');
    currentDroppable = false;

}

// Acquisition de l'image
function getImageFromZII(){

    if (isImageStudio(optionsIZA1)){
        pageMaskTurn = optionsIZA1;
    } else {
        pageMaskTurn = 'img/slideactive.jpg';
    }

}

function InstallMiniEditorZII() {
    initCKEEditorMini('content_option_area'+incremAreaZii,true);
}

function getMiniEditorZIIVal() {
    var ck_area_txt = '';
    var ckArea =  CKEDITOR.instances['content_option_area'+incremAreaZii];
    if (ckArea) {
        ck_area_txt = ckArea.getData();
    }
    return ck_area_txt;
}

// Trouver une image
function getImageDataZII(idFlipPg){

    var pageUrl = '';
    if (pageMaskTurn.indexOf('{num}')==-1){
        pageUrl = pageMaskTurn.replace('{num}',idFlipPg);
    }
    return pageUrl;
}


// Compilation des zones actives
function compilFlipAdata() {

    var finaldata = "";
    var finaldata5 = "";
    for (var i = 0; i < QuickActiveAreas_count; i++) {
        var objElemCtr = QuickActiveAreas[i];
        if (objElemCtr.delete==0) {
            finaldata += objElemCtr.page + '|' + objElemCtr.x + '|' + objElemCtr.y ;
            finaldata += '|' + objElemCtr.w + '|' + objElemCtr.h + '|' + objElemCtr.type + '|' + objElemCtr.style;;
            finaldata += '$';
            finaldata5 += objElemCtr.data + '$';
        }
	}	
    optionsIZA4 = finaldata;
    optionsIZA5 = finaldata5;

}

// Chargement des zones actives
function loadFlipZonesG(){
    loadFlipZonesA(optionsIZA4,optionsIZA5);
}

var pageMaskTurn = '';

var currentFlipDroppable = false;
var currentFlipResizeX = false;
var currentFlipResizeY = false;

var currentidFlipA = -1;
var idFlipZa = 0;
var idFlipPage = 1;
var flipXcoord = 0;
var flipYcoord = 0;

var editorZiiAreaTmp = '';

var memXcoord = 0;
var memYcoord = 0;

var incremAreaZii = 1;

var redimAct = false;

var actualZiiURLimage = '';

function launchEditWindowsZII() {

    if (QuickActiveAreas_count==0) {
        loadFlipZonesG();
    }

    getImageFromZII();

    if ($("#ZoneInImagesManage").length==0) {
        $("body").append('<div class="ZoneInImagesManagerWindows_cover" ></div>');
        $("body").append('<div id="ZoneInImagesManage" class="ZoneInImagesMgA4" ></div>');
    }

    if (pageMaskTurn!='') {

        editorZiiAreaTmp = ramdomLets(7);
        currentFlipDroppable = false;
        currentFlipResizeX = false;
        currentFlipResizeY = false;
        currentidFlipA = -1;
        $(".ZoneInImagesManagerWindows_cover").css('display','');
        $("#ZoneInImagesManage").css('display','');
        $('#ZoneInImagesManage').html(inZIIManagerArea());
        var eZ = document.getElementById('editorZiiArea' + editorZiiAreaTmp);
        eZ.addEventListener('mousemove',calculCoordFlipA);
        eZ.ondragstart = function() { return false; };
        eZ.onmouseup = function(event) { currentFlipDroppable = false; };

        $(".closeZii").css('display','');
        var pageUrl = getImageDataZII(idFlipPage);
        actualZiiURLimage = pageUrl;
        $(".innerZiiArea").html('<img class="imgarea" src="' + pageUrl + '" style="width:100%;" >');    
        QuickActiveArea_ResetPages();

        setTimeout(() => {
            ctrImageValidZii();
            var imgheight = $('.imgarea').height();
            $('.editorZiiArea,.innerZiiArea').css('height',imgheight+'px');
        }, 50);

        if (redimAct==false) {
            setTimeout(() => {
                functionResizeEdition();
            }, 250);
            redimAct = true;
        }
        
    }

}

function inZIIManagerArea() {

    var h = '';
    h += '<a onClick="prevFlipZoneA()" class="addFlipBtn addFlipBtnA" ><</a>';
    h += '<a onClick="addFlipZoneA()" class="addFlipBtn addFlipBtnB" >+</a>';
    h += '<a onClick="nextFlipZoneA()" class="addFlipBtn addFlipBtnC" >></a>';
    h += '<div id="logFlipA" ></div>';
    h += '<div class="closeZii" onClick="closeZIIManager()" >X</div>';
    h += '<div class="innerZiiArea" ></div>';
    h += '<div class="editorZiiArea" id="editorZiiArea'+ editorZiiAreaTmp +'" ></div>';
    return h;

}
function prevFlipZoneA() {
    if (idFlipPage!=1) {
        idFlipPage = idFlipPage - 1;
        if (idFlipPage<1) {
            idFlipPage = 1;
        }
        launchEditWindowsZII();
    }
}
function nextFlipZoneA() {
    idFlipPage = idFlipPage + 1;
    launchEditWindowsZII();
}

function ctrImageValidZii() {

    if (actualZiiURLimage!='') {
        var img = new Image();
        img.onload = function() {
            var otpFlipPage =  $('#option_2').val();
            if (idFlipPage>otpFlipPage) {
                $('#option_2').val(idFlipPage);
            }
        };
        img.onerror = function() {
            if (idFlipPage==1) {
                idFlipPage = 1;
                closeZIIManager();
            } else {
                idFlipPage = idFlipPage-1;
                launchEditWindowsZII();
            }
        };
        img.src = actualZiiURLimage;
    }

}

function functionResizeEdition() {

    var imgw = $('.imgarea').width();
    var imgh = $('.imgarea').height();

    //screen place
    var screenw = $(window).width();
    var screenh = $(window).height();

    //A4
    if (imgh>imgw) {
        if (screenh>850&&screenw>655) {
            $('#ZoneInImagesManage').removeClass('ZoneInImagesMgA4');
            $('#ZoneInImagesManage').removeClass('ZoneInImagesMgPPT');
            $('#ZoneInImagesManage').removeClass('ZoneInImagesMgA4xl');

            $('#ZoneInImagesManage').addClass('ZoneInImagesMgA4xxl');
        } else {
            if (screenh>800&&screenw>616) {
                $('#ZoneInImagesManage').removeClass('ZoneInImagesMgA4');
                $('#ZoneInImagesManage').removeClass('ZoneInImagesMgPPT');
                $('#ZoneInImagesManage').addClass('ZoneInImagesMgA4xl');
            }
        }
    } else {
        if (screenw>800&&screenh>600) {
            $('#ZoneInImagesManage').removeClass('ZoneInImagesMgA4xl');
            $('#ZoneInImagesManage').removeClass('ZoneInImagesMgA4');
            $('#ZoneInImagesManage').addClass('ZoneInImagesMgPPT');
        }
    }

    var imgheight = $('.imgarea').height();
    $('.editorZiiArea,.innerZiiArea').css('height',imgheight+'px');

    setTimeout(() => {
        functionResizeEdition();
    },750);
}

function closeZIIManager() {
    $(".ZoneInImagesManagerWindows_cover").css('display','none');
    $(".closeZii").css('display','none');
    $('#ZoneInImagesManage').css('display','none');
    $('#ZoneInImagesManage').html('');
    compilFlipAdata();
}

function installLinksPgEdit(){

    $('.bubblenumberpageeditline').append();

}

function addFlipZoneA(){
    QuickActiveArea_New();
}

function eventsFlipZoneA(i){

    var ZA = document.getElementById('areaZA' + i);
    ZA.onmousedown = function(event) {
        currentFlipDroppable = true;
        currentidFlipA = i;
    };
    ZA.onmouseup = function(event) {
        currentFlipDroppable = false;
        currentFlipResizeX = false;
        currentFlipResizeY = false;
        currentidFlipA = -1;
        compilFlipAdata();
    };
    ZA.ondragstart = function() {
        return false;
    };

    var rA = document.getElementById('resizeQAA' + i);
    rA.onmousedown = function(event) {
        currentFlipDroppable = true;
        currentFlipResizeX = true;
        currentFlipResizeY = false;
        currentidFlipA = i;
        var documentWidth = $('#editorZiiArea'+editorZiiAreaTmp).width();
        var currObj = QuickActiveArea_getbyid(currentidFlipA);
        var pourcX = documentWidth * (currObj.x / 100);
        memXcoord = pourcX;
    };
    rA.onmouseup = function(event) {
        currentFlipDroppable = false;
        currentFlipResizeX = false;
        currentFlipResizeY = false;
        currentidFlipA = -1;
        compilFlipAdata();
    };
    rA.ondragstart = function() {
        return false;
    };

    var hA = document.getElementById('resizeHAA' + i);
    hA.onmousedown = function(event) {
        currentFlipDroppable = true;
        currentFlipResizeX = false;
        currentFlipResizeY = true;
        currentidFlipA = i;
        var documentHeight = $('#editorZiiArea'+editorZiiAreaTmp).height();
        var currObj = QuickActiveArea_getbyid(currentidFlipA);
        var pourcY = documentHeight * (currObj.y / 100);
        memYcoord = pourcY;
    };
    hA.onmouseup = function(event) {
        currentFlipDroppable = false;
        currentFlipResizeX = false;
        currentFlipResizeY = false;
        currentidFlipA = -1;
        compilFlipAdata();
    };
    hA.ondragstart = function() {
        return false;
    };

}

function loadFlipZonesA(option4,option5){

    if (option4.indexOf('$')!=-1) {
        
        option5 = option5 + '$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$';

        var ArrayObjects = option4.split('$');
        var ArrayOptions = option5.split('$');

        var i = 0;

        for (i = 0; i < ArrayObjects.length; i++) {
        
            var objInfos = ArrayObjects[i] + '||';
            var objValues = ArrayOptions[i];

            if (objInfos.indexOf('|')!=-1) {
                var objetPart = objInfos.split('|');
                var elem  = new QuickActiveArea();
                elem.page = objetPart[0];
                elem.x = objetPart[1];
                elem.y = objetPart[2];
                elem.w = objetPart[3];
                elem.h = objetPart[4];
                elem.type = objetPart[5];
                if (elem.type=='') { elem.type = '0'; }
                elem.data = objValues;
                elem.create = 1;
                QuickActiveArea_Add(elem);
            }

        }

    }

}

function calculCoordFlipA(e) {

    if ( !e ) {
      if( window.event ) {
        e = window.event;
      } else {
        return;
      }
    }
    if( typeof( e.pageX ) == 'number' ) {
      //most browsers
      flipXcoord = e.pageX;
      flipYcoord = e.pageY;
    } else if( typeof( e.clientX ) == 'number' ) {
        
      flipXcoord = e.clientX;
      flipYcoord = e.clientY;
      
    } else {
      //total failure, we have no way of obtaining the mouse coordinates
      return;
    }
    var e_posx = 0;
    var e_posy = 0;

    var obj = this;
    // get parent element position in document
    if (obj.offsetParent){
        do { 
            e_posx += obj.offsetLeft;
            e_posy += obj.offsetTop;
        } while (obj = obj.offsetParent);
    }
    //var decX = 
    flipXcoord = (flipXcoord - e_posx);
    flipYcoord = (flipYcoord - e_posy);
    
    if( document.body && ( document.body.scrollLeft || document.body.scrollTop ) ) {
        flipYcoord -= document.body.scrollTop;
    } else if( document.documentElement && ( document.documentElement.scrollLeft || document.documentElement.scrollTop ) ) {
        flipYcoord -= document.documentElement.scrollTop;
    }

    var documentWidth = $('#editorZiiArea'+editorZiiAreaTmp).width();
	var documentHeight = $('#editorZiiArea'+editorZiiAreaTmp).height();

    var pourcX = (flipXcoord / documentWidth)  * 100;
	var pourcY = (flipYcoord / documentHeight) * 100;
    
    pourcX = Math.round(pourcX * 10) / 10;
    pourcY = Math.round(pourcY * 10) / 10;
    if (pourcX>99) {pourcX = 99; }
    if (pourcY>99){ pourcY = 99; }
    var pref = '';
    $('#logFlipA').html(pref + 'x:' + parseInt(flipXcoord) + '&nbsp;&nbsp;' + 'y:' + parseInt(flipYcoord) );
    if (currentFlipDroppable&&currentidFlipA!=-1) {
        var objElem = QuickActiveArea_getbyid(currentidFlipA);
        var ZA = document.getElementById('areaZA'+currentidFlipA);
        if (currentFlipResizeX) {
            
            var pourcW = ((flipXcoord- memXcoord) / documentWidth) * 100;

            if (pourcW>0) {
                pourcW = Math.round(pourcW * 10) / 10;
                ZA.style.width = pourcW + '%';
                objElem.w = pourcW;
            }

        } else {

            if (currentFlipResizeY) {
                
                var pourcH = ((flipYcoord- memYcoord) / documentHeight)  * 100;
                if (pourcH>0) {
                    pourcH = Math.round(pourcH * 10) / 10;
                    ZA.style.height = pourcH + '%';
                    objElem.h = pourcH;
                }

            } else {
            
                var finalPx = (pourcX - (objElem.w/2));
                finalPx = Math.round(finalPx * 10) / 10;
                var finalPy = (pourcY - (objElem.h/2));
                finalPy = Math.round(finalPy * 10) / 10;
                ZA.style.left = finalPx + '%';
                ZA.style.top = finalPy + '%';
                objElem.x = finalPx;
                objElem.y = finalPy;

            }

        }
    }
  
}

// Class objects

var QuickActiveAreas = new Array();
var QuickActiveAreas_count = 0;

function QuickActiveArea() {
	
	this.id;
    this.idtmp;
	this.create;
    this.data;
    this.type;
    this.page;
    this.delete = 0;
    this.x;this.y;
    this.w;this.h;
	this.show = function() {
		if (this.create==0&&this.delete==0) {
            this.idtmp = this.id + ramdomLets(7);
			var para = "<div class='paramsQAA' ";
            para += " onClick='showQuickActiveAreaManager(\"" + this.idtmp + "\");' ></div>";
            para += "<div id='resizeQAA" + this.idtmp + "' class='resizeQAA' ></div>";
            para += "<div id='resizeHAA" + this.idtmp + "' class='resizeHAA' ></div>";
            if (this.type==5) {
                para +='<div style="padding:5px;" id="tmptxt'+this.idtmp+'" >'+convertDataToHtml(this.data)+'</div>';
            }
            var objR = "<div id='areaZA" + this.idtmp + "' ";
            objR += " style='left:"+this.x+"%;top:"+this.y+"%;width:"+this.w+"%;height:"+this.h+"%;' ";
            objR +=  "class='areaQAA"+this.type+" noselect' >"+para+"</div>";
            if ($('#editorZiiArea'+editorZiiAreaTmp).length==1) {
                $('#editorZiiArea'+editorZiiAreaTmp).append(objR);
                eventsFlipZoneA(this.idtmp);
                this.create = 1;
            } else {
                console.log('Error: editorZiiArea'+editorZiiAreaTmp+' not found');
            }
		}
	}
	
}

function QuickActiveArea_New(){
    var elem  = new QuickActiveArea();
    elem.page = idFlipPage;
    elem.x = 5;
    elem.y = 5;
    elem.w = 10;
    elem.h = 5;
    elem.type = 0;
    elem.data = 0;
    elem.create = 0;
    QuickActiveArea_Add(elem);
}

function QuickActiveArea_Add(Elem){
    Elem.id = QuickActiveAreas_count;
    QuickActiveAreas.push(Elem);
    QuickActiveAreas_count = QuickActiveAreas_count + 1;
    QuickActiveArea_Paint();
}

function QuickActiveArea_Paint(){

	for (var i = 0; i < QuickActiveAreas_count; i++){
        var objElem = QuickActiveAreas[i];
        if (objElem.page==idFlipPage) {
            objElem.show();
        }
	}
	
}

function QuickActiveArea_ResetPages() {

	for (var i = 0; i < QuickActiveAreas_count; i++){
        var objElem = QuickActiveAreas[i];
        if (objElem.page==idFlipPage) {
            objElem.create = 0;
            objElem.show();
        }
	}
	
}

function QuickActiveArea_getbyid(id) {

    var elem  = new QuickActiveArea();
	for (var i = 0; i < QuickActiveAreas_count; i++){
        var objElem = QuickActiveAreas[i];
        if (objElem.id==id||objElem.idtmp==id) {
            elem = objElem;
        }
	}
	return elem;
}

function ramdomLets(n) {
    var text = "";
    var possible = "abcdefghijklmnopqrstuvwxyz";
    for (var i = 0; i < n; i++) {
        text += possible.charAt(Math.floor(Math.random() * possible.length));
    }
    return text;
}

function showQuickActiveAreaManager(idtmp) {

    if($("#QuickActiveAreaManager").length==0){
        $("body").append('<div class="QuickActiveAreaManager_Cover" ></div>');
        $("body").append('<div id="QuickActiveAreaManager" class="QuickActiveAreaManager" ></div>');
    }
    $("#QuickActiveAreaManager").html(innerQuickActiveAreaManager(idtmp));
    
    $("#QuickActiveAreaManager").css('display','');
    $(".QuickActiveAreaManager_Cover").css('display','');
    $("#closequizmana").css('display','');
}

function innerQuickActiveAreaManager(idtmp) {
    
    var obj = QuickActiveArea_getbyid(idtmp);
    var ch = '';
    editorTxtZii = '';
    editorIsLoad = false;
    var h = '';
    h += '<div class="QuickActiveAreaManager_title" >&nbsp;&nbsp;Action&nbsp;Editor</div>';
    h += '<div class="closeQuickActiveAreaManager" onClick="closeQuickActiveAreaM()" >X</div>';
    h += '<div id="innerQuickActiveAreaManager" >';
    
    // Radio box option list
    if (obj.type==0) { ch = 'checked'; }
    h += '<div class="QuickActiveAreaManager_option_mid" >';
    h += '<input ' + ch + ' type="radio" id="typeQA0" name="typeQAA" value="0" >';
    h += '<label for="typeQA0" >Zoom</label>';
    h += '</div>';
    ch = '';

    ch = ''; if (obj.type==4) { ch = 'checked'; }
    h += '<div class="QuickActiveAreaManager_option_mid" >';
    h += '<input ' + ch + ' type="radio" id="typeQA4" name="typeQAA" value="4" >';
    h += '<label for="typeQA4" >Mask</label>';
    h += '</div>';

    ch = ''; if (obj.type==1) { ch = 'checked'; }
    h += '<div class="QuickActiveAreaManager_option_mid" >';
    h += '<input ' + ch + ' type="radio" id="typeQA1" name="typeQAA" value="1" >';
    h += '<label for="typeQA1" >Next page</label>';
    h += '</div>';

    ch = ''; if (obj.type==2) { ch = 'checked'; }
    h += '<div class="QuickActiveAreaManager_option_mid" >';
    h += '<input ' + ch + ' type="radio" id="typeQA2" name="typeQAA" value="2" >';
    h += '<label for="typeQA2" >Previous page</label>';
    h += '</div>';

    ch = ''; if (obj.type==3) { ch = 'checked'; }
    h += '<div class="QuickActiveAreaManager_option" >';
    h += '<input ' + ch + ' type="radio" id="typeQA3" name="typeQAA" value="3" >';
    h += '<label for="typeQA3" >to Page Number</label>';
    h += '<input type="number" class="numberPage" ';
    h += ' value="' + forceParseInteger(obj.data) + '" />';
    h += '</div>';
    
    ch = ''; if (obj.type==5) { 
        ch = 'checked'; 
        editorTxtZii = obj.data;
    }
    h += '<div class="QuickActiveAreaManager_option_mid" >';
    h += '<input ' + ch + ' type="radio" id="typeQA5" name="typeQAA" value="5" >';
    h += '<label for="typeQA5" >HTML</label>';
    h += '<div class="editPage" onClick="loadTxtEditorZii();" ></div>';
    h += '</div>';

    ch = ''; if (obj.type==6) { ch = 'checked';
        editorlinkZii = obj.data;
    }
    h += '<div class="QuickActiveAreaManager_option_mid" >';
    h += '<input ' + ch + ' type="radio" id="typeQA6" name="typeQAA" value="6" >';
    h += '<label for="typeQA6" >Pop Link</label>';
    h += '<div class="editPage" onClick="loadTxtEditorZii();" ></div>';
    h += '</div>';

    h += '<div class="QuickActiveAreaManager_option_mid" >';
    h += '<input ' + ch + ' type="radio" id="typeQA7" name="typeQAA" value="7" >';
    h += '<label for="typeQA7" >Pop Quiz</label>';
    h += '<div class="editPage" onClick="loadTxtEditorZii();" ></div>';
    h += '</div>';

    h += '<p style="width:97%;text-align:center;margin:15px;padding:5px;float:left;" >';
    h += '<a onClick="saveQuickActiveAreaM(\''+idtmp+'\');" ';
    h += ' class="btn btn-info" >&nbsp;OK&nbsp;</a></p>';

    var imgLoad = '<img src="'+ _p['web_plugin'] + 'chamidoc_tools/resources/img/delete-icon-24.png" />';
    h += '<a onClick="if (confirm(\'Delete ?\')) DeleteTxtLinkZii(\''+idtmp+'\');" ';
    h += ' style="position:absolute;left:5px;bottom:5px;background:white;color:white;" ';
    h += ' class="btn btn-classic" >&nbsp;' + imgLoad + '&nbsp;</a>';

    h += '</div>';

    incremAreaZii ++;

    h += '<div id="innerQuickActiveAreaTxtEditor" class="innerQuickActiveAreaTxtEditor" >';
    
    h += '<textarea id="content_option_area' + incremAreaZii + '" ';
    h += ' name="content_option_area" class="ckeditor" >';
    h += convertDataToHtml(editorTxtZii) + '</textarea>';

    h += '<p style="width:97%;text-align:center;margin:5px;padding:5px;float:left;" >';
    h += '<a onclick="ApplyTxtEditorZii();" ';
    h += ' class="btn btn-info" >&nbsp;Apply&nbsp;</a></p>';

    h += '</div>';

    h += '<div id="innerQuickActiveAreaTxtLink" class="innerQuickActiveAreaTxtLink" >';
    h += '<p>Data link :&nbsp;<a style="float:right;cursor:pointer;" ';
    h += ' onClick="helpLinkZii();" >&darr;</a>&nbsp;&nbsp;&nbsp;&nbsp;</p>';
    h += '<input type="text" value="' + convertDataToHtml(editorlinkZii) + '" ';
    h += ' id="content_option_link" style="width:97%;" />';

    h += '<p style="width:97%;text-align:center;margin:5px;padding:5px;float:left;" >';;
    h += '<a onclick="ApplyTxtLinkZii();" class="btn btn-info" >&nbsp;Apply&nbsp;</a>';
    h += '</p>';

    h += '</div>';

    return h;

}

var editorIsLoad = false;
var editorTxtZii = '';
var editorlinkZii = '';

function loadTxtEditorZii() {
    
    var typeQAA = $('input[name=typeQAA]:checked').val();
    if (typeQAA==5) {
        $("#innerQuickActiveAreaManager").css("display","none");
        $("#innerQuickActiveAreaTxtEditor").css("display","block");
        if (editorIsLoad==false) {
            initCKEEditorMini('content_option_area'+incremAreaZii,true);
            editorIsLoad = true;
        }
    }
    if (typeQAA==6) {
        $("#innerQuickActiveAreaManager").css("display","none");
        $("#innerQuickActiveAreaTxtLink").css("display","block");
    }

}

function ApplyTxtEditorZii() {

    var ckArea =  CKEDITOR.instances['content_option_area'+incremAreaZii];
    if (ckArea) {
        var ck_area_txt = ckArea.getData();
        editorTxtZii = convertHtmlToData(ck_area_txt);
        $("#innerQuickActiveAreaManager").css("display","block");
        $("#innerQuickActiveAreaTxtEditor").css("display","none");
    }

}

function DeleteTxtLinkZii(idtmp) {
    var obj = QuickActiveArea_getbyid(idtmp);
    obj.delete = 1;
    $('#tmptxt'+idtmp).remove();
    $('#areaZA'+idtmp).css("background","red");
    setTimeout(() => {
        $('#areaZA'+idtmp).remove();
    }, 300);
    closeQuickActiveAreaM();
}

function ApplyTxtLinkZii() {
    $("#innerQuickActiveAreaManager").css("display","block");
    $("#innerQuickActiveAreaTxtLink").css("display","none");
    editorlinkZii = convertHtmlToData($('#content_option_link').val());
}

function forceParseInteger(data) {
    data = data+'';
    data = data.replace(/[^0-9]/g, '');
    if (data=='') {data = 0;}
    return parseInt(data);
}

function saveQuickActiveAreaM(idtmp) {

    // Get radio typeQAA value
    var typeQAA = $('input[name=typeQAA]:checked').val();
    var obj = QuickActiveArea_getbyid(idtmp);
    obj.type = typeQAA;
    if (obj.type==3) {
        obj.data = parseInt($('.numberPage').val());
    }
    if (obj.type==5) {
        obj.data = editorTxtZii;
        $('#tmptxt'+idtmp).remove();
        $('#areaZA'+idtmp).append('<div style="padding:5px;" id="tmptxt'+idtmp+'" >'+convertDataToHtml(obj.data)+'</div>');
    } else {
        $('#tmptxt'+idtmp).remove();
    }
    if (obj.type==6) {
        obj.data = editorlinkZii;
    }
    $('#areaZA'+idtmp).removeClass('areaQAA1').removeClass('areaQAA2');
    $('#areaZA'+idtmp).removeClass('areaQAA3').removeClass('areaQAA4');
    $('#areaZA'+idtmp).removeClass('areaQAA5').removeClass('areaQAA6');
    $('#areaZA'+idtmp).removeClass('areaQAA7').removeClass('areaQAA8');
    $('#areaZA'+idtmp).removeClass('areaQAA9').removeClass('areaQAA10');
    $('#areaZA'+idtmp).addClass('areaQAA'+typeQAA);

    closeQuickActiveAreaM();

}

function closeQuickActiveAreaM() {
    $("#QuickActiveAreaManager").css('display','none');
    $(".QuickActiveAreaManager_Cover").css('display','none');
}

function convertHtmlToData(txt) {
    txt = txt.replace(/</g,'zilt;');
    txt = txt.replace(/>/g,'zigt;');
    txt = txt.replace(/\//g,'zisol;');
    return txt;
}
function convertDataToHtml(txt) {
    txt = txt.replace(/zilt;/g,'<');
    txt = txt.replace(/zigt;/g,'>');
    txt = txt.replace(/zisol;/g,'/');
    return txt;
}

function helpLinkZii() {
    
    var h = 'https://en.m.wikipedia.org/wiki/Chamilo';
    $('#content_option_link').val(h);

}function renderslidesactive(var1,var2) {

    var h = '';
    
    h += '<div class="plugslidesactive" >';
    h += '<img class="slidesactive" src="img/slideactive.jpg" />';
    h += '</div>';

    h += '<span class=typesource >slidesactive</span>';

    return h;

}
function displaySlidesActiveEdit(myObj){

	var ImageSlideObj = $(myObj);
	tmpObjDom = ImageSlideObj;
	
	var datatext1 = '';
	var datatext2 = '';
	var datatext3 = '';
	var datatext4 = '';
	var datatext5 = '';

	var ObjDivhref = ImageSlideObj.find('div');
	
	datatext1 = ObjDivhref.parent().find('span.datatext1').html();
	datatext2 = ObjDivhref.parent().find('span.datatext2').html();
	datatext3 = ObjDivhref.parent().find('span.datatext3').html();
	datatext4 = ObjDivhref.parent().find('span.datatext4').html();
	datatext5 = ObjDivhref.parent().find('span.datatext5').html();
	
	if(datatext1===undefined){datatext1 = '';}
	if(datatext2===undefined){datatext2 = '';}
	if(datatext3===undefined){datatext3 = '';}
	if(datatext4===undefined){datatext4 = '';}
	if(datatext5===undefined){datatext5 = '';}

	if(datatext1==="undefined"){datatext1 = '';}
	if(datatext2==="undefined"){datatext2 = '';}
	if(datatext3==="undefined"){datatext3 = '';}
	if(datatext4==="undefined"){datatext4 = '';}
	if(datatext5==="undefined"){datatext5 = '';}

	optionsIZA1 = datatext1;
	optionsIZA2 = datatext2;
	optionsIZA3 = datatext3;
	optionsIZA4 = datatext4;
	optionsIZA5 = datatext5;

	strLinkString = ImageSlideObj.attr("datahref");

	$('.ludimenu').css("display","none");

	launchEditWindowsZII();
	
	windowEditorIsOpen = true;
	loadaFunction();

}

var optionsGlobalFiles;
var filterGlobalFiles = '';

function preSelectFileWrapper() {

    $('.media-menu-item').removeClass("media-menu-active");
    $('.menu-insert').addClass("media-menu-active");

    $('.attachments-wrapper').css("display","block");
    $('.attachments-wrapper-upload').css("display","none");
    $('.attachments-wrapper-clipart').css("display","none");
    $('.attachments-wrapper-files').css("display","none");

}

function preSelectFileTab() {

    $('.media-menu-item').removeClass("media-menu-active");
    $('.menu-insert').addClass("media-menu-active");

    $('.attachments-wrapper').css("display","none");
    $('.attachments-wrapper-upload').css("display","none");
    $('.attachments-wrapper-clipart').css("display","none");

    $('.attachments-wrapper-files').css("display","block");

}

function loadDataFileTab() {

    var bdDiv = '<p style="text-align:center;" ><br/>';
    bdDiv += '<img src="img/cube-oe.gif" /><br/><br/></p>';
    
    $('.attachments-wrapper-files').html(bdDiv);

    $.ajax({
		url : 'img_cache/getfiles.php?idteach=' + idPageHtml + '&cotk=' + $('#cotk').val(),
		type: "POST",
        dataType : 'json',
		success: function(data,textStatus,jqXHR){
            var tableH = '';
            optionsGlobalFiles = data;
            if(optionsGlobalFiles.files.length>0){
                $.each(optionsGlobalFiles.files,function(){
                    if (selectFileNameManaged==this.src) {
                        tableH += createThumbAccessSlider(this.src,1,this.nameonly,this.usefile,1);
                    } else {
                        tableH += createThumbAccessSlider(this.src,0,this.nameonly,this.usefile,1);
                    }
                });
                tableH += createThumbdirectUpload();
                $('.attachments-wrapper-files').html(tableH);
             
            } else {
                $('.attachments-wrapper-files').html(createThumbdirectUpload());
            }
		},
		error: function (jqXHR, textStatus, errorThrown)
		{
            $('.attachments-wrapper-files').html("Error");
		}
	});

}

function createThumbAccessSlider(src,act,nam,usefile,larg) {

    if (typeFiletManaged==13) {
        if (filterGlobalFiles=='.svg') {
            if (src.indexOf('.svg')==-1) {
                return '';
            }
        }
    }
    
    if (typeFiletManaged==23) {
        if (filterGlobalFiles!='') {
            if (filterGlobalFiles=='.mp4') {
                if (src.indexOf('.mp4')==-1) {
                    return '';
                }
            }
            if (filterGlobalFiles=='.mp3') {
                if (src.indexOf('.mp3')==-1) {
                    return '';
                }
            }
            if (filterGlobalFiles=='.mp4.pdf') {
                if (src.indexOf('.mp4')==-1&&src.indexOf('.pdf')==-1) {
                    return '';
                }
            }
        }
    }

    if (nam==''&&src!='') {
        nam = getFileNameByUrl(src);
    }
    
    var bdDiv = '<div class="bloc-img-manager-' + larg + '" ';
    bdDiv += 'dataSrc="' + src + '" ';
    bdDiv += ' onClick="selectThumbSlider(this,' + usefile + ')" >';
    
    if (act==0) {
        bdDiv += '<div class="bloc-img-view">';
    } else {
        bdDiv += '<div class="bloc-img-view bloc-img-view-active">';
    }
    
    if (isImageFile(src)) {
        src = src.replace(/web_plugin\|/,_p['web_plugin']);
        bdDiv += '<img src="' + src + '" />';
    } else {
        if (src.indexOf('.mp4')!=-1) {
            bdDiv += '<img src="icon/bouton-video.png" />';
            bdDiv += '<div class="bloc-img-title" >'+nam+'</div>';
        } else if (src.indexOf('.mp3')!=-1) {
            bdDiv += '<img src="icon/audio.png" />';
            bdDiv += '<div class="bloc-img-title" >'+nam+'</div>';
        } else if (src.indexOf('.pdf')!=-1) {
            bdDiv += '<img src="icon/file-pdf-center.png" />';
            bdDiv += '<div class="bloc-img-title" >'+nam+'</div>';
        } else if (src.indexOf('.odt')!=-1) {
            bdDiv += '<img src="icon/file-writer-center.png" />';
            bdDiv += '<div class="bloc-img-title" >'+nam+'</div>';
        } else if (src.indexOf('.ods')!=-1) {
            bdDiv += '<img src="icon/file-calc-center.png" />';
            bdDiv += '<div class="bloc-img-title" >'+nam+'</div>';
        } else {
            bdDiv += '<img src="icon/file-doc-center.png" />';
            bdDiv += '<div class="bloc-img-title" >'+nam+'</div>';
        }
    }

    if (src.indexOf('img/classique')==-1&&src.indexOf('img_cache')!=-1 ) {
        if (usefile==0) {
            bdDiv += '<a title="' + returnTradTerm('Not used in this project !') + '" class="bloc-img-notuse" ></a>';
        }
    }

    bdDiv += '</div>';

    bdDiv += '</div>';

    return bdDiv;

}

function createThumbdirectUpload() {

    var bdDiv = '<div onClick="directUploadFileInStudio();" ';
    if (modeUIeol=='b') {
        bdDiv = '<div onClick="uploadFileInSlider();" ';
    }
    bdDiv += ' style="cursor:pointer;" class="bloc-img-manager-1" >';
    bdDiv += '<div class="bloc-img-view" style="border:1px dotted #7F8C8D!important;" >';
    bdDiv += '<img src="icon/uploadfiles.png" />';
    bdDiv += '<div class="bloc-img-title" >Upload a file</div>';
    bdDiv += '</div>';
    bdDiv += '</div>';
    return bdDiv;

}
var moveAFxObj = false;

var GFXSrcTop = '<table class="teachdocplugteach" ';
GFXSrcTop += 'onMouseDown="parent.displayEditButon(this);" ';
GFXSrcTop += ' style="width:100%;text-align:center;" >';

var GFXSrcT = '<tr><td style="text-align:center;padding:10px;width:100%;position:relative;" >';
GFXSrcT += '<div class="plugteachcontain" >';
GFXSrcT += '{content}';
GFXSrcT += '</div>';
GFXSrcT += '</td></tr>';

var GFXSrcBottom = '</table>';

var firstSrcFX = GFXSrcT.replace("{content}",'$pluginfx-obj$');

var styFX = "background-image: url('icon/fxobjetblue.png');";
styFX += "background-repeat:no-repeat;";
styFX += "background-position:center center;";

editor.BlockManager.add('fxTools',{
	label : 'fxTools',
	attributes : {
		class : 'fa fa-text icon-plugTeach',
		style : styFX
	},
	category: 'Basic',
	content: {
		content: GFXSrcTop+firstSrcFX+GFXSrcBottom,script: "",
		style: {
			width: '100%',
			minHeight: '70px'
		}
	}
});

styFX = "background-image: url('icon/empty.png');";
styFX += "background-repeat:no-repeat;";
styFX += "background-position:center center;";
editor.BlockManager.add('emptyTools',{
	label : '',
	attributes : {
		class : 'fa fa-text icon-plugTeach empty-plugTeach',
		style : styFX
	},
	category: 'Basic',
	content: {
		content: GFXSrcTop+firstSrcFX+GFXSrcBottom,script: "",
		style: {
			width: '100%',
			minHeight: '70px'
		}
	}
});


function searchNewTeachdocfx(){
	
	if (moveAFxObj) {
		
		moveAFxObj = false;

		var iframe = $('.gjs-frame');
		var iframeBody = iframe.contents().find("body");
		var allTables = iframeBody.find("table");

		allTables.each(function(index){
			
			if($(this).hasClass("teachdocplugteach")){
				var srcB = $(this).html();
				if (srcB.indexOf("$pluginfx-obj$")!=-1) {
					displayFXTeachList(this);
				}
			}
			
		});	
	}

	setTimeout(function(){
		searchNewTeachdocfx();
	},300);

}

setTimeout(function(){
	searchNewTeachdocfx();
},1000);

var loadFXObjectevent = false;

var loadFXObjectIndex = 0;

function displayFXTeachList(myObj){

	var btnObj = $(myObj);
	tmpObjDom = btnObj;
	
	identchange = getUnikId();
	tmpObjDom.attr("data-ref",identchange);
	
	if ($("#BtnFXTeachList").length==0) {
		
		var bdDiv = '<div id="BtnFXTeachList" ';
		bdDiv += ' class="gjs-mdl-container BtnFXTeachList" >';

		bdDiv += '<div class="gjs-mdl-dialog-v2 gjs-one-bg gjs-two-color" ';
		bdDiv += ' style="max-width:780px!important;" >';
		
		bdDiv +=  '<div class="gjs-mdl-header">';
		bdDiv += '<div class="gjs-mdl-title trad">Content elements</div>';
		bdDiv += '<div id="closeobjfxarea" class="gjs-mdl-btn-close gjs-mdl-btn-close-audio" ';
		bdDiv += ' onClick="cancelFXObj()" ';
		bdDiv += ' data-close-modal="">⨯</div>';
		bdDiv += '</div>';

		bdDiv += '<div class="fxtoolsdescription" >';
		bdDiv += '</div>';
		
		bdDiv += '<div id="listobjfxarea" class="gjs-am-add-asset" ';
		bdDiv += 'style="padding:25px;padding-top:10px;font-size:16px;" >';
		
		bdDiv += generateIconFx('Dance Trophy','animfx');
		
		// bdDiv += generateIconFx('Life bar','lifebar');
		bdDiv += generateIconFx('Text MathJax','txtmathjax');
		
		bdDiv += generateIconFx('intro bloc','oelcontentcardinfo');
		bdDiv += generateIconFx('text bloc circle','oelcontentcardinfocircle');
		bdDiv += generateIconFx('Photo legend','oelcontentphotowtitle');

		bdDiv += generateIconFx('2 lists','oelcontentlistbox50');
		bdDiv += generateIconFx('List and image','oelcontentlistboximg50');

		if (modeUIeol=='a') { // Alpha
			bdDiv += generateIconFx('Summary','oelcontentsummarywomen');
			//bdDiv += generateIconFx('Leveldoc selector','oelcontentarealeveldoc');
			bdDiv += generateIconFx('Mag cover01','oelcontentmagcover01');

			bdDiv += generateIconFx('Resume cards','oelcontentnumericcards');
			bdDiv += generateIconFx('key points','oelcontentkeypointsblock');
		}

		bdDiv += '</div>';
		bdDiv += '</div>';

		bdDiv += '<div class="gjs-mdl-collector" style="display:none"></div>';
		bdDiv += '</div>';

		$('body').append(bdDiv);

	}
    
	if($("#BtnFXTeachList").length==1){
		
		selectFXObj('')
		$('.ludimenu').css("z-index",'2');
		$('#BtnFXTeachList').css("display",'');
		windowEditorIsOpen = true;
		loadaFunction();
		traductAll();
		
	}
	
}

function selectFXObj(typesource){

	loadFXObjectIndex++;

	var b = '';

	if (typesource == "youtubevideo") {
		b += '<h2 class="titlefx" >Youtube Object</h2>';
		b += '<div class="previewfxobj" ';
		b += ' style="background-image:url(icon/' + typesource + '-d.png);" ></div>';
		b += '<p class="descrifx" >';
		b += 'The simplest method to embed a YouTube video in a page without IFrame';
		b += '</p>';
	}

	if (typesource == "lifebar") {
		b += '<h2 class="titlefx" >Life bar</h2>';
		b += '<div class="previewfxobj" ';
		b += ' style="background-image:url(icon/' + typesource + '-d.png);" ></div>';
		b += '<p class="descrifx" >';
		b += 'Add a Gaming Life Bar in interface';
		b += '</p>';
	}

	if (typesource == "animfx") {
		b += '<h2 class="titlefx" >Dance Trophy</h2>';
		b += '<div class="previewfxobj" ';
		b += ' style="background-image:url(img/white-medium-star.svg?v='+loadFXObjectIndex+');" ></div>';
		b += '<p class="descrifx" >';
		if (langselectUI=='fr_FR') {
			b += 'Ajouter un trophée d\'animation dans l\'interface';
		} else {
			b += 'Add a animation Trophy in interface';
		}
		b += '</p>';
	}

	if (typesource == "googledoc") {
		b += '<h2 class="titlefx" >Google Doc</h2>';
		b += '<div class="previewfxobj" ';
		b += ' style="background-image:url(icon/googledoc-d.png);" ></div>';
		b += '<p class="descrifx" >';
		b += 'Add a Google Doc integration in interface';
		b += '</p>';
	}

	if (typesource == "txtmathjax") {
		b += '<h2 class="titlefx" >Text MathJax</h2>';
		b += '<div class="previewfxobj" ';
		b += ' style="background-image:url(icon/txtmathjax-d.png);" ></div>';
		b += '<p class="descrifx" >';
		if (langselectUI=='fr_FR') {
			b += 'Un texte avec intégration MathJax';
		} else {
			b += 'Add a text with MathJax integration';
		}
		b += '</p>';
	}

	if (typesource == "oelcontentcardinfo") {
		b += '<h2 class="titlefx" >Large info-box</h2>';
		b += '<div class="previewfxobj" ';
		b += ' style="background-image:url(icon/' + typesource + '-d.png);" ></div>';
		b += '<p class="descrifx" >';
		if (langselectUI=='fr_FR') {
			b += "Une infobox avec un titre, un sous-titre, une image, une description dans une boîte.";
		} else {
			b += 'This is a infobox with a title, a sub-title, an image, description in a box.';
		}
			b += '</p>';
	}

	if (typesource == "oelcontentcardinfocircle") {
		b += '<h2 class="titlefx" >Large info-box</h2>';
		b += '<div class="previewfxobj" ';
		b += ' style="background-image:url(icon/' + typesource + '-d.png);" ></div>';
		b += '<p class="descrifx" >';
		if (langselectUI=='fr_FR') {
			b += "Une infobox avec un titre, un sous-titre, une image ronde, une description dans une boîte.";
		} else {
			b += 'This is a infobox with a title, a sub-title, a circle image, description in a box.';
		}
		b += '</p>';
	}

	if (typesource == "oelcontentphotowtitle") {
		b += '<h2 class="titlefx" >Photo and legend</h2>';
		b += '<div class="previewfxobj" ';
		b += ' style="background-image:url(icon/' + typesource + '-d.png);" ></div>';
		b += '<p class="descrifx" >';
		if (langselectUI=='fr_FR') {
			b += 'Une photo avec une légende.';
		} else {
			b += 'This is a photo with a description.';
		}
		b += '</p>';
	}

	if (typesource == "oelcontentlistbox50") {
		b += '<h2 class="titlefx" >Two lists</h2>';
		b += '<div class="previewfxobj" ';
		b += ' style="background-image:url(icon/' + typesource + '-d.png);" ></div>';
		b += '<p class="descrifx" >';
		if (langselectUI=='fr_FR') {
			b += 'Deux listes côte à côte.';
		} else {
			b += 'Two liste.';
		}
		b += '</p>';
	}

	if (typesource == "oelcontentlistboximg50") {
		b += '<h2 class="titlefx" >List and image</h2>';
		b += '<div class="previewfxobj" ';
		b += ' style="background-image:url(icon/' + typesource + '-d.png);" ></div>';
		b += '<p class="descrifx" >';
		if (langselectUI=='fr_FR') {
			b += 'Une liste avec une image à côté.';
		} else {
			b += 'Two liste.';
		}
		b += '</p>';
	}

	if (typesource == "oelcontentsummarywomen"&&modeUIeol=='a') {
		b += '<h2 class="titlefx" >Summary</h2>';
		b += '<div class="previewfxobj" ';
		b += ' style="background-image:url(icon/' + typesource + '-d.png);" ></div>';
		b += '<p class="descrifx" >';
		if (langselectUI=='fr_FR') {
			b += 'Une liste sous la forme d\'un sommaire.';
		} else {
			b += 'Two liste.';
		}
		b += '</p>';
	}

	if (typesource == "oelcontentarealeveldoc"&&modeUIeol=='a') {
		b += '<h2 class="titlefx" >Level doc select</h2>';
		b += '<div class="previewfxobj" ';
		b += ' style="background-image:url(icon/' + typesource + '-d.png);" ></div>';
		b += '<p class="descrifx" >';
		if (langselectUI=='fr_FR') {
			b += 'Un sélecteur de niveau de document.';
		} else {
			b += 'Two liste.';
		}
		b += '</p>';
	}

	if (typesource == "oelcontentmagcover01"&&modeUIeol=='a') {
		b += '<h2 class="titlefx" >Mag cover 01</h2>';
		b += '<div class="previewfxobj" ';
		b += ' style="background-image:url(icon/' + typesource + '-d.png);" ></div>';
		b += '<p class="descrifx" >';
		if (langselectUI=='fr_FR') {
			b += 'Une couverture de magazine avec un titre, un sous-titre, une image, une description dans une boîte.';
		} else {
			b += 'This is a magazine cover with a title, a sub-title, an image, description in a box.';
		}
		b += '</p>';
	}
	
	if (typesource == "oelcontentnumericcards"&&modeUIeol=='a') {
		b += '<h2 class="titlefx" >Numeric cards</h2>';
		b += '<div class="previewfxobj" ';
		b += ' style="background-image:url(icon/' + typesource + '-d.png);" ></div>';
		b += '<p class="descrifx" >';
		if (langselectUI=='fr_FR') {
			b += 'Des cartes numériques avec un titre, une description et un numéro.';
		} else {
			b += 'This is a numeric cards with a title, a description and a number.';
		}
		b += '</p>';
	}

	if (typesource == "oelcontentkeypointsblock"&&modeUIeol=='a') {
		b += '<h2 class="titlefx" >Key points</h2>';
		b += '<div class="previewfxobj" ';
		b += ' style="background-image:url(icon/' + typesource + '-d.png);" ></div>';
		b += '<p class="descrifx" >';
		if (langselectUI=='fr_FR') {
			b += 'Un bloc de points clés avec un titre et une liste de points.';
		} else {
			b += 'This is a key points block with a title and a list of points.';
		}
		b += '</p>';
	}

	if (b == "") {
		b += '<h2 class="titlefx" >FX Object</h2>';
		b += '<div class="previewfxobj" ';
		b += ' style="background-image:url(icon/basic-d.png);" ></div>';
		b += '<p class="descrifx" >';
		if (langselectUI=='fr_FR') {
			b += 'Sélectionnez un objet.';
		} else {
			b += 'Select an object.';
		}
		b += '</p>';
	} else {
		var txtInsert = 'Insert';
		if (langselectUI=='fr_FR') {
			txtInsert = 'Insérer';
		}
		b += '<div style="position:absolute;right:0px;bottom:0px;padding:10px;padding-top:10px;text-align:right;" >';
		b += '<input id="ludiButtonSaveFxObj" onClick="applyFXObj(\''+typesource+'\')" ';
		b += ' class="gjs-one-bg ludiButtonSave trad" type="button" value="'+txtInsert+'" /><br/>';
		b += '</div>';
	}

	$('.fxtoolsdescription').html(b);

	traductAll();

}

function applyFXObj(typesource){

    if(onlyOneUpdate==false){
		return false;
	}

	var rP = '';
	var datatext1 = '';
    var datatext2 = '';
	if (typesource=='youtubevideo') {
        datatext1 = 'https://www.youtube.com/embed/pefhbQ1gzUw';
        datatext2 = '450';
		rP = renderpluginyoutubevideo(datatext1,datatext2);
	}

	if (typesource=='googledoc') {
        datatext1 = '';
        datatext2 = '450';
		rP = renderplugingoogledoc(datatext1,datatext2);
	}
	
	if (typesource=='animfx') {
        datatext1 = 'img/classique/white-medium-star.svg';
        datatext2 = '';
		rP = renderpluginanimfx(datatext1,datatext2);
	}
	
	if (typesource=='lifebar') {
        datatext1 = '3';datatext2 = '';
		rP = renderpluginlifebar(datatext1,datatext2);
	}

	if (typesource=='txtmathjax') {
        datatext1 = '';datatext2 = '';
		rP = renderplugintxtmathjax(datatext1,datatext2);
	}
	
	if (typesource=='oelcontentcardinfo') {
        datatext1 = '';datatext2 = '';
		rP = renderpluginoelcontentcardinfo(datatext1,datatext2);
	}
	if (typesource=='oelcontentcardinfocircle') {
        datatext1 = '';datatext2 = '';
		rP = renderpluginoelcontentcardinfocircle(datatext1,datatext2);
	}
	if (typesource=='oelcontentphotowtitle') {
        datatext1 = '';datatext2 = '';
		rP = renderpluginoelcontentphotowtitle(datatext1,datatext2);
	}
	if (typesource=='oelcontentlistbox50') {
        datatext1 = '';datatext2 = '';
		rP = renderpluginoelcontentlistbox50(datatext1,datatext2);
	}
	if (typesource=='oelcontentlistboximg50') {
        datatext1 = '';datatext2 = '';
		rP = renderpluginoelcontentlistboximg50(datatext1,datatext2);
	}
	if (typesource=='oelcontentsummarywomen') {
        datatext1 = '';datatext2 = '';
		rP = renderpluginoelcontentsummarywomen(datatext1,datatext2);
	}
	if (typesource=='oelcontentarealeveldoc') {
        datatext1 = '';datatext2 = '';
		rP = renderpluginoelcontentarealeveldoc(datatext1,datatext2);
	}
	if (typesource=='oelcontentmagcover01') {
		datatext1 = '';datatext2 = '';
		rP = renderpluginoelcontentmagcover01(datatext1,datatext2);
	}
	if (typesource=='oelcontentnumericcards') {
		datatext1 = '';datatext2 = '';
		rP = render_oellearningnumericcards(datatext1,datatext2);
	}
	if (typesource=='oelcontentkeypointsblock') {
		datatext1 = '';datatext2 = '';
		rP = render_oelcontentkeypointsblock(datatext1,datatext2);
	}

    if (rP=="") {
        reloadPageErr();
        return false;
    }

	$('#ludiButtonSaveFxObj').css("display","none");

	$('#listobjfxarea').html("<p style='text-align:center;' ><br/><img src='img/cube-oe.gif' /><br/><br/></p>");
	$('#closeobjfxarea').css("display","none");

    var paramsDB = '<span class=datatext1  >'+datatext1+'</span>';
    paramsDB += '<span class=datatext2  >'+datatext2+'</span>';
    paramsDB += '<span class=typesource >' ;
    paramsDB +=  typesource + '</span>';

    var rH = GFXSrcT;
	
	if (typesource=='lifebar') {
		rH = rH.replace('plugteachcontain','plugteachuicontain');
	}
    rH = rH.replace("{content}",rP + paramsDB);
	
    tmpObjDom.html(rH);
	
    var gjsHtml = localStorage.getItem("gjs-html-" + idPageHtml);
	if(gjsHtml.indexOf("$pluginfx-obj$")!=-1){
		gjsHtml = gjsHtml.replace('$pluginfx-obj$',rP + paramsDB);
		gjsHtml = gjsHtml.replace('$pluginfx-obj$',rP + paramsDB);
		localStorage.setItem("gjs-html-" + idPageHtml,gjsHtml);
		loadFXObjectevent = true;
		saveSourceFrame(false,false,0);
	}else{
        reloadPageErr();
        return false;
    }

}

function cancelFXObj(){

	tmpObjDom.parent();
	tmpObjDom.html('');
	var gjsHtml = localStorage.getItem("gjs-html-" + idPageHtml);
	if(gjsHtml.indexOf("$pluginfx-obj$")!=-1){
		gjsHtml = gjsHtml.replace('$pluginfx-obj$','');
		gjsHtml = gjsHtml.replace('$pluginfx-obj$','');
		localStorage.setItem("gjs-html-" + idPageHtml,gjsHtml);
		$('.ludimenu').css("z-index","1000");
		saveSourceFrame(false,false,0);
	}
	closeAllEditWindows();

}

function generateIconFx(title,typesrc) {

    var bdDiv = '<a class="fxtoolscube" ';
    bdDiv += ' style="background-image:url(icon/' + typesrc + '.png);" ';
    bdDiv += 'onclick="selectFXObj(\'' + typesrc + '\');" >';
    bdDiv += '<div class="fxtoolstitle" >';
    bdDiv += title + '</div>';
    bdDiv += '</a>';

    return bdDiv;

}

function getinputFXObj(typesource) {

	var bdDiv = "";
   //FX
	if (typesource=='youtubevideo') {
		bdDiv += txtParamsPlugTeach(1,typesource,'Link youtube');
		bdDiv += heightParamsPlugTeach(2,typesource,'Height');
	}
	if (typesource=='googledoc') {
		bdDiv += txtParamsPlugTeach(1,typesource,'Link google doc');
		bdDiv += heightParamsPlugTeach(2,typesource,'Height');
	}
	if (typesource=='lifebar') {
		bdDiv += numbe10ParamsPlugTeach(1,typesource,'number');
	}

    return bdDiv;

}

function renderFXObj(datatext1,datatext2,typesource) {

    if (typesource=='youtubevideo') {
		rP = renderpluginyoutubevideo(datatext1,datatext2);
	}

	if (typesource=='googledoc') {
		rP = renderplugingoogledoc(datatext1,datatext2);
	}

	if (typesource=='lifebar') {
		rP = renderpluginlifebar(datatext1,datatext2);
	}

	if (typesource=='txtmathjax') {
		rP = renderplugintxtmathjax(datatext1,datatext2);
	}

	if (isTypeSourceCont(typesource)) {
		var TareaTeachDocText = $('#contentedittxtarea'+identSourceEdition).val();
		TareaTeachDocText = TareaTeachDocText.replace(/<span /g,'<em ');
		TareaTeachDocText = TareaTeachDocText.replace(/<\/span>/g,'</em>');
		rP = TareaTeachDocText;

		rP += '<span class=typesource >' + typesource + '</span>';

		var datatext1 = $('#contenteditimg'+identSourceEdition).val();
		rP += '<span class=datatext1 >' + datatext1 + '</span>';

	}

    return rP;

}

//Edit direct content
function htmlParamsContentEdit(id,typesource) {

	var bdDiv = '<textarea id="contentedittxtarea'+id+'" type="text" ';
	var src =  cleanCodeBeforeEdit(contentSourceEdition);

	if (typesource=='txtmathjax') {

		bdDiv += ' class="plugAreaDivContentMathJax" >'+src+'</textarea>';
		bdDiv += '<div class="previewMathJax previewMathJax' + id + '" ></div>';
		
		setTimeout(function(){
			refreshEditorFX(id,typesource)
		},500);

	} else {

		if (typesource=='oelcontentsummarywomen'
			||typesource=='oelcontentnumericcards'
			||typesource=='oelcontentkeypointsblock') {
			bdDiv += ' class="plugAreaDivContentMax" >'+src+'</textarea>';
		} else {
			bdDiv += ' class="plugAreaDivContent" >'+src+'</textarea>';
		}
		
		bdDiv += '<div style="padding:5px;" >';
		
		bdDiv += '<input id="contenteditimg' + id + '" type="text" value="" readonly="readonly" ';
		bdDiv += ' class="plugInputDiv" style="width:80px;" />';

		if (typesource=='oelcontentnumericcards'||typesource=='oelcontentkeypointsblock') {
			bdDiv += '<a onClick="addElementInEditor(\''+typesource+'\')" ';
			bdDiv += ' class="buttonAddElementEditor" >+</a>';
		}

		bdDiv += '&nbsp;<input onClick="showFileManagerStudio2(13,\'contenteditimg'+ id + '\',\'finalizeContentEditImg\');" ';
		bdDiv += ' class="gjs-one-bg ludiButtonSave plugInputMin" type="button" value="..." />';

		bdDiv += '</div>';
	}

	return bdDiv;

}

function finalizeContentEditImg() {
	
	$('.ui-widget-overlay').css("display","none");
	$('.workingProcessSave').css("display","none");
	contentSourceEditionV1 = $('#contenteditimg'+identSourceEdition).val();
	
	setTimeout(function(){
		applyImgContentTiny();
	},200);

}

function cleanCodeBeforeEdit(src){

	src = cleanCodeBeforeLoad(src);
	src = src.replace(/<span.*class="typesource.*[\n]+.*?<\/span>/g,'');
	src = src.replace(/<span.*>.*?<\/span>/g,'');
	
	return src;
}

function cleanCodeBeforeLoad(src){

	src = src.replace(/data-gjs-type="row"/g,'');
	src = src.replace(/data-gjs-type="tbody"/g,'');
	src = src.replace(/data-gjs-type="text"/g,'');
	src = src.replace(/data-gjs-type="cell"/g,'');
	src = src.replace(/data-gjs-type="image"/g,'');
	src = src.replace(/data-gjs-type="default"/g,'');
	src = src.replace(/data-highlightable="1"/g,'');
	src = src.replace(' data-gjs-type="cell" ',' ');
	src = src.replace('<tbody  >','<tbody>');

	src = src.replace(/<td  /g,'<td ');
	src = src.replace(/<td  /g,'<td ');
	src = src.replace(/<span  /g,'<span ');
	src = src.replace(/<span  /g,'<span ');
	src = src.replace(/<div  /g,'<div ');
	src = src.replace(/<div  /g,'<div ');
	src = src.replace(/<p  /g,'<p ');
	src = src.replace(/<p  /g,'<p ');
	
	src = src.replace('<span class="datatext1">undefined</span>','');
	src = src.replace('<span class="datatext2">undefined</span>','');

	return src;

}

function installDirectContentEdit(id) {

	//link unlink removeformat blockquote code
	$('#contentedittxtarea'+id).tinymce({
		menubar: false,
		statusbar: false,
		toolbar: 'undo redo| fontselect fontsizeselect forecolor | bold italic underline | bullist numlist outdent indent | code ',
		plugins: 'link lists',
		contextmenu: 'link lists',
		content_css: _p['web_editor'] + "/templates/styles/plug.css",
	});

	$('#contenteditimg'+id).val(contentSourceEditionV1);

	setTimeout(function(){
		applyImgContentTiny();
	},200);
}

function applyImgContentTiny(){

	var hContent =  $('#contentedittxtarea'+identSourceEdition).val();
	if (hContent!='') {

		if (contentSourceEditionV1!='') {
			
			//Pas de background image
			hContent = applyImgContentTiny1(hContent,contentSourceEditionV1);
			
			var tinyMceSrcEdit = tinymce.get('contentedittxtarea'+identSourceEdition);
			tinyMceSrcEdit.setContent(hContent);
		
			directToRender(contentSourceEditionV1);
			
		}

	} else {
		
		setTimeout(function(){
			applyImgContentTiny();
		},200);

	}

}

function applyImgContentTiny1(hContent,var1){

	if (hContent!='') {
		if (var1!='') {
			//Pas de background image
			if (hContent.indexOf("background-image:")==-1) {
				if (hContent.indexOf('class="photo')!=-1) {
					hContent = hContent.replace('class="photo',' style="background-image: url('+var1+')" class="photo');
				}
			} else {
				hContent = hContent.replace(/url\((?!['"]?(?:data|http):)['"]?([^'"\)]*)['"]?\)/g,'url('+var1+')');
			}
		}
	}
	return hContent;

}

function addElementInEditor(typesource) {

	if (typesource=='oelcontentnumericcards') {
		var h = '<div class="tcoel-card">';
		h += '<div class="tcoel-card-number"></div>';
		h += '<h3 class="tcoel-card-title">Définir sa cible</h3>';
		h += '<p class="tcoel-card-description">';
		h += 'Une cible bien définie est la clé du succès.';
		h += '</p>';
		h += '</div>';
	}
	if (typesource=='oelcontentkeypointsblock') {
		h = '<li class="tcoel-key-point">Nouveau point clé</li>';
	}
	var tinyMceSrcEdit = tinymce.get('contentedittxtarea' + identSourceEdition);
    if (tinyMceSrcEdit && h !== '') {
        var content = tinyMceSrcEdit.getContent();
        
        if (content.includes('tcoel-cards-container')) {
            var updatedContent = content.replace(
                /<\/div>\s*<\/div>\s*$/,
                h + '</div></div>'
            );
            tinyMceSrcEdit.setContent(updatedContent);
        }
		if (content.includes('tcoel-key-points-list')) {
			var updatedContent = content.replace(
				/<\/ul>\s*<\/div>\s*$/,
				h + '</ul></div>'
			);
			tinyMceSrcEdit.setContent(updatedContent);
		}
    }

}

var indexGlossEdition = 0;
var subGlossData = new Array();

function displayGlossaryManager() {

	if ($("#glossaryManager").length==0) {

		var bdDiv = '<div id="glossaryManager" ';
        bdDiv += ' class="gjs-mdl-container" style="z-index:3;" >';
		bdDiv += '<div class="gjs-mdl-dialog-v2 gjs-one-bg gjs-two-color">';
		bdDiv += '<div class="gjs-mdl-header">';
		bdDiv += '<div class="gjs-mdl-title">Glossary</div>';
		bdDiv += '<div class="gjs-mdl-btn-close gjs-mdl-btn-close-audio" ';
		bdDiv += ' onClick="closeAllEditWindows();" data-close-modal="">⨯</div>';
		bdDiv += '</div>';
		
		bdDiv += '<div class="gjs-am-add-asset innerGlossaryManager" ';
		bdDiv += 'style="padding:5px;font-size:14px;" ></div>';
		
		bdDiv += '</div>';

		bdDiv += '<div class="gjs-mdl-collector" style="display: none"></div>';
		bdDiv += '</div>';

		$('body').append(bdDiv);

	}

	if ($("#glossaryManager").length==1) {

		$('.ludimenu').css("z-index","2");
		$('#glossaryManager').css("display","");
        $('#glossaryManager').css("z-index",101);
		windowEditorIsOpen = true;
        traductAll();
        innerGlossaryManager();
	}

}

function innerGlossaryManager() {
    
    var h = '';

	h += '<div class="blockGlossaryLoad" >'
    h += '</div>';

    h += '<div class="blockGlossary blockGlossaryAdd" >'
    h += '</div>';
    
    h += innerGlossaryTable();

    $('.innerGlossaryManager').html(h);

	loadGlossaryTermsColl();

}

function launchEditGlossManager(typedit,idterm) {

	indexGlossEdition++;

	var wordTerm = '';
	var defTerm = '';
	var defTerm2 = '';
	
	if (idterm!=''&&typedit==1) {
		wordTerm = subGlossData[idterm].w;
		defTerm = subGlossData[idterm].d;
		defTerm2 = subGlossData[idterm].d2;
	}
	
    var h = '<div class="blockGlossaryLine1" >';
    h += '<div class="blockGlossaryLabel" ><span class="trd" >Term</span>&nbsp;:&nbsp;</div>';
    h += '<input id="termAddGloss' + indexGlossEdition + '" type="text" value="' + wordTerm + '" ';
    h += ' class="blockGlossaryInput" />';
	h += '<div class="blockGlossaryLabelFull" ><span class="trd" >Simple definition</span>&nbsp;:&nbsp;</div>';
	h += '<textarea id="areaGloss1Text' + indexGlossEdition + '"  ';
    h += ' name="areaGloss1Text' + indexGlossEdition + '" ';
	h += 'rows=4 ';
	h += 'style="width:98%;font-size:13px;padding:2px;resize:none;" ';  
	h += ' >' + defTerm + '</textarea>';
    h += '</div>';

	h += '<a onClick="deleteGlossaryProcess(\''+idterm+'\');" ';
	h += ' style="position:absolute;bottom:10px;left:5px;cursor:pointer;"  >';
	h += '<img src="icon/delete-icon-24.png" /></a>';

    h += '<div class="blockGlossaryLine2" >';

    h += '<div class="blockGlossaryLabelFull" ><span class="trd" >Advanced definition</span>&nbsp;:&nbsp;</div>'
    h += '<textarea id="areaGlossText' + indexGlossEdition + '"  ';
    h += ' name="areaTeachDocText' + indexGlossEdition + '" ';
	h += 'rows=4 ';
	h += 'style="width:98%;font-size:13px;padding:2px;margin-left:20px;resize:none;" ';  
	h += ' >' + defTerm2 + '</textarea>';
    
	// Buttons
    h += '<div style="text-align:right;" >';
	h += '<a onClick="closeGlossaryT()" ';
    h += ' class="ludiButtonCancel trd" type="button" >Cancel</a>&nbsp;';
   
    if (typedit==0) {
		h += '<input onClick="saveGlossaryT(\'\')" ';
		h += ' class="ludiButtonSave trd" type="button" value="Add" />';
	}
    if (typedit==1) {
		h += '<input onClick="saveGlossaryT(\''+idterm+'\')" ';
		h += ' class="ludiButtonSave trd" type="button" value="Save" />';
	}

    h += '</div>';
    h += '</div>';

	$('.blockGlossaryAdd').html(h);

	$('.blockGlossaryAdd').css('display','block');
	$('.blockGlossaryTable').css("display","none");
	
	$('#areaGlossText'+indexGlossEdition).tinymce({
        menubar: false,
        statusbar: false
    });

}

function innerGlossaryTable() {
    
    var h = '<div class="blockGlossaryTable" >';
    h += '</div>';
    
    return h ;

}

function closeGlossaryT() {
	$('.blockGlossaryAdd').css("display","none");
	$('.blockGlossaryLoad').css("display","none");
	$('.blockGlossaryTable').css("display","block");
}

function saveGlossaryT(idterm) {

    var txtTerm = $('#termAddGloss'+indexGlossEdition).val();
	var defTerm1 = $('#areaGloss1Text'+indexGlossEdition).val();
	var defTerm2 = $('#areaGlossText'+indexGlossEdition).val();

	if (onlyOneUpdate==false
		||txtTerm==''
		||txtTerm.length<3) {
			return false;
	}

	$('.blockGlossaryAdd').css("display","none");
	$('.blockGlossaryLoad').css("display","block");

    var formData = {
        term : txtTerm,
        def1 : defTerm1,
		def2 : defTerm2,
		idterm : idterm
    };
    var act = 1;
	if (idterm!='') {
		act = 3;
	}
	onlyOneUpdate = false;
    $.ajax({
		url : '../ajax/save/ajax.saveterm.php?act='+act+'&id=' + idPageHtml + '&cotk=' + $('#cotk').val(),
		type: "POST",data : formData,
		cache: false,
		success : function(data,textStatus,jqXHR){

			onlyOneUpdate = true;
			if (data.indexOf("error")==-1) {
				if (idterm!='') {
					
				}
				$('.blockGlossaryLoad').css("display","block");
				loadGlossaryTermsColl();

			} else {
                alert("Error !");
			}

		}, error : function (jqXHR, textStatus, errorThrown)
		{
			alert("Error !");
			alert(textStatus);
		}
	});


}

function loadGlossaryTermsColl() {

	if (onlyOneUpdate==false) {
		return false;
	}

	subGlossData = new Array();

	$('.blockGlossaryAdd').css("display","none");
	
	onlyOneUpdate = false;

    $.ajax({
		url : '../ajax/save/ajax.saveterm.php?act=2&id=' + idPageHtml + '&cotk=' + $('#cotk').val(),
		type: "GET",
		cache: false,
		success: function(data,textStatus,jqXHR){
			
			onlyOneUpdate = true;

			$('.blockGlossaryLoad').css("display","none");
			$('.blockGlossaryTable').css("display","block");

			if (data.indexOf("error")==-1) {
				
				var ArrayObjects = data.split('|');
				var i = 0;
				var nbterms = 0;
				var txtC = '';

				txtC += '<table class="blockGlossTableLine" >';
				txtC += '<tr>';
				txtC += '<td id="GlossNbTerms" ></td>';
				txtC += '<td style="width:28px;" >';
				txtC += '<a onClick="launchEditGlossManager(0,\'\');" ';
				txtC += 'style="cursor:pointer;height:22px;text-align:center;" >';
				txtC += '<img style="margin:4px;margin-top:6px;" ';
				txtC += 'src="icon/add.png" /></td></a>';
				txtC += '</tr>';
				txtC += '</table>';

				txtC += '<table class="blockGlossTableLine" >';

				for (i=0;i<ArrayObjects.length;i++) {
					
					var objInfos = ArrayObjects[i];

					if (objdet!='') {

						if (objInfos.indexOf('@')!=-1) {
						
							var objdet = objInfos.split('@');
							var idt = objdet[0];
							txtC += '<tr id="termid-'+idt+'" >';
							txtC += '<td>&nbsp;' + objdet[1] + '</td>';
							
							var descri = objdet[2];

							if (descri.length>70) {
								descri = descri.substring(0,70);
							}
							
							txtC += '<td>&nbsp;' + descri + '...</td>';
							txtC += '<td style="width:30px;background-color:white;';
							txtC += 'text-align:center;" >';
							txtC += '<a onClick="launchEditGlossManager(1,\''+idt+'\');" ';
							txtC += ' style="cursor:pointer;width:24px;height:24px;" >';
							txtC += '<img style="margin-top:4px;" src="img/edit.png" />';
							txtC += '</a>';
							txtC += '</td>';

							var objectC = {
								w : objdet[1],
								d : objdet[2],
								d2 : objdet[3]
							};
							subGlossData[objdet[0]] = objectC;
							
							//txtC += objdet[0];
	
							txtC += '</tr>';
							nbterms++;
						}
	
					}
				}
				txtC += '</table>';
				
				$('.blockGlossaryTable').html(txtC);
				
				$('#GlossNbTerms').html('&nbsp;' + nbterms + ' terms');
				
			} else {
            
				alert("Error !");
			
			}

		},
		error: function (jqXHR, textStatus, errorThrown)
		{
			alert("Error !");
			alert(textStatus);
		}
	});


}

function deleteGlossaryProcess(idterm) {

	if (onlyOneUpdate==false) {
		return false;
	}
	var formData = {
		idterm : idterm
    };

	$('.blockGlossaryAdd').css('display','none');
	$('.blockGlossaryTable').css("display","none");
	$('.blockGlossaryLoad').css("display","block");
	onlyOneUpdate = false;
    $.ajax({
		url : '../ajax/save/ajax.saveterm.php?act=100&id=' + idPageHtml + '&cotk=' + $('#cotk').val(),
		type: "POST",data:formData,
		cache: false,
		success : function(data,textStatus,jqXHR){
			onlyOneUpdate = true;
			if (data.indexOf("error")==-1
			&&data.indexOf("OK")!=-1) {
				$('.blockGlossaryLoad').css("display","none");
				$('.blockGlossaryTable').css("display","block");
				$('#termid-' + idterm).css("display","none");
			} else {
                alert("Error !");
				$('.blockGlossaryTable').css("display","block");
			}
		}, error : function (jqXHR, textStatus, errorThrown)
		{
			alert("Error !");
			$('.blockGlossaryTable').css("display","block");
			alert(textStatus);
		}
	});

}

function renderpluginyoutubevideo(var1,var2) {

    var1 = 'https://www.youtube.com/embed/' + extractvId(var1);

    var h  = '<div class=topinactiveteach ></div>';
    
    h += '<iframe';
    h += ' style="width:100%;min-height:350px;z-index:1;';
    h += 'height:' + var2 + 'px;overflow:hidden;" ';
    h += ' frameBorder="0" ';
    h += ' src="' + var1 + '" ></iframe>';
    
    h += '<span class=typesource style="display:none;" >youtubevideo</span>';

    return h;

}

function renderpluginyoutubevideoInit(var1,var2) {

    var h = '<div';
    h += ' style="width:100%;height:350px;background:#E5E7E9;" ';
    h += '></div>';
    h += '<span class=typesource style="display:none;" >youtubevideo</span>';

    return h;

}

function extractvId(n) {
    n = real(n,'http://www.youtube.com/watch?v=','');
    n = real(n,'https://www.youtube.com/watch?v=','');
    n = real(n,'https://www.youtube.com/embed/','');
    n = real(n,'http://www.youtube.com/embed/','');
    n = real(n,'https://youtu.be/','');
    n = real(n,'https://www.youtube.com/','');
    n = real(n,'http://www.youtube.com/','');
    n = n.replace('embed/','');
    n = real(n,'/','');
    n = real(n,' ','');
    n = real(n,' ','');
    n = n.replace('watch?v=','');
    n = n.replace('/','');
    n = n.replace(' ','');
    n = n.replace(' ','');
    n = n.replace('&amp;','&');
    
    var ampersandPosition = n.indexOf('&');
    if(ampersandPosition != -1) {
        n = n.substring(0, ampersandPosition);
    }
    return n;
}

function real (txt, rep, witht) {
    return txt.replace(new RegExp(rep,'g'),witht);
}
function renderplugingoogledoc(var1,var2) {

    var h  = '<div class=topinactiveteach ></div>';
    
    h += '<iframe';
    h += ' style="width:100%;min-height:350px;z-index:1;';
    h += 'height:' + var2 + 'px;overflow:hidden;" ';
    h += ' frameBorder="0" ';
    h += ' src="' + var1 + '" ></iframe>';
    
    if (var1=="") {

        h = '<div';
        h += ' style="width:100%;min-height:150px;border:solid 16px #D4E6F1;z-index:1;padding:10px;';
        h += 'height:150px;overflow:hidden;" ';
        h += ' frameBorder="0" ';
        h += ' src="' + var1 + '" ><br/>The document is empty ...</div>';

    }

    h += '<span class=typesource style="display:none;" >googledoc</span>';

    return h;

}

function renderpluginanimfx(var1,var2) {

    var h = '<img';
    h += ' style="width:100%;min-height:150px;z-index:1;';
    h += 'height:150px;" ';
    h += ' frameBorder="0" ';
    h += ' src="' + var1 + '" />';

    h += '<span class=typesource style="display:none;" >animfx</span>';

    return h;

}

function renderpluginlifebar(var1,var2) {

    if (typeof var1 === "undefined"){var1 = 3;}
    if (var1 === ""){var1 = 3;}

    h += '<div class="lifebarcontain" >';

    var h = '';
    var nbLife = parseInt(var1);
    for(var i=0; i < nbLife; i++){
        h += '<div id="lifeopt'+i+'" class="onelifeopt" ></div>';
    }

    h += '</div>';

    h += '<span class=typesource style="display:none;" >lifebar</span>';

    return h;

}

function renderplugintxtmathjax(var1,var2) {

    var h = '';
    h += "<p>"
    h += '\\( J_\\beta(x) = \\sum\\limits_{m=0}^\\infty \\frac{(-1)^m}{m! \\, \\Gamma(m + \\alpha + 1)}{\\left({\\frac{x}{2}}\\right)}^{2 m + \\alpha} \\)';
    h += "</p>"
    h += '<span class=typesource style="display:none;" >txtmathjax</span>';
    return h;

}

function refreshEditorFX(id,typesource) {

	if (windowEditorIsOpen==true) {

		if (typesource=='txtmathjax') {
			var contentToRender = $('#contentedittxtarea'+identSourceEdition).val();
			$('.previewMathJax' + id).html(contentToRender);
			MathJax.typesetPromise();
			setTimeout(function(){
				refreshEditorFX(id,typesource)
			},1000);
		}

	}
	
}

var timerClosePauseWin = 0;
var timerShowPauseObj = 0;
var timerShowPause = 180;

function launchPauseWin() {
    
    if (windowEditorIsOpen==true) {
        return false;
    }
    
    if ($("#framePauseWin").length==0) {

		var bdDiv = '<div id="framePauseWin" class="gjs-mdl-container" >';
		bdDiv += '<div class="gjs-mdl-dialog-v2 gjs-one-bg gjs-two-color" >';	

		bdDiv += '<div class="gjs-mdl-header">';
		bdDiv += '<div class="gjs-mdl-title trd">Pause</div>';
		bdDiv += '</div>';

        bdDiv += '<div style="padding:15px;" ><div class="gjs-mdl-row">';
        bdDiv += '<div class="gjs-mdl-col-12" style="text-align:center;" >';
        
        bdDiv += '<p style="text-align:center;" >';
        bdDiv += 'Voulez-vous continuer à éditer ?';
        bdDiv += '</p>';

        bdDiv += '<p id="timerShowPause" ';
        bdDiv += ' style="text-align:center;font-weight:bold;" >';
        bdDiv += '120</p>';

        bdDiv += '<p style="text-align:center;" >';
        bdDiv += '<input id="btnButtonSavePause" ';
        bdDiv += ' class="gjs-one-bg trd ludiButtonSave" ';
        bdDiv += ' onClick="resetEditEditorWin()" ';
        bdDiv += ' type="button" value="Continue" />';
        bdDiv += '</p>';

        bdDiv += '</div></div></div>';

		bdDiv += '</div>';
        bdDiv += '</div>';

        $('body').append(bdDiv);

    }

    if ($("#framePauseWin").length==1) {

        closeAllEditWindows();
		$('#framePauseWin').css("display","");
        $('.ludimenu').css("display","none");
		traductAll();
        clearTimeout(timerClosePauseWin);
        clearTimeout(timerShowPauseObj);
        timerClosePauseWin = setTimeout(function(){
            quitEditorWinAll();
        },4*60000);
        timerShowPause = 240;
        timerShowPauseObj = incrementTimerShowPause();

	}

}

function incrementTimerShowPause() {
    timerShowPause = timerShowPause - 1;
    $('#timerShowPause').html(timerShowPause);
    timerShowPauseObj = setTimeout(function(){
        incrementTimerShowPause();
    },1000);
}

function quitEditorWinAll() {
    $('#btnButtonSavePause').css("display","none");
    saveSourceFrame(false,false,0);
    setTimeout(function(){
        resetContextEditor();
        setTimeout(function(){quitEditorAll();},10000);
    },10000);
}

function resetEditEditorWin() {
    $('#framePauseWin').css("display","none");
    $('.ludimenu').css("display","");
    clearTimeout(timerInstallPauseWin);
    clearTimeout(timerClosePauseWin);
    clearTimeout(timerShowPause);
    timerInstallPauseWin = setTimeout(function(){
        launchPauseWin();
    },45*60000);
}

function resetContextEditor() {

	$.ajax({
		url : '../ajax/save/ajax.reset.php',
		type: "POST",
		success: function(data,textStatus,jqXHR){},
		error: function (jqXHR, textStatus, errorThrown){}
	});

}

var timerInstallPauseWin = setTimeout(function(){
    launchPauseWin();
},45*60000);

var finalHeightStyleBox = '260px';

function RapidStyleWindows() {

    if ($("#menuStyleFloat").length==0) {
    
        var bdDiv = '<div id="menuStyleFloat" class="styleMenuParamFloat" >';
        bdDiv += '<div id="styleMenuParamFloat" class="styleMenuTitleFloat" >';
        bdDiv += 'Styles';
        bdDiv += '<div class="styleMenuTitleClose" onClick="closeAllEditWindows();" ></div>';
        bdDiv += '</div>';

        bdDiv += '<div onClick="setTitleClassicH1()" class="styleBlocSelect" >';
        bdDiv += '<div class="showBlocTitleClass titleClassicH1" >';
        bdDiv += 'Title';
        bdDiv += '</div></div>';

        bdDiv += '<div onClick="setTitleClassicH1Left()" class="styleBlocSelect" >';
        bdDiv += '<div class="showBlocTitleClass titleClassicH1Left" >';
        bdDiv += 'Title';
        bdDiv += '</div></div>';


        bdDiv += '<div onClick="setTitleFullH1();" class="styleBlocSelect" >';
        bdDiv += '<div style="background:'+mainColorTpl+';" class="showBlocTitleClass titleFullH1" >';
        bdDiv += 'Title';
        bdDiv += '</div></div>';
        
        //titleArrowH1
        bdDiv += '<div onClick="setTitleArrowH1();" class="styleBlocSelect" >';
        bdDiv += '<div style="background-color:'+mainColorTpl+';" class="showBlocTitleClass titleArrowH1" >';
        bdDiv += 'Title';
        bdDiv += '</div></div>';

        //titleIconGradeH1 base-title.css
        bdDiv += '<div onClick="setTitleGradeDocH1();" class="styleBlocSelect" >';
        bdDiv += '<div style="color:white;background-color:'+mainColorTpl+';background-repeat:no-repeat;background-position : left center;';
        bdDiv += 'background-image: url(img/classique/titleicongrade.png);" class="showBlocTitleClass titleIconGradeH1" >';
        bdDiv += 'Title';
        bdDiv += '</div></div>';

        //titleIconDocH1 base-title.css
        bdDiv += '<div onClick="setTitleIconDocH1();" class="styleBlocSelect" >';
        bdDiv += '<div style="color:white;background-color:'+mainColorTpl+';background-repeat:no-repeat;background-position : left center;';
        bdDiv += 'background-image: url(img/classique/titleicondoc.png);" class="showBlocTitleClass titleIconDocH1" >';
        bdDiv += 'Title';
        bdDiv += '</div></div>';

        // HOOK extendedStyleGraph
        if (typeof extendedStyleGraph === "function") {
            bdDiv += extendedStyleGraph();
            finalHeightStyleBox = '300px';
        }

        // HOOK extendedStyleColor
        if (typeof extendedStyleColor === "function") {
            bdDiv += extendedStyleColor();
            finalHeightStyleBox = '475px';
        } else {
            bdDiv += '<a style="cursor:pointer;" target="_blank" ';
            bdDiv += ' href="https://www.batisseurs-numeriques.fr/c-studio-extends.html" class="styleBlocSelect" >';
            bdDiv += '<div class="showBlocTitleClass titleIconDocH1" >';
            bdDiv += 'Extended styles ...';
            bdDiv += '</div></a>';
        }

        bdDiv += '</div>';

        $('body').append(bdDiv);
    }

    if ($("#menuStyleFloat").length==1) {
        
        $('.maskobjMenu').css("display","block");
        $('#menuStyleFloat').css("display","block");
        
        var position = $('.ludiSpeedTools').position();
        
        var Mtop = position.top + 10;
        var Mright = position.right;
        // + $('.ludimenuteachdoc').width() 
        $('#menuStyleFloat').css("width",'20px').css("height",'20px');
        $('#menuStyleFloat').css("margin-right",'-10px');
        $('#menuStyleFloat').css("top",parseInt((Mtop) + 40) + 'px');

        $( "#menuStyleFloat" ).animate({ 
            width : '674px',
            marginRight : '-337px'
	    },100,function(){
            $( "#menuStyleFloat" ).animate({ 
                height: finalHeightStyleBox
            },150,function(){
            });

        });
        
        traductAll();

    }

}

function setTitleClassicH1() {
    if (baseNameObj=='titleh1') {
        setAbstractObjClass('titleClassicH1');
        closeAllEditWindows();
    }
}

function setTitleFullH1() {
    if (baseNameObj=='titleh1') {
        setAbstractObjClass('titleFullH1');
        closeAllEditWindows();
    }
}

function setTitleIconDocH1() {
    if (baseNameObj=='titleh1') {
        setAbstractObjClass('titleIconDocH1');
        closeAllEditWindows();
    }
}
function setTitleGradeDocH1() {
    if (baseNameObj=='titleh1') {
        setAbstractObjClass('titleIconGradeH1');
        closeAllEditWindows();
    }
}

function setTitleKnowledgeDocH1() {
    if (baseNameObj=='titleh1') {
        setAbstractObjClass('titleiconknowledge');
        closeAllEditWindows();
    }
}
function setTitleReminderNoteH1() {
    if (baseNameObj=='titleh1') {
        setAbstractObjClass('titleReminderNote');
        closeAllEditWindows();
    }
}
function setTitleTargetH1() {
    if (baseNameObj=='titleh1') {
        setAbstractObjClass('titletarget');
        closeAllEditWindows();
    }
}

function setTitleArrowH1() {
    if (baseNameObj=='titleh1') {
        setAbstractObjClass('titleArrowH1');
        closeAllEditWindows();
    }
}

function setTitleClassicH1Left() {
    if (baseNameObj=='titleh1') {
        setAbstractObjClass('titleClassicH1Left');
        closeAllEditWindows();
    }
}


function setTitleBlackH1() {
    if (baseNameObj=='titleh1') {
        setAbstractObjClass('titleBlackH1');
        closeAllEditWindows();
    }
}

function setTitleOrangeH1() {
    if (baseNameObj=='titleh1') {
        setAbstractObjClass('titleOrangeH1');
        closeAllEditWindows();
    }
}

function setTitleGreenH1() {
    if (baseNameObj=='titleh1') {
        setAbstractObjClass('titleGreenH1');
        closeAllEditWindows();
    }
}

function setTitleBlueH1() {
    if (baseNameObj=='titleh1') {
        setAbstractObjClass('titleBlueH1');
        closeAllEditWindows();
    }
}

function setTitlePurpleH1() {
    if (baseNameObj=='titleh1') {
        setAbstractObjClass('titlePurpleH1');
        closeAllEditWindows();
    }
}

function setTitleRedH1() {
    if (baseNameObj=='titleh1') {
        setAbstractObjClass('titleRedH1');
        closeAllEditWindows();
    }
}

function setTitleGoldH1() {
    if (baseNameObj=='titleh1') {
        setAbstractObjClass('titleGoldH1');
        closeAllEditWindows();
    }
}

function setTitleNavyH1() {
    if (baseNameObj=='titleh1') {
        setAbstractObjClass('titleNavyH1');
        closeAllEditWindows();
    }
}

//titleGrayH1
function setTitleGrayH1() {
    if (baseNameObj=='titleh1') {
        setAbstractObjClass('titleGrayH1');
        closeAllEditWindows();
    }
}
var txtfakeTxt = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit.';
txtfakeTxt += 'Nulla nec purus feugiat, molestie ipsum et, consequat nibh. Etiam non elit dui. ';
var txtfakeTxt2 = 'Lorem ipsum dolor sit amet consectetur';

function RapidStyleTxtBox() {

    if ($("#menuStyleBoxFloat").length==0) {
    
        var bdDiv = '<div id="menuStyleBoxFloat" class="styleMenuParamFloat" >';
        bdDiv += '<div id="styleBoxMenuParamFloat" class="styleMenuTitleFloat" >';
        bdDiv += 'Styles';
        bdDiv += '<div class="styleMenuTitleClose" onClick="closeAllEditWindows();" >';
        bdDiv += '</div></div>';

        bdDiv += '<div onClick="cleanBoxClassicBox()" class="styleBoxBlocSelect" >';
        bdDiv += '<div class="styleBoxBlocTxt" >';
        bdDiv += txtfakeTxt;
        bdDiv += '</div></div>';

        bdDiv += '<div onClick="setBoxClassicBox()" class="styleBoxBlocSelect" >';
        bdDiv += '<div class="styleBoxBlocTxt BoxTxtRound" >';
        bdDiv += txtfakeTxt;
        bdDiv += '</div></div>';

        bdDiv += '<div onClick="setBoxDashBlue()" class="styleBoxBlocSelect" >';
        bdDiv += '<div class="styleBoxBlocTxt BoxDashBlue" >';
        bdDiv += txtfakeTxt;
        bdDiv += '</div></div>';

        bdDiv += '<div onClick="setBoxPostit()" class="styleBoxBlocSelect" >';
        bdDiv += '<div class="styleBoxBlocTxt BoxPostit" >';
        bdDiv += txtfakeTxt;
        bdDiv += '</div></div>';
        
        bdDiv += '<div onClick="setBoxByName(\'BoxShadowA\')" class="styleBoxBlocSelect" >';
        bdDiv += '<div class="styleBoxBlocTxt BoxShadowA" >';
        bdDiv += txtfakeTxt;
        bdDiv += '</div></div>';

        bdDiv += '<div onClick="setBoxByName(\'BoxAzur\')" class="styleBoxBlocSelect" >';
        bdDiv += '<div class="styleBoxBlocTxt BoxAzur" >';
        bdDiv += txtfakeTxt;
        bdDiv += '</div></div>';

        bdDiv += '<div onClick="setBoxByName(\'BoxCadre\')" class="styleBoxBlocSelect" >';
        bdDiv += '<div class="styleBoxBlocTxt BoxCadre" >';
        bdDiv += txtfakeTxt2;
        bdDiv += '</div></div>';
        

        bdDiv += '</div>';

        $('body').append(bdDiv);
    }

    if ($("#menuStyleBoxFloat").length==1) {
        
        $('.maskobjMenu').css("display","block");
        $('#menuStyleBoxFloat').css("display","block");
        
        var position = $('.ludiSpeedTools').position();
        
        var Mtop = position.top + 10;
        var Mright = position.right;

        $('#menuStyleBoxFloat').css("width",'20px').css("height",'20px');
        $('#menuStyleBoxFloat').css("margin-right",'-10px');
        $('#menuStyleBoxFloat').css("top",parseInt((Mtop) + 40) + 'px');

        $( "#menuStyleBoxFloat" ).animate({ 
            width : '674px',
            marginRight : '-337px'
	    },100,function(){
            $( "#menuStyleBoxFloat" ).animate({ 
                height: '270px'
            },150,function(){
            });
        });
        
        traductAll();

    }

}

function cleanBoxClassicBox() {
    
    if (baseNameObj=='tabletxt') {
        setAbstractObjClass('BoxTxtClean');
        closeAllEditWindows();
    }

}

function setBoxClassicBox() {
    
    if (baseNameObj=='tabletxt') {
        setAbstractObjClass('BoxTxtRound');
        closeAllEditWindows();
    }

}

function setBoxDashBlue() {
    
    if (baseNameObj=='tabletxt') {
        setAbstractObjClass('BoxDashBlue');
        closeAllEditWindows();
    }

}

function setBoxPostit() {
    
    if (baseNameObj=='tabletxt') {
        setAbstractObjClass('BoxPostit');
        closeAllEditWindows();
    }

}

function setBoxByName(name) {
    
    if (baseNameObj=='tabletxt') {
        setAbstractObjClass(name);
        closeAllEditWindows();
    }

}

function haveSpeedCtrExtra(nameObj,nameObj2) {

    var r = false;
    if (nameObj=='tabletxt'||nameObj2=='tabletxt') {
        r = true;
        baseNameObj2 = 'tabletxt';
    }
    return r;

}

function extendedStyleGraph() {

    var bdDiv = '';

    bdDiv += '<div onClick="setTitleTargetH1();" class="styleBlocSelect" >';
    bdDiv += '<div style="color:black;background-color:'+mainColorTpl+';background-repeat:no-repeat;background-position : left center;';
    bdDiv += 'background-image: url(img/classique/titletarget.png);" ';
    bdDiv += 'class="showBlocTitleClass titleiconknowledge" >';
    bdDiv += 'Title</div></div>';

    // 3  columns
    //titleiconknowledge base-title.css
    bdDiv += '<div onClick="setTitleKnowledgeDocH1();" class="styleBlocSelect" >';
    bdDiv += '<div style="color:black;background-color:'+mainColorTpl+';background-repeat:no-repeat;background-position : left center;';
    bdDiv += 'background-image: url(img/classique/titleiconknowledge.png);" ';
    bdDiv += ' class="showBlocTitleClass titleiconknowledge" >';
    bdDiv += 'Title</div></div>';

    // setTitleReminderNoteH1()
    bdDiv += '<div onClick="setTitleReminderNoteH1();" class="styleBlocSelect" >';
    bdDiv += '<div style="color:black;background-color:'+mainColorTpl+';background-repeat:no-repeat;background-position : left center;';
    bdDiv += 'background-image: url(img/classique/titleremindernote.png);" ';
    bdDiv += 'class="showBlocTitleClass titleiconknowledge" >';
    bdDiv += 'Title</div></div>';

    return bdDiv;

}

function extendedStyleColor() {

    var bdDiv = '';

    bdDiv += '<div onClick="setTitleBlackH1()" class="styleBlocSelect" >';
    bdDiv += '<div class="showBlocTitleClass titleBlackH1" >';
    bdDiv += 'Title</div></div>';

    bdDiv += '<div onClick="setTitleOrangeH1()" class="styleBlocSelect" >';
    bdDiv += '<div class="showBlocTitleClass titleOrangeH1" >';
    bdDiv += 'Title</div></div>';

    bdDiv += '<div onClick="setTitleGreenH1()" class="styleBlocSelect" >';
    bdDiv += '<div class="showBlocTitleClass titleGreenH1" >';
    bdDiv += 'Title</div></div>';
    

    bdDiv += '<div onClick="setTitleBlueH1()" class="styleBlocSelect" >';
    bdDiv += '<div class="showBlocTitleClass titleBlueH1" >';
    bdDiv += 'Title</div></div>';

    bdDiv += '<div onClick="setTitlePurpleH1()" class="styleBlocSelect" >';
    bdDiv += '<div class="showBlocTitleClass titlePurpleH1" >';
    bdDiv += 'Title</div></div>';
    
    bdDiv += '<div onClick="setTitleRedH1()" class="styleBlocSelect" >';
    bdDiv += '<div class="showBlocTitleClass titleRedH1" >';
    bdDiv += 'Title</div></div>';



    // setTitleGoldH1
    bdDiv += '<div onClick="setTitleGoldH1()" class="styleBlocSelect" >';
    bdDiv += '<div class="showBlocTitleClass titleGoldH1" >';
    bdDiv += 'Title</div></div>';

    bdDiv += '<div onClick="setTitleNavyH1()" class="styleBlocSelect" >';
    bdDiv += '<div class="showBlocTitleClass titleNavyH1" >';
    bdDiv += 'Title</div></div>';

    //titleGrayH1
    bdDiv += '<div onClick="setTitleGrayH1()" class="styleBlocSelect" >';
    bdDiv += '<div class="showBlocTitleClass titleGrayH1" >';
    bdDiv += 'Title</div></div>';

    return bdDiv;

}
var baseNameObj = '';
var baseNameObj2 = '';

function haveSpeedTools(nameObj,nameObj2) {
    var r = false;
    if (nameObj=='image') {
        r = true;
    }
    if (nameObj=='titleh1') {
        r = true;
    }
    if (typeof haveSpeedCtrExtra === "function") {
        if (haveSpeedCtrExtra(nameObj,nameObj2) ) {
            r = true;
        }
    }
    return r;
}

function installSpeedTools() {

    if (tmpNameObj=='image'&&baseNameObj!='image') {
        baseNameObj = tmpNameObj;
        baseNameObj2 = '';
        var coolTools = '<a onClick="initialImage();" class="image-initial-opt" ></a>';
        coolTools += '<a onClick="classicImage();" class="image-classic-opt" ></a>';
        coolTools += '<a onClick="fullImageFct();" class="image-full-opt" ></a>';
        coolTools += '<a onClick="overImageFct();" class="image-over-opt" ></a>';
        $('.ludiSpeedTools').html(coolTools);
    }
    if (tmpNameObj=='titleh1'&&baseNameObj!='titleh1') {
        baseNameObj = tmpNameObj;
        baseNameObj2 = '';
        var coolTools = '<a onClick="RapidStyleWindows();" ';
        coolTools += ' class="style-over-opt" ></a>';
        $('.ludiSpeedTools').html(coolTools);
    }
    if (baseNameObj2=='tabletxt'&&baseNameObj!='tabletxt') {
        baseNameObj = baseNameObj2;
        var coolTools = '<a onClick="RapidStyleTxtBox();" ';
        coolTools += ' class="style-over-opt" ></a>';
        $('.ludiSpeedTools').html(coolTools);
    }

}

function initialImage() {

    if (baseNameObj=='image') {
        setAbstractObjClass('initialImg');
    }
    
}

function classicImage() {

    if (baseNameObj=='image') {
        setAbstractObjClass('bandeImg');
    }
    
}

function fullImageFct() {

    if (baseNameObj=='image') {
        setAbstractObjClass('bandeImgFull');
    }
    
}

function overImageFct() {

    if (baseNameObj=='image') {
        setAbstractObjClass('bandeImgOverview');
    }
    
}

function renderplugintxtmathjax(var1,var2) {

    var h = '';
    h += "<p>"
    h += '\\( J_\\beta(x) = \\sum\\limits_{m=0}^\\infty \\frac{(-1)^m}{m! \\, \\Gamma(m + \\alpha + 1)}{\\left({\\frac{x}{2}}\\right)}^{2 m + \\alpha} \\)';
    h += "</p>"
    h += '<span class=typesource style="display:none;" >txtmathjax</span>';
    return h;

}

function refreshEditorFX(id,typesource) {

	if (windowEditorIsOpen==true) {

		if (typesource=='txtmathjax') {
			var contentToRender = $('#contentedittxtarea'+identSourceEdition).val();
			$('.previewMathJax' + id).html(contentToRender);
			MathJax.typesetPromise();
			setTimeout(function(){
				refreshEditorFX(id,typesource)
			},1000);
		}

	}
	
}

function displaySelectLanguage(){

	if ($("#SelectLanguageWindows").length==0) {
		
		var bdDiv = '<div id="SelectLanguageWindows" class="gjs-mdl-container" >';

		bdDiv += '<div class="gjs-mdl-dialog-v2 gjs-one-bg gjs-two-color" ';
		bdDiv += ' style="max-width:800px!important;" >';
		
		bdDiv += getTitleBar('Select Language');

		bdDiv += '<div class="gjs-am-add-asset" ';
		bdDiv += 'style="padding:25px;padding-top:10px;font-size:16px;" >';

		var _langNames = {
			'ar':'العربية','ast_ES':'Asturianu','bg':'Български','bs_BA':'Bosanski',
			'ca_ES':'Català','cs_CZ':'Čeština','da':'Dansk','de':'Deutsch',
			'el':'Ελληνικά','en_US':'English','eo':'Esperanto','es':'Español',
			'eu_ES':'Euskara','fa_IR':'فارسی','fi_FI':'Suomi','fr_FR':'Français',
			'gl':'Galego','he_IL':'עברית','hi':'हिन्दी','hr_HR':'Hrvatski',
			'hu_HU':'Magyar','id_ID':'Bahasa Indonesia','it':'Italiano','ja':'日本語',
			'ka_GE':'ქართული','ko_KR':'한국어','lt_LT':'Lietuvių','lv_LV':'Latviešu',
			'ms_MY':'Bahasa Melayu','nl':'Nederlands','nn_NO':'Norsk nynorsk',
			'pl_PL':'Polski','pt_BR':'Português (Brasil)','pt_PT':'Português (Portugal)',
			'ro_RO':'Română','ru_RU':'Русский','sk_SK':'Slovenčina','sl_SI':'Slovenščina',
			'sr_RS':'Српски','sv_SE':'Svenska','th':'ภาษาไทย','tr':'Türkçe',
			'uk_UA':'Українська','vi_VN':'Tiếng Việt','zh_CN':'中文（简体）','zh_TW':'中文（繁體）'
		};
		var _available = (typeof cstudioAvailableLocales !== 'undefined') ? cstudioAvailableLocales : ['en_US'];
		var langs = [];
		for (var _li = 0; _li < _available.length; _li++) {
			var _code = _available[_li];
			langs.push({ code: _code, label: _langNames[_code] || _code });
		}
		langs.sort(function(a, b) { return a.label.localeCompare(b.label); });
		bdDiv += '<select id="langSelectDropdown" style="width:100%;padding:8px;font-size:15px;margin-top:10px;margin-bottom:10px;cursor:pointer;" onchange="selectLangUI(this.value);">';
		for (var li = 0; li < langs.length; li++) {
			var selected = (langselectUI === langs[li].code) ? ' selected' : '';
			bdDiv += '<option value="' + langs[li].code + '"' + selected + '>' + langs[li].label + '</option>';
		}
		bdDiv += '</select>';

		bdDiv += '</div>';
		bdDiv += '</div>';
		
		bdDiv += '<div class="gjs-mdl-collector" style="display:none"></div>';
		bdDiv += '</div>';

		$('body').append(bdDiv);
	
	} else {
		// Refresh the selected option to match the current language
		$('#langSelectDropdown').val(langselectUI);
	}

	$('.ludimenu').css("z-index",'2');
	$('#SelectLanguageWindows').css("display",'');
	windowEditorIsOpen = true;
	loadaFunction();
	traductAll();

}

function selectLangUI(lg) {

	if (langselectUI==lg) {
		closeAllEditWindows();
		return;
	}

	if (langselectUI=='en_US') {
		if(localStorage){
			localStorage.setItem("langselectUI",lg);
		}
		closeAllEditWindows();
		cstudioI18nInit(lg, traductAll);
	} else {
		if(localStorage){
			localStorage.setItem("langselectUI",lg);
			reloadEditorAll();
		}
	}

}

// Use to translate the UI elements of the application
var langselectUI = 'en_US';

function detectTraductAll() {

    var _validLangs = ['ar','ast_ES','bg','bs_BA','ca_ES','cs_CZ','da','de','el','en_US',
        'eo','es','eu_ES','fa_IR','fi_FI','fr_FR','gl','he_IL','hi','hr_HR','hu_HU',
        'id_ID','it','ja','ka_GE','ko_KR','lt_LT','lv_LV','ms_MY','nl','nn_NO',
        'pl_PL','pt_BR','pt_PT','ro_RO','ru_RU','sk_SK','sl_SI','sr_RS','sv_SE',
        'th','tr','uk_UA','vi_VN','zh_CN','zh_TW'];
    var _courseLocale = (typeof cstudioCourseLocale !== 'undefined') ? cstudioCourseLocale : 'en_US';
    var langselectUIStore = _courseLocale;
    if (localStorage) {
        langselectUIStore = localStorage.getItem("langselectUI");
        if (_validLangs.indexOf(langselectUIStore) === -1) {
            var lang = navigator.language || navigator.userLanguage;
            if      (lang.indexOf('pt-BR')!=-1||lang.indexOf('pt_BR')!=-1) { langselectUIStore = 'pt_BR'; }
            else if (lang.indexOf('pt')   !=-1)                            { langselectUIStore = 'pt_PT'; }
            else if (lang.indexOf('zh-TW')!=-1||lang.indexOf('zh_TW')!=-1) { langselectUIStore = 'zh_TW'; }
            else if (lang.indexOf('zh')   !=-1)                            { langselectUIStore = 'zh_CN'; }
            else if (lang.indexOf('fr')   !=-1)                            { langselectUIStore = 'fr_FR'; }
            else if (lang.indexOf('es')   !=-1)                            { langselectUIStore = 'es'; }
            else if (lang.indexOf('de')   !=-1)                            { langselectUIStore = 'de'; }
            else if (lang.indexOf('nl')   !=-1)                            { langselectUIStore = 'nl'; }
            else if (lang.indexOf('ar')   !=-1)                            { langselectUIStore = 'ar'; }
            else if (lang.indexOf('bg')   !=-1)                            { langselectUIStore = 'bg'; }
            else if (lang.indexOf('bs')   !=-1)                            { langselectUIStore = 'bs_BA'; }
            else if (lang.indexOf('ca')   !=-1)                            { langselectUIStore = 'ca_ES'; }
            else if (lang.indexOf('cs')   !=-1)                            { langselectUIStore = 'cs_CZ'; }
            else if (lang.indexOf('da')   !=-1)                            { langselectUIStore = 'da'; }
            else if (lang.indexOf('el')   !=-1)                            { langselectUIStore = 'el'; }
            else if (lang.indexOf('eo')   !=-1)                            { langselectUIStore = 'eo'; }
            else if (lang.indexOf('eu')   !=-1)                            { langselectUIStore = 'eu_ES'; }
            else if (lang.indexOf('fa')   !=-1)                            { langselectUIStore = 'fa_IR'; }
            else if (lang.indexOf('fi')   !=-1)                            { langselectUIStore = 'fi_FI'; }
            else if (lang.indexOf('gl')   !=-1)                            { langselectUIStore = 'gl'; }
            else if (lang.indexOf('he')   !=-1)                            { langselectUIStore = 'he_IL'; }
            else if (lang.indexOf('hi')   !=-1)                            { langselectUIStore = 'hi'; }
            else if (lang.indexOf('hr')   !=-1)                            { langselectUIStore = 'hr_HR'; }
            else if (lang.indexOf('hu')   !=-1)                            { langselectUIStore = 'hu_HU'; }
            else if (lang.indexOf('id')   !=-1)                            { langselectUIStore = 'id_ID'; }
            else if (lang.indexOf('it')   !=-1)                            { langselectUIStore = 'it'; }
            else if (lang.indexOf('ja')   !=-1)                            { langselectUIStore = 'ja'; }
            else if (lang.indexOf('ka')   !=-1)                            { langselectUIStore = 'ka_GE'; }
            else if (lang.indexOf('ko')   !=-1)                            { langselectUIStore = 'ko_KR'; }
            else if (lang.indexOf('lt')   !=-1)                            { langselectUIStore = 'lt_LT'; }
            else if (lang.indexOf('lv')   !=-1)                            { langselectUIStore = 'lv_LV'; }
            else if (lang.indexOf('ms')   !=-1)                            { langselectUIStore = 'ms_MY'; }
            else if (lang.indexOf('nn')   !=-1||lang.indexOf('no')!=-1)    { langselectUIStore = 'nn_NO'; }
            else if (lang.indexOf('pl')   !=-1)                            { langselectUIStore = 'pl_PL'; }
            else if (lang.indexOf('ro')   !=-1)                            { langselectUIStore = 'ro_RO'; }
            else if (lang.indexOf('ru')   !=-1)                            { langselectUIStore = 'ru_RU'; }
            else if (lang.indexOf('sk')   !=-1)                            { langselectUIStore = 'sk_SK'; }
            else if (lang.indexOf('sl')   !=-1)                            { langselectUIStore = 'sl_SI'; }
            else if (lang.indexOf('sr')   !=-1)                            { langselectUIStore = 'sr_RS'; }
            else if (lang.indexOf('sv')   !=-1)                            { langselectUIStore = 'sv_SE'; }
            else if (lang.indexOf('th')   !=-1)                            { langselectUIStore = 'th'; }
            else if (lang.indexOf('tr')   !=-1)                            { langselectUIStore = 'tr'; }
            else if (lang.indexOf('uk')   !=-1)                            { langselectUIStore = 'uk_UA'; }
            else if (lang.indexOf('vi')   !=-1)                            { langselectUIStore = 'vi_VN'; }
            else                                                            { langselectUIStore = _courseLocale; }
        }
    }
    langselectUI = langselectUIStore;

}

function traductAll() {

    var _validLangs = ['ar','ast_ES','bg','bs_BA','ca_ES','cs_CZ','da','de','el','en_US',
        'eo','es','eu_ES','fa_IR','fi_FI','fr_FR','gl','he_IL','hi','hr_HR','hu_HU',
        'id_ID','it','ja','ka_GE','ko_KR','lt_LT','lv_LV','ms_MY','nl','nn_NO',
        'pl_PL','pt_BR','pt_PT','ro_RO','ru_RU','sk_SK','sl_SI','sr_RS','sv_SE',
        'th','tr','uk_UA','vi_VN','zh_CN','zh_TW'];
    var _courseLocale = (typeof cstudioCourseLocale !== 'undefined') ? cstudioCourseLocale : 'en_US';
    var langselectUIStore = _courseLocale;
    
    if (localStorage) {
        langselectUIStore = localStorage.getItem("langselectUI");
        if (_validLangs.indexOf(langselectUIStore) === -1) {
            
            var lang = navigator.language || navigator.userLanguage;
            if      (lang.indexOf('pt-BR')!=-1||lang.indexOf('pt_BR')!=-1) { langselectUIStore = 'pt_BR'; }
            else if (lang.indexOf('pt')   !=-1)                            { langselectUIStore = 'pt_PT'; }
            else if (lang.indexOf('zh-TW')!=-1||lang.indexOf('zh_TW')!=-1) { langselectUIStore = 'zh_TW'; }
            else if (lang.indexOf('zh')   !=-1)                            { langselectUIStore = 'zh_CN'; }
            else if (lang.indexOf('fr')   !=-1)                            { langselectUIStore = 'fr_FR'; }
            else if (lang.indexOf('es')   !=-1)                            { langselectUIStore = 'es'; }
            else if (lang.indexOf('de')   !=-1)                            { langselectUIStore = 'de'; }
            else if (lang.indexOf('nl')   !=-1)                            { langselectUIStore = 'nl'; }
            else if (lang.indexOf('ar')   !=-1)                            { langselectUIStore = 'ar'; }
            else if (lang.indexOf('bg')   !=-1)                            { langselectUIStore = 'bg'; }
            else if (lang.indexOf('bs')   !=-1)                            { langselectUIStore = 'bs_BA'; }
            else if (lang.indexOf('ca')   !=-1)                            { langselectUIStore = 'ca_ES'; }
            else if (lang.indexOf('cs')   !=-1)                            { langselectUIStore = 'cs_CZ'; }
            else if (lang.indexOf('da')   !=-1)                            { langselectUIStore = 'da'; }
            else if (lang.indexOf('el')   !=-1)                            { langselectUIStore = 'el'; }
            else if (lang.indexOf('eo')   !=-1)                            { langselectUIStore = 'eo'; }
            else if (lang.indexOf('eu')   !=-1)                            { langselectUIStore = 'eu_ES'; }
            else if (lang.indexOf('fa')   !=-1)                            { langselectUIStore = 'fa_IR'; }
            else if (lang.indexOf('fi')   !=-1)                            { langselectUIStore = 'fi_FI'; }
            else if (lang.indexOf('gl')   !=-1)                            { langselectUIStore = 'gl'; }
            else if (lang.indexOf('he')   !=-1)                            { langselectUIStore = 'he_IL'; }
            else if (lang.indexOf('hi')   !=-1)                            { langselectUIStore = 'hi'; }
            else if (lang.indexOf('hr')   !=-1)                            { langselectUIStore = 'hr_HR'; }
            else if (lang.indexOf('hu')   !=-1)                            { langselectUIStore = 'hu_HU'; }
            else if (lang.indexOf('id')   !=-1)                            { langselectUIStore = 'id_ID'; }
            else if (lang.indexOf('it')   !=-1)                            { langselectUIStore = 'it'; }
            else if (lang.indexOf('ja')   !=-1)                            { langselectUIStore = 'ja'; }
            else if (lang.indexOf('ka')   !=-1)                            { langselectUIStore = 'ka_GE'; }
            else if (lang.indexOf('ko')   !=-1)                            { langselectUIStore = 'ko_KR'; }
            else if (lang.indexOf('lt')   !=-1)                            { langselectUIStore = 'lt_LT'; }
            else if (lang.indexOf('lv')   !=-1)                            { langselectUIStore = 'lv_LV'; }
            else if (lang.indexOf('ms')   !=-1)                            { langselectUIStore = 'ms_MY'; }
            else if (lang.indexOf('nn')   !=-1||lang.indexOf('no')!=-1)    { langselectUIStore = 'nn_NO'; }
            else if (lang.indexOf('pl')   !=-1)                            { langselectUIStore = 'pl_PL'; }
            else if (lang.indexOf('ro')   !=-1)                            { langselectUIStore = 'ro_RO'; }
            else if (lang.indexOf('ru')   !=-1)                            { langselectUIStore = 'ru_RU'; }
            else if (lang.indexOf('sk')   !=-1)                            { langselectUIStore = 'sk_SK'; }
            else if (lang.indexOf('sl')   !=-1)                            { langselectUIStore = 'sl_SI'; }
            else if (lang.indexOf('sr')   !=-1)                            { langselectUIStore = 'sr_RS'; }
            else if (lang.indexOf('sv')   !=-1)                            { langselectUIStore = 'sv_SE'; }
            else if (lang.indexOf('th')   !=-1)                            { langselectUIStore = 'th'; }
            else if (lang.indexOf('tr')   !=-1)                            { langselectUIStore = 'tr'; }
            else if (lang.indexOf('uk')   !=-1)                            { langselectUIStore = 'uk_UA'; }
            else if (lang.indexOf('vi')   !=-1)                            { langselectUIStore = 'vi_VN'; }
            else                                                            { langselectUIStore = _courseLocale; }

        }
    }

    langselectUI = langselectUIStore;
    
    if (langselectUI=='en_US') { 
        return false;
    }

    $( ".gjs-block-label" ).not('.onetrd').each(function( index ) {
        $(this).addClass("trd");
        $(this).addClass("onetrd");
        if ($(this).parent().hasClass("gjs-block")) {
            var parentNode = $(this).parent();
            var txt1 = parentNode.attr("title");
            var txt2 = returnTradTerm(txt1);
            txt2 = txt2.replace(/&nbsp;/g, ' ');
            if (txt1!=txt2) {
               parentNode.attr("title",txt2);
            }
        }
    });

    //trd
    $( ".trd" ).each(function( index ) {
        
        var isBtn = 0;
        var txt1 = $(this).html();
        if ($(this).is( ":button" )) {
            txt1 = $(this).attr("value");
            isBtn = 1;
        }
        var txt2 = returnTradTerm(txt1);
        if (txt1!=txt2) {
            if (isBtn==1) {
                $(this).attr("value",txt2);
            } else {
                $(this).html(txt2);
            }
        }
        $(this).removeClass("trd");
        
    });

}

detectTraductAll();
cstudioI18nInit(langselectUI, traductAll);






//events-reload.js / tags span for type only
function renderpluginoelcontentcardinfo(var1,var2) {

    var h = '<div class="oelcardinfo oelcardinfoline" >';
    h += ' <div class="meta">';
    h += ' <div class="photo" ></div>';
    h += ' </div>';
    h += ' <div class="description">';
    h += ' <div class=oelcardinfoh1 >Learning to Code</div>';
    h += ' <div class=oelcardinfoh2 >Opening a door to the future</div>';
    h += ' <p> Lorem ipsum dolor sit amet, consectetur adipisicing elit. Ad eum dolorum architecto obcaecati enim dicta praesentium, quam nobis! Neque ad aliquam facilis numquam. Veritatis, sit.</p>';
    h += ' <p class="read-more"><br/></p>';
    h += ' </div>';
    h += ' </div>';

    h += '<span class=typesource style="display:none;" >oelcontentcardinfo</span>';
    
    return h;

}

function renderpluginoelcontentcardinfocircle(var1,var2) {

    var h = '<div class="oelcardinfo oelcardinfolinelarge" >';
 
    h += ' <div class="description">';
    h += ' <div class="oelcardinfoh1 oeltxtcenter" >Learning to Design</div>';
    h += ' <div class="oelcardinfoh2 oeltxtcenter" >Opening a door to elearning</div>';
    h += ' <p class="oeltxtcenter" > Lorem ipsum dolor sit amet, consectetur adipisicing elit. Ad eum dolorum architecto obcaecati enim dicta praesentium, quam nobis! Neque ad aliquam facilis numquam. Veritatis, sit.</p>';
    h += ' <p class="read-more"><br/></p>';
    h += ' </div>';

    h += ' <div class="metacircle">';
    h += ' <div class="photo" ></div>';
    h += ' </div>';

    h += ' </div>';

    h += '<span class=typesource style="display:none;" >oelcontentcardinfocircle</span>';
    
    return h;

}

function renderpluginoelcontentphotowtitle(var1,var2) {

    var h = '<div class="oelcardinfo" >';
    h += '<div class="fotowtitle">';
    h += '<div class="photo" ></div>';
    h += '<div class="oelcentertitle" ><p>Lorem ipsum dolor sit amet, consectetur adipisicing elit.</p></div>';
    h += '</div></div>';
    
    return h;

}

function renderpluginoelcontentlistbox50(var1,var2) {

    var h = '<div class="oelcardinfo oelcardinfoline" >';
    
    h += '<div class="describox50">';
    h += '<div class=oelcardinfoh3 >Title A</div>';
    h += '<div class=oelcardseparatorline ></div>';
    h += '<ul>';
    h += '<li>Lorem ipsum dolor sit 1.</li>';
    h += '<li>Lorem ipsum dolor sit 2.</li>';
    h += '<li>Lorem ipsum dolor sit 3.</li>';
    h += '<li>Lorem ipsum dolor sit 4.</li>';
    h += '</ul></div>';
    
    h += '<div class="describox50">';
    h += '<div class=oelcardinfoh3 >Title B</div>';
    h += '<div class=oelcardseparatorline ></div>';
    h += '<ul>';
    h += '<li>Lorem ipsum dolor sit 5.</li>';
    h += '<li>Lorem ipsum dolor sit 6.</li>';
    h += '<li>Lorem ipsum dolor sit 7.</li>';
    h += '<li>Lorem ipsum dolor sit 8.</li>';
    h += '</ul></div>';
    
    h += '</div>';
    
    return h;

}

function renderpluginoelcontentlistboximg50(var1,var2) {

    var h = '<div class="oelcardinfo oelcardinfoline" >';

    h += '<div class="describox50">';
    h += '<div class=oelcardinfoh3 >Title A</div>';
    h += '<div class=oelcardseparatorline ></div>';
    h += '<ul>';
    h += '<li>Lorem ipsum dolor sit 1.</li>';
    h += '<li>Lorem ipsum dolor sit 2.</li>';
    h += '<li>Lorem ipsum dolor sit 3.</li>';
    h += '<li>Lorem ipsum dolor sit 4.</li>';
    h += '<li>Lorem ipsum dolor sit 5.</li>';
    h += '</ul></div>';

    h += '<div class="descrimg50">';
    h += '<div class="photo" ></div>';
    h += '</div>';
    
    return h;

}

function renderpluginoelcontentsummarywomen(var1,var2) {

    var h = '<div class="oelcardinfo oelcardinfosom" >';
    h += '<div class="sommarybox70">';
    h += '<div class=oelcardinfoh4 >Title A</div>';
    h += '<div class=oelcardseparatorlineleft ></div>';
    h += '<ul>';
    h += '<li>Lorem ipsum dolor sit 1.';
    h += '<ul>';
    h += '<li>Lorem ipsum dolor sit 1.1 </li>';
    h += '<li>Lorem ipsum dolor sit 1.2 </li>';
    h += '</ul>';
    h += '</li>';
    h += '<li>Lorem ipsum dolor sit 2.';
    h += ' <ul>';
    h += '<li>Lorem ipsum dolor sit 1. </li>';
    h += '</ul>';
    h += '</li>';
    h += '<li>Lorem ipsum dolor sit 3.';
    h += '<ul>';
    h += '<li>Lorem ipsum dolor sit 1. </li>';
    h += '</ul>';
    h += '</li>';
    h += '<li>Lorem ipsum dolor sit 4.';
    h += '<ul>';
    h += '<li>Lorem ipsum dolor sit 1. </li>';
    h += '</ul>';
    h += '</li>';
    h += '<li>Lorem ipsum dolor sit 5.';
    h += '<ul>';
    h += '<li>Lorem ipsum dolor sit 1. </li>';
    h += '</ul>';
    h += '</li>';
    h += '</ul>';
    h += '</div>';
    h += '<div class="sommadecoA">';
    h += '<div class="photo" ></div>';
    h += '</div>';
    h += '<div class="sommatriangleA"></div>';
    h += '</div>';

    return h;

}

function renderpluginoelcontentarealeveldoc(var1,var2) {

    var h = '<div class="oelcardinfo" >';
    h += '<div class="describox70 padding3top">';
    h += '<div class=oelcardinfoh3 >Chose your document level</div>';
    h += '<div class="chooseleveldoc" >';
    h += '<div class="blockleveldoc first uileveldoc1" >Easy</div>';
    h += '<div class="blockleveldoc uileveldoc2" >Normal</div>';
    h += '<div class="blockleveldoc end uileveldoc3" >Advanced</div>';
    h += '</div></div>';
    h += '<div class="levelimg30">';
    h += '<div class="photo" ></div>';
    h += '</div></div>';

    return h;

}

function renderpluginoelcontentmagcover01(var1,var2) {

    var h = '<div class="oelcardinfo oelcardinfocover" >';
    h += '<div class="fotowmag01">';
    h += '<div class="photo" ></div>';
    h += '<div class="oelcentertitle" >';
    h += '<p>Lorem ipsum dolor sit amet, consectetur adipisicing elit.</p>';
    h += '</div>';
    h += '<div class="oelsummaryleftbottom" >';
	h += '<p>Sommaire</p></div>';
    h += '</div></div>';

    return h;

}

function render_oellearningnumericcards(var1,var2) {

    var h = '<div class="tcoel-cards-block">';
    h += '<h3 class="tcoel-block-title-no-icon">Points Importants à Retenir</h3>';
    h += '<div class="tcoel-cards-container">';

    h += '<div class="tcoel-card">';
    h += '<div class="tcoel-card-number"></div>';
    h += '<h3 class="tcoel-card-title">Définir sa cible</h3>';
    h += '<p class="tcoel-card-description">';
    h += 'Identifiez précisément votre audience pour adapter votre message et choisir les bons canaux de communication.';
    h += '</p>';
    h += '</div>';

    h += '<div class="tcoel-card">';
    h += '<div class="tcoel-card-number"></div>';
    h += '<h3 class="tcoel-card-title">Définir sa cible</h3>';
    h += '<p class="tcoel-card-description">';
    h += 'Une cible bien définie est la clé du succès.';
    h += '</p>';
    h += '</div>';

    h += '</div>';
    h += '</div>';

    return h;

}

function render_oelcontentkeypointsblock(var1,var2) {

    var h = '<div class="tcoel-key-points-block">';
    h += '<h3 class="tcoel-block-title">Points Clés du Chapitre</h3>';
    h += '<ul class="tcoel-key-points-list">';
    h += '<li class="tcoel-key-point">Comprendre les enjeux du digital dans l\'économie</li>';
    h += '<li class="tcoel-key-point">Identifier les différents canaux de communication</li>';
    h += '<li class="tcoel-key-point">Développer une stratégie</li>';
    h += '</ul>';
    h += '</div>';
    return h;

}
function displayFileEdit() {

	if($("#dataFileEditWindows").length==0){
		
		var bdDiv = '<div id="dataFileEditWindows" class="dataFileEditWindows " >';

        bdDiv += '<div class="gjs-mdl-dialog-v3 gjs-one-bg gjs-two-color" >';
		bdDiv += '<div class="gjs-mdl-header">';
		bdDiv += '<div class="gjs-mdl-title">Edition</div>';
		bdDiv += '</div></div>';

		bdDiv += '<div class="gjs-am-add-asset" ';
		bdDiv += 'style="padding:25px;font-size:16px;text-align:center;" >';

		bdDiv += 'File';
		bdDiv += '<input readonly="readonly" id="inputFileShowLink" type="text" value="'+filePageData+'" ';
		bdDiv += ' style="width:384px;font-size:12px;padding:5px" />';
		bdDiv += '<br/><br/>';
		bdDiv += '<a onClick="loadAFilePage();" ';
		bdDiv += ' style="padding:5px;padding-bottom:2px;padding-left:16px;padding-right:16px;width:50px;" ';
		bdDiv += ' class="gjs-one-bg ludiButtonSave" >';
		bdDiv += '<img src="icon/folder16.png" />';
		bdDiv += '</a>';
		
		bdDiv += '<br/>';
		
		bdDiv += '<div id="overviewfilepage" class="overviewfilepage" >';
		bdDiv += '</div>';

		bdDiv += '</div>';

		bdDiv += '</div>';

		$('body').append(bdDiv);

	}

	viewAFilePageGlobal();
	traductAll();
}

function loadAFilePage(){
	menuLudiBack();
	filterGlobalFiles = '.mp4.pdf';
    showFileManagerStudio2(23,'inputFileShowLink','refreshAFilePageGlobal');
}

function refreshAFilePageGlobal(){

    $('.ui-widget-overlay').css("display","none");
	$('.workingProcessSave').css("display","none");
	
	$('#overviewfilepage').html("");

	var fileA =$('#inputFileShowLink').val();

	viewAFilePageGlobal();

	$.ajax({
		url : '../ajax/params/params-get.php?step=4&idpg=' + idPageHtml + '&idteach=' + idPageHtmlTop+'&opt='+fileA + '&cotk=' + $('#cotk').val(),
		type: "POST",
		success: function(data,textStatus,jqXHR){
		},
		error: function (jqXHR, textStatus, errorThrown)
		{
			$('#inputFileShowLink').val("");
			$('#overviewfilepage').html("error");
		}
	});

}

function viewAFilePageGlobal(){
	
	$('#overviewfilepage').html("");

	var validfile = false;

	var fileA = $('#inputFileShowLink').val();

    if (fileA!=''&&
	fileA.toLowerCase().indexOf('.mp4')!=-1){
		validfile = true;
        $('#overviewfilepage').html("<video style='width:100%;height:100%;' src='"+fileA+"' muted controlsList='nodownload nofullscreen' ></video>");
    }

	if (fileA!=''&&
	fileA.toLowerCase().indexOf('.pdf')!=-1){
		validfile = true;
        $('#overviewfilepage').html("<img style='margin-top:40px' src='icon/file-pdf.png' />");
    }

	if (validfile==false) {
		$('#inputFileShowLink').val("");
		$('#overviewfilepage').html("");
	}

}

function menuLudiBack() {
	$('.ludimenu').css("z-index","99");
}

var clipboardDataTxt = "";
var clipboardHaveImage = false;
var clipboardBlob;
var clipboardBlob64;
var onePasteOnly = true;

function catchEventPaste(){

    if(onePasteOnly){
        if(clipboardDataTxt!=''){
            pasteWindowsShow(true);
            onePasteOnly = false;
        }
        if(clipboardHaveImage){
            pasteWindowsShow(true);
            onePasteOnly = false;
        }
    }

}

function installEventPaste(){

    var iframe = $('.gjs-frame');
	var iframeBody = iframe.contents().find("body");
    
    window.addEventListener("paste", function(thePasteEvent){
        if (windowEditorIsOpen == false ) {
            haveImageClip(thePasteEvent);
            if (clipboardHaveImage==false) {
                var dataH = thePasteEvent.clipboardData.getData('text/html');
                if (dataH.length>3) {
                    clipboardDataTxt = dataH;
                    catchEventPaste();
                }
            }else{
                clipboardDataTxt = '';
                catchEventPaste();
            }
        }
    }, false);

    window.addEventListener('keydown', e => {
        if (e.ctrlKey && e.key === 's') {
            e.preventDefault();
            saveSourceFrame(false,false,0);
            // console.log('CTRL + S');
        }
    });

    const keymaps = editor.Keymaps;
    
    keymaps.add('ns:my-keymap', '⌘+s, ctrl+s','saveEventShortCall');
    
    editor.Commands.add("saveEventShortCall", { 
        run: function(editor) { 
            saveEventShortCallFct(editor);
        }
    });

}

var timerInstallEventPaste = setTimeout(function(){
    installEventPaste();
},500);

function saveEventShortCallFct(editor){
    console.log('do saveEventShortCall');
}

function haveImageClip(event){

    var items = (event.clipboardData || event.originalEvent.clipboardData).items;
    console.log(JSON.stringify(items)); // will give you the mime types
    for (index in items) {
        var item = items[index];
        if (item.kind === 'file') {
            clipboardHaveImage = true;
            clipboardBlob = item.getAsFile();
            var reader = new FileReader();
            reader.onload = function(event){
                //console.log(event.target.result)
                clipboardBlob64 = event.target.result;
            }; // data url!
            reader.readAsDataURL(clipboardBlob);
        }
    }

}

var timerTabActive = setTimeout(function(){
    activeMaskCopyPaste()
},1000);

var isTabActive = false;
var decTabActiv = false;
var firstDivMask = 0;
var tickScrollEvt = 0;

$(window).on("focus", function(e) {
    if (decTabActiv==false) {
        isTabActive = true;
        $( ".maskpause" ).css("display","none");
    }
})

document.addEventListener("visibilitychange", event => {
    if (document.visibilityState == "visible") {
        isTabActive = true;
    } else {
        isTabActive = false;
    }
})

function activeMaskCopyPaste(){

    if (isTabActive==false) {
        $( ".maskpause" ).css("display","block");
        deleteAllTopMenu();
        tickScrollEvt = 0;
        decTabActiv = true;
        if (firstDivMask==0) {
            $( ".maskpause" ).css("display","none");
            deleteAllTopMenu();
            firstDivMask = 1;
            isTabActive = true;
        }
    }
    timerTabActive = setTimeout(function(){
        activeMaskCopyPaste();
    },500);
    
}

function pasteWindowsShow(fromevent){

    if($("#TeachDocPasteEditWindows").length==0){

		var bdDiv = '<div id="TeachDocPasteEditWindows" class="gjs-mdl-container" style="" >';
        bdDiv += '<div class="gjs-mdl-dialog-v2 gjs-one-bg gjs-two-color" ';
        bdDiv += ' style="max-width:800px!important;" >';
        bdDiv += '<div class="gjs-mdl-header">';
        bdDiv += '<div class="gjs-mdl-title">Integration process</div>';
        bdDiv += '<div class="gjs-mdl-btn-close gjs-mdl-btn-close-audio" ';
        bdDiv += ' onClick="closeAllEditWindows()" ';
        bdDiv += ' data-close-modal="">⨯</div>';
        bdDiv += '</div>';

        bdDiv += '<div id="superpastecontent" class="gjs-am-add-asset" ';
        bdDiv += 'style="padding:25px;padding-top:10px;font-size:15px;height:350px;overflow:auto;background-color:white;" >';
        bdDiv += '</div>';

        bdDiv += '<div style="padding:25px;text-align:center;" >';
        bdDiv += '<input id="btnPasteEditWindows" onClick="addPasteEditWindows()" ';
        bdDiv += ' class="gjs-one-bg ludiButtonSave" type="button" value="Insert" />';

        bdDiv += '<p id="btnPasteEditWindowsLoad" style="text-align:center;" >';
        bdDiv += '<img src="img/loadsave.gif" style="width:20px;" />';
        bdDiv += '</p>';

        bdDiv += '<input id="btnPasteEditWindowsError" ';
        bdDiv += ' class="gjs-one-bg ludiButtonSave" type="button" value=" Error  !" />';

        bdDiv += '<br/></div>';

		bdDiv += '</div>';

		$('body').append(bdDiv);
	}

    if($("#TeachDocPasteEditWindows").length==1){
        
		loadaFunction();
        
        if (fromevent) {

            if (clipboardHaveImage==false ) {
            
                $('#superpastecontent').html(clipboardDataTxt);
                CleanPasteWindowsTexte();
                clipboardDataTxt = $('#superpastecontent').html();
                $('#btnPasteEditWindows').css("display","");
            
            } else {

                $('#superpastecontent').html("<img src='' id='previewclipimage' style='width:76%;margin-left:12%;' />");
                $('#previewclipimage').attr("src",URL.createObjectURL(clipboardBlob));
                
                onePasteOnly = true;

                $('#btnPasteEditWindows').css("display","");
                $('#btnPasteEditWindowsError').css("display","none");
                $('#btnPasteEditWindowsLoad').css("display","none");

                setTimeout(function(){
                    if ($('#previewclipimage').height()>385) {
                        $('#previewclipimage').css("width","50%");
                        $('#previewclipimage').css("margin-left","25%");
                        if ($('#previewclipimage').height()>385) {
                            $('#previewclipimage').css("width","40%");
                            $('#previewclipimage').css("margin-left","30%");
                        }
                    }
                },100);

            }   

        } else {

            $('#superpastecontent').html('');
            $('#btnPasteEditWindows').css("display","none");
            $('#btnPasteEditWindowsError').css("display","none");
            $('#btnPasteEditWindowsLoad').css("display","none");

            $(document).focus(); 
            $(window).focus(); 
        
        }

		$('.ludimenu').css("display","none");
		$('#TeachDocPasteEditWindows').css("display","");
        traductAll();

	}

}

function addPasteEditWindows(){

    $('#btnPasteEditWindows').css("display","none");
    $('#btnPasteEditWindowsLoad').css("display","");
    var gjsHtml = localStorage.getItem("gjs-html-" + idPageHtml);

    var ctrFinaltag = gjsHtml.slice(-6);
	if(ctrFinaltag=="</div>"){
    
        if (clipboardHaveImage ) {
            
            uploadImageToCache();

        } else {

            var tb = '<table class="teachdoctext" onmousedown="parent.displayEditButon(this);" >'
            tb += '<tbody><tr><td class="teachdoctextContent">';
            tb += clipboardDataTxt;
            tb += '</td></tr></tbody></table>';
            gjsHtml =  gjsHtml.slice(0, -6) + tb + "</div>";
            localStorage.setItem("gjs-html-" + idPageHtml,gjsHtml);
            loadFXObjectevent = true;
            saveSourceFrame(false,false,0);

        }

    }

}

function uploadImageToCache(){

    const formData = new FormData();

    const req = new XMLHttpRequest();
    req.open('POST', '../ajax/ajax.upldblob.php?class=1'+ '&cotk=' + $('#cotk').val(), true);
    req.onload = function () {
        if (req.status >= 200 && req.status < 400) {
            const res = req.responseText;
            if (res.indexOf("KO")==-1) {
                console.log("load :" + res);
                moveFinalImageToWorkingFolder(res);
            } else {
                console.log("error pass 1 :" + res);
                uploadImageToCachePass2();
            }
        }
    };

    /* Create a new FileReader. */
    var fileReader = new FileReader();

    fileReader.onload = function(event) {
        /* Once the file has finished loading, run the following: */
        formData.append("file64", this.result);
        req.send(formData);
    };
    
    /* Tell the file reader to asynchronously load the files contents. */
    fileReader.readAsDataURL(clipboardBlob);

}

function uploadImageToCachePass2(){

    var v = Math.floor(Math.random() * 10000);
    const formData = new FormData();

    formData.append('file', clipboardBlob);
    
    const req = new XMLHttpRequest();
    req.open('POST', '../ajax/ajax.upldblob.php?class=1'+ '&cotk=' + $('#cotk').val(), true);
    req.onload = function () {
        if (req.status >= 200 && req.status < 400) {
            const res = req.responseText;
            if (res.indexOf("KO")==-1) {
                console.log("load :" + res);
                moveFinalImageToWorkingFolder(res);
            } else {
                console.log("error :" + res);
                $('#btnPasteEditWindowsError').css("display","");
                $('#btnPasteEditWindowsLoad').css("display","none");
            }
        }
    };

    req.send(formData);

}

function dataURItoBlob(dataURI) {
    var byteString = atob(dataURI.split(',')[1]);
    var ab = new ArrayBuffer(byteString.length);
    var ia = new Uint8Array(ab);
    for (var i = 0; i < byteString.length; i++) {
        ia[i] = byteString.charCodeAt(i);
    }
    return new Blob([ab], { type: 'image/jpeg' });
}

function moveFinalImageToWorkingFolder(imageName){

    var formData2 = {
        id : idPageHtml,
        name : imageName
    };
    $.ajax('../ajax/ajax.upldblob.php?step=2'+ '&cotk=' + $('#cotk').val(), {
        method: "POST",
        data: formData2,
        success: function (res) {
            if (res.indexOf("KO")==-1) {
                console.log("uplload :" + res);
                insertImgIntoDoc(res);
            }
        },
        error: function (data) {
            console.log("error :" + data);
        }
    });

}

function insertImgIntoDoc(imagePath){

    var gjsHtml = localStorage.getItem("gjs-html-" + idPageHtml);
    var ctrFinaltag = gjsHtml.slice(-6);

	if(ctrFinaltag=="</div>"){

        var tb = "<img class='bandeImg' src='" + imagePath + "' />";
        gjsHtml =  gjsHtml.slice(0, -6) + tb + "</div>";
        localStorage.setItem("gjs-html-" + idPageHtml,gjsHtml);
        loadFXObjectevent = true;
        saveSourceFrame(false,false,0);

    }
}

function CleanPasteWindowsTexte(){

    $('#superpastecontent p').each(function(){
        CleanOneElementPaste($(this));
    });
    $('#superpastecontent a').each(function(){
        weight = $(this).css('font-weight');
        $(this).attr('style','');
        $(this).attr('target','_blank');
        $(this).removeClass();
        $(this).css('font-weight',  weight);
    });
    $('#superpastecontent span').each(function(){
        CleanOneElementPaste($(this));
    });
    $('#superpastecontent ul').each(function(){
        CleanOneElementPaste($(this));
    });
    $('#superpastecontent li').each(function(){
        CleanOneElementPaste($(this));
    });
    $('#superpastecontent h1').each(function(){
        CleanOneElementPaste($(this));
    });
    $('#superpastecontent h2').each(function(){
        CleanOneElementPaste($(this));
    });
    $('#superpastecontent h3').each(function(){
        CleanOneElementPaste($(this));
    });
    $('#superpastecontent h4').each(function(){
        CleanOneElementPaste($(this));
    });

    //clean wikipedia.org update
    $('#superpastecontent a').each(function(){
        var hrefloc = $(this).attr('href');
        if (hrefloc.indexOf("wikipedia.org")!=-1) {
            if (hrefloc.indexOf("action=edit")!=-1) {
                $(this).remove();
            }
        }
    });

}

function CleanOneElementPaste(Tobj){

    var weight = Tobj.css('font-weight');
    var colorT = Tobj.css('color');

    Tobj.attr('style','');
    Tobj.removeClass();
    Tobj.css('font-weight',  weight);

    var colorC = colorT.replace(/[^0-9,]+/g, "");
    var red = colorC.split(",")[0];
    var gre = colorC.split(",")[1];
    var blu = colorC.split(",")[2];
    if (red<20&&gre<20&&blu<20) {
        colorT = 'black';
    }
    if (colorT!='black') {
        Tobj.css('color',colorT);
    }
}

function displaySubPageEdit(i){
	
	refIdPageLudi = i;
	
	saveSourceFrame(false,false,0);
	
	$('.ludimenu').css("z-index","2");

	if($("#pageEditAdd").length==0){

		var bdDiv = '<div id="pageEditAdd" class="gjs-mdl-container" >';

		bdDiv += '<div class="gjs-mdl-dialog-v2 gjs-one-bg gjs-two-color" style="max-width:700px;" >';
		bdDiv += '<div class="gjs-mdl-header">';
		bdDiv += '<div class="gjs-mdl-title trd">Edition</div>';
		bdDiv += '<div class="gjs-mdl-btn-close" onClick="closeAllEditWindows()" data-close-modal="">⨯</div>';
		bdDiv += '</div>';
		
		bdDiv += '<div class="gjs-am-add-asset" ';
		bdDiv += 'style="padding:25px;padding-top:15px;font-size:16px;" >';

		bdDiv += '<div class="gjs-am-add-asset oelTitlePage" style="padding:4px;" >Title&nbsp;:&nbsp;';
		bdDiv += '<input id="inputTitlePage" type="text" value="" style="width:450px;font-size:14px;padding:4px;" />';
		bdDiv += '</div>';

        bdDiv += '<div id="oelTitleload" class="gjs-am-add-asset" ';
		bdDiv += 'style="padding:25px;font-size:16px;display:none;" >';
        bdDiv += '<p style="text-align:center;" ><br/><img src="img/cube-oe.gif" /><br/><br/></p>';
		bdDiv += '<br/>';
		bdDiv += '</div>';

		bdDiv += '<div class="gjs-am-add-asset oelTitlePage" style="padding:16px;padding-left:40px;" >';
		bdDiv += '<input type="radio" ';
		bdDiv += 'id="typenode2" name="typenode" ></input>';
		bdDiv += '<label style="cursor:pointer;" class="trd" for=typenode2 >Content&nbsp;</label>&nbsp;&nbsp;';

		bdDiv += '<input type="radio" ';
		bdDiv += 'id="typenode3" name="typenode" ></input>';
		bdDiv += '<label style="cursor:pointer;" class="trd" for=typenode3 >Section&nbsp;</label>&nbsp;&nbsp;';
		
		bdDiv += '<input type="radio" ';
		bdDiv += 'id="typenode4" name="typenode" ></input>';
		bdDiv += '<label style="cursor:pointer;" class="trd" for=typenode4 >File&nbsp;</label>';

		bdDiv += '</div>';

		bdDiv += loadPageTemplatesDOM()

		bdDiv += '<div class="oelInputAdd1" style="padding:25px;text-align:right;" >';

		bdDiv += '<input id="inputAddSubPage" onClick="saveNextSubLudi()" ';
		bdDiv += ' class="gjs-one-bg ludiButtonSave trd" type="button" value="Add" /><br/>';
		bdDiv += '</div>';

		bdDiv += '</div>';

		bdDiv += '</div>';

		bdDiv += '<div class="gjs-mdl-collector" style="display: none"></div>';
		bdDiv += '</div>';

		$('body').append(bdDiv);

	}

	if($("#pageEditAdd").length==1){
		
		windowEditorIsOpen = true;
		loadaFunction();
		
		getTemplatesPageLst();

		$('.ludimenu').css("display","none");
		$('#oelTitleload').css("display","none");

		$('.pageDefautTplRight').css("display","none");
		$('.pageTemplateRight').css("display","none");

		$('.ludiEditMenuContext').css("display","none");
		$('#pageEditAdd').css("display","");

		$('#typenode2').attr('checked',true);
		$('#typenode3').attr('checked',false);
		$('#typenode4').attr('checked',false);

		$('#inputTitlePage').focus();
	}

}

function loadPageTemplatesDOM(){

	var bdDiv = '';

	bdDiv += '<div class="gjs-am-add-asset listPageTemplateOel oelChosePage" style="display:none;" >';
	bdDiv += '<img class="tpl-page-loader" src="img/loadsave.gif" style="margin:35px;display:none;" />';
	bdDiv += '<p class="tpl-page-title trd" >Choose a page style</p>';

	bdDiv += '<div class="areaContainLstB areaContainLst" >';

	if (optionsCSCDT.indexOf(';')!=-1) {
		var ArrayObjects = optionsCSCDT.split(';');
		var iter = 0;
		for (iter=0;iter<ArrayObjects.length;iter++) {
			var objInfos = ArrayObjects[iter];
			if(typeof objInfos === 'undefined'||objInfos === null){
				objInfos = '';
			}
			if (objInfos!='') {
				var rowTpl = '<div class="contain-pagetpl-select" >';
                rowTpl += '<img onClick="selectTplPage('+parseInt(iter + 100)+');" ';
                rowTpl += ' tplref="'+objInfos+'" class="tpl-page-select customtplpage tplpage'+parseInt(iter + 100)+'" ';
                rowTpl += ' src="../custom_code/page-templates/'+objInfos+'/overview.png" />';
                rowTpl += '</div>';
				bdDiv = bdDiv + rowTpl;
				nbContainLstB = nbContainLstB + 1;
			}
		}
	}
	
	bdDiv += '<div class="contain-pagetpl-select" >';
	bdDiv += '<img onClick="selectTplPage(0);" class="tpl-page-select tplpage0" src="templates/pages/p0.jpg" />';
	bdDiv += '</div>';

	bdDiv += '<div class="contain-pagetpl-select" >';
	bdDiv += '<img onClick="selectTplPage(1);" class="tpl-page-select tplpage1" src="templates/pages/p1.jpg" />';
	bdDiv += '</div>';

	bdDiv += '<div class="contain-pagetpl-select" >';
	bdDiv += '<img onClick="selectTplPage(2);" class="tpl-page-select tplpage2" src="templates/pages/p2.jpg" />';
	bdDiv += '</div>';

	bdDiv += '<div class="contain-pagetpl-select" >';
	bdDiv += '<img onClick="selectTplPage(10);" class="tpl-page-select tplpage10" src="templates/pages/p10.png" />';
	bdDiv += '</div>';

	bdDiv += '<div class="contain-pagetpl-select" ><div class="tpl-page-select-empty" ></div></div>';
	bdDiv += '<div class="contain-pagetpl-select" ><div class="tpl-page-select-empty" ></div></div>';

	bdDiv += '</div>';

	bdDiv += '</div>';

	bdDiv += '<a onClick="moveDefautTpl();" class="pageDefautTplRight" ></a>';

	bdDiv += '<a onClick="moveCustomTpl();" class="pageTemplateRight" ></a>';

	bdDiv += '<div class="gjs-am-add-asset listPageTemplateOel oelChoseTemplatesPage" style="display:none;" >';
	// bdDiv += '<img class="tpl-page-loader" src="img/loadsave.gif" style="margin:35px;display:none;" />';
	bdDiv += '<p class="tpl-page-title trd" >Choose a template style</p>';
	bdDiv += '</div>';
	
	bdDiv += '<div class="oelInputAdd2" style="position:relative;padding:25px;padding-top:15px;text-align:right;display:none;" >';
	bdDiv += '<input id="inputAddSubPage" onClick="saveNextSubLudiFinal()" ';
	bdDiv += ' class="gjs-one-bg ludiButtonSave trd" type="button" value="Valid" /><br/>';
	bdDiv += '</div>';
	
	return bdDiv;

}

var areaContainLstA = 0;
var nbContainLstA = 0;

function moveCustomTpl() {

	if (nbContainLstA>3) {

		areaContainLstA = areaContainLstA + 4;

		if (areaContainLstA>parseInt(nbContainLstA-4)) {
			areaContainLstA = 0;
		}
		
		$( ".areaContainLstA" ).animate({
			marginLeft: "-" + parseInt(areaContainLstA * 130) + "px"
		},500,function(){

		});

	}

}

var areaContainLstB = 0;
var nbContainLstB = 5;

function moveDefautTpl() {

	if (nbContainLstB>5) {

		//$(".areaContainLstB").css("width",parseInt(areaContainLstB*130) + 'px')
		
		areaContainLstB = areaContainLstB + 3;

		if (areaContainLstB>parseInt(nbContainLstB-3)) {
			areaContainLstB = 0;
		}
		
		$( ".areaContainLstB" ).animate({
			marginLeft: "-" + parseInt(areaContainLstB * 130) + "px"
		},500,function(){

		});

	}

}


function getTemplatesPageLst(){

	$.ajax({
		url : '../ajax/params/get-templ-pages.php?id=' + refIdPageLudi + '&pt=' + idPageHtmlTop + '&att=' + randomIdTpls() + '&cotk=' + $('#cotk').val(),
		type: "POST",
		success: function(data,textStatus,jqXHR){

			if(data.indexOf("KO")==-1&&data.indexOf("error")==-1){

				var renderH = data;
				
				if (renderH=='') {
					$('.oelChoseTemplatesPage').css('display','none');
				} else {
					$('.oelChoseTemplatesPage').html(renderH);
					var nbCustomPg = $('.customtplpage').length;
					nbContainLstA = nbCustomPg;
				}

			}

		},
		error: function (jqXHR, textStatus, errorThrown)
		{
			$('#logMsgLoad').css("display","block");
			$('#logMsgLoad').html("Error !");
			alert(textStatus);
		}
	});


}

function randomIdTpls() {
	return Math.floor((1 + Math.random()) * 0x10000)
		.toString(16)
		.substring(1);
}

var oneTemplateSave = false;

function displaySubPageExport(i){
	
	refIdPageLudi = i;
	
	saveSourceFrame(false,false,0);
	
	$('.ludimenu').css("z-index","2");

	if($("#pageEditAddExport").length==0){

		var bdDiv = '<div id="pageEditAddExport" class="gjs-mdl-container" >';
		
		bdDiv += '<div class="gjs-mdl-dialog-v2 gjs-one-bg gjs-two-color" style="max-width:575px;" >';
		bdDiv += '<div class="gjs-mdl-header">';
		bdDiv += '<div class="gjs-mdl-title trd">Save a page as a template</div>';
		bdDiv += '<div class="gjs-mdl-btn-close" onClick="closeAllEditWindows()" data-close-modal="">⨯</div>';
		bdDiv += '</div>';
		
        bdDiv += '<div class="gjs-am-add-asset" ';
        bdDiv += 'style="padding:20px;font-size:16px;padding-bottom:0px;" >';
        bdDiv += '<div style="width:100px;height:140px;overflow:hidden;margin-left:auto;margin-right:auto;"  >';
        bdDiv += '<iframe border=0 scrolling="no" src="view-page.php?id='+idPageHtml+'" style="background:white;border:solid 1px gray;" ></iframe>';
        bdDiv += '</div>';
        bdDiv += '</div>';
		
		bdDiv += '<div class="gjs-am-add-asset" ';
		bdDiv += 'style="padding:25px;font-size:16px;" >';

		bdDiv += '<div class="gjs-am-add-asset oelTitlePage areaPageExportSave" style="padding:4px;" >';
		bdDiv += '&nbsp;&nbsp;&nbsp;&nbsp;<span class="trd" >Title</span>&nbsp;:&nbsp;&nbsp;';
		bdDiv += '<input id="inputTitlePageExport" type="text" value="" ';
		bdDiv += 'style="width:380px;font-size:14px;padding:4px;" />';
		bdDiv += '</div>';

		bdDiv += '<div id="allPageExportLoad" class="gjs-am-add-asset" ';
		bdDiv += 'style="padding:5px;display:none;" >';
        bdDiv += '<p style="text-align:center;" ><img src="img/cube-oe.gif" /></p>';
		bdDiv += '</div>';
		
		bdDiv += '<div class="areaPageExportSave" style="padding:25px;text-align:right;" >';

		bdDiv += '<div id="inputAddPageExportFake" ';
		bdDiv += ' style="width:100px;text-align:center;padding:2px;float:right;" ';
		bdDiv += ' class="gjs-one-bg ludiButtonSave trd" ><img src="img/loadsave.gif" /></div>';

		bdDiv += '<input id="inputAddPageExport" onClick="processSaveTemplate();" ';
		bdDiv += ' style="display:none;padding:7px;width:100px;" ';
		bdDiv += ' class="gjs-one-bg ludiButtonSave trd" type="button" value="Valid" /><br/>';
		bdDiv += '</div>';

		bdDiv += '</div>';

		bdDiv += '</div>';

		bdDiv += '<div class="gjs-mdl-collector" style="display: none"></div>';
		bdDiv += '</div>';

		$('body').append(bdDiv);

	}

	if($("#pageEditAddExport").length==1){
		
		windowEditorIsOpen = true;
		$('.ludimenu').css("display","none");
		$('#pageEditAddExport').css("display","");
		$('#inputTitlePageExport').focus();
		
		setTimeout(function(){
			$('#inputAddPageExport').css("display","");
			$('#inputAddPageExportFake').css("display","none");
		},9000);

		if (oneTemplateSave){
			reloadPageErr();
		}

	}

}

function processSaveTemplate(){

    var titleExport = $("#inputTitlePageExport").val();
	
	if (titleExport!=''&&onlyOneUpdate) {
		
		onlyOneUpdate = false;
		
		$(".areaPageExportSave").css('display','none');
		$("#allPageExportLoad").css('display','');
		
		$.ajax({
			url : '../ajax/export/ajax.export-page.php?id=' + idPageHtml + '&title=' + titleExport + '&cotk=' + $('#cotk').val(),
			type: "POST",
			success: function(data,textStatus,jqXHR){
				$("#allPageExportLoad").css('display','none');
				if (data.indexOf('OK')!=-1) {
					setTimeout(function(){
						closeAllEditWindows();
					},300);
				} else {
					$("#allPageExportLoad").css('display','none');
				}
				onlyOneUpdate = true;
				oneTemplateSave = true;
			},
			error: function (jqXHR, textStatus, errorThrown)
			{
				onlyOneUpdate = true;
			}
		});
	}

}

function inIframe(){
	if(top.frames.length == 0) {
		return false;
	}else{
		return true;
	}
}

function cleText(s){
	if (s == 'undefined'){return "";}
	if (typeof(s) == 'undefined'){return "";}else{return s;}
}

function getFileNameByUrl(url){
	if (url.indexOf('/')!=-1) {
		url = url.substring(url.lastIndexOf('/')+1);
	} else {
		if (url.indexOf('\\')!=-1) {
			url = url.substring(url.lastIndexOf('\\')+1);
		}
	}
	return url;
}

function cleTextAct(s){
	if (s == 'undefined'){
		s = "";
	}
	if (typeof(s) == 'undefined'){
		s = "";
	}
	s = s.replace('\'','&apos;');
	s = s.replace('\'','&apos;');
	s = s.replace('\'','&apos;');
	s = s.replace('\'','&apos;');
	s = s.replace(/'/g,'');
	s = s.replace(/$/g,'');
	return s;
}


function cleTextTitle(s){
	if (s == 'undefined'){
		s = "";
	}
	if (typeof(s) == 'undefined'){
		s = "";
	}
	s = s.replace('\'','&apos;');
	s = s.replace('\'','&apos;');
	s = s.replace('\'','&apos;');
	s = s.replace('\'','&apos;');
	s = s.replace(/'/g,'');
	s = s.replace(/$/g,'');
	return s;
}