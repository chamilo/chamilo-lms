<?php
/* 		INIT SECTION	*/
$language_file= array('messages','userInfo', 'admin');
$cidReset	= true;
require_once '../../../main/inc/global.inc.php';
require_once 'ticket.class.php';
require_once api_get_path(LIBRARY_PATH).'mail.lib.inc.php';

api_block_anonymous_users();

require_once api_get_path(LIBRARY_PATH).'formvalidator/FormValidator.class.php';
require_once api_get_path(LIBRARY_PATH).'group_portal_manager.lib.php';

$nameTools = api_xml_http_response_encode(get_lang('Soporte Virtual'));


$htmlHeadXtra[]='
<script language="javascript">
$(document).ready(function(){
		if(document.getElementById("divEmail")){
			document.getElementById("divEmail").style.display="none";
		}
});
function changeType() {
var selected = document.getElementById("category_id").selectedIndex;
var id = document.getElementById("category_id").options[selected].value  ;
	document.getElementById("project_id").value= projects[id];
	document.getElementById("other_area").value= other_area[id];
	document.getElementById("email").value= email[id];
	document.getElementById("divEmail").style.display="none";
	if(parseInt(course_required[id]) == 0){
		document.getElementById("divCourse").style.display="none";		
		if( id != "CUR"){
			document.getElementById("divEmail").style.display="";
			document.getElementById("personal_email").required="required";	
		}			
		document.getElementById("course_id").disabled=true;	
		document.getElementById("course_id").value=0;			
	}else{	
		document.getElementById("divCourse").style.display = "";
		document.getElementById("course_id").disabled=false;	
		document.getElementById("course_id").value=0;
		document.getElementById("personal_email").value="";		
	}
}

function validate() {	
	fckEditor1val = FCKeditorAPI.__Instances["content"].GetHTML();	
	document.getElementById("content").value= fckEditor1val;
	var selected = document.getElementById("category_id").selectedIndex;
	var re  = /^([a-zA-Z0-9_.-])+@(([a-zA-Z0-9-])+.)+([a-zA-Z0-9]{2,4})+$/; 
	var id = document.getElementById("category_id").options[selected].value  ;
	if( id == 0){
		alert("Debe seleccionar un tipo");
		return false;		
	}else if(document.getElementById("subject").value == ""){
		alert("Debe escribir un asunto");
		return false;
	}else if(parseInt(course_required[id]) == 1 && document.getElementById("course_id").value == 0){
		alert("Debe elegir un curso");
		return false;
	}else if(id !="CUR" && parseInt(course_required[id]) != 1  && !re.test(document.getElementById("personal_email").value)){
		alert("Debe digitar un email valido");
		return false;
	}else if(fckEditor1val ==""){
		alert("Debe escribir un mensaje");
		return false;
	}
}

</script>';
$htmlHeadXtra[] = '<script type="text/javascript">

var counter_image = 1;
function remove_image_form(id_elem1) {
	var elem1 = document.getElementById(id_elem1);
	elem1.parentNode.removeChild(elem1);
	counter_image = counter_image - 1;
}
function add_image_form() {
	// Multiple filepaths for image form
	var filepaths = document.getElementById("filepaths");
	if (document.getElementById("filepath_"+counter_image)) {
		counter_image = counter_image + 1;
	}  else {
		counter_image = counter_image;
	}
	var elem1 = document.createElement("div");
	elem1.setAttribute("id","filepath_"+counter_image);
	filepaths.appendChild(elem1);
	id_elem1 = "filepath_"+counter_image;
	id_elem1 = "\'"+id_elem1+"\'";
	document.getElementById("filepath_"+counter_image).innerHTML = "<input type=\"file\" name=\"attach_"+counter_image+"\"  size=\"20\" />&nbsp;<a href=\"javascript:remove_image_form("+id_elem1+")\"><img src=\"'.api_get_path(WEB_CODE_PATH).'img/delete.gif\"></a>";
	//document.getElementById("filepath_"+counter_image).innerHTML = "<input type=\"file\" name=\"attach_"+counter_image+"\"  size=\"20\" />&nbsp;<input type=\"text\" name=\"legend[]\" size=\"20\" />";
	if (filepaths.childNodes.length == 6) {
		var link_attach = document.getElementById("link-more-attach");
		if (link_attach) {
			link_attach.innerHTML="";
		}
	}
}
function show_question(questionid){
	if(document.getElementById("C"+questionid)){
		if(document.getElementById("A"+questionid).style.display == "none"){
			document.getElementById("A"+questionid).style.display = ""; 
		}
		else if(document.getElementById("A"+questionid).style.display == ""){
			document.getElementById("A"+questionid).style.display = "none"; 
		}
	}	
}
</script>

<style>
div.row div.label2 {
	float:left;
	width:10%;
}
div.row div.formw2 {
    width:90%;
	float:left
}
 div.formulario {
    width:65%;
	float: right;
	border: 1px;
	border-color: #000000;
	border-left-style: solid;
	padding-left :20px;
}
div.faqs {
    width: 33%;
	float: left;
}
</style>';
$types = TicketManager::get_all_tickets_categories();
$htmlHeadXtra[] = '<script language="javascript">
		var projects = '.js_array($types,'projects','project_id'). '
		var course_required = '.js_array($types,'course_required','course_required').'
		var other_area = '.js_array($types,'other_area','other_area').'
		var email = '.js_array($types,'email','email').'		
		document.getElementById("divCourse").style.display="none";	
		 </script>';
$htmlHeadXtra[] = '<script src="'.api_get_path(WEB_LIBRARY_PATH).'javascript/tag/jquery.fcbkcomplete.js" type="text/javascript" language="javascript"></script>';
$htmlHeadXtra[] = '<link  href="'.api_get_path(WEB_LIBRARY_PATH).'javascript/tag/style.css" rel="stylesheet" type="text/css" />';
$htmlHeadXtra[] ='
<!-- ################## FACEBOOK ################## INI -->
<script>
$(document).ready(function(){
$("#likebox_right").hover(function(){ jQuery(this).stop(true,false).animate({right: 0}, 500); }, function(){ jQuery("#likebox_right").stop(true,false).animate({right: -305},500); });
});

var r={"allowspace":/[^\w|\s|\s|\-|\*]/g}
function valid(o,w){
o.value = o.value.replace(r[w],"");
}
</script>
<style>
#likebox_div{ width:300px; height:400px; overflow:hidden; }
#likebox_right{ z-index:10005; border:2px solid #3c95d9; background-color:#fff; width:300px; height:400px; position:fixed; right:-305px; }
#likebox_right img{ position:absolute; top:120px; left:-35px; }
#likebox_right iframe{ border:0px solid #3c95d9; overflow:hidden; position:static; height:400px; left:-2px; top:-3px; }
</style>

<div id="likebox_right" style="top: 17%;">
<div id="likebox_div">
<img alt="" src="'.api_get_path(WEB_IMG_PATH).'btn_face.png">
<iframe src="http://www.facebook.com/plugins/likebox.php?href=https%3A%2F%2Fwww.facebook.com%2FUSILVIRTUAL&amp;width=300&amp;height=400&amp;show_faces=true&amp;colorscheme=light&amp;stream=true&amp;show_border=true&amp;header=true" scrolling="no" frameborder="0" style="border:none; overflow:hidden; width:300px; height:400px;" allowTransparency="true"></iframe>
</div>
</div>
<!-- ################## FACEBOOK ################## FIN -->
';

function js_str($s) {
  return '"'.addcslashes($s, "\0..\37\"\\").'"';
}

function js_array($array,$name,$key) {
  $temp=array();
  $return = "new Array(); ";
  foreach ($array as $value){
    $return .= $name."['".$value['category_id']."'] ='".$value[$key]."'; ";
  }
  return $return;
}

function show_form_send_ticket(){
	global $types;
	$courses_list = CourseManager::get_courses_list_by_user_id(api_get_user_id(),false,true);
	echo '<form enctype="multipart/form-data" action="'.api_get_self().'" method="post" name="send_ticket" id="send_ticket"
 	onsubmit="return validate()" style="width:100%">';
	$select_types = '<div class="row">
	<div class="label2">Tipo:</div>
       <div class="formw2">';
	$select_types .= '<select style="width: 95%; "   name = "category_id" id="category_id" onChange="changeType();">';
	$select_types .= '<option value="0">---Seleccionar---</option>';
	foreach ($types as $type) {
		$select_types.= "<option value = '".$type['category_id']."'>".$type['name'].":  <br/>".$type['description']."</option>";
	}
	$select_types .= "</select>";
	$select_types .= '</div></div>';
	echo $select_types;
	$select_course = '<div class="row" id="divCourse" >
	<div class="label2"  >Curso:</div>
            <div class="formw2">';
	$select_course .= '<select  class="chzn-select" name = "course_id" id="course_id"  style="width: 56%; display:none;">';
	$select_course .= '<option value="0">---Seleccionar---</option>';
	foreach ($courses_list as $course) {
		$select_course.= "<option value = '".$course['course_id']."'>".$course['title']."</option>";
	}
	$select_course .= "</select>";
	$select_course .= '</div></div>';
	echo $select_course;
	echo '<div class="row" ><div class ="label2">Asunto:</div>
       		<div class="formw2"><input type = "text" id ="subject" name="subject" value="" required ="" style="width:94%"/></div>
		  </div>';
	echo '<div class="row" id="divEmail" ><div class ="label2">Email Personal:</div>
       		<div class="formw2"><input type = "email" id ="personal_email" name="personal_email" value=""  style="width:94%"/></div>
		  </div>';
	echo '<input name="project_id" id="project_id" type="hidden" value="">';
	echo '<input name="other_area" id="other_area" type="hidden" value="">';
	echo '<input name="email" id="email" type="hidden" value="">';
	echo '<div class="row">
		<div class="label2">mensaje</div>
		<div class="formw2">
			<input type="hidden" id="content" name="content" value="" style="display:none">
		<input type="hidden" id="content___Config" value="ToolbarSet=Messages&amp;Width=95%25&amp;Height=250&amp;ToolbarSets={ %22Messages%22: [  [ %22Bold%22,%22Italic%22,%22-%22,%22InsertOrderedList%22,%22InsertUnorderedList%22,%22Link%22,%22RemoveLink%22 ] ], %22MessagesMaximized%22: [  ] }&amp;LoadPlugin=[%22customizations%22]&amp;EditorAreaStyles=body { background: #ffffff; }&amp;ToolbarStartExpanded=false&amp;CustomConfigurationsPath=/main/inc/lib/fckeditor/myconfig.js&amp;EditorAreaCSS=/main/css/chamilo/default.css&amp;ToolbarComboPreviewCSS=/main/css/chamilo/default.css&amp;DefaultLanguage=es&amp;ContentLangDirection=ltr&amp;AdvancedFileManager=true&amp;BaseHref='.api_get_path(WEB_PATH).'main/Support/&amp;&amp;UserIsCourseAdmin=true&amp;UserIsPlatformAdmin=true" style="display:none">
		<iframe id="content___Frame" src="/main/inc/lib/fckeditor/editor/fckeditor.html?InstanceName=content&amp;Toolbar=Messages" width="95%" height="250" frameborder="0" scrolling="no" style="margin: 0px; padding: 0px; border: 0px; background-color: transparent; background-image: none; width: 95%; height: 250px;">
		</iframe>
		</div>
	</div>
';
	echo '<div class="row" ><div class ="label2">Tel&eacute;fono (opcional):</div>
       		<div class="formw2"><input type = "text" id ="telefono" name="telefono" value="" onkeyup="valid(this,'."'allowspace'".')" onblur="valid(this,'."'allowspace'".')" style="width:94%"/></div>
		  </div>';
	echo '<div class="row">
		<div class="label2">'.get_lang('FilesAttachment').'</div>
		<div class="formw2">
				<span id="filepaths">
				<div id="filepath_1">
					<input type="file" name="attach_1" id="attach_1"  size="20" style="width:94%;"/>
				</div></span>
		</div>
	</div>';
	echo '<div class="row">
		<div class="formw2">
			<span id="link-more-attach">
				<a href="javascript://" onclick="return add_image_form()">'.get_lang('AddOneMoreFile').'</a></span>&nbsp;
					('.sprintf(get_lang('MaximunFileSizeX'),format_file_size(api_get_setting('message_max_upload_filesize'))).')
			</div>
		</div>';
	echo '<div class="row">
		<div class="label2">
		</div>
		<div class="formw2">	<button class="save" name="compose" type="submit">Enviar mensaje</button>
		</div>
	</div>';
}
function save_ticket(){
	$category_id	=	$_POST['category_id'];
	$content		=	$_POST['content'];
	if ($_POST['telefono']!="")	$content.=	'<p style="color:red">&nbsp;Tel&eacute;fono: '.$_POST['telefono'].'</p>';
	$course_id		=	$_POST['course_id'];
	$project_id		=	$_POST['project_id'];
	$subject		=	$_POST['subject'];
	$other_area		=	(int)$_POST['other_area'];
	$email			=	$_POST['email'];
	$personal_email	= $_POST['personal_email'];
	$file_attachments =	$_FILES;
	if(TicketManager::insert_new_ticket($category_id, $course_id, $project_id, $other_area, $email, $subject, $content,$personal_email, $file_attachments)){
		header('location:'.api_get_path(WEB_PATH).'main/support/myticket.php?message=success');
	}else{
		Display::display_header(get_lang('ComposeMessage'));
		Display::display_error_message("No se pudo registrar su ticket");
	}
}
if(!isset($_POST['compose'])){
	Display::display_header(get_lang('ComposeMessage'));
	echo '<div class="formulario">';
	show_form_send_ticket();	
	echo '</div>';
	echo '<div class="faqs">';
	echo '<h1><center>Preguntas Frecuentes</center></h1>';
	echo '<table width="100%" height="100%" >';
		echo '<tr>';
			echo '<td id="C1"><a href="javascript:show_question(1)" Title="Click Aqui"><u><b><h3>1. No puedo ingresar a mi Plataforma de Evaluaciones en L&iacute;nea </p></b></h3></a></td>';
		echo '</tr>';
		echo '<tr>';
			echo '<td id="A1" style="display:none;align:justify;">
					<ul>
						<li>Recuerde que sus datos de acceso son los mismos que los de INFOSIL y por ende los mismos que del Campus Virtual.</li>
						<li>Si no puede ingresar con los mismos datos de INFOSIL, genere un ticket enviando su contrase&ntilde;a de infosil.</li>
					</ul>
					
				  </td>';
		echo '</tr>';
		echo '<tr>';
			echo '<td id="C2"><a href="javascript:show_question(2)" Title="Click Aqui"><u><b><h3>2. No visualizo un documento dentro del Itinerario de Aprendizaje</p></b></h3></a></td>';
		echo '</tr>';
		echo '<tr>';
			echo '<td id="A2" style="display:none;align:justify;">
					<ul>
						<li>Compruebe que su navegador web sea Mozilla Firefox y se encuentre actualizado.</li>
						<li>Genere un ticket y adjunte el  print de pantalla donde se visualice el error- por favor guarde esta imagen en formato JPG.</li>
					</ul>
				  </td>';
		echo '</tr>';
		echo '<tr>';
			echo '<td id="C3"><a href="javascript:show_question(3)" Title="Click Aqui"><u><b><h3>3.	No puedo participar en el Foro. No visualizo la participaci&oacute;n realizada en el Foro.</p></b></h3></a></td>';
		echo '</tr>';
		echo '<tr>';
			echo '<td id="A3" style="display:none;align:justify;">
					<ul>
						<li>Verifique con su docente que la fecha y hora de participaci&oacute;n en el foro no haya vencido.</li>
						<li>Recuerde que Ud. no puede borrar sus participaciones realizadas en el foro y tampoco podr&aacute; hacerlo el equipo de soporte virtual, sin autorizaci&oacute;n previa del docente o tutor del curso.</li>
						<li>Cuando responda el Foro, si previamente redact&oacute; su participaci&oacute;n en un Word, c&oacute;pielo a un block de notas para que no se copien caracteres extra&ntilde;os y exista la posibilidad de error, las im&aacute;genes dentro del foro se adjuntan mediante el bot&oacute;n de subir imagen, nunca copie directamente desde su documento o desde internet.</li>
					</ul>
				  </td>';
		echo '</tr>';
		echo '<tr>';
			echo '<td id="C4"><a href="javascript:show_question(4)" Title="Click Aqui"><u><b><h3>4.	No puedo ver los videos del Itinerario de Aprendizaje</p></b></h2></a></td>';
		echo '</tr>';
		echo '</tr>';
		echo '<tr>';
			echo '<td id="A4" style="display:none;align:justify;">
					<ul>
						<li>Instale el Adobe Flash Player y el Java Player (versi&oacute;n10)
						<br/><a href="http://www.adobe.com/software/flash/about/" target="_new">Descargar Adobe Flash Player Aqu&iacute;</a>
						<br/><a href="http://www.java.com/es/download/index.jsp" target="_new">Descargar Java Player Aqu&iacute;</a>
					</li>
						</li>
						<li>Elimine los cookies y temporales de su PC <br/><a href="http://campusvirtual.usil.edu.pe/home/content/HowToDeleteCacheCookies_updateJUL2012.pdf" target="_new">(ver manual)</a></li>					
					</ul>
				  </td>';
		echo '</tr>';
		echo '<tr>';
		echo '<td ><h2><u>OTRAS CONSULTAS</u></h2></td>';
		echo '</tr>';
		echo '<tr>';
		echo '<td id="C5"><a href="javascript:show_question(5)" Title="Click Aqui"><u><b><h3>No puedo acceder al INFOSIL</p></b></h3></a></td>';
		echo '<tr>';
		echo '<td id="A5" style="display:none;align:justify;">
					<ul>
						<li>Cont&aacute;ctese con <a href="mailto:helpdesk@usil.edu.pe"> helpdesk@usil.edu.pe</a> 
							El personal de soporte virtual s&oacute;lo podr&aacute; ayudarlo en problemas referentes al
							<b>Campus Virtual USIL.</b>
						</li>
					</ul>
				  </td>';
		echo '</tr>';
		echo '<tr>';
		echo '<td id="C6"><a href="javascript:show_question(6)" Title="Click Aqui"><u><b><h3>No tengo acceso al Correo USIL</p></b></h3></a></td>';
		echo '<tr>';
		echo '<td id="A6" style="display:none;align:justify;">
					<ul>
						<li>Cont&aacute;ctese con <a href="mailto:helpdesk@usil.edu.pe"> helpdesk@usil.edu.pe</a> 
							El personal de soporte virtual s&oacute;lo podr&aacute; ayudarlo en problemas referentes al
							<b>Campus Virtual USIL.</b>
						</li>
					</ul>
				  </td>';
		echo '</tr>';
	echo '</table>';
	echo '</div>';
}else{
	save_ticket();
}
Display::display_footer();
