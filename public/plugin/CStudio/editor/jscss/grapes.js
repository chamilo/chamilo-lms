
var lstUlLoad = '<ul class="list-teachdoc"><li>&nbsp;</li><li>...</li><ul>';

//CodeMirror lib => For editing code
$(document).ready(function(){
	
	insertMRight();

	$('.gjs-pn-buttons').prepend('<span onClick="saveSourceFrame(false)" class="gjs-pn-btn fa fa-save"></span>');
	
	var bdDiv = '<div id="loadsave" ><img src="img/loadsave.gif" /></div>';
	bdDiv += '<div id="logMsgLoad" ';
	bdDiv += ' style="z-index:10;position:absolute;border:solid 1px red;';
	bdDiv += 'left:22%;top:60px;right:22%;height:600px;';
	bdDiv += 'background-color:white;display:none;" ></div>';
	
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

		localStorage.setItem("gjs-assets",dataImg);
		restyleCadreImage();
		
		var first = getParamValueForOeLEditor('first');
		if(first==1){
			firstRender();
		}

	},1000);

});

function restyleCadre(){
	
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
	$("div[title='Link Block']").css("display","none");
	$(".gjs-title").css("display","none");

	$(".fa-save").each(function(index){

		if(index==3){
			$(this).css("display","block");
			$(this).html("&nbsp;&nbsp;|&nbsp;");
			$(this).css("color","#0B0B61");
			$(this).attr("id","Teachdoc");
			$(this).removeClass("fa-save");
			$(this).addClass("fa-arrow-left");
		}
		if(index==1){
			$(this).css("display","block");
			$(this).css("color","#0B0B61");
			$(this).attr("id","btnsave");
		}
	
	});

	$("#Teachdoc").attr("onclick", "").unbind("click");

	$("#Teachdoc").click(function(){

		$('#btnsave').css("display","none");
		$('#loadsave').css("display","block");
		
		$.ajax({
			url : '../teachdoc-render.php?id=' + idPageHtmlTop,
			type: "POST",
			success: function(data,textStatus,jqXHR){
				saveSourceFrame(true);
			},
			error: function (jqXHR, textStatus, errorThrown)
			{
				$('#logMsgLoad').css("display","block");
				alert("Error : "+textStatus);
			}
		});

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

	reloadHtmlToGrap();

}

function firstRender(){
	
	$('#loadsave').css("display","block");

	$.ajax({
		url : '../ajax/ajax.save.php?id=' + idPageHtml,
		type: "POST",data : formData,
		success: function(data,textStatus,jqXHR){

			$.ajax({
				url : '../teachdoc-render.php?id=' + idPageHtmlTop,
				type: "POST",
				success: function(data,textStatus,jqXHR){
					$('#loadsave').css("display","none");
				},
				error: function (jqXHR, textStatus, errorThrown)
				{
					$('#loadsave').css("display","none");
				}
			});

		},
		error: function (jqXHR, textStatus, errorThrown)
		{
			$('#loadsave').css("display","none");
		}
	});

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
	allVideos.attr('style','max-width:960px;min-height:400px;max-height:540px;width:97%;');
	
	allVideos.each(function(index){
		var container = $(this).parent()
		var src = container.html();
		src = src.replace("editRapidIcon","editRapidIcon reload");
		container.html(src);
	});

}

function insertMRight(){
	$("body").css("position","relative");
	$('.gjs-editor-cont').before(getMenuR());
	$(".gjs-editor-cont").css("width","86%").css("right","0%").css("position","absolute");
	$('#labelMenuLudi'+idPageHtml).parent().addClass('activeli');
	refreshMenu();
}

function restyleCadreImage(){

	if(!document.getElementById("chamiloImages")){

		var bh = "<div id='chamiloImages' onClick='showFileManagerStudio(0,0);' style='width:450px;height:320px;border:solid 1px gray;text-align:center;cursor:pointer;' >";
		bh += "<br><br><br><br><br><br><br>Sélection d'une image du serveur</div>";
		$('.gjs-am-file-uploader').html(bh);
		setTimeout(function(){
			restyleCadreImage();
		},300);

	}
	

}

function saveSourceFrame(redirect){

	if(localStorage){
		
		$('#btnsave').css("display","none");
		$('#loadsave').css("display","block");

		var gjsHtml = localStorage.getItem("gjs-html");
		var gjsCss = localStorage.getItem("gjs-css");
		
		amplify.store("page-html", gjsHtml);
		amplify.store("page-css" , gjsCss);
		
		var formData = {
			id : idPageHtml,
			bh : amplify.store("page-html"),
			bc : amplify.store("page-css")
		};
		var extraRedi = "&r=0";
		if(redirect){
			extraRedi = "&r=1&pt=" + idPageHtmlTop;
		}

		$.ajax({
			url : '../ajax/ajax.save.php?id=' + idPageHtml + extraRedi,
			type: "POST",data : formData,
			success: function(data,textStatus,jqXHR){

				if(redirect){
					
					if(data.indexOf("error")!=-1){
						$('#logMsgLoad').css("display","block");
						$('#logMsgLoad').html(data);
					}else{
						window.location.href = data;
					}

				}else{
					
					if(data.indexOf("OK")!=-1){
						$('#btnsave').css("display","block");
						$('#loadsave').css("display","none");
					}else{
						// alert("Error");
						$('#logMsgLoad').css("display","block");
						$('#logMsgLoad').html(data);
					}

				}

			},
			error: function (jqXHR, textStatus, errorThrown)
			{
				$('#logMsgLoad').css("display","block");
				alert("Error : "+textStatus);
			}
		});
		
	}else{
		
		alert("localStorage error !");
		
	}
	
}


function getMenuR(){

	var h = '<div class="ludimenu" >';
	h += '<div class="luditopheader" ></div>';
	
	h += '<div class="ludimenuteachdoc" >';

	var loadM = amplify.store("menuHtmlInLocal" + idPageHtmlTop);
	if(loadM!=undefined||loadM!=''||loadM!='undefined'){
		h += loadM;
		
	}else{
		h += lstUlLoad;
	}
	
	h += '</div>';
	
	h += '</div>';

	//Context Menu
	h += '<div class="ludiEditMenuContext" >';
	h += '<input id="changeTitlePage" type="text" value="" style="width:310px;margin:11px;font-size:12px;padding:5px;" />';

	h += '<div class="uPIcon minIcon" onClick="upContextMenuSub(0);" ></div>';
	h += '<div class="dowNIcon minIcon" onClick="upContextMenuSub(1);" ></div>';

	h += '<input onClick="deleteContextMenuSub();" ';
	h += ' style="position:absolute;bottom:10px;left:10px;border:solid 1px gray;cursor:pointer;" ';
	h += ' class="gjs-one-bg ludiButtonDelete" type="button" value="Delete" />';

	h += '<input onClick="closeAllEditWindows();" ';
	h += ' style="position:absolute;bottom:10px;right:120px;border:solid 1px gray;cursor:pointer;color:white;" ';
	h += ' class="gjs-one-bg ludiButtonCancel" type="button" value="Cancel" />';

	h += '<input onClick="saveContextMenuSub();" ';
	h += ' style="position:absolute;bottom:10px;right:10px;border:solid 1px gray;cursor:pointer;color:white;" ';
	h += ' class="gjs-one-bg ludiButtonSave" type="button" value="Save" />';

	h += '</div>';
	
	return h;

}

function refreshMenu(){

	$.ajax({
		url : '../ajax/list-menu.php?v=2&id=' + idPageHtml,
		type: "GET",
		cache: false,
		success: function(data,textStatus,jqXHR){
			if(data.indexOf("ul")!=-1){
				$('.ludimenuteachdoc').html(data);
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
	
}

var onlyOneUpdate = true;
var subTitleLoadData = new Array();
var oldTitleLoadData = '';

function loadContextMenuSub(i,posi){

	if(onlyOneUpdate){
		
		refIdPageLudi = i;
		refPosiPageLudi = posi;
		
		$('.miniMenuLudi').css('color','black');
		$('.miniMenuLudi').css('background','transparent');
		$('#labelMenuLudi'+refIdPageLudi).css('background','#F3E2A9');

		if(subTitleLoadData[i]== undefined||subTitleLoadData[i]==''){
			subTitleLoadData[i] = $('#labelMenuLudi'+i).html();
		}
		if(subTitleLoadData[i]!=''){
			$('#labelMenuLudi'+refIdPageLudi).css('color','black');
			$('#changeTitlePage').val(subTitleLoadData[i])
		}
		if(idPageHtmlTop==refIdPageLudi){
			$('.ludiButtonDelete').css("display","none");
		}else{
			$('.ludiButtonDelete').css("display","block");
		}

		$('.ludiEditMenuContext').css("display","block");
		$('.ludiEditMenuContext').css("top",parseInt(78 + (posi * 35))+"px");
		
	}

}

function saveContextMenuSub(){

	var strLib = $('#changeTitlePage').val();
	$('#labelMenuLudi'+refIdPageLudi).html(strLib);

	var formData = {
		id : refIdPageLudi,
		title : strLib
	};
	
	onlyOneUpdate = false;

	$('#labelMenuLudi'+refIdPageLudi).css('color','orange');

	$.ajax({
		url : '../ajax/ajax.uptsubdoc.php?id=' + refIdPageLudi,
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

function deleteContextMenuSub(){

	if(idPageHtmlTop!=refIdPageLudi&&onlyOneUpdate){

		var formData = {id:refIdPageLudi};

		onlyOneUpdate = false;

		$('.ludiEditMenuContext').css("display","none");

		$('#labelMenuLudi'+refIdPageLudi).css('text-decoration','line-through');
		$('#labelMenuLudi'+refIdPageLudi).css('color','red');
		
		$.ajax({
			url : '../ajax/ajax.uptsubdoc.php?a=666&id=' + refIdPageLudi,
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
						window.location.href = "index.php?action=edit&id=" + parseInt(idPageHtmlTop);
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

		$('.minIcon').css("display","none");
		$('#labelMenuLudi'+refIdPageLudi).css('color','orange');
		
		if(u==0){

			if(refPosiPageLudi>0){
				refPosiPageLudi = refPosiPageLudi - 1;
				$('.ludiEditMenuContext').css("top",parseInt(78 + (refPosiPageLudi * 35))+"px");
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
				$('.ludiEditMenuContext').css("top",parseInt(78 + (refPosiPageLudi * 35))+"px");
			}

			var $current = $('#labelMenuLudi'+refIdPageLudi).parent().next();
			var $previous = $current.prev('li');
			if($previous.length !== 0){
			  $current.insertBefore($previous);
			}
		}

		$.ajax({
			url : '../ajax/ajax.subdocmoveup.php?a='+u+'&id=' + refIdPageLudi,
			type: "POST",
			data : formData,
			success: function(data,textStatus,jqXHR){

				if(data.indexOf('KO')==-1){
					$('#labelMenuLudi'+refIdPageLudi).css('color','black');
					$('.minIcon').css("display","block");
				}else{
					$('#labelMenuLudi'+refIdPageLudi).css('color','orange');
					$('#labelMenuLudi'+refIdPageLudi).css('text-decoration','none');
				}
				refreshMenu();
				onlyOneUpdate = true;
				
			},error: function (jqXHR, textStatus, errorThrown)
			{
				$('#logMsgLoad').css("display","block");
				alert("Error !");
				alert(textStatus);
				onlyOneUpdate = true;
			}
		});

	}

}

function saveContextMenuInLocal(){

	if(localStorage){
		var menuHtmlInLocal = $('.ludimenuteachdoc').html();
		menuHtmlInLocal = menuHtmlInLocal.replace('activeli','');
		menuHtmlInLocal = menuHtmlInLocal.replace(/onclick/g,'dataclick');
		menuHtmlInLocal = menuHtmlInLocal.replace(/onClick/g,'dataclick');
		amplify.store("menuHtmlInLocal" + idPageHtmlTop, menuHtmlInLocal);
	}
}
var oldUrlVideo = "";
var tmpNameDom = "editnode";
var tmpObjDom;

function installVideoEdit(){
	
}

function displayVideoEdit(myObj){

	var vidObj = $(myObj).next();
	tmpObjDom = vidObj;

	if($("#VideoEditLinks").length==0){

		var bdDiv = '<div id="VideoEditLinks" class="gjs-mdl-container" style="" >';
		bdDiv += '<div class="gjs-mdl-dialog gjs-one-bg gjs-two-color">';
		bdDiv += '<div class="gjs-mdl-header">';
		bdDiv += '<div class="gjs-mdl-title">Edition</div>';
		bdDiv += '<div class="gjs-mdl-btn-close" onClick="closeAllEditWindows()" data-close-modal="">⨯</div>';
		bdDiv += '</div>';
		
		bdDiv += '<div class="gjs-am-add-asset" ';
		bdDiv += 'style="padding:25px;font-size:16px;" >';
		bdDiv += 'Video&nbsp;file/url&nbsp;:&nbsp;';
		bdDiv += '<input id="inputVideoLink" type="text" value="http://" style="width:450px;font-size:12px;padding:2px;" />';
		bdDiv += '&nbsp;<input onClick="showFileManagerStudio(1,0);" ';
		bdDiv += ' style="border:solid 1px gray;padding:5px;cursor:pointer;color:white;" ';
		bdDiv += ' class="gjs-one-bg ludiButtonSave" type="button" value="..." />';
		
		bdDiv += '<br/>';
		bdDiv += '<div style="padding:25px;text-align:right;" >';
		bdDiv += '<input onClick="saveVideoEdit()" ';
		bdDiv += ' style="border:solid 1px gray;padding:7px;cursor:pointer;color:white;" ';
		bdDiv += ' class="gjs-one-bg ludiButtonSave" type="button" value="Save" /><br/>';
		bdDiv += '</div>';
		
		bdDiv += '</div>';
		bdDiv += '</div>';

		bdDiv += '<div class="gjs-mdl-collector" style="display: none"></div>';
		bdDiv += '</div>';
		$('body').append(bdDiv);

	}

	if($("#VideoEditLinks").length==1){
		
		strLinkString = vidObj.attr("datahref");
		var idm = Math.floor(Math.random() * Math.floor(200));
		tmpNameDom = 'tempnode' + idm;
		vidObj.find(".sourcevid").attr("name",tmpNameDom);
		vidObj.attr("id",tmpNameDom);

		oldUrlVideo = strLinkString;
		$('#inputVideoLink').val(strLinkString);	
		$('.ludimenu').css("display","none");
		$('#VideoEditLinks').css("display","");
	}

}

function saveVideoEdit(){

	var inputVideoLink = $('#inputVideoLink').val();

	var vidObj = tmpObjDom;
	vidObj.attr("datahref",inputVideoLink);
	vidObj.find(".sourcevid").attr("src",inputVideoLink);

	vidObj.load();

	var gjsHtml = localStorage.getItem("gjs-html");
	if(oldUrlVideo!=""&&inputVideoLink!=""&&oldUrlVideo!=inputVideoLink){
		gjsHtml = gjsHtml.replace(oldUrlVideo,inputVideoLink);
		gjsHtml = gjsHtml.replace(oldUrlVideo,inputVideoLink);
		oldUrlVideo = inputVideoLink;
		localStorage.setItem("gjs-html",gjsHtml);
		saveSourceFrame(false);
	}
	
	closeAllEditWindows();
}

function closeAllEditWindows(){
	$('.miniMenuLudi').css('background','transparent');
	$('#VideoEditLinks').css("display","none");
	$('#pageEditAdd').css("display","none");
	$('.ludimenu').css("display","");
	$('.ludiEditMenuContext').css("display","none");
}

var refIdPageLudi = 0;
var refPosiPageLudi = 0;

function displaySubPageEdit(i){

	refIdPageLudi = i;
	
	saveSourceFrame(false);
	
	$('.ludimenu').css("display","none");

	if($("#pageEditAdd").length==0){

		var bdDiv = '<div id="pageEditAdd" class="gjs-mdl-container" >';
		bdDiv += '<div class="gjs-mdl-dialog gjs-one-bg gjs-two-color" style="max-width:575px;" >';
		bdDiv += '<div class="gjs-mdl-header">';
		bdDiv += '<div class="gjs-mdl-title">Edition</div>';
		bdDiv += '<div class="gjs-mdl-btn-close" onClick="closeAllEditWindows()" data-close-modal="">⨯</div>';
		bdDiv += '</div>';
		
		bdDiv += '<div class="gjs-am-add-asset" ';
		bdDiv += 'style="padding:25px;font-size:16px;" >';
		bdDiv += 'Title&nbsp;:&nbsp;';
		bdDiv += '<input id="inputTitlePage" type="text" value="" style="width:450px;font-size:12px;padding:2px;" />';
		
		bdDiv += '<br/>';

		bdDiv += '<div style="padding:25px;text-align:right;" >';
		bdDiv += '<input onClick="saveNextSubLudi()" ';
		bdDiv += ' style="border:solid 1px gray;padding:7px;cursor:pointer;color:white;" ';
		bdDiv += ' class="gjs-one-bg ludiButtonSave" type="button" value="Ajouter" /><br/>';
		bdDiv += '</div>';
		
		bdDiv += '</div>';
		bdDiv += '</div>';

		bdDiv += '<div class="gjs-mdl-collector" style="display: none"></div>';
		bdDiv += '</div>';
		$('body').append(bdDiv);

	}

	if($("#pageEditAdd").length==1){
		$('.ludimenu').css("display","none");
		$('#pageEditAdd').css("display","");
	}

}

function saveNextSubLudi(){
	
	var inputTitlePageStr = $('#inputTitlePage').val();	
	
	if(inputTitlePageStr!=""){
		
		var formData = {
			id : refIdPageLudi,
			title : inputTitlePageStr
		};

		$.ajax({
			url : '../ajax/ajax.addsubdoc.php?id=' + refIdPageLudi,
			type: "POST",
			data : formData,
			success: function(data,textStatus,jqXHR){

				if(data.indexOf("KO")==-1&&data.indexOf("error")==-1){
				
					var idNp = parseInt(data);
					if(isNaN(idNp)){

					}else{
						window.location.href = "index.php?action=edit&id=" + parseInt(data);
					}
					
				}

			},
			error: function (jqXHR, textStatus, errorThrown)
			{
				$('#logMsgLoad').css("display","block");
				alert("Error !");
				alert(textStatus);
			}
		});

	}

}

var onlyOneRedirect = false;

function loadSubLudi(i){
	
	if(onlyOneRedirect==false){
		
		saveSourceFrame(false);

		$('.list-teachdoc li').removeClass('activeli');
		$('#labelMenuLudi'+i).parent().addClass('activeli');

		refIdPageLudi = i;
		onlyOneRedirect = true;

		$('.gjs-frame').css("display","none");
		$('.gjs-cv-canvas').css("position","relative");
		$('.gjs-cv-canvas').css("background-color","white");
		
		var fakeLoad = "<div class=fakeLoadFrame >";
		fakeLoad += "<br/>";
		fakeLoad += "<img class='loadbarre' style='width:240px;height:30px;margin:5px;' src='img/rectangle-loader.gif' />";
		fakeLoad += "<br/><br/>";
		fakeLoad += "<img style='width:150px;height:20px;margin:5px;' src='img/rectangle-loader.gif' />";
		fakeLoad += "<br/><br/>";
		fakeLoad += "<img style='width:250px;height:30px;margin:5px;' src='img/rectangle-loader.gif' />";
		fakeLoad += "<br/><br/>";
		fakeLoad += "<img style='width:150px;height:20px;margin:5px;' src='img/rectangle-loader.gif' />";
		fakeLoad += "</div>";

		$('.gjs-cv-canvas').html(fakeLoad);

		$(".loadbarre").animate({
			width: '480px'
		},3000, function(){
		});
		
		setTimeout(function(){
			window.location.href = "index.php?action=edit&id=" + parseInt(refIdPageLudi);
		},500);	
	
	}

}

var typeWindEditLink = 0;
function showFileMangerold(t){
	
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
					$('.ui-widget-overlay').css("display","none");
					if(typeWindEditLink==0){
						$('.gjs-am-add-asset').find("input").val(file.url);
						$('.gjs-am-add-asset button').css("background","black");
					}
					if(typeWindEditLink==1){
						$('#inputVideoLink').val(file.url);
					}
					
					if(typeWindEditLink==11){
						$('#datatext1').val(file.url);
					}
					if(typeWindEditLink==12){
						$('#datatext2').val(file.url);
					}

				}
			}).elfinder('instance')
		}
	});

}

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
	}
  });

  /*var basePrice = '<table style="width:100%;border-radius:4px;" >';
  basePrice += '<tbody><tr style="background-color:#F5F5F5;border:1px solid #ddd;">';
  basePrice += '<td style="padding:10px;color: #648A1B;">';
  basePrice += '<img style="margin-bottom:-5px;" src="img/shop.png" >&nbsp;Zone de commande';
  basePrice += '</td></tr></tbody></table>';

  editor.BlockManager.add('Price Bloc', {
	label: 'Price Bloc',
	attributes: {class: 'fa fa-text'},
	category: 'Basic',
	content: {
	  content: basePrice,
	  script: "console.log('the element', this)",
	  // Add some style just to make the component visible
	  style: {
		width: '100%',
		minHeight: '100px'
	  }
	}
  });
*/

var cssI = " style='position:absolute;cursor:pointer;background-image:url(\"img/editdoc.png\");background-position:center center;background-repeat:no-repeat;background-color:white;right:2px;top:3px;width:50px;height:50px;z-index: 1000;' ";

var baseButton = '<div class="row" style="position:relative;" id="i25td">';

  baseButton += '<div class="cell" style="text-align:center;position:relative;" >';

baseButton += '<div class="editRapidIcon" ' + cssI + ' onClick="parent.displayVideoEdit(this);" ></div>';

baseButton += '<video style="max-width:960px;min-height:400px;max-height:540px;width:97%;" oncontextmenu="return false;" class="videoByLudi" ';
baseButton += ' datahref="video/oel-teachdoc.mp4" ';
baseButton += ' controls  controlsList="nofullscreen nodownload" >';
baseButton += '<source name="sourcevid" class="sourcevid" src="video/oel-teachdoc.mp4" type="video/mp4">';
baseButton += '</video>';

  baseButton += '</div>';
  baseButton += '</div>';
  
  editor.BlockManager.add('VideoTeach', {
	label: '',
	attributes: {class: 'fa fa-text icon-action'},
	category: 'Basic',
	content: {
	  content: baseButton,
	  script: "parent.installVideoEdit()",
	  style: {
		width: '100%',
		minHeight: '100px'
	  }
	}
  });