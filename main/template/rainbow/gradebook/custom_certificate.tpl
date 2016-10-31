<h2>{{ 'Certificate' | get_lang }}</h2>

{{ complete_name }}

{{ 'UserHasParticipateDansDePlatformeXTheContratDateXCertificateDateXTimeX' | get_lang | format(_s.site_name, certificate_generated_date, terms_validation_date, time_in_platform)}}

{{ 'TheContentsAreValidated' | get_lang }}

{% if sessions %}
    <ul>
    {% for session in sessions %}
      <li>  {{ session.session_name }}</li>
    {% endfor %}
    </ul>
{% endif %}

{{ 'SkillsValidatedOfUserX' | get_lang | format(complete_name) }}

{% if skills %}
    <ul>
    {% for skill in skills %}
        <li>{{ skill.name }}</li>
    {% endfor %}
    </ul>
{% endif %}
