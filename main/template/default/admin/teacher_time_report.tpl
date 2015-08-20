{% extends template ~ "/layout/main.tpl" %}

{% block body %}
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
            <span class="pull-right">
                <a href="{{ _p.web_self }}?export=pdf&from={{ selectedFrom }}&until={{ selectedUntil }}&course={{ selectedCourse }}&session={{ selectedSession }}&teacher={{ selectedTeacher }}">
                    <img src="{{ _p.web_img }}icons/32/pdf.png" title="{{ 'ExportToPDF' | get_lang }}">
                </a>
                <a href="{{ _p.web_self }}?export=xls&from={{ selectedFrom }}&until={{ selectedUntil }}&course={{ selectedCourse }}&session={{ selectedSession }}&teacher={{ selectedTeacher }}">
                    <img src="{{ _p.web_img }}icons/32/export_excel.png" title="{{ 'ExportExcel' | get_lang }}">
                </a>
            </span>
        </div>
        <h1 class="page-header">{{ 'TeacherTimeReport' | get_lang }}</h1>
        {{ form }}
        <h2 class="page-header">{{ reportTitle }} <small>{{ reportSubTitle }}</small></h2>
        <table class="table">
            <thead>
                <tr>
                    {% if withFilter %}
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
                        {% if withFilter %}
                            <td>{{ row.session ? row.session.name : '&nbsp' }}</td>
                            <td>{{ row.course.name }}</td>
                        {% endif %}
                        <td>{{ row.coach.completeName }} ({{ row.coach.username}})</td>
                        <td>{{ row.totalTime }}</td>
                    </tr>
                {% endfor %}
            </tbody>
        </table>
    </div>
{% endblock %}
