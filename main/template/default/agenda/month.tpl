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
			
			var url = '{$_p.web_ajax}agenda.ajax.php?a=add_event&start='+start_date+'&end='+end_date+'&view='+view.name;
			
			$('#start_date').html(start.getDate() +"/"+ start.getMonth() +"/"+start.getFullYear());
			$('#end_date').html(end.getDate() +"/"+ end.getMonth() +"/"+end.getFullYear());
			
			$("#dialog-form").dialog("open");
			$("#dialog-form").dialog({				
				buttons: {
					"Add event": function() {
						var params = $("#add_event_form").serialize();						
						$.ajax({
							url: url+'&'+params,
							success:function() {
								calendar.fullCalendar('renderEvent', 
										{
											title: $("#title").val(),
											start: start,
											end: end,
											allDay: allDay
										},
										true // make the event "stick"
								);
								
								$("#dialog-form").dialog("close");										
							}							
						});
					},
				},				
				close: function() {
						
				}
			});                   

			
			 
		
            //prevent the browser to follow the link
            return false;
			calendar.fullCalendar('unselect');
		},	
		dayClick: function(date, allDay, jsEvent, view) {
		},
		editable: true,		
		events: "{$ajax_url}?a=get_events",		
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

<div id="dialog-form"  title="{"AddEvent"|get_lang}" style="display:none">				
	<form id="add_event_form" name="form">
		<span id="start_date" ></span> - <span id="end_date" ></span>
		<div class="row">
				<label for="name">{"Title"|get_lang}</label>
		</div>
		<div class="formw">
			<input type="text" name="title" id="title" size="52" />				
		</div>
		
		<div class="row">
			<label for="name">{"Description"|get_lang}</label>
		</div>
		
		<div class="formw">
			<textarea name="content" id="content" cols="50" rows="7"></textarea>
		</div>
	</form>
</div>
<div id='calendar'></div>
<div id='loading' style='display:none'>{"Loading"|get_lang}...</div>