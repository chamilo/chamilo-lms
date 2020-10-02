
$(document).ready(function(){

	initTable();

	$('#boostTitle_pathLogo').css("width","80%").css("float","left");
	$('#boostTitle_pathLogo').after("<div id='SelectDrive' style='float:left;width:35px;height:35px;cursor:pointer;margin-left:10px;' ><img src='resources/img/select-img.png' /></div>");
	
	$('#SelectDrive').click(
		function(){
			idRefImage = '#boostTitle_pathLogo';
			showFileManagerDiv()
		}
	);
	$("#boostTitle_itemsList").parent().parent().css("display","none");

});

function initColor(){

	$('#boostTitle_Color1').attr("type","color");
	$('#boostTitle_Color1').css("width","40%").css("float","left");
	$('#boostTitle_Color1').after("<div id='SeleColor1' onClick='resetColors();' style='float:left;width:35px;height:35px;margin-left:10px;cursor:pointer;' ><img src='resources/img/maj.png' /></div>");
	$('#boostTitle_Color1').after("<div id='viewColor1' style='border-radius:50%;float:left;width:35px;height:35px;margin-left:10px;border:solid 1px gray;' ></div>");

	$('#boostTitle_Color2').attr("type","color");
	$('#boostTitle_Color2').css("width","40%").css("float","left");
	$('#boostTitle_Color2').after("<div id='viewColor2' style='border-radius:50%;float:left;width:35px;height:35px;margin-left:10px;border:solid 1px gray;' ></div>");

	$('#boostTitle_ColorText').attr("type","color");
	$('#boostTitle_ColorText').css("width","40%").css("float","left");
	$('#boostTitle_ColorText').after("<div id='viewColorText' style='border-radius:50%;float:left;width:35px;height:35px;margin-left:10px;border:solid 1px gray;' ></div>");
	
	viewColors();

}

function viewColors(){

	$('#viewColor1').css("background-color",$('#boostTitle_Color1').val());
	$('#viewColor2').css("background-color",$('#boostTitle_Color2').val());
	$('#viewColorText').css("background-color",$('#boostTitle_ColorText').val());

	
	$('.nav-side-menu-boost').css("color",$('#boostTitle_ColorText').val());
	$('.nav-side-menu-boost').css("background-color",$('#boostTitle_Color1').val());
	$('.nav-side-menu-boost .brand').css("background-color",$('#boostTitle_Color1').val());
	$('.nav-side-menu-boost .brand-login').css("border-color",$('#boostTitle_Color1').val());
	$('.nav-side-menu-boost li').css("background-color",$('#boostTitle_Color2').val());
	$('.nav-side-menu-boost li').css("border-color",$('#boostTitle_Color1').val());
	$('.nav-side-menu-boost li').css("border-left","3px solid " + $('#boostTitle_Color2').val());
	$('.nav-side-menu-boost li').css("color",$('#boostTitle_ColorText').val());
	$('.nav-side-menu-boost ul .sub-menu li, .nav-side-menu-boost li .sub-menu li').css("background-color",$('#boostTitle_Color1').val());
	$('.brand-login').css("background-color",$('#boostTitle_Color2').val());
	$('.nav-side-menu-boost li a').css("color",$('#boostTitle_ColorText').val());
	
	var topnavigationColor = $("input[name='topnavigationColor']").is(':checked');

	if(topnavigationColor){
		$('.navbar-default').css("background-color",$('#boostTitle_Color2').val());
		$('.navbar-default').css("border-color",$('#boostTitle_Color2').val());
		$('.navbar-default .navbar-nav > li > a').css("color",$('#boostTitle_ColorText').val());
	}
	
	setTimeout(function(){ viewColors(); },400);

}

function resetColors(){

	$('#boostTitle_Color1').val('#23282e');
	$('#boostTitle_Color2').val('#2e353d');
	$('#boostTitle_ColorText').val('#e1ffff');

}

function initTable(){
	
	var cnt = '<table class="table" >';
	cnt += '<thead><tr>';
	cnt += '<th style="width:120px;" >Icon</th>';
	cnt += '<th>Name</th>';
	cnt += '<th>Link</th>';
	cnt += '<th>Params</th>';
	cnt += '<th style="width:90px;" >Actions</th>';
    cnt += '</tr>';
	cnt += '</thead>';
	cnt += '<tbody id="bodyListItems" >';
	cnt += '<tr id="actionListItems" >';
	cnt += '<td colspan=5 style="text-align:center;" >';
	cnt += '<img onClick="addItemsMenu();" style="cursor:pointer;" src="resources/img/add.png" />';
	cnt += '</td></tr>';
	cnt += '</tbody></table>';

	$('#menueditor').html(cnt);

	initTableItems();
	setTimeout(function(){ initColor(); },400);
	setTimeout(function(){ processEditItems(); },300);

}

function addItemsMenu(){

	var idL = uuidKeyTr();

	var trLst = '<tr id="' + idL + '" class="MenuTrItem" ' + uColorTr() + '  >';
	trLst += '<td class="MenuTdItem" ><input style="width:120px;" class="MenuIcon" value="" /></td>';
	trLst += '<td ><input style="width:100%;" class="MenuName" value="" /></td>';
	trLst += '<td><input style="width:100%;" class="MenuItems" value="" /></td>';
	trLst += '<td><input style="width:100%;" class="MenuParams" value="" /></td>';
	trLst += '<td>';

	trLst += '<img style="cursor:pointer;" onClick="uplineTbl(\'' + idL + '\');" src="resources/img/up.png" />';
	trLst += '<img style="cursor:pointer;" onClick="downlineTbl(\'' + idL + '\');" src="resources/img/down.png" />';
	trLst += '<img style="cursor:pointer;" onClick="deletelineTbl(\'' + idL + '\');" src="resources/img/delete.png" />';
	trLst += '</td></tr>';

	$('#actionListItems').before(trLst);

}

function initTableItems(){

	var elemTable = $('#boostTitle_itemsList').val();
	var elemLst = elemTable.split('|');
	var i = 0;

	for (i = 0; i < elemLst.length; i++){

		var elemPart = elemLst[i];
		if(elemPart!=''){

			var elemObject = elemPart.split('@');

			var idL = uuidKeyTr();

			var trLst = '<tr id="' + idL + '" class="MenuTrItem" ' + uColorTr() + ' >';
			trLst += '<td class="MenuTdItem" ><input style="width:120px;" class="MenuIcon" value="'+ elemObject[0] +'" /></td>';
			trLst += '<td><input style="width:100%;" class="MenuName" value="'+ elemObject[1] +'" /></td>';
			trLst += '<td><input style="width:100%;" class="MenuItems" value="'+ elemObject[2] +'" /></td>';
			trLst += '<td><input style="width:100%;" class="MenuParams" value="'+ elemObject[3] +'" /></td>';
			trLst += '<td>';
			
			trLst += '<img style="cursor:pointer;" onClick="uplineTbl(\'' + idL + '\');" src="resources/img/up.png" />';
			
			trLst += '<img style="cursor:pointer;" onClick="downlineTbl(\'' + idL + '\');" src="resources/img/down.png" />';
			trLst += '<img style="cursor:pointer;" onClick="deletelineTbl(\'' + idL + '\');" src="resources/img/delete.png" />';
			trLst += '</td>';
			trLst += '</tr>';

			$('#actionListItems').before(trLst);

		}
		
	}

}

function processEditItems(){

	var listItems = '';
	$('.MenuTrItem').each(function(){
		listItems += $(this).find('.MenuIcon').val() + '@' + $(this).find('.MenuName').val() + '@' + $(this).find('.MenuItems').val()+ '@' + $(this).find('.MenuParams').val() + '|';
	});
	$('#boostTitle_itemsList').val(listItems);
	setTimeout(function(){ processEditItems(); },250);

}

function deletelineTbl(i){
	
	if(window.confirm("Sur ?")){ 
		$("#" + i).remove();
	}

}
function uplineTbl(i){	
	
	var row = $("#" + i);
	row.insertBefore(row.prev());

}
function downlineTbl(i){

	var row = $("#" + i).next();
	if(row.attr('id')!='actionListItems'){
		row.insertBefore(row.prev());
	}	

}

function uuidKeyTr() { 
    return 'xxxxxxxxxxxxxxx'.replace(/[xy]/g, function(c) { 
        var r = Math.random() * 16 | 0, v = c == 'x' ? r : (r & 0x3 | 0x8); 
        return v.toString(16); 
    }); 
}

function uColorTr(){ 
	var brightness = 220;
	return ' style="background-color:#'+ randomChannel(brightness) + randomChannel(brightness) + randomChannel(brightness) + '" ';
}

function randomChannel(brightness){
    var r = 255-brightness;
    var n = 0|((Math.random() * r) + brightness);
    var s = n.toString(16);
    return (s.length==1) ? '0'+s : s;
}





function showFileManagerDiv(){
	
	$('<div \>').dialog({modal: true, width: "80%", title: "Select your file", zIndex: 99999,
		create: function(event, ui) {
			$(this).elfinder({
				resizable: false,
				url: "../../main/inc/lib/elfinder/connectorAction.php",
				commandsOptions: {
					getfile: {
					oncomplete: 'destroy'
					}
				},                            
				getFileCallback: function(file) {
					//alert(file.url);
					$('.ui-dialog').css("display","none");
					$('.ui-widget-overlay').css("display","none");
					$('#boostTitle_pathLogo').val(file.url);
					$('.gjs-am-add-asset button').css("background","black");

				}
			}).elfinder('instance')
		}
	});

}
