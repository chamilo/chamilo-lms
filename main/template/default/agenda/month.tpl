<script type='text/javascript'>
$(document).ready(function() {	
	var date = new Date();
	var d = date.getDate();
	var m = date.getMonth();
	var y = date.getFullYear();
	
	$('#calendar').fullCalendar({
		header: {
			left: 'prev,next today',
			center: 'title',
			right: 'month,agendaWeek,agendaDay'
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
<div id='loading' style='display:none'>{"Loading"|get_lang}...</div>
<div id='calendar'></div>
