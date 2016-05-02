{{ search_form }}

{% if user_list is not empty %}
    <div class="table-responsive">
        <table class="table table-hover table-striped">
            <thead>
                <tr>
                    <th>{{ "FirstName"|get_lang }}</th>
                    <th>{{ "LastName"|get_lang }}</th>
                    <th class="text-right">&nbsp;</th>
                </tr>
            </thead>
            <tbody>
                {% for user in user_list %}
                    <tr>
                        <td>{{ user.firstname }}</td>
                        <td>{{ user.lastname }}</td>
                        <td class="text-right">
                            <a href="{{ _p.web_main }}gradebook/search.php?id={{ user.id }}" class="btn btn-default">
                                <em class="fa fa-external-link"></em> {{ "Certificates"|get_lang }}
                            </a>
                        </td>
                    </tr>
                {% endfor %}
            </tbody>
        </table>
    </div>
{% endif %}

{% if course_list is not empty or session_list is not empty %}
    <h2>{{ user_info.complete_name }}</h2>

    {% if course_list is not empty %}
        <h3 class="page-header">{{ "Courses"|get_lang }}</h3>

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
        <h3 class="page-header">{{ "Sessions"|get_lang }}</h3>

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
{% endif %}
