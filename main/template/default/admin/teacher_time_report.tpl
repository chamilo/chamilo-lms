{% extends "default/layout/main.tpl" %}

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

            $('#daterange').daterangepicker({
                format: 'DD-MM-YYYY',
                startDate: new Date('{{ filterStartDate }}'),
                endDate: new Date('{{ filterEndDate }}'),
                maxDate: new Date('{{ filterMaxDate }}'),
                separator: ' / ',
                locale: {
                    applyLabel: '{{ 'Ok' | get_lang }}',
                    cancelLabel: '{{ 'Cancel' | get_lang }}',
                    fromLabel: '{{ 'From' | get_lang }}',
                    toLabel: '{{ 'Until' | get_lang }}',
                    customRangeLabel: '{{ 'CustomRange' | get_lang }}'
                }
            });
            $('#daterange').on('apply.daterangepicker', function (ev, picker) {
                $('#from').val(picker.startDate.format('YYYY-MM-DD'));
                $('#until').val(picker.endDate.format('YYYY-MM-DD'));
            });
        });
    </script>
    <div class="span12">
        <form class="form-horizontal" method="post">
            <div class="control-group">
                <label class="control-label" for="course">{{ 'Course' | get_lang }}</label>
                <div class="controls">
                    <select name="course" id="course">
                        <option value="0">{{ 'None' | get_lang }}</option>
                        {% for course in courses %}
                            <option value="{{ course.code }}" {{ (course.code == selectedCourse) ? 'selected' : '' }}>{{ course.title }}</option>
                        {% endfor %}
                    </select>
                </div>
            </div>
            <div class="control-group">
                <label class="control-label" for="session">{{ 'Session' | get_lang }}</label>
                <div class="controls">
                    <select name="session" id="session">
                        <option value="0">{{ 'None' | get_lang }}</option>
                        {% for session in sessions %}
                            <option value="{{ session.id }}" {{ (session.id == selectedSession) ? 'selected' : '' }}>{{ session.name }}</option>
                        {% endfor %}
                    </select>
                </div>
            </div>
            <div class="control-group">
                <label class="control-label" for="inputPassword">{{ 'Teacher' | get_lang }}</label>
                <div class="controls">
                    <select name="teacher" id="teacher">
                        <option value="0">{{ 'None' | get_lang }}</option>
                        {% for teacher in courseCoaches %}
                            <option value="{{ teacher.id }}" {{ (teacher.id == selectedTeacher) ? 'selected' : '' }}>{{ teacher.completeName }}</option>
                        {% endfor %}
                    </select>
                </div>
            </div>
            <div class="control-group">
                <label class="control-label" for="inputPassword">{{ 'Date' | get_lang }}</label>
                <div class="controls">
                    <input type="text" id="daterange" readonly>
                    <input type="hidden" id="from" name="from">
                    <input type="hidden" id="until" name="until">
                </div>
            </div>
            <div class="control-group">
                <div class="controls">
                    <button type="submit" class="btn">{{ 'Filter' | get_lang }}</button>
                </div>
            </div>
        </form>
        <h1 class="page-header">{{ 'GlobalTeacherTimeReport' | get_lang }}</h1>
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
                            <td>{{ row.session.name }}</td>
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