<h4>
    {{ meeting.typeName }} {{ meeting.meetingId }}
</h4>

<a class="btn btn-primary" href="meeting.php?meetingId={{ meeting.meetingId }}&{{ url_extra }}">
    {{ 'Edit'|get_lang }}
</a>

<table class="table">
    <thead>
        <tr>
            <th>{{ 'Type'|get_lang }}</th>
            <th>{{ 'Action'|get_plugin_lang('ZoomPlugin') }}</th>
{#            <th>{{ 'User'|get_lang }}</th>#}
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
                {{ activity.createdAt | api_convert_and_format_date(3)}}
            </td>
{#            <td>#}
{#                {% if _u.is_admin %}#}
{#                    <a href="{{ _p.web_main }}admin/user_information.php?user_id={{ activity.user.id }}" >#}
{#                        {{ activity.user.firstname }} {{ activity.user.lastname }} ({{ activity.user.username }})#}
{#                    </a>#}
{#                {% else %}#}
{#                    {{ activity.user.firstname }} {{ activity.user.lastname }} ({{ activity.user.username }})#}
{#                {% endif %}#}
{#            </td>#}
            <td>
                {% if activity.eventDecoded.registrant %}
                    {{ 'User' | get_lang }} :
                    {{ activity.eventDecoded.registrant.first_name }} -
                    {{ activity.eventDecoded.registrant.last_name }} -
                    {{ activity.eventDecoded.registrant.email }} -
                    {{ activity.eventDecoded.registrant.status }}
                {% endif %}

                {% if activity.eventDecoded.participant %}
                    {{ 'User' | get_lang }} :
                    {{ activity.eventDecoded.participant.user_name }}
                {% endif %}
            </td>
        </tr>
    {% endfor %}
    </tbody>
</table>