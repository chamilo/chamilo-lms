<h3>
    {{ 'MyMissingSignatures' | get_lang }}
</h3>
{% for presence in presences %}
    <h4>{{ presence.title }}</h4>
    <ul>
        {% for calendar in presence.calendars %}
            <li>{{ calendar.date_time }} {{ calendar.buttonToSign }}</li>
        {% endfor %}
    </ul>
{% endfor %}
