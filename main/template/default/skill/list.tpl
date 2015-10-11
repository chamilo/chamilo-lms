<legend>
    <h1>{{ "ManageSkills" | get_lang }}</h1>
</legend>

<div class="table table-responsive">
    <table class="table table-hover table-striped">
        <thead>
            <tr>
                <th>{{ "Name" | get_lang }}</th>
                <th>{{ "ShortCode" | get_lang }}</th>
                <th>{{ "Description" | get_lang }}</th>
                <th>{{ "Options" | get_lang }}</th>
            </tr>
        </thead>
        <tfoot>
            <tr>
                <th>{{ "Name" | get_lang }}</th>
                <th>{{ "ShortCode" | get_lang }}</th>
                <th>{{ "Description" | get_lang }}</th>
                <th>{{ "Options" | get_lang }}</th>
            </tr>
        </tfoot>
        <tbody>
            {% for skill in skills %}
                <tr>
                    <td width="200">{{ skill.name }}</td>
                    <td class="text-center">{{ skill.short_code }}</td>
                    <td width="300">{{ skill.description }}</td>
                    <td class="text-right">
                        <a href="{{ _p.web_main }}admin/skill_edit.php?id={{ skill.id }}" class="btn btn-primary btn-sm">
                            <i class="fa fa-edit fa-fw"></i> {{ "Edit" | get_lang }}
                        </a>
                        <a href="{{ _p.web_main }}admin/skill_create.php?parent={{ skill.id }}" class="btn btn-primary btn-sm">
                            <i class="fa fa-plus fa-fw"></i> {{ "CreateChildSkill" | get_lang }}
                        </a>
                        <a href="{{ _p.web_main }}admin/skill_badge_create.php?id={{ skill.id }}" class="btn btn-primary btn-sm">
                            <i class="fa fa-shield fa-fw"></i> {{ "CreateBadge" | get_lang }}
                        </a>

                        {% if skill.status == 0 %}
                            <a href="{{ _p.web_self ~ '?' ~ {"action": "enable", "id": skill.id}|url_encode() }}" class="btn btn-success btn-sm">
                                <i class="fa fa-check-circle-o fa-fw"></i> {{ 'Enable' }}
                            </a>
                        {% else %}
                            <a href="{{ _p.web_self ~ '?' ~ {"action": "disable", "id": skill.id}|url_encode() }}" class="btn btn-danger btn-sm">
                                <i class="fa fa-ban fa-fw"></i> {{ 'Disable' }}
                            </a>
                        {% endif %}
                    </td>
                </tr>
            {% endfor %}
        </tbody>
    </table>
</div>
