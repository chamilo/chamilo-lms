{% if createInstantMeetingForm %}
    {{ createInstantMeetingForm }}
{% endif %}

{% if scheduledMeetings.count %}
    <div class="page-header">
        <h2>{{ 'ScheduledMeetings'|get_lang }}</h2>
    </div>
    <table class="table">
        <tr>
            <th>{{ 'Topic'|get_plugin_lang('ZoomPlugin') }}</th>
            <th>{{ 'Agenda'|get_plugin_lang('ZoomPlugin') }}</th>
            <th>{{ 'StartTime'|get_lang }}</th>
            <th>{{ 'Duration'|get_lang }}</th>
            <th>{{ 'Actions'|get_lang }}</th>
        </tr>
        {% for meeting in scheduledMeetings %}
        <tr>
            <td>
                {{ meeting.meetingInfoGet.topic }}
            </td>
            <td>
                {{ meeting.meetingInfoGet.agenda|nl2br }}
            </td>
            <td>{{ meeting.formattedStartTime }}</td>
            <td>{{ meeting.formattedDuration }}</td>
            <td>
                <a class="btn btn-primary" href="{{ meeting.meetingInfoGet.join_url }}">
                    {{ 'Join'|get_plugin_lang('ZoomPlugin') }}
                </a>

                <a class="btn btn-default" href="meeting.php?type=start&meetingId={{ meeting.meetingId }}">
                    {{ 'Edit'|get_lang }}
                </a>

                <a class="btn btn-danger"
                   href="start.php?action=delete&meetingId={{ meeting.meetingId }}"
                   onclick="javascript:if(!confirm('{{ 'AreYouSureToDelete' | get_lang }}')) return false;"
                >
                    {{ 'Delete'|get_lang }}
                </a>
            </td>
        </tr>
        {% endfor %}
    </table>
{% else %}
<!-- p>No scheduled meeting currently</p -->
{% endif %}

{% if scheduleMeetingForm %}
    {{ scheduleMeetingForm }}
{% endif %}