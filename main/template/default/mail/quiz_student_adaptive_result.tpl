<p><strong>{{ result.user.completeName }}</strong></p>
{% if not course_info is empty %}
    <p><strong>{{ course_info.course.title }}</strong></p>
    {% if not course_info.fields is empty %}
        <table border="0" cellpadding="1" cellspacing="0">
            {% for display_text, value in course_info.fields %}
                <tr>
                    <td style="padding-right: 10px;">{{ display_text }}</td>
                    <td>{{ value }}</td>
                </tr>
            {% endfor %}
        </table>
    {% endif %}
{% endif %}
<br>
{% if not session_info is empty %}
    <p><strong>{{ session_info.session.name }}</strong></p>
    {% if not session_info.fields is empty %}
        <table border="0" cellpadding="1" cellspacing="0">
            {% for display_text, value in session_info.fields %}
                <tr>
                    <td style="padding-right: 10px;">{{ display_text }}</td>
                    <td>{{ value }}</td>
                </tr>
            {% endfor %}
        </table>
    {% endif %}
    <br>
{% endif %}
<table border="0" cellpadding="1" cellspacing="0" style="border: 0 none;">
    <tr>
        <td style="padding-right: 10px;">{{ 'Username'|get_lang }}</td>
        <td>{{ result.user.username }}</td>
    </tr>
    <tr>
        <td style="padding-right: 10px;">{{ 'StartDate'|get_lang }}</td>
        <td>{{ result.exe.exeDate|api_convert_and_format_date }}</td>
    </tr>
    <tr>
        <td style="padding-right: 10px;">{{ 'Duration'|get_lang }}</td>
        <td>{{ exe_duration }}</td>
    </tr>
    <tr>
        <td style="padding-right: 10px;">{{ 'IP'|get_lang }}</td>
        <td>{{ result.exe.userIp }}</td>
    </tr>
</table>
<p>{{ 'LevelReachedX'|get_lang|format(result.achievedLevel) }}</p>
<p>
    <img src="{{ qr }}" alt="{{ 'ResultHashX'|get_lang|format(result.hash) }}">
    <br>
    <span>{{ 'ResultHashX'|get_lang|format(result.hash) }}</span>
</p>
