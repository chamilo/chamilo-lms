<?php
/*
==============================================================================
		INIT SECTION
==============================================================================
*/
// name of the language file that needs to be included
$language_file = 'registration';
$cidReset = true;

require ('../inc/global.inc.php');
$this_section = SECTION_MYPROFILE;
$_SESSION['this_section']=$this_section;

api_block_anonymous_users();

$htmlHeadXtra[] = '<script type="text/javascript">
function confirmation(name)
{
	if (confirm("'.get_lang('AreYouSureToDelete').' " + name + " ?"))
		{return true;}
	else
		{return false;}
}
		
function show_image(image,width,height) {
	width = parseInt(width) + 20;
	height = parseInt(height) + 20;			
	window_x = window.open(image,\'windowX\',\'width=\'+ width + \', height=\'+ height + \'\');
		
}
				
</script>';
$htmlHeadXtra[] = '<script src="../inc/lib/javascript/jquery.js" type="text/javascript" language="javascript"></script>'; //jQuery
	$htmlHeadXtra[] ='<script type="text/javascript">
	$(document).ready(function(){
	$("input").bind("click", function() {
	   id_button=$(this).attr("id");
if (id_button=="tab1" || id_button=="tab2" || id_button=="tab3" || id_button=="tab4") {
				$.ajax({
							contentType: "application/x-www-form-urlencoded",
							beforeSend: function(objeto) {
							$("#div_response_tab").html("Cargando..."); },
							type: "POST",
							url: "../auth/load_content_tab.php",
							data: "option_tab="+id_button,
							success: function(datos) {
							// $("div#"+name_div_id).hide();
							 $("#div_response_tab").html(datos);
///////////////////////////////////////////////////////////////////////////////
			$("input").bind("click", function() {
	   name_button=$(this).attr("id");
	   name_div_id="id_"+name_button.substring(13,14);
	   user_id=name_div_id.split("_");
	   user_friend_id=user_id[1];

					$.ajax({
							contentType: "application/x-www-form-urlencoded",
							beforeSend: function(objeto) {
							$("#id_response").html("Cargando..."); },
							type: "POST",
							url: "../auth/register_friend.php",
							data: "friend_id="+user_friend_id,
							success: function(datos) {
							 $("div#"+name_div_id).hide();
							 $("#id_response").html(datos);
							},
						});
	});
////////////////////////////////////////////////////////////////////////////////////////////////////////
							 		
							},
						});
					} 
										
				});				

	});
	</script>';	

/*if (api_get_setting('allow_message_tool')=='true') {
	$htmlHeadXtra[] ='<script type="text/javascript">
	$(document).ready(function(){
		$(".message-content .message-delete").click(function(){
			$(this).parents(".message-content").animate({ opacity: "hide" }, "slow");
			
			$(".message-view").animate({ opacity: "show" }, "slow");
		});				
		
	});
	</script>';	
}*/
Display :: display_header(get_lang('ModifyProfile'));
?>
<table width="100%" border="1" cellpadding="0" cellspacing="0">
  <tr>
    <td width="10%" height="20" valign="top"><div><input class="tab-table" type="button" class="button" name="tab1" id="tab1"  value="Invitaciones" /></div></td>
	<td width="10%" height="20" valign="top"><div><input class="tab-table" type="button" class="button" name="tab2" id="tab2"  value="Amigos" /></div></td>
	<td width="10%" height="20" valign="top"><div><input class="tab-table" type="button" class="button" name="tab3" id="tab3"  value="Grupos" /></div></td>
	<td width="70%" height="20" valign="top"><div><input class="tab-table" type="button" class="button" name="tab4" id="tab4"  value="Datos Personales" /></div></td>
    </tr>
 </table>
 <table width="100%" border="1" cellpadding="0" cellspacing="0" height="95%">
  <tr>
    <td width="100%" height="20" valign="top"><div id="div_response_tab"></div></td>
    </tr>
 </table>
<?php
Display :: display_footer();
?>