{{ form }}

<table class="table table-hover table-striped data_table">
    <tr>
        <th>{{ 'Name' | get_lang }}</th>
        <th>{{ 'SkillLevel' | get_lang }}</th>
        <th>{{ 'Actions' | get_lang }}</th>
    </tr>
{% for item in list %}
    <tr>
        <td>
            {{ item.name }}
        </td>
        <td>
            <ul>
                {% for level in item.levels %}
                    <li>
                        {{ level }}

                        {% if loop.first %}
                            <img src="{{ 'up_na.png'|icon(22) }}">
                        {% else %}
                            <a href="{{ _p.web_main }}admin/skill_profile.php?action=move_up&level_id={{ level.id }}&id={{ item.id }}">
                                <img src="{{ 'up.png'|icon(22) }}">
                            </a>
                        {% endif %}

                        {% if loop.last %}
                            <img src="{{ 'down_na.png'|icon(22) }}">
                        {% else %}
                            <a href="{{ _p.web_main }}admin/skill_profile.php?action=move_down&level_id={{ level.id }}&id={{ item.id }}">
                                <img src="{{ 'down.png'|icon(22) }}">
                            </a>
                        {% endif %}
                    </li>
                {% endfor %}
            </ul>
        </td>
        <td>
            <a href="{{ _p.web_main }}admin/skill_profile.php?action=edit&id={{ item.id }}">
                <img src="{{ 'edit.png'|icon(22) }}" alt="{{ 'Edit' | get_lang }}" title="{{ 'Edit' | get_lang }}"> </a>

            <a href="{{ _p.web_main }}admin/skill_profile.php?action=delete&id={{ item.id }}">
                <img src="{{ 'delete.png'|icon(22) }}" alt="{{ 'Delete' | get_lang }}" title="{{ 'Delete' | get_lang }}">
            </a>

            <a href="{{ _p.web_main }}admin/skill_level.php?action=add&profile_id={{ item.id }}">
                <img src="{{ 'add.png'|icon(22) }}" alt="{{ 'AddLevel' | get_lang }}" title="{{ 'AddLevel' | get_lang }}">
            </a>
        </td>
    </tr>
{% endfor %}

</table>