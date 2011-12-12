<?php

// name of the language file that needs to be included
$language_file = array('admin','events');
$cidReset = true;
require_once '../inc/global.inc.php';
$this_section = SECTION_PLATFORM_ADMIN;
api_protect_admin_script();

$interbreadcrumb[] = array ('url' => 'index.php', 'name' => get_lang('PlatformAdmin'));
$tool_name = get_lang('EventsTitle');

$action = isset($_POST['action'])?$_POST['action']:null;
$eventId = isset($_POST['eventId'])?$_POST['eventId']:null;
$eventUsers = isset($_POST['eventUsers'])?$_POST['eventUsers']:null;
$eventMessage = isset($_POST['eventMessage'])?$_POST['eventMessage']:null;
$eventSubject = isset($_POST['eventSubject'])?$_POST['eventSubject']:null;

if($action == 'modEventType') {
	if($eventUsers) {
		$users = explode(';',$eventUsers);
	}
	else {
		$users = array();
	}
	
	eventType_mod($eventId,$users,$eventMessage,$eventSubject);
	// echo mysql_error();
	header('location: event_type.php');
	exit;
}

$ets = eventType_getAll();


$ajaxPath = api_get_path(WEB_CODE_PATH).'inc/ajax/events.ajax.php';
$htmlHeadXtra[] = '<script src="../inc/lib/javascript/jquery.js" type="text/javascript" language="javascript"></script>';

Display::display_header($tool_name);

?>

<script language="javascript">
	var usersList;
	var eventTypes = <?php print json_encode($ets) ?>;

	$(document).ready(function(){
		ajax({action:"getUsers"},function(data) {
				usersList = data;
			}
		);
		
		// ajax({action:"getEventTypes"},function(data) {
				// eventTypes = data;
				// showEventTypes(data);
			// }
		// );
	});

	function ajax(params,func) {
		$.ajax({
				url: "<?php echo $ajaxPath ?>",
				type: "POST",
				data: params,
				success: func
			}
		);
	}
	
	function refreshUsersList() {
		removeAllOption($('#usersList'));
		$.each(usersList,function(ind,item) {
				addOption($('#usersList'),item.user_id,item.firstname + ' '+item.lastname);
			}
		);
	}
	
	// function showEventTypes(data) {
		// $.each(data,function(ind,item) {
				// addOption($('#eventList'),item.id,item.name);
			// }
		// );
	// }
	
	function getCurrentEventTypeInd() {
		var ind=false;
		$.each(eventTypes,function(i,item)
			{
				if(item.id == $('#eventList option:selected').first().attr('value')) {
					ind=i;
					return false;
				}
			}	
		)
		
		return ind;
	}
	
	function showEventType() {
		eInd = getCurrentEventTypeInd();
		
		$('#eventId').attr('value',eventTypes[eInd].id);
		$('#eventName').attr('value',eventTypes[eInd].name);
		$('#eventNameTitle').text(eventTypes[eInd].nameLangVar);
		$('#eventMessage').text(eventTypes[eInd].message);
		$('#eventSubject').attr('value',eventTypes[eInd].subject);
		$('#descLangVar').text(eventTypes[eInd].descLangVar);
				
		ajax({action:"getEventTypeUsers","id":eventTypes[eInd].id},function(data) {
				removeAllOption($('#usersSubList'));
				
				refreshUsersList();
				
				usersIds = new Array();
				
				$.each(data,function(ind,item) {
					addOption($('#usersSubList'),item.user_id,item.firstname + ' '+item.lastname);
					usersIds[ind] = item.value;
					removeOption($('#usersList'),item.user_id);
				});
				
				$('#eventUsers').attr('value',usersIds.join(';'));
			}
		);
	}
	
	function submitForm() {
		if($('#eventId')) {
			usersIds = new Array();
			
			$('#usersSubList option').each(function(ind,item)
				{
					usersIds[ind] = item.value;
				}
			);
			
			$('#eventUsers').attr('value',usersIds.join(';'));
			
			return true;
		}
		
		return false;
	}
	
	function addOption(select,value,text) {
		select.append('<option value="'+value+'">'+text+'</option>');
	}
	
	function removeOption(select,value) {
		select.find('option[value='+value+']').remove();
	}
	
	function removeAllOption(select) {
		select.find('option').remove();
	}
	
	function moveUsers(src,dest) {
		src.find('option:selected').each(function(index,opt) {
			text = opt.text;
			val = opt.value;
			
			addOption(dest,val,text);
			removeOption(src,val);
		});
	}
</script>

<h3><?php print get_lang('EventsTitle') ?></h3>

<table id="" width="90%">
	<tr>
		<td width="5%">
			<h4><?php print get_lang('EventsListTitle'); ?></h4>
		</td>
		<td width="5%">
			<h4><?php print get_lang('EventsUserListTile'); ?></h4>
		</td>
		<td width="5%">
			&nbsp;
		</td>
		<td width="5%">
			<h4><?php print get_lang('EventsUserSubListTile'); ?></h4>
		</td>
	</tr>
	<tr>
		<td>
			<select multiple="1" id="eventList" onChange="showEventType()">
				<?php
					
					foreach($ets as $et) {
						print '<option value="'.$et['id'].'">'.$et['nameLangVar'].'</option>';
					}
					
				?>
			</select>
		</td>
		<td>
			<select multiple="1" id="usersList"></select>
		</td>
		<td valign="middle">
			<button class="arrowr" onclick='moveUsers($("#usersList"),$("#usersSubList")); return false;'></button>
			<br />
			<button class="arrowl" onclick='moveUsers($("#usersSubList"),$("#usersList")); return false;'></button>
		</td>
		<td>
			<select multiple="1" id="usersSubList"></select>
		</td>
	</tr>
</table>

<br />

<h2 id="eventNameTitle"></h2>

<form method="POST" onSubmit="return submitForm(); ">
	<input type="hidden" name="action" value="modEventType" />
	<input type="hidden" name="eventId" id="eventId" />
	<input type="hidden" name="eventUsers" id="eventUsers" />
	<input type="hidden" id="eventName" />
	
	<br />
	
	<div id="descLangVar">
	</div>
	<br />
	
	<label for="eventSubject"><h4><?php print get_lang('EventsLabelSubject'); ?></h4></label>
	<input type="text" id="eventSubject" name="eventSubject" />
	<br /><br />
	<label for="eventMessage"><h4><?php print get_lang('EventsLabelMessage'); ?></h4></label>
	<textarea cols="100" rows="10" name="eventMessage" id="eventMessage">
	
	</textarea>

<br /><br />

<input type="submit" value="<?php print get_lang('EventsButtonMod'); ?>" />

</form>


<?php

Display :: display_footer();

?>
