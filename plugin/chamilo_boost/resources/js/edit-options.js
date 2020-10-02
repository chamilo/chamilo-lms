
var idRefImage = '';

$(document).ready(function(){

	$('#boostTitle_logo').css("width","80%").css("float","left");
	$('#boostTitle_logo').after("<div id='SelectDrive' style='float:left;width:35px;height:35px;cursor:pointer;margin-left:10px;' ><img src='resources/img/select-img.png' /></div>");
	$('#SelectDrive').click(
		function(){
			idRefImage = '#boostTitle_logo';
			showFileManagerDiv()
		}
	);
	
	$('#boostTitle_logotop').css("width","80%").css("float","left");
	$('#boostTitle_logotop').after("<div id='SelectDriveTop' style='float:left;width:35px;height:35px;cursor:pointer;margin-left:10px;' ><img src='resources/img/select-img.png' /></div>");
	$('#SelectDriveTop').click(
		function(){
			idRefImage = '#boostTitle_logotop';
			showFileManagerDiv()
		}
	);


});

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
					$(idRefImage).val(file.url);
					$('.gjs-am-add-asset button').css("background","black");

				}
			}).elfinder('instance')
		}
	});

}
