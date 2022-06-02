{{toolbar}}

{{ form }}

{% if users %}
<h3 class="page-header">{{ sessionName }}</h3>
<div class="table-responsive">
    <table class="table table-hover table-striped">
        <thead>
        <tr>
            <th>{{ 'StudentName'|get_lang }}</th>
            <th>{{ 'TimeSpentOnThePlatform'|get_lang }}</th>

            {% for course_code in courses %}
            <th>{{ 'Progress'|get_lang }}<br />{{ course_code }}</th>
            <th>{{ 'Certificate'|get_lang }}<br />{{ course_code }}</th>
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

{{script}}