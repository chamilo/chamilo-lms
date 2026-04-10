
var ludiicon = '/plugin/CStudio/img/base/oeltools32.png';
var ludiiconplus = '/plugin/CStudio/img/base/oeltools32plus.png';
var caneditparamicon = false;

$(document).ready(function($){
	
	var teachdocLstIds = $('#teachdocLstIds').html();

	if (teachdocLstIds!="no") {

    var h = '<a href="/plugin/CStudio/oel_tools_teachdoc_link.php?action=add&' + window.chamiloCidReq.queryParams + '" ';
    h += ' class="btn btn--plain-outline" ';
		h += ' alt="Studio Tools" title="Studio Tools">';
		h += '<img id="studioeltools" class="h-6" src="'+ ludiiconplus + '" ';
		h += ' alt="Studio Tools" title="Studio Tools" /> ';
		h += '</a>';
		$('.section-header__actions').prepend(h);

		if(teachdocLstIds==''){
			getIdsLocalStorage();
      getOelToolsId();
    }else{
      document.addEventListener("chamilo:lp-list-loaded", function () {
        installExtrasToolsOelTools(teachdocLstIds);
        getOelToolsId();
      });

      if (window.location.href.indexOf("/main/lp/lp_controller.php") !== -1) {
        getOelToolsId();
      }
		}

	}

	setTimeout(function(){processExtraPour();},100);

});

function installExtrasToolsOelTools(teachdocLstIds) {

	var action = getParamValueForOelTools('action');
	var lpId = getParamValueForOelTools('lp_id');

	if (action=="add_item"&&lpId!='') {
		
		if(teachdocLstIds.indexOf(','+lpId+',')!=-1){
			$('#doc_form').css('background-color','white').css('padding-top','50px').css('padding-bottom','70px').css('border-radius','20px').css('width','80%').css('margin-left','10%');
			$('#doc_form').html('<center><img style="width:50%;" src="/plugin/CStudio/img/base/oel_tools.jpg" /><br><br><img style="width:128px;margin-top:20px;" src="/plugin/CStudio/img/loadsaveline.gif" /></center>');
			$('#lp_sidebar').html('<center></center>');
			setTimeout(function(){
				window.location.href = "/plugin/CStudio/oel_tools_teachdoc_link.php?action=redir&idLudiLP=" + parseInt(lpId);
			},2000);
		}

	}

	if(
		(action==''&&lpId=='')
		||action=='switch_view_mode'||action=='delete'||action=='move_lp_up'
		||action=='move_lp_down'||action=="list"||action=='switch_attempt_mode'
		||action=='move_down_category'||action=='move_up_category'
	){
		installExtrasToolsLp(teachdocLstIds);
	}
	
}

//LP tools
function installExtrasToolsLp(teachdocLstIds) {

	var feedUpdateSplit = teachdocLstIds.split(",");
	var anchors = document.querySelectorAll(".lp-panel a");

	for (var i = 0; i < anchors.length; i++) {
		
		for (var x = 0; x < feedUpdateSplit.length; x++){

			if (feedUpdateSplit[x]!='') {

				if(feedUpdateSplit[x]=='canedit'){
					caneditparamicon = true;
				}else{

					var idlp = parseInt(feedUpdateSplit[x]);
					var aObj = anchors[i];

					var ctrurl = aObj.href + "&";

					if(ctrurl.indexOf("lp_controller.php")!=-1){

						if((ctrurl.indexOf("lp_id="+idlp+"&")!=-1)
						&&ctrurl.indexOf("teachdoc=")==-1){

							if (ctrurl.indexOf("action=view")!=-1) {

								var labObj = $(aObj).find('.lp_content_type_label');
								labObj.html("<em>TeachDoc tools</em>");
								
								var iObj = $(aObj).prev();
								if (iObj.length>0) {
									iObj.attr('src',ludiicon);
									iObj.css('height','24px').css('width','24px');
								}

								if(caneditparamicon){
									aObj.href = aObj.href + "&teachdoc=edit";
								}
							}

							if (ctrurl.indexOf("action=add_item")!=-1) {

								var iObj = $(aObj).prev();
								if (iObj.length>0) {
									iObj.attr('src',ludiicon);
									iObj.css('height','24px').css('width','24px');
								}
								if(caneditparamicon){
									aObj.href = aObj.href + "&teachdoc=edit";
								}

							}
							
						}

					}
					
					if (ctrurl.indexOf("lp_id="+idlp)!=-1) {

						if (ctrurl.indexOf("lp_controller.php")!=-1) {

							if (ctrurl.indexOf("action=copy")!=-1) {
								aObj.href = "#";
								aObj.style.display = 'none';
							}
							if (ctrurl.indexOf("action=switch_scorm_debug")!=-1) {
								aObj.href = "#";
								aObj.style.display = 'none';
							}
							if (ctrurl.indexOf("action=export_to_pdf")!=-1) {
								aObj.href = "#";
								aObj.style.display = 'none';
							}
							if (ctrurl.indexOf("action=switch_attempt_mode")!=-1) {
								aObj.href = "#";
								aObj.style.display = 'none';
							}

						}
						
						if (ctrurl.indexOf("lp_update_scorm.php?")!=-1) {
							aObj.style.display = 'none';
						}

					}
				
				}
			}
		}

	}

}

function getParamValueForOelTools(param) {
	var u = window.top.location.href;var reg=new RegExp('(\\?|&|^)'+param+'=(.*?)(&|$)');
	matches=u.match(reg);
	if(matches==null){return '';}
	var vari=matches[2] != undefined ? decodeURIComponent(matches[2]).replace(/\+/g,' ') : '';
	return vari;
}

function getOelToolsId(){

	$.ajax({
		url : '/plugin/CStudio/ajax/oel_tools_teachdoc_getids.php',
		type: "GET",
		success: function(data,textStatus,jqXHR){

			if(data.indexOf('KO')==-1){
				teachdocLstIds = data;
				window.localStorage.setItem('teachdocLstIds',data);
				installExtrasToolsOelTools(teachdocLstIds);
			} else {
				console.log('oel_tools_teachdoc_getids KO');
			}
			
		},error: function (jqXHR, textStatus, errorThrown){
			console.log('oel_tools_teachdoc_getids KO');
		}
	});
	
}

function getIdsLocalStorage() {

	var mem_context_data = '';

	if (localStorage) {

		mem_context_data = window.localStorage.getItem('teachdocLstIds');
		
		if (mem_context_data === null||mem_context_data == "null"){
			mem_context_data = "";
		}
		if (mem_context_data === undefined) {
			mem_context_data = "";
		}
		if (typeof mem_context_data == 'undefined') {
			mem_context_data = "";
		}
		if(mem_context_data!=""){
			installExtrasToolsLp(mem_context_data);
		}
	}

}

function processExtraPour() {

	var mem_idstudio = window.localStorage.getItem('idstudio');
	var mem_pourcstudio = window.localStorage.getItem('pourcstudio');

	if (mem_idstudio === null||mem_idstudio == "null"){
		mem_idstudio = "0";
	}
	if (mem_idstudio === undefined) {
		mem_idstudio = "0";
	}
	if (typeof mem_idstudio == 'undefined') {
		mem_idstudio = "0";
	}
	if (parseInt(mem_idstudio)>0) {
		
		// alert(processExtraPour);
		installPourcentageToolsLp(mem_idstudio,mem_pourcstudio);

		var lk = '/plugin/CStudio/ajax/sco/scorm-save-location.php';
		$.ajax({
			url: lk + "?loc=0&id=" + mem_idstudio + '&pour=' + mem_pourcstudio + '&' + window.chamiloCidReq.queryParams
		}).done(function(){
			window.localStorage.setItem('idstudio',0);
			window.localStorage.setItem('pourcstudio',0);
		});
	
	}

}

function installPourcentageToolsLp(mem_idstudio,mem_pourcstudio) {

	var anchors = document.getElementsByTagName("a");

	for (var i = 0; i < anchors.length; i++) {
		
		var idlp = parseInt(mem_idstudio);
		var aObj = anchors[i];
		var hrefObj =  aObj.href ;
		if (typeof hrefObj == 'undefined') {hrefObj = '';}
		hrefObj = hrefObj + "&";

		if (hrefObj.indexOf("lp_controller.php")!=-1) {

			if ((hrefObj.indexOf("lp_id="+idlp+"&")!=-1)) {

				var iObj = $(aObj).prev();
				var trObj = $(iObj).parent().parent();
				var valdefault = trObj.find('td:eq(3)').html();
				
				if (typeof valdefault == 'undefined') {valdefault = '';}

				if (valdefault.indexOf('>0%')!=-1) {
					trObj.find('td:eq(3)').html('<center><span style="font-weight:bold;color:green;" >' + mem_pourcstudio +'%</span></center>');
				}

				valdefault = trObj.find('td:eq(1)').text();
				if (typeof valdefault == 'undefined') {valdefault = '';}

				if (valdefault.indexOf('%')!=-1) {
					var objProgress = trObj.find('td:eq(1)').find('.progress').find('#progress_bar_value');
					objProgress.css("width",mem_pourcstudio + "%");
					objProgress.html(mem_pourcstudio + "%");
				}
				
			}

		}

	}

}