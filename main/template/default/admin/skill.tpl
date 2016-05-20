
<h3>{{ 'Skills' | get_lang }}</h3>

<ul>
    <li>
        <a href="{{ _p.web_main }}{{ 'admin/skill_profile.php' }}">
            {{ 'AddProfile' | get_lang }}
        </a>
    </li>

    <li>
        <a href="{{ _p.web_main }}{{ 'admin/skill_level.php' }}">
            {{ 'AddLevel' | get_lang }}
        </a>
    </li>
</ul>


{{ form }}



{% for item in list %}
    <h4>{{ item.name }} ({{ item.shortCode }})
        <a href="{{ _p.web_main }}admin/skill.php?action=edit&id={{ item.id }}">
            <img src="{{ 'edit.png'|icon(22) }}">
        </a>
    </h4>
    {{ item.profile }}
{% endfor %}
