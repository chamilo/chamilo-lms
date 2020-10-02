
$(document).ready(function(){

	displayTuilles("dictionary_imageUrl","none");
	displayTuilles("dictionary_imagePic","none");

	var u = window.top.location.href;
	if(u.indexOf('action=edit')==-1&&u.indexOf('action=add')==-1){
		
		$("#dictionary").css("display","none");
		
		var btn = '';
		
		var numRnd = Math.floor(Math.random() * 6) + 1;
		
		if(p_boost_url==1||p_boost_url=='1'){p_boost_url = '';}

		btn += '<iframe id="frameoverview" src="params/speed'+p_boost_url+'view.html?v='+ numRnd +'" width="99%" height="240px"></iframe>';
		btn += '<iframe id="frameoverview2" src="params/speed'+p_boost_url+'view2.html?v=a'+ numRnd +'" width="99%" height="240px" ></iframe>';
		
		btn += '<a id="addElement" href="edit-title.php?action=add" class="btn btn-success">+</a>&nbsp;&nbsp;';
		btn += '<a id="btnoff" onClick="switchOffOnIframe()" class="btn btn-success" >OFFLINE</a><br>';
		
		$("#actions").after(btn);
		
		$("#frameoverview2").css("display","none");
		 
		$.ajax({
			url:"ajax-render.php",
			cache:true
		}).done(function(html){
			setTimeout(function(){refreshRender();},200);
		});
		
	}else{
		$("#dictionary").css("display","block");
	}
	
	
});

var switchOffOnI = true;

function switchOffOnIframe(){
	
	if(switchOffOnI){
		switchOffOnI = false;
		$("#frameoverview2").css("display","");
		$("#frameoverview").css("display","none");
		$("#btnoff").html("ONLINE");
		$("#btnoff").css("background","#AC58FA");
	}else{
		switchOffOnI = true;
		$("#frameoverview").css("display","");
		$("#frameoverview2").css("display","none");
		$("#btnoff").html("OFFLINE");
		$("#btnoff").css("background","#5cb85c");
	}
	
}

function showEditShowCards(){

	var tpl = "localhost";
	if(document.getElementById("folder-tpl")){
		tpl = $("#folder-tpl").text();	
	}
	
	var title = getbyelem("dictionary_title");
	$("#o-title").html(title);
	
	var subTitle = getbyelem("dictionary_subTitle");
	$("#o-subtitle").html(subTitle);
	
	displayImgP();
	setTimeout(function(){showEditShowCards();},500);
	
	$("#o-subtitle").html(subTitle);
	
	showDeployShowCards();
	var u = window.top.location.href;
	if(u.indexOf('action=edit')!=-1){
		$(".dataTables_wrapper").css("display","none");
	}
	if(u.indexOf('action=add')!=-1){
		$(".dataTables_wrapper").css("display","none");
	}

}

function displayImgP(){
	var ImgUrl = $("#dictionary_imageUrl").val();
	if(ImgUrl==''){
		selectImgP('defaut.jpg');
	}else{
		$("#o-backimg").css( "background-image","url(" + ImgUrl + ")");
	}
}

function selectImgP(backimg){

	$("#dictionary_imagePic").val(backimg);
	var tpl = "localhost";
	if(document.getElementById("folder-tpl")){
		tpl = $("#folder-tpl").text();	
	}
	var ImgUrl = "resources/templates/" + tpl + "/img/" + backimg;
	$("#dictionary_imageUrl").val(ImgUrl);
	displayImgP();

}

var OldIdVideo = '';
var OldContent = '';

function showDeployShowCards(){
	
	var tpl = "localhost";
	if(document.getElementById("folder-tpl")){
		tpl = $("#folder-tpl").text();
	}

	$(".thecard").css('display','block').css('margin-top','2px');
	
	displayTuilles("dictionary_idContent","block");
	displayTuilles("dictionary_title","block");
	displayTuilles("dictionary_subTitle","block");
	displayTuilles("dictionary_acces","block");
	displayTuilles("dictionary_indexTitle","none");
	displayTuilles("leftContent","none");
	displayTuilles("rightContent","none");
	$(".theCardViewOver").css("display","none");

	var dt = getbyelem('dictionary_typeCard');
	var labelBottom = $("label[for='dictionary_idContent']");
	
	if(dt=='link'){
		labelBottom.html("Link");
		displayTuilles("dictionary_idContent","block");
	}
	
	if(dt=='video'){

		var IdVideo = $("#dictionary_idContent").val();
		labelBottom.html("Id&nbsp;youtube<br>Mp4&nbsp;url");
		
		OldContent = "";
		
		if(IdVideo.indexOf('youtube')!=-1&&IdVideo.indexOf('http')!=-1){
			IdVideo = IdVideo.replace();
			IdVideo = IdVideo.replace('http://www.youtube.com/watch?v=','');
			IdVideo = IdVideo.replace('https://www.youtube.com/watch?v=','');
			IdVideo = IdVideo.replace('https://youtu.be/','');
			IdVideo = IdVideo.replace('https://www.youtube.com/','');
			IdVideo = IdVideo.replace('http://www.youtube.com/','');
			IdVideo = IdVideo.replace('/','');
			IdVideo = IdVideo.replace(' ','');
			IdVideo = IdVideo.replace(' ','');
			IdVideo = IdVideo.replace('watch?v=','');
			IdVideo = IdVideo.replace('/','');
			IdVideo = IdVideo.replace(' ','');
			IdVideo = IdVideo.replace(' ','');
		}
		
		if(IdVideo!=''&&OldIdVideo!=IdVideo){
			OldIdVideo = IdVideo;
			$('.theCardViewOver').html(loadVideoNoAutoplay(IdVideo));
		}else{
			if(IdVideo==""){
				$('.theCardViewOver').html("");
			}
		}
		$(".theCardViewOver").css("display","block");
	}
	
	if(dt=='texthtml'){
		displayTuilles("dictionary_idContent","none");
		displayTuilles("leftContent","block");
		displayTuilles("rightContent","block");
	}
	
	if(dt.indexOf('loadpagecontent@')!=-1){
		displayTuilles("dictionary_acces","none");
		displayTuilles("dictionary_idContent","none");
		displayTuilles("picture","none");
		$('.theCardViewOver').css("display","none");
	}

		
	if(dt=='cards'){

		var title = $("#dictionary_title").val();

		if(title==''){
			$("#dictionary_title").val('CARDS');
		}

		displayTuilles("dictionary_title","none");
		displayTuilles("dictionary_subTitle","none");
		displayTuilles("dictionary_acces","none");
		displayTuilles("dictionary_idContent","none");
		displayTuilles("picture","none");
		$('.theCardViewOver').css("display","none");
		$("#o-backimg").css( "background-image","url(resources/img/cards.jpg)");
	}

	if(dt=='catalog'){

		var title = $("#dictionary_title").val();

		if(title==''){
			$("#dictionary_title").val('CATALOG');
		}

		displayTuilles("dictionary_title","none");
		displayTuilles("dictionary_subTitle","none");
		displayTuilles("dictionary_acces","none");
		displayTuilles("dictionary_idContent","none");
		displayTuilles("picture","none");
		$('.theCardViewOver').css("display","none");
		$("#o-backimg").css( "background-image","url(resources/img/catalog.jpg)");
	}

	if(dt=='stats'||dt=='statstable'){
		$('.theCardViewOver').css("display","none");
		displayTuilles("dictionary_idContent","none");
		displayTuilles("picture","none");
	}

	if(dt.indexOf('.html')!=-1){
		displayTuilles("dictionary_idContent","none");
		$(".theCardViewOver").css("display","block");
		displayTuilles("picture","none");
	}
	
	if(OldContent!=dt&&dt.indexOf('.html')!=-1){
		
		OldContent = dt;
		OldIdVideo = "";

		var urlplug = $('#plugfullpath').html();
		var urlLoad = urlplug + "templates/" + tpl + "/contents/" + dt;
		
		$.ajax({
			url:urlLoad,
			cache:true
		}).done(function(codeHtml){
			codeHtml = codeHtml.replace(/{urlplug}/g,urlplug);
			codeHtml = codeHtml.replace(/{onClick}/g,"dataClick");
			$('.theCardViewOver').html(codeHtml);
			showCircliful();
		});
		
	}
	
}

function displayTuilles(idstr,disp){

	var objParent = $("#"+idstr).parent().parent();
	if(objParent.hasClass('form-group')){
		objParent.css("display",disp);
	}

}

function displayTuilles2(idstr,disp){
	
	var objParent = $("#"+idstr).parent().parent().parent();
	if(objParent.hasClass('form-group')){
		objParent.css("display",disp);
	}
	objParent = $("#"+idstr).parent().parent();
	if(objParent.hasClass('form-group')){
		objParent.css("display",disp);
	}

}

setTimeout(function(){showEditShowCards();matchInput();},500);

function matchInput(){

	var dicoit = getbyelem('dictionary_indexTitle');
	$('#rel_indexTitle').val(dicoit);

}

function matchOuput(){

	var dicoit = getbyelem('rel_indexTitle');
	$('#dictionary_indexTitle').val(dicoit);

}

function getbyelem(n){
	
	if(document.getElementById(n)){
	
		var tagName = document.getElementById(n).tagName;
		
		if(tagName=='SELECT'){
			var get_id = document.getElementById(n);
			var resultselect = get_id.options[get_id.selectedIndex].value;
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

function refreshRender(){

	if(p_boost_url==1||p_boost_url=='1'){p_boost_url = '';}

	var numRnd = Math.floor(Math.random() * 7) + 1;
	$("#frameoverview").attr("src","params/speed"+p_boost_url+"view.html?v=2" + numRnd);
	$("#frameoverview2").attr("src","params/speed"+p_boost_url+"view2.html?v=2" + numRnd);
}

function showEditFormulaire(){
	$("#dictionary").css("display","");
	$("#addElement").css("display","none");
	$("#frameoverview").css("display","none");
}

function showOverviewSelect(){
	$(".imagesOverviewSelect").css("display","block");
}
function closeOverviewSelect(){
	$(".imagesOverviewSelect").css("display","none");
}
