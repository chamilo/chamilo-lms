{{ settings_form }}

{{ 'RecordList'|get_plugin_lang('BBBPlugin') }}

{{ search_form }}
<table class="table table-hover table-striped">
    <thead>
        <tr>
            <th>{{ 'DateStart'|get_lang }}</th>
            <th>{{ 'DateEnd'|get_lang }}</th>
            <th>{{ 'Status'|get_lang }}</th>
            <th>{{ 'Records'|get_plugin_lang('BBBPlugin') }}</th>
            <th>{{ 'Course'|get_lang }}</th>
            <th>{{ 'Session'|get_lang }}</th>
            <th>{{ 'Participants'|get_lang }}</th>
            <th>{{ 'CountUsers'|get_lang }}</th>
            <th>{{ 'Actions'|get_lang }}</th>
        </tr>
    </thead>
        <tbody>
        {% for meeting in meetings %}
            <tr id="meeting-{{ meeting.id }}">
                {% if meeting.visibility == 0 %}
                    <td class="muted">{{ meeting.created_at }}</td>
                {% else %}
                    <td>{{ meeting.created_at }}</td>
                {% endif %}
                <td>{{ meeting.closed_at }}</td>
                <td>
                    {% if meeting.status == 1 %}
                        <span class="label label-success">{{ 'MeetingOpened'|get_plugin_lang('BBBPlugin') }}</span>
                    {% else %}
                        <span class="label label-info">{{ 'MeetingClosed'|get_plugin_lang('BBBPlugin') }}</span>
                    {% endif %}
                </td>
                <td>
                    {% if meeting.record == 1 %}
                        {# Record list #}
                        {{ meeting.show_links }}
                    {% else %}
                        {{ 'NoRecording'|get_plugin_lang('BBBPlugin') }}
                    {% endif %}
                </td>
                <td>{{ meeting.course ?: '-' }}</td>
                <td>{{ meeting.session ?: '-' }}</td>
                <td>
                    {{ meeting.participants ? meeting.participants|join('<br>') : '-' }}
                </td>
                <td>
                    {{ meeting.participants ? meeting.participants | length : 0 }}
                </td>
                <td>
                    {{ meeting.action_links }}
                </td>
            </tr>
        {% endfor %}
    </tbody>
</table>
