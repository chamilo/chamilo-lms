
{% import "default/document/recycle.tpl" as macro %}

{{ schedule_form }}

{{ search_form }}

{% if meetings.count %}
    <h4>{{ 'MeetingsFound'|get_plugin_lang('ZoomPlugin') }}: </h4>
    <table class="table table-hover table-striped">
        <thead>
            <tr>
                <th>{{ 'Topic'|get_plugin_lang('ZoomPlugin') }}</th>
                <th>{{ 'StartTime'|get_lang }}</th>
                <th>{{ 'ForEveryone'|get_plugin_lang('ZoomPlugin') }}</th>
{#                <th>{{ 'Course'|get_lang }}</th>#}
{#                <th>{{ 'Session'|get_lang }}</th>#}
                {% if recordings %}
                <th>{{ 'Recordings'|get_plugin_lang('ZoomPlugin') }}</th>
                {% endif %}
                <th></th>
            </tr>
        </thead>
            <tbody>
            {% for meeting in meetings %}
                <tr>
                    <td>{{ meeting.meetingInfoGet.topic }}</td>
                    <td>{{ meeting.formattedStartTime }}</td>
                    <td>{{ meeting.user ? 'No' : 'Yes' }}</td>
{#                    <td>{{ meeting.course ? meeting.course : '-' }}</td>#}
{#                    <td>{{ meeting.session ? meeting.session : '-' }}</td>#}
                    {% if recordings %}
                        <td>
                            {% for recording in recordings %}
                                {% if recording.recordingMeeting.id == meeting.meetingId %}
                                <dl>
                                    <dt>
                                        {{ recording.formattedStartTime }}
                                        ({{ recording.formattedDuration }})
                                    </dt>
                                    <dd>
                                        <ul>
                                            {% for file in recording.recordingMeeting.recording_files %}
                                            <li>
                                                <a href="{{ file.play_url }}" target="_blank">
                                                    {{ file.recording_type }}.{{ file.file_type }}
                                                    ({{ macro.bytesToSize(file.file_size) }})
                                                </a>
                                            </li>
                                            {% endfor %}
                                        </ul>
                                    </dd>
                                </dl>
                                {% endif %}
                            {% endfor %}
                        </td>
                    {% endif %}
                    <td>
                        <a class="btn btn-primary" href="meeting.php?meetingId={{ meeting.meetingId }}">
                            {{ 'Details'|get_lang }}
                        </a>
                    </td>
                </tr>
            {% endfor %}
        </tbody>
    </table>
{% endif %}