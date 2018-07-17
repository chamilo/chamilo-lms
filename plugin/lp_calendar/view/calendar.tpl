<script>
    function getEvents(e) {
        var myData = [];
        $.ajax({
            dataType: "json",
            url: "{{ ajax_url }}&a=get_events",
            success: function(response) {
                for (var i = 0; i < response.length; i++) {
                    myData.push({
                        id: response[i].id,
                        title: response[i].title,
                        startDate: new Date(response[i].start_date),
                        endDate: new Date(response[i].end_date),
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
            enableRangeSelection: false,
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
            clickDay: function(e) {
                var dateString = moment(e.date).format("YYYY-MM-DD");
                $.ajax({
                    type: "GET",
                    url: "{{ ajax_url }}&a=toggle_day&date="+dateString,
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
</style>

<div id=calendar></div>
