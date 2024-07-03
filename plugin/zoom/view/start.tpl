{% if instant_meeting_form %}
    {{ instant_meeting_form }}
{% endif %}

{% if group_form %}
    {{ group_form }}
{% endif %}

{% if meetings.count %}
    <div class="page-header">
        <h2>{{ 'ScheduledMeetings'|get_lang }}</h2>
    </div>
    <table class="table">
        <tr>
            <th>{{ 'Type'|get_lang }}</th>
            <th>{{ 'Topic'|get_plugin_lang('ZoomPlugin') }}</th>
            <th>{{ 'Agenda'|get_plugin_lang('ZoomPlugin') }}</th>
            <th>{{ 'StartTime'|get_lang }}</th>
            <th>{{ 'Duration'|get_lang }}</th>
            <th>{{ 'Actions'|get_lang }}</th>
        </tr>
        {% for meeting in meetings %}
        <tr>
            <td>{{ meeting.typeName }}</td>
            <td>
                {{ meeting.meetingInfoGet.topic }}
                {{ meeting.webinarSchema.topic }}
            </td>
            <td>
                {{ meeting.meetingInfoGet.agenda|nl2br }}
                {{ meeting.webinarSchema.agenda|nl2br }}
            </td>
            <td>{{ meeting.formattedStartTime }}</td>
            <td>{{ meeting.formattedDuration }}</td>
            <td>
                <a class="btn btn-primary" href="join_meeting.php?meetingId={{ meeting.meetingId }}&{{ _p.web_cid_query }}">
                    {{ 'Join'|get_plugin_lang('ZoomPlugin') }}
                </a>

                {% if is_manager %}
                    <a class="btn btn-default" href="meeting.php?meetingId={{ meeting.meetingId }}&{{ _p.web_cid_query }}">
                        {{ 'Details'|get_plugin_lang('ZoomPlugin') }}
                    </a>

                    <a class="btn btn-danger"
                       href="start.php?action=delete&meetingId={{ meeting.meetingId }}&{{ _p.web_cid_query }}"
                       onclick="javascript:if(!confirm('{{ 'AreYouSureToDelete' | get_lang }}')) return false;"
                    >
                        {{ 'Delete'|get_lang }}
                    </a>
                {% endif %}
            </td>
        </tr>
        {% endfor %}
    </table>
{% endif %}

{% if schedule_meeting_form %}
    {{ schedule_meeting_form }}
{% endif %}