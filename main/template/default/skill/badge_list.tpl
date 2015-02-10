{% extends "default/layout/main.tpl" %}

{% block body %}
    <div class="span12">
        <h1 class="page-header">{{ 'Badges' | get_lang }}</h1>
        <ul class="nav nav-tabs">
            <li>
                <a href="{{ _p.web_main }}admin/skill_badge.php">{{ 'Home' | get_lang }}</a>
            </li>
            <li>
                <a href="{{ _p.web_main }}admin/skill_badge_issuer.php">{{ 'IssuerDetails' | get_lang }}</a>
            </li>
            <li class="active">
                <a href="{{ _p.web_main }}admin/skill_badge_list.php">{{ 'Skills' | get_lang }}</a>
            </li>
        </ul>
        <div class="tab-content">
            <div class="tab-pane active">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>{{ 'Name' | get_lang }}</th>
                            <th>{{ 'Description' | get_lang }}</th>
                            <th>{{ 'Actions' | get_lang }}</th>
                        </tr>
                    </thead>
                    <tfoot>
                        <tr>
                            <th>{{ 'Name' | get_lang }}</th>
                            <th>{{ 'Description' | get_lang }}</th>
                            <th>{{ 'Actions' | get_lang }}</th>
                        </tr>
                    </tfoot>
                    <tbody>
                        {% for skill in skills %}
                            <tr>
                                <td>
                                    {% if skill.icon %}
                                        <img src="{{ [_p.web, skill.icon] | join('') }}" width="50" alt="{{ skill.name }}">
                                    {% endif %}
                                    {{ skill.name }}
                                </td>
                                <td>{{ skill.description }}</td>
                                <td>
                                    <a href="{{ _p.web_main }}admin/skill_badge_create.php?id={{ skill.id }}" title="{{ 'Edit' | get_lang }}">
                                        <img src="{{ _p.web_img }}icons/22/edit.png" alt="{{ 'Edit' | get_lang }}">
                                    </a>
                                </td>
                            </tr>
                        {% endfor %}
                    </tbody>
                </table>
            </div>
        </div>
    </div>
{% endblock %}
