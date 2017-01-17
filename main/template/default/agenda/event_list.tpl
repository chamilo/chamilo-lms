{{ agenda_actions }}


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
        {% if is_allowed_to_edit and show_action %}
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

                {% if event.description %}
                    <p>{{ event.description}}</p>
                {% endif %}

                {% if event.comment %}
                    <p>{{ event.comment}}</p>
                {% endif %}

                {{ event.attachment }}
            </td>

            {% if is_allowed_to_edit and show_action %}
                <td>
                    {% if event.visibility == 1 %}
                        <a class="btn btn-default" href="{% if url %}{{ url }}{% else %}{{ event.url }}{% endif %}&action=change_visibility&visibility=0&id={{ event.real_id }}&type={{ event.type }}">
                            <img title="{{ 'Invisible' }}" src="{{'visible.png'|icon(32)}} " width="32" height="32">
                        </a>
                    {% else %}
                        {% if event.type == 'course' or event.type == 'session' %}
                            <a class="btn btn-default" href="{% if url %}{{ url }}{% else %}{{ event.url }}{% endif %}&action=change_visibility&visibility=1&id={{ event.real_id }}&type={{ event.type }}">
                                <img title="{{ 'Visible' }}" src="{{'invisible.png'|icon(32)}} " width="32" height="32">
                            </a>
                        {% endif %}
                    {% endif %}
                </td>
            {% endif %}
        </tr>
    {% endfor %}
</table>
