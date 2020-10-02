
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

function overviewByBase(){
	
	var term = getbyelem("dictionary_term");
	var srci = "pictures/" + term + '.jpg';
	var asrc = $("#overviewpicture").attr("src");
	if(srci!=asrc){
		$("#overviewpicture").attr("src",srci);
	}
	setTimeout(function(){overviewByBase();},500);
	
}

setTimeout(function(){overviewByBase();},500);

$(document).ready(function(){

	var u = window.top.location.href;
	if(u.indexOf('action=edit')==-1){
		$("#dictionary").css("display","none");
		var btn = '<a id="addElement" href="#" onClick="showEditFormulaire();" class="btn btn-success">Ajouter un élément</a><br>';
		$("#dictionary").parent().prepend(btn);
	}
	
});

function showEditFormulaire(){
	$("#dictionary").css("display","");
	$("#addElement").css("display","none");
}