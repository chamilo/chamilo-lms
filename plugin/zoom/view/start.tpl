{% if createInstantMeetingForm %}
    {{ createInstantMeetingForm }}
{% endif %}

{% if scheduledMeetings %}
    <div class="page-header">
        <h2>{{ 'ScheduledMeetings'|get_lang }}</h2>
    </div>
    <table class="table">
        <tr>
            <!-- th>{{ 'CreatedAt'|get_lang }}</th -->
            <th>{{ 'StartTime'|get_lang }}</th>
            <th>{{ 'Duration'|get_lang }}</th>
            <!-- th>{{ 'Type'|get_lang }}</th -->
            <th>{{ 'TopicAndAgenda'|get_plugin_lang('ZoomPlugin') }}</th>
            <th></th>
        </tr>
        {% for meeting in scheduledMeetings %}
        <tr>
            <!-- td>{{ meeting.created_at }}</td -->
            <td>{{ meeting.formattedStartTime }}</td>
            <td>{{ meeting.formattedDuration }}</td>
            <!-- td>{{ meeting.typeName }}</td -->
            <td>
                <strong>{{ meeting.meetingInfoGet.topic }}</strong>
                <p class="small">{{ meeting.meetingInfoGet.agenda|nl2br }}</p>
            </td>
            <td>
                <a class="btn" href="meeting_from_start.php?meetingId={{ meeting.id }}">
                    {{ 'Details'|get_lang }}
                </a>
                <a class="btn" href="{{ meeting.meetingInfoGet.join_url }}">
                    {{ 'Join'|get_plugin_lang('ZoomPlugin') }}
                </a>
            </td>
        </tr>
        {% endfor %}
    </table>
{% else %}
<!-- p>No scheduled meeting currently</p -->
{% endif %}

{% if scheduleMeetingForm %}
    <h3>{{ 'ScheduleAMeeting'|get_plugin_lang('ZoomPlugin') }}</h3>
    {{ scheduleMeetingForm }}
{% endif %}