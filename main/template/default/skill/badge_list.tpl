<div class="col-md-12">

        <ul class="nav nav-tabs">
            <li>
                <a href="{{ _p.web_main }}admin/skill_badge.php">{{ 'Home' | get_lang }}</a>
            </li>
            <li class="active">
                <a href="{{ _p.web_main }}admin/skill_badge_list.php">{{ "CurrentBadges" | get_lang }}</a>
            </li>
        </ul>

    <div class="tab-content">
        <div class="tab-pane active">
            <div class="openbadges-introduction">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>{{ 'Badges' | get_lang }}</th>
                                <th>{{ 'Name' | get_lang }}</th>
                                <th>{{ 'Description' | get_lang }}</th>
                                <th>{{ 'Actions' | get_lang }}</th>
                            </tr>
                        </thead>
                        <tbody>
                        {% for skill in skills %}
                            <tr>
                                <td>
                                    {{ skill.img_small }}
                                </td>
                                <td>
                                    {{ skill.name }}
                                </td>
                                <td>{{ skill.description }}</td>
                                <td>
                                    <a href="{{ _p.web_main }}admin/skill_badge_create.php?id={{ skill.id }}" class="btn btn-primary btn-sm" title="{{ "CreateBadge" | get_lang }}">
                                        <em class="fa fa-shield fa-fw"></em>
                                    </a>
                                </td>
                            </tr>
                        {% endfor %}
                        </tbody>
                    </table>
            </div>
        </div>
    </div>
</div>
