<style>
.fc-day-grid-event > .fc-content {
    white-space: normal;
}
</style>
<script>
function checkLength( o, n, min, max ) {
    if ( o.val().length > max || o.val().length < min ) {
        o.addClass( "ui-state-error" );
        /*updateTips( "Length of " + n + " must be between " +
            min + " and " + max + "." );*/
        return false;
    } else {
        return true;
    }
}
function clean_user_select() {
    //Cleans the selected attr
    $("#users_to_send").val('').trigger("chosen:updated");
}

var region_value = '{{ region_value }}';

$(function() {
    var cookieData = Cookies.getJSON('agenda_cookies');
    var defaultView = (cookieData && cookieData.view) || '{{ default_view }}';
    var defaultStartDate = (cookieData && cookieData.start) || moment.now();

    // Reset button.
    $("button[type=reset]").click(function() {
        $("#session_id").find('option').removeAttr("selected");
    });

	$("#dialog-form").dialog({
		autoOpen : false,
		modal : false,
		width : 600,
		height : 580,
        zIndex : 20000 // added because of qtip2
   	});

    $("#simple-dialog-form").dialog({
		autoOpen : false,
		modal : false,
		width : 600,
		height : 550,
        zIndex : 20000 // added because of qtip2
   	});

	var title = $("#title"),
	content = $("#content"),
	allFields = $([]).add( title ).add( content ), tips = $(".validateTips");

    $("#select_form_id_search").change(function() {
        var temp ="&user_id="+$("#select_form_id_search").val();
        var position =String(window.location).indexOf("&user");
        var url = String(window.location).substring(0,position)+temp;
        if (position > 0) {
            window.location.replace(url);
        } else {
            url = String(window.location)+temp;
            window.location.replace(url);
        }
    });

    var CustomListViewGrid  = ListViewGrid.extend({
        fgSegHtml: function(seg) {
            var view = this.view;
            var classes = [ 'fc-list-item' ].concat(this.getSegCustomClasses(seg));
            var bgColor = this.getSegBackgroundColor(seg);
            var event = seg.event;
            var url = event.url;
            var timeHtml;

            if (event.allDay) {
                timeHtml = view.getAllDayHtml();
            }
            else if (view.isMultiDayEvent(event)) { // if the event appears to span more than one day
                if (seg.isStart || seg.isEnd) { // outer segment that probably lasts part of the day
                    timeHtml = htmlEscape(this.getEventTimeText(seg));
                }
                else { // inner segment that lasts the whole day
                    timeHtml = view.getAllDayHtml();
                }
            }
            else {
                // Display the normal time text for the *event's* times
                timeHtml = htmlEscape(this.getEventTimeText(event));
            }

            if (url) {
                classes.push('fc-has-url');
            }

            return '<tr class="' + classes.join(' ') + '">' +
                (this.displayEventTime ?
                    '<td class="fc-list-item-time ' + view.widgetContentClass + '">' +
                        (timeHtml || '') +
                    '</td>' :
                    '') +
                '<td class="fc-list-item-marker ' + view.widgetContentClass + '">' +
                    '<span class="fc-event-dot"' +
                    (bgColor ?
                        ' style="background-color:' + bgColor + '"' :
                        '') +
                    '></span>' +
                '</td>' +
                '<td class="fc-list-item-title ' + view.widgetContentClass + '">' +
                    '<a' + (url ? ' href="' + htmlEscape(url) + '"' : '') + '>' +
                        htmlEscape(seg.event.title || '') + (seg.event.description || '')
                    '</a>' +
                '</td>' +
            '</tr>';
        },

        // render the event segments in the view
        renderSegList: function(allSegs) {
            var segsByDay = this.groupSegsByDay(allSegs); // sparse array
            var dayIndex;
            var daySegs;
            var i;
            var tableEl = $('<table class="fc-list-table"><tbody/></table>');
            var tbodyEl = tableEl.find('tbody');
            var eventList = new Array;
            for (dayIndex = 0; dayIndex < segsByDay.length; dayIndex++) {
                daySegs = segsByDay[dayIndex];
                if (daySegs) { // sparse array, so might be undefined
                    this.sortEventSegs(daySegs);
                    for (i = 0; i < daySegs.length; i++) {
                        var event = daySegs[i].event;
                        if (jQuery.inArray(event.id, eventList) !== -1) {
                            continue;
                        }
                        eventList.push(event.id);
                         // append a day header
                        tbodyEl.append(this.dayHeaderHtml(
                            this.view.start.clone().add(dayIndex, 'days'),
                            event
                        ));

                        tbodyEl.append(daySegs[i].el); // append event row
                    }
                }
            }

            this.el.empty().append(tableEl);
        },
        // generates the HTML for the day headers that live amongst the event rows
        dayHeaderHtml: function(dayDate, event) {
            var view = this.view;
            var mainFormat = 'LL';
            var altFormat = 'dddd';
            var checkIfSame = true;
            if (event.end) {
                checkIfSame = event.end.format(mainFormat) == dayDate.format(mainFormat);
            }

            return '<tr class="fc-list-heading" data-date="' + dayDate.format('YYYY-MM-DD') + '">' +
                '<td class="' + view.widgetHeaderClass + '" colspan="3">' +
                    (mainFormat ?
                        view.buildGotoAnchorHtml(
                            dayDate,
                            { 'class': 'fc-list-heading-main' },
                            htmlEscape(dayDate.format(mainFormat)) // inner HTML
                        ) :
                        '') +

                      ((checkIfSame == false && mainFormat) ?
                        view.buildGotoAnchorHtml(
                            dayDate,
                            { 'class': 'fc-list-heading-main' },
                            '&nbsp;-&nbsp; ' + htmlEscape(event.end.format(mainFormat)) // inner HTML
                        ) :
                        '') +

                    (altFormat ?
                        view.buildGotoAnchorHtml(
                            dayDate,
                            { 'class': 'fc-list-heading-alt' },
                            htmlEscape(dayDate.format(altFormat)) // inner HTML
                        ) :
                        '') +
                '</td>' +
            '</tr>';
        },
    });

	var FC = $.fullCalendar; // a reference to FullCalendar's root namespace
    var View = ListView;      // the class that all views must inherit from
    var CustomView;          // our subclass

    CustomView = View.extend({ // make a subclass of View
        initialize: function() {
            this.grid = new CustomListViewGrid(this);
            this.scroller = new Scroller({
                overflowX: 'hidden',
                overflowY: 'auto'
            });
        }
    });

    FC.views.CustomView = CustomView; // register our class with the view system
    var height = '';
    if (/Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent)) {
        height = 'auto';
    }

	var calendar = $('#calendar').fullCalendar({
        height: height,
		header: {
			left: 'today,prev,next',
			center: 'title',
			right: 'month,agendaWeek,agendaDay,CustomView'
		},
        views: {
            CustomView: { // name of view
                type: 'list',
                buttonText: '{{ 'AgendaList' | get_lang | escape('js') }}',
                duration: { month: 1 },
                defaults: {
                    'listDayAltFormat': 'dddd' // day-of-week is nice-to-have
                }
            },
            month: {
                'displayEventEnd' : true
            }
        },
        locale: region_value,
        {% if use_google_calendar == 1 %}
            eventSources: [
                // if you want to add more just add URL in this array
                '{{ google_calendar_url }}',
                {
                    className: 'gcal-event' // an option!
                }
            ],
        {% endif %}
        defaultView: defaultView,
        defaultDate: defaultStartDate,
        firstHour: 8,
        firstDay: 1,
        {% if fullcalendar_settings %}
            {{ fullcalendar_settings  }}
        {% endif %}
        selectable	: true,
		selectHelper: true,
        viewDisplay: function(view) {
            /* When changing the view update the qtips */
            /*var api = $('.qtip').qtip('api'); // Access the API of the first tooltip on the page
            if (api) {
                api.destroy();
                //api.render();
            }*/
        },
        viewRender: function(view, element) {
            var data = {
                'view': view.name,
                'start': view.intervalStart.format("YYYY-MM-DD")
            };
            Cookies.set('agenda_cookies', data, 1); // Expires 1 day
        },
		// Add event
		select: function(start, end, jsEvent, view) {
            var diffDays = moment(end).diff(start, 'days');

            var allDay = true;
            if (end.hasTime()) {
                allDay = false;
            }

			$('#visible_to_input').show();
			$('#add_as_announcement_div').show();
			$('#visible_to_read_only').hide();

			// Cleans the selected attr
		    clean_user_select();

            // Sets the 1st item selected by default
            $('#users_to_send option').eq(0).attr('selected', 'selected');

			// Update chz-select
			//$("#users_to_send").trigger("chosen:updated");
			if ({{ can_add_events }} == 1) {
			    var startEn = start.clone().locale('en'),
                    endEn = end.clone().locale('en');

				var url = '{{ web_agenda_ajax_url }}&a=add_event&start='+startEn.format("YYYY-MM-DD HH:mm:00")+'&end='+endEn.format("YYYY-MM-DD HH:mm:00")+'&all_day='+allDay+'&view='+view.name;
			    var start_date_value = start.format('{{ js_format_date }}');
                $('#start_date').html(start_date_value);

                if (diffDays > 1) {
                    $('#start_date').html('');
                    var end_date_value = '';
                    if (end) {
                        var clone = end.clone();
                        end_date_value = clone.subtract(1, 'days').format('{{ js_format_date }}');
                    }
                    $('#end_date').html(start_date_value + " - " + end_date_value);
                } else if (diffDays == 0) {
                    var start_date_value = start.format('ll');
                    var startTime = start.format('LT');
                    var endTime = end.format('LT');
                    $('#start_date').html('');
                    $('#end_date').html(start_date_value + " (" + startTime + " - " + endTime+") ");
                } else {
                    $('#end_date').html('');
                }

				$('#color_calendar')
                    .html('{{ type_label | escape('js')}}')
                    .removeClass('group_event')
                    .addClass('label_tag')
                    .addClass('{{ type_event_class | escape('js') }}')
                    .css('background-color', '{{ type_event_color }}');

                //It shows the CKEDITOR while Adding an Event
                $('#cke_content').show();
                //It Fixing a minor bug with textarea ckeditor.remplace
                $('#content').css('display','none');
                //Reset the CKEditor content that persist in memory
                CKEDITOR.instances['content'].setData('');
				allFields.removeClass("ui-state-error");
				$("#dialog-form").dialog("open");
				$("#dialog-form").dialog({
					buttons: {
						'{{ "Add" | get_lang }}' : function() {
							var bValid = true;
							bValid = bValid && checkLength(title, "title", 1, 255);

                            //Update the CKEditor Instance to the remplaced textarea, ready to be serializable
                            for ( instance in CKEDITOR.instances ) {
                                CKEDITOR.instances[instance].updateElement();
                            }

							var params = $("#add_event_form").serialize();

							$.ajax({
								url: url+'&'+params,
								success:function(data) {
									var user = $('#users_to_send').val();
                                    if (user) {
                                        if (user.length > 1) {
                                            user = 0;
                                        } else {
                                            user = user[0];
                                        }
                                        var user_length = String(user).length;
                                        if (String(user).substring(0,1) == 'G') {
                                            var user_id = String(user).substring(6,user_length);
                                            var user_id = "G:"+user_id;
                                        } else {
                                            var user_id = String(user).substring(5,user_length);
                                        }
                                        var temp = "&user_id="+user_id;
                                        var position = String(window.location).indexOf("&user");
                                        var url = String(window.location).substring(0, position)+temp;
                                        /*if (position > 0) {
                                            window.location.replace(url);
                                        } else {
                                            url = String(window.location)+temp;
                                            window.location.replace(url);
                                        }*/
                                    }

                                    $("#title").val('');
                                    $("#content").val('');
                                    $("#comment").val('');

                                    calendar.fullCalendar('refetchEvents');
                                    calendar.fullCalendar('rerenderEvents');

									$("#dialog-form").dialog('close');
								}
							});
						}
					},
					close: function() {
                        $("#title").val('');
                        $("#content").val('');
                        $("#comment").val('');
					}
				});

				calendar.fullCalendar('unselect');
                //Reload events
                calendar.fullCalendar("refetchEvents");
                calendar.fullCalendar("rerenderEvents");
			}
		},
		eventRender: function(event, element) {
            if (event.attachment) {
                /*element.qtip({
                    hide: {
                        delay: 2000
                    },
		            content: event.attachment,
		            position: { at:'top right' , my:'bottom right'}
		        }).removeData('qtip'); // this is an special hack to add multiple qtip in the same target
		        */
            }

            var onHoverInfo = '';
            {% if on_hover_info.description %}
                if (event.description) {
                    onHoverInfo = event.description;
                }
            {% endif %}

            {% if on_hover_info.comment %}
                if (event.comment) {
                    onHoverInfo = onHoverInfo + event.comment;
                }
            {% endif %}

            if (onHoverInfo) {
                element.qtip({
                    content: onHoverInfo,
                    position: {
                        at: 'top center',
                        my: 'bottom center',
                        viewport: $(window)
                    }
                });
			}
	    },
		eventClick: function(calEvent, jsEvent, view) {
            var start = calEvent.start;
            var end = calEvent.end;
            var diffDays = moment(end).diff(start, 'days');
            var endDateMinusOne = '';

            // If event is not editable then just return the qtip
            if (!calEvent.editable) {
                var onHoverInfo = '';
                {% if calEvent.description %}
                if (calEvent.description) {
                    onHoverInfo = calEvent.description;
                }
                {% endif %}
                {% if on_hover_info.comment %}
                if (calEvent.comment) {
                    onHoverInfo = onHoverInfo + calEvent.comment;
                }
                {% endif %}

                if (/Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent)) {
                    $(this).qtip({
                        overwrite: false,
                        show: {ready: true},
                        content: onHoverInfo,
                        position: {
                            at: 'top center',
                            my: 'bottom center',
                            viewport: $(window)
                        }
                    });

                    return;
                }
            }

            if (end) {
                var clone = end.clone();
                endDateMinusOne = clone.subtract(1, 'days').format('{{ js_format_date }}');
            }
            var startDateToString = start.format("{{ js_format_date }}");

			// Edit event.
			if (calEvent.editable) {
				$('#visible_to_input').hide();
                $('#add_as_announcement_div').hide();
                {% if type != 'admin' %}
                    $('#visible_to_read_only').show();
                    $("#visible_to_read_only_users").html(calEvent.sent_to);
				{% endif %}

                $('#color_calendar').html('{{ type_label | escape('js') }}');
                $('#color_calendar').addClass('label_tag');
                $('#color_calendar').removeClass('course_event');
                $('#color_calendar').removeClass('personal_event');
                $('#color_calendar').removeClass('group_event');
                $('#color_calendar').addClass(calEvent.type+'_event');

                // It hides the CKEDITOR while clicking an existing Event
                $('#cke_content').hide();
                $('#start_date').html(startDateToString);
                if (diffDays > 1) {
                    $('#end_date').html(' - ' + endDateMinusOne);
                } else if (diffDays == 0) {
                    var start_date_value = start.format('ll');
                    var startTime = start.format('LT');
                    var endTime = end.format('LT');
                    $('#start_date').html('');
                    $('#end_date').html(start_date_value + " (" + startTime + " - " + endTime+") ");
                } else {
                    $('#end_date').html('');
                }

                if ($("#title").parent().find('#title_edit').length == 0) {
                    $("#title").parent().append('<div id="title_edit"></div>');
                }

                $("#title_edit").html(calEvent.title);

                if ($("#content").parent().find('#content_edit').length == 0) {
                    $("#content").parent().append('<div id="content_edit"></div>');
                }
                $("#content_edit").html(calEvent.description);

                if ($("#comment").parent().find('#comment_edit').length == 0) {
                    $("#comment").parent().append('<div id="comment_edit"></div>');
                }

                if (calEvent.course_name) {
                    $("#calendar_course_info").html(
                        '<div class="form-group"><label class="col-sm-2 control-label">{{ 'Course' | get_lang }}</label>' +
                        '<div class="class="col-sm-8">' + calEvent.course_name+"</div></div>"
                    );
                } else {
                    $("#calendar_course_info").html('');
                }

                if (calEvent.session_name) {
                    $("#calendar_session_info").html(
                        '<div class="form-group"><label class="col-sm-2 control-label">{{ 'Session' | get_lang }}</label>'+
                        '<div class="class="col-sm-8">' + calEvent.session_name+"</div></div>"
                    );
                } else {
                    $("#calendar_session_info").html('');
                }

                if (calEvent.comment != '') {
                    $("#comment_edit").html(calEvent.comment);
                    $("#comment_edit").show();
                }

                if (calEvent.attachment != '') {
                    $("#attachment_text").html(calEvent.attachment);
                    $("#attachment_block").show();
                    $("#attachment_text").show();
                }

                $("#title_edit").show();
                $("#content_edit").show();

                $("#title").hide();
                $("#content").hide();
                $("#comment").hide();

				allFields.removeClass( "ui-state-error" );
				$("#dialog-form").dialog("open");

				var url = '{{ web_agenda_ajax_url }}&a=edit_event&id='+calEvent.id+'&view='+view.name;
				var delete_url = '{{ web_agenda_ajax_url }}&a=delete_event&id='+calEvent.id;

				$("#dialog-form").dialog({
					buttons: {
                        '{{ "ExportiCalConfidential"|get_lang }}' : function() {
                            url =  "{{ _p.web_main }}calendar/ical_export.php?id=" + calEvent.id+'&course_id='+calEvent.course_id+"&class=confidential";
                            window.location.href = url;
						},
						'{{ "ExportiCalPrivate"|get_lang }}': function() {
                            url =  "{{ _p.web_main }}calendar/ical_export.php?id=" + calEvent.id+'&course_id='+calEvent.course_id+"&class=private";
                            window.location.href = url;
						},
                        '{{ "ExportiCalPublic"|get_lang }}': function() {
                            url =  "{{ _p.web_main }}calendar/ical_export.php?id=" + calEvent.id+'&course_id='+calEvent.course_id+"&class=public";
                            window.location.href = url;
						},
                        {% if type == 'not_available' %}
						'{{ "Edit" | get_lang }}' : function() {
							var bValid = true;
							bValid = bValid && checkLength( title, "title", 1, 255 );

							var params = $("#add_event_form").serialize();
							$.ajax({
								url: url+'&'+params,
								success:function() {
									calEvent.title = $("#title").val();
									calEvent.start = calEvent.start;
									calEvent.end = calEvent.end;
									calEvent.allDay = calEvent.allDay;
									calEvent.description = $("#content").val();
									calendar.fullCalendar('updateEvent',
                                        calEvent,
                                        true // make the event "stick"
									);
									$("#dialog-form").dialog("close");
								}
							});
						},
                        {% endif %}
                        '{{ "Edit"|get_lang }}' : function() {
                            url =  "{{ _p.web_main }}calendar/agenda.php?action=edit&type=fromjs&id="+calEvent.id+'&course_id='+calEvent.course_id+"";
                            window.location.href = url;
                            $("#dialog-form").dialog( "close" );
                        },
						'{{ "Delete"|get_lang }}': function() {
                            if (calEvent.parent_event_id || calEvent.has_children != '') {
                                var newDiv = $('<div>');
                                newDiv.dialog({
                                    modal: true,
                                    title: "{{ 'DeleteThisItem' | get_lang }}",
                                    buttons: []
                                });

                                var buttons = newDiv.dialog("option", "buttons");

                                if (calEvent.has_children == '0') {
                                    buttons.push({
                                        text: '{{ "DeleteThisItem" | get_lang }}',
                                        click: function() {
                                            $.ajax({
                                                url: delete_url,
                                                success:function() {
                                                    calendar.fullCalendar('removeEvents',
                                                        calEvent
                                                    );
                                                    calendar.fullCalendar("refetchEvents");
                                                    calendar.fullCalendar("rerenderEvents");
                                                    $("#dialog-form").dialog("close");
                                                    newDiv.dialog( "destroy" );
                                                }
                                            });
                                        }
                                    });
                                    newDiv.dialog("option", "buttons", buttons);
                                }

                                var buttons = newDiv.dialog("option", "buttons");
                                buttons.push({
                                    text: '{{ "DeleteAllItems" | get_lang }}',
                                    click: function() {
                                        $.ajax({
                                            url: delete_url+'&delete_all_events=1',
                                            success:function() {
                                                calendar.fullCalendar('removeEvents',
                                                    calEvent
                                                );
                                                calendar.fullCalendar('refetchEvents');
                                                calendar.fullCalendar('rerenderEvents');
                                                $("#dialog-form").dialog('close');
                                                newDiv.dialog( "destroy" );
                                            }
                                        });
                                    }
                                });
                                newDiv.dialog("option", "buttons", buttons);

                                return true;
                            }

							$.ajax({
								url: delete_url,
								success:function() {
									calendar.fullCalendar('removeEvents',
										calEvent
									);
									calendar.fullCalendar('refetchEvents');
									calendar.fullCalendar('rerenderEvents');
									$("#dialog-form").dialog('close');
								}
							});
						}
					},
					close: function() {
                        $("#title_edit").hide();
                        $("#content_edit").hide();
                        $("#comment_edit").hide();
                        $("#attachment_block").hide();
                        $("#attachment_text").hide();

                        $("#title").show();
                        $("#content").show();
                        $("#comment").show();

						$("#title_edit").html('');
						$("#content_edit").html('');
                        $("#comment_edit").html('');
                        $("#attachment_text").html('');

                        $("#title").val('');
                        $("#content").val('');
                        $("#comment").val('');
					}
				});
			} else {
			    // Simple form
                $('#simple_start_date').html(startDateToString);
                if (diffDays > 1) {
                    $('#simple_end_date').html(' - ' + endDateMinusOne);
                } else if (diffDays == 0) {
                    var start_date_value = start.format('ll');
                    var startTime = start.format('LT');
                    var endTime = end.format('LT');
                    $('#simple_start_date').html('');
                    $('#simple_end_date').html(start_date_value + " (" + startTime + " - " + endTime+") ");
                } else {
                    $('#simple_end_date').html('');
                }

                if (calEvent.course_name) {
                    $("#calendar_course_info_simple").html(
                        '<div class="form-group"><label class="col-sm-3 control-label">{{ 'Course' | get_lang }}</label>' +
                        '<div class="col-sm-9">' + calEvent.course_name+"</div></div>"
                    );
                } else {
                    $("#calendar_course_info_simple").html('');
                }

                if (calEvent.session_name) {
                    $("#calendar_session_info").html(
                        '<div class="form-group"><label class="col-sm-3 control-label">{{ 'Session' | get_lang }}</label>' +
                        '<div class="col-sm-9">' + calEvent.session_name+"</div></div>"
                    );
                } else {
                    $("#calendar_session_info").html('');
                }

                $("#simple_title").html(calEvent.title);
                $("#simple_content").html(calEvent.description);
                $("#simple_comment").html(calEvent.comment);
                $("#simple_attachment").html(calEvent.attachment);

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
		eventDrop: function(event, delta, revert_func) {
		    var allDay = 0;
		    if (event.allDay == true) {
		        allDay = 1;
            }
			$.ajax({
				url: '{{ web_agenda_ajax_url }}',
				data: {
                    a: 'move_event',
                    id: event.id,
                    all_day: allDay,
                    minute_delta: delta.asMinutes()
				}
			});
		},
        eventResize: function(event, delta, revert_func) {
            $.ajax({
				url: '{{ web_agenda_ajax_url }}',
				data: {
                    a: 'resize_event',
                    id: event.id,
                    minute_delta: delta.asMinutes()
				}
			});
        },
		axisFormat: 'H(:mm)', // pm-am format -> h(:mm)a
		timeFormat: 'H:mm',   // pm-am format -> h:mm
		loading: function(bool) {
			if (bool) $('#loading').show();
			else $('#loading').hide();
		}
	});
});
</script>
{{ actions_div }}
{{ toolbar }}

<div id="simple-dialog-form" style="display:none;">
    <div style="width:500px;">
        <form name="form-simple" class="form-horizontal">
            <span id="calendar_course_info_simple"></span>
            <span id="calendar_session_info"></span>
            <div class="form-group">
                <label class="col-sm-3 control-label">
                    <b>{{ "Date" |get_lang}}</b>
                </label>
                <div class="col-sm-9">
                    <span id="simple_start_date"></span>
                    <span id="simple_end_date"></span>
                </div>
            </div>
            <div class="form-group">
                <label class="col-sm-3 control-label">
                    <b>{{ "Title" |get_lang}}</b>
                </label>
                <div class="col-sm-9">
                    <div id="simple_title"></div>
                </div>
            </div>
            <div class="form-group">
                <label class="col-sm-3 control-label">
                    <b>{{ "Description" |get_lang}}</b>
                </label>
                <div class="col-sm-9">
                    <div id="simple_content"></div>
                </div>
            </div>
            <div class="form-group">
                <label class="col-sm-3 control-label">
                    <b>{{ "Comment" |get_lang}}</b>
                </label>
                <div class="col-sm-9">
                    <div id="simple_comment"></div>
                </div>
            </div>

            <div class="form-group">
                <label class="col-sm-3 control-label">
                    <b>{{ "Attachment" |get_lang}}</b>
                </label>
                <div class="col-sm-9">
                    <div id="simple_attachment"></div>
                </div>
            </div>
        </form>
    </div>
</div>

<div id="dialog-form" style="display:none;">
	<div class="dialog-form-content">
        {{ form_add }}
	</div>
</div>

{% if legend_list %}
    {% for color, text in legend_list %}
        <span style="background-color: {{ color }}" class="label label-default">&nbsp;</span> {{ text }} &nbsp;&nbsp;
    {% endfor %}
    <br /><br />
{% endif %}
<div id="loading" style="margin-left:150px;position:absolute;display:none">
    {{ "Loading" | get_lang }}...
</div>
<div id="calendar"></div>
