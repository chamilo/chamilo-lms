<div class="media">
    <div class="media-left">
        <a href="#">
            <img class="media-object" src="{{ user_avatar }}" alt="{{ user.completeName }}">
        </a>
    </div>
    <div class="media-body">
        <h3 class="media-heading">{{ user.completeName }}</h3>
        <p>{{ user.username }}</p>
    </div>
</div>
<br>
{% for survey in surveys %}
    {% set course_code = survey.course ? survey.course.code : '' %}
    {% set session_id = survey.session ? survey.session.id : 0 %}

    <div class="panel panel-default">
        <div class="panel-body">
            <div>
                <a href="{{ _p.web_main ~ 'survey/fillsurvey.php?' ~ {'course': course_code, 'invitationcode': survey.invitation_code, 'cidReq': course_code, 'id_session': session_id}|url_encode }}">
                    {{ survey.title }}
                </a>
            </div>
            <ul class="list-inline">
                {% if survey.course %}
                    <li>
                        <span class="label" style="background-color: {{ survey.session ? '#00496D' : '#458B00' }}">
                            {{ survey.course.title }}

                            {% if survey.session %}
                                ({{ survey.session.name }})
                            {% endif %}
                        </span>
                    </li>
                {% endif %}
                <li>
                    {{ 'FromDateXToDateY'|get_lang|format(survey.avail_from|api_convert_and_format_date(2), survey.avail_till|api_convert_and_format_date(2)) }}
                </li>
            </ul>
        </div>
    </div>
{% else %}
    <div class="alert alert-info">
        {{ 'NoPendingSurveys'|get_lang }}
    </div>
{% endfor %}
