<script>
    function getEvents(e) {
        var myData = [];
        $.ajax({
            dataType: "json",
            url: "{{ ajax_url }}&a=get_events",
            success: function(response) {
                for (var i = 0; i < response.length; i++) {
                    var startDate = moment(response[i].start_date + " 13:00:00").toDate();
                    var endDate = moment(response[i].end_date + " 13:00:00").toDate();
                    myData.push({
                        id: response[i].id,
                        title: response[i].title,
                        startDate: startDate,
                        endDate: endDate,
                        color: response[i].color
                    });
                }
                $(e.target).data('calendar').setDataSource(myData);
            }
        });
    }
    $(document).ready(function() {
        $('#calendar').calendar({
            style:'background',
            language : '{{ calendar_language }}',
            enableRangeSelection: true,
            enableContextMenu: true,
            /*contextMenuItems:[
                {
                    text: 'Update',
                    //click: editEvent
                },
                {
                    text: 'Delete',
                    click: deleteEvent
                }
            ],*/
            customDayRenderer: function(e) {
                $(e).parent().css('background-color', 'green');
            },
            {#clickDay: function(e) {#}
                {#var dateString = moment(e.date).format("YYYY-MM-DD");#}
                {#$.ajax({#}
                    {#type: "GET",#}
                    {#url: "{{ ajax_url }}&a=toggle_day&start_date="+dateString+"&end_date=",#}
                    {#success: function(returnValue) {#}
                        {#getEvents(e);#}
                    {#}#}
                {#});#}
            {#},#}
            selectRange: function(e) {
                var startString = moment(e.startDate).format("YYYY-MM-DD");
                var endString = moment(e.endDate).format("YYYY-MM-DD");
                $.ajax({
                    type: "GET",
                    url: "{{ ajax_url }}&a=toggle_day&start_date="+startString+"&end_date=" + endString,
                    success: function(returnValue) {
                        getEvents(e);
                    }
                });
            },
            yearChanged: function(e) {
                e.preventRendering = true;
                $(e.target).append('<div style="text-align:center"><i class="fa fa-spinner fa-spin fa-2x"></i></div>');
                getEvents(e);
            },
        });
    });
</script>

<style>
    #calendar {
        overflow: visible;
    }

    .calendar table.month tr td .day-content {
        height: 24px;
    }
</style>

<div class="row">
    <div class="col-xs-12 col-md-12">
        <div id="calendar"></div>
    </div>
</div>
<br />
<br />
<div class="row">
    <div class="col-xs-12 col-md-12">
<table>
    {% for event in events %}
    <tr>
        <td>
            {{ event.name }}:
        </td>
        <td>
            <span style="display:block;height:20px;width:100px; background-color: {{ event.color }}"></span>
        </td>
    </tr>
    {% endfor %}
</table>
    </div>
</div>
