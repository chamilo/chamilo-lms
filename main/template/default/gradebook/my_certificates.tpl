{% if course_list is not empty %}
    <h1 class="page-header">{{ "Courses"|get_lang }}</h1>

    <div class="table-responsive">
        <table class="table table-hover table-striped">
            <thead>
                <tr>
                    <th>{{ "Course"|get_lang }}</th>
                    <th class="text-right">{{ "Score"|get_lang }}</th>
                    <th class="text-center">{{ "Date"|get_lang }}</th>
                    <th class="text-right">&nbsp;</th>
                </tr>
            </thead>
            <tbody>
                {% for row in course_list %}
                    <tr>
                        <td>{{ row.course }}</td>
                        <td class="text-right">{{ row.score }}</td>
                        <td class="text-center">{{ row.date }}</td>
                        <td class="text-right">
                            <a href="{{ row.link }}" target="_blank" class="btn btn-default">
                                <em class="fa fa-external-link"></em> {{ 'Certificate'|get_lang }}
                            </a>
                        </td>
                    </tr>
                {% endfor %}
            </tbody>
        </table>
    </div>
{% endif %}

{% if session_list is not empty %}
    <h1 class="page-header">{{ "Sessions"|get_lang }}</h1>

    <div class="table-responsive">
        <table class="table table-hover table-striped">
            <thead>
                <tr>
                    <th>{{ "Session"|get_lang }}</th>
                    <th>{{ "Course"|get_lang }}</th>
                    <th class="text-right">{{ "Score"|get_lang }}</th>
                    <th class="text-center">{{ "Date"|get_lang }}</th>
                    <th class="text-right">&nbsp;</th>
                </tr>
            </thead>
            <tbody>
                {% for row in session_list %}
                    <tr>
                        <td>{{ row.session }}</td>
                        <td>{{ row.course }}</td>
                        <td class="text-right">{{ row.score }}</td>
                        <td class="text-center">{{ row.date }}</td>
                        <td class="text-right">
                            <a href="{{ row.link }}" target="_blank" class="btn btn-default">
                                <em class="fa fa-external-link"></em> {{ 'Certificate'|get_lang }}
                            </a>
                        </td>
                    </tr>
                {% endfor %}
            </tbody>
        </table>
    </div>
{% endif %}
