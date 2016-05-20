{{ form }}

{% for item in list %}

    <h4>{{ item.name }}

        {#<a href="{{ _p.web_main }}admin/skill_level.php?action=add_level&id={{ item.id }}">#}
        {#<img src="{{ 'add.png'|icon(22) }}">#}
        {#</a>#}

        <a href="{{ _p.web_main }}admin/skill_profile.php?action=edit&id={{ item.id }}">
            <img src="{{ 'edit.png'|icon(22) }}"> </a>

        <a href="{{ _p.web_main }}admin/skill_profile.php?action=delete&id={{ item.id }}">
            <img src="{{ 'delete.png'|icon(22) }}">
        </a>
    </h4>

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
{% endfor %}
