{% if profiles %}
    <h4>{{ "SkillProfiles"|get_lang }}</h4>
    <table class="table table-responsive table-condensed">
        <tbody>
            {%for profile in profiles %}
                <tr>
                    <td>{{ profile.name }}</td>
                    <td class="text-right">
                        <div class="btn-group btn-group-sm">
                            <button class="btn btn-default btn-sm skill-wheel-load-profile" aria-label="{{ 'Search'|get_lang }}" title="{{ 'Search'|get_lang }}" data-id="{{ profile.id }}">
                                <span class="fa fa-search" aria-hidden="true"></span>
                            </button>
                            <button class="btn btn-default btn-sm skill-wheel-delete-profile" aria-label="{{ 'Delete'|get_lang }}" title="{{ 'Delete'|get_lang }}" data-id="{{ profile.id }}">
                                <span class="fa fa-trash" aria-hidden="true"></span>
                            </button>
                        </div>
                    </td>
                </tr>
            {% endfor %}
        </tbody>
    </table>
{% endif %}
