<legend>
    <h1>{{ "ManageSkills" | get_lang }}</h1>
</legend>

<div class="table table-responsive">
    <table class="table table-hover table-striped">
        <thead>
            <tr>
                <th>{{ "Badges" | get_lang }}</th>
                <th>{{ "Name" | get_lang }}</th>
                <th class="text-center">{{ "ShortCode" | get_lang }}</th>
                <th class="text-center">{{ "Description" | get_lang }}</th>
                <th class="text-center">{{ "Options" | get_lang }}</th>
            </tr>
        </thead>
        <tfoot>
            <tr>
                <th>{{ "Badges" | get_lang }}</th>
                <th>{{ "Name" | get_lang }}</th>
                <th class="text-center">{{ "ShortCode" | get_lang }}</th>
                <th class="text-center">{{ "Description" | get_lang }}</th>
                <th class="text-center">{{ "Options" | get_lang }}</th>
            </tr>
        </tfoot>
        <tbody>
            {% for skill in skills %}
                <tr>
                    <td width="50">
                        {% if skill.icon is empty %}
                            <img src="{{ 'badges-default.png' | icon(32) }}" width="32" height="32" alt="{{ skill.name }}">
                        {% else %}
                            <img src="{{ skill.web_icon_path }}" width="32" height="32" alt="{{ skill.name }}">
                        {% endif %}

                    </td>
                    <td width="200">{{ skill.name }}</td>
                    <td class="text-center">{{ skill.short_code }}</td>
                    <td width="500">{{ skill.description }}</td>
                    <td class="text-right">
                        <a href="{{ _p.web_main }}admin/skill_edit.php?id={{ skill.id }}" class="btn btn-primary btn-sm" title="{{ "Edit" | get_lang }}">
                            <em class="fa fa-edit fa-fw"></em>
                        </a>
                        <a href="{{ _p.web_main }}admin/skill_create.php?parent={{ skill.id }}" class="btn btn-primary btn-sm" title="{{ "CreateChildSkill" | get_lang }}">
                            <em class="fa fa-plus fa-fw"></em>
                        </a>
                        <a href="{{ _p.web_main }}admin/skill_badge_create.php?id={{ skill.id }}" class="btn btn-primary btn-sm" title="{{ "CreateBadge" | get_lang }}">
                            <em class="fa fa-shield fa-fw"></em>
                        </a>

                        {% if skill.status == 0 %}
                            <a href="{{ _p.web_self ~ '?' ~ {"action": "enable", "id": skill.id}|url_encode() }}" class="btn btn-success btn-sm" title="{{ 'Enable' }}">
                                <em class="fa fa-check-circle-o fa-fw"></em>
                            </a>
                        {% else %}
                            <a href="{{ _p.web_self ~ '?' ~ {"action": "disable", "id": skill.id}|url_encode() }}" class="btn btn-danger btn-sm" title="{{ 'Disable' }}">
                                <em class="fa fa-ban fa-fw"></em>
                            </a>
                        {% endif %}
                    </td>
                </tr>
            {% endfor %}
        </tbody>
    </table>
</div>
