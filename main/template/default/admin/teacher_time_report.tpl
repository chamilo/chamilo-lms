<script>
$(function () {
    $('#course').on('change', function () {
        $('#session').prop('selectedIndex', 0);
        $('#teacher').prop('selectedIndex', 0);
    });

    $('#session').on('change', function () {
        $('#course').prop('selectedIndex', 0);
        $('#teacher').prop('selectedIndex', 0);
    });

    $('#teacher').on('change', function () {
        $('#course').prop('selectedIndex', 0);
        $('#session').prop('selectedIndex', 0);
    });

    $('#daterange').on('apply.daterangepicker', function (ev, picker) {
        $('[name="from"]').val(picker.startDate.format('YYYY-MM-DD'));
        $('[name="until"]').val(picker.endDate.format('YYYY-MM-DD'));
    }).on('cancel.daterangepicker', function (ev, picker) {
        $('#daterange, [name="from"], [name="until"]').val('');
    });
});
</script>

{{ form }}
<h3 class="page-header">{{ report_title }} <small>{{ report_sub_title }}</small></h3>

<table class="table">
    <thead>
        <tr>
            {% if with_filter %}
                <th>{{ 'Session' | get_lang }}</th>
                <th>{{ 'Course' | get_lang }}</th>
            {% endif %}
            <th>{{ 'Coach' | get_lang }}</th>
            <th class="text-center">{{ 'TotalTime' | get_lang }}</th>
        </tr>
    </thead>
    <tbody>
        {% for row in rows %}
            <tr>
                {% if with_filter %}
                    <td>{{ row.session ? row.session.name : '&nbsp' }}</td>
                    <td>{{ row.course.name }}</td>
                {% endif %}
                <td>{{ row.coach.complete_name }} ({{ row.coach.username}})</td>
                <td class="text-center">{{ row.total_time }}</td>
            </tr>
        {% endfor %}
    </tbody>
</table>
