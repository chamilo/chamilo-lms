<h4>
    {{ meeting.typeName }} {{ meeting.meetingId }}
</h4>

<table class="table">
    <thead>
    <tr>
        <th>{{ 'Name'|get_plugin_lang('ZoomPlugin') }}</th>
        <th>{{ 'Type'|get_lang }}</th>
        <th>{{ 'Event'|get_lang }} </th>
    </tr>
    </thead>
    <tbody>
    {% for activity in meeting.activities %}
        <tr>
            <td>
                {{ activity.name }}
            </td>
            <td>
                {{ activity.type }}
            </td>
            <td>
                {{ activity.eventDecoded }}
            </td>
        </tr>
    {% endfor %}
    </tbody>
</table>