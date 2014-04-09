<table class="data_table">
    <tr>
        <th>
            {{ 'StartDate'| get_lang }}
        </th>
        <th>
            {{ 'EndDate'| get_lang }}
        </th>
        <th>
            {{ 'Title' | get_lang }}
        </th>
        {% if is_allowed_to_edit %}
            <th>
                {{ 'Actions' | get_lang }}
            </th>
        {% endif %}
    </tr>
    {% for event in agenda_events %}
        <tr>
            <td style="width:20%">
                {{ event.start_date_localtime }}
            </td>
            <td style="width:20%">
                {% if event.allDay %}
                    {{ 'AllDay' | get_lang }}
                {% else %}
                    {{ event.end_date_localtime }}
                {% endif %}
            </td>
            <td style="width:50%">
                {{ event.title }}
                <p>{{ event.description}}</p>
                {{ event.attachment }}
            </td>

            {% if is_allowed_to_edit %}
                <td>
                    {% if event.visibility == 1 %}
                        <a class="btn" href="{{ url }}&action=change_visibility&visibility=0&id={{ event.real_id }}">
                            <img title="{{ 'Invisible' }}" src="{{'visible.png'|icon(32)}} ">
                        </a>
                    {% else %}
                        <a class="btn" href="{{ url }}&action=change_visibility&visibility=1&id={{ event.real_id }}">
                            <img title="{{ 'Visible' }}" src="{{'invisible.png'|icon(32)}} ">
                        </a>
                    {% endif %}
                </td>
            {% endif %}

        </tr>
    {% endfor %}
</table>
