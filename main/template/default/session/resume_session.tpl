{% extends template ~ "/layout/layout_1_col.tpl" %}

{% block content %}

{{ session_header }}
{{ title }}

<table id="session-properties" class="data_table">
    <tr>
        <td>{{ 'CreatedBy'|get_lang }}</td>
        <td>{{ session_admin.complete_name_with_username }}</td>
    </tr>
    <tr>
        <td>{{ 'GeneralCoach' | get_lang}} :</td>
        <td>{{ general_coach.complete_name_with_username }}</td>
    </tr>
    {% if session_category  %}
    <tr>
        <td>{{ 'SessionCategory' | get_lang}} </td>
        <td>{{ session_category }}</td>
    </tr>
    {% endif %}

    {% if session.duration > 0 %}
    <tr>
        <td>{{ 'Duration' | get_lang}} </td>
        <td>
            {{ session.duration }} {{ 'Days' | get_lang }}
        </td>
    </tr>
    {% else %}
    <tr>
        <td>{{ 'DisplayDates' | get_lang}} </td>
        <td>{{ session_dates.display }}</td>
    </tr>
    <tr>
        <td>{{ 'AccessDates' | get_lang}} </td>
        <td>{{ session_dates.access }}</td>
    </tr>
    <tr>
        <td>{{ 'CoachDates' | get_lang}} </td>
        <td>{{ session_dates.coach }}</td>
    </tr>
    {% endif %}

    <tr>
        <td>{{ 'Description' | get_lang}} </td>
        <td>
            {{ session.description }}
        </td>
    </tr>
    <tr>
        <td>{{ 'ShowDescription' | get_lang}} </td>
        <td>
            {% if session.show_description == 1 %}
                {{ 'Yes' | get_lang}}
            {% else %}
                {{ 'No' | get_lang}}
            {% endif %}
        </td>
    </tr>
    <tr>
        <td>{{ 'SessionVisibility' | get_lang}} </td>
        <td>
            {{ session_visibility }}
        </td>
    </tr>
    {% if promotion %}
        <tr>
            <td>{{ 'Career' | get_lang}}</td>
            <td>
                <a href="{{ _p.web_main }}admin/career_dashboard.php?filter={{ promotion.career.id }}&submit=&_qf__filter_form=">{{ promotion.career.name }}</a>
            </td>
        </tr>
        <tr>
            <td>{{ 'Promotion' | get_lang}}</td>
            <td>
                <a href="{{ _p.web_main }}admin/promotions.php?action=edit&id={{ promotion.id }}">{{ promotion.name }}</a>
            </td>
        </tr>
    {% endif %}

    {% if url_list %}
        <tr>
            <td>URL</td>
            <td>
            {% for url in url_list %}
                {{ url.url }}
            {% endfor %}
            </td>
        </tr>
    {% endif %}

    {% for extra_field in extra_fields %}
        <tr>
            <td>{{ extra_field.text }}</td>
            <td>{{ extra_field.value }}</td>
        </tr>
    {% endfor %}

    {% if programmed_announcement %}
        <tr>
            <td>{{ 'ScheduledAnnouncements' | get_lang}}</td>
            <td>
                <a class="btn btn-default" href="{{ _p.web_main }}session/scheduled_announcement.php?session_id={{ session.id }}">
                    {{ 'Edit' | get_lang }}
                </a>
            </td>
        </tr>
    {% endif %}
</table>

{{ course_list }}
{{ user_list }}

{{ requirements }}
{{ dependencies }}
{% endblock %}
