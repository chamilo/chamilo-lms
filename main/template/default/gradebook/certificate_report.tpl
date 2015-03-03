<script>
    $(document).on('ready', function () {
        $('select#session').on('change', function () {
            var sessionId = parseInt(this.value, 10),
                    $selectCourse = $('select#course');

            $selectCourse.empty();

            $.get('{{ _p.web_main }}inc/ajax/course.ajax.php', {
                a: 'display_sessions_courses',
                session: sessionId
            }, function (courseList) {
                var $option = null;

                $('<option>', {
                    value: 0,
                    text: "{{ 'Select' | get_lang }}"
                }).appendTo($selectCourse);

                if (courseList.length > 0) {
                    $.each(courseList, function (index, course) {
                        $('<option>', {
                            value: course.id,
                            text: course.name
                        }).appendTo($selectCourse);
                    });
                }
            }, 'json');
        });
    });
</script>

<form action="{{ _p.web_main }}gradebook/certificate_report.php" method="post" class="form-horizontal">
    <div class="control-group">
        <label class="control-label" for="session">{{ 'Sessions' | get_lang }}</label>
        <div class="controls">
            <select name="session" id="session">
                <option value="0">{{ 'Select' | get_lang }}</option>
                {% for session in sessions %}
                    <option value="{{ session.id }}" {{ selectedSession == session.id ? 'selected' : '' }}>{{ session.name }}</option>
                {% endfor %}
            </select>
        </div>
    </div>
    <div class="control-group">
        <label class="control-label" for="course">{{ 'Courses' | get_lang }}</label>
        <div class="controls">
            <select name="course" id="course">
                <option value="0">{{ 'Select' | get_lang }}</option>
                {% for course in courses %}
                    <option value="{{ course.real_id }}" {{ selectedCourse == course.real_id ? 'selected' : ''}}>{{ course.title }}</option>
                {% endfor %}
            </select>
        </div>
    </div>
    <div class="control-group">
        <label class="control-label" for="month">{{ 'Date' | get_lang }}</label>
        <div class="controls">
            <select name="month" id="month">
                <option value="0">{{ 'Select' | get_lang }}</option>
                {% for month in months %}
                    <option value="{{ month.key }}" {{ selectedMonth == month.key ? 'selected' : ''}}>{{ month.name }}</option>
                {% endfor %}
            </select>
            <input type="text" name="year" id="year" class="input-mini" value="{{ selectedYear }}">
        </div>
    </div>
    <div class="control-group">
        <div class="controls">
            <button type="submit" class="btn btn-primary">{{ 'Search' | get_lang }}</button>
        </div>
    </div>
</form>

<h1 class="page-header">{{ 'GradebookListOfStudentsCertificates' | get_lang }}</h1>

{% if errorMessage is defined %}
    <div class="alert alert-error">{{ errorMessage }}</div>
{% endif %}

{% if not certificateStudents is empty %}
    <p>
        <a href="{{ exportAllLink }}" class="btn btn-info">{{ 'ExportAllCertificatesToPDF' | get_lang }}</a>
    </p>

    <table class="table table-striped">
        <thead>
            <tr>
                <th>{{ 'Student' | get_lang }}</th>
                <th>{{ 'Date' | get_lang }}</th>
                <th>{{ 'Certificate' | get_lang }}</th>
            </tr>
        </thead>
        <tfoot>
            <tr>
                <th>{{ 'Student' | get_lang }}</th>
                <th>{{ 'Date' | get_lang }}</th>
                <th>{{ 'Certificate' | get_lang }}</th>
            </tr>
        </tfoot>
        <tbody>
            {% for student in certificateStudents %}
                <tr>
                    <td>{{ student.fullName }}</td>
                    <td>
                        {% for certificate in student.certificates %}
                            <p>{{ certificate.createdAt }}</p>
                        {% endfor %}
                    </td>
                    <td>
                        {% for certificate in student.certificates %}
                            <a href="{{ _p.web }}certificates/index.php?id={{ certificate.id }}" class="btn">{{ 'Certificate' | get_lang }}</a>
                        {% endfor %}
                    </td>
                </tr>
            {% endfor %}
        </tbody>
    </table>
{% else %}
    <p class="alert alert-info">{{ 'NoResults' | get_lang }}</p>
{% endif %}
