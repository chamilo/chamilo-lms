
function getbyelem(n){
	
	if(document.getElementById(n)){
	
		var tagName = document.getElementById(n).tagName;
		
		if(tagName=='SELECT'){
			var get_id = document.getElementById(n);
			var resultselect = get_id.options[get_id.selectedIndex].value;
			//console.log(resultselect);
			return resultselect;
		}
		
		if(tagName=='INPUT'){
			return document.getElementById(n).value;
		}
		
		if(tagName=='TEXTAREA'){
			var ct = document.getElementById(n).value;
			ct = ct.replace('\n','<br />');
			return ct;
		}
	
	}else{
	
		return "-"
		
	}

	
}

setTimeout(function(){ applikSelectDemo(); }, 500);

function applikSelectDemo(){
	
	var textIconImg = getbyelem('dictionary_iconimg');
	var textTitle = getbyelem('dictionary_title');
	
	$("#icodemo").removeClass();
	$("#icodemo").addClass(textIconImg);
	$("#icodemo").addClass('document-vignette');
	$("#titledemo").html(textTitle);
	
	setTimeout(function(){ applikSelectDemo(); }, 500);
	
}


$(document).ready(function(){

	var u = window.top.location.href;
	
	if(u.indexOf('action=edit')==-1){
		$("#dictionary").css("display","none");
		var btn = '<a id="addElement" href="#" onClick="showEditFormulaire();" class="btn btn-success">';
		btn += 'Créer un espace client</a><br><br>';
		$("#dictionary").parent().prepend(btn);
	}
	
	$("#dictionary_listOfUsers").parent().parent().css("display","none");
	
});

function showEditFormulaire(){
	$("#dictionary").css("display","");
	$("#addElement").css("display","none");
}



