<script>
$(document).on('ready', function () {
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

<div class="col-md-12">
    <div class="actions">
        <a href="{{ _p.web_main }}admin/teachers_time_by_session_report.php">
            {{ 'session.png'|img(32, 'Sessions'|get_lang) }}
        </a>
        <div class="pull-right">
            <a href="{{ _p.web_self ~ '?' ~ {'export':'pdf','from':selected_from,'until':selected_until,'course':selected_course,'session':selected_session,'teacher':selected_teacher } | url_encode }}">
                {{ 'pdf.png' | img(32, 'ExportToPDF'|get_lang ) }}
            </a>
            <a href="{{ _p.web_self ~ '?' ~ {'export':'xls','from':selected_from,'until':selected_until,'course':selected_course,'session':selected_session,'teacher':selected_teacher } | url_encode }}">
                {{ 'export_excel.png' | img(32, 'ExportExcel'|get_lang ) }}
            </a>
        </div>
    </div>
</div>

<h1 class="page-header">{{ 'TeacherTimeReport' | get_lang }}</h1>
{{ form }}
<h2 class="page-header">{{ report_title }} <small>{{ report_sub_title }}</small></h2>

<table class="table">
    <thead>
        <tr>
            {% if with_filter %}
                <th>{{ 'Session' | get_lang }}</th>
                <th>{{ 'Course' | get_lang }}</th>
            {% endif %}
            <th>{{ 'Coach' | get_lang }}</th>
            <th>{{ 'TotalTime' | get_lang }}</th>
        </tr>
    </thead>
    <tbody>
        {% for row in rows %}
            <tr>
                {% if with_filter %}
                    <td>{{ row.session ? row.session.name : '&nbsp' }}</td>
                    <td>{{ row.course.name }}</td>
                {% endif %}
                <td>{{ row.coach.completeName }} ({{ row.coach.username}})</td>
                <td>{{ row.totalTime }}</td>
            </tr>
        {% endfor %}
    </tbody>
</table>
