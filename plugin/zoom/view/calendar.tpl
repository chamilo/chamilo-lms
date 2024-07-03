<div id="loading" style="margin-left:150px;position:absolute;display:none">
    {{ "Loading"|get_lang }} &hellip;
</div>

<div id="calendar"></div>

<div class="modal fade" tabindex="-1" role="dialog" id="simple-dialog-form">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="{{ 'Close'|get_lang }}">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title">{{ 'Details'|get_lang }}</h4>
            </div>
            <div class="modal-body">
                <form class="form-horizontal">
                    <div class="form-group">
                        <label class="col-sm-4 control-label">{{ 'Type' }}</label>
                        <div class="col-sm-8">
                            <p id="simple_type"></p>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-4 control-label">{{ "Date"|get_lang }}</label>
                        <div class="col-sm-8">
                            <p>
                                <span id="simple_start_date"></span>
                                <span id="simple_end_date"></span>
                            </p>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-4 control-label">{{ "Title"|get_lang }}</label>
                        <div class="col-sm-8">
                            <p id="simple_title"></p>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-4 control-label">{{ "Description"|get_lang }}</label>
                        <div class="col-sm-8">
                            <p id="simple_content"></p>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-4 control-label">{{ "AccountEmail"|get_plugin_lang('ZoomPlugin') }}</label>
                        <div class="col-sm-8">
                            <p id="simple_account"></p>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">{{ 'Close'|get_lang }}</button>
            </div>
        </div>
    </div>
</div>

<script>
    $(function () {
        var cookieData = Cookies.getJSON('agenda_cookies');
        var defaultView = (cookieData && cookieData.view) || '{{ default_view }}';
        var defaultStartDate = (cookieData && cookieData.start) || moment.now();

        var CustomListViewGrid = ListViewGrid.extend({
            fgSegHtml: function (seg) {
                var view = this.view;
                var classes = ['fc-list-item'].concat(this.getSegCustomClasses(seg));
                var bgColor = this.getSegBackgroundColor(seg);
                var event = seg.event;
                var url = event.url;
                var timeHtml;

                if (view.isMultiDayEvent(event)) { // if the event appears to span more than one day
                    if (seg.isStart || seg.isEnd) { // outer segment that probably lasts part of the day
                        timeHtml = htmlEscape(this.getEventTimeText(seg));
                    } else { // inner segment that lasts the whole day
                        timeHtml = view.getAllDayHtml();
                    }
                } else {
                    // Display the normal time text for the *event's* times
                    timeHtml = htmlEscape(this.getEventTimeText(event));
                }

                if (url) {
                    classes.push('fc-has-url');
                }

                return '<tr class="' + classes.join(' ') + '">' +
                    (this.displayEventTime
                        ? '<td class="fc-list-item-time ' + view.widgetContentClass + '">' + (timeHtml || '') + '</td>'
                        : ''
                    ) +
                    '<td class="fc-list-item-marker ' + view.widgetContentClass + '">' +
                    '<span class="fc-event-dot"' +
                    (bgColor ? ' style="background-color:' + bgColor + '"' : '') +
                    '></span>' +
                    '</td>' +
                    '<td class="fc-list-item-title ' + view.widgetContentClass + '">' +
                    '<a' + (url ? ' href="' + htmlEscape(url) + '"' : '') + '>' +
                    htmlEscape(seg.event.title || '') + (seg.event.description || '') +
                    '</a>' +
                    '</td>' +
                    '</tr>';
            },

            // render the event segments in the view
            renderSegList: function (allSegs) {
                var segsByDay = this.groupSegsByDay(allSegs); // sparse array
                var dayIndex;
                var daySegs;
                var i;
                var tableEl = $('<table class="fc-list-table"><tbody/></table>');
                var tbodyEl = tableEl.find('tbody');
                var eventList = [];
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
            dayHeaderHtml: function (dayDate, event) {
                var view = this.view;
                var mainFormat = 'LL';
                var altFormat = 'dddd';
                var checkIfSame = true;
                if (event.end) {
                    checkIfSame = event.end.format(mainFormat) === dayDate.format(mainFormat);
                }

                return '<tr class="fc-list-heading" data-date="' + dayDate.format('YYYY-MM-DD') + '">' +
                    '<td class="' + view.widgetHeaderClass + '" colspan="3">' +
                    (
                        mainFormat
                            ? view.buildGotoAnchorHtml(
                                dayDate,
                                { 'class': 'fc-list-heading-main' },
                                htmlEscape(dayDate.format(mainFormat)) // inner HTML
                            )
                            : ''
                    ) +
                    (
                        (checkIfSame === false && mainFormat)
                            ? view.buildGotoAnchorHtml(
                                dayDate,
                                { 'class': 'fc-list-heading-main' },
                                '&nbsp;-&nbsp; ' + htmlEscape(event.end.format(mainFormat)) // inner HTML
                            )
                            : ''
                    ) +
                    (
                        altFormat
                            ? view.buildGotoAnchorHtml(
                                dayDate,
                                { 'class': 'fc-list-heading-alt' },
                                htmlEscape(dayDate.format(altFormat)) // inner HTML
                            )
                            : ''
                    ) +
                    '</td>' +
                    '</tr>'
            }
        })

        var FC = $.fullCalendar; // a reference to FullCalendar's root namespace
        var View = ListView;      // the class that all views must inherit from
        var CustomView;          // our subclass

        CustomView = View.extend({ // make a subclass of View
            initialize: function () {
                this.grid = new CustomListViewGrid(this);
                this.scroller = new Scroller({
                    overflowX: 'hidden',
                    overflowY: 'auto'
                });
            }
        })

        FC.views.CustomView = CustomView; // register our class with the view system
        var height = '';
        if (/Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent)) {
            height = 'auto';
        }

        $('#calendar').fullCalendar({
            height: height,
            header: {
                left: 'today,prev,next',
                center: 'title',
                right: 'month,agendaWeek,agendaDay,CustomView'
            },
            views: {
                CustomView: { // name of view
                    type: 'list',
                    buttonText: '{{ 'AgendaList'|get_lang | escape('js') }}',
                    duration: { month: 1 },
                    defaults: {
                        'listDayAltFormat': 'dddd' // day-of-week is nice-to-have
                    }
                },
                month: {
                    'displayEventEnd': true
                }
            },
            locale: '{{ region_value }}',
            defaultView: defaultView,
            defaultDate: defaultStartDate,
            firstHour: 8,
            firstDay: 1,
            {% if fullcalendar_settings %}
            {{ fullcalendar_settings }}
            {% endif %}
            selectable: false,
            selectHelper: true,
            viewRender: function (view, element) {
                var data = {
                    'view': view.name,
                    'start': view.intervalStart.format('YYYY-MM-DD')
                };
                Cookies.set('agenda_cookies', data, 1); // Expires 1 day
            },
            eventRender: function (event, element) {
                {% if on_hover_info.description %}
                if (event.description) {
                    element.qtip({
                        content: event.description,
                        position: {
                            at: 'top center',
                            my: 'bottom center',
                            viewport: $(window)
                        }
                    });
                }
                {% endif %}
            },
            eventClick: function (calEvent, jsEvent, view) {
                var start = calEvent.start;
                var end = calEvent.end;
                var diffDays = moment(end).diff(start, 'days');
                var endDateMinusOne = '';

                if (/Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent)) {
                    // If event is not editable then just return the qtip
                    {% if on_hover_info.description %}
                    if (calEvent.description) {
                        $(this).qtip({
                            overwrite: false,
                            show: { ready: true },
                            content: calEvent.description,
                            position: {
                                at: 'top center',
                                my: 'bottom center',
                                viewport: $(window)
                            }
                        });
                    }
                    {% endif %}

                    return;
                }

                var clone = end.clone();
                endDateMinusOne = clone.subtract(1, 'days').format('{{ js_format_date }}');
                var startDateToString = start.format("{{ js_format_date }}");

                // Simple form
                $('#simple_start_date').text(startDateToString);
                if (diffDays > 1) {
                    $('#simple_end_date').text(' - ' + endDateMinusOne);
                } else if (diffDays == 0) {
                    var start_date_value = start.format('ll');
                    var startTime = start.format('LT');
                    var endTime = end.format('LT');
                    $('#simple_start_date').html('');
                    $('#simple_end_date').html(start_date_value + ' (' + startTime + ' - ' + endTime + ') ');
                } else {
                    $('#simple_end_date').text('');
                }

                $('#simple_type').text(calEvent.typeName);
                $('#simple_title').text(calEvent.title);
                $('#simple_content').empty().text(calEvent.description);

                if (calEvent.accountEmail) {
                    $('#simple_account').text(calEvent.accountEmail).parents('.form-group').show();
                } else {
                    $('#simple_account').empty().parents('.form-group').hide();
                }

                $('#simple-dialog-form').modal('show');
            },
            editable: false,
            events: "{{ web_agenda_ajax_url }}&a=get_events",
            axisFormat: 'H(:mm)', // pm-am format -> h(:mm)a
            timeFormat: 'H:mm',   // pm-am format -> h:mm
            loading: function (bool) {
                if (bool) {
                    $('#loading').show();
                } else {
                    $('#loading').hide();
                }
            }
        })
    })
</script>
