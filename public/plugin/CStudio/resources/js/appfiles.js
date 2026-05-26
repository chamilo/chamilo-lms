
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

$(document).ready(function(){

	var u = window.top.location.href;
	
	if(u.indexOf('action=edit')==-1){
	
		$("#dictionary").css("display","none");
		var btn = '<a id="addElement" href="edit.php" class="btn btn-success">';
		btn += 'Retour</a>&nbsp;-&nbsp;';
		
		btn += '<a id="addElement" href="#" onClick="showEditFormulaire();" class="btn btn-success">';
		btn += 'Add a file</a>';
		
		btn += '<br><br>';
		
		$("#dictionary").parent().prepend(btn);
	
	}
	
	$("#dictionary_listOfUsers").parent().parent().css("display","none");
	
});

function showEditFormulaire(){
	$("#dictionary").css("display","");
	$("#addElement").css("display","none");
}



