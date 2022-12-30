<h3>{{ 'Skills' | get_lang }}</h3>
<ul>
    <li>
        <a href="{{ _p.web_main }}{{ 'admin/skill_profile.php' }}">
            {{ 'SkillLevelProfiles' | get_lang }}
        </a>
    </li>
    <li>
        <a href="{{ _p.web_main }}{{ 'admin/skill_level.php' }}">
            {{ 'SkillLevels' | get_lang }}
        </a>
    </li>
</ul>

{{ form }}
<table class="table table-hover table-striped data_table">
    <tr>
        <th>
            {{ 'Name' | get_lang }}
        </th>
        <th>
            {{ 'Profile' | get_lang }}
        </th>
        <th>
            {{ 'Actions' | get_lang }}
        </th>
    </tr>
{% for item in list %}
    <tr>
        <td>
        {{ item.name }}
        {% if item.shortCode %}
            ({{ item.shortCode }})
        {% endif %}
        </td>
        <td>
        {{ item.profile }}
        </td>
        <td>
        <a href="{{ _p.web_main }}admin/skill.php?action=edit&id={{ item.id }}">
            <img src="{{ 'edit.png'|icon(22) }}">
        </a>
        </td>
    </tr>

{% endfor %}
</table>