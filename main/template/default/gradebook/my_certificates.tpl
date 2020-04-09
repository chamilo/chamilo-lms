{% if course_list is not empty %}
    <h2 class="page-header">{{ "Courses"|get_lang }}</h2>
    <div class="table-responsive">
        <table class="table table-hover table-striped">
            <thead>
                <tr>
                    <th>{{ "Course"|get_lang }}</th>
                    <th class="text-right">{{ "Score"|get_lang }}</th>
                    <th class="text-center">{{ "Date"|get_lang }}</th>
                    <th width="10%" class="text-right">&nbsp;</th>
                    <th width="10%" class="text-right">&nbsp;</th>
                </tr>
            </thead>
            <tbody>
                {% for row in course_list %}
                    <tr>
                        <td>{{ row.course }}</td>
                        <td class="text-right">{{ row.score }}</td>
                        <td class="text-center">{{ row.date }}</td>

                        {% if allow_export %}
                        <td class="text-right">
                            <a href="{{ row.pdf }}" target="_blank" class="btn btn-primary btn-block">
                                <i class="fa fa-file-pdf-o" aria-hidden="true"></i>
                                {{ 'DownloadCertificatePdf'|get_lang }}
                            </a>
                        </td>
                        {% endif %}

                        <td class="text-right">
                            <a href="{{ row.link }}" target="_blank" class="btn btn-default btn-block">
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
    <h2 class="page-header">{{ "Sessions"|get_lang }}</h2>
    <div class="table-responsive">
        <table class="table table-hover table-striped">
            <thead>
                <tr>
                    <th>{{ "Session"|get_lang }}</th>
                    <th>{{ "Course"|get_lang }}</th>
                    <th class="text-right">{{ "Score"|get_lang }}</th>
                    <th class="text-center">{{ "Date"|get_lang }}</th>
                    <th width="10%" class="text-right">&nbsp;</th>
                    <th width="10%" class="text-right">&nbsp;</th>
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
                            <a href="{{ row.pdf }}" target="_blank" class="btn btn-primary btn-block">
                                <i class="fa fa-file-pdf-o" aria-hidden="true"></i>
                                 {{ 'DownloadCertificatePdf'|get_lang }}
                            </a>
                        </td>
                        <td class="text-right">
                            <a href="{{ row.link }}" target="_blank" class="btn btn-default btn-block">
                                <em class="fa fa-external-link"></em> {{ 'Certificate'|get_lang }}
                            </a>
                        </td>
                    </tr>
                {% endfor %}
            </tbody>
        </table>
    </div>
{% endif %}
