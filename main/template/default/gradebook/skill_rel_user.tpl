
<h3>{{ user.complete_name_with_username }}</h3>
<br />
<table class="table table-striped">
    <tr>
        <th>{{ 'Skill' | get_lang }}</th>
        <th>{{ 'Occurrences' | get_lang }}</th>
        <th>{{ 'Conclusion' | get_lang }}</th>
    </tr>

    {% for skill in skills %}
    <tr>
        <td>{{ skill.name }}</td>
        <td>
            {% for item in items[skill.id] %}
                {% set status = 'danger' %}
                {% if item.info.status %}
                    {% set status = 'success' %}
                {% endif %}
                <span class="label label-{{ status }}">
                    {{ item.info.name }}
                </span>                &nbsp;
            {% endfor %}
        </td>
        <td>
            {% if conclusion_list[skill.id] %}
                <span class="label label-success">
                    {{ 'Achieved' }}
                </span>
            {% else %}
                <span class="label label-danger">
                    {{ 'NotYetAchieved' }}
                </span>
            {% endif %}
        </td>
    </tr>
    {% endfor %}
</table>