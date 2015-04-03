<div class ="row">

{% if openmeetings_status == true %}
  {% if show_join_button == true %}
    <div class ="span12" style="text-align:center">
        <a href="{{ conference_url }}" target="_blank" class="btn btn-primary btn-large">
            {{ 'EnterConference'|get_lang }}
        </a>
        <!--span id="users_online" class="label label-warning">{{ 'XUsersOnLine'| get_lang | format(users_online) }} </span-->
    </div>
  {% endif %}

    <div class ="span12">
        <div class="page-header">
            <h2>{{ 'RecordList'|get_lang }}</h2>
        </div>

        <table class="table">
            <tr>
                <th>#</th>
                <th>{{ 'CreatedAt'|get_lang }}</th>
                <th>{{ 'Status'|get_lang }}</th>
                <th>{{ 'Records'|get_lang }}</th>

                {% if allow_to_edit  %}
                    <th>{{ 'Actions'|get_lang }}</th>
                {% endif %}

            </tr>
            {% for meeting in meetings %}
            <tr>
                <td>{{ meeting.id }}</td>
                <td>{{ meeting.created_at }}</td>
                <td>
                    {% if meeting.status == 1 %}
                        <span class="label label-success">{{ 'MeetingOpened'|get_lang }}</span>
                    {% else %}
                        <span class="label label-info">{{ 'MeetingClosed'|get_lang }}</span>
                    {% endif %}
                </td>
                <td>
                    {% if meeting.record == 1 %}
                        {# Record list #}
                        {{ meeting.show_links }}
                    {% endif %}
                </td>

                {% if allow_to_edit %}
                    <td>
                    {% if meeting.status == 1 %}
                        <a class="btn btn-default" href="{{ meeting.end_url }} "> {{ 'CloseMeeting' | get_lang }}</a>
                    {% endif %}
                    <a class="btn btn-danger" href="{{ meeting.delete_url }} "> {{ 'DeleteMeeting' | get_lang }}</a>
                    </td>
                {% endif %}

            </tr>
            {% endfor %}
        </table>
    </div>
{% else %}
    <div class ="span12" style="text-align:center">
        {{ 'ServerIsNotRunning' | return_message('warning') }}
    </div>
{% endif %}
</div>
