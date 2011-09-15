<script type='text/javascript'>
$(document).ready(function() {
	
	var date = new Date();
	var d = date.getDate();
	var m = date.getMonth();
	var y = date.getFullYear();
	
	$( "#dialog-form" ).dialog({
		autoOpen: false,
		modal	: false, 
		width	: 500, 
		height	: 320
   	});
	
	var calendar = $('#calendar').fullCalendar({
		header: {
			left: '',
			center: 'title',
			right: 'today prev,next month,agendaWeek,agendaDay'
		},	
		selectable	: true,
		selectHelper: true,
		select: function(start, end, allDay, jsEvent, view) {	
			var start_date 	= Math.round(start.getTime() / 1000);
			var end_date 	= Math.round(end.getTime() / 1000);
			
			var url = '{$web_agenda_ajax_url}a=add_event&start='+start_date+'&end='+end_date+'&all_day='+allDay+'&view='+view.name;
			
			$('#start_date').html(start.getDate() +"/"+ start.getMonth() +"/"+start.getFullYear());
			$('#end_date').html('- '+ end.getDate() +"/"+ end.getMonth() +"/"+end.getFullYear());
			
			$("#dialog-form").dialog("open");

			
			
			$("#dialog-form").dialog({				
				buttons: {
					"Add event": function() {
						var params = $("#add_event_form").serialize();						
						$.ajax({
							url: url+'&'+params,
							success:function(data) {
								calendar.fullCalendar("refetchEvents");
								calendar.fullCalendar("rerenderEvents");
								$("#dialog-form").dialog("close");										
							}							
						});
					},
				},				
				close: function() {		
					$("#title").attr('value', '');
					$("#content").attr('value', '');					
				}
			});		
            //prevent the browser to follow the link
            return false;
			calendar.fullCalendar('unselect');
		},	
		eventRender: function(event, element) {
			if (event.description) {
				element.qtip({
		            content: event.description
		        });
			}
	        
	    },
		eventClick: function(calEvent, jsEvent, view) {						
			var start_date 	= Math.round(calEvent.start.getTime() / 1000);
			if (calEvent.allDay == 1) {				
				var end_date 	= '';				
			} else {			
				var end_date 	= Math.round(calEvent.end.getTime() / 1000);				
			}			
			
			$('#start_date').html(calEvent.start.getDate() +"/"+ calEvent.start.getMonth() +"/"+calEvent.start.getFullYear());
			
			if (end_date != '') {
				$('#end_date').html(calEvent.end.getDate() +"/"+ calEvent.end.getMonth() +"/"+calEvent.end.getFullYear());
			}			

			$("#title").attr('value', calEvent.title);
			$("#content").attr('value', calEvent.description);
			
			$("#dialog-form").dialog("open");

			var url = '{$web_agenda_ajax_url}a=edit_event&id='+calEvent.id+'&start='+start_date+'&end='+end_date+'&all_day='+calEvent.allDay+'&view='+view.name;
			var delete_url = '{$web_agenda_ajax_url}a=delete_event&id='+calEvent.id;
			
			$("#dialog-form").dialog({				
				buttons: {
					"Edit event" : function() {
						var params = $("#add_event_form").serialize();						
						$.ajax({
							url: url+'&'+params,
							success:function() {
								calEvent.title 			= $("#title").val();
								calEvent.start 			= calEvent.start;
								calEvent.end 			= calEvent.end;
								calEvent.allDay 		= calEvent.allDay;
								calEvent.description 	= $("#content").val();								
								calendar.fullCalendar('updateEvent', 
										calEvent,
										true // make the event "stick"
								);
								
								$("#dialog-form").dialog("close");										
							}							
						});
					},
					"Delete": function() {
						$.ajax({
							url: delete_url,
							success:function() {
								calendar.fullCalendar('removeEvents',										
									calEvent										
								);								
								calendar.fullCalendar("refetchEvents");
								calendar.fullCalendar("rerenderEvents");
								$("#dialog-form").dialog( "close" );		
							}
						});
						
						
					}
				},				
				close: function() {		
					$("#title").attr('value', '');
					$("#content").attr('value', '');				
				}
			});

		},
		editable: true,		
		events: "{$web_agenda_ajax_url}a=get_events",		
		eventDrop: function(event, day_delta, minute_delta, all_day, revert_func) {		
			$.ajax({
				url: '{$ajax_url}',
				data: {
					a: 'move_event', id: event.id, day_delta: day_delta, minute_delta: minute_delta, type: event.className
				}
			});
		},
		axisFormat: 'HH(:mm)',
		timeFormat: 'HH:mm{ - HH:mm}',		
		loading: function(bool) {
			if (bool) $('#loading').show();
			else $('#loading').hide();
		}
		
	});
	
});
</script>

<div id="dialog-form" style="display:none;">				
	<form id="add_event_form" name="form">
	<fieldset>
		<div class="row">		
			<div class="label">
				<label for="date">{"Date"|get_lang}</label>
			</div>
			<div class="formw">
				<span id="start_date" class="label"></span><span id="end_date" class="label"></span>
			</div>					
		</div>
		<div class="row">
			<div class="label">
				<label for="name">{"Title"|get_lang}</label>
			</div>		
			<div class="formw">
				<input type="text" name="title" id="title" size="52" />				
			</div>
		</div>		
		<div class="row">
			<div class="label">
				<label for="name">{"Description"|get_lang}</label>
			</div>		
			<div class="formw">
				<textarea name="content" id="content" cols="50" rows="7"></textarea>
			</div>
		</div>
		</fieldset>
	</form>
</div>
<div id='loading' style='position:absolute; display:none'>{"Loading"|get_lang}...</div>
<div id='calendar'></div>
