<header class="page-header">
    <h1>{{ "ManageSkills" | get_lang }}</h1>
</header>

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
            <th>{{ "ShortName" | get_lang }}</th>
            <th>{{ "Description" | get_lang }}</th>
            <th>{{ "Options" | get_lang }}</th>
        </tr>
    </tfoot>
    <tbody>
        {% for skill in skills %}
            <tr>
                <td>{{ skill.name }}</td>
                <td>{{ skill.short_code }}</td>
                <td>{{ skill.description }}</td>
                <td>
                    <a href="{{ _p.web_main }}admin/skill_edit.php?id={{ skill.id }}" class="btn btn-default">
                        <i class="fa fa-edit"></i> {{ "Edit" | get_lang }}
                    </a>
                    <a href="{{ _p.web_main }}admin/skill_create.php?parent={{ skill.id }}" class="btn btn-default">
                        <i class="fa fa-plus"></i> {{ "CreateChildSkill" | get_lang }}
                    </a>
                    <a href="{{ _p.web_main }}admin/skill_badge_create.php?id={{ skill.id }}" class="btn btn-default">
                        <i class="fa fa-plus"></i> {{ "CreateBadge" | get_lang }}
                    </a>
                </td>
            </tr>
        {% endfor %}
    </tbody>
</table>
