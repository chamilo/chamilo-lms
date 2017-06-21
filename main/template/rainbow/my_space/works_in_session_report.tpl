{{ form }}

{% if session %}
    <h3 class="page-header">{{ session.name }}</h3>
    <div class="table-responsive">
        <table class="table table-hover table-striped">
            <thead>
                <tr>
                    <th>{{ 'OfficialCode'|get_lang }}</th>
                    <th>{{ 'StudentName'|get_lang }}</th>
                    <th>{{ 'TimeSpentOnThePlatform'|get_lang }}</th>
                    <th>{{ 'FirstLoginInPlatform'|get_lang }}</th>
                    <th>{{ 'LatestLoginInPlatform'|get_lang }}</th>

                    {% for course_code in courses %}
                        <th title="{{ header.title }}">{{ course_code }} <br />({{ 'BestScore' | get_lang }})</th>
                        <th>{{ 'Progress'|get_lang }}</th>
                        <th>{{ 'LastSentWorkDate'|get_lang }}</th>
                    {% endfor %}
                </tr>
            </thead>
            <tbody>
                {% for user in users %}
                    <tr>
                        {% for data in user %}
                            <td>{{ data }}</td>
                        {% endfor %}
                    </tr>
                {% endfor %}
            </tbody>
        </table>
    </div>
{% endif %}
