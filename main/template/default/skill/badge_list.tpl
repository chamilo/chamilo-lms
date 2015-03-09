{% extends "default/layout/main.tpl" %}

{% block body %}
    <div class="col-md-12">
        <div class="openbadges-tabs">
        <ul class="nav nav-tabs">
            <li>
                <a href="{{ _p.web_main }}admin/skill_badge.php">{{ 'Home' | get_lang }}</a>
            </li>
            <li class="active">
                <a href="{{ _p.web_main }}admin/skill_badge_list.php">{{ 'Insignias Actuales' | get_lang }}</a>
            </li>
        </ul>
        </div>
        <div class="tab-content">
            <div class="tab-pane active">
                <div class="openbadges-introduction">
                {% if not errorMessage is empty %}
                    <div class="alert alert-error">
                        {{ errorMessage }}
                    </div>
                {% endif %}
                <div class="openbadges-tablet">
                <table class="table">
                    <thead>
                        <tr>
                            <th>{{ 'Name' | get_lang }}</th>
                            <th>{{ 'Description' | get_lang }}</th>
                            <th>{{ 'Actions' | get_lang }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        {% for skill in skills %}
                            <tr>
                                <td>
                                    {% if skill.icon is empty %}
                                        <img src="{{ 'badges-default.png' | icon(128) }}" width="50" alt="{{ skill.name }}">
                                    {% else %}
                                        <img src="{{ [_p.web_data, skill.icon] | join('') }}" width="50" alt="{{ skill.name }}">
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
        </div>
    </div>
{% endblock %}
