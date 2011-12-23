<script type="text/javascript">

function checkLength( o, n, min, max ) {
    if ( o.val().length > max || o.val().length < min ) {
        o.addClass( "ui-state-error" );
        updateTips( "Length of " + n + " must be between " +
            min + " and " + max + "." );
        return false;
    } else {
        return true;
    }
}
function clean_user_select() {
    //Cleans the selected attr     
    $('#users_to_send_id')
        .find('option')
        .removeAttr('selected')
        .end();
}

$(document).ready(function() {
	
	var date = new Date();
	var d = date.getDate();
	var m = date.getMonth();
	var y = date.getFullYear();
	
	$("#dialog-form").dialog({
		autoOpen: false,
		modal	: false, 
		width	: 550, 
		height	: 450
   	});

	var title = $( "#title" ),
	content = $( "#content" ),	
	allFields = $( [] ).add( title ).add( content ), tips = $(".validateTips");	

	$('#users_to_send_id').bind('change', function() {
	    
	    var selected_counts = $("#users_to_send_id option:selected").size();
	    
	    //alert(selected_counts);        
       /* if (selected_counts >= 1 && $("#users_to_send_id option[value='everyone']").attr('selected') == 'selected') {        
            clean_user_select();
            
            $('#users_to_send_id option').eq(0).attr('selected', 'selected');
            //deleting the everyone
            $("#users_to_send_id").trigger("liszt:updated");
            deleted_items = true;
            
        }*/
        $("#users_to_send_id").trigger("liszt:updated");    
     /*
	    if (selected_counts >= 1) {	        	                
	        $('#users_to_send_id option').eq(0).removeAttr('selected');            
            
            
	    }
	    
	   */ 
	    //clean_user_select();
	    //$("#users_to_send_id").trigger("liszt:updated");
	    //alert($("#users_to_send_id option[value='everyone']").attr('selected'));
	    if ($("#users_to_send_id option[value='everyone']").attr('selected') == 'selected') {
            //clean_user_select();
            //$('#users_to_send_id option').eq(0).attr('selected', 'selected');
            //$("#users_to_send_id").trigger("liszt:updated");            
        }
    });
	
	var calendar = $('#calendar').fullCalendar({
		header: {
			left: 'today prev,next',
			center: 'title',
			right: 'month,agendaWeek,agendaDay',	
		},	
		buttonText: 	{$button_text}, 
		monthNames: 	{$month_names},
		monthNamesShort:{$month_names_short},
		dayNames: 		{$day_names},
		dayNamesShort: 	{$day_names_short},		
		selectable	: true,
		selectHelper: true,
		//add event
		select: function(start, end, allDay, jsEvent, view) {
			/* When selecting one day or several days */
			
			var start_date 	= Math.round(start.getTime() / 1000);
			var end_date 	= Math.round(end.getTime() / 1000);
			
			$('#visible_to_input').show();
			$('#add_as_announcement_div').show();
			
			$('#visible_to_read_only').hide();
			
			//Cleans the selected attr 	
		    clean_user_select();
                
            //Sets the 1st item selected by default
            //$('#users_to_send_id option').eq(0).attr('selected', 'selected');
			
			//Update chz-select
			$("#users_to_send_id").trigger("liszt:updated");
			
			if ({$can_add_events} == 1) {							
				var url = '{$web_agenda_ajax_url}a=add_event&start='+start_date+'&end='+end_date+'&all_day='+allDay+'&view='+view.name;
				
				$('#start_date').html(start.toDateString() + " " +  start.toTimeString().substr(0, 8));
				if (view.name != 'month') {
					$('#start_date').html(start.toDateString() + " " +  start.toTimeString().substr(0, 8));
					if (start.toDateString() == end.toDateString()) {					
						$('#end_date').html(' - '+end.toTimeString().substr(0, 8));
					} else {
						$('#end_date').html(' - '+end.toDateString()+" " + end.toTimeString().substr(0, 8));
					}
				} else {
					$('#start_date').html(start.toDateString());
					$('#end_date').html(' - ' + end.toDateString());					
				}
				$('#color_calendar').html('{$type_label}');
				$('#color_calendar').removeClass('group_event');
				$('#color_calendar').addClass('label_tag');				
				$('#color_calendar').addClass('{$type}_event');
				
				allFields.removeClass( "ui-state-error" );		
				$("#dialog-form").dialog("open");		
				
				$("#dialog-form").dialog({				
					buttons: {
						{"Add"|get_lang}: function() {
							var bValid = true;
							bValid = bValid && checkLength( title, "title", 1, 255 );
							//bValid = bValid && checkLength( content, "content", 1, 255 );
							
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
			}
		},	
		eventRender: function(event, element) {
			if (event.description) {
				element.qtip({
		            content: event.description,
		            position: { at:'top left' , my:'bottom left'},	
		        });
			}
	        
	    },
		eventClick: function(calEvent, jsEvent, view) {
			//edit event
			if (calEvent.editable) {									
				var start_date 	= Math.round(calEvent.start.getTime() / 1000);
				if (calEvent.allDay == 1) {				
					var end_date 	= '';				
				} else {			
					var end_date 	= Math.round(calEvent.end.getTime() / 1000);				
				}
				
				$('#visible_to_input').hide();
				$('#visible_to_read_only').show();				
				$('#add_as_announcement_div').hide();
				
				$("#visible_to_read_only_users").html(calEvent.sent_to);
				
				$('#color_calendar').html('{$type_label}');
				$('#color_calendar').addClass('label_tag');
								
				$('#color_calendar').removeClass('course_event');
				$('#color_calendar').removeClass('personal_event');
				$('#color_calendar').removeClass('group_event');
				
				$('#color_calendar').addClass(calEvent.type+'_event');
				
				$('#start_date').html(calEvent.start.getDate() +"/"+ calEvent.start.getMonth() +"/"+calEvent.start.getFullYear());
				
				if (end_date != '') {
					$('#end_date').html(' '+calEvent.end.getDate() +"/"+ calEvent.end.getMonth() +"/"+calEvent.end.getFullYear());
				}			
	
				$("#title").attr('value', calEvent.title);
				$("#content").attr('value', calEvent.description);
				allFields.removeClass( "ui-state-error" );
				
				$("#dialog-form").dialog("open");
	
				var url = '{$web_agenda_ajax_url}a=edit_event&id='+calEvent.id+'&start='+start_date+'&end='+end_date+'&all_day='+calEvent.allDay+'&view='+view.name;
				var delete_url = '{$web_agenda_ajax_url}a=delete_event&id='+calEvent.id;
				
				$("#dialog-form").dialog({				
					buttons: {
						{"Edit"|get_lang} : function() {
							
							var bValid = true;
							bValid = bValid && checkLength( title, "title", 1, 255 );
							//bValid = bValid && checkLength( content, "content", 1, 255 );
							
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
						{"Delete"|get_lang}: function() { 
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
			}
		},
		editable: true,		
		events: "{$web_agenda_ajax_url}a=get_events",		
		eventDrop: function(event, day_delta, minute_delta, all_day, revert_func) {		
			$.ajax({
				url: '{$web_agenda_ajax_url}',
				data: {
					a: 'move_event', id: event.id, day_delta: day_delta, minute_delta: minute_delta
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
	<div style="width:500px">
	<form id="add_event_form" name="form">
	      
	    {if !empty($visible_to) } 
    	    <div id="visible_to_input" class="row">      
                <div class="label">
                    <label for="date">{"To"|get_lang}</label>
                </div>
                <div class="formw">
                    {$visible_to}                    
                </div>                  
            </div>
        {/if}
        
         <div id="visible_to_read_only" class="row" style="display:none">      
                <div class="label">
                    <label for="date">{"To"|get_lang}</label>
                </div>
                <div class="formw">
                    <div id="visible_to_read_only_users"></div>                  
                </div>                  
         </div>
        
          
        	
		<div class="row">		
			<div class="label">
				<label for="date">{"Agenda"|get_lang}</label>
			</div>
			<div class="formw">
				<div id="color_calendar"></div>
			</div>					
		</div>
		<div class="row">		
			<div class="label">
				<label for="date">{"Date"|get_lang}</label>
			</div>
			<div class="formw">
				<span id="start_date"></span><span id="end_date"></span>
			</div>					
		</div>
		<div class="row">
			<div class="label">
				<label for="name">{"Title"|get_lang}</label>
			</div>		
			<div class="formw">
				<input type="text" name="title" id="title" size="40" />				
			</div>
		</div>
				
		<div class="row">
			<div class="label">
				<label for="name">{"Description"|get_lang}</label>
			</div>		
			<div class="formw">
				<textarea name="content" id="content" cols="40" rows="7"></textarea>
			</div>
		</div>	
		
		{if $type == 'course'}
		<div id="add_as_announcement_div">
    		 <div class="row">
                <div class="label">                    
                </div>      
                <div class="formw">
                    <input type="checkbox" name="add_as_annonuncement" id="add_as_annonuncement" />
                    <label for="add_as_annonuncement">{"AddAsAnnouncement"|get_lang}</label>
                </div>
            </div>
        </div>
		{/if}
	</form>
	</div>
</div>
<div id='loading' style='left:180px;top:10px;position:absolute; display:none'>{"Loading"|get_lang}...</div>
<div id='calendar'></div>