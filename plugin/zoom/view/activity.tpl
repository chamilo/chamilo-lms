<h4>
    {{ meeting.typeName }} {{ meeting.meetingId }}
</h4>

<a class="btn btn-primary" href="meeting.php?meetingId={{ meeting.meetingId }}">
    {{ 'Edit'|get_lang }}
</a>

<table class="table">
    <thead>
        <tr>
            <th>{{ 'Type'|get_lang }}</th>
            <th>{{ 'Action'|get_plugin_lang('ZoomPlugin') }}</th>
            <th>{{ 'Date'|get_lang }}</th>
            <th>{{ 'Details'|get_lang }} </th>
        </tr>
    </thead>
    <tbody>
    {% for activity in meeting.activities %}
        <tr>
            <td>
                {{ activity.type }}
            </td>
            <td>
                {{ activity.name }}
            </td>
            <td>
                {{ activity.createdAt }}
            </td>
            <td>
                {% if activity.eventDecoded.registrant %}
                    {{ 'User' | get_lang }} :
                    {{ activity.eventDecoded.registrant.first_name }} -
                    {{ activity.eventDecoded.registrant.last_name }} -
                    {{ activity.eventDecoded.registrant.email }} -
                    {{ activity.eventDecoded.registrant.status }}
                {% endif %}
            </td>
        </tr>
    {% endfor %}
    </tbody>
</table>