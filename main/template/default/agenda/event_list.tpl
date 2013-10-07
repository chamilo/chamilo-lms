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
    </tr>
    {% for event in agenda_events %}
        <tr>
            <td>
                {{ event.start }}
            </td>
            <td>
                {% if event.allDay %}
                    {{ 'AllDay' | get_lang }}
                {% else %}
                    {{ event.start }} - {{ event.end }}
                {% endif %}
            </td>
            <td>
                {{ event.title }}
                <p>{{ event.description}}</p>
                {{ event.className }}
            </td>

        </tr>
    {% endfor %}
</table>
