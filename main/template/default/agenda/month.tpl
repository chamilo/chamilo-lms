<script>

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

var region_value = '{{region_value}}';
$(document).ready(function() {

    /*$("body").delegate(".datetime", "focusin", function(){
        $(this).datepicker({
            stepMinute: 10,            
            dateFormat: 'dd/mm/yy',
            timeFormat: 'hh:mm:ss'            
        });
    });*/

	
	var date = new Date();
	var d = date.getDate();
	var m = date.getMonth();
	var y = date.getFullYear();
	
	$("#dialog-form").dialog({
		autoOpen: false,
		modal	: false, 
		width	: 550, 
		height	: 480,
        zIndex: 20000 // added because of qtip2
   	});
    
    $("#simple-dialog-form").dialog({
		autoOpen: false,
		modal	: false, 
		width	: 550, 
		height	: 480,
        zIndex: 20000 // added because of qtip2
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
    
    $.datepicker.setDefaults( $.datepicker.regional[region_value] );
	
	var calendar = $('#calendar').fullCalendar({
		header: {
			left: 'today prev,next',
			center: 'title',
			right: 'month,agendaWeek,agendaDay',	
		},	        
        {% if use_google_calendar == 1 %}
            eventSources: [
                '{{google_calendar_url}}',  //if you want to add more just add URL in this array
                {
                    className: 'gcal-event',           // an option!                    
                }
            ],
        {% endif %}
        
		buttonText: 	{{button_text}}, 
		monthNames: 	{{month_names}},
		monthNamesShort:{{month_names_short}},
		dayNames: 		{{day_names}},
		dayNamesShort: 	{{day_names_short}},		
        firstHour: 8,
        firstDay: 1, 
		selectable	: true,
		selectHelper: true,
        
        viewDisplay: function(view) {
            /* When changing the view update the qtips */            
            var api = $('.qtip').qtip('api'); // Access the API of the first tooltip on the page
            if (api) {
                api.destroy();
                //api.render();
            }
        },
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
			
			if ({{can_add_events}} == 1) {							
				var url = '{{web_agenda_ajax_url}}&a=add_event&start='+start_date+'&end='+end_date+'&all_day='+allDay+'&view='+view.name;
                
                var start_date_value = $.datepicker.formatDate('{{js_format_date}}', start);
                var end_date_value  = $.datepicker.formatDate('{{js_format_date}}', end);
				
				$('#start_date').html(start_date_value + " " +  start.toTimeString().substr(0, 8));
                
				if (view.name != 'month') {
					$('#start_date').html(start_date_value + " " +  start.toTimeString().substr(0, 8));
					if (start.toDateString() == end.toDateString()) {					
						$('#end_date').html(' - '+end.toTimeString().substr(0, 8));
					} else {
						$('#end_date').html(' - '+start_date_value+" " + end.toTimeString().substr(0, 8));
					}
				} else {
					$('#start_date').html(start_date_value);
					$('#end_date').html(' ');					
				}
				$('#color_calendar').html('{{type_label}}');
				$('#color_calendar').removeClass('group_event');
				$('#color_calendar').addClass('label_tag');				
				$('#color_calendar').addClass('{{type}}_event');
				
				allFields.removeClass( "ui-state-error" );		
				$("#dialog-form").dialog("open");		
				
				$("#dialog-form").dialog({				
					buttons: {
						{{"Add"|get_lang}}: function() {
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
            if (event.attachment) {
                element.qtip({            
                    hide: {
                        delay: 2000
                    },
		            content: event.attachment,
		            position: { at:'top right' , my:'bottom right'},	
		        }).removeData('qtip'); // this is an special hack to add multipl qtip in the same target!
                
            }
            
			if (event.description) {
				element.qtip({
                    hide: {
                        delay: 2000
                    },
		            content: event.description,
		            position: { at:'top left' , my:'bottom left'},	
		        });                
			}
	        
	    },
		eventClick: function(calEvent, jsEvent, view) {
             
            var start_date 	= Math.round(calEvent.start.getTime() / 1000);
            if (calEvent.allDay == 1) {				
                var end_date 	= '';				
            } else {			
                var end_date 	= '';	
                if (calEvent.end && calEvent.end != '') {
                    var end_date 	= Math.round(calEvent.end.getTime() / 1000);				
                }
            }

			//edit event
			if (calEvent.editable) {	       
				
				$('#visible_to_input').hide();                
                $('#add_as_announcement_div').hide();
                
                {% if type != 'admin' %}
                    $('#visible_to_read_only').show();                    
                    $("#visible_to_read_only_users").html(calEvent.sent_to);
				{% endif %}
                    
                $('#color_calendar').html('{{type_label}}');            
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
	
				var url = '{{web_agenda_ajax_url}}&a=edit_event&id='+calEvent.id+'&start='+start_date+'&end='+end_date+'&all_day='+calEvent.allDay+'&view='+view.name;
				var delete_url = '{{web_agenda_ajax_url}}&a=delete_event&id='+calEvent.id;
				
				$("#dialog-form").dialog({
					buttons: {
                        '{{"ExportiCalConfidential"|get_lang}}' : function() {                                            
                                url =  "ical_export.php?id=" + calEvent.id+'&course_id='+calEvent.course_id+"&class=confidential";                                
                                window.location.href = url;
                                
						},
						'{{"ExportiCalPrivate"|get_lang}}': function() { 
                                url =  "ical_export.php?id=" + calEvent.id+'&course_id='+calEvent.course_id+"&class=private";			
                                window.location.href = url;
						},
                        '{{"ExportiCalPublic"|get_lang}}': function() { 
                                url =  "ical_export.php?id=" + calEvent.id+'&course_id='+calEvent.course_id+"&class=public";			
                                window.location.href = url;
						},                        
						'{{"Edit"|get_lang}}' : function() {
							
							var bValid = true;
							bValid = bValid && checkLength( title, "title", 1, 255 );							
							
							var params = $("#add_event_form").serialize();						
							$.ajax({
								url: url+'&'+params,
								success:function() {
									calEvent.title 			= $("#title").val();
									calEvent.start 			= calEvent.start;
									calEvent.end 			= calEvent.end;									
									calEvent.allDay         = calEvent.allDay;
									calEvent.description 	= $("#content").val();
																	
									calendar.fullCalendar('updateEvent', 
											calEvent,
											true // make the event "stick"
									);
									
									$("#dialog-form").dialog("close");										
								}							
							});
						},
						'{{"Delete"|get_lang}}': function() { 
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
			} else { //simple form    
            
                $('#simple_start_date').html(calEvent.start.getDate() +"/"+ calEvent.start.getMonth() +"/"+calEvent.start.getFullYear());

                if (end_date != '') {
                    $('#simple_start_date').html(calEvent.start.getDate() +"/"+ calEvent.start.getMonth() +"/"+calEvent.start.getFullYear() +" - "+calEvent.start.toLocaleTimeString());
                    $('#simple_end_date').html(' '+calEvent.end.getDate() +"/"+ calEvent.end.getMonth() +"/"+calEvent.end.getFullYear() +" - "+calEvent.end.toLocaleTimeString());
                }
                
                $("#simple_title").html(calEvent.title);
                $("#simple_content").html(calEvent.description);	                
                $("#simple-dialog-form").dialog("open");
                
                $("#simple-dialog-form").dialog({
					buttons: {
						'{{"ExportiCalConfidential"|get_lang}}' : function() {                                            
                                url =  "ical_export.php?id=" + calEvent.id+'&course_id='+calEvent.course_id+"&class=confidential";			
                                window.location.href = url;
                                
						},
						'{{"ExportiCalPrivate"|get_lang}}': function() { 
                                url =  "ical_export.php?id=" + calEvent.id+'&course_id='+calEvent.course_id+"&class=private";			
                                window.location.href = url;
						},
                        '{{"ExportiCalPublic"|get_lang}}': function() { 
                                url =  "ical_export.php?id=" + calEvent.id+'&course_id='+calEvent.course_id+"&class=public";			
                                window.location.href = url;
						}
					}
				});
                
            }
		},
		editable: true,		
		events: "{{web_agenda_ajax_url}}&a=get_events",
		eventDrop: function(event, day_delta, minute_delta, all_day, revert_func) {		
			$.ajax({
				url: '{{web_agenda_ajax_url}}',
				data: {
					a:'move_event', id: event.id, day_delta: day_delta, minute_delta: minute_delta
				}
			});
		},
        eventResize: function(event, day_delta, minute_delta, revert_func) {
            $.ajax({
				url: '{{web_agenda_ajax_url}}',
				data: {
					a:'resize_event', id: event.id, day_delta: day_delta, minute_delta: minute_delta
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

<div id="simple-dialog-form" style="display:none;">
    <div style="width:500px">
        <form name="form-simple" class="form-vertical" >        
            <div class="control-group">
                <label class="control-label"><b>{{"Date"|get_lang}}</b></label>			
                <div class="controls">
                    <span id="simple_start_date"></span><span id="simple_end_date"></span>                
                </div>					
            </div>
            <div class="control-group">			
                <label class="control-label"><b>{{"Title"|get_lang}}</b></label>
                <div class="controls">				
                    <div id="simple_title"></div>
                </div>
            </div>

            <div class="control-group">			
                <label class="control-label"><b>{{"Description"|get_lang}}</b></label>			
                <div class="controls">
                    <div id="simple_content"></div>
                </div>
            </div>
        </form>
    </div>
</div>

<div id="dialog-form" style="display:none;">
	<div style="width:500px">
	<form class="form-horizontal" id="add_event_form" name="form">
	    
        {% if visible_to is not null %}
    	    <div id="visible_to_input" class="control-group">                      
                <label class="control-label" for="date">{{"To"|get_lang}}</label>                
                <div class="controls">
                    {{visible_to}}                   
                </div>                  
            </div>
        {% endif %}        
         <div id="visible_to_read_only" class="control-group" style="display:none">                      
                <label class="control-label" for="date">{{"To"|get_lang}}</label>                
                <div class="controls">
                    <div id="visible_to_read_only_users"></div>                  
                </div>                  
         </div>        	
		<div class="control-group">					
            <label class="control-label" for="date">{{"Agenda"|get_lang}}</label>			
			<div class="controls">
				<div id="color_calendar"></div>
			</div>					
		</div>
		<div class="control-group">					
			<label class="control-label" for="date">{{"Date"|get_lang}}</label>			
			<div class="controls">
				<span id="start_date"></span><span id="end_date"></span>                
			</div>					
		</div>
		<div class="control-group">			
			<label class="control-label" for="name">{{"Title"|get_lang}}</label>			
			<div class="controls">
				<input type="text" name="title" id="title" size="40" />				
			</div>
		</div>
				
		<div class="control-group">			
			<label class="control-label" for="name">{{"Description"|get_lang}}</label>			
			<div class="controls">
				<textarea name="content" id="content" class="span3" rows="5"></textarea>
			</div>
		</div>	
		
        {% if type == 'course' %}
		<div id="add_as_announcement_div">
    		 <div class="control-group">                
                <label></label>
                <div class="controls">                    
                    <label class="checkbox inline" for="add_as_annonuncement">
                        {{"AddAsAnnouncement"|get_lang}}
                        <input type="checkbox" name="add_as_annonuncement" id="add_as_annonuncement" />                    
                    </label>                    
                </div>
            </div>
        </div>
		{% endif %}
	</form>
	</div>
</div>
<div id="loading" style="margin-left:150px;position:absolute;display:none">{{"Loading"|get_lang}}...</div>
<div id="calendar"></div>